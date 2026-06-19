@php
    // $accent: 'sage' (Admin) atau 'blue' (Owner) — hanya untuk warna aksen
    $isSage = ($accent ?? 'blue') === 'sage';
    $focusRing = $isSage ? 'focus:ring-sage focus:border-sage' : 'focus:ring-blue-pro focus:border-blue-pro';
    $dotNeutral = $isSage ? 'bg-sage' : 'bg-blue-400';
@endphp

<div class="rounded-2xl border border-slate-200/70 bg-white overflow-hidden">

    {{-- Header + Filter Tanggal Log --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 pt-5 pb-3 border-b border-slate-50">
        <div class="min-w-0">
            <h4 class="font-label text-[11px] font-bold uppercase tracking-widest text-slate-400">Log Aktivitas Sistem</h4>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Rentang: <span class="font-semibold text-slate-600">{{ $logFilterText }}</span></p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
            <input type="date" wire:model.live="logStartDate" class="flex-1 sm:flex-none min-w-0 text-xs border border-slate-200 rounded-lg px-2 py-1.5 outline-none text-slate-700 {{ $focusRing }}">
            <span class="text-slate-400 text-xs shrink-0">s/d</span>
            <input type="date" wire:model.live="logEndDate" class="flex-1 sm:flex-none min-w-0 text-xs border border-slate-200 rounded-lg px-2 py-1.5 outline-none text-slate-700 {{ $focusRing }}">
        </div>
    </div>

    <div class="px-4 pb-5 pt-3 max-h-[360px] overflow-y-auto">
        @if($aktivitasLog->isEmpty())
            <p class="text-center text-slate-400 text-sm font-semibold py-8">Belum ada aktivitas pada rentang ini.</p>
        @else
            <div class="space-y-4">
                @foreach($aktivitasLog as $tanggal => $logs)
                    <div>
                        <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 px-2 pb-1.5">
                            {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d M Y') }}
                        </p>
                        <div class="space-y-1">
                            @foreach($logs as $log)
                                <button type="button" wire:click="openLogDetail({{ $log->id_riwayat }})"
                                    class="group w-full flex gap-3 text-sm items-start text-left rounded-lg px-2 py-2 hover:bg-slate-50 transition-colors">
                                    <div class="w-2 h-2 mt-1.5 rounded-full shrink-0 {{ in_array($log->tipe, ['MASUK', 'KOREKSI_PLUS', 'ROL_MASUK']) ? 'bg-emerald-400' : (in_array($log->tipe, ['KELUAR', 'KOREKSI_MINUS', 'ROL_KELUAR']) ? 'bg-red-400' : $dotNeutral) }}"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-charcoal">{{ $log->user->name ?? 'Sistem' }}</p>
                                        <p class="text-slate-500 text-xs mt-0.5">
                                            {{ str_replace('_', ' ', $log->tipe) }}
                                            @if(in_array($log->tipe, ['ROL_MASUK', 'ROL_KELUAR']))
                                                <span class="font-bold text-indigo-600">{{ abs($log->rol_mutasi) }}</span> Rol
                                            @else
                                                <span class="font-bold text-slate-700">{{ abs($log->jumlah) }}</span> qty
                                            @endif
                                            pada <span class="font-semibold">{{ $log->produk->nama_produk ?? '-' }}</span>.
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5 truncate">{{ $log->created_at->translatedFormat('H:i') }} | {{ $log->keterangan }}</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[16px] text-slate-300 group-hover:text-slate-500 shrink-0 mt-1">chevron_right</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
