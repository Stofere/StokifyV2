<?php

namespace App\Livewire\Laporan;

use App\Exports\KatalogProdukExport;
use App\Models\Kategori;
use App\Models\Produk;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class KatalogProduk extends Component
{
    public $filterKategori = '';

    public $semuaKategori = [];

    public function mount()
    {
        $this->semuaKategori = Kategori::orderBy('nama_kategori')->get();
    }

    private function getGroupedData()
    {
        $query = Produk::with('kategori')->where('status_aktif', true);

        if ($this->filterKategori) {
            $query->where('id_kategori', $this->filterKategori);
        }

        // Mengambil data dan mengelompokkannya berdasarkan nama kategori
        return $query->orderBy('id_kategori')
            ->orderBy('kode_barang', 'ASC')
            ->get()
            ->groupBy('kategori.nama_kategori');
    }

    public function cetakPdf()
    {
        $groupedProduk = $this->getGroupedData();
        $namaKategori = $this->filterKategori ? Kategori::find($this->filterKategori)->nama_kategori : 'Semua Kategori';

        $pdf = Pdf::loadView('pdf.katalog-produk', [
            'groupedProduk' => $groupedProduk,
            'namaKategori' => $namaKategori,
            'tanggal' => now()->translatedFormat('d F Y'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Katalog_Produk_'.now()->format('Ymd').'.pdf');
    }

    public function exportExcel()
    {
        $groupedProduk = $this->getGroupedData();
        $namaKategori = $this->filterKategori ? Kategori::find($this->filterKategori)->nama_kategori : 'Semua Kategori';
        $tanggal = now()->translatedFormat('d F Y');

        return Excel::download(
            new KatalogProdukExport($groupedProduk, $namaKategori, $tanggal),
            'Katalog_Produk_'.now()->format('Ymd').'.xlsx'
        );
    }

    public function render()
    {
        return view('livewire.laporan.katalog-produk', [
            'groupedProduk' => $this->getGroupedData(),
        ]);
    }
}
