#!/bin/bash
#
# Jalankan sebagai user situs, BUKAN root:
#   sudo su stokifywsm
#   bash ~/htdocs/stokifywsm.web.id/deploy/update.sh
#
# Kenapa bukan root: folder ini bisa ditulis oleh PHP (user stokifywsm). Script yang dijalankan
# root dari folder ini bisa diubah oleh siapa pun yang berhasil menjalankan kode lewat PHP,
# lalu ikut berjalan sebagai root saat update berikutnya. Semua langkah di bawah tidak butuh root.

set -euo pipefail

if [ "$(id -u)" -eq 0 ]; then
    echo "Jangan jalankan sebagai root. Masuk dulu: sudo su stokifywsm"
    exit 1
fi

# Selalu bekerja di folder aplikasi, dari mana pun script ini dipanggil
cd "$(dirname "$0")/.."

echo "Memulai proses update dari GitHub..."

# 1. Tarik kode terbaru. Berhenti di sini jika gagal (mis. ada file lokal yang bentrok).
git pull --ff-only origin main

# 2. Pasang paket PHP persis sesuai composer.lock (wajib, termasuk patch keamanan paket)
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Perbarui salinan JS Livewire di public/vendor/livewire
#    (Nginx CloudPanel menyajikan file .js langsung dari folder public)
php artisan vendor:publish --tag=livewire:assets --force

# 4. Bersihkan cache Laravel (config, route, view yang sudah dikompilasi, dll.)
php artisan optimize:clear

# 5. Folder yang ditulis aplikasi harus bisa ditulis user & grup situs
chmod -R u+rwX,g+rwX storage bootstrap/cache

echo "Update selesai! Web sudah versi terbaru."
