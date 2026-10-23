{{-- Kotak "Cek Stok Barang" di dashboard owner (komponen App\Livewire\CekStok) --}}
<div class="bg-white rounded-2xl border border-slate-200/70 p-4 sm:p-5">
    <label for="cek-stok-cari" class="flex items-center gap-2 font-headline font-bold text-charcoal">
        <span class="material-symbols-outlined text-[20px] text-blue-pro">inventory_2</span>
        Cek Stok Barang
    </label>
    <p class="text-xs text-slate-500 mt-0.5">Ketik nama atau kode barang, minimal 2 huruf.</p>

    <div class="relative mt-3">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-slate-400 pointer-events-none">search</span>
        <input id="cek-stok-cari" type="text" inputmode="search" enterkeyhint="search" autocomplete="off"
               wire:model.live.debounce.400ms="cari"
               placeholder="Contoh: woofer 12"
               class="w-full pl-10 pr-11 py-3 rounded-xl bg-slate-50 border border-slate-200 text-base text-charcoal outline-none focus:bg-white focus:border-blue-pro focus:ring-2 focus:ring-blue-pro/20">
        @if($cari !== '')
            <button type="button" wire:click="$set('cari', '')" aria-label="Hapus pencarian"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        @endif
    </div>

    @if($cukupPanjang)
        <div class="mt-1 divide-y divide-slate-100" wire:loading.class="opacity-50" wire:target="cari">
            @forelse($hasil as $prod)
                <x-baris-stok :produk="$prod" wire:key="cek-stok-{{ $prod->id_produk }}" />
            @empty
                <p class="py-6 text-center text-sm font-semibold text-slate-400">Barang tidak ditemukan.</p>
            @endforelse
        </div>
        @if($adaLagi)
            <p class="mt-2 text-xs text-slate-500">Hanya {{ \App\Livewire\CekStok::MAKS_HASIL }} barang pertama yang tampil. Ketik lebih lengkap untuk mempersempit.</p>
        @endif
    @endif
</div>
