<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel mungkin sudah dibuat manual di sesi sebelumnya. Buat hanya bila belum ada.
        if (!Schema::hasTable('riwayat_koreksi_nota')) {
            Schema::create('riwayat_koreksi_nota', function (Blueprint $table) {
                $table->id('id_koreksi');
                $table->unsignedBigInteger('id_transaksi_penjualan');
                $table->unsignedBigInteger('id_detail_penjualan');
                $table->unsignedBigInteger('id_produk');
                $table->unsignedBigInteger('user_id'); // Pelaku koreksi

                $table->decimal('qty_sebelum', 15, 3);
                $table->decimal('qty_sesudah', 15, 3);

                // Khusus barang dual-unit (meter): KG fisik yang dipotong gudang
                $table->decimal('potong_gudang_sebelum', 15, 3)->nullable();
                $table->decimal('potong_gudang_sesudah', 15, 3)->nullable();

                $table->decimal('harga_satuan', 15, 2);
                $table->decimal('subtotal_sebelum', 15, 2);
                $table->decimal('subtotal_sesudah', 15, 2);

                $table->string('alasan', 255);
                $table->timestamps();

                $table->foreign('id_transaksi_penjualan')->references('id_transaksi_penjualan')->on('transaksi_penjualan')->onDelete('cascade');
                $table->foreign('id_detail_penjualan')->references('id_detail_penjualan')->on('detail_penjualan')->onDelete('cascade');
                $table->foreign('id_produk')->references('id_produk')->on('produk')->onDelete('restrict');
                $table->foreign('user_id')->references('id')->on('users');
            });

            return;
        }

        // Tabel sudah ada: pastikan kolom audit KG (dual-unit) tersedia.
        Schema::table('riwayat_koreksi_nota', function (Blueprint $table) {
            if (!Schema::hasColumn('riwayat_koreksi_nota', 'potong_gudang_sebelum')) {
                $table->decimal('potong_gudang_sebelum', 15, 3)->nullable()->after('qty_sesudah');
            }
            if (!Schema::hasColumn('riwayat_koreksi_nota', 'potong_gudang_sesudah')) {
                $table->decimal('potong_gudang_sesudah', 15, 3)->nullable()->after('potong_gudang_sebelum');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_koreksi_nota');
    }
};
