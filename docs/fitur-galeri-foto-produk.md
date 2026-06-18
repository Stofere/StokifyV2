# Fitur Galeri Foto Produk ("Rak Foto Digital")

Fitur untuk menyimpan foto tiap produk sebagai arsip/rak digital. Saat pembeli minta foto barang, user tinggal cari produknya lalu **download** fotonya — tidak perlu berburu file manual.

## Ringkasan

- **Opsional**, maksimal **3 foto** per produk.
- **Upload & hapus** dilakukan di form **Master Produk** (halaman "Katalog Barang & Stok"). OWNER & KASIR sama-sama boleh.
- **Tampil** di halaman Master Produk sebagai thumbnail kecil + badge "📷 N foto". Diklik → popup geser (◀ ▶ / panah keyboard / Esc) dengan tombol **Download Foto**.
- **Tidak** ditampilkan di halaman Kasir/POS.
- **Cetak PDF/Excel** pada Laporan Katalog **tidak** memuat foto (tetap seperti sebelumnya).

## Cara kerja penyimpanan (anti-lemot, kualitas terjaga)

Tiap foto disimpan dalam **2 versi** di disk `public` (`storage/app/public/produk-foto/{id_produk}/`):

| Versi | Ukuran | Kualitas | Dipakai untuk |
|---|---|---|---|
| Besar (`path`) | maks 2500px | JPEG q90 | dilihat penuh & **download** |
| Thumbnail (`path_thumbnail`) | maks 400px | JPEG q75 | tampilan thumbnail di list |

Foto otomatis diluruskan sesuai EXIF (foto HP tidak miring). Download selalu memakai versi besar dengan nama file asli, jadi kualitas kirim ke pembeli tetap tajam. Thumbnail + `loading="lazy"` membuat halaman tetap ringan.

## Komponen teknis

- **Migrasi/tabel:** `produk_gambar` (`id_gambar`, `id_produk` FK cascade, `path`, `path_thumbnail`, `nama_asli`, `urutan`).
- **Model:** `App\Models\ProdukGambar` (+ accessor `url`, `url_thumbnail`); relasi `Produk::gambar()` (hasMany, urut `urutan`).
- **Service:** `App\Services\ImageService` (paket `intervention/image` v4, driver GD) — resize + orient + encode, serta hapus file.
- **Livewire:** `App\Livewire\Master\ProdukIndex` (`WithFileUploads`, properti `foto_baru`, `daftarFotoExisting`, method `hapusFoto`, batas `MAKS_FOTO = 3`).
- **Route download:** `produk.foto.download` di `routes/web.php` (force-download via `Storage::download`, butuh login).
- **Validasi upload:** `image|mimes:jpeg,jpg,png,webp|max:15360` (15MB per foto).

---

## Yang harus dilakukan di VPS saat deploy

Jalankan dari root project di VPS:

```bash
git pull

# Pasang dependency baru (intervention/image). Wajib PHP 8.4.
composer install --no-dev --optimize-autoloader

# Buat tabel produk_gambar
php artisan migrate

# Symlink storage — Anda sudah membuatnya sebelumnya, jadi cukup pastikan masih ada.
# Jika /storage/... mengembalikan 404, jalankan ulang:
php artisan storage:link

# Bersihkan cache
php artisan optimize:clear
```

### Prasyarat ekstensi PHP di VPS

Pastikan ekstensi **gd** dan **exif** aktif (dipakai untuk resize foto):

```bash
php -m | grep -E "gd|exif"
```

Jika `gd` belum ada, pasang sesuai PHP di VPS, contoh:
`sudo apt install php8.4-gd php8.4-exif` lalu restart web server/PHP-FPM.

### Catatan aset frontend

Aset hasil build (`public/build/`) **ikut di-commit**, jadi setelah `git pull` tampilan sudah sesuai tanpa harus `npm run build` di VPS. Jika alur deploy Anda tetap mem-build sendiri, jalankan `npm install && npm run build` seperti biasa.

### Folder & izin tulis

Foto tersimpan di `storage/app/public/produk-foto/`. Pastikan folder `storage/` dapat ditulis oleh web server (umumnya sudah, sama seperti fitur upload background POS yang sudah berjalan).
