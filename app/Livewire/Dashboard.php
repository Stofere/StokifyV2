<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TransaksiPenjualan;
use App\Models\TransaksiRetur;
use App\Models\RiwayatStok;
use App\Models\Produk;
use App\Models\DetailPenjualan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $startDate;
    public $endDate;
    public $filterText = '';

    public $isMarketingModalOpen = false;
    public $isCustomerModalOpen = false;

    public $selectedMarketingName = '';
    public $marketingDrilldownData = [];
    public $expandedTransaksiId = null;
    public $expandedTransaksiDetail = [];

    public $selectedCustomerName = '';
    public $customerDrilldownMarketing = [];
    public $customerDrilldownProducts = [];

    // Filter rentang tanggal khusus Log Aktivitas (default: 1 minggu terakhir)
    public $logStartDate;
    public $logEndDate;
    public $logFilterText = '';

    // Drilldown Log Aktivitas
    public $isLogModalOpen = false;
    public $logModalType = null;   // 'PENJUALAN' | 'RETUR' | 'STOK'
    public $logModalTitle = '';
    public $logDetailMeta = [];
    public $logDetailItems = [];
    public $logLinkId = null;      // id nota/retur untuk tombol "Buka di Riwayat Transaksi"
    public $logLinkType = null;    // 'POS' | 'RETUR'

    public function mount()
    {
        $this->startDate = Carbon::now('Asia/Jakarta')->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now('Asia/Jakarta')->endOfMonth()->format('Y-m-d');
        $this->updateFilterText();

        // Default log: 7 hari terakhir (termasuk hari ini)
        $this->logEndDate = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $this->logStartDate = Carbon::now('Asia/Jakarta')->subDays(6)->format('Y-m-d');
        $this->updateLogFilterText();
    }

    public function updatedStartDate() { $this->updateFilterText(); }
    public function updatedEndDate() { $this->updateFilterText(); }

    public function updatedLogStartDate() { $this->updateLogFilterText(); }
    public function updatedLogEndDate() { $this->updateLogFilterText(); }

    private function updateLogFilterText()
    {
        Carbon::setLocale('id');
        $start = Carbon::parse($this->logStartDate);
        $end = Carbon::parse($this->logEndDate);
        if ($start->isSameDay($end)) {
            $this->logFilterText = $start->translatedFormat('d M Y');
        } else {
            $this->logFilterText = $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y');
        }
    }

    private function updateFilterText()
    {
        Carbon::setLocale('id');
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        if ($start->format('Y-m') === $end->format('Y-m') && $start->copy()->startOfMonth()->format('Y-m-d') === $start->format('Y-m-d') && $end->copy()->endOfMonth()->format('Y-m-d') === $end->format('Y-m-d')) {
            $this->filterText = $start->translatedFormat('F Y');
        } else {
            $this->filterText = $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y');
        }
    }

    public function openMarketingModal($marketingId, $marketingName)
    {
        $this->selectedMarketingName = $marketingName;
        $this->expandedTransaksiId = null;
        $this->expandedTransaksiDetail = [];

        $this->marketingDrilldownData = TransaksiPenjualan::with('pelanggan')
            ->withCount('riwayatKoreksi')
            ->where('id_marketing', $marketingId)
            ->whereBetween('tanggal_transaksi', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->where('status_penjualan', '!=', 'DIBATALKAN')
            ->orderByDesc('tanggal_transaksi')
            ->get();
            
        $this->isMarketingModalOpen = true;
    }

    public function toggleTransaksiDetail($transaksiId)
    {
        $transaksiId = intval($transaksiId);

        if ($this->expandedTransaksiId === $transaksiId) {
            $this->expandedTransaksiId = null;
            $this->expandedTransaksiDetail = [];
        } else {
            $this->expandedTransaksiId = $transaksiId;
            $this->expandedTransaksiDetail = DetailPenjualan::with('produk')
                ->where('id_transaksi_penjualan', $transaksiId)
                ->get()
                ->toArray();
        }
    }

    public function closeMarketingModal()
    {
        $this->isMarketingModalOpen = false;
    }

    public function openCustomerModal($customerId, $customerName)
    {
        $this->selectedCustomerName = $customerName;
        
        $marketings = TransaksiPenjualan::with('marketing')
            ->where('id_pelanggan', $customerId)
            ->whereBetween('tanggal_transaksi', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->where('status_penjualan', '!=', 'DIBATALKAN')
            ->whereNotNull('id_marketing')
            ->select('id_marketing')
            ->groupBy('id_marketing')
            ->get();
            
        $this->customerDrilldownMarketing = $marketings->map(function($t) {
            return $t->marketing ? $t->marketing->nama : null;
        })->filter()->unique()->values()->toArray();
            
        $this->customerDrilldownProducts = DetailPenjualan::with('produk')
            ->whereHas('transaksiPenjualan', function($q) use ($customerId) {
                $q->where('id_pelanggan', $customerId)
                  ->whereBetween('tanggal_transaksi', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
                  ->where('status_penjualan', '!=', 'DIBATALKAN');
            })
            ->select('id_produk', DB::raw('sum(jumlah) as total_jumlah'), DB::raw('sum(subtotal) as total_subtotal'))
            ->groupBy('id_produk')
            ->orderByDesc('total_jumlah')
            ->get();
            
        $this->isCustomerModalOpen = true;
    }

    public function closeCustomerModal()
    {
        $this->isCustomerModalOpen = false;
    }

    public function openLogDetail($logId)
    {
        $log = RiwayatStok::with(['user', 'produk'])->find($logId);
        if (! $log) {
            return;
        }

        $this->logDetailMeta = [];
        $this->logDetailItems = [];
        $this->logLinkId = null;
        $this->logLinkType = null;

        // 1. Log terkait Nota Penjualan (KELUAR dari kasir, dll)
        if ($log->id_transaksi_penjualan) {
            $trx = TransaksiPenjualan::with(['pelanggan', 'marketing', 'user', 'detailPenjualan.produk'])
                ->find($log->id_transaksi_penjualan);

            if ($trx) {
                $this->logModalType = 'PENJUALAN';
                $this->logModalTitle = 'Detail Nota Penjualan';
                $this->logLinkType = 'POS';
                $this->logLinkId = $trx->id_transaksi_penjualan;
                $this->logDetailMeta = [
                    'tanggal' => $trx->tanggal_transaksi->translatedFormat('d M Y, H:i'),
                    'pelanggan' => $trx->pelanggan->nama ?? 'Walk-in',
                    'marketing' => $trx->marketing->nama ?? '-',
                    'kasir' => $trx->user->name ?? '-',
                    'status' => $trx->status_penjualan,
                    'total' => $trx->total_harga,
                ];
                $this->logDetailItems = $trx->detailPenjualan->map(fn ($d) => [
                    'nama' => $d->produk->nama_produk ?? '-',
                    'jumlah' => $d->jumlah_display,
                    'satuan' => $d->satuan_saat_jual,
                    'harga' => $d->harga_satuan,
                    'subtotal' => $d->subtotal,
                ])->toArray();
                $this->isLogModalOpen = true;

                return;
            }
        }

        // 2. Log terkait Retur (MASUK karena retur)
        if ($log->id_retur) {
            $retur = TransaksiRetur::with([
                'transaksiPenjualan', 'user',
                'detailRetur.produkDikembalikan', 'detailRetur.produkPengganti',
            ])->find($log->id_retur);

            if ($retur) {
                $this->logModalType = 'RETUR';
                $this->logModalTitle = 'Detail Transaksi Retur';
                $this->logLinkType = 'RETUR';
                $this->logLinkId = $retur->id_retur;
                $this->logDetailMeta = [
                    'kode' => $retur->kode_retur,
                    'nota_asal' => optional($retur->transaksiPenjualan)->tanggal_transaksi?->translatedFormat('d M Y, H:i') ?? '-',
                    'tanggal' => $retur->tanggal_retur->translatedFormat('d M Y, H:i'),
                    'petugas' => $retur->user->name ?? '-',
                    'total' => $retur->total_biaya_retur,
                ];
                $this->logDetailItems = $retur->detailRetur->map(fn ($d) => [
                    'dikembalikan' => $d->produkDikembalikan->nama_produk ?? '-',
                    'pengganti' => $d->produkPengganti->nama_produk ?? null,
                    'jumlah' => $d->jumlah + 0,
                    'kondisi' => $d->kondisi_barang_dikembalikan,
                    'subtotal' => $d->subtotal_biaya,
                ])->toArray();
                $this->isLogModalOpen = true;

                return;
            }
        }

        // 3. Penyesuaian stok manual / mutasi rol (tidak terkait nota)
        $isRol = in_array($log->tipe, ['ROL_MASUK', 'ROL_KELUAR']);
        $this->logModalType = 'STOK';
        $this->logModalTitle = 'Detail Penyesuaian Stok';
        $this->logDetailMeta = [
            'tipe' => str_replace('_', ' ', $log->tipe),
            'produk' => $log->produk->nama_produk ?? '-',
            'petugas' => $log->user->name ?? 'Sistem',
            'tanggal' => $log->created_at->translatedFormat('d M Y, H:i'),
            'is_rol' => $isRol,
            'jumlah' => $isRol ? abs($log->rol_mutasi) : abs($log->jumlah) + 0,
            'sebelum' => $isRol ? $log->rol_sebelum : $log->stok_sebelum + 0,
            'sesudah' => $isRol ? $log->rol_sesudah : $log->stok_sesudah + 0,
            'keterangan' => $log->keterangan,
        ];
        $this->isLogModalOpen = true;
    }

    public function closeLogModal()
    {
        $this->isLogModalOpen = false;
    }

    public function render()
    {
        $isOwner = Auth::user()->peran === 'OWNER';

        // Range: Bulan ini (Tanggal 1 s/d Hari ini)
        $awalBulan = Carbon::now('Asia/Jakarta')->startOfMonth()->startOfDay();
        $awalHariIni = Carbon::now('Asia/Jakarta')->startOfDay();
        $hariIni = Carbon::now('Asia/Jakarta')->endOfDay();
        $hariIniDate = Carbon::now('Asia/Jakarta');
        $labelBulan = Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('F Y');

        // 1. DATA KEUANGAN BULAN INI (Omset hanya untuk Owner)
        $omsetBulanIni = 0;
        $returBulanIni = 0;
        if ($isOwner) {
            $omsetBulanIni = TransaksiPenjualan::whereBetween('tanggal_transaksi', [$awalBulan, $hariIni])
                ->where('status_penjualan', '!=', 'DIBATALKAN')
                ->sum('total_harga');
            $returBulanIni = TransaksiRetur::whereBetween('tanggal_retur', [$awalBulan, $hariIni])->sum('total_biaya_retur');
        }

        $notaCount = TransaksiPenjualan::whereBetween('tanggal_transaksi', [$awalBulan, $hariIni])->count();
        $notaCountHari = TransaksiPenjualan::whereBetween('tanggal_transaksi', [$awalHariIni, $hariIni])->count();
        // 2. DATA CHART (Bulan Ini — 1 batch query)
        $dailyCounts = TransaksiPenjualan::whereBetween('tanggal_transaksi', [$awalBulan, $hariIni])
            ->selectRaw('DATE(tanggal_transaksi) as tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $chartLabels = [];
        $chartData = [];
        for ($date = $awalBulan->copy(); $date->lte($hariIniDate); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $chartLabels[] = $date->format('d');
            $chartData[] = $dailyCounts[$key] ?? 0;
        }

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // 3. TOP PERFORMER MARKETING (Filtered)
        $topMarketing = TransaksiPenjualan::with('marketing')
            ->whereNotNull('id_marketing')
            ->whereBetween('tanggal_transaksi', [$start, $end])
            ->where('status_penjualan', '!=', 'DIBATALKAN')
            ->select('id_marketing', DB::raw('count(*) as total_nota'), DB::raw('sum(total_harga) as total_revenue'))
            ->groupBy('id_marketing')
            ->orderByDesc($isOwner ? 'total_revenue' : 'total_nota')
            ->limit(5)->get();

        // 4. TOP PELANGGAN AKTIF (Filtered)
        $topPelanggan = TransaksiPenjualan::with('pelanggan')
            ->whereNotNull('id_pelanggan')
            ->whereBetween('tanggal_transaksi', [$start, $end])
            ->where('status_penjualan', '!=', 'DIBATALKAN')
            ->select('id_pelanggan', DB::raw('count(*) as total_nota'), DB::raw('sum(total_harga) as total_revenue'))
            ->groupBy('id_pelanggan')
            ->orderByDesc($isOwner ? 'total_revenue' : 'total_nota')
            ->limit(5)->get();

        // 5. AUDIT LOG (Aktivitas User — rentang tanggal, dikelompokkan per hari)
        $logStart = Carbon::parse($this->logStartDate)->startOfDay();
        $logEnd = Carbon::parse($this->logEndDate)->endOfDay();
        $aktivitasLog = RiwayatStok::with(['user', 'produk'])
            ->whereBetween('created_at', [$logStart, $logEnd])
            ->orderBy('id_riwayat', 'desc')
            ->limit(150)
            ->get()
            ->groupBy(fn ($l) => $l->created_at->format('Y-m-d'));

        // 6. MONITORING GUDANG (Stok Menipis & Habis)
        $baseGudang = Produk::where('status_aktif', true)->where('lacak_stok', true);
        $totalSKU = (clone $baseGudang)->count();
        $stokHabis = (clone $baseGudang)->where('stok_saat_ini', '<=', 0)->count();
        $stokMenipis = (clone $baseGudang)->where('stok_saat_ini', '>', 0)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereIn(DB::raw('LOWER(satuan)'), ['pcs', 'biji', 'unit', 'buah'])
                      ->where('stok_saat_ini', '<=', 20);
                })->orWhere(function ($q) {
                    $q->whereNotIn(DB::raw('LOWER(satuan)'), ['pcs', 'biji', 'unit', 'buah'])
                      ->where('stok_saat_ini', '<=', 1);
                });
            })->count();
        $nilaiInventaris = Produk::where('status_aktif', true)
            ->where('lacak_stok', true)
            ->where('stok_saat_ini', '>', 0)
            ->sum(DB::raw('stok_saat_ini * harga_jual_satuan'));

        return view('livewire.dashboard', [
            'isOwner' => $isOwner,
            'omsetBulanIni' => $omsetBulanIni,
            'returBulanIni' => $returBulanIni,
            'notaCount' => $notaCount,
            'notaCountHari' => $notaCountHari,
            'labelBulan' => $labelBulan,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'topMarketing' => $topMarketing,
            'topPelanggan' => $topPelanggan,
            'aktivitasLog' => $aktivitasLog,
            'totalSKU' => $totalSKU,
            'stokHabis' => $stokHabis,
            'stokMenipis' => $stokMenipis,
            'nilaiInventaris' => $nilaiInventaris,
        ]);
    }
}