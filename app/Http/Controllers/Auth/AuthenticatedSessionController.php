<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: PINTU GERBANG MASUK & KELUAR SISTEM (AUTHENTICATED SESSION)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Pintu Masuk Utama & Petugas Pos Keamanan Gedung".
 * Setiap kali Admin, Pimpinan, atau Supir hendak masuk ke aplikasi,
 * mereka harus menunjukkan alamat email dan kata sandi di sini.
 * Petugas akan mencocokkan data; jika benar, pengunjung diberi tiket tanda masuk
 * (sesi login) dan diantarkan ke ruangan kerja masing-masing.
 * Saat pulang kerja (logout), tiket tanda masuk disobek/dihanguskan agar aman.
 * ============================================================================
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan Halaman Formulir Login
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Memproses Pengajuan Masuk (Login) Pengguna
     * 
     * Alur Kerja Bernomor:
     * (1) Periksa kecocokan email dan kata sandi pengguna (dibatasi 5 kali salah).
     * (2) Acak ulang nomor tiket sesi (regenerate session) agar tidak dibajak orang lain.
     * (3) Ambil identitas pengguna dan tentukan halaman tujuan (intended URL).
     * (4) Siapkan notifikasi sambutan ramah (Toast Sukses).
     * (5) Alihkan pengguna ke dalam dashboard sesuai peran mereka.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        // (1) Jalankan verifikasi kredensial login
        $request->authenticate();

        // (2) Perbarui ID sesi demi keamanan
        $request->session()->regenerate();

        // (3) Identifikasi pengguna yang berhasil masuk
        $pengguna = $request->user();
        $urlTujuan = session()->pull('url.intended', route('dashboard', absolute: false));
        $pesanSukses = 'Selamat datang kembali, '.($pengguna?->name ?? 'Pengguna').'. Sesi kerja Anda telah terverifikasi.';

        // (4) Siapkan paket notifikasi toast
        $muatanToast = [
            'success'   => true,
            'type'      => 'success',
            'title'     => 'Berhasil Masuk ke Sistem',
            'action'    => 'AUTH_LOGIN',
            'status'    => 'COMPLETED',
            'message'   => $pesanSukses,
            'data'      => [
                'id'    => $pengguna?->id,
                'nama'  => $pengguna?->name,
                'peran' => $pengguna?->role?->value,
            ],
            'redirect'  => $urlTujuan,
            'timestamp' => now()->toIso8601String(),
        ];

        $request->session()->flash('toast', $muatanToast);

        // Jika form login dikirim lewat AJAX
        if ($request->expectsJson() || $request->ajax() || $request->header('X-Interaksi-Ajax') === '1') {
            return response()->json($muatanToast);
        }

        // (5) Arahkan ke dashboard
        return redirect()->to($urlTujuan);
    }

    /**
     * Memproses Keluar Sistem (Logout)
     * 
     * Alur Kerja Bernomor:
     * (1) Cabut hak otentikasi akun pengguna.
     * (2) Batalkan dan hapus seluruh data tiket sesi di server.
     * (3) Terbitkan token keamanan CSRF baru.
     * (4) Arahkan kembali pengguna ke gerbang depan (halaman login).
     */
    public function destroy(Request $request): RedirectResponse
    {
        // (1) Logout pengguna
        Auth::guard('web')->logout();

        // (2) Hanguskan sesi
        $request->session()->invalidate();

        // (3) Acak token CSRF
        $request->session()->regenerateToken();

        // (4) Alihkan ke halaman awal
        return redirect('/');
    }
}
