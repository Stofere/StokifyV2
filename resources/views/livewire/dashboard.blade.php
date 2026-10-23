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
    {{-- OWNER DASHBOARD: fokus stok, rapi di HP / tablet / PC              --}}
    {{-- ================================================================== --}}
    @if($isOwner)

    @php
        $jamOwner = \Carbon\Carbon::now('Asia/Jakarta')->hour;
        $sapaanOwner = match (true) {
            $jamOwner >= 5 && $jamOwner < 12 => 'Pagi',
            $jamOwner >= 12 && $jamOwner < 15 => 'Siang',
            $jamOwner >= 15 && $jamOwner < 18 => 'Sore',
            default => 'Malam',
        };
        $jumlahPerluRestock = $stokHabis + $stokMenipis;
    @endphp

    <div class="px-4 py-5 md:p-6 xl:p-8 space-y-5 md:space-y-6">

        {{-- Sapaan (tanggal hanya di layar kecil; di PC sudah ada di header) --}}
        <div>
            <p class="lg:hidden text-xs font-semibold text-slate-500">{{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}</p>
            <h2 class="font-headline text-2xl md:text-3xl font-bold text-charcoal mt-0.5">Selamat {{ $sapaanOwner }}, {{ Auth::user()->name }}</h2>
            <p class="text-sm text-slate-500 mt-1">Ringkasan stok & penjualan toko.</p>
        </div>

        {{-- Stok Gudang: 3 kartu jumlah barang + catatan kecil nilai stok --}}
        <section>
            <h3 class="font-label text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-3">Stok Gudang</h3>
            <div class="grid grid-cols-3 gap-3 md:gap-4">

                <a href="{{ route('laporan.stok-menipis') }}" wire:navigate
                   class="group relative overflow-hidden rounded-2xl border border-red-200 bg-white p-3 sm:p-4 transition hover:shadow-md hover:border-red-300 active:scale-[0.98]">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500 rounded-r-full"></div>
                    <div class="flex items-center justify-between">
                        <span class="material-symbols-outlined text-[22px] text-red-600">error</span>
                        <span class="material-symbols-outlined text-[18px] text-red-300 group-hover:text-red-500 transition-colors">chevron_right</span>
                    </div>
                    <p class="mt-2 text-[11px] sm:text-xs font-bold text-red-600">Stok Habis</p>
                    <p class="font-headline text-2xl sm:text-3xl font-bold text-red-600 leading-tight">{{ number_format($stokHabis, 0, ',', '.') }}</p>
                    <p class="hidden sm:block text-[11px] font-semibold text-red-400 mt-0.5">Perlu segera restock</p>
                </a>

                <a href="{{ route('laporan.stok-menipis') }}" wire:navigate
                   class="group relative overflow-hidden rounded-2xl border border-amber-200 bg-white p-3 sm:p-4 transition hover:shadow-md hover:border-amber-300 active:scale-[0.98]">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500 rounded-r-full"></div>
                    <div class="flex items-center justify-between">
                        <span class="material-symbols-outlined text-[22px] text-amber-600">warning</span>
                        <span class="material-symbols-outlined text-[18px] text-amber-300 group-hover:text-amber-500 transition-colors">chevron_right</span>
                    </div>
                    <p class="mt-2 text-[11px] sm:text-xs font-bold text-amber-600">Stok Menipis</p>
                    <p class="font-headline text-2xl sm:text-3xl font-bold text-amber-600 leading-tight">{{ number_format($stokMenipis, 0, ',', '.') }}</p>
                    <p class="hidden sm:block text-[11px] font-semibold text-amber-500 mt-0.5">Mendekati batas minimum</p>
                </a>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200/70 bg-white p-3 sm:p-4">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-pro rounded-r-full"></div>
                    <span class="material-symbols-outlined text-[22px] text-blue-pro">inventory_2</span>
                    <p class="mt-2 text-[11px] sm:text-xs font-bold text-slate-500">Barang Aktif</p>
                    <p class="font-headline text-2xl sm:text-3xl font-bold text-charcoal leading-tight">{{ number_format($totalSKU, 0, ',', '.') }}</p>
                    <p class="hidden sm:block text-[11px] font-semibold text-slate-400 mt-0.5">Stoknya dicatat sistem</p>
                </div>

            </div>
            {{-- Sebagian data stok masih contoh, jadi nilai stok hanya ditampilkan kecil sebagai perkiraan --}}
            <p class="mt-2 px-1 text-xs text-slate-400">
                Perkiraan nilai stok: <span class="font-semibold text-slate-500">Rp {{ number_format($nilaiInventaris, 0, ',', '.') }}</span>
            </p>
        </section>

        {{-- Omset kotor bulan ini: angka uang utama (lebih bisa dipegang daripada nilai stok) --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-200/70 bg-white p-4 sm:p-5">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500 rounded-r-full"></div>
            <div class="flex items-center gap-3 sm:gap-4">
                <div class="shrink-0 bg-emerald-50 p-2.5 sm:p-3 rounded-xl text-emerald-600">
                    <span class="material-symbols-outlined text-[22px] sm:text-[26px]">payments</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] sm:text-xs font-bold text-slate-500">Omset Kotor {{ $labelBulan }}</p>
                    <p class="font-headline text-2xl sm:text-3xl font-bold text-charcoal leading-tight">Rp {{ number_format($omsetBulanIni, 0, ',', '.') }}</p>
                    <p class="text-[11px] font-semibold text-slate-400 mt-0.5">Total nota bulan ini, belum termasuk selisih retur</p>
                </div>
            </div>
        </div>

        {{-- Cek Stok + Perlu Restock: bertumpuk di HP, berdampingan mulai tablet --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5 items-start">

            <livewire:cek-stok />

            <div class="bg-white rounded-2xl border border-slate-200/70 overflow-hidden">
                <div class="flex items-start justify-between gap-3 px-4 sm:px-5 pt-4 sm:pt-5">
                    <div class="min-w-0">
                        <h3 class="flex items-center gap-2 font-headline font-bold text-charcoal">
                            <span class="material-symbols-outlined text-[20px] text-amber-500">notification_important</span>
                            Perlu Restock
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Stok habis & menipis, yang habis paling atas.</p>
                    </div>
                    @if($jumlahPerluRestock > 0)
                        <span class="shrink-0 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-xs font-bold text-amber-700">{{ number_format($jumlahPerluRestock, 0, ',', '.') }} barang</span>
                    @endif
                </div>
                <div class="px-4 sm:px-5 mt-1 divide-y divide-slate-100">
                    @forelse($perluRestock as $prod)
                        <x-baris-stok :produk="$prod" />
                    @empty
                        <div class="py-8 text-center">
                            <span class="material-symbols-outlined text-4xl text-emerald-400">check_circle</span>
                            <p class="text-sm font-semibold text-slate-500 mt-1">Semua stok aman.</p>
                        </div>
                    @endforelse
                </div>
                @if($jumlahPerluRestock > 0)
                    <a href="{{ route('laporan.stok-menipis') }}" wire:navigate
                       class="flex items-center justify-center gap-1 border-t border-slate-100 px-4 py-3 text-sm font-bold text-blue-pro hover:bg-blue-50 transition-colors">
                        Lihat semua {{ number_format($jumlahPerluRestock, 0, ',', '.') }} barang
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Penjualan bulan ini (omset sudah tampil besar di atas): bertumpuk di HP, 2 kolom mulai tablet --}}
        <section>
            <h3 class="font-label text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-3">Penjualan {{ $labelBulan }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">

                <a href="/transaksi/riwayat" wire:navigate
                   class="group relative overflow-hidden rounded-2xl border border-slate-200/70 bg-white p-4 flex items-center gap-3 transition hover:shadow-md">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-pro rounded-r-full"></div>
                    <div class="shrink-0 bg-blue-50 p-2.5 rounded-xl text-blue-pro group-hover:bg-blue-pro group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[22px]">receipt_long</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-500">Nota bulan ini</p>
                        <p class="font-headline text-2xl font-bold text-charcoal leading-tight">{{ number_format($notaCount, 0, ',', '.') }}</p>
                        <p class="text-[11px] font-semibold text-slate-400">Hari ini: {{ number_format($notaCountHari, 0, ',', '.') }} nota</p>
                    </div>
                </a>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200/70 bg-white p-4 flex items-center gap-3">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500 rounded-r-full"></div>
                    <div class="shrink-0 bg-amber-50 p-2.5 rounded-xl text-amber-600">
                        <span class="material-symbols-outlined text-[22px]">currency_exchange</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-500">Selisih retur</p>
                        <p class="font-headline text-xl font-bold text-charcoal leading-tight">Rp {{ number_format($returBulanIni, 0, ',', '.') }}</p>
                        <p class="text-[11px] font-semibold text-slate-400">Plus: pelanggan tambah bayar · Minus: uang dikembalikan</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Activity Log (full width) --}}
        @include('livewire.partials.activity-log', ['accent' => 'blue'])

        {{-- Performa Marketing & Pelanggan --}}
        <section class="space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200/70 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-headline font-bold text-charcoal">Performa Marketing & Pelanggan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Periode: <span class="font-semibold text-slate-700">{{ $filterText }}</span></p>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="date" wire:model.live="startDate" class="flex-1 sm:flex-none min-w-0 text-sm border border-slate-200 rounded-lg px-3 py-2 outline-none text-slate-700 focus:ring-blue-pro focus:border-blue-pro">
                    <span class="text-slate-400 text-sm shrink-0">s/d</span>
                    <input type="date" wire:model.live="endDate" class="flex-1 sm:flex-none min-w-0 text-sm border border-slate-200 rounded-lg px-3 py-2 outline-none text-slate-700 focus:ring-blue-pro focus:border-blue-pro">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">

                {{-- Marketing Terbaik --}}
                <div class="bg-white rounded-2xl border border-slate-200/70 overflow-hidden">
                    <h4 class="font-label text-[11px] font-bold uppercase tracking-widest px-4 sm:px-5 pt-4 sm:pt-5 pb-2 text-slate-500 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-amber-500">emoji_events</span>
                        Marketing Terbaik
                    </h4>
                    <div class="divide-y divide-slate-50">
                        @forelse($topMarketing as $index => $mkt)
                            <button type="button" wire:click="openMarketingModal('{{ $mkt->id_marketing }}', '{{ addslashes($mkt->marketing->nama) }}')"
                                    class="w-full flex items-center gap-3 px-4 sm:px-5 py-3 text-left hover:bg-slate-50 active:bg-slate-100 transition-colors">
                                <div class="w-7 h-7 shrink-0 rounded-full bg-blue-pro/10 text-blue-pro flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</div>
                                <p class="flex-1 min-w-0 font-semibold text-charcoal text-sm truncate">{{ $mkt->marketing->nama }}</p>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-headline font-bold text-blue-pro">Rp {{ number_format($mkt->total_revenue, 0, ',', '.') }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold">{{ $mkt->total_nota }} nota</p>
                                </div>
                                <span class="material-symbols-outlined text-[18px] text-slate-300 shrink-0">chevron_right</span>
                            </button>
                        @empty
                            <div class="px-5 py-6 text-center text-slate-400 text-sm font-semibold">Belum ada data marketing.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Pelanggan Teraktif --}}
                <div class="bg-white rounded-2xl border border-slate-200/70 overflow-hidden">
                    <h4 class="font-label text-[11px] font-bold uppercase tracking-widest px-4 sm:px-5 pt-4 sm:pt-5 pb-2 text-slate-500 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-rose-400">favorite</span>
                        Pelanggan Teraktif
                    </h4>
                    <div class="divide-y divide-slate-50">
                        @forelse($topPelanggan as $index => $plg)
                            <button type="button" wire:click="openCustomerModal('{{ $plg->id_pelanggan }}', '{{ addslashes($plg->pelanggan->nama) }}')"
                                    class="w-full flex items-center gap-3 px-4 sm:px-5 py-3 text-left hover:bg-slate-50 active:bg-slate-100 transition-colors">
                                <div class="w-7 h-7 shrink-0 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</div>
                                <p class="flex-1 min-w-0 font-semibold text-charcoal text-sm truncate">{{ $plg->pelanggan->nama }}</p>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-headline font-bold text-rose-500">Rp {{ number_format($plg->total_revenue, 0, ',', '.') }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold">{{ $plg->total_nota }} nota</p>
                                </div>
                                <span class="material-symbols-outlined text-[18px] text-slate-300 shrink-0">chevron_right</span>
                            </button>
                        @empty
                            <div class="px-5 py-6 text-center text-slate-400 text-sm font-semibold">Belum ada data pelanggan.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
    @endif

    {{-- ============================================== --}}
    {{-- MODALS                                         --}}
    {{-- ============================================== --}}
    {{-- Modal dashboard memakai z-[60] agar di atas menu bawah HP (z-50); di HP tampil sebagai lembar dari bawah --}}
    @if($isMarketingModalOpen)
        <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity" wire:click.self="closeMarketingModal">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-3xl sm:mx-4 flex flex-col max-h-[90vh] sm:max-h-[85vh]">
                <div class="flex justify-between items-center gap-3 px-4 sm:px-6 py-4 border-b border-slate-100 bg-slate-50 shrink-0 rounded-t-2xl">
                    <div class="min-w-0">
                        <h3 class="font-headline font-bold text-lg text-charcoal">Riwayat Transaksi Marketing</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $selectedMarketingName }} — {{ $filterText }}</p>
                    </div>
                    <button wire:click="closeMarketingModal" class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto overscroll-contain divide-y divide-slate-100">
                    @forelse($this->marketingDrilldown as $trx)
                        @php $terbuka = $expandedTransaksiId == $trx->id_transaksi_penjualan; @endphp
                        <div wire:key="mkt-nota-{{ $trx->id_transaksi_penjualan }}" class="{{ $terbuka ? 'bg-blue-50/50' : '' }}">
                            {{-- Satu baris = satu nota; tap untuk membuka rincian barang --}}
                            <button type="button" wire:click="toggleTransaksiDetail({{ $trx->id_transaksi_penjualan }})"
                                    class="w-full flex items-center gap-3 px-4 sm:px-6 py-3 text-left hover:bg-slate-50/70 transition-colors">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-charcoal">
                                        {{ $trx->tanggal_transaksi->translatedFormat('d M Y, H:i') }}
                                        @if($trx->riwayat_koreksi_count > 0)
                                            <span class="inline-flex items-center gap-0.5 ml-1 bg-amber-50 text-amber-700 text-[9px] px-1.5 py-0.5 rounded font-bold uppercase border border-amber-200 align-middle">
                                                <span class="material-symbols-outlined text-[11px]">edit_note</span> Dikoreksi
                                            </span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-slate-500 truncate mt-0.5">{{ $trx->pelanggan->nama ?? 'Walk-in' }}</p>
                                </div>
                                @if($isOwner)
                                    <p class="shrink-0 text-sm font-bold text-emerald-600">Rp {{ number_format($trx->total_harga, 0, ',', '.') }}</p>
                                @endif
                                <span class="shrink-0 inline-flex items-center gap-0.5 px-2 py-1 rounded-lg text-[11px] font-bold transition-all {{ $terbuka ? 'bg-blue-pro text-white' : 'bg-slate-100 text-slate-600' }}">
                                    <span class="material-symbols-outlined text-[14px]">{{ $terbuka ? 'expand_less' : 'expand_more' }}</span>
                                    Detail
                                </span>
                            </button>

                            @if($terbuka && $this->expandedDetail->isNotEmpty())
                                <div class="mx-4 sm:mx-6 mb-3 bg-slate-50 border-l-4 border-blue-pro rounded-r-lg px-3 sm:px-4 py-3">
                                    <p class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400 mb-2">Detail Barang — {{ $trx->tanggal_transaksi->translatedFormat('d M Y, H:i') }}</p>
                                    <div class="space-y-1.5">
                                        @foreach($this->expandedDetail as $detail)
                                            {{-- HP: nama di baris pertama, jumlah × harga di baris kedua --}}
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-3 text-xs bg-white rounded-lg px-3 py-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <x-nomor-urut :no="$loop->iteration" size="sm" />
                                                    <span class="font-semibold text-charcoal truncate">{{ $detail->produk->nama_produk ?? '-' }}</span>
                                                </div>
                                                <div class="flex items-center justify-between sm:justify-end gap-3 pl-7 sm:pl-0 text-slate-500 shrink-0">
                                                    <span>{{ fmod((float) $detail->jumlah, 1) == 0 ? (int) $detail->jumlah : $detail->jumlah }} {{ $detail->satuan_saat_jual }} × Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}</span>
                                                    <span class="font-bold text-charcoal">= Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-center text-sm text-slate-400 py-8 font-semibold">Tidak ada transaksi pada periode ini.</p>
                    @endforelse
                </div>

                {{-- Footer Summary (pinned at bottom) --}}
                @if($this->marketingDrilldown->isNotEmpty())
                    <div class="px-4 sm:px-6 py-3 border-t border-slate-200 flex justify-between items-center shrink-0 bg-slate-50/50 sm:rounded-b-2xl">
                        <p class="text-xs text-slate-500 font-semibold">Total: <span class="text-charcoal font-bold">{{ $this->marketingDrilldown->count() }} Nota</span></p>
                        @if($isOwner)
                            <p class="text-sm font-headline font-bold text-emerald-600">Rp {{ number_format($this->marketingDrilldown->sum('total_harga'), 0, ',', '.') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($isCustomerModalOpen)
        <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity" wire:click.self="closeCustomerModal">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-xl sm:mx-4 flex flex-col max-h-[90vh] overflow-hidden">
                <div class="flex justify-between items-center px-4 sm:px-6 py-4 border-b border-slate-100 bg-slate-50 shrink-0">
                    <h3 class="font-headline font-bold text-lg text-charcoal">Drill-down Pelanggan</h3>
                    <button wire:click="closeCustomerModal" class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-4 sm:p-6 overflow-y-auto overscroll-contain">
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
                            <div class="py-3 flex justify-between items-center gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-6 h-6 shrink-0 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold">{{ $idx + 1 }}</div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-charcoal text-sm">{{ $prod->produk->nama_produk }}</p>
                                        <p class="text-xs text-slate-500">{{ $prod->total_jumlah + 0 }} qty</p>
                                    </div>
                                </div>
                                @if($isOwner)
                                    <p class="shrink-0 text-sm font-bold text-emerald-600">Rp {{ number_format($prod->total_subtotal, 0, ',', '.') }}</p>
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
        <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity" wire:click.self="closeLogModal">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-lg sm:mx-4 flex flex-col max-h-[90vh] sm:max-h-[85vh]">
                <div class="flex justify-between items-center gap-3 px-4 sm:px-6 py-4 border-b border-slate-100 bg-slate-50 shrink-0 rounded-t-2xl">
                    <div class="min-w-0">
                        <h3 class="font-headline font-bold text-lg text-charcoal">{{ $logModalTitle }}</h3>
                        @if(!empty($logDetailMeta['kode']))
                            <p class="text-xs text-slate-500 mt-0.5 font-mono uppercase tracking-wider">{{ $logDetailMeta['kode'] }}</p>
                        @endif
                    </div>
                    <button wire:click="closeLogModal" class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain p-4 sm:p-6 space-y-5">

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
                                    {{-- HP: nama di baris pertama, jumlah × harga di baris kedua --}}
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2 text-xs bg-slate-50 rounded-lg px-3 py-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <x-nomor-urut :no="$loop->iteration" size="sm" />
                                            <span class="font-semibold text-charcoal min-w-0 truncate">{{ $item['nama'] }}</span>
                                        </div>
                                        <div class="flex items-center justify-between sm:justify-end gap-3 pl-7 sm:pl-0 text-slate-500 shrink-0">
                                            <span>{{ $item['jumlah'] }} {{ $item['satuan'] }} × Rp {{ number_format($item['harga'], 0, ',', '.') }}</span>
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
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Transaksi Asal</p>
                                <p class="text-charcoal font-semibold">{{ $logDetailMeta['nota_asal'] }}</p>
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
                                            <div class="flex items-center gap-2 min-w-0">
                                                <x-nomor-urut :no="$loop->iteration" size="sm" />
                                                <span class="font-semibold text-charcoal min-w-0 truncate">{{ $item['dikembalikan'] }}</span>
                                            </div>
                                            <span class="text-slate-500 shrink-0">{{ $item['jumlah'] }} pcs</span>
                                        </div>
                                        @if(!empty($item['pengganti']))
                                            <p class="text-[11px] text-slate-500 mt-1 pl-7 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[13px] text-blue-pro">swap_horiz</span>
                                                Diganti: <span class="font-semibold text-charcoal">{{ $item['pengganti'] }}</span>
                                            </p>
                                        @else
                                            <p class="text-[11px] text-slate-500 mt-1 pl-7 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[13px] text-blue-pro">undo</span>
                                                Batal beli, tanpa pengganti
                                            </p>
                                        @endif
                                        @if(!empty($item['kondisi']))
                                            <p class="text-[10px] text-slate-400 mt-0.5 pl-7">Kondisi: {{ $item['kondisi'] }}</p>
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

                @if(in_array($logModalType, ['PENJUALAN', 'RETUR']) && $logLinkId)
                    <div class="px-4 sm:px-6 py-3 border-t border-slate-200 bg-slate-50/50 sm:rounded-b-2xl flex justify-end shrink-0">
                        <a href="{{ '/transaksi/riwayat?open=' . $logLinkId . '&tipe=' . $logLinkType }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-pro hover:underline">
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
