<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: PENGINGAT VERIFIKASI EMAIL (EMAIL VERIFICATION PROMPT)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Papan Peringatan: Mohon Cek Kotak Pos Anda".
 * Ketika seorang pengguna mencoba mengakses fitur tetapi emailnya belum diverifikasi,
 * sistem mencegatnya dan menampilkan instruksi ramah agar memeriksa email aktivasi terlebih dahulu.
 * ============================================================================
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * Menampilkan Layar Pengingat Verifikasi Email
     * 
     * Alur Kerja Bernomor:
     * (1) Cek apakah pengguna sudah memverifikasi emailnya.
     * (2) Jika sudah, langsung teruskan ke dashboard.
     * (3) Jika belum, tampilkan layar 'auth.verify-email'.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false))
            : view('auth.verify-email');
    }
}
