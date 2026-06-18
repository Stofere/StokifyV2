<?php

namespace App\Services;

use App\Models\ProdukGambar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Memproses foto produk untuk "rak foto digital".
 *
 * Tiap foto disimpan dalam 2 versi:
 *  - BESAR (path)           : re-encode JPEG kualitas tinggi (q90), maks 2500px -> untuk lihat penuh & DOWNLOAD.
 *  - THUMBNAIL (path_thumbnail) : JPEG ringan (q75), maks 400px -> hanya untuk tampilan di katalog.
 *
 * Tujuan: download tetap tajam, tapi tampilan layar ringan (anti-lemot, hemat bandwidth).
 * Memakai driver GD (tersedia di server) + auto-orient EXIF agar foto HP tidak miring.
 */
class ImageService
{
    private const MAKS_BESAR = 2500;

    private const KUALITAS_BESAR = 90;

    private const MAKS_THUMB = 400;

    private const KUALITAS_THUMB = 75;

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Proses 1 file upload -> simpan versi besar + thumbnail ke disk public.
     *
     * @return array{path: string, path_thumbnail: string, nama_asli: string}
     */
    public function simpanGambarProduk(UploadedFile $file, int $idProduk): array
    {
        $folder = "produk-foto/{$idProduk}";
        $basename = Str::uuid()->toString();
        $pathBesar = "{$folder}/{$basename}.jpg";
        $pathThumb = "{$folder}/thumb_{$basename}.jpg";

        // Versi BESAR (download) - auto-orient, kecilkan jika > 2500px, encode q90
        $besar = $this->manager->decodePath($file->getRealPath())
            ->orient()
            ->scaleDown(self::MAKS_BESAR, self::MAKS_BESAR)
            ->encode(new JpegEncoder(quality: self::KUALITAS_BESAR, strip: true));
        Storage::disk('public')->put($pathBesar, (string) $besar);

        // Versi THUMBNAIL (tampilan) - maks 400px, encode q75
        $thumb = $this->manager->decodePath($file->getRealPath())
            ->orient()
            ->scaleDown(self::MAKS_THUMB, self::MAKS_THUMB)
            ->encode(new JpegEncoder(quality: self::KUALITAS_THUMB, strip: true));
        Storage::disk('public')->put($pathThumb, (string) $thumb);

        return [
            'path' => $pathBesar,
            'path_thumbnail' => $pathThumb,
            'nama_asli' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Hapus kedua file (besar + thumbnail) milik sebuah record gambar dari disk.
     */
    public function hapusFileGambar(ProdukGambar $gambar): void
    {
        Storage::disk('public')->delete([
            $gambar->path,
            $gambar->path_thumbnail,
        ]);
    }
}
