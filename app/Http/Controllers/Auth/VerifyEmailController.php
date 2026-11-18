<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * ============================================================================
 * CONTROLLER: VERIFIKASI KEASLIAN ALAMAT EMAIL (VERIFY EMAIL CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Stempel Pengesahan Keaslian Surat Alamat Pengguna".
 * Ketika pengguna mengklik link di dalam email mereka, sistem memeriksa
 * tanda tangan digitalnya. Jika sah, akun diberi stempel resmi 'Terverifikasi'
 * sehingga semua hak akses sistem terbuka penuh.
 * ============================================================================
 */
class VerifyEmailController extends Controller
{
    /**
     * Mengesahkan Alamat Email Pengguna Menjadi Terverifikasi
     * 
     * Alur Kerja Bernomor:
     * (1) Cek apakah pengguna sudah memiliki status terverifikasi sebelumnya.
     * (2) Jika belum, beri tanda bahwa email telah diverifikasi di database.
     * (3) Picu event 'Verified' untuk keperluan log audit keamanan.
     * (4) Alihkan pengguna ke dashboard utama dengan tanda status '?verified=1'.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // (1) Jika sudah pernah diverifikasi
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        // (2) Beri tanda verified
        if ($request->user()->markEmailAsVerified()) {
            // (3) Picu event verifikasi
            event(new Verified($request->user()));
        }

        // (4) Alihkan ke dashboard
        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
