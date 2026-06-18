<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Laporan\KatalogProduk;
use App\Livewire\Laporan\Penjualan;
use App\Livewire\Laporan\StokMenipis;
use App\Livewire\Master\AtributIndex;
use App\Livewire\Master\KategoriIndex;
use App\Livewire\Master\MarketingIndex;
use App\Livewire\Master\PelangganIndex;
use App\Livewire\Master\ProdukIndex;
use App\Livewire\Master\SystemSettings;
use App\Livewire\Transaksi\KasirPos;
use App\Livewire\Transaksi\ReturPenjualan;
use App\Livewire\Transaksi\RiwayatTransaksi;
use App\Models\ProdukGambar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Route Authentication (Guest)
Route::middleware(['guest'])->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/', function () {
        return redirect('/login');
    });
});

// Route System (Harus Login)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Transaksi
    Route::get('/pos', KasirPos::class)->name('pos');
    Route::get('/retur', ReturPenjualan::class)->name('retur');
    Route::get('/transaksi/riwayat', RiwayatTransaksi::class)->name('transaksi.riwayat');

    // Laporan
    Route::get('/laporan/penjualan', Penjualan::class)->name('laporan.penjualan');
    Route::get('/laporan/katalog', KatalogProduk::class)->name('laporan.katalog');
    Route::get('/laporan/stok-menipis', StokMenipis::class)->name('laporan.stok-menipis');

    // Download foto produk (force-download dengan nama file asli)
    Route::get('/produk-foto/download/{gambar}', function (ProdukGambar $gambar) {
        abort_unless(Storage::disk('public')->exists($gambar->path), 404);

        return Storage::disk('public')->download($gambar->path, $gambar->nama_asli ?: 'foto-produk.jpg');
    })->name('produk.foto.download');

    // Master Data
    Route::get('/master/pelanggan', PelangganIndex::class)->name('master.pelanggan');
    Route::get('/master/marketing', MarketingIndex::class)->name('master.marketing');
    Route::get('/master/produk', ProdukIndex::class)->name('master.produk');

    Route::get('/master/kategori', KategoriIndex::class)->name('master.kategori');
    Route::get('/master/atribut', AtributIndex::class)->name('master.atribut');
    Route::get('/master/pengaturan', SystemSettings::class)->name('master.pengaturan');

    // Proses Logout (Non-Livewire Standard Route)
    Route::post('/logout', function () {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/login');
    });
});
