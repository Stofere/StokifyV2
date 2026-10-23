<?php

namespace App\Services;

use App\Models\TransaksiPenjualan;
use App\Models\DetailPenjualan;
use App\Models\TransaksiRetur;
use App\Models\DetailRetur;
use App\Models\Produk;
use App\Models\RiwayatStok;
use Illuminate\Support\Facades\DB;
use Exception;

class ReturnService
{
    /**
     * Memproses Retur: Tukar Barang atau Batal Beli.
     * $itemsRetur format:
     * [['id_detail_penjualan' => x, 'id_produk_pengganti' => y, 'jumlah' => z,
     *   (id_produk_pengganti null = Batal Beli: tanpa barang pengganti, uang dikembalikan ke pelanggan)
     *   'kondisi_barang_dikembalikan' => 'BAGUS'/'RUSAK',
     *   'jumlah_potong_gudang_retur' => w (opsional, KG fisik yang dikembalikan ke gudang),
     *   'harga_pengganti_per_unit' => h (harga per unit pengganti, bisa /KG atau /Meter),
     *   'satuan_pengganti' => s (satuan pengganti: 'kg', 'meter', 'pcs')]]
     */
    public function prosesRetur(int $idTransaksiPenjualan, int $userId, array $itemsRetur, ?string $catatan = null, $tanggalRetur = null)
    {
        if (empty($itemsRetur)) {
            throw new Exception("Daftar barang yang diretur tidak boleh kosong.");
        }

        $tanggalRetur = $tanggalRetur ?? now();

        return DB::transaction(function () use ($idTransaksiPenjualan, $userId, $itemsRetur, $catatan, $tanggalRetur) {
            
            // Kunci Transaksi Induk (Pessimistic Locking)
            $transaksiAwal = TransaksiPenjualan::lockForUpdate()->find($idTransaksiPenjualan);
            if (!$transaksiAwal) {
                throw new Exception("Nota penjualan asli tidak ditemukan.");
            }

            $totalBiayaRetur = 0; // Nilai (+) berarti pelanggan nombok, Nilai (-) berarti toko kembalikan uang

            // 1. Buat Sampul Nota Retur
            $transaksiRetur = TransaksiRetur::create([
                'kode_retur' => $this->generateKodeRetur(),
                'id_transaksi_penjualan' => $transaksiAwal->id_transaksi_penjualan,
                'user_id' => $userId,
                'tanggal_retur' => $tanggalRetur,
                'total_biaya_retur' => 0, 
                'catatan' => $catatan,
            ]);

            // 2. Proses tiap item yang ditukar
            foreach ($itemsRetur as $item) {
                $qtyRetur = $item['jumlah'];
                
                // Kunci baris detail nota
                $detailAwal = DetailPenjualan::lockForUpdate()->find($item['id_detail_penjualan']);
                if (!$detailAwal || $detailAwal->id_transaksi_penjualan != $transaksiAwal->id_transaksi_penjualan) {
                    throw new Exception("Item detail tidak valid untuk nota ini.");
                }

                // VALIDASI MAX RETUR
                $sisaBisaDiretur = $detailAwal->jumlah - $detailAwal->jumlah_diretur;
                if ($qtyRetur > $sisaBisaDiretur) {
                    throw new Exception("Jumlah retur melebihi batas. Sisa yang boleh diretur: " . $sisaBisaDiretur);
                }

                // Kunci produk lama dan produk baru (Batal Beli: tidak ada produk pengganti)
                $idPengganti = $item['id_produk_pengganti'] ?? null;
                $produkLama = Produk::lockForUpdate()->find($detailAwal->id_produk);
                $produkPengganti = $idPengganti !== null ? Produk::lockForUpdate()->find($idPengganti) : null;

                if (!$produkLama || ($idPengganti !== null && !$produkPengganti)) {
                    throw new Exception("Data produk fisik (lama/pengganti) tidak ditemukan.");
                }

                // ===================================================================
                // KALKULASI UANG BERDASARKAN HARGA UNIT YANG DIPILIH
                // ===================================================================
                // Harga lama per unit = harga_satuan di nota (sudah benar, bisa /Meter atau /KG)
                $hargaTotalLama = $detailAwal->harga_satuan * $qtyRetur;
                // Batal Beli: tidak ada barang baru → seluruh harga lama dikembalikan ke pelanggan
                $hargaTotalBaru = 0;
                if ($produkPengganti) {
                    // Harga pengganti = harga per unit yang sudah dihitung oleh Livewire (bisa /KG atau /Meter)
                    $hargaPenggantiPerUnit = $item['harga_pengganti_per_unit'] ?? $produkPengganti->harga_jual_satuan;
                    $hargaTotalBaru = $hargaPenggantiPerUnit * $qtyRetur;
                }
                $subtotalBiaya = $hargaTotalBaru - $hargaTotalLama;
                $totalBiayaRetur += $subtotalBiaya;

                // ===================================================================
                // HITUNG KG FISIK YANG DIKEMBALIKAN KE GUDANG (PROPORSIONAL)
                // ===================================================================
                // Tentukan berapa KG yang harus dikembalikan ke stok
                $isDualUnit = strtolower($detailAwal->satuan_saat_jual) === 'meter';
                
                if ($isDualUnit) {
                    // Jika ada input manual admin (admin menimbang ulang)
                    if (isset($item['jumlah_potong_gudang_retur']) && $item['jumlah_potong_gudang_retur'] > 0) {
                        $kgFisikKembali = $item['jumlah_potong_gudang_retur'];
                    } else {
                        // Fallback: hitung proporsional dari data timbangan saat jual
                        $kgAsliTotal = $detailAwal->jumlah_potong_gudang ?? $detailAwal->jumlah;
                        $meterAsliTotal = $detailAwal->jumlah;
                        $kgFisikKembali = ($meterAsliTotal > 0) 
                            ? round(($kgAsliTotal / $meterAsliTotal) * $qtyRetur, 3) 
                            : $qtyRetur;
                    }
                } else {
                    // Barang non-dual-unit: qty retur = qty stok langsung
                    $kgFisikKembali = $qtyRetur;
                }


                // ==========================================================
                // TAHAP 1: MANAJEMEN STOK PRODUK LAMA (DIKEMBALIKAN KE TOKO)
                // ==========================================================
                if ($item['kondisi_barang_dikembalikan'] === 'BAGUS' && $produkLama->lacak_stok) {
                    $stokSebelumLama = $produkLama->stok_saat_ini;
                    $stokSesudahLama = $stokSebelumLama + $kgFisikKembali;
                    
                    $produkLama->update(['stok_saat_ini' => $stokSesudahLama]);

                    $ketRetur = ($produkPengganti ? "Pengembalian Retur" : "Retur Batal Beli") . " (Kondisi Bagus). Nota: " . $transaksiAwal->kode_nota;
                    if ($isDualUnit) {
                        $ketRetur .= " (Dikembalikan {$qtyRetur} Meter = {$kgFisikKembali} KG)";
                    }

                    RiwayatStok::create([
                        'id_produk' => $produkLama->id_produk,
                        'user_id' => $userId,
                        'id_retur' => $transaksiRetur->id_retur,
                        'tipe' => 'MASUK', 
                        'jumlah' => $kgFisikKembali,
                        'stok_sebelum' => $stokSebelumLama,
                        'stok_sesudah' => $stokSesudahLama,
                        'keterangan' => $ketRetur,
                    ]);
                }


                // ==========================================================
                // TAHAP 2: MANAJEMEN STOK PRODUK PENGGANTI (DIKELUARKAN TOKO)
                // ==========================================================
                // Batal Beli: tidak ada barang pengganti yang keluar dari stok
                if ($produkPengganti && $produkPengganti->lacak_stok) {
                    
                    // 🐛 FIX BUG RACE-CONDITION MEMORY!
                    if ($produkPengganti->id_produk === $produkLama->id_produk) {
                        $produkPengganti->refresh();
                    }

                    // Hitung KG fisik yang harus dipotong dari stok pengganti
                    // Untuk dual-unit: pakai jumlah_potong_gudang_retur (KG yang ditimbang)
                    // Untuk non-dual: pakai qtyRetur langsung
                    $satuanPengganti = $item['satuan_pengganti'] ?? strtolower($produkPengganti->satuan);
                    if ($satuanPengganti === 'meter') {
                        // Pengganti dijual per Meter → stok dipotong sebesar KG fisik (sama dengan yg dikembalikan)
                        $kgPotongPengganti = $kgFisikKembali; // Default: sama dengan KG yang dikembalikan
                    } else {
                        // Pengganti dijual per KG/Pcs → potong stok sesuai qty retur
                        $kgPotongPengganti = $qtyRetur;
                    }

                    if ($produkPengganti->stok_saat_ini < $kgPotongPengganti) {
                        throw new Exception("Stok produk pengganti ({$produkPengganti->nama_produk}) tidak mencukupi untuk ditukar. Sisa stok: " . $produkPengganti->stok_saat_ini);
                    }

                    $stokSebelumBaru = $produkPengganti->stok_saat_ini;
                    $stokSesudahBaru = $stokSebelumBaru - $kgPotongPengganti;

                    $produkPengganti->update(['stok_saat_ini' => $stokSesudahBaru]);

                    $ketPengganti = "Pengganti Retur. Nota Asli: " . $transaksiAwal->kode_nota;
                    if ($satuanPengganti === 'meter') {
                        $ketPengganti .= " (Keluar {$qtyRetur} Meter = {$kgPotongPengganti} KG)";
                    }

                    RiwayatStok::create([
                        'id_produk' => $produkPengganti->id_produk,
                        'user_id' => $userId,
                        'id_retur' => $transaksiRetur->id_retur,
                        'tipe' => 'KELUAR', 
                        'jumlah' => $kgPotongPengganti,
                        'stok_sebelum' => $stokSebelumBaru,
                        'stok_sesudah' => $stokSesudahBaru,
                        'keterangan' => $ketPengganti,
                    ]);
                }

                // --- UPDATE CATATAN NOTA & RETUR ---
                $detailAwal->update(['jumlah_diretur' => $detailAwal->jumlah_diretur + $qtyRetur]);

                DetailRetur::create([
                    'id_retur' => $transaksiRetur->id_retur,
                    'id_produk_dikembalikan' => $produkLama->id_produk,
                    'id_produk_pengganti' => $produkPengganti?->id_produk,
                    'jumlah' => $qtyRetur,
                    'kondisi_barang_dikembalikan' => $item['kondisi_barang_dikembalikan'],
                    'subtotal_biaya' => $subtotalBiaya,
                ]);
            }

            // Update Total Biaya Selisih Retur & Status Penjualan Asli
            $transaksiRetur->update(['total_biaya_retur' => $totalBiayaRetur]);
            $transaksiAwal->update(['status_penjualan' => 'DIRETUR']);

            return $transaksiRetur;
        });
    }

    private function generateKodeRetur(): string
    {
        $tanggal = now()->format('d-m-Y');
        // Gunakan ID terakhir + 1 (global auto-increment) agar tidak collision dengan custom tanggal
        $lastId = TransaksiRetur::max('id_retur') ?? 0;
        $urutan = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
        
        return "Retur_{$tanggal}-{$urutan}";
    }
}