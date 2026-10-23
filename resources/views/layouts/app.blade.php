<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stokify POS V2 - Enterprise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Serif:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    {{-- Icon font dimuat non-blocking (axis dipersempit ke wght 400-700 untuk memperkecil unduhan) --}}
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"></noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

@php
    $isOwnerRole = Auth::check() && Auth::user()->peran === 'OWNER';
    $currentRoute = request()->path();
    $inisialUser = strtoupper(substr(Auth::user()->name ?? 'U', 0, 1));

    // Menu dikelompokkan sekali: dipakai sidebar (semua peran) & lembar "Menu" owner di layar kecil
    $navGroups = [
        ['text' => null, 'items' => [
            ['label' => 'Dashboard', 'icon' => 'dashboard', 'href' => '/dashboard', 'match' => 'dashboard'],
            ['label' => 'Kasir POS', 'icon' => 'point_of_sale', 'href' => '/pos', 'match' => 'pos', 'hideForOwner' => true],
        ]],
        ['text' => 'Transaksi', 'items' => [
            ['label' => 'Retur Barang', 'icon' => 'assignment_return', 'href' => '/retur', 'match' => 'retur'],
            ['label' => 'Riwayat Transaksi', 'icon' => 'history', 'href' => '/transaksi/riwayat', 'match' => 'transaksi'],
        ]],
        ['text' => 'Laporan & Analitik', 'items' => [
            ['label' => 'Lap. Penjualan', 'icon' => 'bar_chart', 'href' => '/laporan/penjualan', 'match' => 'laporan/penjualan'],
            ['label' => 'Lap. Katalog', 'icon' => 'pie_chart', 'href' => '/laporan/katalog', 'match' => 'laporan/katalog'],
            ['label' => 'Lap. Stok Menipis', 'icon' => 'notification_important', 'href' => '/laporan/stok-menipis', 'match' => 'laporan/stok-menipis'],
        ]],
        ['text' => 'Master Data', 'items' => [
            ['label' => 'Katalog Produk', 'icon' => 'inventory_2', 'href' => '/master/produk', 'match' => 'master/produk'],
            ['label' => 'Kategori', 'icon' => 'category', 'href' => '/master/kategori', 'match' => 'master/kategori'],
            ['label' => 'Atribut', 'icon' => 'tune', 'href' => '/master/atribut', 'match' => 'master/atribut'],
            ['label' => 'Pelanggan', 'icon' => 'group', 'href' => '/master/pelanggan', 'match' => 'master/pelanggan'],
            ['label' => 'Marketing', 'icon' => 'campaign', 'href' => '/master/marketing', 'match' => 'master/marketing'],
        ]],
        ['text' => 'Sistem', 'items' => [
            ['label' => 'Pengaturan', 'icon' => 'settings', 'href' => '/master/pengaturan', 'match' => 'master/pengaturan'],
        ]],
    ];

    // Owner tidak menginput penjualan: menu Kasir POS disembunyikan, halaman /pos tetap bisa dibuka lewat alamat.
    if ($isOwnerRole) {
        foreach ($navGroups as $i => $group) {
            $navGroups[$i]['items'] = array_values(array_filter($group['items'], fn ($item) => empty($item['hideForOwner'])));
        }
    }

    // Owner lebih sering memakai tablet & HP: sidebar baru muncul mulai lg (1024px = tablet tidur / PC),
    // di bawahnya bar atas + menu bawah. Admin tetap mulai md. Class ditulis utuh agar terbaca Tailwind.
    $tampilDesktop = $isOwnerRole ? 'hidden lg:flex' : 'hidden md:flex';
    $tampilMobile = $isOwnerRole ? 'lg:hidden' : 'md:hidden';
    $jarakMenuBawah = $isOwnerRole ? 'lg:pb-0 pb-24' : 'md:pb-0 pb-20';
    $geserKonten = $isOwnerRole ? ['lg:ml-64', 'lg:ml-20'] : ['md:ml-64', 'md:ml-20'];
@endphp

