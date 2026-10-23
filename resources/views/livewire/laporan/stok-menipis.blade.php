@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp
<div class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-200 pb-4">
        <div>
            <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Laporan Stok Menipis & Habis</h2>
            <p class="text-sm text-slate-400 mt-1">Daftar produk yang membutuhkan restock segera berdasarkan batas minimum otomatis.</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button wire:click="cetakPdf" wire:loading.attr="disabled" class="w-full md:w-auto justify-center bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-bold shadow transition flex items-center gap-2 shrink-0">
                <span wire:loading.remove wire:target="cetakPdf">📄 Cetak PDF</span>
                <span wire:loading wire:target="cetakPdf">Memproses...</span>
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3">
            <div class="bg-amber-100 p-2 rounded-lg">
                <span class="material-symbols-outlined text-amber-600">warning</span>
            </div>
            <div>
                <p class="text-[10px] font-label font-bold uppercase tracking-widest text-amber-600">Stok Menipis</p>
                <h3 class="font-headline text-2xl font-bold text-amber-700">{{ $produkList->where('status_stok', 'MENIPIS')->count() }}</h3>
            </div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
            <div class="bg-red-100 p-2 rounded-lg">
                <span class="material-symbols-outlined text-red-600">error</span>
            </div>
            <div>
                <p class="text-[10px] font-label font-bold uppercase tracking-widest text-red-600">Stok Habis</p>
                <h3 class="font-headline text-2xl font-bold text-red-700">{{ $produkList->where('status_stok', 'HABIS')->count() }}</h3>
            </div>
        </div>
    </div>

    {{-- Data Table: tabel di layar lebar (lg), di HP & tablet berdiri tiap baris tampil sebagai kartu --}}
    <div class="bg-white rounded-2xl border border-slate-200/70 overflow-hidden">
        <div class="lg:overflow-x-auto">
        <table class="block lg:table w-full text-left text-sm border-collapse">
            <thead class="hidden lg:table-header-group bg-charcoal text-white uppercase tracking-wider text-[11px]">
                <tr>
                    <th class="p-4 font-bold w-12 text-center">No</th>
                    <th class="p-4 font-bold w-32">Kode / SKU</th>
                    <th class="p-4 font-bold">Nama Barang</th>
                    <th class="p-4 font-bold w-32">Kategori</th>
                    <th class="p-4 font-bold text-center w-28">Sisa Stok</th>
                    <th class="p-4 font-bold text-center w-28">Batas Min.</th>
                    <th class="p-4 font-bold text-center w-28">Status</th>
                </tr>
            </thead>
            <tbody class="block lg:table-row-group divide-y divide-gray-100">
                @forelse($produkList as $index => $prod)
                    <tr class="grid grid-cols-[1fr_auto] gap-x-3 gap-y-1.5 p-4 lg:table-row lg:p-0 hover:bg-gray-50 {{ $prod->status_stok === 'HABIS' ? 'bg-red-50/40' : '' }}">
                        <td class="hidden lg:table-cell p-4 text-center font-bold text-gray-400">{{ $index + 1 }}.</td>
                        <td class="hidden lg:table-cell p-4 font-mono font-bold text-gray-700 uppercase tracking-wider">{{ $prod->kode_barang }}</td>
                        <td class="row-span-2 min-w-0 lg:p-4">
                            <span class="font-bold text-gray-800">{{ $prod->nama_produk }}</span>
                            {{-- Di layar lebar kode & kategori punya kolom sendiri --}}
                            <div class="lg:hidden flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-1 text-xs text-gray-500">
                                @if($prod->kode_barang)
                                    <span class="font-mono font-bold uppercase tracking-wider text-gray-600">{{ $prod->kode_barang }}</span>
                                @endif
                                <span>{{ $prod->kategori->nama_kategori ?? '-' }}</span>
                            </div>
                            @if($prod->metadata)
                                <div class="text-[10px] text-gray-500 mt-0.5">
                                    @foreach($prod->metadata as $key => $val) [{{ $key }}: {{ is_array($val) ? implode(', ', $val) : $val }}] @endforeach
                                </div>
                            @endif
                            <p class="lg:hidden text-xs text-gray-500 mt-1">Batas min: {{ $prod->stok_minimum }} {{ $prod->satuan }}</p>
                        </td>
                        <td class="hidden lg:table-cell p-4 text-gray-600 text-xs font-semibold">{{ $prod->kategori->nama_kategori ?? '-' }}</td>
                        <td class="text-right lg:text-center lg:p-4 whitespace-nowrap">
                            <span class="font-black text-xl lg:text-base {{ $prod->stok_saat_ini <= 0 ? 'text-red-600' : 'text-amber-600' }}">{{ $prod->stok_display }}</span>
                            <span class="text-xs text-gray-500">{{ $prod->satuan }}</span>
                        </td>
                        <td class="hidden lg:table-cell p-4 text-center">
                            <span class="font-bold text-gray-700">{{ $prod->stok_minimum }}</span>
                            <span class="text-xs text-gray-500">{{ $prod->satuan }}</span>
                        </td>
                        <td class="text-right lg:text-center lg:p-4">
                            @if($prod->status_stok === 'HABIS')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    HABIS
                                </span>
                            @elseif($prod->status_stok === 'MENIPIS')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    MENIPIS
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="block lg:table-row">
                        <td colspan="7" class="block lg:table-cell p-10 text-center text-gray-500 font-bold">
                            <div class="flex flex-col items-center gap-2">
                                <span class="material-symbols-outlined text-4xl text-emerald-400">check_circle</span>
                                <span>Semua stok dalam kondisi aman! 🎉</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
