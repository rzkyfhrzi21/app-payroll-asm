<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: PERMINTAAN TAUTAN LUPA KATA SANDI (FORGOT PASSWORD CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kotak Surat Darurat Lupa Kunci Rumah".
 * Jika pengguna tidak bisa masuk karena lupa kata sandinya, mereka cukup memasukkan
 * alamat email kantor mereka di sini. Sistem akan mengirimkan surat elektronik
 * berisi tautan rahasia sekali pakai untuk mereset kata sandi.
 * 
 * Keamanan Anti Serangan Bot (Rate Limiting):
 * Permintaan dibatasi maksimal 5 kali percobaan dalam 15 menit agar server
 * tidak dibanjiri spam oleh pihak yang tidak bertanggung jawab.
 * ============================================================================
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Menampilkan Formulir Permintaan Reset Kata Sandi
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Memproses Pengiriman Tautan Reset Kata Sandi ke Email
     * 
     * Alur Kerja Bernomor:
     * (1) Validasi format alamat email yang diketik pengguna.
     * (2) Terapkan pembatasan rate limiter (maksimal 5 kali percobaan per IP dan email).
     * (3) Minta broker kata sandi Laravel mengirimkan tautan reset ke inbox email.
     * (4) Kembalikan respon sukses atau pesan kesalahan jika email tidak ditemukan.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
        ], [
            'email.required' => 'Alamat email terdaftar wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $kunciThrottle = 'forgot-password:'.Str::lower((string) $request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunciThrottle, 5)) {
            $detikTersisa = RateLimiter::availableIn($kunciThrottle);
            $pesanLimit = "Terlalu banyak permintaan pemulihan kata sandi. Silakan tunggu {$detikTersisa} detik sebelum mencoba kembali.";

            throw ValidationException::withMessages([
                'email' => [$pesanLimit],
            ]);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            RateLimiter::clear($kunciThrottle);
            $pesanSukses = __($status);
            $muatanToast = [
                'success' => true,
                'type' => 'success',
                'title' => 'Tautan Pemulihan Terkirim',
                'action' => 'PASSWORD_RESET_LINK',
                'status' => 'COMPLETED',
                'message' => "Instruksi atur ulang kata sandi telah dikirimkan ke alamat email '{$request->input('email')}'.",
                'data' => ['email' => $request->input('email')],
                'timestamp' => now()->toIso8601String(),
            ];

            if ($request->expectsJson() || $request->ajax() || $request->header('X-Interaksi-Ajax') === '1') {
                return response()->json($muatanToast);
            }

            return back()
                ->with('status', $pesanSukses)
                ->with('toast', $muatanToast);
        }

        RateLimiter::hit($kunciThrottle, 900);
        $pesanGalat = __($status);
        $muatanGalat = [
            'success' => false,
            'type' => 'error',
            'title' => 'Permintaan Pemulihan Gagal',
            'action' => 'PASSWORD_RESET_FAILED',
            'status' => 'VALIDATION_FAILED',
            'message' => "Alamat email '{$request->input('email')}' tidak ditemukan atau belum terdaftar pada sistem.",
            'errors' => ['email' => [$pesanGalat]],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Interaksi-Ajax') === '1') {
            return response()->json($muatanGalat, 422);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $pesanGalat])
            ->with('toast', $muatanGalat);
    }
}
