<?php

namespace App\Livewire\Laporan;

use Livewire\Component;
use App\Models\TransaksiPenjualan;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PenjualanExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class Penjualan extends Component
{
    public $tipe_filter = 'harian'; 
    public $filter_tanggal;
    public $filter_bulan;
    public $filter_tahun;
    public $teks_rekap_harian = '';

    // STATE MODAL DETAIL NOTA
    public $modal_open = false;
    public $detail_nota = null;

    public function mount()
    {
        $this->filter_tanggal = today()->format('Y-m-d');
        $this->filter_bulan = today()->format('m');
        $this->filter_tahun = today()->format('Y');
    }

    private function getQuery()
    {
        $query = TransaksiPenjualan::with(['detailPenjualan.produk', 'pelanggan', 'marketing'])
                    ->withCount('riwayatKoreksi')
                    ->where('status_penjualan', '!=', 'DIBATALKAN');

        if ($this->tipe_filter === 'harian') {
            $query->whereDate('tanggal_transaksi', $this->filter_tanggal);
        } elseif ($this->tipe_filter === 'bulanan') {
            $query->whereMonth('tanggal_transaksi', $this->filter_bulan)
                  ->whereYear('tanggal_transaksi', $this->filter_tahun);
        } elseif ($this->tipe_filter === 'tahunan') {
            $query->whereYear('tanggal_transaksi', $this->filter_tahun);
        }

        return $query->orderBy('tanggal_transaksi', 'asc')->get();
    }

    public function updated()
    {
        $this->generateRekapTeks();
    }

    // FUNGSI MODAL DETAIL NOTA
    public function lihatDetail($id)
    {
        $this->detail_nota = TransaksiPenjualan::with([
            'detailPenjualan.produk',
            'user',
            'pelanggan',
            'marketing',
            'transaksiRetur.detailRetur.produkPengganti', // Menarik relasi jejak retur (smart trace)
            'riwayatKoreksi.produk',
            'riwayatKoreksi.user',
        ])->find($id);
        
        $this->modal_open = true;
    }

    public function tutupModal()
    {
        $this->modal_open = false;
        $this->detail_nota = null;
    }

    public function generateRekapTeks()
    {
        if ($this->tipe_filter !== 'harian') return;

        $transaksis = $this->getQuery();
        $tanggalFormat = \Carbon\Carbon::parse($this->filter_tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY');;
        
        $teks = "Rekap Penjualan: {$tanggalFormat}\n\n";

        foreach ($transaksis as $trx) {
            $namaPelanggan = $trx->pelanggan->nama ?? 'Umum';
            $namaSales = $trx->marketing->nama ?? 'Toko';
            
            $teks .= "👤 {$namaPelanggan} from {$namaSales}\n";
            
            foreach ($trx->detailPenjualan as $det) {
                $qty = fmod($det->jumlah, 1) == 0 ? (int)$det->jumlah : $det->jumlah;
                $teks .= "- {$qty} {$det->satuan_saat_jual} - {$det->produk->nama_produk} - {$det->produk->kode_barang}\n";
            }
            $teks .= "-----------------------------\n";
        }

        if ($transaksis->count() == 0) {
            $teks .= "Tidak ada penjualan di hari ini.";
        }

        $this->teks_rekap_harian = $teks;
    }

    public function cetakPdf()
    {
        $dataTransaksi = $this->getQuery();
        
        $judulPeriode = '';
        if ($this->tipe_filter === 'harian') $judulPeriode = Carbon::parse($this->filter_tanggal)->translatedFormat('d F Y');
        if ($this->tipe_filter === 'bulanan') $judulPeriode = Carbon::create(null, $this->filter_bulan)->translatedFormat('F') . ' ' . $this->filter_tahun;
        if ($this->tipe_filter === 'tahunan') $judulPeriode = "Tahun " . $this->filter_tahun;

        $pdf = Pdf::loadView('pdf.laporan-penjualan', [
            'dataTransaksi' => $dataTransaksi,
            'judulPeriode' => $judulPeriode,
            'tanggalCetak' => now()->translatedFormat('d F Y H:i')
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Laporan_Penjualan_' . $judulPeriode . '.pdf');
    }

    public function exportExcel()
    {
        $dataTransaksi = $this->getQuery();

        $judulPeriode = '';
        if ($this->tipe_filter === 'harian') $judulPeriode = Carbon::parse($this->filter_tanggal)->translatedFormat('d F Y');
        if ($this->tipe_filter === 'bulanan') $judulPeriode = Carbon::create(null, $this->filter_bulan)->translatedFormat('F') . ' ' . $this->filter_tahun;
        if ($this->tipe_filter === 'tahunan') $judulPeriode = "Tahun " . $this->filter_tahun;

        return Excel::download(
            new PenjualanExport($dataTransaksi, $judulPeriode, now()->translatedFormat('d F Y H:i')),
            'Laporan_Penjualan_' . $judulPeriode . '.xlsx'
        );
    }

    public function render()
    {
        $this->generateRekapTeks(); 

        return view('livewire.laporan.penjualan', [
            'daftarTransaksi' => $this->getQuery()
        ]);
    }
}