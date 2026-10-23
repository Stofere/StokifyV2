<?php

namespace App\Support;

use App\Rules\MaksimalPiksel;

/**
 * Centralized validation rules for all image uploads (product photos & POS backgrounds)
 * 
 * Multi-layered: validates file content (mimes + mimetypes via finfo), uploader extension,
 * file size, and pixel resolution. SVG, GIF, and BMP are intentionally blocked.
 * Livewire's temporary upload handler (config/livewire.php) shares these base rules.
 */
class AturanGambar
{
    public const MAKS_KB = 5120; // 5 MB

    public const MAKS_PIKSEL = 16_000_000; // 16 megapiksel

    /**
     * Returns base rules as plain strings, safe for use in cached config files.
     */
    public static function aturanDasar(): array
    {
        return [
            'file',
            'max:'.self::MAKS_KB,
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'extensions:jpg,jpeg,png,webp',
        ];
    }

    /**
     * Returns complete validation rules for component use (including resolution limit).
     */
    public static function rules(): array
    {
        return ['bail', ...self::aturanDasar(), new MaksimalPiksel(self::MAKS_PIKSEL)];
    }

    /**
     * Returns friendly error messages for specific attributes
     */
    public static function messages(string $atribut): array
    {
        $formatSalah = 'Format foto harus JPG, PNG, atau WEBP.';

        return [
            "{$atribut}.file" => 'File yang diunggah harus berupa gambar.',
            "{$atribut}.max" => 'Ukuran tiap foto maksimal 5MB.',
            "{$atribut}.mimes" => $formatSalah,
            "{$atribut}.mimetypes" => $formatSalah,
            "{$atribut}.extensions" => $formatSalah,
        ];
    }
}
