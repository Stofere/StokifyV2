@props([
    'no' => null,            // nomor urut (1-based). null / string kosong = tidak dirender
    'tone' => null,          // override warna untuk latar berwarna, cth: 'bg-red-100 text-red-600'
    'size' => 'md',          // 'sm' (20px, untuk list padat) | 'md' (24px, default)
])

@php
    // Netral, bukan tema per-peran: nomor urut itu penanda struktural, bukan semantik.
    // Warna default sama dengan daftar bernomor yang sudah ada di drilldown pelanggan.
    $toneClass = $tone ?: 'bg-slate-100 text-slate-500';
    $sizeClass = $size === 'sm' ? 'w-5 h-5 text-[10px]' : 'w-6 h-6 text-xs';
@endphp

@if($no !== null && $no !== '')
    <span {{ $attributes->class([
        'inline-flex items-center justify-center shrink-0 rounded-full font-bold tabular-nums leading-none',
        $sizeClass,
        $toneClass,
    ]) }}>{{ $no }}</span>
@endif
