<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: BENTENG PERTAHANAN KEAMANAN & BUKU LOG AUDIT (OWASP TOP 10)
 * ==============================================================================
 * Kelas ini bekerja seperti tim keamanan digital dan buku tamu khusus pos penjagaan:
 * 1. Menempelkan segel pengaman pada setiap lembar data (HTTP Headers) agar aplikasi
 *    tidak bisa dibajak dari luar (anti-sniffing MIME type, anti-clickjacking dalam iframe,
 *    dan melarang robot Google mengindeks data internal rahasia gaji batubara).
 * 2. Mencatat setiap perubahan data penting (POST, PUT, PATCH, DELETE) ke dalam buku
 *    audit hitam `storage/logs/security-audit.log` (siapa pengguna yang mengubah, jam berapa,
 *    alamat IP dari mana, dan rute apa yang dipanggil).
 * ==============================================================================
 */
class StandarKeamananMiddleware
{
    /**
     * Menambahkan header keamanan OWASP Top 10 (A05), proteksi indeksasi mesin pencari (Bab 11.2),
     * dan mencatat audit trail mutasi data (OWASP A09).
     */
    public function handle(Request $permintaan, Closure $lanjutkan): Response
    {
        // (1) Jalankan request utama hingga menghasilkan respon
        $respon = $lanjutkan($permintaan);

        // (2) Suntikkan header standar keamanan web modern
        $respon->headers->set('X-Content-Type-Options', 'nosniff');                          // Cegah browser menebak-nebak tipe file (anti sniffing)
        $respon->headers->set('X-Frame-Options', 'SAMEORIGIN');                             // Cegah halaman dimasukkan ke dalam iframe website orang lain (anti clickjacking)
        $respon->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');        // Lindungi privasi alamat asal saat berpindah link
        $respon->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()'); // Matikan akses sensor perangkat yang tidak relevan
        $respon->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');              // Larang mesin pencari (Google, Bing) mengindeks aplikasi internal ini

        // (3) Bila ada aksi mutasi/perubahan data (bukan sekadar membaca), catat ke buku audit
        if (in_array(strtoupper($permintaan->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $this->catatAuditMutasi($permintaan, $respon);
        }

        return $respon;
    }

    /**
     * Mencatat jejak audit mutasi ke file teks `security-audit.log`.
     */
    private function catatAuditMutasi(Request $permintaan, Response $respon): void
    {
        try {
            $pengguna = $permintaan->user();
            $catatanAudit = [
                'waktu' => now()->toIso8601String(),
                'metode' => strtoupper($permintaan->method()),
                'url' => $permintaan->path(),
                'rute' => $permintaan->route()?->getName() ?? '-',
                'id_pengguna' => $pengguna?->id,
                'email_pengguna' => $pengguna?->email ?? $permintaan->input('email', 'anonim'),
                'peran' => $pengguna?->role?->value ?? 'tamu',
                'ip' => $permintaan->ip(),
                'status_http' => $respon->getStatusCode(),
            ];

            $jalurLog = storage_path('logs/security-audit.log');
            @file_put_contents(
                $jalurLog,
                json_encode($catatanAudit, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable) {
            // Jangan gagalkan request utama bila direktori log terkunci.
        }
    }
}
