<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penampung foto produk ("rak foto digital").
     * Tiap produk boleh punya 1-3 foto, bersifat opsional.
     * Foto hanya dipakai di Katalog Produk (lihat & download), TIDAK di POS.
     */
    public function up(): void
    {
        Schema::create('produk_gambar', function (Blueprint $table) {
            $table->id('id_gambar');
            $table->unsignedBigInteger('id_produk');
            $table->string('path');               // versi download (q90, maks 2500px)
            $table->string('path_thumbnail');     // versi tampilan (q75, maks 400px)
            $table->string('nama_asli')->nullable(); // nama file asli untuk penamaan saat download
            $table->unsignedTinyInteger('urutan')->default(0); // urutan tampil 0-2
            $table->timestamps();

            $table->foreign('id_produk')->references('id_produk')->on('produk')->onDelete('cascade');
            $table->index('id_produk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_gambar');
    }
};
