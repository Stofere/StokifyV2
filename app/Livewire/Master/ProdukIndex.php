<?php

namespace App\Livewire\Master;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\ProdukGambar;
use App\Models\RiwayatStok;
use App\Models\TransaksiPenjualan;
use App\Models\TransaksiRetur;
use App\Services\ImageService;
use App\Services\StockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProdukIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    // Batas maksimal foto per produk
    const MAKS_FOTO = 3;

    public $keyword = '';

    public $form_open = false;

    public $edit_id = null;

    // Data Master
    public $daftarKategori = [];

    public $atributDinamis = [];

    // Field Form Produk
    public $id_kategori = '';

    public $kode_barang = '';

    public $nama_produk = '';

    public $satuan = 'pcs';

    public $harga_jual_satuan = 0;

    public $lacak_stok = true;

    public $lokasi = '';

    public $metadata_input = [];

    // --- STATE FOTO PRODUK (RAK FOTO DIGITAL) ---
    public $foto_baru = [];          // staging upload (maks 3 total)

    public $daftarFotoExisting = []; // foto tersimpan saat mode edit

    // --- STATE UNTUK MODAL BUKU STOK UTAMA ---
    public $stok_modal_open = false;

    public $produk_stok_aktif = null;

    // Filter Riwayat Stok
    public $riwayat_tgl_mulai;

    public $riwayat_tgl_akhir;

    // --- STATE UNTUK FORM ADJUST STOK & MODAL RECHECK ---
    public $tipe_penyesuaian = 'KOREKSI_PLUS';

    public $jumlah_adjust = 0;

    public $keterangan_adjust = '';

    public $showConfirmModal = false; // State Modal Recheck

    public $password_admin = '';      // Password dipindah ke Modal Recheck

    // --- STATE UNTUK FORM ADJUST ROL & MODAL RECHECK ---
    public $tipe_penyesuaian_rol = 'ROL_MASUK';

    public $jumlah_adjust_rol = 0;

    public $keterangan_adjust_rol = '';

    public $showConfirmModalRol = false;

    public $password_admin_rol = '';

    // --- STATE UNTUK MODAL DETAIL NOTA (KLIK DARI RIWAYAT) ---
    public $modal_detail_nota_open = false;

    public $detail_nota_aktif = null;

    public $tipe_nota_aktif = '';

    public function mount()
    {
        $this->daftarKategori = Kategori::orderBy('nama_kategori')->get();
    }

    public function updatedIdKategori($value)
    {
        $this->metadata_input = [];
        $this->atributDinamis = [];

        if ($value) {
            $kategori = Kategori::with('atribut')->find($value);
            if ($kategori) {
                $this->atributDinamis = $kategori->atribut;
                foreach ($this->atributDinamis as $attr) {
                    if ($attr->nama_atribut === 'Tekstur') {
                        $this->metadata_input[$attr->nama_atribut] = [];
                    } else {
                        $this->metadata_input[$attr->nama_atribut] = '';
                    }
                }
            }
        }
    }

    public function simpan()
    {
        if ($this->kode_barang) {
            $this->kode_barang = strtoupper(trim($this->kode_barang));
        }

        $this->validate([
            'id_kategori' => 'required',
            'kode_barang' => 'nullable|unique:produk,kode_barang,'.$this->edit_id.',id_produk',
            'nama_produk' => 'required|string|max:255|unique:produk,nama_produk,'.$this->edit_id.',id_produk',
            'satuan' => 'required|string',
            'harga_jual_satuan' => 'required|numeric|min:0',
            'lacak_stok' => 'boolean',
            'lokasi' => 'nullable|string|max:255',
            'foto_baru.*' => 'image|mimes:jpeg,jpg,png,webp|max:15360',
        ], [
            'nama_produk.unique' => 'Nama produk ini telah terpakai! Silakan gunakan nama lain.',
            'kode_barang.unique' => 'Kode barang ini telah terpakai!',
            'foto_baru.*.image' => 'File yang diunggah harus berupa gambar.',
            'foto_baru.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto_baru.*.max' => 'Ukuran tiap foto maksimal 15MB.',
        ]);

        $metadataFinal = [];

        // Simpan Atribut Dinamis (Merk, Ring, dll)
        foreach ($this->atributDinamis as $attr) {
            $val = $this->metadata_input[$attr->nama_atribut] ?? null;
            if (! empty($val)) {
                if (is_array($val)) {
                    $metadataFinal[$attr->nama_atribut] = implode(', ', $val);
                } else {
                    $metadataFinal[$attr->nama_atribut] = $val;
                }
            }
        }

        // FIX TAHAP 2: Simpan Harga Meter (Jika satuan KG/ROL dan diisi)
        if (in_array($this->satuan, ['kg', 'rol']) && ! empty($this->metadata_input['harga_meter'])) {
            $metadataFinal['harga_meter'] = $this->metadata_input['harga_meter'];
        }

        $metaString = ! empty($metadataFinal) ? implode(' ', array_values($metadataFinal)) : '';
        $indexPencarian = strtolower(($this->kode_barang ?? '').' '.$this->nama_produk.' '.$metaString);

        if ($this->edit_id) {
            $produk = Produk::find($this->edit_id);
            $produk->update([
                'id_kategori' => $this->id_kategori,
                'kode_barang' => $this->kode_barang ?: null,
                'nama_produk' => $this->nama_produk,
                'satuan' => $this->satuan,
                'harga_jual_satuan' => $this->harga_jual_satuan,
                'lacak_stok' => $this->lacak_stok,
                'lokasi' => $this->lokasi ?? null,
                'metadata' => empty($metadataFinal) ? null : $metadataFinal,
                'index_pencarian' => $indexPencarian,
            ]);
            session()->flash('sukses', 'Data barang berhasil diperbarui.');
        } else {
            $produk = Produk::create([
                'id_kategori' => $this->id_kategori,
                'kode_barang' => $this->kode_barang ?: null,
                'nama_produk' => $this->nama_produk,
                'satuan' => $this->satuan,
                'harga_jual_satuan' => $this->harga_jual_satuan,
                'lacak_stok' => $this->lacak_stok,
                'stok_saat_ini' => 0,
                'lokasi' => $this->lokasi ?? null,
                'metadata' => empty($metadataFinal) ? null : $metadataFinal,
                'index_pencarian' => $indexPencarian,
            ]);
            session()->flash('sukses', 'Barang baru berhasil ditambahkan! Stok awal adalah 0.');
        }

        // Simpan foto produk (jika ada yang di-upload) setelah produk punya ID
        if (! empty($this->foto_baru)) {
            $this->prosesFotoBaru($produk->id_produk);
        }

        $this->resetForm();
    }

    // Proses staging foto -> resize (besar + thumbnail) -> simpan record, enforce maks 3
    private function prosesFotoBaru(int $idProduk): void
    {
        $jumlahExisting = ProdukGambar::where('id_produk', $idProduk)->count();
        $sisaSlot = self::MAKS_FOTO - $jumlahExisting;

        if ($sisaSlot <= 0) {
            return;
        }

        $imageService = app(ImageService::class);
        $urutan = $jumlahExisting;

        foreach (array_slice($this->foto_baru, 0, $sisaSlot) as $file) {
            $hasil = $imageService->simpanGambarProduk($file, $idProduk);
            ProdukGambar::create([
                'id_produk' => $idProduk,
                'path' => $hasil['path'],
                'path_thumbnail' => $hasil['path_thumbnail'],
                'nama_asli' => $hasil['nama_asli'],
                'urutan' => $urutan++,
            ]);
        }
    }

    // Validasi instan saat user memilih file
    public function updatedFotoBaru()
    {
        $this->validate([
            'foto_baru.*' => 'image|mimes:jpeg,jpg,png,webp|max:15360',
        ], [
            'foto_baru.*.image' => 'File yang diunggah harus berupa gambar.',
            'foto_baru.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto_baru.*.max' => 'Ukuran tiap foto maksimal 15MB.',
        ]);
    }

    // Hapus 1 foto existing (saat mode edit) + file fisiknya
    public function hapusFoto($idGambar)
    {
        $gambar = ProdukGambar::where('id_gambar', $idGambar)
            ->where('id_produk', $this->edit_id)
            ->first();

        if (! $gambar) {
            return;
        }

        app(ImageService::class)->hapusFileGambar($gambar);
        $gambar->delete();

        $this->muatFotoExisting();
    }

    // Muat ulang daftar foto tersimpan untuk produk yang sedang diedit
    private function muatFotoExisting(): void
    {
        $this->daftarFotoExisting = $this->edit_id
            ? ProdukGambar::where('id_produk', $this->edit_id)->orderBy('urutan')->get()->toArray()
            : [];
    }

    public function edit($id)
    {
        $produk = Produk::find($id);
        $this->edit_id = $produk->id_produk;
        $this->id_kategori = $produk->id_kategori;
        $this->kode_barang = $produk->kode_barang;
        $this->nama_produk = $produk->nama_produk;
        $this->satuan = $produk->satuan;
        $this->harga_jual_satuan = $produk->harga_jual_satuan;
        $this->lacak_stok = $produk->lacak_stok;
        $this->lokasi = $produk->lokasi;

        $this->updatedIdKategori($this->id_kategori);
        $this->metadata_input = $produk->metadata ?? [];

        // Tarik kembali Harga Meter
        if (isset($produk->metadata['harga_meter'])) {
            $this->metadata_input['harga_meter'] = $produk->metadata['harga_meter'];
        }

        // ==========================================
        // FIX: BUG CHECKBOX MULTI-SELECT LIVEWIRE
        // ==========================================
        // Kita harus memastikan atribut 'Tekstur' selalu berbentuk Array saat form Edit dibuka.
        if (isset($this->metadata_input['Tekstur'])) {
            if (is_string($this->metadata_input['Tekstur'])) {
                // Jika dari database berupa string (misal: "Halus, Kasar"), pecah jadi array
                $this->metadata_input['Tekstur'] = array_map('trim', explode(',', $this->metadata_input['Tekstur']));
            } elseif (is_null($this->metadata_input['Tekstur'])) {
                // Jika dari database null (karena sebelumnya di-uncheck semua), paksakan jadi array kosong
                $this->metadata_input['Tekstur'] = [];
            }
        } else {
            // Jika key 'Tekstur' sama sekali tidak ada di JSON, paksakan buat key baru berbentuk array kosong
            $this->metadata_input['Tekstur'] = [];
        }

        $this->muatFotoExisting();

        $this->form_open = true;
    }

    public function toggleAktif($id)
    {
        $produk = Produk::find($id);
        $produk->update(['status_aktif' => ! $produk->status_aktif]);
    }

    public function resetForm()
    {
        $this->reset(['edit_id', 'id_kategori', 'kode_barang', 'nama_produk', 'satuan', 'harga_jual_satuan', 'lokasi', 'metadata_input', 'atributDinamis', 'foto_baru', 'daftarFotoExisting']);
        $this->lacak_stok = true;
        $this->form_open = false;
        $this->resetValidation();
    }

    public function updatingKeyword()
    {
        $this->resetPage();
    }

    // =========================================================================
    // FITUR BUKU STOK (ADJUST STOK)
    // =========================================================================

    public function bukaModalStok($id_produk)
    {
        $this->produk_stok_aktif = Produk::find($id_produk);
        $this->riwayat_tgl_mulai = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->riwayat_tgl_akhir = Carbon::now()->format('Y-m-d');

        $this->stok_modal_open = true;
        $this->resetFormAdjust();
        $this->resetPage('riwayatPage');
    }

    public function tutupModalStok()
    {
        $this->stok_modal_open = false;
        $this->produk_stok_aktif = null;
        $this->resetFormAdjust();
    }

    public function updatedRiwayatTglMulai()
    {
        $this->resetPage('riwayatPage');
    }

    public function updatedRiwayatTglAkhir()
    {
        $this->resetPage('riwayatPage');
    }

    private function resetFormAdjust()
    {
        $this->reset(['tipe_penyesuaian', 'jumlah_adjust', 'keterangan_adjust', 'password_admin', 'showConfirmModal']);
        $this->tipe_penyesuaian = 'KOREKSI_MINUS';
    }

    private function resetFormAdjustRol()
    {
        $this->reset(['tipe_penyesuaian_rol', 'jumlah_adjust_rol', 'keterangan_adjust_rol', 'password_admin_rol', 'showConfirmModalRol']);
        $this->tipe_penyesuaian_rol = 'ROL_MASUK';
    }

    // TAHAP 1: VALIDASI DATA DAN MUNCULKAN MODAL RECHECK
    public function reviewMutasiStok()
    {
        $this->validate([
            'tipe_penyesuaian' => 'required|in:KOREKSI_PLUS,KOREKSI_MINUS',
            'jumlah_adjust' => 'required|numeric|min:0.01',
            'keterangan_adjust' => 'required|string|min:5',
        ]);

        // BACKEND DOUBLE CHECK: Cegah mutasi desimal di satuan PCS
        $satuan = strtolower($this->produk_stok_aktif->satuan);
        if (in_array($satuan, ['pcs', 'biji', 'unit', 'buah']) && fmod($this->jumlah_adjust, 1) !== 0.0) {
            $this->addError('jumlah_adjust', "Barang dengan satuan {$satuan} tidak boleh memiliki nilai koma (desimal)!");

            return;
        }

        if ($this->tipe_penyesuaian === 'KOREKSI_MINUS' && $this->jumlah_adjust > $this->produk_stok_aktif->stok_saat_ini) {
            $this->addError('jumlah_adjust', 'Jumlah keluar melebihi batas stok! Maksimal: '.$this->produk_stok_aktif->stok_saat_ini);

            return;
        }

        $this->showConfirmModal = true;
    }

    // TAHAP 2: OTORISASI PASSWORD DAN SIMPAN KE DATABASE
    public function prosesAdjustStok(StockService $stockService)
    {
        $this->validate([
            'password_admin' => 'required',
        ]);

        if (! Hash::check($this->password_admin, Auth::user()->password)) {
            $this->addError('password_admin', 'Password otorisasi salah!');

            return;
        }

        try {
            $stockService->adjustStokManual(
                $this->produk_stok_aktif->id_produk,
                Auth::id(),
                $this->tipe_penyesuaian,
                $this->jumlah_adjust,
                $this->keterangan_adjust
            );

            session()->flash('sukses_stok', 'Stok fisik barang berhasil diperbarui.');

            // Refresh data setelah berhasil
            $this->produk_stok_aktif->refresh();
            $this->resetFormAdjust();
            $this->resetPage('riwayatPage');

        } catch (Exception $e) {
            $this->showConfirmModal = false;
            $this->addError('sistem_stok', $e->getMessage());
        }
    }

    // =========================================================================
    // FITUR BUKU STOK ROL (Kabel / Lacak Rol)
    // =========================================================================

    public function reviewMutasiRol()
    {
        $this->validate([
            'tipe_penyesuaian_rol' => 'required|in:ROL_MASUK,ROL_KELUAR',
            'jumlah_adjust_rol' => 'required|numeric|min:1',
            'keterangan_adjust_rol' => 'required|string|min:5',
        ]);

        if (fmod($this->jumlah_adjust_rol, 1) !== 0.0) {
            $this->addError('jumlah_adjust_rol', 'Rol harus berupa angka bulat (tidak boleh desimal).');

            return;
        }

        if ($this->tipe_penyesuaian_rol === 'ROL_KELUAR' && $this->jumlah_adjust_rol > $this->produk_stok_aktif->stok_rol) {
            $this->addError('jumlah_adjust_rol', 'Jumlah keluar melebihi batas rol! Maksimal: '.$this->produk_stok_aktif->stok_rol);

            return;
        }

        $this->showConfirmModalRol = true;
    }

    public function prosesAdjustRol(StockService $stockService)
    {
        $this->validate([
            'password_admin_rol' => 'required',
        ]);

        if (! Hash::check($this->password_admin_rol, Auth::user()->password)) {
            $this->addError('password_admin_rol', 'Password otorisasi salah!');

            return;
        }

        try {
            $stockService->adjustRolManual(
                $this->produk_stok_aktif->id_produk,
                Auth::id(),
                $this->tipe_penyesuaian_rol,
                (int) $this->jumlah_adjust_rol,
                $this->keterangan_adjust_rol
            );

            session()->flash('sukses_rol', 'Stok rol fisik barang berhasil diperbarui.');

            $this->produk_stok_aktif->refresh();
            $this->resetFormAdjustRol();
            $this->resetPage('riwayatPage');

        } catch (Exception $e) {
            $this->showConfirmModalRol = false;
            $this->addError('sistem_rol', $e->getMessage());
        }
    }

    // =========================================================================
    // FITUR LIHAT DETAIL NOTA (DARI DALAM BUKU STOK)
    // =========================================================================
    public function lihatDetailNota($id_transaksi, $tipe)
    {
        $this->tipe_nota_aktif = $tipe;

        if ($tipe === 'POS') {
            $this->detail_nota_aktif = TransaksiPenjualan::with([
                'detailPenjualan.produk',
                'user',
                'pelanggan',
                'marketing',
                'transaksiRetur.detailRetur.produkPengganti',
                'riwayatKoreksi.produk',
                'riwayatKoreksi.user',
            ])->find($id_transaksi);
        } elseif ($tipe === 'RETUR') {
            $this->detail_nota_aktif = TransaksiRetur::with([
                'detailRetur.produkDikembalikan',
                'detailRetur.produkPengganti',
                'user',
                'transaksiPenjualan',
            ])->find($id_transaksi);
        }

        $this->modal_detail_nota_open = true;
    }

    public function tutupDetailNota()
    {
        $this->modal_detail_nota_open = false;
        $this->detail_nota_aktif = null;
    }

    // =========================================================================
    // RENDER METODE
    // =========================================================================
    public function render()
    {
        $query = Produk::with(['kategori', 'gambar'])->orderBy('status_aktif', 'desc')->latest();
        if (! empty(trim($this->keyword))) {
            $terms = explode(' ', trim(strtolower($this->keyword)));
            foreach ($terms as $term) {
                $query->where('index_pencarian', 'LIKE', '%'.$term.'%');
            }
        }

        $riwayat_stok_paginated = null;
        if ($this->stok_modal_open && $this->produk_stok_aktif) {
            $queryRiwayat = RiwayatStok::with(['user', 'transaksiPenjualan.pelanggan', 'transaksiPenjualan.marketing', 'transaksiRetur'])
                ->where('id_produk', $this->produk_stok_aktif->id_produk);

            if ($this->riwayat_tgl_mulai && $this->riwayat_tgl_akhir) {
                $queryRiwayat->whereBetween('created_at', [
                    $this->riwayat_tgl_mulai.' 00:00:00',
                    $this->riwayat_tgl_akhir.' 23:59:59',
                ]);
            }

            $riwayat_stok_paginated = $queryRiwayat->orderBy('id_riwayat', 'desc')->paginate(15, ['*'], 'riwayatPage');
        }

        return view('livewire.master.produk-index', [
            'daftarProduk' => $query->paginate(15),
            'riwayatStok' => $riwayat_stok_paginated,
        ]);
    }
}
