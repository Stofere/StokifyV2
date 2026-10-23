<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Produk extends Model
{
    protected $table = 'produk';

    protected $primaryKey = 'id_produk';

    protected $fillable = [
        'id_kategori',
        'kode_barang',
        'nama_produk',
        'satuan',
        'harga_jual_satuan',
        'lacak_stok',
        'stok_saat_ini',
        'metadata',
        'index_pencarian',
        'lokasi',
        'status_aktif',
        'stok_rol',
    ];

    protected $casts = [
        'metadata' => 'array',
        'lacak_stok' => 'boolean',
        'status_aktif' => 'boolean',
        'harga_jual_satuan' => 'decimal:2',
        'stok_saat_ini' => 'decimal:3',
        'stok_rol' => 'integer',
    ];

    // Accessor: Membersihkan .00 jika satuan adalah pcs/unit
    protected function stokDisplay(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $stok = $attributes['stok_saat_ini'];
                $satuan = strtolower($attributes['satuan'] ?? '');

                // Jika satuannya PCS, buang desimalnya jadi integer
                if (in_array($satuan, ['pcs', 'unit', 'buah', 'biji'])) {
                    return (int) $stok;
                }

                // Jika meter/kg, biarkan desimal (Hapus angka 0 di belakang jika tidak perlu)
                return (float) $stok;
            }
        );
    }

    // Accessor: Batas minimum stok otomatis berdasarkan satuan
    public function getStokMinimumAttribute(): int
    {
        $satuan = strtolower($this->satuan ?? '');

        if (in_array($satuan, ['pcs', 'biji', 'unit', 'buah'])) {
            return 20;
        }

        return 1;
    }

    // Accessor: Status stok otomatis (UNLIMITED, HABIS, MENIPIS, AMAN)
    public function getStatusStokAttribute(): string
    {
        if (! $this->lacak_stok) {
            return 'UNLIMITED';
        }

        if ($this->stok_saat_ini <= 0) {
            return 'HABIS';
        }

        if ($this->stok_saat_ini > 0 && $this->stok_saat_ini <= $this->stok_minimum) {
            return 'MENIPIS';
        }

        return 'AMAN';
    }

    // Scope: barang aktif berstatus HABIS / MENIPIS (batas sama dengan getStokMinimumAttribute),
    // urut HABIS dulu lalu kode barang. Dipakai Laporan Stok Menipis & pratinjau dashboard owner.
    public function scopePerluRestock(Builder $query): Builder
    {
        return $query->where('status_aktif', true)
            ->where('lacak_stok', true)
            ->where(function ($query) {
                // HABIS: stok <= 0
                $query->where('stok_saat_ini', '<=', 0)
                    // MENIPIS: stok > 0 tapi di bawah batas minimum
                    ->orWhere(function ($q) {
                        $q->where('stok_saat_ini', '>', 0)
                          ->where(function ($inner) {
                              $inner->where(function ($sub) {
                                  $sub->whereIn(DB::raw('LOWER(satuan)'), ['pcs', 'biji', 'unit', 'buah'])
                                      ->where('stok_saat_ini', '<=', 20);
                              })->orWhere(function ($sub) {
                                  $sub->whereNotIn(DB::raw('LOWER(satuan)'), ['pcs', 'biji', 'unit', 'buah'])
                                      ->where('stok_saat_ini', '<=', 1);
                              });
                          });
                    });
            })
            ->orderByRaw('CASE WHEN stok_saat_ini <= 0 THEN 0 ELSE 1 END ASC')
            ->orderBy('kode_barang', 'ASC');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function riwayatStok(): HasMany
    {
        return $this->hasMany(RiwayatStok::class, 'id_produk', 'id_produk');
    }

    // Foto produk ("rak foto digital") - opsional 1-3 foto, hanya untuk katalog
    public function gambar(): HasMany
    {
        return $this->hasMany(ProdukGambar::class, 'id_produk', 'id_produk')->orderBy('urutan');
    }
}
