@props([
    'nota',
    'tipe' => 'POS',          // 'POS' atau 'RETUR'
    'owner' => false,
    'closeAction',            // nama method Livewire untuk menutup modal (wajib)
    'koreksiAction' => null,  // nama method untuk tombol Koreksi qty (null = sembunyikan tombol)
    'returLinkAction' => null, // nama method untuk navigasi silang nota POS <-> RETUR (null = teks biasa)
])

@php
    // Helper format angka: buang desimal jika bulat
    $fmt = fn ($v) => fmod((float) $v, 1) == 0 ? (int) $v : (float) $v;
    $accent = $owner ? 'text-blue-pro' : 'text-sage-dark';
    $headerBg = $owner ? 'bg-charcoal' : 'bg-sage-dark';

    $jumlahKoreksi = ($tipe === 'POS' && $nota->riwayatKoreksi) ? $nota->riwayatKoreksi->count() : 0;
@endphp

<div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh] shadow-2xl">

        {{-- HEADER --}}
        <div class="px-6 py-4 flex justify-between items-center shrink-0 {{ $headerBg }} text-white">
            <div class="min-w-0">
                <h3 class="font-headline text-lg font-bold">
                    {{ $tipe === 'POS' ? 'Detail Nota Penjualan' : 'Detail Nota Retur' }}
                </h3>
                <div class="flex flex-wrap items-center gap-2 mt-1">
                    @if($tipe === 'POS')
                        <p class="text-sm opacity-80">{{ $nota->tanggal_transaksi->format('d M Y, H:i') }}</p>
                    @else
                        <p class="text-sm opacity-80">Kode: {{ $nota->kode_retur }}</p>
                    @endif
                    @if($tipe === 'POS' && ($nota->status_penjualan ?? '') === 'DIRETUR')
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white/15 text-white">
                            <span class="material-symbols-outlined text-[13px]">undo</span> Ada Retur
                        </span>
                    @endif
                    @if($jumlahKoreksi > 0)
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-400/90 text-amber-950">
                            <span class="material-symbols-outlined text-[13px]">edit_note</span> Dikoreksi {{ $jumlahKoreksi }}x
                        </span>
                    @endif
                </div>
            </div>
            <button wire:click="{{ $closeAction }}" class="px-4 py-2 rounded-lg font-bold text-sm bg-white/10 hover:bg-red-500 transition-colors shrink-0">Tutup</button>
        </div>

        <div class="p-6 overflow-y-auto flex-1">
            @if($tipe === 'POS')
                {{-- ============================ NOTA PENJUALAN ============================ --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                    <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Waktu</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $nota->tanggal_transaksi->format('d M Y, H:i') }}</p></div>
                    <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Kasir</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $nota->user->name ?? '-' }}</p></div>
                    <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pelanggan</p><p class="font-semibold text-sm {{ $accent }} mt-1">{{ $nota->pelanggan->nama ?? 'Umum' }}</p></div>
                    <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Marketing</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $nota->marketing->nama ?? '-' }}</p></div>
                </div>

                <h4 class="font-semibold text-sm text-slate-700 mb-2 border-b pb-2 border-slate-100">Daftar Barang</h4>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 border-b border-slate-100">
                            <th class="p-3">Barang</th>
                            <th class="p-3 text-center">Qty</th>
                            <th class="p-3 text-right">Harga</th>
                            <th class="p-3 text-right">Subtotal</th>
                            @if($koreksiAction)<th class="p-3 text-center">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($nota->detailPenjualan as $det)
                            @php
                                $sudahKoreksi = $jumlahKoreksi > 0 && $nota->riwayatKoreksi->where('id_detail_penjualan', $det->id_detail_penjualan)->count() > 0;
                            @endphp
                            <tr>
                                <td class="p-3 align-top">
                                    <span class="font-bold text-gray-800 inline-flex items-center gap-1.5">
                                        {{ $det->produk->nama_produk }}
                                        @if($sudahKoreksi)
                                            <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="material-symbols-outlined text-[11px]">edit_note</span> Dikoreksi
                                            </span>
                                        @endif
                                    </span>

                                    {{-- Jejak retur untuk barang ini --}}
                                    @if($det->jumlah_diretur > 0)
                                        @php
                                            $daftarJejakRetur = [];
                                            foreach($nota->transaksiRetur as $retur) {
                                                foreach($retur->detailRetur as $dRet) {
                                                    if($dRet->id_produk_dikembalikan === $det->id_produk) {
                                                        $daftarJejakRetur[] = ['detail' => $dRet, 'nota_retur' => $retur];
                                                    }
                                                }
                                            }
                                        @endphp

                                        @forelse($daftarJejakRetur as $jejak)
                                            <div class="mt-2 bg-orange-50 border border-orange-200 rounded-lg p-3 text-xs">
                                                <p class="text-orange-700 font-bold mb-1 inline-flex items-center gap-1 uppercase tracking-wide text-[10px]">
                                                    <span class="material-symbols-outlined text-[13px]">undo</span>
                                                    Diretur {{ $jejak['nota_retur']->tanggal_retur->format('d/m/Y H:i') }}
                                                </p>
                                                <p class="text-gray-700 mb-0.5">Dikembalikan <strong class="text-red-600">{{ $fmt($jejak['detail']->jumlah) }} {{ strtoupper($det->satuan_saat_jual) }}</strong> (Kondisi: {{ $jejak['detail']->kondisi_barang_dikembalikan }})</p>
                                                <p class="text-gray-700 mb-0.5">Diganti dengan <strong class="text-green-700">{{ $jejak['detail']->produkPengganti->nama_produk }}</strong> ({{ $fmt($jejak['detail']->jumlah) }} {{ strtoupper($det->satuan_saat_jual) }})</p>
                                                <p class="bg-white p-1.5 rounded border border-orange-100 text-gray-600 italic mt-1.5">"{{ $jejak['nota_retur']->catatan ?? 'Tanpa catatan' }}"</p>
                                                @if($returLinkAction)
                                                    <button wire:click="{{ $returLinkAction }}({{ $jejak['nota_retur']->id_retur }}, 'RETUR')" class="mt-2 bg-white border border-orange-300 text-orange-700 hover:bg-orange-100 px-3 py-1 rounded-full font-bold transition-colors w-max text-[10px]">
                                                        Buka Dokumen Retur &rarr;
                                                    </button>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="block text-[10px] bg-red-50 text-red-600 px-2 py-0.5 rounded font-bold mt-1 w-max border border-red-200">Total Diretur: {{ $fmt($det->jumlah_diretur) }} qty</span>
                                        @endforelse
                                    @endif
                                </td>
                                <td class="p-3 text-center align-top">
                                    <span class="font-bold text-gray-700">{{ $fmt($det->jumlah) }} {{ strtoupper($det->satuan_saat_jual) }}</span>
                                    @if(strtolower($det->satuan_saat_jual) === 'meter' && $det->jumlah_potong_gudang)
                                        <span class="block text-[9px] text-amber-600 font-semibold mt-0.5">{{ $fmt($det->jumlah_potong_gudang) }} KG ditimbang</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right text-gray-600 align-top">Rp {{ number_format($det->harga_satuan, 0, ',', '.') }}<span class="text-[9px] text-slate-400 block">/{{ $det->satuan_saat_jual }}</span></td>
                                <td class="p-3 text-right font-bold text-green-700 align-top">Rp {{ number_format($det->subtotal, 0, ',', '.') }}</td>
                                @if($koreksiAction)
                                    <td class="p-3 text-center align-top">
                                        <button wire:click="{{ $koreksiAction }}({{ $det->id_detail_penjualan }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-colors {{ $owner ? 'border-blue-pro/30 text-blue-pro hover:bg-blue-pro hover:text-white' : 'border-sage-dark/30 text-sage-dark hover:bg-sage-dark hover:text-white' }}">
                                            <span class="material-symbols-outlined text-[14px]">edit_note</span>
                                            Koreksi
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50">
                            <td colspan="3" class="p-3 text-right font-bold text-slate-500 uppercase text-xs">Total:</td>
                            <td class="p-3 text-right font-headline font-bold text-lg text-emerald-600">Rp {{ number_format($nota->total_harga, 0, ',', '.') }}</td>
                            @if($koreksiAction)<td></td>@endif
                        </tr>
                    </tfoot>
                </table>

                {{-- Riwayat koreksi qty (read-only, tampil di semua halaman) --}}
                @if($jumlahKoreksi > 0)
                    <div class="mt-6">
                        <h4 class="font-semibold text-sm text-slate-700 mb-3 border-b pb-2 border-slate-100 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-amber-500">history</span>
                            Riwayat Perubahan Qty
                        </h4>
                        <div class="space-y-2">
                            @foreach($nota->riwayatKoreksi->sortByDesc('created_at') as $kor)
                                <div class="border border-slate-200 rounded-lg p-3 text-xs bg-white">
                                    <div class="flex flex-wrap justify-between items-center gap-2 mb-1.5">
                                        <span class="font-bold text-slate-700">{{ $kor->produk->nama_produk ?? '-' }}</span>
                                        <span class="text-slate-400 text-[10px] font-semibold">{{ \Carbon\Carbon::parse($kor->created_at)->format('d M Y, H:i') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-semibold line-through">{{ $fmt($kor->qty_sebelum) }}</span>
                                        <span class="material-symbols-outlined text-[14px] text-slate-400">arrow_forward</span>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold">{{ $fmt($kor->qty_sesudah) }}</span>
                                        @if($kor->potong_gudang_sesudah !== null)
                                            <span class="text-amber-600 font-semibold ml-1">({{ $fmt($kor->potong_gudang_sebelum) }} &rarr; {{ $fmt($kor->potong_gudang_sesudah) }} KG)</span>
                                        @endif
                                    </div>
                                    <p class="text-slate-600 italic">"{{ $kor->alasan }}"</p>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-1">Oleh: {{ $kor->user->name ?? '-' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                {{-- ============================ NOTA RETUR ============================ --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                    <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Waktu Retur</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $nota->tanggal_retur->format('d M Y, H:i') }}</p></div>
                    <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Diproses Oleh</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $nota->user->name ?? '-' }}</p></div>
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Transaksi Asal</p>
                        @if($returLinkAction)
                            <p class="font-semibold text-sm {{ $accent }} mt-1 cursor-pointer underline" wire:click="{{ $returLinkAction }}({{ $nota->transaksiPenjualan->id_transaksi_penjualan }}, 'POS')">{{ optional($nota->transaksiPenjualan)->tanggal_transaksi?->format('d M Y, H:i') ?? '-' }}</p>
                        @else
                            <p class="font-semibold text-sm text-slate-700 mt-1">{{ optional($nota->transaksiPenjualan)->tanggal_transaksi?->format('d M Y, H:i') ?? '-' }}</p>
                        @endif
                    </div>
                    <div class="bg-slate-50 p-3 rounded-lg"><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pelanggan</p><p class="font-semibold text-sm text-slate-700 mt-1">{{ $nota->transaksiPenjualan->pelanggan->nama ?? 'Umum' }}</p></div>
                </div>

                <div class="mb-5 bg-slate-50 border border-slate-200 p-4 rounded-xl">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Catatan Retur</p>
                    <p class="text-sm text-slate-700 italic">"{{ $nota->catatan ?? 'Tidak ada catatan.' }}"</p>
                </div>

                <h4 class="font-semibold text-sm text-slate-700 mb-3 border-b pb-2 border-slate-100">Rincian Tukar Barang</h4>
                <div class="space-y-4">
                    @foreach($nota->detailRetur as $detRetur)
                        @php
                            $detailAsli = $nota->transaksiPenjualan->detailPenjualan->firstWhere('id_produk', $detRetur->id_produk_dikembalikan);
                            $satuanAsli = $detailAsli ? strtoupper($detailAsli->satuan_saat_jual) : strtoupper($detRetur->produkDikembalikan->satuan);
                            $hargaAsli = $detailAsli ? $detailAsli->harga_satuan : 0;
                        @endphp
                        <div class="rounded-xl border border-slate-200 overflow-hidden">
                            <div class="flex flex-col md:flex-row">
                                <div class="flex-1 bg-red-50 p-4">
                                    <p class="text-[9px] font-bold text-red-500 uppercase tracking-widest mb-2">Dikembalikan</p>
                                    <p class="font-bold text-red-700 text-sm">{{ $detRetur->produkDikembalikan->nama_produk }}</p>
                                    <div class="mt-2 space-y-1 text-xs text-slate-600">
                                        <p>Jumlah: <strong class="text-red-600">{{ $fmt($detRetur->jumlah) }} {{ $satuanAsli }}</strong></p>
                                        <p>Harga Nota: <strong>Rp {{ number_format($hargaAsli, 0, ',', '.') }}</strong> /{{ $satuanAsli }}</p>
                                        <p>Kondisi: <span class="font-bold px-1.5 py-0.5 rounded text-[10px] {{ $detRetur->kondisi_barang_dikembalikan === 'BAGUS' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $detRetur->kondisi_barang_dikembalikan }}</span></p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-center px-3 bg-slate-100">
                                    <span class="material-symbols-outlined text-slate-400">arrow_forward</span>
                                </div>
                                <div class="flex-1 bg-emerald-50 p-4">
                                    <p class="text-[9px] font-bold text-emerald-600 uppercase tracking-widest mb-2">Pengganti</p>
                                    <p class="font-bold text-emerald-700 text-sm">{{ $detRetur->produkPengganti->nama_produk }}</p>
                                    <div class="mt-2 space-y-1 text-xs text-slate-600">
                                        <p>Jumlah: <strong class="text-emerald-600">{{ $fmt($detRetur->jumlah) }} {{ $satuanAsli }}</strong></p>
                                    </div>
                                </div>
                            </div>
                            @if($detRetur->subtotal_biaya != 0)
                                <div class="px-4 py-2 {{ $detRetur->subtotal_biaya > 0 ? 'bg-amber-50 border-t border-amber-200' : 'bg-emerald-50 border-t border-emerald-200' }} text-xs font-bold">
                                    @if($detRetur->subtotal_biaya > 0)
                                        <span class="text-amber-700">Pelanggan Menambah: Rp {{ number_format(abs($detRetur->subtotal_biaya), 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-emerald-700">Toko Mengembalikan: Rp {{ number_format(abs($detRetur->subtotal_biaya), 0, ',', '.') }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 p-4 rounded-xl {{ $nota->total_biaya_retur > 0 ? 'bg-amber-50 border-2 border-amber-200' : ($nota->total_biaya_retur < 0 ? 'bg-emerald-50 border-2 border-emerald-200' : 'bg-slate-50 border-2 border-slate-200') }}">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Selisih Biaya Retur</p>
                    @if($nota->total_biaya_retur > 0)
                        <p class="font-headline text-xl font-bold text-amber-700">Pelanggan Menambah: Rp {{ number_format(abs($nota->total_biaya_retur), 0, ',', '.') }}</p>
                    @elseif($nota->total_biaya_retur < 0)
                        <p class="font-headline text-xl font-bold text-emerald-700">Toko Mengembalikan: Rp {{ number_format(abs($nota->total_biaya_retur), 0, ',', '.') }}</p>
                    @else
                        <p class="font-headline text-xl font-bold text-slate-600">Tukar Guling (Rp 0)</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
