<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Produk;
use App\Models\Pelanggan;
use App\Models\Marketing;
use App\Models\TransaksiPenjualan;
use App\Services\TransactionService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;


class KasirPos extends Component
{
    public $keyword = '';
    
    // --- STATE PELANGGAN (WAJIB) ---
    public $id_pelanggan = null;
    public $pelangganTerpilihNama = '';
    public $searchPelanggan = '';
    
    // State Pelanggan Baru (Disimpan di Frontend dulu)
    public $is_pelanggan_baru = false;
    public $pelanggan_baru_nama = '';
    public $pelanggan_baru_telepon = '';
    public $pelanggan_baru_alamat = '';

    // --- STATE MARKETING (WAJIB) ---
    public $id_marketing = null;
    public $marketingTerpilihNama = '';
    public $searchMarketing = '';

    public $catatan = '';
    public $keranjang = []; 
    public $total_belanja = 0;
    public $tanggal_transaksi; // Custom tanggal transaksi (backdate support)
    
    public $showConfirmModal = false;

    public function mount()
    {
        // Default tanggal_transaksi = tanggal dari transaksi terbaru di database
        // Agar admin yang telat input tidak perlu mengubah tanggal berulang kali
        $lastTrx = TransaksiPenjualan::latest('tanggal_transaksi')->first();
        if ($lastTrx) {
            $this->tanggal_transaksi = Carbon::parse($lastTrx->tanggal_transaksi)->format('Y-m-d\TH:i');
        } else {
            $this->tanggal_transaksi = now()->format('Y-m-d\TH:i');
        }
    }

    // --- FUNGSI PELANGGAN ---
    public function pilihPelanggan($id, $nama)
    {
        $this->is_pelanggan_baru = false;
        $this->id_pelanggan = $id;
        $this->pelangganTerpilihNama = $nama;
        $this->searchPelanggan = '';
        $this->resetErrorBag('pelanggan_wajib');
    }

    public function hapusPelanggan()
    {
        $this->id_pelanggan = null;
        $this->pelangganTerpilihNama = '';
        $this->is_pelanggan_baru = false;
        $this->pelanggan_baru_nama = '';
        $this->pelanggan_baru_telepon = '';
        $this->pelanggan_baru_alamat = '';
    }

    public function setPelangganBaru()
    {
        $this->validate(['searchPelanggan' => 'required|min:2|max:255']);
        
        $this->is_pelanggan_baru = true;
        $this->pelanggan_baru_nama = $this->searchPelanggan;
        $this->pelangganTerpilihNama = $this->searchPelanggan . ' (Pelanggan Baru)';
        $this->id_pelanggan = null;
        $this->searchPelanggan = '';
        $this->resetErrorBag('pelanggan_wajib');
    }

    // --- FUNGSI MARKETING ---
    public function pilihMarketing($id, $nama)
    {
        $this->id_marketing = $id;
        $this->marketingTerpilihNama = $nama;
        $this->searchMarketing = '';
        $this->resetErrorBag('marketing_wajib');
    }

    public function hapusMarketing()
    {
        $this->id_marketing = null;
        $this->marketingTerpilihNama = '';
    }

    // --- FUNGSI KERANJANG ---
    public function tambahKeKeranjang(int $idProduk)
    {
        $produk = Produk::with('kategori')->find($idProduk);
        if (!$produk || !$produk->status_aktif) return;

        $index = collect($this->keranjang)->search(fn($item) => $item['id_produk'] === $idProduk);

        if ($index !== false) {
            // Jika sudah ada, cukup berikan flash message
            session()->flash('sukses', "Barang sudah ada di keranjang, silakan ubah jumlahnya.");
        } else {
            if ($produk->lacak_stok && $produk->stok_saat_ini <= 0) {
                session()->flash('error', "Stok {$produk->nama_produk} habis!");
                return;
            }
            
            // Cek apakah barang ini punya Harga Meter (Dual Unit)
            $hasEceran = isset($produk->metadata['harga_meter']);
            // Flag: Kabel (lacak_rol) → tidak perlu input potong fisik terpisah
            $isKabel = $produk->kategori->lacak_rol ?? false;

            $this->keranjang[] = [
                'id_produk' => $produk->id_produk,
                'kode_barang' => $produk->kode_barang, // Penanda SKU di keranjang & recheck nota
                'nama_produk' => $produk->nama_produk,
                'satuan_utama' => strtolower($produk->satuan),
                'harga_utama' => $produk->harga_jual_satuan,
                
                // Variabel Dual-Unit
                'has_eceran' => $hasEceran,
                'harga_eceran' => $hasEceran ? $produk->metadata['harga_meter'] : 0,
                'tipe_jual' => 'utama', // 'utama' (KG/Pcs) atau 'eceran' (Meter)
                'is_kabel' => $isKabel, // Kabel: potong gudang = jumlah jual (tanpa input terpisah)
                
                // Input User
                'jumlah_jual' => 1, // Angka yang tampil di nota (Bisa Meter / KG)
                'jumlah_potong_gudang' => 1, // Angka berat fisik yang memotong stok gudang
                
                'harga_terpakai' => $produk->harga_jual_satuan,
                'subtotal' => $produk->harga_jual_satuan,
                'max_stok' => $produk->lacak_stok ? $produk->stok_saat_ini : 999999,
                'lacak_stok' => $produk->lacak_stok,
            ];
        }
        $this->hitungTotal();
    }

