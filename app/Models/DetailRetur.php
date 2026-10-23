<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailRetur extends Model
{
    protected $table = 'detail_retur';
    protected $primaryKey = 'id_detail_retur';

    protected $fillable = [
        'id_retur',
        'id_produk_dikembalikan',
        'id_produk_pengganti',
        'jumlah',
        'kondisi_barang_dikembalikan',
        'subtotal_biaya',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'subtotal_biaya' => 'decimal:2',
    ];

    public function transaksiRetur(): BelongsTo
    {
        return $this->belongsTo(TransaksiRetur::class, 'id_retur', 'id_retur');
    }

    public function produkDikembalikan(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk_dikembalikan', 'id_produk');
    }

    public function produkPengganti(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk_pengganti', 'id_produk');
    }

    // Retur tanpa barang pengganti: barang dikembalikan, uang dikembalikan ke pelanggan
    public function isBatalBeli(): bool
    {
        return $this->id_produk_pengganti === null;
    }
}