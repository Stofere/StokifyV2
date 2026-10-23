<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Retur "Batal Beli": barang dikembalikan tanpa barang pengganti → id_produk_pengganti NULL.
        // Hanya mengizinkan kolom kosong; data retur yang sudah ada tidak berubah.
        Schema::table('detail_retur', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produk_pengganti')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Data Batal Beli tidak punya barang pengganti, jadi kolom tidak bisa dikembalikan ke NOT NULL
        // tanpa merusaknya. Tolak rollback daripada mengubah/menghapus data tersebut.
        if (DB::table('detail_retur')->whereNull('id_produk_pengganti')->exists()) {
            throw new RuntimeException('Sudah ada data retur Batal Beli (tanpa barang pengganti). Rollback dibatalkan agar data tidak rusak.');
        }

        Schema::table('detail_retur', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produk_pengganti')->nullable(false)->change();
        });
    }
};
