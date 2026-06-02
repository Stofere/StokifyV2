@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp

<div class="p-4 md:p-8 max-w-7xl mx-auto fade-in">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Katalog Barang & Stok</h2>
            <p class="text-slate-400 text-sm mt-1">Kelola spesifikasi barang dan pantau riwayat mutasi gudang.</p>
        </div>
        @if(!$form_open && !$stok_modal_open && !$modal_detail_nota_open)
            <button wire:click="$set('form_open', true)"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white shadow-md hover:shadow-lg transition-all
                           {{ $isOwnerRole ? 'bg-gradient-to-r from-blue-pro to-blue-600' : 'bg-gradient-to-r from-sage-dark to-sage' }}">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Barang Baru
            </button>
        @endif
    </div>

    {{-- Alert --}}
    @if(session()->has('sukses'))
        <div class="bg-emerald-50 text-emerald-700 p-3.5 mb-5 rounded-xl text-sm font-semibold flex items-center gap-2 border border-emerald-100">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            {{ session('sukses') }}
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- MODAL FORMULIR TAMBAH / EDIT BARANG                              --}}
    {{-- ================================================================ --}}
    @if($form_open)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 md:p-8"
             x-data x-on:keydown.escape.window="$wire.resetForm()">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="resetForm"></div>

            {{-- Modal Panel --}}
            <div class="relative w-full max-w-4xl flex flex-col bg-white rounded-2xl shadow-2xl z-10" style="max-height: 90vh">

                {{-- Header (Sticky) --}}
                <div class="flex-shrink-0 px-6 py-4 border-b flex justify-between items-center rounded-t-2xl {{ $isOwnerRole ? 'bg-slate-50 border-slate-200' : 'bg-sage-light/50 border-sage/10' }}">
                    <div>
                        <h3 class="font-headline text-lg font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">
                            {{ $edit_id ? 'Perbarui Data Barang' : 'Form Barang Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $edit_id ? 'Ubah detail dan spesifikasi barang.' : 'Isi informasi barang yang ingin didaftarkan.' }}
                        </p>
                    </div>
                    <button wire:click="resetForm" class="text-slate-400 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                {{-- Body (Scrollable) --}}
                <div class="flex-1 overflow-y-auto p-6">
                    {{-- Step 1: Kategori --}}
                <div class="mb-6 p-4 rounded-xl relative {{ $isOwnerRole ? 'bg-slate-50' : 'bg-sage-light/30' }}">
                    <span class="absolute -top-2.5 -left-2.5 w-7 h-7 flex items-center justify-center rounded-full text-xs font-bold text-white {{ $isOwnerRole ? 'bg-blue-pro' : 'bg-sage' }}">1</span>
                    <label class="block text-sm font-bold mb-2 {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Kategori barang</label>
                    <select wire:model.live="id_kategori" class="w-full md:w-1/2 border-0 rounded-lg p-3 bg-white focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }} shadow-sm text-sm">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($daftarKategori as $kat)
                            <option value="{{ $kat->id_kategori }}">{{ $kat->nama_kategori }}</option>
                        @endforeach
                    </select>
                </div>

                @if($id_kategori)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        {{-- Step 2: Info Dasar --}}
                        <div class="space-y-4 relative">
                            <span class="absolute -top-2.5 -left-4 w-7 h-7 flex items-center justify-center rounded-full text-xs font-bold text-white {{ $isOwnerRole ? 'bg-blue-pro' : 'bg-sage' }} hidden md:flex">2</span>
                            <h4 class="font-semibold text-sm {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }} border-b pb-2 {{ $isOwnerRole ? 'border-slate-200' : 'border-sage/15' }}">Info Dasar Barang</h4>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Kode Barang (SKU) <span class="text-slate-400 normal-case">(Opsional)</span></label>
                                <input type="text" wire:model="kode_barang" placeholder="Contoh: FR-12-CN-GRS" class="w-full border-0 rounded-lg p-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }} uppercase font-semibold">
                                @error('kode_barang') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Nama Barang</label>
                                <input type="text" wire:model="nama_produk" placeholder="Contoh: Fr 12 in CN Grs (3)" class="w-full border-0 rounded-lg p-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                                @error('nama_produk') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Satuan</label>
                                    <select wire:model.live="satuan" class="w-full border-0 rounded-lg p-2.5 text-sm bg-slate-50">
                                        <option value="pcs">Pcs / Biji</option>
                                        <option value="meter">Meter</option>
                                        <option value="kg">Kilogram</option>
                                        <option value="rol">Rol</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Harga Jual (Rp)</label>
                                    <div x-data="{
                                        display: '',
                                        init() {
                                            // Math.round(parseFloat()) agar '45000.00' tidak jadi '4500000'
                                            const angka = Math.round(parseFloat($wire.harga_jual_satuan) || 0);
                                            this.display = angka > 0 ? this.formatNumber(String(angka)) : '';
                                        },
                                        formatNumber(val) {
                                            val = String(val).replace(/[^\d]/g, '');
                                            return val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                        },
                                        onInput() {
                                            let raw = this.display.replace(/[^\d]/g, '');
                                            this.display = this.formatNumber(raw);
                                            $wire.set('harga_jual_satuan', parseInt(raw) || 0);
                                        }
                                    }">
                                        <input type="text" inputmode="numeric" x-model="display" @input="onInput()" placeholder="150.000" class="w-full border-0 rounded-lg p-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }} font-semibold">
                                    </div>
                                    @error('harga_jual_satuan') <span class="text-red-500 text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                                </div>

                                @if(in_array($satuan, ['kg', 'rol']))
                                    <div class="col-span-2 mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                        <label class="block text-sm font-bold text-amber-900 mb-1">Harga Eceran per Meter (Opsional)</label>
                                        <p class="text-[10px] text-amber-700 mb-2">Isi jika barang ini (Kertas Film/Kabel) bisa diecer per meter di kasir.</p>
                                        <div x-data="{
                                            display: '',
                                            init() {
                                                const angka = Math.round(parseFloat($wire.metadata_input?.harga_meter) || 0);
                                                this.display = angka > 0 ? this.formatNumber(String(angka)) : '';
                                            },
                                            formatNumber(val) {
                                                val = String(val).replace(/[^\d]/g, '');
                                                return val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                            },
                                            onInput() {
                                                let raw = this.display.replace(/[^\d]/g, '');
                                                this.display = this.formatNumber(raw);
                                                $wire.set('metadata_input.harga_meter', parseInt(raw) || 0);
                                            }
                                        }">
                                            <input type="text" inputmode="numeric" x-model="display" @input="onInput()" placeholder="Contoh: 15.000" class="w-full border-amber-300 rounded-lg p-2.5 border focus:ring-amber-500 bg-white font-semibold">
                                        </div>
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Lokasi Rak</label>
                                    <input type="text" wire:model="lokasi" placeholder="Rak A1" class="w-full border-0 rounded-lg p-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                                </div>
                            </div>
                        </div>

                        {{-- Step 3: Spesifikasi Dinamis --}}
                        <div class="space-y-5 relative bg-blue-50 p-5 rounded-xl border border-blue-100">
                            <span class="absolute -top-3 -left-3 bg-blue-600 text-white w-8 h-8 flex items-center justify-center rounded-full font-bold shadow md:hidden">3</span>
                            <h4 class="font-bold text-blue-800 border-b border-blue-200 pb-2">Pilih Spesifikasi Khusus</h4>
                            
                            @if(count($atributDinamis) == 0)
                                <div class="text-gray-500 text-sm py-4 text-center bg-white rounded-lg border border-dashed">
                                    Kategori ini tidak punya spesifikasi khusus.
                                </div>
                            @else
                                <div class="grid grid-cols-1 gap-5">
                                    @foreach($atributDinamis as $attr)
                                        
                                        {{-- JIKA ATRIBUT ADALAH TEKSTUR, RENDER SEBAGAI CHECKBOX MULTI-SELECT --}}
                                        @if($attr->nama_atribut === 'Tekstur')
                                            <div>
                                                <label class="block text-sm font-bold text-blue-900 mb-2">{{ $attr->nama_atribut }} <span class="text-xs text-blue-600 font-normal">(Bisa pilih lebih dari 1)</span></label>
                                                <div class="flex flex-wrap gap-2 bg-white p-3 rounded-lg border border-blue-200 shadow-inner">
                                                    @foreach($attr->pilihan_opsi as $opsi)
                                                        <label class="flex items-center gap-2 cursor-pointer hover:bg-blue-50 px-2 py-1 rounded transition-colors">
                                                            <!-- binding wire:model ke Array -->
                                                            <input type="checkbox" wire:model="metadata_input.{{ $attr->nama_atribut }}" value="{{ $opsi }}" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                                            <span class="text-sm font-semibold text-gray-700">{{ $opsi }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        
                                        {{-- SELAIN TEKSTUR, TETAP DROPDOWN SINGLE SELECT --}}
                                        @else
                                            <div class="md:w-1/2">
                                                <label class="block text-sm font-bold text-blue-900 mb-1">{{ $attr->nama_atribut }}</label>
                                                <select wire:model="metadata_input.{{ $attr->nama_atribut }}" class="w-full border-blue-200 rounded-lg p-2.5 border bg-white focus:ring-blue-500 text-sm">
                                                    <option value="">-- Bebas / Tanpa {{ $attr->nama_atribut }} --</option>
                                                    @foreach($attr->pilihan_opsi as $opsi)
                                                        <option value="{{ $opsi }}">{{ $opsi }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif

                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                @endif
                </div>{{-- /Body --}}

                {{-- Footer (Sticky) — Lacak Stok + Aksi --}}
                @if($id_kategori)
                    <div class="flex-shrink-0 flex items-center justify-between border-t px-6 py-4 rounded-b-2xl {{ $isOwnerRole ? 'border-slate-200 bg-slate-50' : 'border-sage/10 bg-white' }}">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="lacak_stok" class="w-5 h-5 rounded {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }}">
                            <span class="font-semibold text-sm text-slate-700">Lacak Stok Barang Ini</span>
                        </label>
                        <div class="flex gap-2">
                            <button wire:click="resetForm" class="px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                            <button wire:click="simpan" class="px-6 py-2.5 rounded-xl font-bold text-sm text-white shadow-md {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }} transition-colors">Simpan</button>
                        </div>
                    </div>
                @endif

            </div>{{-- /Modal Panel --}}
        </div>{{-- /Modal Overlay --}}
    @endif

    {{-- ================================================================ --}}
    {{-- TABEL DATA BARANG                                                --}}
    {{-- ================================================================ --}}
    <div x-data="{ showHargaGlobal: false }" class="bg-white rounded-2xl overflow-hidden {{ ($stok_modal_open || $modal_detail_nota_open) ? 'hidden' : '' }} {{ $isOwnerRole ? 'border border-slate-200' : '' }}">
        {{-- Toolbar --}}
        <div class="p-4 flex flex-col sm:flex-row justify-between items-center gap-3 {{ $isOwnerRole ? 'bg-slate-50 border-b border-slate-200' : 'bg-[#F8F9FA] border-b border-sage/10' }}">
            <div class="relative w-full sm:w-1/2">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input type="text" wire:model.live.debounce.500ms="keyword" placeholder="Cari Kode, Nama, Merk..."
                       class="w-full pl-10 pr-4 py-2.5 border-0 rounded-xl text-sm bg-white focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }} shadow-sm">
            </div>
            <button @click="showHargaGlobal = !showHargaGlobal"
                    class="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg transition-colors shrink-0
                           {{ $isOwnerRole ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' : 'bg-white border border-sage/15 text-sage-dark hover:bg-sage-light/30' }}">
                <span class="material-symbols-outlined text-[18px]" x-text="showHargaGlobal ? 'visibility_off' : 'visibility'"></span>
                <span x-text="showHargaGlobal ? 'Sembunyikan Harga' : 'Tampilkan Harga'"></span>
            </button>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 {{ $isOwnerRole ? 'border-b border-slate-100' : 'border-b border-sage/10' }}">
                        <th class="p-4">Detail Barang</th>
                        <th class="p-4 text-right">Harga</th>
                        <th class="p-4 text-center">Stok</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-center w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($daftarProduk as $prod)
                    <tr class="transition-colors {{ !$prod->status_aktif ? 'opacity-50' : '' }} {{ $isOwnerRole ? 'hover:bg-slate-50 border-b border-slate-50' : 'hover:bg-sage-light/20 border-b border-sage/5' }}">
                        <td class="p-4">
                            <p class="font-bold text-base leading-tight {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">{{ $prod->nama_produk }}</p>
                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                <span class="text-[10px] font-mono bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold">{{ $prod->kode_barang ?? '—' }}</span>
                                <span class="text-[10px] {{ $isOwnerRole ? 'bg-blue-pro text-white' : 'bg-sage-dark text-white' }} px-2 py-0.5 rounded uppercase font-bold">{{ $prod->kategori->nama_kategori }}</span>
                                @if($prod->lokasi)
                                    <span class="text-[10px] bg-amber-50 text-amber-700 px-2 py-0.5 rounded font-bold">📍 {{ $prod->lokasi }}</span>
                                @endif
                            </div>
                            @if($prod->metadata)
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($prod->metadata as $key => $val)
                                        {{-- Sembunyikan harga_meter dari tumpukan pill spesifikasi --}}
                                        @if($key !== 'harga_meter')
                                            <span class="bg-blue-100 text-blue-700 text-[11px] px-2 py-0.5 rounded-full font-semibold">{{ $val }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex flex-col items-end justify-center h-full">
                                <span x-show="!showHargaGlobal" class="font-black text-gray-400 text-lg select-none">Rp ***.***</span>
                                
                                <div x-show="showHargaGlobal" style="display: none;" class="text-right">
                                    <span class="font-black text-green-700 text-lg block">Rp {{ number_format($prod->harga_jual_satuan, 0, ',', '.') }} <span class="text-[10px] text-gray-500 uppercase">/{{ $prod->satuan }}</span></span>
                                    
                                    {{-- Jika ada harga ecer meteran --}}
                                    @if(isset($prod->metadata['harga_meter']))
                                        <span class="font-bold text-amber-600 text-xs block mt-1">Rp {{ number_format($prod->metadata['harga_meter'], 0, ',', '.') }} <span class="text-[9px] text-gray-500 uppercase">/Mtr</span></span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-center">
                            @if($prod->lacak_stok)
                                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg {{ $prod->stok_saat_ini <= 0 ? 'bg-red-50 text-red-600' : 'bg-slate-50 text-slate-700' }}">
                                    <span class="font-bold text-base">{{ $prod->stok_display }}</span>
                                    <span class="text-[10px] uppercase font-bold">{{ $prod->satuan }}</span>
                                </div>
                                @if($prod->kategori->lacak_rol ?? false)
                                    <div class="inline-flex items-center gap-1 mt-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 w-max mx-auto border border-indigo-100">
                                        <span class="text-[10px] uppercase font-bold">🔵</span>
                                        <span class="font-bold text-sm">{{ $prod->stok_rol }} Rol</span>
                                    </div>
                                @endif
                            @else
                                <span class="text-[10px] bg-slate-100 text-slate-400 px-2 py-1 rounded font-bold uppercase">Tanpa Stok</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            @if($prod->status_aktif)
                                <button wire:click="toggleAktif({{ $prod->id_produk }})" class="bg-emerald-50 text-emerald-600 px-3 py-1 rounded-full text-[10px] font-bold hover:bg-red-50 hover:text-red-600 transition-colors">Aktif</button>
                            @else
                                <button wire:click="toggleAktif({{ $prod->id_produk }})" class="bg-red-50 text-red-500 px-3 py-1 rounded-full text-[10px] font-bold hover:bg-emerald-50 hover:text-emerald-600 transition-colors">Nonaktif</button>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex flex-col gap-1.5">
                                @if($prod->lacak_stok)
                                    <button wire:click="bukaModalStok({{ $prod->id_produk }})" class="text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors {{ $isOwnerRole ? 'bg-blue-50 text-blue-pro hover:bg-blue-pro hover:text-white' : 'bg-sage-light text-sage-dark hover:bg-sage hover:text-white' }}">
                                        Buku Stok
                                    </button>
                                @endif
                                <button @click="$dispatch('confirm-edit', { id: {{ $prod->id_produk }}, nama: '{{ addslashes($prod->nama_produk) }}' })" class="text-[11px] font-bold px-3 py-1.5 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-200 transition-colors">Edit</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="p-10 text-center text-slate-400 font-semibold">Tidak ada barang ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 {{ $isOwnerRole ? 'bg-slate-50 border-t border-slate-100' : 'bg-[#F8F9FA] border-t border-sage/10' }}">{{ $daftarProduk->links() }}</div>
    </div>

    {{-- ================================================================ --}}
    {{-- MODAL RIWAYAT & ADJUST STOK                                      --}}
    {{-- ================================================================ --}}
    @if($stok_modal_open && $produk_stok_aktif)
        <!-- PERUBAHAN: class z-40 agar berada di bawah modal recheck z-50 -->
        <div class="bg-white rounded-2xl overflow-hidden mb-6 {{ $modal_detail_nota_open ? 'hidden' : '' }} {{ $isOwnerRole ? 'border-2 border-blue-pro' : 'border-2 border-sage' }} relative z-40">
            <div class="px-6 py-4 flex justify-between items-center {{ $isOwnerRole ? 'bg-charcoal text-white' : 'bg-sage-dark text-white' }}">
                <div>
                    <h3 class="font-headline text-xl font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px]">inventory_2</span>
                        {{ $produk_stok_aktif->nama_produk }} {{ $produk_stok_aktif->kode_barang ? '('.$produk_stok_aktif->kode_barang.')' : '' }}
                    </h3>
                    <p class="text-sm mt-0.5 opacity-80">Buku Mutasi & Koreksi Stok</p>
                </div>
                <button wire:click="tutupModalStok" class="px-4 py-2 rounded-lg font-bold text-sm bg-white/10 hover:bg-red-500 transition-colors">Tutup</button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3">
                {{-- Left: Adjust Form --}}
                <div class="p-5 {{ $isOwnerRole ? 'bg-slate-50 border-r border-slate-200' : 'bg-sage-light/30 border-r border-sage/10' }}">
                    <div class="bg-white p-4 rounded-xl text-center mb-5 shadow-sm">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sisa Fisik Sistem</p>
                        <p class="text-4xl font-headline font-bold mt-1 {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }}">{{ $produk_stok_aktif->stok_display }} <span class="text-base text-slate-400">{{ $produk_stok_aktif->satuan }}</span></p>
                    </div>

                    <h4 class="font-semibold text-sm {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }} mb-3 border-b pb-2 {{ $isOwnerRole ? 'border-slate-200' : 'border-sage/15' }}">Form Koreksi Stok</h4>

                    @if(session()->has('sukses_stok'))
                        <div class="bg-emerald-50 text-emerald-600 p-2.5 mb-3 rounded-lg text-xs font-semibold border border-emerald-100">{{ session('sukses_stok') }}</div>
                    @endif
                    @error('sistem_stok')
                        <div class="bg-red-50 text-red-600 p-2.5 mb-3 rounded-lg text-xs font-semibold border border-red-100">{{ $message }}</div>
                    @enderror

                    <!-- PERUBAHAN: Form ini hanya submit ke fungsi Review Mutasi -->
                    <form wire:submit.prevent="reviewMutasiStok" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Tipe Mutasi</label>
                            <select wire:model="tipe_penyesuaian" class="w-full border-0 rounded-lg p-2.5 text-sm bg-white shadow-sm">
                                <option value="KOREKSI_MINUS">Barang KELUAR</option>
                                <option value="KOREKSI_PLUS">Barang MASUK</option>
                            </select>
                            @error('tipe_penyesuaian') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Jumlah Fisik Mutasi ({{ $produk_stok_aktif->satuan }})</label>
                            @php
                                $isPcsMutasi = in_array(strtolower($produk_stok_aktif->satuan), ['pcs', 'biji', 'unit', 'buah']);
                                $stepMutasi = $isPcsMutasi ? "1" : "0.001"; 
                            @endphp
                            <input type="number" 
                                   step="{{ $stepMutasi }}" 
                                   min="{{ $stepMutasi }}"
                                   wire:model="jumlah_adjust" 
                                   class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-indigo-500 text-lg font-bold text-center">
                            @error('jumlah_adjust') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Keterangan Wajib</label>
                            <textarea wire:model="keterangan_adjust" rows="2" class="w-full border-0 rounded-lg p-2.5 text-sm bg-white shadow-sm" placeholder="Alasan koreksi..."></textarea>
                            @error('keterangan_adjust') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="w-full py-3 rounded-xl font-bold text-sm text-white shadow-md transition-all active:scale-[0.98] bg-blue-600 hover:bg-blue-700">
                            Review Perubahan Stok
                        </button>
                    </form>

                    {{-- Form Koreksi Rol (Hanya tampil jika kategori melacak rol) --}}
                    @if($produk_stok_aktif->kategori->lacak_rol ?? false)
                        <div class="mt-8 border-t border-dashed {{ $isOwnerRole ? 'border-slate-300' : 'border-sage/30' }} pt-6">
                            <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl text-center mb-5 shadow-sm relative overflow-hidden">
                                <div class="absolute -right-4 -top-4 opacity-10 text-[60px]">🔵</div>
                                <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest relative z-10">Sisa Rol Fisik</p>
                                <p class="text-4xl font-headline font-bold mt-1 text-indigo-700 relative z-10">{{ $produk_stok_aktif->stok_rol }} <span class="text-base text-indigo-400">Rol</span></p>
                            </div>

                            <h4 class="font-semibold text-sm {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }} mb-3 border-b pb-2 {{ $isOwnerRole ? 'border-slate-200' : 'border-sage/15' }}">Form Koreksi Rol</h4>

                            @if(session()->has('sukses_rol'))
                                <div class="bg-emerald-50 text-emerald-600 p-2.5 mb-3 rounded-lg text-xs font-semibold border border-emerald-100">{{ session('sukses_rol') }}</div>
                            @endif
                            @error('sistem_rol')
                                <div class="bg-red-50 text-red-600 p-2.5 mb-3 rounded-lg text-xs font-semibold border border-red-100">{{ $message }}</div>
                            @enderror

                            <form wire:submit.prevent="reviewMutasiRol" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Tipe Mutasi Rol</label>
                                    <select wire:model="tipe_penyesuaian_rol" class="w-full border-0 rounded-lg p-2.5 text-sm bg-white shadow-sm">
                                        <option value="ROL_KELUAR">Rol Dipotong/Keluar (-)</option>
                                        <option value="ROL_MASUK">Rol Baru Masuk (+)</option>
                                    </select>
                                    @error('tipe_penyesuaian_rol') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2">
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Jumlah Rol</label>
                                        <input type="number" step="1" min="1" wire:model="jumlah_adjust_rol" class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-indigo-500 text-lg font-bold text-center">
                                        @error('jumlah_adjust_rol') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Keterangan Wajib</label>
                                    <textarea wire:model="keterangan_adjust_rol" rows="2" class="w-full border-0 rounded-lg p-2.5 text-sm bg-white shadow-sm" placeholder="Alasan..."></textarea>
                                    @error('keterangan_adjust_rol') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" class="w-full py-3 rounded-xl font-bold text-sm text-white shadow-md transition-all active:scale-[0.98] bg-indigo-600 hover:bg-indigo-700">
                                    Review Perubahan Rol
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                {{-- Right: History Table --}}
                <div class="xl:col-span-2 flex flex-col h-full">
                    <div class="p-4 flex flex-wrap gap-3 items-center justify-between {{ $isOwnerRole ? 'bg-slate-50 border-b border-slate-200' : 'bg-[#F8F9FA] border-b border-sage/10' }}">
                        <h4 class="font-semibold text-sm {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Rekam Jejak Stok</h4>
                        <div class="flex items-center gap-2">
                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase">Dari</label>
                                <input wire:model.live="riwayat_tgl_mulai" type="date" class="border-0 rounded-lg px-2 py-1.5 text-xs bg-white shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase">Sampai</label>
                                <input wire:model.live="riwayat_tgl_akhir" type="date" class="border-0 rounded-lg px-2 py-1.5 text-xs bg-white shadow-sm">
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left text-sm min-w-[600px]">
                            <thead>
                                <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 {{ $isOwnerRole ? 'border-b border-slate-100' : 'border-b border-sage/10' }}">
                                    <th class="p-3">Waktu & Pelaku</th>
                                    <th class="p-3">Tipe</th>
                                    <th class="p-3 text-right">Mutasi</th>
                                    <th class="p-3 text-center">Sisa</th>
                                    @if($produk_stok_aktif->kategori->lacak_rol ?? false)
                                        <th class="p-3 text-center">Rol</th>
                                    @endif
                                    <th class="p-3">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($riwayatStok)
                                    @forelse($riwayatStok as $riwayat)
                                        <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                            <td class="p-3">
                                                <p class="font-semibold text-slate-700">{{ $riwayat->created_at->format('d/m/Y H:i') }}</p>
                                                <p class="text-xs {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }} font-semibold">{{ $riwayat->user->name }}</p>
                                            </td>
                                            <td class="p-3">
                                                @php
                                                    $tw = 'bg-slate-50 text-slate-500';
                                                    if(in_array($riwayat->tipe, ['MASUK', 'KOREKSI_PLUS', 'ROL_MASUK'])) $tw = 'bg-emerald-50 text-emerald-600';
                                                    if(in_array($riwayat->tipe, ['KELUAR', 'KOREKSI_MINUS', 'ROL_KELUAR'])) $tw = 'bg-red-50 text-red-500';
                                                @endphp
                                                <span class="{{ $tw }} px-2 py-0.5 rounded text-[10px] font-bold uppercase">{{ str_replace('_', ' ', $riwayat->tipe) }}</span>
                                            </td>
                                            <td class="p-3 text-right font-bold {{ in_array($riwayat->tipe, ['MASUK', 'KOREKSI_PLUS', 'AWAL', 'ROL_MASUK']) ? 'text-emerald-600' : 'text-red-500' }}">
                                                @if(in_array($riwayat->tipe, ['ROL_MASUK', 'ROL_KELUAR']))
                                                    <span class="text-slate-400 font-normal">-</span>
                                                @else
                                                    {{ in_array($riwayat->tipe, ['MASUK', 'KOREKSI_PLUS', 'AWAL']) ? '+' : '-' }}{{ fmod($riwayat->jumlah, 1) == 0 ? (int)$riwayat->jumlah : $riwayat->jumlah }}
                                                    @if(strpos($riwayat->keterangan, '(Terjual') !== false)
                                                        @php
                                                            preg_match('/\(Terjual\s+(.*?)\)/', $riwayat->keterangan, $matches);
                                                            $terjualInfo = $matches[1] ?? '';
                                                            $isMeter = stripos($terjualInfo, 'Meter') !== false;
                                                        @endphp
                                                        @if($terjualInfo)
                                                            <div class="mt-1">
                                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded border
                                                                    {{ $isMeter
                                                                        ? 'text-amber-600 bg-amber-50 border-amber-100'
                                                                        : 'text-blue-600 bg-blue-50 border-blue-100' }}">
                                                                    Terjual {{ $terjualInfo }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="p-3 text-center font-bold text-slate-700 bg-slate-50/50">
                                                {{ fmod($riwayat->stok_sesudah, 1) == 0 ? (int)$riwayat->stok_sesudah : $riwayat->stok_sesudah }}
                                            </td>
                                            @if($produk_stok_aktif->kategori->lacak_rol ?? false)
                                                <td class="p-3 text-center">
                                                    @if($riwayat->rol_mutasi !== null)
                                                        @php $isPlus = in_array($riwayat->tipe, ['ROL_MASUK', 'AWAL', 'MASUK']); @endphp
                                                        <div class="text-[10px] font-bold {{ $isPlus ? 'text-indigo-600' : 'text-rose-500' }}">
                                                            {{ $isPlus ? '+' : '-' }}{{ $riwayat->rol_mutasi }} 🔵
                                                        </div>
                                                        <div class="text-[11px] font-bold text-slate-700">{{ $riwayat->rol_sesudah }}</div>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="p-3 text-xs">
                                                @if($riwayat->id_transaksi_penjualan)
                                                    <button wire:click="lihatDetailNota({{ $riwayat->id_transaksi_penjualan }}, 'POS')" class="w-full text-left p-2 rounded-lg transition-colors {{ $isOwnerRole ? 'bg-blue-50 hover:bg-blue-100 border border-blue-100' : 'bg-sage-light/50 hover:bg-sage-light border border-sage/10' }}">
                                                        <span class="font-bold block {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }}">POS: {{ $riwayat->transaksiPenjualan->kode_nota }}</span>
                                                        <span class="text-slate-500 block mt-0.5">👤 {{ $riwayat->transaksiPenjualan->pelanggan->nama ?? 'Umum' }}</span>
                                                        @if($riwayat->transaksiPenjualan->marketing)
                                                            <span class="text-slate-400 block">👔 {{ $riwayat->transaksiPenjualan->marketing->nama }}</span>
                                                        @endif
                                                    </button>
                                                @elseif($riwayat->id_retur)
                                                    <button wire:click="lihatDetailNota({{ $riwayat->id_retur }}, 'RETUR')" class="w-full text-left bg-violet-50 hover:bg-violet-100 border border-violet-100 p-2 rounded-lg transition-colors">
                                                        <span class="font-bold text-violet-700 block">Retur: {{ $riwayat->transaksiRetur->kode_retur }}</span>
                                                        <span class="text-slate-500 block mt-0.5">Nota: {{ $riwayat->transaksiRetur->transaksiPenjualan->kode_nota ?? '-' }}</span>
                                                    </button>
                                                @else
                                                    <div class="bg-slate-50 p-2 rounded-lg text-slate-600">
                                                        <span class="font-bold block text-slate-700">✍️ Manual</span>
                                                        {{ $riwayat->keterangan }}
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="p-8 text-center text-slate-400 font-semibold">Tidak ada riwayat.</td></tr>
                                    @endforelse
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-t {{ $isOwnerRole ? 'border-slate-100' : 'border-sage/10' }}">
                        @if($riwayatStok) {{ $riwayatStok->links() }} @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================================================================== --}}
        {{-- MODAL RECHECK KONFIRMASI MUTASI STOK FINAL                           --}}
        {{-- ==================================================================== --}}
        @if($showConfirmModal)
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4 fade-in">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden flex flex-col border-t-4 {{ $tipe_penyesuaian === 'KOREKSI_PLUS' ? 'border-emerald-500' : 'border-red-500' }}">
                    
                    <div class="px-6 py-4 flex justify-between items-center shrink-0 {{ $isOwnerRole ? 'bg-charcoal text-white' : 'bg-sage-dark text-white' }}">
                        <h3 class="text-lg font-headline font-bold flex items-center gap-2">⚠️ Konfirmasi Final Mutasi</h3>
                        <button wire:click="$set('showConfirmModal', false)" class="text-white/70 hover:text-red-500 transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="p-6 bg-slate-50 flex-1">
                        <p class="text-center text-slate-600 mb-6 text-sm">Anda akan mengubah fisik stok gudang sistem. Pastikan data di bawah ini sudah sesuai dengan kenyataan fisik.</p>

                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
                            <div class="flex justify-between border-b border-slate-100 pb-3">
                                <span class="text-slate-500 font-bold text-sm">Barang:</span>
                                <span class="font-bold text-slate-800 text-right">{{ $produk_stok_aktif->nama_produk }}</span>
                            </div>
                            
                            <div class="flex justify-between border-b border-slate-100 pb-3">
                                <span class="text-slate-500 font-bold text-sm">Perubahan:</span>
                                @if($tipe_penyesuaian === 'KOREKSI_PLUS')
                                    <span class="font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded text-lg">+ {{ $jumlah_adjust }} {{ $produk_stok_aktif->satuan }} (MASUK)</span>
                                @else
                                    <span class="font-bold text-red-600 bg-red-50 px-3 py-1 rounded text-lg">- {{ $jumlah_adjust }} {{ $produk_stok_aktif->satuan }} (KELUAR)</span>
                                @endif
                            </div>

                            <div class="flex justify-between border-b border-slate-100 pb-3">
                                <span class="text-slate-500 font-bold text-sm">Sisa Stok Nanti:</span>
                                <span class="font-bold {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }} text-xl">
                                    {{ $tipe_penyesuaian === 'KOREKSI_PLUS' ? ($produk_stok_aktif->stok_saat_ini + $jumlah_adjust) : ($produk_stok_aktif->stok_saat_ini - $jumlah_adjust) }} 
                                    <span class="text-sm font-semibold uppercase tracking-wider">{{ $produk_stok_aktif->satuan }}</span>
                                </span>
                            </div>

                            <div>
                                <span class="text-slate-500 font-bold text-sm block mb-1">Alasan Penyesuaian:</span>
                                <p class="text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100 italic text-sm">"{{ $keterangan_adjust }}"</p>
                            </div>
                        </div>

                        {{-- PASSWORD OTORISASI DIPINDAH KE SINI --}}
                        <div class="mt-6 bg-red-50 border border-red-200 rounded-xl p-5 shadow-sm">
                            <label class="block text-[10px] font-bold text-red-700 mb-1.5 uppercase tracking-widest">Otorisasi Keamanan (Wajib)</label>
                            <p class="text-xs text-red-600 mb-3 font-medium">Tindakan ini akan tercatat permanen di SISTEM. Masukkan password.</p>
                            <input type="password" wire:model="password_admin" placeholder="Masukkan Password Akun Anda..." class="w-full border border-red-200 rounded-lg p-3 focus:ring-2 focus:ring-red-200 text-sm font-semibold bg-white shadow-inner">
                            @error('password_admin') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="bg-white px-6 py-4 border-t border-slate-200 flex flex-col-reverse sm:flex-row gap-3 justify-end items-center">
                        <button wire:click="$set('showConfirmModal', false)" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl font-semibold hover:bg-slate-50 transition-colors">Batal</button>
                        
                        <button wire:click="prosesAdjustStok" 
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto px-8 py-2.5 text-white rounded-xl font-bold shadow-md transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }}">
                            <span wire:loading.remove>Eksekusi Stok</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ==================================================================== --}}
        {{-- MODAL RECHECK KONFIRMASI MUTASI ROL FINAL                            --}}
        {{-- ==================================================================== --}}
        @if($showConfirmModalRol)
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4 fade-in">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden flex flex-col border-t-4 {{ $tipe_penyesuaian_rol === 'ROL_MASUK' ? 'border-indigo-500' : 'border-rose-500' }}">
                    
                    <div class="px-6 py-4 flex justify-between items-center shrink-0 border-b border-slate-100">
                        <h3 class="text-lg font-headline font-bold flex items-center gap-2 text-indigo-900">⚠️ Konfirmasi Final Mutasi Rol</h3>
                        <button wire:click="$set('showConfirmModalRol', false)" class="text-slate-400 hover:text-red-500 transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div class="p-6 bg-slate-50 flex-1">
                        <p class="text-center text-slate-600 mb-6 text-sm">Anda akan mengubah fisik stok ROL gudang. Pastikan data sudah sesuai fisik.</p>

                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
                            <div class="flex justify-between border-b border-slate-100 pb-3">
                                <span class="text-slate-500 font-bold text-sm">Barang Kabel:</span>
                                <span class="font-bold text-slate-800 text-right">{{ $produk_stok_aktif->nama_produk }}</span>
                            </div>
                            
                            <div class="flex justify-between border-b border-slate-100 pb-3">
                                <span class="text-slate-500 font-bold text-sm">Perubahan Rol:</span>
                                @if($tipe_penyesuaian_rol === 'ROL_MASUK')
                                    <span class="font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded text-lg">+ {{ $jumlah_adjust_rol }} Rol (MASUK)</span>
                                @else
                                    <span class="font-bold text-rose-600 bg-rose-50 px-3 py-1 rounded text-lg">- {{ $jumlah_adjust_rol }} Rol (KELUAR)</span>
                                @endif
                            </div>

                            <div class="flex justify-between border-b border-slate-100 pb-3">
                                <span class="text-slate-500 font-bold text-sm">Sisa Rol Nanti:</span>
                                <span class="font-bold text-indigo-700 text-xl">
                                    {{ $tipe_penyesuaian_rol === 'ROL_MASUK' ? ($produk_stok_aktif->stok_rol + $jumlah_adjust_rol) : ($produk_stok_aktif->stok_rol - $jumlah_adjust_rol) }} 
                                    <span class="text-sm font-semibold uppercase tracking-wider">Rol 🔵</span>
                                </span>
                            </div>

                            <div>
                                <span class="text-slate-500 font-bold text-sm block mb-1">Alasan Penyesuaian:</span>
                                <p class="text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100 italic text-sm">"{{ $keterangan_adjust_rol }}"</p>
                            </div>
                        </div>

                        <div class="mt-6 bg-red-50 border border-red-200 rounded-xl p-5 shadow-sm">
                            <label class="block text-[10px] font-bold text-red-700 mb-1.5 uppercase tracking-widest">Otorisasi Keamanan (Wajib)</label>
                            <p class="text-xs text-red-600 mb-3 font-medium">Tindakan ini akan tercatat permanen. Masukkan password admin.</p>
                            <input type="password" wire:model="password_admin_rol" placeholder="Masukkan Password Akun Anda..." class="w-full border border-red-200 rounded-lg p-3 focus:ring-2 focus:ring-red-200 text-sm font-semibold bg-white shadow-inner">
                            @error('password_admin_rol') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="bg-white px-6 py-4 border-t border-slate-200 flex flex-col-reverse sm:flex-row gap-3 justify-end items-center">
                        <button wire:click="$set('showConfirmModalRol', false)" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl font-semibold hover:bg-slate-50 transition-colors">Batal</button>
                        
                        <button wire:click="prosesAdjustRol" 
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto px-8 py-2.5 text-white rounded-xl font-bold shadow-md transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700">
                            <span wire:loading.remove>Eksekusi Rol</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ================================================================ --}}
    {{-- MODAL DETAIL NOTA                                                --}}
    {{-- ================================================================ --}}
    @if($modal_detail_nota_open && $detail_nota_aktif)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
            <div class="bg-white rounded-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh] shadow-2xl">
                <div class="px-6 py-4 flex justify-between items-center shrink-0 {{ $isOwnerRole ? 'bg-charcoal text-white' : 'bg-sage-dark text-white' }}">
                    <div>
                        <h3 class="font-headline text-lg font-bold">
                            @if($tipe_nota_aktif == 'POS') Detail Nota Penjualan @else Detail Nota Retur @endif
                        </h3>
                        <p class="text-sm mt-0.5 opacity-80">Kode: {{ $detail_nota_aktif->kode_nota ?? $detail_nota_aktif->kode_retur }}</p>
                    </div>
                    <button wire:click="tutupDetailNota" class="px-4 py-2 rounded-lg font-bold text-sm bg-white/10 hover:bg-red-500 transition-colors">× Tutup</button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    @if($tipe_nota_aktif == 'POS')
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                            <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Waktu</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $detail_nota_aktif->tanggal_transaksi->format('d M Y, H:i') }}</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Kasir</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $detail_nota_aktif->user->name ?? '-' }}</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pelanggan</p><p class="font-semibold text-sm {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }} mt-1">{{ $detail_nota_aktif->pelanggan->nama ?? 'Umum' }}</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Marketing</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $detail_nota_aktif->marketing->nama ?? '-' }}</p></div>
                        </div>
                        <h4 class="font-semibold text-sm text-slate-700 mb-2 border-b pb-2 border-slate-100">Daftar Barang</h4>
                        <table class="w-full text-left text-sm bg-white">
                            <thead><tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 border-b border-slate-100"><th class="p-3">Barang</th><th class="p-3 text-center">Qty</th><th class="p-3 text-right">Harga</th><th class="p-3 text-right">Subtotal</th></tr></thead>
                            <tbody class="divide-y">
                                @foreach($detail_nota_aktif->detailPenjualan as $det)
                                    <tr>
                                        <td class="p-3">
                                            <span class="font-bold text-gray-800 block">{{ $det->produk->nama_produk }}</span>
                                            
                                            {{-- FIX: JEJAK MULTI-RETUR DI DALAM BUKU STOK --}}
                                            @if($det->jumlah_diretur > 0)
                                                @php
                                                    $daftarJejakRetur = [];
                                                    foreach($detail_nota_aktif->transaksiRetur as $retur) {
                                                        foreach($retur->detailRetur as $dRet) {
                                                            if($dRet->id_produk_dikembalikan === $det->id_produk) {
                                                                $daftarJejakRetur[] = ['detail' => $dRet, 'nota_retur' => $retur];
                                                            }
                                                        }
                                                    }
                                                @endphp

                                                @forelse($daftarJejakRetur as $jejak)
                                                    <div class="mt-2 bg-orange-50 border border-orange-200 rounded p-3 text-xs shadow-sm relative">
                                                        <span class="text-orange-700 font-black block mb-1 uppercase tracking-wider text-[10px]">⚠️ Diretur: {{ $jejak['nota_retur']->tanggal_retur->format('d/m/Y H:i') }}</span>
                                                        <span class="text-gray-700 block mb-1">Dikembalikan <strong class="text-red-600">{{ fmod($jejak['detail']->jumlah, 1) == 0 ? (int)$jejak['detail']->jumlah : $jejak['detail']->jumlah }} {{ strtoupper($det->satuan_saat_jual) }}</strong> (Kondisi: {{ $jejak['detail']->kondisi_barang_dikembalikan }})</span>
                                                        <span class="text-gray-700 block mb-1">Diganti dgn: <strong class="text-green-700">{{ $jejak['detail']->produkPengganti->nama_produk }}</strong> ({{ fmod($jejak['detail']->jumlah, 1) == 0 ? (int)$jejak['detail']->jumlah : $jejak['detail']->jumlah }} {{ strtoupper($det->satuan_saat_jual) }})</span>
                                                        
                                                        <span class="block bg-white p-1.5 rounded border border-orange-100 text-gray-600 italic mt-1.5">
                                                            "{{ $jejak['nota_retur']->catatan ?? 'Tanpa catatan' }}"
                                                        </span>
                                                        
                                                        <button wire:click="lihatDetailNota({{ $jejak['nota_retur']->id_retur }}, 'RETUR')" class="mt-2 bg-white border border-orange-300 text-orange-700 hover:bg-orange-100 px-3 py-1 rounded-full font-bold transition-colors w-max text-[10px]">
                                                            Buka Dokumen Retur &rarr;
                                                        </button>
                                                    </div>
                                                @empty
                                                    <span class="block text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold mt-1 w-max border border-red-200">Total Diretur: {{ fmod($det->jumlah_diretur, 1) == 0 ? (int)$det->jumlah_diretur : $det->jumlah_diretur }} qty</span>
                                                @endforelse
                                            @endif
                                        </td>
                                        <td class="p-3 text-center align-top pt-4">
                                            <span class="font-bold text-gray-700">{{ fmod($det->jumlah, 1) == 0 ? (int)$det->jumlah : $det->jumlah }} {{ strtoupper($det->satuan_saat_jual) }}</span>
                                            @if(strtolower($det->satuan_saat_jual) === 'meter' && $det->jumlah_potong_gudang)
                                                <span class="block text-[9px] text-amber-600 font-bold mt-0.5">⚖️ {{ $det->jumlah_potong_gudang }} KG</span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-right text-gray-600 align-top pt-4">Rp {{ number_format($det->harga_satuan, 0, ',', '.') }}<span class="text-[9px] text-slate-400 block">/{{ $det->satuan_saat_jual }}</span></td>
                                        <td class="p-3 text-right font-bold text-green-700 align-top pt-4">Rp {{ number_format($det->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr class="bg-slate-50"><td colspan="3" class="p-3 text-right font-bold text-slate-500 uppercase text-xs">Total:</td><td class="p-3 text-right font-headline font-bold text-lg text-emerald-600">Rp {{ number_format($detail_nota_aktif->total_harga, 0, ',', '.') }}</td></tr></tfoot>
                        </table>
                    @elseif($tipe_nota_aktif == 'RETUR')
                        {{-- ==================== DETAIL NOTA RETUR (REDESIGN LENGKAP) ==================== --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                            <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Waktu Retur</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $detail_nota_aktif->tanggal_retur->format('d M Y, H:i') }}</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Diproses Oleh</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $detail_nota_aktif->user->name ?? '-' }}</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Nota POS Asal</p><p class="font-semibold text-sm {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }} mt-1 cursor-pointer underline" wire:click="lihatDetailNota({{ $detail_nota_aktif->transaksiPenjualan->id_transaksi_penjualan }}, 'POS')">{{ $detail_nota_aktif->transaksiPenjualan->kode_nota ?? '-' }}</p></div>
                            <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pelanggan</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $detail_nota_aktif->transaksiPenjualan->pelanggan->nama ?? 'Umum' }}</p></div>
                        </div>

                        {{-- Catatan Retur --}}
                        <div class="mb-5 bg-amber-50 border border-amber-200 p-4 rounded-xl">
                            <p class="text-[9px] font-bold text-amber-600 uppercase tracking-widest mb-1">📝 Catatan Retur</p>
                            <p class="text-sm text-slate-700 italic font-medium">"{{ $detail_nota_aktif->catatan ?? 'Tidak ada catatan.' }}"</p>
                        </div>

                        <h4 class="font-semibold text-sm text-slate-700 mb-3 border-b pb-2 border-slate-100">Rincian Tukar Barang</h4>
                        <div class="space-y-4">
                            @foreach($detail_nota_aktif->detailRetur as $detRetur)
                                @php
                                    $detailAsli = $detail_nota_aktif->transaksiPenjualan->detailPenjualan->firstWhere('id_produk', $detRetur->id_produk_dikembalikan);
                                    $satuanAsli = $detailAsli ? strtoupper($detailAsli->satuan_saat_jual) : strtoupper($detRetur->produkDikembalikan->satuan);
                                    $hargaAsli = $detailAsli ? $detailAsli->harga_satuan : 0;
                                @endphp
                                <div class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="flex flex-col md:flex-row">
                                        <div class="flex-1 bg-red-50 p-4">
                                            <p class="text-[9px] font-bold text-red-500 uppercase tracking-widest mb-2">❌ Dikembalikan</p>
                                            <p class="font-bold text-red-700 text-sm">{{ $detRetur->produkDikembalikan->nama_produk }}</p>
                                            <div class="mt-2 space-y-1 text-xs text-slate-600">
                                                <p>Jumlah: <strong class="text-red-600">{{ fmod($detRetur->jumlah, 1) == 0 ? (int)$detRetur->jumlah : $detRetur->jumlah }} {{ $satuanAsli }}</strong></p>
                                                <p>Harga Nota: <strong>Rp {{ number_format($hargaAsli, 0, ',', '.') }}</strong> /{{ $satuanAsli }}</p>
                                                <p>Kondisi: <span class="font-bold px-1.5 py-0.5 rounded text-[10px] {{ $detRetur->kondisi_barang_dikembalikan === 'BAGUS' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $detRetur->kondisi_barang_dikembalikan }}</span></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center px-3 bg-slate-100">
                                            <span class="material-symbols-outlined text-slate-400">arrow_forward</span>
                                        </div>
                                        <div class="flex-1 bg-emerald-50 p-4">
                                            <p class="text-[9px] font-bold text-emerald-600 uppercase tracking-widest mb-2">✅ Pengganti</p>
                                            <p class="font-bold text-emerald-700 text-sm">{{ $detRetur->produkPengganti->nama_produk }}</p>
                                            <div class="mt-2 space-y-1 text-xs text-slate-600">
                                                <p>Jumlah: <strong class="text-emerald-600">{{ fmod($detRetur->jumlah, 1) == 0 ? (int)$detRetur->jumlah : $detRetur->jumlah }} {{ $satuanAsli }}</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    @if($detRetur->subtotal_biaya != 0)
                                        <div class="px-4 py-2 {{ $detRetur->subtotal_biaya > 0 ? 'bg-amber-50 border-t border-amber-200' : 'bg-emerald-50 border-t border-emerald-200' }} text-xs font-bold">
                                            @if($detRetur->subtotal_biaya > 0)
                                                <span class="text-amber-700">💰 Pelanggan Nambah: Rp {{ number_format(abs($detRetur->subtotal_biaya), 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-emerald-700">💰 Toko Kembalikan: Rp {{ number_format(abs($detRetur->subtotal_biaya), 0, ',', '.') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-5 p-4 rounded-xl {{ $detail_nota_aktif->total_biaya_retur > 0 ? 'bg-amber-50 border-2 border-amber-200' : ($detail_nota_aktif->total_biaya_retur < 0 ? 'bg-emerald-50 border-2 border-emerald-200' : 'bg-slate-50 border-2 border-slate-200') }}">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Selisih Biaya Retur</p>
                            @if($detail_nota_aktif->total_biaya_retur > 0)
                                <p class="font-headline text-xl font-bold text-amber-600">Pelanggan Nambah: Rp {{ number_format(abs($detail_nota_aktif->total_biaya_retur), 0, ',', '.') }}</p>
                            @elseif($detail_nota_aktif->total_biaya_retur < 0)
                                <p class="font-headline text-xl font-bold text-emerald-600">Toko Kembalikan: Rp {{ number_format(abs($detail_nota_aktif->total_biaya_retur), 0, ',', '.') }}</p>
                            @else
                                <p class="font-headline text-xl font-bold text-slate-600">Tukar Guling (Rp 0)</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- MODAL KONFIRMASI EDIT PRODUK                                     --}}
    {{-- ================================================================ --}}
    <div x-data="{ 
            showEditConfirm: false, 
            editId: null, 
            editNama: '' 
         }"
         x-on:confirm-edit.window="showEditConfirm = true; editId = $event.detail.id; editNama = $event.detail.nama"
         x-on:keydown.escape.window="showEditConfirm = false"
    >
        {{-- Backdrop --}}
        <div x-show="showEditConfirm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[70]"
             @click="showEditConfirm = false"
             style="display: none;"
        ></div>

        {{-- Modal Panel --}}
        <div x-show="showEditConfirm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90"
             class="fixed inset-0 z-[75] flex items-center justify-center p-4"
             style="display: none;"
        >
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border-t-4 {{ $isOwnerRole ? 'border-blue-pro' : 'border-sage' }}"
                 @click.away="showEditConfirm = false">
                
                {{-- Header --}}
                <div class="px-6 py-5 text-center {{ $isOwnerRole ? 'bg-slate-50' : 'bg-sage-light/30' }}">
                    <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-3 {{ $isOwnerRole ? 'bg-blue-100' : 'bg-sage-light' }}">
                        <span class="material-symbols-outlined text-[32px] {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }}">edit_note</span>
                    </div>
                    <h3 class="font-headline text-lg font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Konfirmasi Edit Barang</h3>
                    <p class="text-slate-400 text-sm mt-1">Apakah Anda yakin ingin mengubah data produk ini?</p>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Produk yang akan diedit</p>
                        <p class="font-bold text-base {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}" x-text="editNama"></p>
                    </div>
                    <div class="mt-3 flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-xl p-3">
                        <span class="material-symbols-outlined text-amber-500 text-[18px] mt-0.5 shrink-0">info</span>
                        <p class="text-xs text-amber-700 font-medium">Pastikan Anda benar-benar ingin mengedit barang ini. Perubahan yang disimpan akan mengubah data produk di sistem.</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-slate-100 flex gap-3">
                    <button @click="showEditConfirm = false" 
                            class="flex-1 px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button @click="showEditConfirm = false; $wire.edit(editId)" 
                            class="flex-1 px-5 py-2.5 rounded-xl font-bold text-sm text-white shadow-md transition-all active:scale-[0.98] {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }}">
                        <span class="flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                            Ya, Edit Produk
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prevent scroll from changing number input values
        // when user scrolls the page while input is focused 
        document.addEventListener('wheel', function (e) {
            if (document.activeElement.type === 'number') {
                document.activeElement.blur();
            }
        }, { passive:true });
    </script>

</div>