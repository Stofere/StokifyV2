<?php

namespace App\Livewire\Laporan;

use Livewire\Component;
use App\Models\Produk;
use Barryvdh\DomPDF\Facade\Pdf;

class StokMenipis extends Component
{
    public function getQueryBuilder()
    {
        // Aturan HABIS/MENIPIS & urutan ada di Produk::scopePerluRestock (dipakai juga dashboard owner)
        return Produk::with('kategori')->perluRestock();
    }

    public function cetakPdf()
    {
        $produkList = $this->getQueryBuilder()->get();

        $pdf = Pdf::loadView('pdf.stok-menipis', [
            'produkList' => $produkList,
            'tanggal' => now()->translatedFormat('d F Y'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Laporan_Stok_Menipis_' . now()->format('Ymd') . '.pdf');
    }

    public function render()
    {
        return view('livewire.laporan.stok-menipis', [
            'produkList' => $this->getQueryBuilder()->get(),
        ]);
    }
}
