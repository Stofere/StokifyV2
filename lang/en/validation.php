<?php

// Hanya menimpa pesan tertentu; pesan lainnya tetap memakai bawaan Laravel.
return [

    // Upload gagal tanpa detail (mis. terlalu sering mengunggah atau sesi login habis)
    'uploaded' => 'Foto gagal diunggah. Tunggu sebentar lalu coba lagi.',

    // Gerbang upload sementara Livewire (config/livewire.php) memvalidasi atribut "files.*"
    'custom' => [
        'files.*' => [
            'required' => 'Pilih foto yang akan diunggah.',
            'file' => 'File yang diunggah harus berupa gambar.',
            'max' => 'Ukuran tiap foto maksimal 5MB.',
            'mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'mimetypes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'extensions' => 'Format foto harus JPG, PNG, atau WEBP.',
        ],
    ],

];
