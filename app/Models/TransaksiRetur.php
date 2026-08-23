<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransaksiRetur extends Model
{
    protected $table = 'transaksi_retur';
    protected $primaryKey = 'id_retur';

    protected $fillable = [
        'kode_retur',
        'id_transaksi_penjualan',
        'user_id',
        'tanggal_retur',
        'total_biaya_retur',
        'catatan',
    ];

    protected $casts = [
        'tanggal_retur' => 'datetime',
        'total_biaya_retur' => 'decimal:2',
    ];

    public function transaksiPenjualan(): BelongsTo
    {
        return $this->belongsTo(TransaksiPenjualan::class, 'id_transaksi_penjualan', 'id_transaksi_penjualan');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function detailRetur(): HasMany
    {
        // Urutan wajib deterministik — lihat catatan di TransaksiPenjualan::detailPenjualan().
        return $this->hasMany(DetailRetur::class, 'id_retur', 'id_retur')
            ->orderBy('id_detail_retur');
    }
}