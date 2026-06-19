@php
    $isOwnerRole = Auth::user()->peran === 'OWNER';
@endphp

<div class="fade-in">

    {{-- ================================================================== --}}
    {{-- ADMIN DASHBOARD: Fantasy-Minimalist Frieren Theme                  --}}
    {{-- ================================================================== --}}
    @if(!$isOwner)

    @php
        $hour = \Carbon\Carbon::now('Asia/Jakarta')->hour;

        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Pagi';
            $mainText = 'Sudahkah kamu menginput penjualan hari ini?';
            $frierenJoke = 'Karena Pahlawan Himmel pasti udah ngegas dari subuh buat farming target harian~ 🌅☕';
        } 
        elseif ($hour >= 12 && $hour < 15) {
            $greeting = 'Siang';
            $mainText = 'Semangat terus ya, Master! Sudah makan siang?';
            $frierenJoke = 'Karena bahkan Himmel butuh recharge dulu biar ga burnout grinding sales~ 🍱✨';
        } 
        elseif ($hour >= 15 && $hour < 18) {
            $greeting = 'Sore';
            $mainText = 'Sudahkah kamu menginput penjualan hari ini?';
            $frierenJoke = 'Karena di sore hari Himmel malah makin ganas farming, jangan kalah semangatnya ya~ 🔥';
        } 
        else {
            $greeting = 'Malam';
            $mainText = 'Jangan lembur terlalu lama ya~ Istirahat yang cukup!';
            $frierenJoke = 'Karena Pahlawan Himmel juga tau kapan harus stop farming dan tidur, balance life dulu dong~ 🌙😴';
        }
    @endphp

    {{-- Konten Admin --}}
    <div class="px-4 md:px-8 pt-6 md:pt-8 space-y-6">

        {{-- Hero Section --}}
        <div class="relative overflow-hidden rounded-3xl border border-sage/15 bg-gradient-to-br from-sage-light/60 via-sage-bg to-white p-6 md:p-9">

            {{-- Decorative orbs --}}
            <div class="pointer-events-none absolute -top-12 -right-12 w-44 h-44 bg-sage/10 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-16 right-24 w-36 h-36 bg-sage-light/50 rounded-full blur-2xl"></div>

            <div class="relative flex flex-col md:flex-row items-center gap-6 md:gap-8 group">

                {{-- Anime Image Character --}}
                <div id="frieren-chibi" class="relative w-36 h-36 md:w-52 md:h-52 shrink-0 frieren-float order-last md:order-none cursor-pointer">
                    <img src="/images/chibi-frieren-open.png" alt="Frieren"
                        class="absolute inset-0 w-full h-full object-contain frieren-wave transition-transform duration-500 group-hover:scale-105 frieren-eye-open">
                    <img src="/images/chibi-frieren-blink.png" alt="Frieren Blink"
                        class="absolute inset-0 w-full h-full object-contain frieren-wave transition-transform duration-500 group-hover:scale-105 frieren-eye-closed opacity-0">
                </div>

                {{-- Welcome Text --}}
                <div class="flex-1 text-center md:text-left">
                    <p class="text-[11px] font-label font-bold uppercase tracking-[0.2em] text-sage">Dasbor Staff</p>
                    <h1 class="mt-2 font-headline text-[1.9rem] md:text-4xl font-bold leading-tight text-sage-dark">
                        Selamat {{ $greeting }}, {{ Auth::user()->name ?? 'Master' }}.
                    </h1>
                    <p class="mt-3 text-sm text-slate-500 leading-relaxed max-w-xl mx-auto md:mx-0">
                        {!! $mainText !!}<br>
                        <span class="italic text-sage">{{ $frierenJoke }}</span>
                    </p>
                    <a href="/pos" wire:navigate class="mt-5 inline-flex items-center gap-2 rounded-xl bg-sage-dark px-5 py-3 text-sm font-semibold text-white transition hover:bg-sage focus:outline-none focus:ring-4 focus:ring-sage/20">
                        <span class="material-symbols-outlined text-[18px]">point_of_sale</span>
                        Input Transaksi Baru
                    </a>
                </div>
            </div>
        </div>

        {{-- Bento Row 1: Metric + Activity --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- Nota Count Card --}}
            <a href="/transaksi/riwayat" wire:navigate class="group relative flex items-center gap-4 overflow-hidden rounded-2xl border border-slate-200/70 bg-white p-6 transition hover:border-sage/40 hover:shadow-[0_8px_30px_-12px_rgba(0,0,0,0.12)]">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-sage-light text-sage-dark transition group-hover:bg-sage group-hover:text-white">
                    <span class="material-symbols-outlined text-[26px]">receipt_long</span>
                </div>
                <div>
                    <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400">Total Nota Hari Ini</p>
                    <h3 class="font-headline text-3xl font-bold text-sage-dark mt-0.5">{{ $notaCountHari }}</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5 font-semibold">{{ $labelBulan }}</p>
                </div>
            </a>

            {{-- Chart Preview Card --}}
            <div class="md:col-span-2 rounded-2xl border border-slate-200/70 bg-white p-6" wire:ignore>
                <h4 class="font-label text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-4">Grafik Transaksi — {{ $labelBulan }}</h4>
                <div class="w-full h-48">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Bento Row 2: Activity Log --}}
        @include('livewire.partials.activity-log', ['accent' => 'sage'])

        {{-- Filter Global Waktu --}}
        <div class="bg-white rounded-2xl border border-slate-100 p-5 mb-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="font-headline font-bold text-charcoal">Filter Performa</h3>
                <p class="text-xs text-slate-500">Rentang waktu: <span class="font-semibold text-slate-700">{{ $filterText }}</span></p>
            </div>
            <div class="flex items-center gap-3">
                <input type="date" wire:model.live="startDate" class="text-sm border border-slate-200 rounded-lg px-3 py-2 outline-none text-slate-700 focus:ring-sage focus:border-sage">
                <span class="text-slate-400 text-sm">s/d</span>
                <input type="date" wire:model.live="endDate" class="text-sm border border-slate-200 rounded-lg px-3 py-2 outline-none text-slate-700 focus:ring-sage focus:border-sage">
            </div>
        </div>

        {{-- Top Performers Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Top Marketing --}}
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <h4 class="font-label text-[11px] font-bold uppercase tracking-widest px-5 pt-5 pb-3 text-slate-400 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-amber-500">emoji_events</span>
                    Performa Marketing ({{ $filterText }})
                </h4>
                <div class="divide-y divide-slate-50">
                    @forelse($topMarketing as $index => $mkt)
                        <div class="px-5 py-3 flex justify-between items-center hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-blue-pro/10 text-blue-pro flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</div>
                                <p class="font-semibold text-charcoal text-sm cursor-pointer hover:text-blue-600 hover:underline" wire:click="openMarketingModal('{{ $mkt->id_marketing }}', '{{ addslashes($mkt->marketing->nama) }}')">{{ $mkt->marketing->nama }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-headline font-bold text-blue-pro">{{ $mkt->total_nota }} Nota</p>
                                <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">Total Transaksi</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-slate-400 text-sm font-semibold">Belum ada data marketing.</div>
                    @endforelse
                </div>
            </div>

            {{-- Top Pelanggan --}}
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <h4 class="font-label text-[11px] font-bold uppercase tracking-widest px-5 pt-5 pb-3 text-slate-400 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-rose-400">favorite</span>
                    Pelanggan Paling Aktif ({{ $filterText }})
                </h4>
                <div class="divide-y divide-slate-50">
                    @forelse($topPelanggan as $index => $plg)
                        <div class="px-5 py-3 flex justify-between items-center hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</div>
                                <p class="font-semibold text-charcoal text-sm cursor-pointer hover:text-rose-500 hover:underline" wire:click="openCustomerModal('{{ $plg->id_pelanggan }}', '{{ addslashes($plg->pelanggan->nama) }}')">{{ $plg->pelanggan->nama }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-headline font-bold text-rose-500">{{ $plg->total_nota }} Nota</p>
                                <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">Total Transaksi</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-slate-400 text-sm font-semibold">Belum ada data pelanggan.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    @endif

    {{-- ================================================================== --}}
    {{-- OWNER DASHBOARD: Professional SaaS Analytical Theme                --}}
    {{-- ================================================================== --}}
    @if($isOwner)

    <div class="p-4 md:p-8 space-y-6">

        {{-- Title Row --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            <div>
                <h2 class="font-headline text-2xl md:text-3xl font-bold text-charcoal">Ringkasan Sistem ERP</h2>
                <p class="text-slate-500 text-sm mt-1">Dasbor manajemen — analisis data real-time.</p>
            </div>
        </div>

        {{-- Monitoring Gudang Row --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

            {{-- Card 1: Total SKU Aktif --}}
            <div class="bg-white rounded-2xl p-5 border border-slate-100 relative overflow-hidden group">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-pro rounded-r-full"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-blue-50 p-2.5 rounded-xl text-blue-pro">
                        <span class="material-symbols-outlined text-[22px]">inventory_2</span>
                    </div>
                </div>
                <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400">Total SKU Aktif</p>
                <h3 class="font-headline text-2xl font-bold text-charcoal mt-1">{{ number_format($totalSKU) }}</h3>
                <p class="text-[10px] text-slate-400 mt-0.5 font-semibold">Produk dilacak stoknya</p>
            </div>

            {{-- Card 2: Stok Menipis (Yellow) --}}
            <a href="{{ route('laporan.stok-menipis') }}" wire:navigate class="bg-white rounded-2xl p-5 border border-amber-200 relative overflow-hidden group hover:shadow-md hover:border-amber-300 transition-all cursor-pointer">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500 rounded-r-full"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-amber-50 p-2.5 rounded-xl text-amber-600">
                        <span class="material-symbols-outlined text-[22px]">warning</span>
                    </div>
                </div>
                <p class="text-[10px] font-label font-bold uppercase tracking-widest text-amber-600">Stok Menipis</p>
                <h3 class="font-headline text-2xl font-bold text-amber-600 mt-1">{{ number_format($stokMenipis) }}</h3>
                <p class="text-[10px] text-amber-500 mt-0.5 font-semibold">Mendekati batas minimum</p>
            </a>

            {{-- Card 3: Stok Habis (Red) --}}
            <a href="{{ route('laporan.stok-menipis') }}" wire:navigate class="bg-white rounded-2xl p-5 border border-red-200 relative overflow-hidden group hover:shadow-md hover:border-red-300 transition-all cursor-pointer">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500 rounded-r-full"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-red-50 p-2.5 rounded-xl text-red-600">
                        <span class="material-symbols-outlined text-[22px]">error</span>
                    </div>
                </div>
                <p class="text-[10px] font-label font-bold uppercase tracking-widest text-red-600">Stok Habis</p>
                <h3 class="font-headline text-2xl font-bold text-red-600 mt-1">{{ number_format($stokHabis) }}</h3>
                <p class="text-[10px] text-red-400 mt-0.5 font-semibold">Perlu segera restock</p>
            </a>

            {{-- Card 4: Nilai Inventaris (Owner Only) --}}
            @if($isOwner)
            <div class="bg-white rounded-2xl p-5 border border-slate-100 relative overflow-hidden group">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500 rounded-r-full"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-emerald-50 p-2.5 rounded-xl text-emerald-600">
                        <span class="material-symbols-outlined text-[22px]">account_balance</span>
                    </div>
                </div>
                <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400">Nilai Inventaris</p>
                <h3 class="font-headline text-lg font-bold text-charcoal mt-1">Rp {{ number_format($nilaiInventaris, 0, ',', '.') }}</h3>
                <p class="text-[10px] text-slate-400 mt-0.5 font-semibold">Total nilai jual di gudang</p>
            </div>
            @endif

        </div>

        {{-- KPI Row --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- Nota Count --}}
            <a href="/transaksi/riwayat" wire:navigate class="bg-white rounded-2xl p-5 flex items-center gap-4 hover:shadow-md transition-all group cursor-pointer relative overflow-hidden border border-slate-100">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-pro rounded-r-full"></div>
                <div class="bg-blue-50 p-3 rounded-xl text-blue-pro group-hover:bg-blue-pro group-hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400">Total Nota Bulan Ini</p>
                    <h3 class="font-headline text-3xl font-bold text-charcoal flex items-baseline gap-2">{{ $notaCount }} <span class="text-xs text-slate-400 font-body font-semibold">transaksi</span></h3>
                    <p class="text-[10px] text-slate-400 mt-0.5 font-semibold">{{ $labelBulan }}</p>
                </div>
            </a>

            {{-- Omset --}}
            <div class="bg-white rounded-2xl p-5 flex items-center gap-4 border border-slate-100 relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500 rounded-r-full"></div>
                <div class="bg-emerald-50 p-3 rounded-xl text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400">Omset Kotor Bulan Ini</p>
                    <h3 class="font-headline text-2xl font-bold text-charcoal mt-1">Rp {{ number_format($omsetBulanIni, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5 font-semibold">{{ $labelBulan }}</p>
                </div>
            </div>

            {{-- Retur --}}
            <div class="bg-white rounded-2xl p-5 flex items-center gap-4 border border-slate-100 relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500 rounded-r-full"></div>
                <div class="bg-amber-50 p-3 rounded-xl text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                </div>
                <div>
                    <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400">Selisih Retur Bulan Ini</p>
                    <h3 class="font-headline text-xl font-bold text-charcoal mt-1">Rp {{ number_format($returBulanIni, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5 font-semibold">(+) Pelanggan Nombok, (-) Toko Rugi</p>
                </div>
            </div>
        </div>

        {{-- Activity Log (full width) --}}
        @include('livewire.partials.activity-log', ['accent' => 'blue'])

        {{-- Filter Global Waktu --}}
        <div class="bg-white rounded-2xl border border-slate-100 p-5 mb-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="font-headline font-bold text-charcoal">Filter Performa</h3>
                <p class="text-xs text-slate-500">Rentang waktu: <span class="font-semibold text-slate-700">{{ $filterText }}</span></p>
            </div>
            <div class="flex items-center gap-3">
                <input type="date" wire:model.live="startDate" class="text-sm border border-slate-200 rounded-lg px-3 py-2 outline-none text-slate-700 focus:ring-blue-pro focus:border-blue-pro">
                <span class="text-slate-400 text-sm">s/d</span>
                <input type="date" wire:model.live="endDate" class="text-sm border border-slate-200 rounded-lg px-3 py-2 outline-none text-slate-700 focus:ring-blue-pro focus:border-blue-pro">
            </div>
        </div>

        {{-- Top Performers Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Top Marketing --}}
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <h4 class="font-label text-[11px] font-bold uppercase tracking-widest px-5 pt-5 pb-3 text-slate-400 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-amber-500">emoji_events</span>
                    Performa Marketing ({{ $filterText }})
                </h4>
                <div class="divide-y divide-slate-50">
                    @forelse($topMarketing as $index => $mkt)
                        <div class="px-5 py-3 flex justify-between items-center hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-blue-pro/10 text-blue-pro flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</div>
                                <p class="font-semibold text-charcoal text-sm cursor-pointer hover:text-blue-600 hover:underline" wire:click="openMarketingModal('{{ $mkt->id_marketing }}', '{{ addslashes($mkt->marketing->nama) }}')">{{ $mkt->marketing->nama }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-headline font-bold text-blue-pro">Rp {{ number_format($mkt->total_revenue, 0, ',', '.') }}</p>
                                <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">{{ $mkt->total_nota }} Nota Transaksi</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-slate-400 text-sm font-semibold">Belum ada data marketing.</div>
                    @endforelse
                </div>
            </div>

            {{-- Top Pelanggan --}}
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <h4 class="font-label text-[11px] font-bold uppercase tracking-widest px-5 pt-5 pb-3 text-slate-400 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-rose-400">favorite</span>
                    Pelanggan Paling Aktif ({{ $filterText }})
                </h4>
                <div class="divide-y divide-slate-50">
                    @forelse($topPelanggan as $index => $plg)
                        <div class="px-5 py-3 flex justify-between items-center hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</div>
                                <p class="font-semibold text-charcoal text-sm cursor-pointer hover:text-rose-500 hover:underline" wire:click="openCustomerModal('{{ $plg->id_pelanggan }}', '{{ addslashes($plg->pelanggan->nama) }}')">{{ $plg->pelanggan->nama }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-headline font-bold text-rose-500">Rp {{ number_format($plg->total_revenue, 0, ',', '.') }}</p>
                                <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">{{ $plg->total_nota }} Nota Transaksi</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-slate-400 text-sm font-semibold">Belum ada data pelanggan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================== --}}
    {{-- MODALS                                         --}}
    {{-- ============================================== --}}
    @if($isMarketingModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl mx-4 flex flex-col max-h-[85vh]">
                <div class="flex justify-between items-center px-6 py-4 border-b border-slate-100 bg-slate-50 shrink-0 rounded-t-2xl">
                    <div>
                        <h3 class="font-headline font-bold text-lg text-charcoal">Riwayat Transaksi Marketing</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $selectedMarketingName }} — {{ $filterText }}</p>
                    </div>
                    <button wire:click="closeMarketingModal" class="text-slate-400 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 pt-4 pb-2">
                    <table class="w-full text-left text-sm">
                        <thead class="sticky top-0 bg-white z-10">
                            <tr class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 border-b border-slate-200">
                                <th class="px-3 py-2.5">Tanggal</th>
                                <th class="px-3 py-2.5">Kode Nota</th>
                                <th class="px-3 py-2.5">Pelanggan</th>
                                @if($isOwner)
                                    <th class="px-3 py-2.5 text-right">Total Harga</th>
                                @endif
                                <th class="px-3 py-2.5 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($marketingDrilldownData as $trx)
                                <tr class="hover:bg-slate-50/50 transition-colors {{ $expandedTransaksiId == $trx->id_transaksi_penjualan ? 'bg-blue-50/50' : '' }}">
                                    <td class="px-3 py-2.5 font-semibold text-charcoal whitespace-nowrap">{{ $trx->tanggal_transaksi->translatedFormat('d M Y') }}</td>
                                    <td class="px-3 py-2.5 font-mono text-xs text-slate-600 uppercase tracking-wider">
                                        {{ $trx->kode_nota }}
                                        @if($trx->riwayat_koreksi_count > 0)
                                            <span class="inline-flex items-center gap-0.5 ml-1 bg-amber-50 text-amber-700 text-[9px] px-1.5 py-0.5 rounded font-bold uppercase border border-amber-200 align-middle">
                                                <span class="material-symbols-outlined text-[11px]">edit_note</span> Dikoreksi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-700">{{ $trx->pelanggan->nama ?? 'Walk-in' }}</td>
                                    @if($isOwner)
                                        <td class="px-3 py-2.5 text-right font-bold text-emerald-600">Rp {{ number_format($trx->total_harga, 0, ',', '.') }}</td>
                                    @endif
                                    <td class="px-3 py-2.5 text-center">
                                        <button wire:click="toggleTransaksiDetail({{ $trx->id_transaksi_penjualan }})" 
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all
                                                       {{ $expandedTransaksiId == $trx->id_transaksi_penjualan 
                                                          ? 'bg-blue-pro text-white' 
                                                          : 'bg-slate-100 text-slate-600 hover:bg-blue-50 hover:text-blue-pro' }}">
                                            <span class="material-symbols-outlined text-[14px]">{{ $expandedTransaksiId == $trx->id_transaksi_penjualan ? 'expand_less' : 'expand_more' }}</span>
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                                {{-- Expanded Detail Row --}}
                                @if($expandedTransaksiId == $trx->id_transaksi_penjualan && count($expandedTransaksiDetail) > 0)
                                    <tr>
                                        <td colspan="{{ $isOwner ? 5 : 4 }}" class="p-0">
                                            <div class="bg-slate-50 border-l-4 border-blue-pro px-5 py-3">
                                                <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 mb-2">Detail Barang pada Nota {{ $trx->kode_nota }}</p>
                                                <div class="space-y-1.5">
                                                    @foreach($expandedTransaksiDetail as $detail)
                                                        <div class="flex justify-between items-center text-xs bg-white rounded-lg px-3 py-2">
                                                            <div class="flex items-center gap-2">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-pro shrink-0"></span>
                                                                <span class="font-semibold text-charcoal">{{ $detail['produk']['nama_produk'] ?? '-' }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-4 text-slate-500">
                                                                <span>{{ fmod((float)$detail['jumlah'], 1) == 0 ? (int)$detail['jumlah'] : $detail['jumlah'] }} {{ $detail['satuan_saat_jual'] }}</span>
                                                                <span class="text-slate-300">×</span>
                                                                <span>Rp {{ number_format($detail['harga_satuan'], 0, ',', '.') }}</span>
                                                                <span class="font-bold text-charcoal">= Rp {{ number_format($detail['subtotal'], 0, ',', '.') }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ $isOwner ? 5 : 4 }}" class="text-center text-sm text-slate-400 py-8 font-semibold">Tidak ada transaksi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer Summary (pinned at bottom) --}}
                @if(count($marketingDrilldownData) > 0)
                    <div class="px-6 py-3 border-t border-slate-200 flex justify-between items-center shrink-0 bg-slate-50/50 rounded-b-2xl">
                        <p class="text-xs text-slate-500 font-semibold">Total: <span class="text-charcoal font-bold">{{ count($marketingDrilldownData) }} Nota</span></p>
                        @if($isOwner)
                            <p class="text-sm font-headline font-bold text-emerald-600">Rp {{ number_format($marketingDrilldownData->sum('total_harga'), 0, ',', '.') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($isCustomerModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl mx-4 overflow-hidden">
                <div class="flex justify-between items-center px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-headline font-bold text-lg text-charcoal">Drill-down Pelanggan</h3>
                    <button wire:click="closeCustomerModal" class="text-slate-400 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-500 mb-2">Profil belanja <span class="font-bold text-charcoal">{{ $selectedCustomerName }}</span> ({{ $filterText }})</p>
                    
                    <div class="mb-4 p-3 bg-blue-50 rounded-xl">
                        <p class="text-xs font-bold text-blue-800 uppercase mb-1">Dilayani Oleh Marketing:</p>
                        <p class="text-sm text-blue-900">
                            @if(count($customerDrilldownMarketing) > 0)
                                {{ implode(', ', $customerDrilldownMarketing) }}
                            @else
                                <span class="italic">Tidak ada / Tanpa Marketing</span>
                            @endif
                        </p>
                    </div>

                    <p class="text-xs font-bold text-slate-500 uppercase mb-2">Produk Paling Sering Dibeli:</p>
                    <div class="max-h-56 overflow-y-auto pr-2 divide-y divide-slate-100">
                        @forelse($customerDrilldownProducts as $idx => $prod)
                            <div class="py-3 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold">{{ $idx + 1 }}</div>
                                    <div>
                                        <p class="font-semibold text-charcoal text-sm">{{ $prod->produk->nama_produk }}</p>
                                        <p class="text-xs text-slate-500">{{ $prod->total_jumlah + 0 }} qty</p>
                                    </div>
                                </div>
                                @if($isOwner)
                                    <p class="text-sm font-bold text-emerald-600">Rp {{ number_format($prod->total_subtotal, 0, ',', '.') }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-center text-sm text-slate-400 py-4">Tidak ada data produk.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Drilldown Log Aktivitas (klik salah satu log) --}}
    @if($isLogModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity" wire:click.self="closeLogModal">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 flex flex-col max-h-[85vh]">
                <div class="flex justify-between items-center px-6 py-4 border-b border-slate-100 bg-slate-50 shrink-0 rounded-t-2xl">
                    <div>
                        <h3 class="font-headline font-bold text-lg text-charcoal">{{ $logModalTitle }}</h3>
                        @if(!empty($logDetailMeta['kode']))
                            <p class="text-xs text-slate-500 mt-0.5 font-mono uppercase tracking-wider">{{ $logDetailMeta['kode'] }}</p>
                        @endif
                    </div>
                    <button wire:click="closeLogModal" class="text-slate-400 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-5">

                    {{-- ===== NOTA PENJUALAN ===== --}}
                    @if($logModalType === 'PENJUALAN')
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Tanggal</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['tanggal'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Status</p>
                                <p class="font-semibold {{ $logDetailMeta['status'] === 'DIRETUR' ? 'text-amber-600' : ($logDetailMeta['status'] === 'DIBATALKAN' ? 'text-red-500' : 'text-emerald-600') }}">{{ $logDetailMeta['status'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Pelanggan</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['pelanggan'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Marketing</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['marketing'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Kasir</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['kasir'] }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 mb-2">Barang</p>
                            <div class="space-y-1.5">
                                @foreach($logDetailItems as $item)
                                    <div class="flex justify-between items-center gap-2 text-xs bg-slate-50 rounded-lg px-3 py-2">
                                        <span class="font-semibold text-charcoal min-w-0 truncate">{{ $item['nama'] }}</span>
                                        <div class="flex items-center gap-3 text-slate-500 shrink-0">
                                            <span>{{ $item['jumlah'] }} {{ $item['satuan'] }}</span>
                                            <span class="text-slate-300">×</span>
                                            <span>Rp {{ number_format($item['harga'], 0, ',', '.') }}</span>
                                            <span class="font-bold text-charcoal">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if($isOwner)
                            <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Total Nota</span>
                                <span class="font-headline font-bold text-lg text-emerald-600">Rp {{ number_format($logDetailMeta['total'], 0, ',', '.') }}</span>
                            </div>
                        @endif

                    {{-- ===== RETUR ===== --}}
                    @elseif($logModalType === 'RETUR')
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Nota Asal</p>
                                <p class="text-charcoal font-semibold font-mono uppercase">{{ $logDetailMeta['nota_asal'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Tanggal</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['tanggal'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Petugas</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['petugas'] }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 mb-2">Barang Diretur</p>
                            <div class="space-y-1.5">
                                @foreach($logDetailItems as $item)
                                    <div class="text-xs bg-slate-50 rounded-lg px-3 py-2">
                                        <div class="flex justify-between items-center gap-2">
                                            <span class="font-semibold text-charcoal min-w-0 truncate">{{ $item['dikembalikan'] }}</span>
                                            <span class="text-slate-500 shrink-0">{{ $item['jumlah'] }} pcs</span>
                                        </div>
                                        @if(!empty($item['pengganti']))
                                            <p class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[13px] text-blue-pro">swap_horiz</span>
                                                Diganti: <span class="font-semibold text-charcoal">{{ $item['pengganti'] }}</span>
                                            </p>
                                        @endif
                                        @if(!empty($item['kondisi']))
                                            <p class="text-[10px] text-slate-400 mt-0.5">Kondisi: {{ $item['kondisi'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if($isOwner)
                            <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Total Biaya Retur</span>
                                <span class="font-headline font-bold text-lg text-amber-600">Rp {{ number_format($logDetailMeta['total'], 0, ',', '.') }}</span>
                            </div>
                        @endif

                    {{-- ===== PENYESUAIAN STOK MANUAL / ROL ===== --}}
                    @else
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Jenis</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['tipe'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Produk</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['produk'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Petugas</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['petugas'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Waktu</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['tanggal'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-center gap-3 sm:gap-5 bg-slate-50 rounded-xl p-4">
                            <div class="text-center">
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Sebelum</p>
                                <p class="text-xl font-headline font-bold text-charcoal">{{ $logDetailMeta['sebelum'] }}</p>
                            </div>
                            <div class="flex flex-col items-center text-slate-400">
                                <span class="material-symbols-outlined">arrow_forward</span>
                                <span class="text-[10px] font-bold {{ $logDetailMeta['is_rol'] ? 'text-indigo-600' : 'text-slate-500' }}">{{ $logDetailMeta['jumlah'] }} {{ $logDetailMeta['is_rol'] ? 'Rol' : 'Qty' }}</span>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Sesudah</p>
                                <p class="text-xl font-headline font-bold text-charcoal">{{ $logDetailMeta['sesudah'] }}</p>
                            </div>
                        </div>

                        @if(!empty($logDetailMeta['keterangan']))
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold mb-1">Keterangan</p>
                                <p class="text-sm text-slate-600">{{ $logDetailMeta['keterangan'] }}</p>
                            </div>
                        @endif
                    @endif
                </div>

                @if(in_array($logModalType, ['PENJUALAN', 'RETUR']))
                    <div class="px-6 py-3 border-t border-slate-200 bg-slate-50/50 rounded-b-2xl flex justify-end shrink-0">
                        <a href="/transaksi/riwayat" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-pro hover:underline">
                            Buka di Riwayat Transaksi <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif


{{-- ============================================== --}}
{{-- ANIMATIONS & CHART SCRIPTS                     --}}
{{-- ============================================== --}}
<style>
    @keyframes floating { 0% { transform: translateY(0px); } 50% { transform: translateY(-8px); } 100% { transform: translateY(0px); } }
    .frieren-float { animation: floating 4s ease-in-out infinite; }
    @keyframes wave { 0% { transform: rotate(0deg); } 25% { transform: rotate(2deg); } 75% { transform: rotate(-2deg); } 100% { transform: rotate(0deg); } }
    .frieren-wave { animation: wave 6s ease-in-out infinite; }
    .frieren-eye-closed { opacity: 0; transition: opacity 0.1s ease-in-out; }
</style>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endassets

@script
<script>
    (() => {
        const ctx = document.getElementById('salesChart');
        if(!ctx) return;

        const labels = @json($chartLabels);
        const dataPoint = @json($chartData);
        const isOwner = {{ $isOwner ? 'true' : 'false' }};

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: dataPoint,
                    borderColor: isOwner ? '#1E3A8A' : '#84A59D',
                    backgroundColor: isOwner ? 'rgba(30, 58, 138, 0.08)' : 'rgba(132, 165, 157, 0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: isOwner ? '#1E3A8A' : '#84A59D',
                    pointBorderWidth: 1.5,
                    pointRadius: labels.length > 15 ? 2 : 4,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { grid: { display: false }, ticks: { maxRotation: 0, font: { size: 10 } } }
                }
            }
        });

        // Frieren blink
        const chibi = document.getElementById('frieren-chibi');
        if (chibi) {
            const openImg = chibi.querySelector('.frieren-eye-open');
            const closedImg = chibi.querySelector('.frieren-eye-closed');
            chibi.addEventListener('click', () => {
                closedImg.style.opacity = '1';
                setTimeout(() => { closedImg.style.opacity = '0'; }, 400);
            });
        }
    })();
</script>
@endscript

</div>
