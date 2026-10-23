@props([
    'produk',                // App\Models\Produk (relasi kategori sebaiknya sudah di-eager load)
])

@php
    // Warna & label mengikuti Produk::status_stok (UNLIMITED | HABIS | MENIPIS | AMAN)
    $tampilan = match ($produk->status_stok) {
        'HABIS' => ['angka' => 'text-red-600', 'chip' => 'bg-red-50 text-red-700 border-red-200', 'label' => 'Habis'],
        'MENIPIS' => ['angka' => 'text-amber-600', 'chip' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Menipis'],
        'AMAN' => ['angka' => 'text-emerald-700', 'chip' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'Aman'],
        default => ['angka' => 'text-slate-400', 'chip' => 'bg-slate-100 text-slate-500 border-slate-200', 'label' => 'Tidak dilacak'],
    };
@endphp

<div {{ $attributes->class(['flex items-center gap-3 py-3']) }}>
    <div class="min-w-0 flex-1">
        <p class="font-semibold text-charcoal leading-snug">{{ $produk->nama_produk }}</p>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-xs text-slate-500">
            <x-kode-barang :kode="$produk->kode_barang" owner />
            @if($produk->lokasi)
                <span class="inline-flex items-center gap-0.5">
                    <span class="material-symbols-outlined text-[14px]">location_on</span>{{ $produk->lokasi }}
                </span>
            @endif
        </div>
    </div>
    <div class="text-right shrink-0">
        @if($produk->lacak_stok)
            <p class="font-headline text-lg font-bold leading-none {{ $tampilan['angka'] }}">
                {{ $produk->stok_display }} <span class="font-body text-xs font-semibold text-slate-500">{{ $produk->satuan }}</span>
            </p>
        @endif
        <span class="inline-block mt-1.5 px-2 py-0.5 rounded-full border text-[11px] font-bold {{ $tampilan['chip'] }}">{{ $tampilan['label'] }}</span>
        @if($produk->kategori->lacak_rol ?? false)
            <p class="text-[11px] font-bold text-indigo-600 mt-1">{{ $produk->stok_rol }} Rol</p>
        @endif
    </div>
</div>
