<?php

namespace App\Livewire\Laporan;

use Livewire\Component;
use App\Models\TransaksiPenjualan;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PenjualanExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Peran ADMIN tidak boleh melihat angka rupiah maupun periode agregat
     * (bulanan/tahunan) — hanya OWNER. Dipakai sebagai satu-satunya sumber
     * kebenaran oleh getQuery(), cetakPdf(), exportExcel(), dan view.
     */
    public function bolehLihatUang(): bool
    {
        return Auth::user()?->peran === 'OWNER';
    }

    /**
     * Tipe filter yang benar-benar dipakai. Properti $tipe_filter terikat
     * wire:model.live sehingga bisa diubah dari browser; nilainya TIDAK boleh
     * dipercaya. Non-OWNER selalu dipaksa ke 'harian'.
     */
    public function tipeFilterEfektif(): string
    {
        if (! $this->bolehLihatUang()) {
            return 'harian';
        }

        return in_array($this->tipe_filter, ['harian', 'bulanan', 'tahunan'], true)
            ? $this->tipe_filter
            : 'harian';
    }

    /** Kembalikan properti ke nilai sah bila klien mencoba mengubahnya. */
    public function updatedTipeFilter(): void
    {
        $this->tipe_filter = $this->tipeFilterEfektif();
    }

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

        $tipe = $this->tipeFilterEfektif();

        if ($tipe === 'harian') {
            $query->whereDate('tanggal_transaksi', $this->filter_tanggal);
        } elseif ($tipe === 'bulanan') {
            $query->whereMonth('tanggal_transaksi', $this->filter_bulan)
                  ->whereYear('tanggal_transaksi', $this->filter_tahun);
        } elseif ($tipe === 'tahunan') {
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
        if ($this->tipeFilterEfektif() !== 'harian') return;

        $transaksis = $this->getQuery();
        $tanggalFormat = \Carbon\Carbon::parse($this->filter_tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY');;
        
        $teks = "Rekap Penjualan: {$tanggalFormat}\n\n";

        foreach ($transaksis as $trx) {
            $namaPelanggan = $trx->pelanggan->nama ?? 'Umum';
            $namaSales = $trx->marketing->nama ?? 'Toko';
            
            $teks .= "👤 {$namaPelanggan} from {$namaSales}\n";
            
            $noItem = 1;
            foreach ($trx->detailPenjualan as $det) {
                $qty = fmod($det->jumlah, 1) == 0 ? (int)$det->jumlah : $det->jumlah;
                $teks .= "{$noItem}. {$qty} {$det->satuan_saat_jual} - {$det->produk->nama_produk} - {$det->produk->kode_barang}\n";
                $noItem++;
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
        
        $tipe = $this->tipeFilterEfektif();
        $judulPeriode = '';
        if ($tipe === 'harian') $judulPeriode = Carbon::parse($this->filter_tanggal)->translatedFormat('d F Y');
        if ($tipe === 'bulanan') $judulPeriode = Carbon::create(null, $this->filter_bulan)->translatedFormat('F') . ' ' . $this->filter_tahun;
        if ($tipe === 'tahunan') $judulPeriode = "Tahun " . $this->filter_tahun;

        $pdf = Pdf::loadView('pdf.laporan-penjualan', [
            'dataTransaksi' => $dataTransaksi,
            'judulPeriode' => $judulPeriode,
            'tanggalCetak' => now()->translatedFormat('d F Y H:i'),
            'tampilkanUang' => $this->bolehLihatUang(),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Laporan_Penjualan_' . $judulPeriode . '.pdf');
    }

    public function exportExcel()
    {
        $dataTransaksi = $this->getQuery();

        $tipe = $this->tipeFilterEfektif();
        $judulPeriode = '';
        if ($tipe === 'harian') $judulPeriode = Carbon::parse($this->filter_tanggal)->translatedFormat('d F Y');
        if ($tipe === 'bulanan') $judulPeriode = Carbon::create(null, $this->filter_bulan)->translatedFormat('F') . ' ' . $this->filter_tahun;
        if ($tipe === 'tahunan') $judulPeriode = "Tahun " . $this->filter_tahun;

        return Excel::download(
            new PenjualanExport($dataTransaksi, $judulPeriode, now()->translatedFormat('d F Y H:i'), $this->bolehLihatUang()),
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