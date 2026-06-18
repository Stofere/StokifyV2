<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatKoreksiNota extends Model
{
    protected $table = 'riwayat_koreksi_nota';
    protected $primaryKey = 'id_koreksi';

    protected $fillable = [
        'id_transaksi_penjualan',
        'id_detail_penjualan',
        'id_produk',
        'user_id',
        'qty_sebelum',
        'qty_sesudah',
        'potong_gudang_sebelum',
        'potong_gudang_sesudah',
        'harga_satuan',
        'subtotal_sebelum',
        'subtotal_sesudah',
        'alasan',
    ];

    protected $casts = [
        'qty_sebelum' => 'decimal:3',
        'qty_sesudah' => 'decimal:3',
        'potong_gudang_sebelum' => 'decimal:3',
        'potong_gudang_sesudah' => 'decimal:3',
        'harga_satuan' => 'decimal:2',
        'subtotal_sebelum' => 'decimal:2',
        'subtotal_sesudah' => 'decimal:2',
    ];

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function detailPenjualan(): BelongsTo
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan', 'id_detail_penjualan');
    }

    public function transaksiPenjualan(): BelongsTo
    {
        return $this->belongsTo(TransaksiPenjualan::class, 'id_transaksi_penjualan', 'id_transaksi_penjualan');
    }
}
