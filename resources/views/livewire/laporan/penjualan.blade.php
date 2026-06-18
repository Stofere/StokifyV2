@php $isOwnerRole = Auth::user()->peran === 'OWNER'; @endphp
<div class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-200 pb-4">
        <div>
            <h2 class="font-headline text-2xl md:text-3xl font-bold {{ $isOwnerRole ? 'text-charcoal' : 'text-sage-dark' }}">Laporan Penjualan</h2>
            <p class="text-sm text-slate-400 mt-1">Lihat rekapitulasi penjualan harian, bulanan, atau tahunan.</p>
        </div>
    </div>

    <!-- FILTER AREA -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/70 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipe Laporan</label>
            <select wire:model.live="tipe_filter" class="border-gray-300 rounded-lg p-2.5 text-sm font-bold bg-gray-50 focus:ring-blue-500">
                <option value="harian">Laporan Harian (Rekap WA)</option>
                <option value="bulanan">Laporan Bulanan</option>
                <option value="tahunan">Laporan Tahunan</option>
            </select>
        </div>

        @if($tipe_filter === 'harian')
            <div> 
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Tanggal</label>
                <input type="date" wire:model.live="filter_tanggal" class="border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500">
            </div>
        @endif

        @if($tipe_filter === 'bulanan')
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Bulan</label>
                <select wire:model.live="filter_bulan" class="border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500">
                    @for($i=1; $i<=12; $i++) <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option> @endfor
                </select>
            </div>
        @endif

        @if(in_array($tipe_filter, ['bulanan', 'tahunan']))
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Tahun</label>
                <input type="number" wire:model.live="filter_tahun" class="border-gray-300 rounded-lg p-2.5 text-sm w-24 focus:ring-blue-500">
            </div>
        @endif

        <div class="ml-auto flex items-center gap-3">
            <button wire:click="cetakPdf" wire:loading.attr="disabled" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-bold shadow flex items-center gap-2">
                <span wire:loading.remove wire:target="cetakPdf">📄 PDF</span>
                <span wire:loading wire:target="cetakPdf">Memproses...</span>
            </button>
            <button wire:click="exportExcel" wire:loading.attr="disabled" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-bold shadow flex items-center gap-2">
                <span wire:loading.remove wire:target="exportExcel">📊 Excel</span>
                <span wire:loading wire:target="exportExcel">Memproses...</span>
            </button>
        </div>
    </div>

     <!-- TAMPILAN KHUSUS HARIAN: TEXT BOX COPY-PASTE UNTUK BOS -->
    @if($tipe_filter === 'harian')
        <!-- x-data untuk mengelola state tombol Copy via Alpine.js -->
        <div x-data="{ copied: false }" class="rounded-2xl shadow-lg p-6 {{ $isOwnerRole ? 'bg-gradient-to-r from-blue-900 to-indigo-900' : 'bg-gradient-to-r from-sage-dark to-sage' }}">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                <div class="flex items-center gap-3">
                    <h3 class="text-white font-bold flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg> 
                        Teks Rekapan Cepat
                    </h3>
                    <span class="text-[10px] bg-blue-800 text-blue-200 px-2 py-0.5 rounded font-bold uppercase tracking-wider border border-blue-700">Auto-Generated</span>
                </div>
                
                <!-- TOMBOL COPY (ALPINE.JS) -->
                <button 
                    @click="
                        navigator.clipboard.writeText($refs.rekapTeks.value);
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/30 text-white px-4 py-2 rounded-lg font-bold text-sm transition-all shadow-sm"
                >
                    <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    <svg x-show="copied" style="display: none;" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    <span x-text="copied ? 'Tersalin ke Clipboard!' : 'Copy Teks Rekapan'"></span>
                </button>
            </div>
            
            <!-- TextArea ditambahkan x-ref agar bisa dibaca oleh Alpine.js -->
            <textarea x-ref="rekapTeks" readonly rows="8" class="w-full bg-white/10 text-white border border-white/20 rounded-xl p-4 font-mono text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none transition-all" onclick="this.select()">{{ $teks_rekap_harian }}</textarea>
            <p class="text-blue-200 text-xs mt-2 italic">Teks ini dirancang khusus untuk dikirim langsung ke WhatsApp.</p>
        </div>
    @endif

    <!-- TABEL PREVIEW WEB (DENGAN TOMBOL BUKA MODAL) -->
    <div class="bg-white rounded-2xl border border-slate-200/70 overflow-hidden">
        <div class="bg-slate-50 p-4 border-b border-slate-100 font-bold text-slate-600">Preview Data Penjualan di Website</div>
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-white border-b-2 border-gray-100 text-gray-500 uppercase tracking-wider text-[11px]">
                <tr>
                    <th class="p-4 font-bold text-center w-12">No</th>
                    <th class="p-4 font-bold">Waktu</th>
                    <th class="p-4 font-bold">Pelanggan & Sales</th>
                    <th class="p-4 font-bold text-right">Total Uang</th>
                    <th class="p-4 font-bold text-center w-40">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $nomor = 1; @endphp
                @forelse($daftarTransaksi as $trx)
                    <tr class="{{ $isOwnerRole ? 'hover:bg-blue-50' : 'hover:bg-sage-light/20' }}">
                        <td class="p-4 text-center font-bold text-gray-400">{{ $nomor++ }}.</td>
                        <td class="p-4">
                            <p class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d/m/Y H:i') }}</p>
                            <p class="text-[10px] text-gray-400 font-mono mt-1">{{ $trx->kode_nota }}</p>
                            
                            <div class="flex flex-wrap gap-1 mt-1">
                                @if($trx->status_penjualan === 'DIRETUR')
                                    <span class="inline-flex items-center gap-0.5 bg-orange-50 text-orange-700 text-[10px] px-2 py-0.5 rounded font-bold uppercase border border-orange-200">
                                        <span class="material-symbols-outlined text-[12px]">undo</span> Ada Retur
                                    </span>
                                @endif
                                @if($trx->riwayat_koreksi_count > 0)
                                    <span class="inline-flex items-center gap-0.5 bg-amber-50 text-amber-700 text-[10px] px-2 py-0.5 rounded font-bold uppercase border border-amber-200">
                                        <span class="material-symbols-outlined text-[12px]">edit_note</span> Dikoreksi
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="p-4">
                            <p class="font-bold {{ $isOwnerRole ? 'text-blue-700' : 'text-sage-dark' }}">{{ $trx->pelanggan->nama ?? 'Umum' }}</p>
                            <p class="text-xs text-gray-500 mt-1">Sales: {{ $trx->marketing->nama ?? '-' }}</p>
                        </td>
                        <td class="p-4 text-right font-black text-green-700 text-base">
                            Rp {{ number_format($trx->total_harga, 0, ',', '.') }}
                        </td>
                        <td class="p-4 text-center">
                            <button wire:click="lihatDetail({{ $trx->id_transaksi_penjualan }})" class="px-3 py-1.5 rounded-lg font-bold text-xs transition-colors shadow-sm {{ $isOwnerRole ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-600 hover:text-white' : 'bg-sage-light text-sage-dark hover:bg-sage hover:text-white' }}">
                                Lihat Rincian
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-gray-400 font-bold">Tidak ada transaksi di periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- ==================================================================== -->
    <!-- MODAL POP-UP BACA DETAIL NOTA (SAMA DENGAN RIWAYAT TRANSAKSI) -->
    <!-- ==================================================================== -->
    @if($modal_open && $detail_nota)
        <x-nota-detail-modal
            :nota="$detail_nota"
            tipe="POS"
            :owner="false"
            close-action="tutupModal" />
    @endif
</div>