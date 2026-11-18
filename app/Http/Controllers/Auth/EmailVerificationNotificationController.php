<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ============================================================================
 * CONTROLLER: PENGIRIM ULANG NOTIFIKASI VERIFIKASI EMAIL
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kurir Pos Pengirim Surat Konfirmasi Alamat".
 * Jika tautan surat konfirmasi email sebelumnya hilang atau kedaluwarsa di kotak masuk,
 * pengguna dapat menekan tombol kirim ulang. Kurir ini akan mengirimkan surat baru
 * berisi link verifikasi resmi ke email pengguna.
 * ============================================================================
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Mengirimkan Tautan Surat Verifikasi Baru ke Email Pengguna
     * 
     * Alur Kerja Bernomor:
     * (1) Cek apakah email pengguna sebetulnya sudah berstatus terverifikasi.
     * (2) Jika sudah, langsung alihkan ke dashboard.
     * (3) Jika belum, kirimkan surat elektronik notifikasi verifikasi.
     * (4) Kembalikan pengguna dengan pesan status bahwa surat sudah terkirim.
     */
    public function store(Request $request): RedirectResponse
    {
        // (1) & (2) Jika sudah terverifikasi, langsung lewat
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // (3) Kirim email verifikasi
        $request->user()->sendEmailVerificationNotification();

        // (4) Beri tanda status sukses kirim
        return back()->with('status', 'verification-link-sent');
    }
}
