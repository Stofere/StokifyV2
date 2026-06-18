@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp

<div class="p-4 md:p-8 max-w-7xl mx-auto fade-in">

    <div class="mb-6">
        <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Proses Retur Transaksi</h2>
        <p class="text-slate-400 text-sm mt-1">Cari nota penjualan dan proses pengembalian barang.</p>
    </div>

    @if (session()->has('sukses'))
        <div class="bg-emerald-50 text-emerald-700 p-3.5 mb-5 rounded-xl text-sm font-semibold flex items-center gap-2 border border-emerald-100">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            {{ session('sukses') }}
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- TAMPILAN 1: DAFTAR NOTA PENCARIAN                                --}}
    {{-- ================================================================ --}}
    @if(!$notaTerpilih)
        <div class="bg-white rounded-2xl overflow-hidden mb-6 border {{ $isOwnerRole ? 'border-slate-200' : 'border-slate-200/70' }}">
            {{-- Filters --}}
            <div class="p-4 flex flex-wrap gap-3 items-end {{ $isOwnerRole ? 'bg-slate-50 border-b border-slate-200' : 'bg-[#F8F9FA] border-b border-sage/10' }}">
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Mulai Tgl</label>
                    <input wire:model.live="filter_tanggal_mulai" type="date" class="border-0 rounded-lg px-3 py-2 text-sm bg-white shadow-sm focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Sampai Tgl</label>
                    <input wire:model.live="filter_tanggal_akhir" type="date" class="border-0 rounded-lg px-3 py-2 text-sm bg-white shadow-sm focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pelanggan</label>
                    <select wire:model.live="filter_pelanggan_id" class="border-0 rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-[150px] focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                        <option value="">Semua</option>
                        @foreach($daftarPelanggan as $plg)
                            <option value="{{ $plg->id_pelanggan }}">{{ $plg->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Marketing</label>
                    <select wire:model.live="filter_marketing_id" class="border-0 rounded-lg px-3 py-2 text-sm bg-white shadow-sm min-w-[150px] focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                        <option value="">Semua</option>
                        @foreach($daftarMarketing as $mkt)
                            <option value="{{ $mkt->id_marketing }}">{{ $mkt->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Cari Nota</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input wire:model.live.debounce.300ms="filter_keyword" type="text" placeholder="Ketik kata kunci..."
                               class="w-full pl-10 pr-4 py-2 border-0 rounded-lg text-sm bg-white shadow-sm focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 {{ $isOwnerRole ? 'border-b border-slate-100' : 'border-b border-sage/10' }}">
                            <th class="p-4">Tgl Transaksi</th><th class="p-4">Kode Nota</th><th class="p-4">Plg & Mkt</th><th class="p-4 text-right">Total</th><th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftar_nota as $nota)
                            <tr class="transition-colors {{ $isOwnerRole ? 'hover:bg-slate-50 border-b border-slate-50' : 'hover:bg-sage-light/20 border-b border-sage/5' }}">
                                <td class="p-4 text-slate-600">{{ $nota->tanggal_transaksi->format('d M Y, H:i') }}</td>
                                <td class="p-4 font-headline font-bold {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }}">{{ $nota->kode_nota }}</td>
                                <td class="p-4">
                                    <span class="block font-semibold text-slate-700">{{ $nota->pelanggan->nama ?? 'Umum' }}</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">Sales: {{ $nota->marketing->nama ?? '-' }}</span>
                                </td>
                                <td class="p-4 text-right font-bold text-slate-600">Rp {{ number_format($nota->total_harga, 0, ',', '.') }}</td>
                                <td class="p-4 text-center">
                                    <button wire:click="pilihNota({{ $nota->id_transaksi_penjualan }})"
                                            class="text-[11px] font-bold px-4 py-1.5 rounded-lg text-white shadow-sm transition-colors
                                                   {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }}">
                                        Pilih Nota
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-10 text-center text-slate-400 font-semibold">Tidak ada nota ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- TAMPILAN 2: DETAIL NOTA TERPILIH                                 --}}
    {{-- ================================================================ --}}
    @if($notaTerpilih)
        <div class="mb-4">
            <button wire:click="batalPilihNota" class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-white shadow-sm border {{ $isOwnerRole ? 'border-slate-200 text-blue-pro hover:bg-slate-50' : 'text-sage-dark border-sage/15 hover:bg-sage-light/30' }} transition-colors">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Kembali ke Daftar
            </button>
        </div>

        <div class="bg-white rounded-2xl overflow-hidden border {{ $isOwnerRole ? 'border-slate-200' : 'border-slate-200/70' }}">
            <div class="px-6 py-4 flex flex-col md:flex-row justify-between items-start md:items-center {{ $isOwnerRole ? 'bg-charcoal text-white' : 'bg-sage-dark text-white' }}">
                <div>
                    <h3 class="font-headline text-lg font-bold">Detail Nota: {{ $notaTerpilih->kode_nota }}</h3>
                    <p class="text-sm opacity-80 mt-0.5">{{ $notaTerpilih->tanggal_transaksi->format('d M Y, H:i') }} | Kasir: {{ $notaTerpilih->user->name ?? 'Admin' }}</p>
                </div>
                <div class="mt-2 md:mt-0 bg-white/10 px-4 py-2 rounded-lg">
                    <p class="text-[9px] uppercase tracking-widest opacity-70 font-bold">Pelanggan</p>
                    <p class="font-bold">{{ $notaTerpilih->pelanggan->nama ?? 'Walk-in (Umum)' }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 {{ $isOwnerRole ? 'border-b border-slate-100' : 'border-b border-sage/10' }}">
                            <th class="p-4">Nama Barang</th><th class="p-4 text-center">Beli</th><th class="p-4 text-center text-red-500">Diretur</th><th class="p-4 text-center text-emerald-600">Sisa</th><th class="p-4 text-right">Harga</th><th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notaTerpilih->detailPenjualan as $detail)
                            @php $sisaBisaDiretur = $detail->jumlah - $detail->jumlah_diretur; @endphp
                            <tr class="{{ $sisaBisaDiretur <= 0 ? 'opacity-40' : '' }} {{ $isOwnerRole ? 'hover:bg-slate-50 border-b border-slate-50' : 'hover:bg-sage-light/20 border-b border-sage/5' }} transition-colors">
                                <td class="p-4 font-semibold text-slate-700">{{ $detail->produk->nama_produk }}</td>
                                <td class="p-4 text-center text-slate-600 font-semibold">{{ fmod($detail->jumlah, 1) == 0 ? (int)$detail->jumlah : $detail->jumlah }} {{ strtoupper($detail->satuan_saat_jual) }}</td>
                                <td class="p-4 text-center font-bold text-red-500">{{ fmod($detail->jumlah_diretur, 1) == 0 ? (int)$detail->jumlah_diretur : $detail->jumlah_diretur }}</td>
                                <td class="p-4 text-center font-bold text-emerald-600">{{ fmod($sisaBisaDiretur, 1) == 0 ? (int)$sisaBisaDiretur : $sisaBisaDiretur }} {{ strtoupper($detail->satuan_saat_jual) }}</td>
                                <td class="p-4 text-right text-slate-600 font-semibold">Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}<span class="text-[9px] text-slate-400 block">/{{ $detail->satuan_saat_jual }}</span></td>
                                <td class="p-4 text-center">
                                    @if($sisaBisaDiretur > 0)
                                        <button wire:click="bukaModalRetur({{ $detail->id_detail_penjualan }})"
                                                class="text-[11px] font-bold px-4 py-1.5 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-500 hover:text-white transition-colors shadow-sm">
                                            Proses Retur
                                        </button>
                                    @else
                                        <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-full">Habis</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- MODAL PROSES RETUR (Dual Pane)                                   --}}
    {{-- ================================================================ --}}
    @if($showReturModal && $detailTerpilih && $produk_pengganti)
        @php
            $sisaMaksimal = $detailTerpilih->jumlah - $detailTerpilih->jumlah_diretur;
            $isDualUnitRetur = strtolower($detailTerpilih->satuan_saat_jual) === 'meter';
            
            // Hitung harga pengganti berdasarkan satuan yang dipilih
            $hargaPenggantiDisplay = $produk_pengganti->harga_jual_satuan;
            $satuanPenggantiDisplay = strtoupper($produk_pengganti->satuan);
            if ($satuan_pengganti === 'meter' && isset($produk_pengganti->metadata['harga_meter'])) {
                $hargaPenggantiDisplay = $produk_pengganti->metadata['harga_meter'];
                $satuanPenggantiDisplay = 'METER';
            }
            
            $selisihSatuan = $hargaPenggantiDisplay - $detailTerpilih->harga_satuan;
            $totalSelisih = $selisihSatuan * (float)($qty_retur ?: 0);
        @endphp

        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-2xl w-full max-w-5xl flex flex-col md:flex-row max-h-[92vh] overflow-y-auto relative shadow-2xl {{ $isOwnerRole ? 'border-t-4 border-blue-pro' : 'border-t-4 border-gold' }}">

                {{-- Close Button --}}
                <button wire:click="tutupModalRetur" class="absolute top-3 right-4 text-slate-400 hover:text-red-500 z-10 transition-colors">
                    <span class="material-symbols-outlined text-[22px]">close</span>
                </button>

                {{-- LEFT: RETURNED ITEM --}}
                <div class="w-full md:w-1/2 p-6 {{ $isOwnerRole ? 'bg-slate-50 border-r border-slate-200' : 'bg-sage-light/30 border-r border-sage/10' }}">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4 border-b pb-2 {{ $isOwnerRole ? 'border-slate-200' : 'border-sage/15' }}">Barang Dikembalikan Pelanggan</h3>

                    <div class="mb-5 bg-white p-4 rounded-xl shadow-sm">
                        <p class="font-headline text-lg font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }} leading-tight">{{ $detailTerpilih->produk->nama_produk }}</p>
                        <p class="text-sm text-slate-500 mt-1 font-semibold">Harga Nota: <span class="{{ $isOwnerRole ? 'text-blue-pro' : 'text-sage' }}">Rp {{ number_format($detailTerpilih->harga_satuan, 0, ',', '.') }}</span> <span class="text-[10px] text-slate-400">/{{ $detailTerpilih->satuan_saat_jual }}</span></p>
                        @if($isDualUnitRetur && $detailTerpilih->jumlah_potong_gudang)
                            <p class="text-[10px] text-amber-600 font-bold mt-1 bg-amber-50 px-2 py-0.5 rounded inline-block">📦 Timbangan saat jual: {{ $detailTerpilih->jumlah_potong_gudang }} KG untuk {{ $detailTerpilih->jumlah }} Meter</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Jumlah Diretur ({{ strtoupper($detailTerpilih->satuan_saat_jual) }})</label>
                            @php
                                $isPcsRetur = in_array(strtolower($detailTerpilih->satuan_saat_jual), ['pcs', 'biji', 'unit', 'buah']);
                                $stepRetur = $isPcsRetur ? "1" : "0.01";
                            @endphp
                            <input wire:model.live="qty_retur" 
                                   type="number" 
                                   min="{{ $stepRetur }}" 
                                   max="{{ $sisaMaksimal }}" 
                                   step="{{ $stepRetur }}" 
                                   class="w-full border-gray-300 rounded-lg px-3 py-2 bg-white font-bold focus:ring-yellow-500 text-lg">
                            <p class="text-[11px] text-red-500 mt-1 font-bold">Batas Maksimal: {{ fmod($sisaMaksimal, 1) == 0 ? (int)$sisaMaksimal : $sisaMaksimal }} {{ strtoupper($detailTerpilih->satuan_saat_jual) }}</p>
                            @error('qty_retur') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Kondisi Fisik</label>
                            <select wire:model="kondisi_retur" class="w-full border-0 rounded-lg px-3 py-2.5 bg-white font-bold shadow-sm focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                                <option value="BAGUS">BAGUS (Kembali Gudang)</option>
                                <option value="RUSAK">RUSAK (Cacat)</option>
                            </select>
                        </div>
                    </div>

                    {{-- INPUT KG RETUR (Hanya untuk barang dual-unit / Meter) --}}
                    @if($isDualUnitRetur)
                        <div class="bg-red-50 p-3 rounded-lg border border-red-200 mb-2">
                            <label class="block text-[10px] font-bold text-red-600 uppercase tracking-wider mb-1">⚖️ Berat Timbangan Kembali (KG)</label>
                            <p class="text-[9px] text-red-500 mb-2">Default: proporsional dari timbangan saat jual. Ubah jika admin menimbang ulang.</p>
                            <input wire:model.live="jumlah_potong_gudang_retur" 
                                   type="number" step="0.001" min="0.001"
                                   class="w-full border-red-300 rounded-lg px-3 py-2 bg-white font-bold text-red-700 text-lg focus:ring-red-500">
                            @error('jumlah_potong_gudang_retur') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>

                {{-- RIGHT: REPLACEMENT ITEM --}}
                <div class="w-full md:w-1/2 p-6 flex flex-col justify-between bg-white">
                    <div>
                        <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4 border-b pb-2 border-slate-100">Barang Pengganti</h3>

                        {{-- Live Search --}}
                        <div class="mb-4 relative">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Ganti dengan barang lain?</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                <input wire:model.live.debounce.300ms="search_produk_pengganti" type="text" placeholder="Ketik SKU, Nama, Merk..."
                                       class="w-full pl-10 pr-4 py-2.5 border-0 rounded-lg text-sm {{ $isOwnerRole ? 'bg-blue-50/50' : 'bg-sage-light/30' }} focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }} shadow-sm">
                            </div>
                            @if(count($hasil_pencarian_produk) > 0)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden max-h-48 overflow-y-auto">
                                    @foreach($hasil_pencarian_produk as $hasil)
                                        <div wire:click="pilihBarangPengganti({{ $hasil->id_produk }})" class="p-3 cursor-pointer hover:bg-slate-50 flex justify-between items-center transition-colors border-b border-slate-50">
                                            <div>
                                                <p class="text-sm font-bold text-slate-700">{{ $hasil->nama_produk }}</p>
                                                <p class="text-xs font-semibold text-emerald-600">Rp {{ number_format($hasil->harga_jual_satuan, 0, ',', '.') }}</p>
                                            </div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $hasil->stok_saat_ini > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">{{ $hasil->stok_display }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Selected Replacement --}}
                        <div class="bg-slate-50 p-4 rounded-xl mb-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Akan Diberikan:</p>
                                    <p class="font-headline font-bold {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }} leading-tight">{{ $produk_pengganti->nama_produk }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-headline text-xl font-bold text-slate-700">Rp {{ number_format($hargaPenggantiDisplay, 0, ',', '.') }}</p>
                                    <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">per {{ $satuanPenggantiDisplay }}</p>
                                </div>
                            </div>

                            {{-- Toggle KG / Meter untuk pengganti (hanya jika pengganti punya harga_meter) --}}
                            @if(isset($produk_pengganti->metadata['harga_meter']))
                                <div class="mt-3 pt-3 border-t border-slate-200">
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Satuan Pengganti</p>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="$set('satuan_pengganti', '{{ strtolower($produk_pengganti->satuan) }}')"
                                                class="flex-1 py-2 rounded-lg text-xs font-bold transition-all {{ $satuan_pengganti !== 'meter' ? ($isOwnerRole ? 'bg-blue-pro text-white' : 'bg-sage-dark text-white') : 'bg-white text-slate-500 border border-slate-200' }}">
                                            {{ strtoupper($produk_pengganti->satuan) }} — Rp {{ number_format($produk_pengganti->harga_jual_satuan, 0, ',', '.') }}
                                        </button>
                                        <button type="button" wire:click="$set('satuan_pengganti', 'meter')"
                                                class="flex-1 py-2 rounded-lg text-xs font-bold transition-all {{ $satuan_pengganti === 'meter' ? ($isOwnerRole ? 'bg-blue-pro text-white' : 'bg-sage-dark text-white') : 'bg-white text-slate-500 border border-slate-200' }}">
                                            METER — Rp {{ number_format($produk_pengganti->metadata['harga_meter'], 0, ',', '.') }}
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Price Difference --}}
                        <div class="mb-4 p-4 rounded-xl {{ $totalSelisih > 0 ? 'bg-amber-50 border-2 border-amber-200' : ($totalSelisih < 0 ? 'bg-emerald-50 border-2 border-emerald-200' : 'bg-slate-50 border-2 border-slate-200') }}">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Selisih Biaya</p>
                            @if($totalSelisih > 0)
                                <p class="font-headline text-xl font-bold text-amber-700">Pelanggan Nambah: Rp {{ number_format(abs($totalSelisih), 0, ',', '.') }}</p>
                            @elseif($totalSelisih < 0)
                                <p class="font-headline text-xl font-bold text-emerald-700">Toko Kembalikan: Rp {{ number_format(abs($totalSelisih), 0, ',', '.') }}</p>
                            @else
                                <p class="font-headline text-xl font-bold text-slate-600">Tukar Guling (Rp 0)</p>
                            @endif
                        </div>
                    </div>

                    {{-- Execute Section --}}
                    <form wire:submit="prosesRetur" class="border-t border-slate-100 pt-4 mt-auto">
                        <div class="mb-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tanggal & Waktu Retur</label>
                            <input wire:model="tanggal_retur" type="datetime-local" 
                                   class="w-full border-0 rounded-lg px-3 py-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }} font-semibold">
                        </div>
                        <div class="mb-3">
                            <input wire:model="catatan" type="text" placeholder="Catatan Wajib (Cth: Speaker sobek minta tukar)" required
                                   class="w-full border-0 rounded-lg px-3 py-2.5 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
                        </div>
                        <div class="mb-4">
                            <input wire:model="password_admin" type="password" placeholder="Otorisasi: Password Admin" required
                                   class="w-full border border-red-200 rounded-lg px-3 py-2.5 text-sm focus:ring-red-300 bg-red-50/50">
                            @error('password_admin') <span class="text-red-500 text-xs font-semibold block mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="tutupModalRetur" class="px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                            <button type="submit"
                                    wire:confirm="Pastikan fisik barang lama dan uang selisih sudah diterima/dikembalikan. Lanjutkan proses?"
                                    class="px-6 py-2.5 rounded-xl font-bold text-sm text-white shadow-md transition-all
                                           {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }}">
                                Proses Retur Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>