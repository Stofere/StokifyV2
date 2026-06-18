<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdukGambar extends Model
{
    protected $table = 'produk_gambar';

    protected $primaryKey = 'id_gambar';

    protected $fillable = [
        'id_produk',
        'path',
        'path_thumbnail',
        'nama_asli',
        'urutan',
    ];

    protected $appends = ['url', 'url_thumbnail'];

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    // URL foto besar (untuk lightbox & download)
    public function getUrlAttribute(): string
    {
        return '/storage/'.$this->path;
    }

    // URL thumbnail (untuk tampilan katalog)
    public function getUrlThumbnailAttribute(): string
    {
        return '/storage/'.$this->path_thumbnail;
    }
}
