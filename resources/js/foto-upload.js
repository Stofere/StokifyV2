// Siapkan foto sebelum diunggah ("smart resize").
//
// Hanya foto yang PASTI ditolak server (> 5MB atau > 16 megapiksel) yang diperkecil di browser,
// ke ukuran yang sama dengan versi besar yang disimpan server (sisi terpanjang 2500px).
// Foto lain diunggah apa adanya. Ini hanya kenyamanan: aturan di server tetap penentu akhir
// (lihat app/Support/AturanGambar.php).

const MAKS_BYTE = 5 * 1024 * 1024;
const MAKS_PIKSEL = 16_000_000;
const SISI_MAKS = 2500;
const TIPE_DITERIMA = ['image/jpeg', 'image/png', 'image/webp'];

function muatGambar(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        // <img> mengikuti orientasi EXIF, jadi hasil gambar di canvas sudah tegak
        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve(img);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Gambar tidak bisa dibaca'));
        };
        img.src = url;
    });
}

function keBlob(canvas, tipe, kualitas) {
    return new Promise((resolve) => canvas.toBlob(resolve, tipe, kualitas));
}

/**
 * @param {File} file
 * @param {{ pertahankanTransparansi?: boolean }} opsi  true -> PNG/WebP dijadikan WebP (alpha tetap)
 * @returns {Promise<File>} file asli, atau versi yang sudah diperkecil
 */
export async function siapkanFoto(file, { pertahankanTransparansi = false } = {}) {
    // Bukan jpg/png/webp: biarkan server yang menolak dengan pesan yang jelas
    if (!TIPE_DITERIMA.includes(file.type)) {
        return file;
    }

    let img;
    try {
        img = await muatGambar(file);
    } catch {
        return file;
    }

    const lebar = img.naturalWidth;
    const tinggi = img.naturalHeight;
    if (file.size <= MAKS_BYTE && lebar * tinggi <= MAKS_PIKSEL) {
        return file; // tidak diubah sama sekali
    }

    const skala = Math.min(1, SISI_MAKS / Math.max(lebar, tinggi));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(lebar * skala);
    canvas.height = Math.round(tinggi * skala);

    const keWebp = pertahankanTransparansi && file.type !== 'image/jpeg';
    const tipe = keWebp ? 'image/webp' : 'image/jpeg';

    const ctx = canvas.getContext('2d');
    if (!keWebp) {
        // JPEG tidak punya transparansi: pakai latar putih, sama seperti hasil encode di server
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    const blob = await keBlob(canvas, tipe, keWebp ? 0.92 : 0.95);
    // Browser tidak bisa membuat format ini (mis. WebP di Safari lama): biarkan server memutuskan
    if (!blob || blob.type !== tipe) {
        return file;
    }

    const namaDasar = file.name.replace(/\.[^.]+$/, '') || 'foto';

    return new File([blob], `${namaDasar}.${keWebp ? 'webp' : 'jpg'}`, { type: tipe, lastModified: Date.now() });
}
