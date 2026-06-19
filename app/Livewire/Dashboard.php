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

    public function mount()
    {
        $this->startDate = Carbon::now('Asia/Jakarta')->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now('Asia/Jakarta')->endOfMonth()->format('Y-m-d');
        $this->updateFilterText();
    }

    public function updatedStartDate() { $this->updateFilterText(); }
    public function updatedEndDate() { $this->updateFilterText(); }

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

        // 5. AUDIT LOG (Aktivitas User Hari Ini)
        $aktivitasHariIni = RiwayatStok::with(['user', 'produk', 'transaksiPenjualan'])
            ->whereDate('created_at', today())
            ->orderBy('id_riwayat', 'desc')
            ->limit(8)
            ->get();

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
            'notaCountHari' => $notaCountHari,
            'labelBulan' => $labelBulan,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'topMarketing' => $topMarketing,
            'topPelanggan' => $topPelanggan,
            'aktivitasHariIni' => $aktivitasHariIni,
            'totalSKU' => $totalSKU,
            'stokHabis' => $stokHabis,
            'stokMenipis' => $stokMenipis,
            'nilaiInventaris' => $nilaiInventaris,
        ]);
    }
}