<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ============================================================================
 * LAYANAN: PENGELOLA FOTO & KEAMANAN MEDIA GAMBAR (MEDIA IMAGE SERVICE)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Satpam Pemeriksa Barang Bawaan & Tukang Kompres Foto Fisik".
 * Supir di lapangan sering mengirim foto surat jalan atau foto profil berukuran besar (kamera HP canggih).
 * Jika disimpan mentah-mentah, harddisk server akan cepat penuh dan website jadi lemot.
 * 
 * Tugas Satpam & Kompresor Ini:
 * 1. Menolak file berbahaya: Memeriksa isi biner file agar file virus/skrip jahat tidak bisa masuk.
 * 2. Membatasi ukuran maksimal 2 MB.
 * 3. Mengubah otomatis foto ke format .WebP terkompresi yang jernih tetapi sangat ringan.
 * ============================================================================
 */
class StandarMediaGambarService
{
    // Batas maksimal ukuran berkas gambar: 2 MB (2.097.152 bytes)
    public const BATAS_MAKSIMAL_BYTE = 2097152;

    /**
     * @var array<int, string>
     */
    public const MIME_GAMBAR_DIIZINKAN = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Validasi keamanan berkas gambar dan simpan sebagai .webp teroptimasi.
     *
     * @throws ValidationException
     */
    public function validasiDanSimpanWebp(
        UploadedFile $berkas,
        string $folderTujuan = 'media-operasional',
        string $namaField = 'foto',
        int $lebarMaksimal = 1280,
        int $kualitasWebp = 82
    ): string {
        $namaAsli = (string) $berkas->getClientOriginalName();

        // 1. Tolak ekstensi berbahaya / double extension (OWASP A04 / BRIEF Bab 7.1)
        if (preg_match('/\.(php\d*|phtml|phar|exe|sh|bat|cmd|ps1|js|mjs|html?|svg|cgi|pl)\b/i', $namaAsli)) {
            throw ValidationException::withMessages([
                $namaField => ['Berkas ditolak karena mengandung ekstensi skrip atau eksekusi yang dilarang.'],
            ]);
        }

        // 2. Validasi ukuran maksimal 2 MB (BRIEF Bab 3.4)
        $ukuranByte = (int) $berkas->getSize();
        if ($ukuranByte <= 0 || $ukuranByte > self::BATAS_MAKSIMAL_BYTE) {
            $ukuranMb = round($ukuranByte / 1048576, 2);
            throw ValidationException::withMessages([
                $namaField => ["Ukuran gambar ({$ukuranMb} MB) melebihi batas maksimal 2 MB."],
            ]);
        }

        // 3. Inspeksi konten biner menggunakan finfo_file() (BRIEF Bab 7.1)
        $jalurSementara = $berkas->getRealPath();
        if ($jalurSementara === false || ! is_file($jalurSementara)) {
            throw ValidationException::withMessages([
                $namaField => ['Berkas unggahan tidak valid atau gagal dibaca oleh server.'],
            ]);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeAsli = $finfo ? (string) finfo_file($finfo, $jalurSementara) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if (! in_array($mimeAsli, self::MIME_GAMBAR_DIIZINKAN, true)) {
            throw ValidationException::withMessages([
                $namaField => ["Tipe konten berkas ({$mimeAsli}) tidak diizinkan. Gunakan gambar JPG, PNG, atau WEBP."],
            ]);
        }

        // 4. Konversi otomatis ke .webp dengan nama acak UUID (BRIEF Bab 3.4 & 7.1)
        $folderBersih = trim($folderTujuan, '/');
        $namaAcak = Str::uuid()->toString().'.webp';
        $jalurRelatif = $folderBersih.'/'.$namaAcak;

        $kontenWebp = $this->konversiKeBinaryWebp($jalurSementara, $lebarMaksimal, $kualitasWebp);

        Storage::disk('public')->put($jalurRelatif, $kontenWebp);

        return '/storage/'.$jalurRelatif;
    }

    /**
     * Validasi dan simpan satu atau banyak berkas gambar menjadi daftar path .webp.
     *
     * @param  array<int|string, UploadedFile|null>|UploadedFile|null  $berkasMasuk
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    public function simpanBanyakWebp(
        array|UploadedFile|null $berkasMasuk,
        string $folderTujuan = 'media-operasional',
        string $namaField = 'foto',
        int $lebarMaksimal = 1280,
        int $kualitasWebp = 82
    ): array {
        if ($berkasMasuk === null) {
            return [];
        }

        $daftarBerkas = $berkasMasuk instanceof UploadedFile ? [$berkasMasuk] : $berkasMasuk;
        $daftarHasil = [];

        foreach ($daftarBerkas as $berkas) {
            if (! $berkas instanceof UploadedFile) {
                continue;
            }

            $daftarHasil[] = $this->validasiDanSimpanWebp(
                $berkas,
                $folderTujuan,
                $namaField,
                $lebarMaksimal,
                $kualitasWebp
            );
        }

        return $daftarHasil;
    }

    /**
     * Konversi berkas gambar ke binary WebP menggunakan GD.
     */
    private function konversiKeBinaryWebp(string $jalurBerkas, int $lebarMaksimal, int $kualitas): string
    {
        $dataMentah = (string) file_get_contents($jalurBerkas);

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return $dataMentah;
        }

        $sumberGambar = @imagecreatefromstring($dataMentah);
        if ($sumberGambar === false) {
            return $dataMentah;
        }

        $lebarAsli = imagesx($sumberGambar);
        $tinggiAsli = imagesy($sumberGambar);

        if ($lebarAsli > $lebarMaksimal && $lebarMaksimal > 0) {
            $tinggiBaru = (int) max(1, round(($tinggiAsli * $lebarMaksimal) / $lebarAsli));
            $gambarSkala = imagescale($sumberGambar, $lebarMaksimal, $tinggiBaru);
            if ($gambarSkala !== false) {
                imagedestroy($sumberGambar);
                $sumberGambar = $gambarSkala;
            }
        }

        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($sumberGambar);
        }
        @imagealphablending($sumberGambar, true);
        @imagesavealpha($sumberGambar, true);

        ob_start();
        $berhasil = @imagewebp($sumberGambar, null, max(50, min(95, $kualitas)));
        $hasilBinary = (string) ob_get_clean();
        imagedestroy($sumberGambar);

        return ($berhasil && $hasilBinary !== '') ? $hasilBinary : $dataMentah;
    }
}
