<?php

namespace App\Services;

use App\Models\DetailPenjualan;
use App\Models\Produk;
use App\Models\RiwayatKoreksiNota;
use App\Models\RiwayatStok;
use App\Models\TransaksiPenjualan;
use Illuminate\Support\Facades\DB;
use Exception;

class AdjustmentService
{
    /**
     * Mengkoreksi jumlah barang pada detail penjualan yang sudah selesai.
     * Otomatis menyesuaikan stok gudang dan mencatat audit trail.
     *
     * Untuk barang dual-unit (dijual per Meter), stok gudang dilacak dalam KG.
     * Maka penyesuaian stok dihitung dari selisih KG fisik (jumlah_potong_gudang),
     * BUKAN selisih qty meter di nota.
     *
     * @param int        $idDetail         ID detail_penjualan yang akan dikoreksi
     * @param float      $newQty           Jumlah baru (qty yang tampil di nota: Meter/KG/Pcs)
     * @param float|null $newPotongGudang  KG fisik baru yang dipotong gudang (WAJIB untuk barang Meter; null = ikut $newQty)
     * @param string     $reason           Alasan koreksi (wajib)
     * @param int        $userId           ID user yang melakukan koreksi
     * @return RiwayatKoreksiNota
     * @throws Exception
     */
    public function adjustDetailPenjualan(int $idDetail, float $newQty, ?float $newPotongGudang, string $reason, int $userId): RiwayatKoreksiNota
    {
        return DB::transaction(function () use ($idDetail, $newQty, $newPotongGudang, $reason, $userId) {

            // 1. Pessimistic Locking — kunci baris agar tidak bisa diubah proses lain
            $detail = DetailPenjualan::lockForUpdate()->find($idDetail);
            if (!$detail) {
                throw new Exception("Detail penjualan tidak ditemukan.");
            }

            $transaksi = TransaksiPenjualan::lockForUpdate()->find($detail->id_transaksi_penjualan);
            if (!$transaksi) {
                throw new Exception("Transaksi penjualan tidak ditemukan.");
            }

            $produk = Produk::lockForUpdate()->find($detail->id_produk);
            if (!$produk) {
                throw new Exception("Produk tidak ditemukan.");
            }

            // 2. Validasi qty nota
            $oldQty = (float) $detail->jumlah;

            if ($newQty <= 0) {
                throw new Exception("Kuantitas baru harus lebih dari 0.");
            }

            // Qty baru tidak boleh lebih kecil dari jumlah yang sudah diretur
            if ($newQty < (float) $detail->jumlah_diretur) {
                throw new Exception("Kuantitas baru ({$newQty}) tidak boleh lebih kecil dari barang yang sudah diretur (" . ($detail->jumlah_diretur + 0) . ").");
            }

            // 3. Tentukan basis KG fisik untuk penyesuaian stok
            $isDualUnit = strtolower($detail->satuan_saat_jual) === 'meter';

            if ($isDualUnit) {
                // Barang Meter: stok gudang dalam KG, butuh KG fisik baru hasil timbang ulang
                $newKg = $newPotongGudang;
                if ($newKg === null || $newKg <= 0) {
                    throw new Exception("Barang dijual per Meter, wajib isi berat timbangan (KG) yang baru.");
                }
                // KG lama: pakai potong_gudang; fallback ke jumlah untuk data lama yang belum punya kolom ini
                $oldKg = $detail->jumlah_potong_gudang !== null ? (float) $detail->jumlah_potong_gudang : $oldQty;
            } else {
                // Barang biasa (KG/Pcs): KG fisik = qty nota
                $newKg = $newQty;
                $oldKg = $detail->jumlah_potong_gudang !== null ? (float) $detail->jumlah_potong_gudang : $oldQty;
            }

            // 4. Pastikan ada perubahan (qty nota maupun KG fisik)
            if ($newQty == $oldQty && $newKg == $oldKg) {
                throw new Exception("Tidak ada perubahan jumlah.");
            }

            // 5. Penyesuaian stok gudang berbasis KG (HANYA jika produk melacak stok)
            $selisihStok = $newKg - $oldKg; // (+) qty naik → stok turun, (-) qty turun → stok naik

            if ($produk->lacak_stok && $selisihStok != 0) {
                $stokSebelum = (float) $produk->stok_saat_ini;
                $absSelisih = abs($selisihStok);

                if ($selisihStok > 0) {
                    // KG BERTAMBAH di nota → stok gudang BERKURANG
                    if ($stokSebelum < $absSelisih) {
                        throw new Exception("Stok gudang tidak mencukupi. Sisa stok: " . ($stokSebelum + 0) . " " . strtoupper($produk->satuan) . ", dibutuhkan: " . ($absSelisih + 0) . ".");
                    }
                    $stokSesudah = $stokSebelum - $absSelisih;
                    $tipeRiwayat = 'KOREKSI_MINUS';
                } else {
                    // KG BERKURANG di nota → stok gudang BERTAMBAH
                    $stokSesudah = $stokSebelum + $absSelisih;
                    $tipeRiwayat = 'KOREKSI_PLUS';
                }

                // Update stok produk
                $produk->update(['stok_saat_ini' => $stokSesudah]);

                // Catat di RiwayatStok
                RiwayatStok::create([
                    'id_produk'               => $produk->id_produk,
                    'user_id'                 => $userId,
                    'id_transaksi_penjualan'  => $transaksi->id_transaksi_penjualan,
                    'tipe'                    => $tipeRiwayat,
                    'jumlah'                  => $absSelisih,
                    'stok_sebelum'            => $stokSebelum,
                    'stok_sesudah'            => $stokSesudah,
                    'keterangan'              => "Koreksi Nota [{$transaksi->kode_nota}]: {$reason}",
                ]);
            }

            // 6. Simpan data sebelum untuk audit trail
            $subtotalSebelum = (float) $detail->subtotal;
            $hargaSatuan = (float) $detail->harga_satuan;

            // 7. Update DetailPenjualan (qty nota, KG fisik, subtotal)
            $subtotalBaru = $newQty * $hargaSatuan;
            $detail->update([
                'jumlah'               => $newQty,
                'jumlah_potong_gudang' => $newKg,
                'subtotal'             => $subtotalBaru,
            ]);

            // 8. Recalculate total_harga pada TransaksiPenjualan
            $totalBaru = DetailPenjualan::where('id_transaksi_penjualan', $transaksi->id_transaksi_penjualan)
                ->sum('subtotal');
            $transaksi->update(['total_harga' => $totalBaru]);

            // 9. AUDIT TRAIL — Catat koreksi nota
            $koreksi = RiwayatKoreksiNota::create([
                'id_transaksi_penjualan' => $transaksi->id_transaksi_penjualan,
                'id_detail_penjualan'    => $detail->id_detail_penjualan,
                'id_produk'              => $detail->id_produk,
                'user_id'                => $userId,
                'qty_sebelum'            => $oldQty,
                'qty_sesudah'            => $newQty,
                'potong_gudang_sebelum'  => $isDualUnit ? $oldKg : null,
                'potong_gudang_sesudah'  => $isDualUnit ? $newKg : null,
                'harga_satuan'           => $hargaSatuan,
                'subtotal_sebelum'       => $subtotalSebelum,
                'subtotal_sesudah'       => $subtotalBaru,
                'alasan'                 => $reason,
            ]);

            return $koreksi;
        });
    }
}