<body class="font-body antialiased min-h-screen {{ $isOwnerRole ? 'bg-slate-100' : 'bg-[#F8F9FA]' }}"
      x-data="{ sidebarOpen: $persist(true), mobileMenuOpen: false }"
      :class="{ 'overflow-hidden': mobileMenuOpen }">

    <aside class="{{ $tampilDesktop }} flex-col fixed left-0 top-0 h-screen z-40 sidebar-transition overflow-hidden
                  {{ $isOwnerRole ? 'bg-charcoal text-slate-300' : 'bg-sage-bg text-sage-dark' }}"
           :class="sidebarOpen ? 'w-64' : 'w-20'">

        <div class="px-5 py-6 flex items-center gap-3 shrink-0 {{ $isOwnerRole ? 'border-b border-slate-700' : 'border-b border-sage/20' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-headline font-bold text-lg
                        {{ $isOwnerRole ? 'bg-blue-pro text-white' : 'bg-sage text-white' }}">
                S2
            </div>
            <div x-show="sidebarOpen" x-transition.opacity class="overflow-hidden">
                <h1 class="font-headline text-lg font-bold leading-tight {{ $isOwnerRole ? 'text-white' : 'text-sage-dark' }}">Stokify</h1>
                <p class="text-[10px] font-label uppercase tracking-widest {{ $isOwnerRole ? 'text-slate-400' : 'text-sage' }}">
                    {{ $isOwnerRole ? 'Owner Mode' : 'Staff Mode' }}
                </p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @foreach($navGroups as $group)
                @if($group['text'])
                    <div class="pt-5 pb-2 px-3" x-show="sidebarOpen" x-transition.opacity>
                        <p class="text-[10px] font-label font-bold uppercase tracking-[0.15em] {{ $isOwnerRole ? 'text-slate-500' : 'text-sage/70' }}">
                            {{ $group['text'] }}
                        </p>
                    </div>
                @endif
                @foreach($group['items'] as $item)
                    @php $isActive = str_contains($currentRoute, $item['match']); @endphp
                    <a href="{{ $item['href'] }}" wire:navigate
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group relative
                              {{ $isActive
                                  ? ($isOwnerRole
                                      ? 'bg-slate-700/60 text-white'
                                      : 'bg-white/80 text-sage-dark shadow-sm')
                                  : ($isOwnerRole
                                      ? 'text-slate-400 hover:text-white hover:bg-slate-700/40'
                                      : 'text-slate-500 hover:text-sage-dark hover:bg-white/50')
                              }}">
                        @if($isActive)
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-5 rounded-r-full
                                        {{ $isOwnerRole ? 'bg-blue-400' : 'bg-gold' }}"></div>
                        @endif
                        <span class="material-symbols-outlined text-[20px] shrink-0 {{ $isActive ? ($isOwnerRole ? 'text-blue-400' : 'text-gold') : '' }}">{{ $item['icon'] }}</span>
                        <span x-show="sidebarOpen" x-transition.opacity class="text-sm font-semibold truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="px-3 py-4 space-y-1 shrink-0 {{ $isOwnerRole ? 'border-t border-slate-700' : 'border-t border-sage/20' }}">
            <button @click="sidebarOpen = !sidebarOpen"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all
                           {{ $isOwnerRole ? 'text-slate-400 hover:text-white hover:bg-slate-700/40' : 'text-slate-500 hover:text-sage-dark hover:bg-white/50' }}">
                <span class="material-symbols-outlined text-[20px] shrink-0 transition-transform duration-300"
                      :class="sidebarOpen ? '' : 'rotate-180'">chevron_left</span>
                <span x-show="sidebarOpen" x-transition.opacity class="text-sm font-semibold">Tutup Menu</span>
            </button>

            <form action="/logout" method="POST">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all
                               {{ $isOwnerRole ? 'text-red-400 hover:bg-red-500/10' : 'text-red-500 hover:bg-red-50' }}">
                    <span class="material-symbols-outlined text-[20px] shrink-0">logout</span>
                    <span x-show="sidebarOpen" x-transition.opacity class="text-sm font-semibold">Logout</span>
                </button>
            </form>
        </div>

        <div class="px-4 py-3 shrink-0 {{ $isOwnerRole ? 'bg-slate-800/50' : 'bg-sage/10' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                            {{ $isOwnerRole ? 'bg-blue-pro text-white' : 'bg-sage text-white' }}">
                    {{ $inisialUser }}
                </div>
                <div x-show="sidebarOpen" x-transition.opacity class="overflow-hidden">
                    <p class="text-xs font-bold truncate {{ $isOwnerRole ? 'text-white' : 'text-sage-dark' }}">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-[10px] uppercase tracking-wider {{ $isOwnerRole ? 'text-slate-500' : 'text-sage/70' }}">{{ Auth::user()->peran ?? 'ADMIN' }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-h-screen transition-all duration-300 {{ $jarakMenuBawah }}"
         :class="sidebarOpen ? '{{ $geserKonten[0] }}' : '{{ $geserKonten[1] }}'">

        <header class="{{ $tampilDesktop }} items-center justify-between px-8 h-14 shrink-0
                        {{ $isOwnerRole ? 'bg-white border-b border-slate-200' : 'bg-white/60 backdrop-blur-md border-b border-sage/10' }}">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] {{ $isOwnerRole ? 'text-slate-400' : 'text-sage' }}">location_on</span>
                <span class="text-xs font-label font-semibold {{ $isOwnerRole ? 'text-slate-500' : 'text-sage' }} uppercase tracking-wider">Sistem Internal Enterprise</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs font-label {{ $isOwnerRole ? 'text-slate-400 bg-slate-100 border border-slate-200' : 'text-sage bg-sage-light/50 border border-sage/20' }} px-3 py-1 rounded-full font-semibold">
                    {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d M Y') }}
                </span>
            </div>
        </header>

        <header class="{{ $tampilMobile }} flex items-center justify-between px-4 h-14 shrink-0
                        {{ $isOwnerRole ? 'bg-charcoal text-white' : 'bg-white border-b border-sage/10' }}">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-headline font-bold
                            {{ $isOwnerRole ? 'bg-blue-pro text-white' : 'bg-sage text-white' }}">S2</div>
                <span class="font-headline font-bold {{ $isOwnerRole ? 'text-white' : 'text-sage-dark' }}">Stokify</span>
                @if($isOwnerRole)
                    <span class="text-[10px] font-label font-bold uppercase tracking-widest text-slate-400">Owner</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if($isOwnerRole)
                    {{-- Avatar owner membuka lembar Menu (berisi semua halaman & Logout) --}}
                    <button type="button" @click="mobileMenuOpen = true" aria-label="Buka menu"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold bg-blue-pro text-white ring-2 ring-white/10">
                        {{ $inisialUser }}
                    </button>
                @else
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold bg-sage text-white">
                        {{ $inisialUser }}
                    </div>
                @endif
            </div>
        </header>

        <main class="flex-1 flex flex-col overflow-x-hidden overflow-y-auto relative">

            <div class="flex-1">
                {{ $slot }}
            </div>

            <footer class="mt-auto py-5 px-8 flex flex-col md:flex-row justify-between items-center gap-2 shrink-0 border-t
                           {{ $isOwnerRole ? 'border-slate-200 text-slate-400' : 'border-sage/10 text-sage/70' }}">
                <p class="text-[11px] font-label font-medium tracking-wide">
                    &copy; {{ date('Y') }} Stokify POS Enterprise.
                </p>
                <p class="text-[11px] font-label tracking-wide">
                    Engineered by <span class="font-bold {{ $isOwnerRole ? 'text-slate-500' : 'text-sage-dark' }}">Roger Jeremy</span>
                </p>
            </footer>

        </main>
    </div>

    @if($isOwnerRole)
        {{-- Menu bawah owner (HP & tablet berdiri). Kasir POS sengaja tidak ada; sisanya di tombol Menu. --}}
        @php
            $menuBawahOwner = [
                ['icon' => 'dashboard', 'href' => '/dashboard', 'match' => 'dashboard', 'label' => 'Beranda'],
                ['icon' => 'inventory_2', 'href' => '/master/produk', 'match' => 'master/produk', 'label' => 'Stok'],
                ['icon' => 'notification_important', 'href' => '/laporan/stok-menipis', 'match' => 'laporan/stok-menipis', 'label' => 'Menipis'],
                ['icon' => 'history', 'href' => '/transaksi/riwayat', 'match' => 'transaksi', 'label' => 'Riwayat'],
            ];
        @endphp
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-charcoal/95 backdrop-blur-xl border-t border-slate-700 rounded-t-2xl px-2 pt-2 pb-3">
            <div class="grid grid-cols-5 gap-1 max-w-2xl mx-auto">
                @foreach($menuBawahOwner as $mItem)
                    @php $mActive = str_contains($currentRoute, $mItem['match']); @endphp
                    <a href="{{ $mItem['href'] }}" wire:navigate
                       class="flex flex-col items-center justify-center gap-1 h-14 rounded-xl transition-colors
                              {{ $mActive ? 'bg-blue-600 text-white' : 'text-slate-300 hover:text-white active:bg-slate-700/60' }}">
                        <span class="material-symbols-outlined text-[22px]">{{ $mItem['icon'] }}</span>
                        <span class="text-[11px] font-semibold leading-none">{{ $mItem['label'] }}</span>
                    </a>
                @endforeach
                <button type="button" @click="mobileMenuOpen = true"
                        class="flex flex-col items-center justify-center gap-1 h-14 rounded-xl transition-colors"
                        :class="mobileMenuOpen ? 'bg-blue-600 text-white' : 'text-slate-300 hover:text-white'">
                    <span class="material-symbols-outlined text-[22px]">menu</span>
                    <span class="text-[11px] font-semibold leading-none">Menu</span>
                </button>
            </div>
        </nav>

        {{-- Lembar "Menu" owner: semua halaman (sama dengan sidebar) + Logout. Tertutup saat pindah halaman. --}}
        <div class="lg:hidden">
            <div x-show="mobileMenuOpen" x-cloak x-transition.opacity
                 class="fixed inset-0 z-[70] bg-black/50 backdrop-blur-sm" @click="mobileMenuOpen = false"></div>

            <div x-show="mobileMenuOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                 @keydown.escape.window="mobileMenuOpen = false"
                 role="dialog" aria-modal="true" aria-label="Menu"
                 class="fixed inset-x-0 bottom-0 z-[71] max-h-[90vh] overflow-y-auto overscroll-contain bg-white rounded-t-3xl shadow-2xl">

                <div class="sticky top-0 z-10 bg-white px-5 pt-3 pb-3 border-b border-slate-100">
                    <div class="mx-auto w-10 h-1.5 rounded-full bg-slate-300"></div>
                    <div class="flex items-center gap-3 mt-3 max-w-2xl mx-auto">
                        <div class="w-11 h-11 shrink-0 rounded-full bg-blue-pro text-white flex items-center justify-center font-bold">{{ $inisialUser }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-charcoal truncate">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ Auth::user()->peran }}</p>
                        </div>
                        <button type="button" @click="mobileMenuOpen = false" aria-label="Tutup menu"
                                class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center text-slate-500 hover:bg-slate-100">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>

                <div class="px-5 pt-4 pb-6 space-y-4 max-w-2xl mx-auto">
                    @foreach($navGroups as $group)
                        <div>
                            @if($group['text'])
                                <p class="text-[10px] font-label font-bold uppercase tracking-[0.15em] text-slate-400 mb-2">{{ $group['text'] }}</p>
                            @endif
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                @foreach($group['items'] as $item)
                                    @php $isActive = str_contains($currentRoute, $item['match']); @endphp
                                    <a href="{{ $item['href'] }}" wire:navigate @click="mobileMenuOpen = false"
                                       class="flex flex-col items-center justify-center gap-1 min-h-[64px] rounded-2xl px-2 py-2 text-center transition-colors
                                              {{ $isActive ? 'bg-blue-50 text-blue-pro ring-1 ring-blue-200' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 active:bg-slate-200' }}">
                                        <span class="material-symbols-outlined text-[24px]">{{ $item['icon'] }}</span>
                                        <span class="text-[11px] font-semibold leading-tight">{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <form action="/logout" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 rounded-2xl bg-red-50 text-red-600 font-bold py-3.5 hover:bg-red-100 transition-colors">
                            <span class="material-symbols-outlined text-[20px]">logout</span>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-2 pb-5 pt-2
                    bg-white/80 backdrop-blur-xl border-t border-sage/10 shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.05)]
                    rounded-t-2xl">
            @php
                $mobileNav = [
                    ['icon' => 'dashboard', 'href' => '/dashboard', 'match' => 'dashboard', 'label' => 'Home'],
                    ['icon' => 'point_of_sale', 'href' => '/pos', 'match' => 'pos', 'label' => 'POS'],
                    ['icon' => 'inventory_2', 'href' => '/master/produk', 'match' => 'master/produk', 'label' => 'Stok'],
                    ['icon' => 'assignment_return', 'href' => '/retur', 'match' => 'retur', 'label' => 'Retur'],
                    ['icon' => 'history', 'href' => '/transaksi/riwayat', 'match' => 'transaksi', 'label' => 'Riwayat'],
                ];
            @endphp
            @foreach($mobileNav as $mItem)
                @php $mActive = str_contains($currentRoute, $mItem['match']); @endphp
                <a href="{{ $mItem['href'] }}" wire:navigate
                   class="flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl transition-all
                          {{ $mActive ? 'text-sage-dark bg-sage/10' : 'text-slate-400' }}">
                    <span class="material-symbols-outlined text-[22px]">{{ $mItem['icon'] }}</span>
                    <span class="text-[9px] font-label font-bold uppercase tracking-wider">{{ $mItem['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    @livewireScripts
</body>
</html>
