<?php

namespace Tests\Feature;

use App\Livewire\Transaksi\ReturPenjualan;
use App\Models\DetailPenjualan;
use App\Models\DetailRetur;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\RiwayatStok;
use App\Models\TransaksiPenjualan;
use App\Models\TransaksiRetur;
use App\Models\User;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Retur "Batal Beli": barang dikembalikan tanpa barang pengganti, uang dikembalikan ke pelanggan.
 * Mode Tukar Barang harus tetap berjalan seperti sebelumnya.
 */
class ReturBatalBeliTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Produk $produk;
    private TransaksiPenjualan $nota;
    private DetailPenjualan $detail;

    /**
     * Pengaman: RefreshDatabase mengosongkan database sebelum test. Itu hanya boleh terjadi di
     * SQLite in-memory (phpunit.xml). Bila config ter-cache, phpunit.xml diabaikan dan koneksi
     * bisa mengarah ke MySQL — hentikan sebelum database tersentuh.
     */
    protected function beforeRefreshingDatabase()
    {
        if (! $this->usingInMemoryDatabase()) {
            throw new RuntimeException('Test database hanya boleh berjalan di SQLite in-memory. Jalankan `php artisan config:clear` lalu ulangi.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Owner Test',
            'username' => 'owner',
            'password' => 'rahasia', // di-hash oleh cast 'hashed'
            'peran' => 'OWNER',
        ]);

        // Terjual 3 PCS @ Rp100.000, sisa stok 7
        $this->produk = $this->buatProduk('Speaker Mini', 'pcs', stok: 7, harga: 100000);
        [$this->nota, $this->detail] = $this->buatNota($this->produk, jumlah: 3, harga: 100000, satuan: 'pcs');
    }

    public function test_batal_beli_kondisi_bagus_mengembalikan_stok_tanpa_memotong_pengganti(): void
    {
        $retur = $this->batalBeli($this->detail, 2);

        $this->assertEquals(9, (float) $this->produk->fresh()->stok_saat_ini);
        // Hanya barang masuk, tidak ada barang pengganti yang keluar
        $this->assertSame(['MASUK'], RiwayatStok::where('id_retur', $retur->id_retur)->pluck('tipe')->all());

        $detailRetur = $retur->detailRetur()->sole();
        $this->assertNull($detailRetur->id_produk_pengganti);
        $this->assertTrue($detailRetur->isBatalBeli());
        $this->assertEquals(-200000, (float) $detailRetur->subtotal_biaya);
        $this->assertEquals(-200000, (float) $retur->fresh()->total_biaya_retur);

        $this->assertEquals(2, (float) $this->detail->fresh()->jumlah_diretur);
        $this->assertSame('DIRETUR', $this->nota->fresh()->status_penjualan);
    }

    public function test_batal_beli_kondisi_rusak_tidak_menambah_stok(): void
    {
        $retur = $this->batalBeli($this->detail, 1, 'RUSAK');

        $this->assertEquals(7, (float) $this->produk->fresh()->stok_saat_ini);
        $this->assertSame(0, RiwayatStok::where('id_retur', $retur->id_retur)->count());
        $this->assertNull($retur->detailRetur()->sole()->id_produk_pengganti);
        $this->assertEquals(-100000, (float) $retur->fresh()->total_biaya_retur);
    }

    public function test_batal_beli_barang_meter_mengembalikan_kg_proporsional(): void
    {
        // Terjual 10 Meter @ Rp15.000 = 2,5 KG ditimbang dari gudang, sisa stok 7,5 KG
        $kabel = $this->buatProduk('Kabel NYM', 'kg', stok: 7.5, harga: 60000, metadata: ['harga_meter' => 15000]);
        [, $detail] = $this->buatNota($kabel, jumlah: 10, harga: 15000, satuan: 'meter', potongGudang: 2.5);

        // Tanpa timbang ulang: KG kembali proporsional = 4/10 × 2,5 KG
        $retur = $this->batalBeli($detail, 4);

        $this->assertEquals(8.5, (float) $kabel->fresh()->stok_saat_ini);
        $this->assertEquals(1, (float) RiwayatStok::where('id_retur', $retur->id_retur)->sole()->jumlah);
        $this->assertEquals(-60000, (float) $retur->fresh()->total_biaya_retur);
    }

    public function test_tukar_barang_sama_tetap_seperti_sebelumnya(): void
    {
        $retur = app(ReturnService::class)->prosesRetur(
            $this->nota->id_transaksi_penjualan,
            $this->user->id,
            [[
                'id_detail_penjualan' => $this->detail->id_detail_penjualan,
                'id_produk_pengganti' => $this->produk->id_produk,
                'jumlah' => 1,
                'kondisi_barang_dikembalikan' => 'BAGUS',
                'satuan_pengganti' => 'pcs',
                'harga_pengganti_per_unit' => 100000,
            ]],
            'Tukar unit lain'
        );

        // Masuk 1 lalu keluar 1 untuk barang yang sama: stok neto tetap
        $this->assertEquals(7, (float) $this->produk->fresh()->stok_saat_ini);
        $this->assertSame(['MASUK', 'KELUAR'], RiwayatStok::where('id_retur', $retur->id_retur)->orderBy('id_riwayat')->pluck('tipe')->all());
        $this->assertEquals($this->produk->id_produk, $retur->detailRetur()->sole()->id_produk_pengganti);
        $this->assertEquals(0, (float) $retur->fresh()->total_biaya_retur);
    }

    public function test_halaman_retur_default_batal_beli(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ReturPenjualan::class)
            ->call('pilihNota', $this->nota->id_transaksi_penjualan)
            ->call('bukaModalRetur', $this->detail->id_detail_penjualan)
            ->assertSet('jenis_retur', 'BATAL_BELI')
            ->assertSee('Uang Dikembalikan ke Pelanggan')
            ->set('qty_retur', 2)
            ->set('catatan', 'Ukuran tidak cocok')
            ->set('password_admin', 'rahasia')
            ->call('prosesRetur')
            ->assertHasNoErrors();

        $this->assertEquals(9, (float) $this->produk->fresh()->stok_saat_ini);
        $this->assertNull(DetailRetur::sole()->id_produk_pengganti);
    }

    public function test_halaman_retur_mode_tukar_tetap_menampilkan_barang_pengganti(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ReturPenjualan::class)
            ->call('pilihNota', $this->nota->id_transaksi_penjualan)
            ->call('bukaModalRetur', $this->detail->id_detail_penjualan)
            ->set('jenis_retur', 'TUKAR')
            ->assertSee('Akan Diberikan:')
            ->assertSee('Tukar Guling (Rp 0)')
            ->assertDontSee('Uang Dikembalikan ke Pelanggan');
    }

    public function test_rincian_nota_dan_pdf_menampilkan_batal_beli(): void
    {
        $retur = $this->batalBeli($this->detail, 1);

        // Tampilan ini dulu selalu membaca produkPengganti, yang kosong pada Batal Beli
        $this->blade('<x-nota-detail-modal :nota="$nota" tipe="RETUR" close-action="tutup" />', ['nota' => $retur->fresh()])
            ->assertSee('Tanpa barang pengganti');

        $nota = $this->nota->fresh();
        $this->blade('<x-nota-detail-modal :nota="$nota" tipe="POS" close-action="tutup" />', ['nota' => $nota])
            ->assertSee('Batal beli');

        $pdf = view('pdf.laporan-penjualan', [
            'dataTransaksi' => collect([$nota]),
            'judulPeriode' => 'Test',
            'tanggalCetak' => 'Test',
            'tampilkanUang' => true,
        ])->render();
        $this->assertStringContainsString('Batal beli', $pdf);
    }

    private function buatProduk(string $nama, string $satuan, float $stok, float $harga, ?array $metadata = null): Produk
    {
        $kategori = Kategori::firstOrCreate(['nama_kategori' => 'Umum']);

        return Produk::create([
            'id_kategori' => $kategori->id_kategori,
            'nama_produk' => $nama,
            'satuan' => $satuan,
            'harga_jual_satuan' => $harga,
            'lacak_stok' => true,
            'stok_saat_ini' => $stok,
            'metadata' => $metadata,
            'index_pencarian' => strtolower($nama),
        ]);
    }

    /** @return array{TransaksiPenjualan, DetailPenjualan} */
    private function buatNota(Produk $produk, float $jumlah, float $harga, string $satuan, ?float $potongGudang = null): array
    {
        $nota = TransaksiPenjualan::create([
            'kode_nota' => 'Nota_Test-' . $produk->id_produk,
            'user_id' => $this->user->id,
            'total_harga' => $jumlah * $harga,
            'status_penjualan' => 'SELESAI',
            'tanggal_transaksi' => now(),
        ]);

        $detail = DetailPenjualan::create([
            'id_transaksi_penjualan' => $nota->id_transaksi_penjualan,
            'id_produk' => $produk->id_produk,
            'jumlah' => $jumlah,
            'jumlah_potong_gudang' => $potongGudang ?? $jumlah,
            'satuan_saat_jual' => $satuan,
            'harga_satuan' => $harga,
            'subtotal' => $jumlah * $harga,
        ]);

        return [$nota, $detail];
    }

    private function batalBeli(DetailPenjualan $detail, float $jumlah, string $kondisi = 'BAGUS'): TransaksiRetur
    {
        return app(ReturnService::class)->prosesRetur(
            $detail->id_transaksi_penjualan,
            $this->user->id,
            [[
                'id_detail_penjualan' => $detail->id_detail_penjualan,
                'id_produk_pengganti' => null,
                'jumlah' => $jumlah,
                'kondisi_barang_dikembalikan' => $kondisi,
            ]],
            'Ukuran tidak cocok'
        );
    }
}
