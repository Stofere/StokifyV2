<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TransaksiPenjualan;
use App\Models\TransaksiRetur;
use App\Models\DetailPenjualan;
use App\Services\AdjustmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;

class RiwayatTransaksi extends Component
{
    use WithPagination;

    public $activeTab = 'POS'; // 'POS' atau 'RETUR'

    // Filter
    public $tgl_mulai;
    public $tgl_akhir;
    public $keyword = '';

    // Modal Detail
    public $modal_open = false;
    public $detail_nota = null;

    // --- STATE KOREKSI QTY NOTA ---
    public $showKoreksiModal = false;        // Modal input koreksi
    public $showKoreksiConfirmModal = false; // Modal konfirmasi + password
    public $koreksi_id_detail = null;
    public $koreksi_nama_produk = '';
    public $koreksi_satuan = '';
    public $koreksi_is_dual_unit = false;
    public $koreksi_qty_lama = 0;
    public $koreksi_potong_lama = 0;
    public $koreksi_qty_baru = 0;
    public $koreksi_potong_baru = 0;
    public $koreksi_harga_satuan = 0;
    public $koreksi_diretur = 0;
    public $koreksi_alasan = '';
    public $password_admin = '';

    public function mount()
    {
        // Default filter: Hari ini
        $this->tgl_mulai = today()->format('Y-m-d');
        $this->tgl_akhir = today()->format('Y-m-d');
    }

    public function switchTab($tabName)
    {
        $this->activeTab = $tabName;
        $this->resetPage(); // Reset pagination saat pindah tab
    }

    public function updatingKeyword() { $this->resetPage(); }
    public function updatedTglMulai() { $this->resetPage(); }
    public function updatedTglAkhir() { $this->resetPage(); }

    public function lihatDetail($id, $tipe_paksa = null)
    {
        // Jika dari klik tombol "Lihat Nota Retur" di dalam Nota POS, tipe_paksa akan terisi 'RETUR'
        if ($tipe_paksa) {
            $this->activeTab = $tipe_paksa; // Paksa pindah tab state sementara di memori
        }

        if ($this->activeTab === 'POS') {
            // FIX: Tambahkan relasi transaksiRetur.detailRetur.produkPengganti untuk melacak jejak retur di dalam Nota POS
            $this->detail_nota = TransaksiPenjualan::with([
                'detailPenjualan.produk',
                'user',
                'pelanggan',
                'marketing',
                'transaksiRetur.detailRetur.produkPengganti', // <--- RELASI BARU
                'riwayatKoreksi.produk',                      // Audit koreksi qty
                'riwayatKoreksi.user',
            ])->find($id);
        } else {
            $this->detail_nota = TransaksiRetur::with([
                'detailRetur.produkDikembalikan', 
                'detailRetur.produkPengganti', 
                'user', 
                'transaksiPenjualan.pelanggan'
            ])->find($id);
        }
        
        $this->modal_open = true;
    }

    public function tutupModal()
    {
        $this->modal_open = false;
        $this->detail_nota = null;
    }

    // ==================== KOREKSI QTY NOTA ====================

    public function bukaKoreksi($idDetail)
    {
        $detail = DetailPenjualan::with('produk')->find($idDetail);
        if (!$detail) {
            session()->flash('error', 'Detail barang tidak ditemukan.');
            return;
        }

        $this->resetErrorBag();
        $this->koreksi_id_detail   = $detail->id_detail_penjualan;
        $this->koreksi_nama_produk = $detail->produk->nama_produk ?? '-';
        $this->koreksi_satuan      = $detail->satuan_saat_jual;
        $this->koreksi_is_dual_unit = strtolower($detail->satuan_saat_jual) === 'meter';
        $this->koreksi_qty_lama    = (float) $detail->jumlah;
        $this->koreksi_potong_lama = $detail->jumlah_potong_gudang !== null
            ? (float) $detail->jumlah_potong_gudang
            : (float) $detail->jumlah;
        $this->koreksi_qty_baru    = (float) $detail->jumlah;
        $this->koreksi_potong_baru = $this->koreksi_potong_lama;
        $this->koreksi_harga_satuan = (float) $detail->harga_satuan;
        $this->koreksi_diretur     = (float) $detail->jumlah_diretur;
        $this->koreksi_alasan      = '';
        $this->password_admin      = '';

        $this->showKoreksiModal = true;
    }

