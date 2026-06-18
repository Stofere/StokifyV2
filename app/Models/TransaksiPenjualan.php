<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransaksiPenjualan extends Model
{
    protected $table = 'transaksi_penjualan';
    protected $primaryKey = 'id_transaksi_penjualan';

    protected $fillable = [
        'kode_nota',
        'user_id',
        'id_pelanggan',
        'id_marketing',
        'total_harga',
        'status_penjualan',
        'tanggal_transaksi',
        'catatan',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'datetime',
        'total_harga' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function marketing(): BelongsTo
    {
        return $this->belongsTo(Marketing::class, 'id_marketing', 'id_marketing');
    }

    public function detailPenjualan(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class, 'id_transaksi_penjualan', 'id_transaksi_penjualan');
    }

    public function transaksiRetur(): HasMany
    {
        return $this->hasMany(TransaksiRetur::class, 'id_transaksi_penjualan', 'id_transaksi_penjualan');
    }

    public function riwayatKoreksi(): HasMany
    {
        return $this->hasMany(RiwayatKoreksiNota::class, 'id_transaksi_penjualan', 'id_transaksi_penjualan');
    }
}