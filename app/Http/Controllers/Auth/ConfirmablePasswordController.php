<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: KONFIRMASI KATA SANDI (CONFIRMABLE PASSWORD CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Pintu Lapis Kedua Sebelum Masuk ke Ruangan Brankas Rahasia".
 * Saat pengguna sudah login tetapi ingin melakukan tindakan sangat sensitif
 * (misalnya mengubah rekening bank atau menghapus akun), sistem meminta
 * pengguna mengetikkan ulang kata sandi mereka untuk memastikan bahwa orang yang
 * berada di depan layar benar-benar pemilik sah akun tersebut.
 * ============================================================================
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * Menampilkan Formulir Konfirmasi Kata Sandi
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Memeriksa Keabsahan Kata Sandi yang Dimasukkan
     * 
     * Alur Kerja Bernomor:
     * (1) Cocokkan email dan kata sandi yang diketik dengan data akun aktif.
     * (2) Jika kata sandi salah, lemparkan pesan kesalahan validasi.
     * (3) Jika benar, simpan tanda cap waktu konfirmasi di sesi.
     * (4) Lanjutkan pengalihan ke halaman tujuan sensitif tadi.
     */
    public function store(Request $request): RedirectResponse
    {
        // (1) & (2) Validasi kata sandi saat ini
        if (! Auth::guard('web')->validate([
            'email'    => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        // (3) Catat cap waktu konfirmasi sukses
        $request->session()->put('auth.password_confirmed_at', time());

        // (4) Lanjutkan aksi
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
