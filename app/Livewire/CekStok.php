<?php

namespace App\Livewire;

use App\Models\Produk;
use Livewire\Component;

/**
 * Kotak "Cek Stok Barang" di dashboard owner.
 * Dipisah dari Dashboard agar tiap ketikan hanya merender ulang hasil pencarian ini,
 * bukan seluruh dashboard (lebih ringan untuk data seluler di HP/tablet).
 */
class CekStok extends Component
{
    // Hasil yang ditampilkan dibatasi; jika lebih, owner diminta mengetik lebih spesifik
    const MAKS_HASIL = 10;

    public $cari = '';

    public function render()
    {
        $kata = trim($this->cari);
        $cukupPanjang = mb_strlen($kata) >= 2;
        $hasil = collect();

        if ($cukupPanjang) {
            // Pola pencarian sama dengan Katalog Produk & POS: setiap kata harus ada di index_pencarian
            $query = Produk::with('kategori')->where('status_aktif', true);
            foreach (preg_split('/\s+/', strtolower($kata)) as $term) {
                $query->where('index_pencarian', 'LIKE', '%'.$term.'%');
            }
            $hasil = $query->orderBy('nama_produk')->limit(self::MAKS_HASIL + 1)->get();
        }

        return view('livewire.cek-stok', [
            'cukupPanjang' => $cukupPanjang,
            'hasil' => $hasil->take(self::MAKS_HASIL),
            'adaLagi' => $hasil->count() > self::MAKS_HASIL,
        ]);
    }
}