    // FUNGSI BARU: Mengganti Tipe Jual (Tombol Switch KG <-> Meter)
    public function gantiTipeJual($index, $tipe)
    {
        $this->keranjang[$index]['tipe_jual'] = $tipe;
        $isKabel = $this->keranjang[$index]['is_kabel'] ?? false;
        
        if ($tipe === 'eceran') {
            // Jika pindah ke Meter, ubah harga yang terpakai ke harga meteran
            $this->keranjang[$index]['harga_terpakai'] = $this->keranjang[$index]['harga_eceran'];
            if ($isKabel) {
                // Kabel: potong gudang selalu sync dengan jumlah jual (tanpa input manual terpisah)
                $this->keranjang[$index]['jumlah_potong_gudang'] = $this->keranjang[$index]['jumlah_jual'];
            } else {
                // Kertas Film: Reset input gudang jadi 0 agar kasir ingat untuk menimbang
                $this->keranjang[$index]['jumlah_potong_gudang'] = 0; 
            }
        } else {
            $this->keranjang[$index]['harga_terpakai'] = $this->keranjang[$index]['harga_utama'];
            // Jika KG, maka jumlah jual sama dengan jumlah potong gudang
            $this->keranjang[$index]['jumlah_potong_gudang'] = $this->keranjang[$index]['jumlah_jual'];
        }
        
        $this->kalkulasiBaris($index);
    }

    // FIX: Hook ini otomatis jalan saat user mengetik angka di kolom input keranjang (Reaktivitas Harga)
    public function updatedKeranjang($value, $key)
    {
        $parts = explode('.', $key); // format: $key = "0.jumlah_jual" atau "0.jumlah_potong_gudang"
        if (count($parts) == 2) {
            $index = $parts[0];
            $field = $parts[1];
            
            // Sanitize input dari type="text": ganti koma dengan titik, hapus karakter non-numerik
            $sanitized = str_replace(',', '.', (string) $value);
            $sanitized = preg_replace('/[^0-9.]/', '', $sanitized);
            $val = (float) $sanitized;

            $satuanUtama = $this->keranjang[$index]['satuan_utama'];
            $tipeJual = $this->keranjang[$index]['tipe_jual'];

            // ANTI DESIMAL UNTUK PCS
            if (in_array($satuanUtama, ['pcs', 'biji', 'unit', 'buah'])) {
                $val = floor($val);
            }

            // Pastikan nilai minimal 0
            $val = max(0, $val);
            
            $this->keranjang[$index][$field] = $val;

            $isKabel = $this->keranjang[$index]['is_kabel'] ?? false;

            // Jika jual "Utama" (KG), maka jumlah potong gudang harus ngikut jumlah jual
            // Jika Kabel dan jual eceran (Meter), potong gudang juga ngikut jumlah jual (tanpa input manual)
            if ($field === 'jumlah_jual') {
                if ($tipeJual === 'utama' || ($tipeJual === 'eceran' && $isKabel)) {
                    $this->keranjang[$index]['jumlah_potong_gudang'] = $val;
                }
            }

            $this->kalkulasiBaris($index);
        }
    }

