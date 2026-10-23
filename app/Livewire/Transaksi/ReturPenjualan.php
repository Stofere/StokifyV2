<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\TransaksiPenjualan;
use App\Models\DetailPenjualan;
use App\Models\Produk;
use App\Models\Pelanggan;
use App\Models\Marketing;
use App\Services\ReturnService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ReturPenjualan extends Component
{
    // State Filter Nota
    public $filter_tanggal_mulai;
    public $filter_tanggal_akhir;
    public $filter_keyword = '';
    public $filter_pelanggan_id = '';
    public $filter_marketing_id = '';
    
    // Data Dropdown Filter
    public $daftarPelanggan = [];
    public $daftarMarketing = [];

    // State Tampilan Nota
    public $notaTerpilih = null;

    // State Modal Retur
    public $showReturModal = false;
    public $detailTerpilih = null;
    public $qty_retur = 1;
    public $kondisi_retur = 'BAGUS';
    public $jenis_retur = 'BATAL_BELI'; // 'BATAL_BELI' (tanpa pengganti, uang dikembalikan) | 'TUKAR' (ganti barang)
    public $jumlah_potong_gudang_retur = 0; // KG fisik yang dikembalikan (editable oleh admin)
    public $satuan_pengganti = ''; // Satuan untuk barang pengganti: 'kg', 'meter', 'pcs', dll
    public $tanggal_retur; // Custom tanggal retur
    
    // State Pencarian Barang Pengganti
    public $search_produk_pengganti = '';
    public $produk_pengganti = null;
    
    // State Form Eksekusi
    public $catatan = '';
    public $password_admin = '';

    public function mount()
    {
        $this->tanggal_retur = now()->format('Y-m-d\TH:i');
        $this->filter_tanggal_mulai = Carbon::now()->subDays(7)->format('Y-m-d');
        $this->filter_tanggal_akhir = Carbon::now()->format('Y-m-d');
        
        $this->daftarPelanggan = Pelanggan::where('aktif', true)->orderBy('nama')->get();
        $this->daftarMarketing = Marketing::where('aktif', true)->orderBy('nama')->get();
    }

    public function pilihNota($id_transaksi)
    {
        $this->notaTerpilih = TransaksiPenjualan::with(['detailPenjualan.produk', 'user', 'pelanggan'])
            ->find($id_transaksi);
    }

    public function batalPilihNota()
    {
        $this->notaTerpilih = null;
        $this->tutupModalRetur();
    }

    public function bukaModalRetur($id_detail)
    {
        $this->detailTerpilih = DetailPenjualan::with('produk')->find($id_detail);
        $this->produk_pengganti = $this->detailTerpilih->produk; 
        
        $this->qty_retur = 1;
        $this->kondisi_retur = 'BAGUS';
        $this->jenis_retur = 'BATAL_BELI'; // Kasus paling sering: pelanggan tidak jadi beli
        $this->search_produk_pengganti = '';
        $this->catatan = '';
        $this->password_admin = '';
        
        // Pre-populasi KG retur berdasarkan data timbangan saat jual (proporsional untuk 1 unit)
        $isDualUnit = strtolower($this->detailTerpilih->satuan_saat_jual) === 'meter';
        if ($isDualUnit) {
            $kgTotal = $this->detailTerpilih->jumlah_potong_gudang ?? $this->detailTerpilih->jumlah;
            $meterTotal = $this->detailTerpilih->jumlah;
            $this->jumlah_potong_gudang_retur = ($meterTotal > 0) ? round($kgTotal / $meterTotal, 3) : 0;
        } else {
            $this->jumlah_potong_gudang_retur = 0;
        }

        // Default satuan pengganti mengikuti satuan nota asli
        $this->satuan_pengganti = strtolower($this->detailTerpilih->satuan_saat_jual);
        // Default tanggal retur = sekarang
        $this->tanggal_retur = now()->format('Y-m-d\TH:i');
        
        $this->showReturModal = true;
    }

    public function tutupModalRetur()
    {
        $this->showReturModal = false;
        $this->detailTerpilih = null;
        $this->produk_pengganti = null;
    }

    // Recalculate KG proporsional saat admin ubah jumlah retur (meter)
    public function updatedQtyRetur()
    {
        if ($this->detailTerpilih && strtolower($this->detailTerpilih->satuan_saat_jual) === 'meter') {
            $kgTotal = $this->detailTerpilih->jumlah_potong_gudang ?? $this->detailTerpilih->jumlah;
            $meterTotal = $this->detailTerpilih->jumlah;
            $this->jumlah_potong_gudang_retur = ($meterTotal > 0) 
                ? round(($kgTotal / $meterTotal) * $this->qty_retur, 3) 
                : 0;
        }
    }

    public function pilihBarangPengganti($id_produk)
    {
        $this->produk_pengganti = Produk::find($id_produk);
        $this->search_produk_pengganti = '';
        
        // Cek apakah pengganti juga punya harga meter (dual-unit)
        $hasMeter = isset($this->produk_pengganti->metadata['harga_meter']);
        
        // Default: ikut satuan nota asli. Jika pengganti tidak punya meter, fallback ke satuan utama produk
        $satuanAsli = strtolower($this->detailTerpilih->satuan_saat_jual ?? '');
        if ($satuanAsli === 'meter' && $hasMeter) {
            $this->satuan_pengganti = 'meter';
        } else {
            $this->satuan_pengganti = strtolower($this->produk_pengganti->satuan);
        }
    }

    // Hook: saat admin ubah satuan pengganti
    public function updatedSatuanPengganti()
    {
        // Tidak perlu logika tambahan, blade akan auto-recalculate selisih via @php block
    }

    public function prosesRetur(ReturnService $returnService)
    {
        $sisaMaksimal = $this->detailTerpilih->jumlah - $this->detailTerpilih->jumlah_diretur;

        $validationRules = [
            'jenis_retur' => 'required|in:BATAL_BELI,TUKAR',
            'qty_retur' => "required|numeric|min:0.01|max:$sisaMaksimal",
            'kondisi_retur' => 'required|in:BAGUS,RUSAK',
            'catatan' => 'required|string|min:3',
            'password_admin' => 'required',
        ];

        // Tambah validasi KG retur untuk barang dual-unit
        $isDualUnit = strtolower($this->detailTerpilih->satuan_saat_jual) === 'meter';
        if ($isDualUnit) {
            $validationRules['jumlah_potong_gudang_retur'] = 'required|numeric|min:0.001';
        }

        $this->validate($validationRules);

        // BACKEND DOUBLE CHECK: gunakan satuan_saat_jual (nota), bukan satuan produk
        $satuanNota = strtolower($this->detailTerpilih->satuan_saat_jual);
        if (in_array($satuanNota, ['pcs', 'biji', 'unit', 'buah']) && fmod($this->qty_retur, 1) !== 0.0) {
            $this->addError('qty_retur', "Barang dengan satuan {$satuanNota} tidak boleh diretur dengan nilai koma (desimal)!");
            return;
        }

        $isTukar = $this->jenis_retur === 'TUKAR';

        if ($isTukar && !$this->produk_pengganti) {
            $this->addError('search_produk_pengganti', 'Pilih barang pengganti terlebih dahulu!');
            return;
        }

        if (!Hash::check($this->password_admin, Auth::user()->password)) {
            $this->addError('password_admin', 'Password otorisasi salah!');
            return;
        }

        // Hitung harga pengganti berdasarkan satuan yang dipilih (Batal Beli: tidak ada pengganti)
        $hargaPenggantiPerUnit = null;
        if ($isTukar) {
            $hargaPenggantiPerUnit = $this->produk_pengganti->harga_jual_satuan; // default: harga KG/satuan utama
            if ($this->satuan_pengganti === 'meter' && isset($this->produk_pengganti->metadata['harga_meter'])) {
                $hargaPenggantiPerUnit = $this->produk_pengganti->metadata['harga_meter'];
            }
        }

        $itemsRetur = [
            [
                'id_detail_penjualan' => $this->detailTerpilih->id_detail_penjualan,
                'id_produk_pengganti' => $isTukar ? $this->produk_pengganti->id_produk : null,
                'jumlah' => $this->qty_retur,
                'kondisi_barang_dikembalikan' => $this->kondisi_retur,
                'jumlah_potong_gudang_retur' => $isDualUnit ? $this->jumlah_potong_gudang_retur : null,
                'satuan_pengganti' => $isTukar ? $this->satuan_pengganti : null,
                'harga_pengganti_per_unit' => $hargaPenggantiPerUnit,
            ]
        ];

        // Parse tanggal retur custom
        $tanggalReturParsed = Carbon::parse($this->tanggal_retur);

        try {
            $retur = $returnService->prosesRetur(
                $this->notaTerpilih->id_transaksi_penjualan,
                Auth::id(),
                $itemsRetur,
                $this->catatan,
                $tanggalReturParsed
            );

            if ($isTukar) {
                session()->flash('sukses', 'Proses Retur Berhasil! Mutasi stok & uang telah disesuaikan.');
            } else {
                session()->flash('sukses', 'Retur Batal Beli berhasil! Uang dikembalikan ke pelanggan: Rp ' . number_format(abs($retur->total_biaya_retur), 0, ',', '.') . '.');
            }
            $this->notaTerpilih->refresh(); 
            $this->tutupModalRetur();

        } catch (Exception $e) {
            $this->addError('password_admin', $e->getMessage()); 
        }
    }

    public function render()
    {
        $daftar_nota = collect();
        if (!$this->notaTerpilih) {
            $queryNota = TransaksiPenjualan::with(['pelanggan', 'marketing'])
                ->whereBetween('tanggal_transaksi', [
                    $this->filter_tanggal_mulai . ' 00:00:00',
                    $this->filter_tanggal_akhir . ' 23:59:59'
                ]);

            if ($this->filter_pelanggan_id) {
                $queryNota->where('id_pelanggan', $this->filter_pelanggan_id);
            }

            if ($this->filter_marketing_id) {
                $queryNota->where('id_marketing', $this->filter_marketing_id);
            }

            if (!empty(trim($this->filter_keyword))) {
                $queryNota->where(function($q) {
                    $q->where('kode_nota', 'LIKE', '%' . $this->filter_keyword . '%')
                      ->orWhereHas('pelanggan', function($q2) {
                          $q2->where('nama', 'LIKE', '%' . $this->filter_keyword . '%');
                      })
                      ->orWhereHas('marketing', function($q3) {
                          $q3->where('nama', 'LIKE', '%' . $this->filter_keyword . '%');
                      });
                });
            }
            $daftar_nota = $queryNota->orderBy('tanggal_transaksi', 'desc')->limit(50)->get();
        }

        $hasil_pencarian_produk = collect();
        if ($this->showReturModal && $this->jenis_retur === 'TUKAR' && !empty(trim($this->search_produk_pengganti))) {
            $queryProduk = Produk::where('status_aktif', true);
            $terms = explode(' ', trim(strtolower($this->search_produk_pengganti)));
            
            foreach ($terms as $term) {
                $queryProduk->where('index_pencarian', 'LIKE', '%' . $term . '%');
            }
            $hasil_pencarian_produk = $queryProduk->limit(10)->get();
        }

        return view('livewire.transaksi.retur-penjualan', [
            'daftar_nota' => $daftar_nota,
            'hasil_pencarian_produk' => $hasil_pencarian_produk
        ]);
    }
}