<?php

namespace Tests\Feature;

use App\Livewire\CekStok;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\TransaksiPenjualan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Tampilan owner: menu Kasir POS disembunyikan (halaman /pos tetap bisa dibuka),
 * dashboard fokus stok, dan aturan "perlu restock" yang dipakai bersama
 * dashboard owner & Laporan Stok Menipis.
 */
class TampilanOwnerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_owner_tidak_melihat_menu_pos_tapi_halaman_pos_tetap_bisa_dibuka(): void
    {
        $owner = $this->buatUser('OWNER');

        $this->actingAs($owner)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('href="/pos"', false)
            ->assertSee('Beranda')
            ->assertSee('Logout');

        $this->actingAs($owner)->get('/pos')->assertOk();
    }

    public function test_admin_tetap_melihat_menu_pos(): void
    {
        $this->actingAs($this->buatUser('ADMIN'))->get('/dashboard')
            ->assertOk()
            ->assertSee('href="/pos"', false);
    }

    public function test_perlu_restock_mengikuti_batas_satuan_dan_habis_paling_atas(): void
    {
        $this->buatProduk('B-AMAN', 'Speaker Aman', 'pcs', 21);
        $this->buatProduk('A-MENIPIS', 'Speaker Menipis', 'pcs', 20);
        $this->buatProduk('C-HABIS', 'Speaker Habis', 'pcs', 0);
        $this->buatProduk('D-KG-MENIPIS', 'Kabel Menipis', 'kg', 1);
        $this->buatProduk('E-KG-AMAN', 'Kabel Aman', 'kg', 1.5);
        $this->buatProduk('F-NONAKTIF', 'Speaker Nonaktif', 'pcs', 0, aktif: false);
        $this->buatProduk('G-TANPA-LACAK', 'Jasa Pasang', 'pcs', 0, lacakStok: false);

        // HABIS dulu, lalu MENIPIS urut kode barang; AMAN, nonaktif & tanpa lacak stok tidak ikut
        $this->assertSame(
            ['C-HABIS', 'A-MENIPIS', 'D-KG-MENIPIS'],
            Produk::perluRestock()->pluck('kode_barang')->all()
        );
    }

    public function test_cek_stok_mencari_nama_atau_kode_mulai_dua_huruf(): void
    {
        $this->buatProduk('WF-12', 'Woofer 12 inch', 'pcs', 8);
        $this->buatProduk('TW-01', 'Tweeter Mini', 'pcs', 40);

        Livewire::actingAs($this->buatUser('OWNER'))
            ->test(CekStok::class)
            ->set('cari', 'w')
            ->assertDontSee('Woofer 12 inch')
            ->set('cari', 'woofer 12')
            ->assertSee('Woofer 12 inch')
            ->assertSee('Menipis')
            ->assertDontSee('Tweeter Mini')
            ->set('cari', 'tw-01')
            ->assertSee('Tweeter Mini')
            ->assertSee('Aman')
            ->set('cari', 'barang tidak ada')
            ->assertSee('Barang tidak ditemukan.');
    }

    public function test_dashboard_owner_menampilkan_pratinjau_perlu_restock(): void
    {
        $this->buatProduk('KB-250', 'Kabel Speaker 2x50', 'pcs', 0);
        $this->buatProduk('FR-10', 'Fullring 10 inch', 'pcs', 50);

        $this->actingAs($this->buatUser('OWNER'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Perlu Restock')
            ->assertSee('Kabel Speaker 2x50')
            ->assertSee('Lihat semua 1 barang')
            ->assertDontSee('Fullring 10 inch');
    }

    public function test_dashboard_owner_menonjolkan_omset_dan_nilai_stok_hanya_perkiraan(): void
    {
        $owner = $this->buatUser('OWNER');
        $this->buatProduk('WF-12', 'Woofer 12 inch', 'pcs', 10); // nilai stok 10 × Rp100.000
        TransaksiPenjualan::create([
            'kode_nota' => 'Nota_Test-1',
            'user_id' => $owner->id,
            'total_harga' => 250000,
            'status_penjualan' => 'SELESAI',
            'tanggal_transaksi' => now('Asia/Jakarta'),
        ]);

        // Sebagian data stok masih contoh: omset jadi angka utama, nilai stok cuma catatan "perkiraan"
        $this->actingAs($owner)->get('/dashboard')
            ->assertOk()
            ->assertSee('Omset Kotor')
            ->assertSee('Rp 250.000')
            ->assertSee('Perkiraan nilai stok')
            ->assertSee('Rp 1.000.000')
            ->assertDontSee('Nilai Stok di Gudang');
    }

    private function buatUser(string $peran): User
    {
        return User::create([
            'name' => $peran === 'OWNER' ? 'Owner Test' : 'Admin Test',
            'username' => strtolower($peran),
            'password' => 'rahasia', // di-hash oleh cast 'hashed'
            'peran' => $peran,
        ]);
    }

    private function buatProduk(string $kode, string $nama, string $satuan, float $stok, bool $aktif = true, bool $lacakStok = true): Produk
    {
        $kategori = Kategori::firstOrCreate(['nama_kategori' => 'Umum']);

        return Produk::create([
            'id_kategori' => $kategori->id_kategori,
            'kode_barang' => $kode,
            'nama_produk' => $nama,
            'satuan' => $satuan,
            'harga_jual_satuan' => 100000,
            'lacak_stok' => $lacakStok,
            'stok_saat_ini' => $stok,
            'status_aktif' => $aktif,
            // Sama dengan ProdukIndex::simpan(): "kode nama" huruf kecil
            'index_pencarian' => strtolower($kode.' '.$nama),
        ]);
    }
}
