<?php

namespace Tests\Feature;

use App\Models\ProdukGambar;
use App\Models\User;
use App\Services\ImageService;
use App\Support\AturanGambar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pengamanan upload gambar: gerbang upload sementara Livewire, aturan bersama,
 * encode ulang di ImageService, dan nama file download. Tidak membutuhkan database.
 */
class UploadKeamananTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Livewire memakai disk 'tmp-for-tests' untuk upload sementara saat testing
        Storage::fake('tmp-for-tests');
        Storage::fake('public');
    }

    // =========================================================================
    // GERBANG UPLOAD SEMENTARA LIVEWIRE (config/livewire.php)
    // =========================================================================

    #[DataProvider('gambarSah')]
    public function test_gerbang_upload_menerima_gambar_yang_sah(string $nama): void
    {
        $this->unggah(UploadedFile::fake()->image($nama, 800, 600))
            ->assertOk()
            ->assertJsonCount(1, 'paths');
    }

    public static function gambarSah(): array
    {
        return [['foto.jpg'], ['foto.jpeg'], ['foto.png'], ['foto.webp'], ['FOTO.JPG']];
    }

    public function test_gerbang_upload_menerima_gambar_asli_berdasarkan_isinya(): void
    {
        foreach (['foto.jpg' => 'imagejpeg', 'foto.png' => 'imagepng', 'foto.webp' => 'imagewebp'] as $nama => $encoder) {
            $gd = imagecreatetruecolor(40, 30);
            ob_start();
            $encoder($gd);

            $this->unggah($this->fileAsli($nama, ob_get_clean()))->assertOk();
        }
    }

    #[DataProvider('fileBerbahaya')]
    public function test_gerbang_upload_menolak_file_berbahaya(string $nama, string $isi): void
    {
        $this->unggah($this->fileAsli($nama, $isi))
            ->assertUnprocessable();

        $this->assertSame([], Storage::disk('tmp-for-tests')->allFiles());
    }

    public static function fileBerbahaya(): array
    {
        $php = "<?php system(\$_GET['c']); ?>";

        return [
            'php polos' => ['shell.php', $php],
            'phtml' => ['shell.phtml', $php],
            'polyglot GIF+PHP bernama .php' => ['shell.php', "GIF89a\x01\x00\x01\x00".$php],
            'polyglot GIF+PHP bernama .gif' => ['shell.gif', "GIF89a\x01\x00\x01\x00".$php],
            'polyglot JPEG+PHP bernama .php' => ['foto.php', self::jpeg(10, 10).$php],
            'PHP menyamar jadi .jpg' => ['foto.jpg', $php],
            'svg' => ['gambar.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'html' => ['halaman.html', '<html><script>alert(1)</script></html>'],
            '.htaccess' => ['.htaccess', 'AddType application/x-httpd-php .jpg'],
        ];
    }

    public function test_gerbang_upload_menolak_file_lebih_dari_5mb(): void
    {
        $this->unggah(UploadedFile::fake()->image('besar.jpg')->size(AturanGambar::MAKS_KB + 1))
            ->assertUnprocessable();
    }

    public function test_gerbang_upload_menolak_tamu_yang_belum_login(): void
    {
        $this->post($this->urlUpload(), ['files' => [UploadedFile::fake()->image('foto.jpg')]], ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }

    public function test_gerbang_upload_dibatasi_10_kali_per_menit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->unggah(UploadedFile::fake()->image('foto.jpg', 10, 10))->assertOk();
        }

        $this->unggah(UploadedFile::fake()->image('foto.jpg', 10, 10))->assertTooManyRequests();
    }

    // =========================================================================
    // ATURAN BERSAMA (App\Support\AturanGambar)
    // =========================================================================

    #[DataProvider('formatDitolak')]
    public function test_aturan_menolak_format_selain_jpg_png_webp(UploadedFile $file): void
    {
        $this->assertTrue($this->validasi($file)->fails());
    }

    public static function formatDitolak(): array
    {
        return [
            'svg' => [UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
            'gif' => [UploadedFile::fake()->image('a.gif')],
            'bmp' => [UploadedFile::fake()->image('a.bmp')],
        ];
    }

    public function test_aturan_menolak_php_yang_menyamar_jadi_jpg(): void
    {
        $this->assertTrue($this->validasi($this->fileAsli('foto.jpg', "<?php system(\$_GET['c']); ?>"))->fails());
    }

    public function test_aturan_menolak_bom_dekompresi_resolusi_raksasa(): void
    {
        // File kecil (< 1 KB) yang mengaku berukuran 20.000 x 20.000 piksel (400 MP)
        $bom = UploadedFile::fake()->createWithContent('bom.png', self::pngDenganUkuranPalsu(20000, 20000));

        $validator = $this->validasi($bom);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('megapiksel', $validator->errors()->first('foto'));
    }

    public function test_aturan_menerima_foto_hp_biasa(): void
    {
        // 12 MP (4032 x 3024), ukuran umum kamera HP
        $this->assertTrue($this->validasi(UploadedFile::fake()->image('hp.jpg', 4032, 3024))->passes());
    }

    // =========================================================================
    // IMAGE SERVICE (encode ulang)
    // =========================================================================

    public function test_foto_produk_diperkecil_ke_2500_dan_thumbnail_400(): void
    {
        $hasil = app(ImageService::class)->simpanGambarProduk(UploadedFile::fake()->image('foto.png', 3000, 2000), 7);

        $this->assertMatchesRegularExpression('#^produk-foto/7/[0-9a-f-]{36}\.jpg$#', $hasil['path']);
        $this->assertMatchesRegularExpression('#^produk-foto/7/thumb_[0-9a-f-]{36}\.jpg$#', $hasil['path_thumbnail']);
        $this->assertSame([2500, 1667, 'image/jpeg'], $this->ukuran($hasil['path']));
        $this->assertSame([400, 267, 'image/jpeg'], $this->ukuran($hasil['path_thumbnail']));
    }

    public function test_foto_produk_diluruskan_sesuai_exif(): void
    {
        // Foto landscape 300x100 dengan tag EXIF Orientation=6 (HP dipegang tegak)
        $file = UploadedFile::fake()->createWithContent('tegak.jpg', self::jpegDenganOrientasi(300, 100, 6));

        $hasil = app(ImageService::class)->simpanGambarProduk($file, 1);

        $this->assertSame([100, 300, 'image/jpeg'], $this->ukuran($hasil['path']));
        $this->assertSame([100, 300, 'image/jpeg'], $this->ukuran($hasil['path_thumbnail']));
    }

    public function test_kode_php_tersembunyi_terbuang_saat_encode_ulang(): void
    {
        $payload = "<?php system(\$_GET['c']); ?>";
        // Payload di dalam segmen komentar JPEG (tetap JPEG valid) + di belakang file
        $polyglot = self::sisipkanSegmen(self::jpeg(50, 50), "\xFF\xFE", $payload).$payload;
        $file = UploadedFile::fake()->createWithContent('polyglot.jpg', $polyglot);

        $produk = app(ImageService::class)->simpanGambarProduk($file, 1);
        $background = app(ImageService::class)->simpanBackgroundPos($file);

        foreach ([$produk['path'], $produk['path_thumbnail'], $background] as $path) {
            $this->assertStringNotContainsString('<?php', Storage::disk('public')->get($path));
        }
    }

    public function test_background_pos_jpeg_tetap_jpeg(): void
    {
        $path = app(ImageService::class)->simpanBackgroundPos(UploadedFile::fake()->image('bg.jpg', 3200, 1800));

        $this->assertMatchesRegularExpression('#^pos-backgrounds/[0-9a-f-]{36}\.jpg$#', $path);
        $this->assertSame([2500, 1406, 'image/jpeg'], $this->ukuran($path));
    }

    public function test_background_pos_png_transparan_menjadi_webp_dan_tetap_transparan(): void
    {
        $gd = imagecreatetruecolor(20, 20);
        imagealphablending($gd, false);
        imagesavealpha($gd, true);
        imagefill($gd, 0, 0, imagecolorallocatealpha($gd, 0, 0, 0, 127));
        ob_start();
        imagepng($gd);
        $png = ob_get_clean();

        $path = app(ImageService::class)->simpanBackgroundPos(UploadedFile::fake()->createWithContent('logo.png', $png));

        $this->assertMatchesRegularExpression('#^pos-backgrounds/[0-9a-f-]{36}\.webp$#', $path);
        $hasil = imagecreatefromstring(Storage::disk('public')->get($path));
        $alpha = (imagecolorat($hasil, 10, 10) >> 24) & 0x7F;
        $this->assertSame(127, $alpha, 'Piksel transparan harus tetap transparan.');
    }

    // =========================================================================
    // NAMA FILE DOWNLOAD
    // =========================================================================

    #[DataProvider('namaDownload')]
    public function test_nama_download_selalu_berakhiran_jpg(?string $namaAsli, string $harapan): void
    {
        $this->assertSame($harapan, (new ProdukGambar(['nama_asli' => $namaAsli]))->namaDownload());
    }

    public static function namaDownload(): array
    {
        return [
            [' foto.png', 'foto.jpg'],
            ['IMG_1234.jpeg', 'IMG_1234.jpg'],
            ['shell.php', 'shell.jpg'],
            ['halaman.html', 'halaman.jpg'],
            ['folder\\x.png', 'folder-x.jpg'],
            ['', 'foto-produk.jpg'],
            [null, 'foto-produk.jpg'],
        ];
    }

    // =========================================================================
    // IP PENGUNJUNG DI BELAKANG PROXY LOKAL (bootstrap/app.php)
    // =========================================================================

    public function test_ip_asli_terbaca_dari_proxy_lokal_dan_tidak_bisa_dipalsukan(): void
    {
        Route::get('/_uji-ip', fn () => request()->ip());

        // Lewat proxy lokal CloudPanel (Nginx -> Varnish -> Nginx :8080): IP asli dari X-Forwarded-For
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.5, 127.0.0.1')
            ->get('/_uji-ip')
            ->assertSee('203.0.113.5');

        // Langsung dari internet dengan header palsu: header diabaikan
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->withHeader('X-Forwarded-For', '203.0.113.5')
            ->get('/_uji-ip')
            ->assertSee('198.51.100.7');
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function urlUpload(): string
    {
        // Livewire 4 memvalidasi signature relatif (tanpa skema & host)
        return URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5), [], false);
    }

    private function unggah(UploadedFile $file)
    {
        $user = new User(['name' => 'Penguji', 'username' => 'penguji', 'peran' => 'ADMIN']);
        $user->id = 1;

        return $this->actingAs($user)
            ->post($this->urlUpload(), ['files' => [$file]], ['Accept' => 'application/json']);
    }

    // UploadedFile sungguhan: tipe MIME dideteksi dari ISI file (finfo), sama seperti upload asli.
    // (UploadedFile::fake() menebak MIME dari nama file, jadi tidak bisa menguji penyamaran.)
    private function fileAsli(string $nama, string $isi): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'uji');
        file_put_contents($path, $isi);

        return new UploadedFile($path, $nama, null, null, true);
    }

    private function validasi(UploadedFile $file)
    {
        return Validator::make(['foto' => $file], ['foto' => AturanGambar::rules()]);
    }

    /** @return array{0: int, 1: int, 2: string} lebar, tinggi, mime dari file di disk public */
    private function ukuran(string $path): array
    {
        $info = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$info[0], $info[1], $info['mime']];
    }

    private static function jpeg(int $lebar, int $tinggi): string
    {
        $gd = imagecreatetruecolor($lebar, $tinggi);
        imagefill($gd, 0, 0, imagecolorallocate($gd, 200, 120, 40));
        ob_start();
        imagejpeg($gd, null, 90);

        return ob_get_clean();
    }

    // Sisipkan segmen JPEG (mis. APP1/EXIF atau komentar) tepat setelah marker SOI
    private static function sisipkanSegmen(string $jpeg, string $marker, string $isi): string
    {
        return substr($jpeg, 0, 2).$marker.pack('n', strlen($isi) + 2).$isi.substr($jpeg, 2);
    }

    private static function jpegDenganOrientasi(int $lebar, int $tinggi, int $orientasi): string
    {
        // TIFF big-endian, IFD0 berisi satu entri: tag 0x0112 (Orientation), tipe SHORT, 1 nilai
        $tiff = "MM\x00\x2A".pack('N', 8)
            .pack('n', 1)
            .pack('nnNnn', 0x0112, 3, 1, $orientasi, 0)
            .pack('N', 0);

        return self::sisipkanSegmen(self::jpeg($lebar, $tinggi), "\xFF\xE1", "Exif\x00\x00".$tiff);
    }

    // PNG 1x1 yang header IHDR-nya diubah agar mengaku berukuran $lebar x $tinggi
    private static function pngDenganUkuranPalsu(int $lebar, int $tinggi): string
    {
        $gd = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($gd);
        $png = ob_get_clean();

        // Signature PNG (8 byte) + panjang chunk (4) + "IHDR" (4) -> lebar & tinggi di offset 16
        return substr_replace($png, pack('NN', $lebar, $tinggi), 16, 8);
    }
}
