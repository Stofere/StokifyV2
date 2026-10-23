<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id('id_produk');
            $table->unsignedBigInteger('id_kategori');
            $table->string('kode_barang', 255)->nullable()->unique();
            $table->string('nama_produk', 255)->unique();
            $table->string('satuan', 50);
            $table->decimal('harga_jual_satuan', 15, 2)->default(0.00);
            $table->boolean('lacak_stok')->default(true);
            $table->decimal('stok_saat_ini', 15, 3)->default(0.000);
            $table->json('metadata')->nullable();
            $table->text('index_pencarian');
            $table->string('lokasi', 255)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->integer('stok_rol')->default(0); // Penanda manual: sisa berapa Rol fisik (khusus kategori Kabel)
            $table->timestamps();

            // Sesuai aturan arsitektur: RESTRICT, produk tidak boleh dihapus jika sudah berelasi
            $table->foreign('id_kategori')->references('id_kategori')->on('kategori')->onDelete('restrict');
            
            // Fulltext Indexing untuk pencarian No-Lag di POS.
            // SQLite (database test in-memory) tidak mendukung fulltext, jadi dilewati di sana.
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText('index_pencarian', 'idx_pencarian');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};