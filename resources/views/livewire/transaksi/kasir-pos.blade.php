@php
    $isOwnerRole = Auth::user()->peran === 'OWNER';
    $posBg = \App\Models\Setting::getValue('pos_background_image');
@endphp

<div class="flex flex-col md:flex-row md:h-[calc(100vh-3.5rem)] md:overflow-hidden fade-in relative"
     x-data="{ 
        showPrices: true,
        jam: '',
        initClock() {
            const update = () => {
                const now = new Date();
                const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][now.getDay()];
                const tgl = String(now.getDate()).padStart(2,'0');
                const bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'][now.getMonth()];
                const jam = String(now.getHours()).padStart(2,'0');
                const mnt = String(now.getMinutes()).padStart(2,'0');
                const dtk = String(now.getSeconds()).padStart(2,'0');
                this.jam = `${hari}, ${tgl} ${bln} ${now.getFullYear()} — ${jam}:${mnt}:${dtk}`;
            };
            update();
            setInterval(() => update(), 1000);
        },
        playSukses() {
            try {
                const audio = new Audio('/audio/transaksi-sukses.mp3');
                audio.volume = 0.7;
                audio.play().catch(() => {});
            } catch(e) {}
        }
     }"
     x-init="initClock()"
     @transaksi-sukses.window="playSukses()">

    {{-- Background Image (custom or default) --}}
    @if($posBg)
        <div class="absolute inset-0 z-0 flex items-center justify-center bg-slate-100/30">
            <img src="{{ $posBg }}" alt="" class="max-w-full max-h-full object-contain opacity-30">
        </div>
    @endif

    {{-- ============================== --}}
    {{-- LEFT: PRODUCT CATALOG (70%)    --}}
    {{-- ============================== --}}
    <section class="w-full md:w-[70%] md:h-full flex flex-col p-4 md:p-6 md:overflow-y-auto relative z-10">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-3">
            <div>
                <h2 class="font-headline text-2xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Point of Sale</h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="material-symbols-outlined text-[14px] {{ $isOwnerRole ? 'text-blue-pro/60' : 'text-sage/60' }}">schedule</span>
                    <p class="text-xs font-semibold {{ $isOwnerRole ? 'text-blue-pro/70' : 'text-sage/80' }} font-mono tracking-wide" x-text="jam"></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <label class="flex items-center cursor-pointer gap-2">
                    <span class="font-label text-[10px] text-slate-400 uppercase tracking-widest font-bold">Harga</span>
                    <div class="relative">
                        <input type="checkbox" class="sr-only" x-model="showPrices">
                        <div class="block w-10 h-6 rounded-full transition-colors"
                             :class="showPrices ? '{{ $isOwnerRole ? 'bg-blue-pro' : 'bg-sage' }}' : 'bg-slate-200'"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform shadow-sm"
                             :class="{'translate-x-4': showPrices}"></div>
                    </div>
                </label>
            </div>
        </div>

        <div class="relative mb-5">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
            <input type="text" wire:model.live.debounce.300ms="keyword"
                   placeholder="Cari SKU, Nama, Merk, Motif..."
                   class="w-full pl-11 pr-4 py-3 rounded-xl font-body text-sm
                          {{ $isOwnerRole ? 'border-0 bg-slate-100 focus:ring-blue-pro/30' : 'border border-slate-200/70 bg-white focus:ring-sage/30 focus:border-sage' }}
                          focus:ring-2 focus:outline-none transition-all shadow-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 auto-rows-max pb-4">
            @foreach($daftarProduk as $produk)
                <div wire:click="tambahKeKeranjang({{ $produk->id_produk }})"
                     class="bg-white rounded-xl p-4 flex flex-col gap-3 cursor-pointer hover:scale-[1.01] transition-all duration-200 group
                            {{ $isOwnerRole ? 'hover:shadow-md border border-slate-100' : 'border border-slate-200/70 hover:border-sage/40 hover:shadow-[0_8px_30px_-12px_rgba(0,0,0,0.12)]' }}">

                    <div class="flex items-center justify-between">
                        <span class="font-label text-[10px] font-bold tracking-widest uppercase {{ $isOwnerRole ? 'text-slate-400' : 'text-sage/70' }}">{{ $produk->kode_barang ?? '—' }}</span>
                        <span class="material-symbols-outlined text-[18px] opacity-0 group-hover:opacity-100 transition-opacity {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }}">add_circle</span>
                    </div>

                    <h3 class="font-headline text-base font-bold leading-tight {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">{{ $produk->nama_produk }}</h3>

                    @if($produk->metadata)
                        <div class="flex flex-wrap gap-1.5">
                            @php
                                $pillColors = ['bg-teal-50 text-teal-700', 'bg-amber-50 text-amber-700', 'bg-violet-50 text-violet-700', 'bg-rose-50 text-rose-700', 'bg-sky-50 text-sky-700', 'bg-emerald-50 text-emerald-700'];
                                $pillIndex = 0;
                            @endphp
                            @foreach($produk->metadata as $key => $val)
                                {{-- Sembunyikan harga_meter dari pill spesifikasi --}}
                                @if($key !== 'harga_meter')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide {{ $pillColors[$pillIndex % count($pillColors)] }}">{{ is_array($val) ? implode(', ', $val) : $val }}</span>
                                    @php $pillIndex++; @endphp
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @php $hasDualPrice = isset($produk->metadata['harga_meter']); @endphp
                    <div class="mt-auto pt-3 flex justify-between items-end border-t {{ $hasDualPrice ? 'border-amber-100' : 'border-slate-50' }}">
                        <div>
                            {{-- Harga Tersembunyi --}}
                            <span x-show="!showPrices" class="font-headline text-lg font-bold text-slate-300">Rp ***</span>

                            {{-- Harga Tampil: Dual-Unit (Meter + KG) --}}
                            @if($hasDualPrice)
                                <div x-show="showPrices" x-transition.opacity class="flex flex-col gap-0.5">
                                    <span class="font-headline text-base font-bold text-amber-600">
                                        Rp {{ number_format($produk->metadata['harga_meter'], 0, ',', '.') }}
                                        <span class="text-[10px] font-semibold text-amber-500">/ Meter</span>
                                    </span>
                                    <span class="font-headline text-xs font-bold {{ $isOwnerRole ? 'text-blue-pro/70' : 'text-sage/70' }}">
                                        Rp {{ number_format($produk->harga_jual_satuan, 0, ',', '.') }}
                                        <span class="text-[10px] font-semibold text-slate-400">/ {{ strtoupper($produk->satuan) }}</span>
                                    </span>
                                </div>
                            @else
                                {{-- Harga Tampil: Single-Unit --}}
                                <span x-show="showPrices" x-transition.opacity class="font-headline text-lg font-bold {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }}">
                                    Rp {{ number_format($produk->harga_jual_satuan, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        @if($produk->lacak_stok)
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $produk->stok_saat_ini > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                                {{ $produk->stok_display }} {{ $produk->satuan }}
                            </span>
                        @else
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full">∞</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================== --}}
    {{-- RIGHT: CART SIDEBAR (30%)      --}}
    {{-- ============================== --}}
    <section class="w-full md:w-[30%] md:h-full flex flex-col z-20 {{ $isOwnerRole ? 'bg-white md:border-l border-slate-200' : 'bg-[#F1F3F2] md:shadow-[-8px_0_30px_-8px_rgba(0,0,0,0.04)]' }}">

        <div class="p-5 flex-1 flex flex-col md:overflow-hidden">

            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-[22px] {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }}">shopping_bag</span>
                <h2 class="font-headline text-xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Keranjang</h2>
            </div>

            @error('checkout') <div class="bg-red-50 text-red-600 p-2.5 rounded-lg mb-3 text-xs font-semibold border border-red-100">{{ $message }}</div> @enderror
            @error('pelanggan_wajib') <div class="bg-red-50 text-red-600 p-2.5 rounded-lg mb-3 text-xs font-semibold border border-red-100">{{ $message }}</div> @enderror
            @error('marketing_wajib') <div class="bg-red-50 text-red-600 p-2.5 rounded-lg mb-3 text-xs font-semibold border border-red-100">{{ $message }}</div> @enderror
            @if(session()->has('error')) <div class="bg-red-50 text-red-600 p-2.5 rounded-lg mb-3 text-xs font-semibold border border-red-100">{{ session('error') }}</div> @endif
            @if(session()->has('sukses')) <div class="bg-emerald-50 text-emerald-600 p-2.5 rounded-lg mb-3 text-xs font-semibold border border-emerald-100">{{ session('sukses') }}</div> @endif

            {{-- SEARCHABLE UI: PELANGGAN & MARKETING --}}
            <div class="space-y-3 mb-5">
                
                {{-- PELANGGAN (Bisa Tambah Baru) --}}
                <div x-data="{ open: false }" class="relative">
                    @if($id_pelanggan || $is_pelanggan_baru)
                        <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm transition-all">
                            <div class="flex justify-between items-center mb-1">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-label font-bold uppercase tracking-wider text-slate-400">Pelanggan <span class="text-red-500">*</span></span>
                                    <span class="text-sm font-headline font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">{{ $pelangganTerpilihNama }}</span>
                                </div>
                                <button wire:click="hapusPelanggan" class="w-7 h-7 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-100 transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                </button>
                            </div>
                            
                            @if($is_pelanggan_baru)
                                <div class="mt-3 space-y-2 border-t border-slate-100 pt-3">
                                    <p class="text-[10px] text-sky-600 font-bold uppercase">Lengkapi Data (Opsional)</p>
                                    <input type="text" wire:model="pelanggan_baru_telepon" placeholder="No. Telepon / WA" class="w-full border-slate-200 rounded-lg px-3 py-2 text-xs focus:ring-sky-500 bg-slate-50">
                                    <input type="text" wire:model="pelanggan_baru_alamat" placeholder="Alamat Lengkap" class="w-full border-slate-200 rounded-lg px-3 py-2 text-xs focus:ring-sky-500 bg-slate-50">
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">person_add</span>
                            <!-- FIX: Hapus debounce.300ms agar instan, ubah menjadi wire:model.live murni -->
                            <input type="text" wire:model.live="searchPelanggan" @focus="open = true" @click.away="open = false" placeholder="Cari / Tambah Pelanggan Baru * ..."
                                   class="w-full pl-10 pr-3 py-2.5 rounded-xl border-0 font-body text-sm bg-white focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/30' : 'focus:ring-sage/30' }} shadow-sm">
                        </div>
                        
                        <div x-show="open && $wire.searchPelanggan.length > 0" style="display: none;" class="absolute z-50 w-full mt-1 bg-white border border-slate-100 shadow-xl rounded-xl overflow-hidden">
                            @forelse($hasilPelanggan as $plg)
                                <div wire:click="pilihPelanggan({{ $plg->id_pelanggan }}, '{{ addslashes($plg->nama) }}')" @click="open = false" class="px-4 py-2.5 hover:bg-slate-50 cursor-pointer text-sm font-semibold text-slate-700 transition-colors border-b border-slate-50">
                                    {{ $plg->nama }}
                                </div>
                            @empty
                                <div class="p-2 border-t border-slate-100">
                                    <button wire:click="setPelangganBaru" @click="open = false" class="w-full flex items-center justify-center gap-2 py-2.5 bg-sky-50 text-sky-600 hover:bg-sky-100 rounded-lg text-sm font-bold transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">person_add</span> Buat Pelanggan Baru "{{ $searchPelanggan }}"
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>

                {{-- MARKETING --}}
                <div x-data="{ openMkt: false }" class="relative">
                    @if($id_marketing)
                        <div class="flex justify-between items-center bg-white border border-slate-200 rounded-xl p-3 shadow-sm">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-label font-bold uppercase tracking-wider text-slate-400">Sales / Marketing <span class="text-red-500">*</span></span>
                                <span class="text-sm font-headline font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">{{ $marketingTerpilihNama }}</span>
                            </div>
                            <button wire:click="hapusMarketing" class="w-7 h-7 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-100 transition-colors">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            </button>
                        </div>
                    @else
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">badge</span>
                            <!-- FIX: Hapus debounce.300ms agar instan, ubah menjadi wire:model.live murni -->
                            <input type="text" wire:model.live="searchMarketing" @focus="openMkt = true" @click.away="openMkt = false" placeholder="Cari Sales (Wajib) * ..."
                                   class="w-full pl-10 pr-3 py-2.5 rounded-xl border-0 font-body text-sm bg-white focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/30' : 'focus:ring-sage/30' }} shadow-sm">
                        </div>
                        
                        <div x-show="openMkt && $wire.searchMarketing.length > 0" style="display: none;" class="absolute z-50 w-full mt-1 bg-white border border-slate-100 shadow-xl rounded-xl overflow-hidden">
                            @forelse($hasilMarketing as $mkt)
                                <div wire:click="pilihMarketing({{ $mkt->id_marketing }}, '{{ addslashes($mkt->nama) }}')" @click="openMkt = false" class="px-4 py-2.5 hover:bg-slate-50 cursor-pointer text-sm font-semibold text-slate-700 transition-colors border-b border-slate-50">
                                    {{ $mkt->nama }}
                                </div>
                            @empty
                                <div class="px-4 py-3 text-center text-xs font-bold text-red-500">Sales tidak ditemukan!</div>
                            @endforelse
                        </div>
                    @endif
                </div>

                {{-- TANGGAL TRANSAKSI (Backdate Support) --}}
                <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm">
                    <label class="block text-[10px] font-label font-bold uppercase tracking-wider text-slate-400 mb-1">Tanggal Transaksi</label>
                    <input wire:model="tanggal_transaksi" type="datetime-local" 
                           class="w-full border-0 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/30' : 'focus:ring-sage/30' }} font-semibold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">
                </div>

            </div>

            {{-- Empty State --}}
            @if(empty($keranjang))
                <div class="md:flex-1 flex flex-col items-center justify-center text-center opacity-50 py-12 md:py-0">
                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-3">receipt_long</span>
                    <p class="text-sm text-slate-400 font-semibold">Keranjang masih kosong</p>
                </div>
            @else
                {{-- Cart Items --}}
                <div class="md:flex-1 md:overflow-y-auto space-y-3 pr-1">
                    @foreach($keranjang as $index => $item)
                        @php
                            $isPcs = in_array($item['satuan_utama'], ['pcs', 'biji', 'unit', 'buah']);
                            $stepValue = $isPcs ? "1" : "0.001";
                        @endphp
                        <div class="p-3 bg-white rounded-lg border {{ $item['has_eceran'] ? 'border-amber-200' : 'border-slate-200' }} hover:shadow-sm transition-all relative">
                            
                            <div class="flex justify-between items-start mb-2">
                                <div class="pr-6">
                                    <h4 class="font-semibold text-sm text-slate-800 leading-tight">{{ $item['nama_produk'] }}</h4>
                                    <p class="text-xs font-bold mt-1 {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }}">Rp {{ number_format($item['harga_terpakai'], 0, ',', '.') }}</p>
                                </div>
                                <button wire:click="hapusItem({{ $index }})" class="absolute top-3 right-3 text-red-400 hover:text-red-600 transition-colors"><span class="material-symbols-outlined text-[18px]">close</span></button>
                            </div>

                            {{-- JIKA BARANG MEMILIKI DUAL-UNIT (Bisa dijual per KG atau per METER) --}}
                            @if($item['has_eceran'])
                                @php $isKabel = $item['is_kabel'] ?? false; @endphp
                                <div class="bg-amber-50 p-2 rounded-lg border border-amber-100 mb-2">
                                    <div class="flex gap-2 mb-2">
                                        <button wire:click="gantiTipeJual({{ $index }}, 'utama')" class="flex-1 text-[10px] font-bold py-1 rounded {{ $item['tipe_jual'] == 'utama' ? 'bg-amber-500 text-white' : 'bg-white text-amber-600 border border-amber-200' }}">JUAL PER {{ strtoupper($item['satuan_utama']) }}</button>
                                        <button wire:click="gantiTipeJual({{ $index }}, 'eceran')" class="flex-1 text-[10px] font-bold py-1 rounded {{ $item['tipe_jual'] == 'eceran' ? 'bg-amber-500 text-white' : 'bg-white text-amber-600 border border-amber-200' }}">JUAL PER METER</button>
                                    </div>
                                    
                                    @if($isKabel)
                                        {{-- KABEL: Hanya 1 input (jumlah jual), potong gudang auto-sync --}}
                                        <div>
                                            <label class="block text-[9px] font-bold text-amber-700 uppercase">Jumlah {{ $item['tipe_jual'] == 'eceran' ? 'Meter' : strtoupper($item['satuan_utama']) }}</label>
                                            <input type="text" inputmode="decimal" 
                                                   wire:model.live.debounce.500ms="keranjang.{{ $index }}.jumlah_jual" 
                                                   placeholder="0"
                                                   class="w-full text-center border-0 rounded text-sm p-2 font-bold bg-white focus:ring-1 focus:ring-amber-500">
                                        </div>
                                    @else
                                        {{-- KERTAS FILM: 2 input (jumlah nota + potong fisik KG) --}}
                                        <div class="flex items-center gap-2">
                                            {{-- Input 1: Panjang Meter / KG (Untuk Nota) --}}
                                            <div class="flex-1">
                                                <label class="block text-[9px] font-bold text-amber-700 uppercase">Jml Nota ({{ $item['tipe_jual'] == 'eceran' ? 'Meter' : strtoupper($item['satuan_utama']) }})</label>
                                                <input type="text" inputmode="decimal" 
                                                       wire:model.live.debounce.500ms="keranjang.{{ $index }}.jumlah_jual" 
                                                       placeholder="0"
                                                       class="w-full text-center border-0 rounded text-sm p-2 font-bold bg-white focus:ring-1 focus:ring-amber-500">
                                            </div>
                                            <span class="text-amber-300 font-bold mt-4">&rarr;</span>
                                            {{-- Input 2: Berat Timbangan (Hanya bisa diubah jika mode Eceran/Meter) --}}
                                            <div class="flex-1">
                                                <label class="block text-[9px] font-bold {{ $item['tipe_jual'] == 'eceran' ? 'text-red-600' : 'text-amber-700' }} uppercase">Potong Fisik ({{ strtoupper($item['satuan_utama']) }})</label>
                                                <input type="text" inputmode="decimal" 
                                                       wire:model.live.debounce.500ms="keranjang.{{ $index }}.jumlah_potong_gudang" 
                                                       placeholder="Ketik berat"
                                                       {{ $item['tipe_jual'] == 'utama' ? 'readonly' : '' }} 
                                                       class="w-full text-center border-0 rounded text-sm p-2 font-bold {{ $item['tipe_jual'] == 'eceran' ? 'bg-red-50 text-red-700 ring-1 ring-red-300' : 'bg-amber-100 text-amber-700 opacity-70' }}">
                                            </div>
                                        </div>

                                        {{-- Warning: Wajib Timbang --}}
                                        @if($item['tipe_jual'] == 'eceran' && $item['jumlah_potong_gudang'] <= 0)
                                            <p class="text-[9px] text-red-500 font-bold mt-1.5 italic text-center">⚖️ Wajib Timbang! Isi fisik yg terpotong dari gudang.</p>
                                        @endif
                                    @endif

                                    {{-- Warning: Stok Limit --}}
                                    @if($item['lacak_stok'] && $item['jumlah_potong_gudang'] > 0)
                                        <div class="mt-1.5 flex items-center justify-between">
                                            <span class="text-[9px] font-bold {{ $item['jumlah_potong_gudang'] > $item['max_stok'] ? 'text-red-600' : 'text-slate-400' }}">
                                                Maks stok: {{ $item['max_stok'] }} {{ strtoupper($item['satuan_utama']) }}
                                            </span>
                                            @if($item['jumlah_potong_gudang'] > $item['max_stok'])
                                                <span class="text-[9px] font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded-full animate-pulse">⚠️ MELEBIHI STOK!</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- INPUT NORMAL UNTUK BARANG BIASA --}}
                                <div class="flex justify-between items-end mt-2 pt-2 border-t border-slate-100">
                                    <div class="flex items-center gap-1.5">
                                        <input type="{{ $isPcs ? 'number' : 'text' }}" 
                                               {{ $isPcs ? 'step=1 min=1' : 'inputmode=decimal' }}
                                               wire:model.live.debounce.500ms="keranjang.{{ $index }}.jumlah_jual" 
                                               placeholder="0"
                                               class="w-20 text-center border border-slate-200 rounded-lg text-sm p-2 font-bold bg-white focus:ring-1 {{ $isOwnerRole ? 'focus:ring-blue-pro/50 focus:border-blue-pro' : 'focus:ring-sage/50 focus:border-sage' }}">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase w-8">{{ $item['satuan_utama'] }}</span>
                                    </div>
                                    {{-- Warning: Stok Limit untuk barang biasa --}}
                                    @if($item['lacak_stok'] && $item['jumlah_jual'] > $item['max_stok'])
                                        <span class="text-[9px] font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded-full animate-pulse">⚠️ Maks: {{ $item['max_stok'] }}</span>
                                    @endif
                                </div>
                            @endif

                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Cart Footer --}}
            <div class="mt-4 pt-4 border-t {{ $isOwnerRole ? 'border-slate-200' : 'border-sage/15' }}">
                <div class="flex justify-between items-center mb-4">
                    <span class="font-label text-sm font-bold uppercase tracking-wider text-slate-400">Total</span>
                    <span class="font-headline text-2xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Rp {{ number_format($total_belanja, 0, ',', '.') }}</span>
                </div>
                
                <button wire:click="konfirmasiPembayaran"
                        {{ empty($keranjang) ? 'disabled' : '' }}
                        class="w-full py-3.5 rounded-xl font-label uppercase tracking-wider text-sm font-bold text-white shadow-sm hover:shadow-md transition-all disabled:opacity-40 disabled:cursor-not-allowed {{ $isOwnerRole ? 'bg-gradient-to-r from-blue-pro to-blue-600' : 'bg-sage-dark hover:bg-sage' }}">
                    Review & Cetak Nota
                </button>
            </div>
        </div>
    </section>

    {{-- ========================================== --}}
    {{-- MODAL RE-CHECK KONFIRMASI PEMBAYARAN FINAL --}}
    {{-- ========================================== --}}
    @if($showConfirmModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh] border-t-4 {{ $isOwnerRole ? 'border-blue-500' : 'border-sage-dark' }}">
                
                <div class="{{ $isOwnerRole ? 'bg-blue-50 text-blue-900 border-blue-100' : 'bg-emerald-50 text-emerald-900 border-emerald-100' }} px-6 py-4 flex justify-between items-center border-b">
                    <h3 class="font-headline text-xl font-black flex items-center gap-2">
                        <span class="material-symbols-outlined">receipt_long</span> Recheck Transaksi Final
                    </h3>
                    <button wire:click="$set('showConfirmModal', false)" class="text-gray-400 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined text-3xl">cancel</span>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto bg-slate-50 flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                            <p class="text-[10px] font-label font-bold text-slate-400 uppercase tracking-widest mb-1">Pelanggan</p>
                            <p class="text-base font-headline font-bold text-charcoal">{{ $pelangganTerpilihNama }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                            <p class="text-[10px] font-label font-bold text-slate-400 uppercase tracking-widest mb-1">Marketing / Sales</p>
                            <p class="text-base font-headline font-bold text-charcoal">{{ $marketingTerpilihNama }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                            <p class="text-[10px] font-label font-bold text-slate-400 uppercase tracking-widest mb-1">Tanggal Transaksi</p>
                            <p class="text-sm font-headline font-bold text-charcoal">
                                {{ \Carbon\Carbon::parse($tanggal_transaksi)->locale('id')->translatedFormat('d M Y - H:i') }}
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <h4 class="font-headline font-bold text-sm text-slate-800 p-4 border-b border-slate-100 bg-slate-50">Daftar Barang</h4>
                        <div class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                            @foreach($keranjang as $item)
                                <div class="p-3 flex justify-between items-center">
                                    <div>
                                        <p class="font-semibold text-sm text-slate-800">{{ $item['nama_produk'] }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $item['jumlah_jual'] }} {{ $item['tipe_jual'] == 'eceran' ? 'Meter' : strtoupper($item['satuan_utama']) }}
                                            x Rp {{ number_format($item['harga_terpakai'], 0, ',', '.') }}
                                        </p>
                                        {{-- Info potong gudang untuk barang dual-unit yang jual meter --}}
                                        @if($item['has_eceran'] && $item['tipe_jual'] == 'eceran' && $item['jumlah_potong_gudang'] > 0)
                                            <span class="inline-flex items-center gap-1 mt-1 text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100">
                                                <span class="material-symbols-outlined text-[12px]">scale</span>
                                                Potong Gudang: {{ $item['jumlah_potong_gudang'] }} {{ strtoupper($item['satuan_utama']) }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="font-bold text-slate-800">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div>
                        <p class="text-xs font-label font-bold text-slate-400 uppercase tracking-widest mb-1">Total Pembayaran</p>
                        <p class="text-3xl font-headline font-black text-charcoal">Rp {{ number_format($total_belanja, 0, ',', '.') }}</p>
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <button wire:click="$set('showConfirmModal', false)" class="flex-1 sm:flex-none px-6 py-3 bg-white border-2 border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-50 transition-colors">Perbaiki</button>
                        
                        <button wire:click="prosesPembayaranFinal" 
                                wire:loading.attr="disabled"
                                wire:target="prosesPembayaranFinal"
                                class="flex-1 sm:flex-none px-8 py-3 {{ $isOwnerRole ? 'bg-blue-600 hover:bg-blue-700' : 'bg-emerald-500 hover:bg-emerald-600' }} text-white rounded-xl font-black shadow-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="prosesPembayaranFinal" class="material-symbols-outlined">check_circle</span>
                            <span wire:loading wire:target="prosesPembayaranFinal" class="material-symbols-outlined animate-spin">refresh</span>
                            <span wire:loading.remove wire:target="prosesPembayaranFinal">SIMPAN NOTA</span>
                            <span wire:loading wire:target="prosesPembayaranFinal">MEMPROSES...</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif

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