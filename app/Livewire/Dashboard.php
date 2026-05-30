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
        
        $isOwner = Auth::user()->peran === 'OWNER';

        $this->marketingDrilldownData = TransaksiPenjualan::with('pelanggan')
            ->where('id_marketing', $marketingId)
            ->whereBetween('tanggal_transaksi', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->where('status_penjualan', '!=', 'DIBATALKAN')
            ->whereNotNull('id_pelanggan')
            ->select('id_pelanggan', DB::raw('count(*) as total_nota'), DB::raw('sum(total_harga) as total_revenue'))
            ->groupBy('id_pelanggan')
            ->orderByDesc($isOwner ? 'total_revenue' : 'total_nota')
            ->get();
            
        $this->isMarketingModalOpen = true;
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
        $hariIni = today();
        $isOwner = Auth::user()->peran === 'OWNER';

        // 1. DATA KEUANGAN (Hanya untuk Owner)
        $omsetHariIni = 0;
        $returHariIni = 0;
        if ($isOwner) {
            $omsetHariIni = TransaksiPenjualan::whereDate('tanggal_transaksi', $hariIni)
                ->where('status_penjualan', '!=', 'DIBATALKAN')
                ->sum('total_harga');
            $returHariIni = TransaksiRetur::whereDate('tanggal_retur', $hariIni)->sum('total_biaya_retur');
        }

        $notaCount = TransaksiPenjualan::whereDate('tanggal_transaksi', $hariIni)->count();

        // 2. DATA CHART (7 Hari Terakhir & Bulan Ini)
        $chartLabels7Hari = [];
        $chartData7Hari = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartLabels7Hari[] = $date->format('d M');
            $chartData7Hari[] = TransaksiPenjualan::whereDate('tanggal_transaksi', $date)->count();
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
            ->whereDate('created_at', $hariIni)
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
            'omsetHariIni' => $omsetHariIni,
            'returHariIni' => $returHariIni,
            'notaCount' => $notaCount,
            'chartLabels7Hari' => $chartLabels7Hari,
            'chartData7Hari' => $chartData7Hari,
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