    public function reviewKoreksi()
    {
        $rules = [
            'koreksi_qty_baru' => "required|numeric|gt:0|gte:{$this->koreksi_diretur}",
            'koreksi_alasan'   => 'required|string|min:5',
        ];
        if ($this->koreksi_is_dual_unit) {
            $rules['koreksi_potong_baru'] = 'required|numeric|gt:0';
        }

        $this->validate($rules, [
            'koreksi_qty_baru.gte' => "Qty baru tidak boleh kurang dari yang sudah diretur ({$this->koreksi_diretur}).",
            'koreksi_qty_baru.gt'  => 'Qty baru harus lebih dari 0.',
            'koreksi_alasan.min'   => 'Alasan koreksi wajib diisi minimal 5 karakter.',
            'koreksi_potong_baru.gt' => 'Berat timbangan (KG) harus lebih dari 0.',
        ]);

        // Satuan Pcs tidak boleh desimal
        if (in_array(strtolower($this->koreksi_satuan), ['pcs', 'biji', 'unit', 'buah']) && fmod((float) $this->koreksi_qty_baru, 1) !== 0.0) {
            $this->addError('koreksi_qty_baru', "Barang satuan {$this->koreksi_satuan} tidak boleh ada koma/desimal!");
            return;
        }

        $this->showKoreksiConfirmModal = true;
    }

    public function prosesKoreksi(AdjustmentService $adjustmentService)
    {
        if (!Hash::check($this->password_admin, Auth::user()->password)) {
            $this->addError('password_admin', 'Password otorisasi salah!');
            return;
        }

        try {
            $adjustmentService->adjustDetailPenjualan(
                (int) $this->koreksi_id_detail,
                (float) $this->koreksi_qty_baru,
                $this->koreksi_is_dual_unit ? (float) $this->koreksi_potong_baru : null,
                $this->koreksi_alasan,
                Auth::id()
            );

            session()->flash('sukses', 'Koreksi qty berhasil! Stok sistem telah disesuaikan otomatis.');

            // Refresh data modal detail agar qty/subtotal/total & riwayat koreksi terbarui
            if ($this->detail_nota) {
                $this->lihatDetail($this->detail_nota->id_transaksi_penjualan, 'POS');
            }

            $this->tutupKoreksi();
        } catch (Exception $e) {
            $this->addError('password_admin', $e->getMessage());
        }
    }

    public function tutupKoreksi()
    {
        $this->reset([
            'showKoreksiModal', 'showKoreksiConfirmModal', 'koreksi_id_detail',
            'koreksi_nama_produk', 'koreksi_satuan', 'koreksi_is_dual_unit',
            'koreksi_qty_lama', 'koreksi_potong_lama', 'koreksi_qty_baru',
            'koreksi_potong_baru', 'koreksi_harga_satuan', 'koreksi_diretur',
            'koreksi_alasan', 'password_admin',
        ]);
        $this->resetErrorBag();
    }

    public function render()
    {
        $queryPOS = TransaksiPenjualan::with(['user', 'pelanggan', 'marketing'])->withCount('riwayatKoreksi');
        $queryRetur = TransaksiRetur::with(['user', 'transaksiPenjualan.pelanggan']);

        // Filter Tanggal
        if ($this->tgl_mulai && $this->tgl_akhir) {
            $queryPOS->whereBetween('tanggal_transaksi', [$this->tgl_mulai . ' 00:00:00', $this->tgl_akhir . ' 23:59:59']);
            $queryRetur->whereBetween('tanggal_retur', [$this->tgl_mulai . ' 00:00:00', $this->tgl_akhir . ' 23:59:59']);
        }

        // Filter Keyword
        if (!empty(trim($this->keyword))) {
            $kw = '%' . trim($this->keyword) . '%';
            
            $queryPOS->where(function($q) use ($kw) {
                $q->where('kode_nota', 'LIKE', $kw)
                  ->orWhereHas('pelanggan', fn($p) => $p->where('nama', 'LIKE', $kw))
                  ->orWhereHas('marketing', fn($m) => $m->where('nama', 'LIKE', $kw));
            });

            $queryRetur->where(function($q) use ($kw) {
                $q->where('kode_retur', 'LIKE', $kw)
                  ->orWhereHas('transaksiPenjualan.pelanggan', fn($p) => $p->where('nama', 'LIKE', $kw));
            });
        }

        return view('livewire.transaksi.riwayat-transaksi', [
            'daftarPos' => $this->activeTab === 'POS' ? $queryPOS->orderBy('id_transaksi_penjualan', 'desc')->paginate(10) : null,
            'daftarRetur' => $this->activeTab === 'RETUR' ? $queryRetur->orderBy('id_retur', 'desc')->paginate(10) : null,
        ]);
    }
}