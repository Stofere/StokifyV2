@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp
<div class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-200 pb-4">
        <div>
            <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Laporan Katalog & Stok</h2>
            <p class="text-sm text-slate-400 mt-1">Lihat dan cetak ketersediaan barang di gudang berdasarkan Kategori.</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <select wire:model.live="filterKategori" class="w-full md:w-auto border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-blue-500">
                <option value="">-- Semua Kategori --</option>
                @foreach($semuaKategori as $kat)
                    <option value="{{ $kat->id_kategori }}">{{ $kat->nama_kategori }}</option>
                @endforeach
            </select>
            <button wire:click="cetakPdf" wire:loading.attr="disabled" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-bold shadow transition flex items-center gap-2 shrink-0">
                <span wire:loading.remove wire:target="cetakPdf">📄 PDF</span>
                <span wire:loading wire:target="cetakPdf">Memproses...</span>
            </button>
            <button wire:click="exportExcel" wire:loading.attr="disabled" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-bold shadow transition flex items-center gap-2 shrink-0">
                <span wire:loading.remove wire:target="exportExcel">📊 Excel</span>
                <span wire:loading wire:target="exportExcel">Memproses...</span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/70 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-charcoal text-white uppercase tracking-wider text-[11px]">
                <tr>
                    <th class="p-4 font-bold w-12 text-center">No</th>
                    <th class="p-4 font-bold w-32">Kode / SKU</th>
                    <th class="p-4 font-bold">Nama Barang & Spesifikasi</th>
                    <th class="p-4 font-bold text-center w-32">Stok Sisa</th>
                    <th class="p-4 font-bold text-center w-28">Status</th>
                    <th class="p-4 font-bold w-48">Lokasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($groupedProduk as $namaKategori => $produks)
                    
                    {{-- HEADER GRUP KATEGORI --}}
                    <tr class="{{ $isOwnerRole ? 'bg-blue-50' : 'bg-sage-light/40' }}">
                        <td colspan="6" class="p-3 font-black text-sm tracking-wide uppercase border-y {{ $isOwnerRole ? 'text-blue-800 border-blue-200' : 'text-sage-dark border-sage/20' }}">
                            🏷️ KATEGORI: {{ $namaKategori }}
                        </td>
                    </tr>
                    
                    {{-- LOOPING PRODUK DI DALAM KATEGORI TERSEBUT --}}
                    @php $nomor = 1; @endphp
                    @foreach($produks as $prod)
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 text-center font-bold text-gray-400">{{ $nomor++ }}.</td>
                            <td class="p-4 font-mono font-bold text-gray-700 uppercase tracking-wider">{{ $prod->kode_barang }}</td>
                            <td class="p-4">
                                <span class="font-bold text-gray-800">{{ $prod->nama_produk }}</span>
                                @if($prod->metadata)
                                    <div class="text-[10px] text-gray-500 mt-0.5">
                                        @foreach($prod->metadata as $key => $val) [{{ $key }}: {{ is_array($val) ? implode(', ', $val) : $val }}] @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if($prod->lacak_stok)
                                    <span class="font-black text-base {{ $prod->stok_saat_ini <= 0 ? 'text-red-600' : 'text-green-700' }}">{{ $prod->stok_display }}</span>
                                    <span class="text-xs text-gray-500">{{ $prod->satuan }}</span>
                                @else
                                    <span class="text-xs text-gray-400 font-bold">Unlimited</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if($prod->status_stok === 'AMAN')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> AMAN
                                    </span>
                                @elseif($prod->status_stok === 'MENIPIS')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-600">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> MENIPIS
                                    </span>
                                @elseif($prod->status_stok === 'HABIS')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-600">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span> HABIS
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-600 font-semibold">{{ $prod->lokasi ?? '-' }}</td>
                        </tr>
                    @endforeach

                @empty
                    <tr><td colspan="6" class="p-10 text-center text-gray-500 font-bold">Tidak ada barang di kategori ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>