    private function kalkulasiBaris($index)
    {
        $item = $this->keranjang[$index];
        $isDualUnit = $item['has_eceran'] ?? false;

        // Validasi Maksimal Stok: Berdasarkan jumlah fisik yang dipotong dari gudang
        if ($item['lacak_stok'] && $item['jumlah_potong_gudang'] > $item['max_stok']) {
            $this->keranjang[$index]['jumlah_potong_gudang'] = $item['max_stok'];
            
            // Untuk non-dual-unit, potong gudang == jumlah jual, jadi clamp keduanya
            if (!$isDualUnit) {
                $this->keranjang[$index]['jumlah_jual'] = $item['max_stok'];
            }
            
            session()->flash('error', "Stok gudang tidak mencukupi! Maksimal: " . $item['max_stok'] . " " . strtoupper($item['satuan_utama']));
        }

        // Validasi tambahan: untuk barang NON-dual-unit, jumlah_jual juga harus dicek terhadap stok
        if (!$isDualUnit && $item['lacak_stok'] && $item['jumlah_jual'] > $item['max_stok']) {
            $this->keranjang[$index]['jumlah_jual'] = $item['max_stok'];
            $this->keranjang[$index]['jumlah_potong_gudang'] = $item['max_stok'];
            session()->flash('error', "Stok gudang tidak mencukupi! Maksimal: " . $item['max_stok'] . " " . strtoupper($item['satuan_utama']));
        }

        // Subtotal selalu dihitung dari jumlah jual (Meter / KG) dikali harga terpakai
        $this->keranjang[$index]['subtotal'] = $this->keranjang[$index]['jumlah_jual'] * $item['harga_terpakai'];
        
        $this->hitungTotal();
    }

    public function hapusItem(int $index)
    {
        unset($this->keranjang[$index]);
        $this->keranjang = array_values($this->keranjang);
        $this->hitungTotal();
    }

    public function hitungTotal()
    {
        $this->total_belanja = collect($this->keranjang)->sum('subtotal');
    }

    // --- FUNGSI PROSES CHECKOUT ---
    public function konfirmasiPembayaran()
    {
        if (empty($this->keranjang)) {
            $this->addError('keranjang', 'Keranjang belanja kosong.');
            return;
        }

        // VALIDASI WAJIB: Pelanggan & Marketing Harus Ada
        if (!$this->id_pelanggan && !$this->is_pelanggan_baru) {
            $this->addError('pelanggan_wajib', 'Pilih atau tambahkan pelanggan terlebih dahulu!');
            return;
        }

        if (!$this->id_marketing) {
            $this->addError('marketing_wajib', 'Sales / Marketing wajib dipilih!');
            return;
        }

        // Pastikan nama termuat untuk tampilan di Modal
        if ($this->id_pelanggan && empty($this->pelangganTerpilihNama)) {
            $this->pelangganTerpilihNama = Pelanggan::find($this->id_pelanggan)->nama;
        }
        if ($this->id_marketing && empty($this->marketingTerpilihNama)) {
            $this->marketingTerpilihNama = Marketing::find($this->id_marketing)->nama;
        }

        // DOUBLE CHECK BACKEND: mencegah desimal di satuan PCS + validasi timbangan wajib untuk jual meter
        foreach ($this->keranjang as $item) {
            if (in_array($item['satuan_utama'], ['pcs', 'biji', 'unit', 'buah']) && fmod($item['jumlah_jual'], 1) !== 0.0) {
                $this->addError('checkout', "Barang {$item['nama_produk']} satuannya Pcs, tidak boleh ada koma/desimal!");
                return;
            }

            // Jika jual meter tapi belum ditimbang → tolak (hanya untuk non-Kabel seperti Kertas Film)
            $isKabelItem = $item['is_kabel'] ?? false;
            if ($item['has_eceran'] && $item['tipe_jual'] === 'eceran' && !$isKabelItem && $item['jumlah_potong_gudang'] <= 0) {
                $this->addError('checkout', "Barang {$item['nama_produk']} dijual per Meter, wajib isi berat timbangan (KG)!");
                return;
            }
        }

        $this->showConfirmModal = true;
    }

