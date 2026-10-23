<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Rejects images that exceed a maximum resolution (width x height)
 * 
 * This prevents "decompression bombs" tiny files that consume massive amounts
 * of RAM when decoded, crashing PHP. It only reads the image header, avoiding a full file decode.
 * 
 */
class MaksimalPiksel implements ValidationRule
{
    public function __construct(private int $maksPiksel) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('File yang diunggah harus berupa gambar.');

            return;
        }

        // Livewire's TemporaryUploadFile has dimension(), standard UploadedFile uses getimagesize()
        $ukuran = method_exists($value, 'dimensions')
            ? $value->dimensions()
            : @getimagesize($value->getRealPath());

        if (! $ukuran) {
            $fail('File gambar rusak atau tidak bisa dibaca.');

            return;
        }

        if ($this->maksPiksel < $ukuran[0] * $ukuran[1]) {
            $fail('Resolusi foto terlalu besar (maksimal '.round($this->maksPiksel / 1_000_000).' megapiksel).');
        }
    }
}
