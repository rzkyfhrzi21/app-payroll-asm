<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\StandarKeamananMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Reverse proxy meneruskan skema lewat X-Forwarded-Proto.
        // Tanpa ini Laravel menganggap permintaan selalu HTTP dan Vite menghasilkan
        // URL aset http:// di halaman https:// sehingga CSS/JS diblokir browser.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );

        $middleware->web(append: [
            StandarKeamananMiddleware::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Standar Respon JSON Validasi Gagal (BRIEF Bab 1.2)
        $exceptions->render(function (ValidationException $galatValidasi, Request $permintaan) {
            if ($permintaan->expectsJson() || $permintaan->ajax() || $permintaan->header('X-Interaksi-Ajax') === '1') {
                $daftarPesan = collect($galatValidasi->errors())->flatten()->values()->all();
                $pesanRingkas = $daftarPesan !== []
                    ? 'Pembaruan data gagal: '.implode(' ', array_slice($daftarPesan, 0, 2))
                    : 'Terdapat kesalahan pada data formulir yang dikirimkan.';

                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Validasi Formulir Gagal',
                    'action' => strtoupper((string) ($permintaan->route()?->getName() ?: 'VALIDATION_FAILED')),
                    'status' => 'VALIDATION_FAILED',
                    'message' => $pesanRingkas,
                    'errors' => $galatValidasi->errors(),
                    'timestamp' => now()->toIso8601String(),
                ], $galatValidasi->status);
            }

            return null;
        });

        // Standar Respon Rate Limiter HTTP 429 (BRIEF Bab 10.2)
        $exceptions->render(function (ThrottleRequestsException $galatRateLimit, Request $permintaan) {
            $headers = $galatRateLimit->getHeaders();
            $retryAfter = (int) ($headers['Retry-After'] ?? 60);
            $pesan = 'Terlalu banyak permintaan dari perangkat Anda. Harap tunggu beberapa saat sebelum mencoba kembali.';

            if ($permintaan->expectsJson() || $permintaan->ajax() || $permintaan->header('X-Interaksi-Ajax') === '1') {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Batas Permintaan Terlampaui',
                    'action' => 'RATE_LIMIT_EXCEEDED',
                    'status' => 'BLOCKED',
                    'message' => $pesan,
                    'retry_after_seconds' => $retryAfter,
                    'timestamp' => now()->toIso8601String(),
                ], 429, array_merge($headers, [
                    'Retry-After' => (string) $retryAfter,
                    'X-RateLimit-Limit' => (string) ($headers['X-RateLimit-Limit'] ?? '60'),
                ]));
            }

            return null;
        });
    })->create();