    public function prosesPembayaranFinal(TransactionService $transactionService)
    {
        $lock = Cache::lock('proses-bayar:' . Auth::id(), 10);

        if ($lock->get()) {
            try {
                // PENCEGAHAN SPAM: Menggunakan DB Transaction agar pembuatan Pelanggan & Nota berjalan dalam 1 antrean aman
                DB::beginTransaction();
                try {
                    $id_pelanggan_final = $this->id_pelanggan;

                    // Jika pelanggan baru, buat datanya di database SEKARANG
                    if ($this->is_pelanggan_baru) {
                        $newPlg = Pelanggan::create([
                            'nama' => $this->pelanggan_baru_nama,
                            'telepon' => $this->pelanggan_baru_telepon,
                            'alamat' => $this->pelanggan_baru_alamat,
                            'aktif' => true
                        ]);
                        $id_pelanggan_final = $newPlg->id_pelanggan;
                    }

                    $dataNota = [
                        'id_pelanggan' => $id_pelanggan_final,
                        'id_marketing' => $this->id_marketing,
                        'catatan' => $this->catatan,
                        'tanggal_transaksi' => Carbon::parse($this->tanggal_transaksi),
                    ];

                    // Lempar ke otak bisnis untuk diproses
                    $nota = $transactionService->createPenjualan(Auth::id(), $dataNota, $this->keranjang);
                    
                    DB::commit(); // Konfirmasi semua perubahan database (Pelanggan + Transaksi)

                    session()->flash('sukses', "Transaksi berhasil! Nomor Nota: " . $nota->kode_nota);
                    $this->dispatch('transaksi-sukses');
                    
                    // Reset seluruh State (tapi pertahankan tanggal_transaksi untuk batch input)
                    $tanggalSebelumnya = $this->tanggal_transaksi;
                    $this->reset([
                        'keranjang', 'total_belanja', 'catatan', 'keyword', 'showConfirmModal',
                        'id_pelanggan', 'pelangganTerpilihNama', 'searchPelanggan', 'is_pelanggan_baru', 'pelanggan_baru_nama', 'pelanggan_baru_telepon', 'pelanggan_baru_alamat',
                        'id_marketing', 'marketingTerpilihNama', 'searchMarketing'
                    ]);
                    // Restore tanggal ke yang terakhir dipakai agar batch input cepat
                    $this->tanggal_transaksi = $tanggalSebelumnya;

                } catch (Exception $e) {
                    DB::rollBack(); // Batalkan pembuatan pelanggan & nota jika ada yang error (misal stok tiba-tiba habis)
                    $this->showConfirmModal = false;
                    $this->addError('checkout', $e->getMessage());
                }
            } finally {
                $lock->release();
            }
        } else {
            session()->flash('error', 'Transaksi sebelumnya masih diproses. Harap tunggu sebentar.');
        }
    }

    public function render()
    {
        // 1. Pencarian Produk
        $query = Produk::where('status_aktif', true);
        if (!empty(trim($this->keyword))) {
            $terms = explode(' ', trim(strtolower($this->keyword)));
            foreach ($terms as $term) {
                $query->where('index_pencarian', 'LIKE', '%' . $term . '%');
            }
            $hasilPencarian = $query->limit(20)->get();
        } else {
            $hasilPencarian = $query->latest()->limit(12)->get();
        }

        // 2. Pencarian Cepat Pelanggan
        // FIX: Pencarian Cepat (Dibuat lebih ringan & tanpa debounce di blade nanti)
        $hasilPelanggan = collect();
        if (strlen($this->searchPelanggan) > 0) {
            // Pencarian di memory database yang ringan
            $hasilPelanggan = Pelanggan::where('aktif', true)->where('nama', 'like', $this->searchPelanggan . '%')->limit(5)->get();
            if($hasilPelanggan->count() < 5) {
                $hasilPelanggan = $hasilPelanggan->merge(Pelanggan::where('aktif', true)->where('nama', 'like', '%' . $this->searchPelanggan . '%')->limit(5 - $hasilPelanggan->count())->get())->unique('id_pelanggan');
            }
        }

        // 3. Pencarian Cepat Marketing (Hanya mencari yang sudah ada)
        $hasilMarketing = collect();
        if (strlen($this->searchMarketing) > 0) {
            $hasilMarketing = Marketing::where('aktif', true)->where('nama', 'like', $this->searchMarketing . '%')->limit(5)->get();
            if($hasilMarketing->count() < 5) {
                $hasilMarketing = $hasilMarketing->merge(Marketing::where('aktif', true)->where('nama', 'like', '%' . $this->searchMarketing . '%')->limit(5 - $hasilMarketing->count())->get())->unique('id_marketing');
            }
        }

        return view('livewire.transaksi.kasir-pos', [
            'daftarProduk' => $hasilPencarian,
            'hasilPelanggan' => $hasilPelanggan,
            'hasilMarketing' => $hasilMarketing,
        ]);
    }
}