<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StandarKeamananMiddleware
{
    /**
     * Menambahkan header keamanan OWASP Top 10 (A05), proteksi indeksasi mesin pencari (Bab 11.2),
     * dan mencatat audit trail mutasi data (OWASP A09).
     */
    public function handle(Request $permintaan, Closure $lanjutkan): Response
    {
        $respon = $lanjutkan($permintaan);

        // Header Keamanan OWASP Top 10 (BRIEF Bab 7 & Bab 14.1)
        $respon->headers->set('X-Content-Type-Options', 'nosniff');
        $respon->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respon->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respon->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $respon->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        // Audit Trail (OWASP A09) untuk seluruh request mutasi data (POST, PUT, PATCH, DELETE)
        if (in_array(strtoupper($permintaan->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $this->catatAuditMutasi($permintaan, $respon);
        }

        return $respon;
    }

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
