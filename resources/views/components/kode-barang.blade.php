@props([
    'kode' => null,          // Produk::kode_barang (nullable di master produk)
    'owner' => false,        // true = tema OWNER (biru), false = tema KASIR (sage)
    'tone' => null,          // override warna chip untuk latar berwarna, cth: 'bg-red-600'
])

@php
    $toneClass = $tone ?: ($owner ? 'bg-blue-pro' : 'bg-sage-dark');
@endphp

@if(!empty($kode))
    <span class="inline-block align-middle px-1.5 py-0.5 rounded-md font-mono text-[10px] font-bold tracking-wider text-white whitespace-nowrap {{ $toneClass }}">[{{ strtoupper($kode) }}]</span>
@endif
