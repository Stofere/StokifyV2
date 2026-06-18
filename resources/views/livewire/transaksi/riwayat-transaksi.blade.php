@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp

<div class="p-4 md:p-8 max-w-7xl mx-auto fade-in">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-3">
        <div>
            <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Riwayat Dokumen Transaksi</h2>
            <p class="text-slate-400 text-sm mt-1">Lacak seluruh riwayat penjualan kasir dan proses tukar retur barang.</p>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="flex gap-1 mb-5 {{ $isOwnerRole ? 'border-b border-slate-200' : 'border-b border-sage/15' }}">
        <button wire:click="switchTab('POS')"
                class="px-5 py-3 font-bold text-sm transition-all border-b-2 {{ $activeTab === 'POS'
                    ? ($isOwnerRole ? 'border-blue-pro text-blue-pro' : 'border-sage-dark text-sage-dark')
                    : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            <span class="material-symbols-outlined text-[16px] align-middle mr-1">receipt_long</span>
            Nota Penjualan
        </button>
        <button wire:click="switchTab('RETUR')"
                class="px-5 py-3 font-bold text-sm transition-all border-b-2 {{ $activeTab === 'RETUR'
                    ? ($isOwnerRole ? 'border-violet-600 text-violet-700' : 'border-violet-500 text-violet-600')
                    : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            <span class="material-symbols-outlined text-[16px] align-middle mr-1">swap_horiz</span>
            Nota Retur & Tukar
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl p-4 mb-5 flex flex-wrap gap-3 items-end border {{ $isOwnerRole ? 'border-slate-200' : 'border-slate-200/70' }}">
        <div>
            <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Dari Tgl</label>
            <input wire:model.live="tgl_mulai" type="date" class="border-0 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
        </div>
        <div>
            <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Sampai Tgl</label>
            <input wire:model.live="tgl_akhir" type="date" class="border-0 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
        </div>
        <div class="flex-1">
            <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pencarian</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                <input wire:model.live.debounce.500ms="keyword" type="text" placeholder="Cari Nota, Pelanggan, Sales..."
                       class="w-full pl-10 pr-4 py-2 border-0 rounded-lg text-sm bg-slate-50 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }}">
            </div>
        </div>
    </div>

    {{-- Flash Notifikasi --}}
    @if(session()->has('sukses'))
        <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 p-3 mb-5 rounded-xl text-sm font-semibold">{{ session('sukses') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="bg-red-50 text-red-700 border border-red-200 p-3 mb-5 rounded-xl text-sm font-semibold">{{ session('error') }}</div>
    @endif

    {{-- TAB: POS --}}
    @if($activeTab === 'POS')
        <div class="bg-white rounded-2xl overflow-hidden fade-in border {{ $isOwnerRole ? 'border-slate-200' : 'border-slate-200/70' }}">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 {{ $isOwnerRole ? 'border-b border-slate-100' : 'border-b border-sage/10' }}">
                            <th class="p-4">Waktu & Nota</th><th class="p-4">Pelanggan & Sales</th><th class="p-4">Kasir</th><th class="p-4 text-right">Total</th><th class="p-4 text-center">Menu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarPos as $pos)
                            <tr class="transition-colors {{ $isOwnerRole ? 'hover:bg-slate-50 border-b border-slate-50' : 'hover:bg-sage-light/20 border-b border-sage/5' }}">
                                <td class="p-4">
                                    <p class="font-headline font-bold mt-0.5 {{ $isOwnerRole ? 'text-blue-pro' : 'text-sage-dark' }}">{{ $pos->tanggal_transaksi->format('d/m/Y H:i') }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold">{{ $pos->kode_nota }}</p>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @if($pos->status_penjualan === 'DIRETUR')
                                            <span class="inline-flex items-center gap-0.5 bg-orange-50 text-orange-700 text-[10px] px-2 py-0.5 rounded font-bold uppercase border border-orange-200">
                                                <span class="material-symbols-outlined text-[12px]">undo</span> Ada Retur
                                            </span>
                                        @endif
                                        @if($pos->riwayat_koreksi_count > 0)
                                            <span class="inline-flex items-center gap-0.5 bg-amber-50 text-amber-700 text-[10px] px-2 py-0.5 rounded font-bold uppercase border border-amber-200">
                                                <span class="material-symbols-outlined text-[12px]">edit_note</span> Dikoreksi
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4">
                                    <p class="font-semibold text-slate-700">{{ $pos->pelanggan->nama ?? 'Walk-in (Umum)' }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Sales: {{ $pos->marketing->nama ?? '-' }}</p>
                                </td>
                                <td class="p-4 text-slate-600 font-semibold">{{ $pos->user->name ?? '-' }}</td>
                                <td class="p-4 text-right font-headline font-bold text-emerald-600">Rp {{ number_format($pos->total_harga, 0, ',', '.') }}</td>
                                <td class="p-4 text-center">
                                    <button wire:click="lihatDetail({{ $pos->id_transaksi_penjualan }})"
                                            class="text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors {{ $isOwnerRole ? 'bg-blue-50 text-blue-pro hover:bg-blue-pro hover:text-white' : 'bg-sage-light text-sage-dark hover:bg-sage hover:text-white' }}">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-10 text-center text-slate-400 font-semibold">Tidak ada Nota Penjualan ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 {{ $isOwnerRole ? 'bg-slate-50 border-t border-slate-100' : 'bg-[#F8F9FA] border-t border-sage/10' }}">{{ $daftarPos->links() }}</div>
        </div>
    @endif

    {{-- TAB: RETUR --}}
    @if($activeTab === 'RETUR')
        <div class="bg-white rounded-2xl overflow-hidden fade-in border {{ $isOwnerRole ? 'border-slate-200' : 'border-slate-200/70' }}">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 {{ $isOwnerRole ? 'border-b border-slate-100' : 'border-b border-sage/10' }}">
                            <th class="p-4">Waktu & Retur</th><th class="p-4">Nota Asal & Pelanggan</th><th class="p-4">Diinput Oleh</th><th class="p-4 text-right">Selisih</th><th class="p-4 text-center">Menu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarRetur as $retur)
                            <tr class="transition-colors {{ $isOwnerRole ? 'hover:bg-slate-50 border-b border-slate-50' : 'hover:bg-sage-light/20 border-b border-sage/5' }}">
                                <td class="p-4">
                                    <p class="font-headline font-bold mt-0.5 text-violet-600">{{ $retur->tanggal_retur->format('d/m/Y H:i') }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold">{{ $retur->kode_retur }}</p>
                                </td>
                                <td class="p-4">
                                    <p class="text-xs text-slate-400 font-semibold mb-0.5">Nota: {{ $retur->transaksiPenjualan->kode_nota ?? '-' }}</p>
                                    <p class="font-semibold text-slate-700">{{ $retur->transaksiPenjualan->pelanggan->nama ?? 'Umum' }}</p>
                                </td>
                                <td class="p-4 text-slate-600 font-semibold">{{ $retur->user->name ?? '-' }}</td>
                                <td class="p-4 text-right">
                                    @if($retur->total_biaya_retur > 0)
                                        <p class="font-bold text-amber-600">+ Rp {{ number_format(abs($retur->total_biaya_retur), 0, ',', '.') }}</p>
                                        <p class="text-[9px] text-slate-400 uppercase font-bold">Plg Nambah</p>
                                    @elseif($retur->total_biaya_retur < 0)
                                        <p class="font-bold text-emerald-600">- Rp {{ number_format(abs($retur->total_biaya_retur), 0, ',', '.') }}</p>
                                        <p class="text-[9px] text-slate-400 uppercase font-bold">Toko Kembalikan</p>
                                    @else
                                        <p class="font-bold text-slate-500">Rp 0</p>
                                        <p class="text-[9px] text-slate-400 uppercase font-bold">Tukar Guling</p>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    <button wire:click="lihatDetail({{ $retur->id_retur }})"
                                            class="text-[11px] font-bold px-3 py-1.5 rounded-lg bg-violet-50 text-violet-600 hover:bg-violet-600 hover:text-white transition-colors">
                                        Rincian
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-10 text-center text-slate-400 font-semibold">Tidak ada Nota Retur ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 {{ $isOwnerRole ? 'bg-slate-50 border-t border-slate-100' : 'bg-[#F8F9FA] border-t border-sage/10' }}">{{ $daftarRetur->links() }}</div>
        </div>
    @endif

    {{-- MODAL DETAIL NOTA (komponen bersama) --}}
    @if($modal_open && $detail_nota)
        <x-nota-detail-modal
            :nota="$detail_nota"
            :tipe="$activeTab"
            :owner="$isOwnerRole"
            close-action="tutupModal"
            koreksi-action="bukaKoreksi"
            retur-link-action="lihatDetail" />
    @endif

    {{-- ==================== MODAL INPUT KOREKSI QTY ==================== --}}
    @if($showKoreksiModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4 fade-in">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[92vh] overflow-y-auto flex flex-col border-t-4 {{ $isOwnerRole ? 'border-blue-pro' : 'border-sage-dark' }}">
                <div class="px-6 py-4 flex justify-between items-center shrink-0 {{ $isOwnerRole ? 'bg-charcoal text-white' : 'bg-sage-dark text-white' }}">
                    <h3 class="text-lg font-headline font-bold">Koreksi Jumlah Barang</h3>
                    <button wire:click="tutupKoreksi" class="text-white/70 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="p-6 bg-slate-50">
                    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Barang</p>
                        <p class="font-bold text-slate-800 mt-0.5">{{ $koreksi_nama_produk }}</p>
                        <p class="text-xs text-slate-500 mt-1">
                            Qty saat ini yang ada di nota: <strong>{{ fmod($koreksi_qty_lama, 1) == 0 ? (int)$koreksi_qty_lama : $koreksi_qty_lama }} {{ strtoupper($koreksi_satuan) }}</strong>
                            @if($koreksi_diretur > 0)
                                <span class="text-orange-600">• Sudah diretur: {{ fmod($koreksi_diretur, 1) == 0 ? (int)$koreksi_diretur : $koreksi_diretur }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-widest">Qty Baru ({{ strtoupper($koreksi_satuan) }}) <span class="text-red-500">*</span></label>
                        <input type="number" step="any" min="0" wire:model="koreksi_qty_baru"
                               class="w-full border border-slate-200 rounded-lg p-3 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }} text-sm font-semibold bg-white">
                        @error('koreksi_qty_baru') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($koreksi_is_dual_unit)
                        <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-3">
                            <label class="block text-[10px] font-bold text-amber-700 mb-1.5 uppercase tracking-widest">Berat Timbangan Baru (KG) <span class="text-red-500">*</span></label>
                            <p class="text-[11px] text-amber-600 mb-2">Barang dijual per Meter — masukkan KG fisik yang benar (stok gudang disesuaikan dari KG ini). KG lama: {{ $koreksi_potong_lama + 0 }}.</p>
                            <input type="number" step="any" min="0" wire:model="koreksi_potong_baru"
                                   class="w-full border border-amber-200 rounded-lg p-3 focus:ring-2 focus:ring-amber-200 text-sm font-semibold bg-white">
                            @error('koreksi_potong_baru') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="mb-2">
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-widest">Alasan Koreksi <span class="text-red-500">*</span></label>
                        <textarea wire:model="koreksi_alasan" rows="2" placeholder="Contoh: Barang sampai ke pembeli hanya 5, sisa dikembalikan ke stok."
                                  class="w-full border border-slate-200 rounded-lg p-3 focus:ring-2 {{ $isOwnerRole ? 'focus:ring-blue-pro/20' : 'focus:ring-sage/20' }} text-sm bg-white"></textarea>
                        @error('koreksi_alasan') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="bg-white px-6 py-4 border-t border-slate-200 flex flex-col-reverse sm:flex-row gap-3 justify-end items-center">
                    <button wire:click="tutupKoreksi" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl font-semibold hover:bg-slate-50 transition-colors">Batal</button>
                    <button wire:click="reviewKoreksi" class="w-full sm:w-auto px-8 py-2.5 text-white rounded-xl font-bold shadow-md transition-all active:scale-[0.98] {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }}">
                        Lanjut Konfirmasi
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== MODAL KONFIRMASI + PASSWORD ==================== --}}
    @if($showKoreksiConfirmModal)
        @php
            $selisihStokKg = ($koreksi_is_dual_unit ? (float)$koreksi_potong_baru : (float)$koreksi_qty_baru) - (float)$koreksi_potong_lama;
        @endphp
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[110] p-4 fade-in">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[92vh] overflow-y-auto flex flex-col border-t-4 {{ $selisihStokKg > 0 ? 'border-red-500' : 'border-emerald-500' }}">
                <div class="px-6 py-4 flex justify-between items-center shrink-0 {{ $isOwnerRole ? 'bg-charcoal text-white' : 'bg-sage-dark text-white' }}">
                    <h3 class="text-lg font-headline font-bold">Konfirmasi Koreksi</h3>
                    <button wire:click="$set('showKoreksiConfirmModal', false)" class="text-white/70 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="p-6 bg-slate-50">
                    <p class="text-center text-slate-600 mb-5 text-sm">Pastikan data di bawah benar. Stok sistem akan otomatis disesuaikan.</p>

                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
                        <div class="flex justify-between border-b border-slate-100 pb-3">
                            <span class="text-slate-500 font-bold text-sm">Barang:</span>
                            <span class="font-bold text-slate-800 text-right">{{ $koreksi_nama_produk }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-3">
                            <span class="text-slate-500 font-bold text-sm">Perubahan Qty:</span>
                            <span class="font-bold text-slate-800 text-right">
                                {{ fmod($koreksi_qty_lama, 1) == 0 ? (int)$koreksi_qty_lama : $koreksi_qty_lama }}
                                &rarr;
                                {{ fmod((float)$koreksi_qty_baru, 1) == 0 ? (int)$koreksi_qty_baru : $koreksi_qty_baru }}
                                {{ strtoupper($koreksi_satuan) }}
                            </span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-3">
                            <span class="text-slate-500 font-bold text-sm">Efek ke Stok Sistem:</span>
                            @if($selisihStokKg > 0)
                                <span class="font-bold text-red-600 bg-red-50 px-3 py-1 rounded">&minus; {{ abs($selisihStokKg) + 0 }} (stok berkurang)</span>
                            @elseif($selisihStokKg < 0)
                                <span class="font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded">+ {{ abs($selisihStokKg) + 0 }} (stok bertambah)</span>
                            @else
                                <span class="font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded">Tidak berubah</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-slate-500 font-bold text-sm block mb-1">Alasan Koreksi:</span>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100 italic text-sm">"{{ $koreksi_alasan }}"</p>
                        </div>
                    </div>

                    <div class="mt-6 bg-red-50 border border-red-200 rounded-xl p-5 shadow-sm">
                        <label class="block text-[10px] font-bold text-red-700 mb-1.5 uppercase tracking-widest">Otorisasi Keamanan (Wajib)</label>
                        <p class="text-xs text-red-600 mb-3 font-medium">Tindakan ini tercatat permanen di SISTEM. Masukkan password akun Anda.</p>
                        <input type="password" wire:model="password_admin" placeholder="Masukkan Password Akun Anda..."
                               class="w-full border border-red-200 rounded-lg p-3 focus:ring-2 focus:ring-red-200 text-sm font-semibold bg-white shadow-inner">
                        @error('password_admin') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="bg-white px-6 py-4 border-t border-slate-200 flex flex-col-reverse sm:flex-row gap-3 justify-end items-center">
                    <button wire:click="$set('showKoreksiConfirmModal', false)" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl font-semibold hover:bg-slate-50 transition-colors">Kembali</button>
                    <button wire:click="prosesKoreksi" wire:loading.attr="disabled"
                            class="w-full sm:w-auto px-8 py-2.5 text-white rounded-xl font-bold shadow-md transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 {{ $isOwnerRole ? 'bg-blue-pro hover:bg-blue-800' : 'bg-sage-dark hover:bg-sage' }}">
                        <span wire:loading.remove wire:target="prosesKoreksi">Eksekusi Koreksi</span>
                        <span wire:loading wire:target="prosesKoreksi">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
