<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================================
 * CONTROLLER: POLISI LALU LINTAS GERBANG UTAMA (DASHBOARD REDIRECTOR)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Resepsionis / Polisi Penunjuk Arah di Lobi Gedung".
 * Ketika seorang pengguna berhasil login dan mengetik alamat web '/dashboard',
 * resepsionis ini akan memeriksa kartu identitasnya (peran/role pengguna):
 * - Jika dia Admin HRD      -> Antarkan ke ruangan '/admin/dashboard'
 * - Jika dia Pimpinan/Bos    -> Antarkan ke ruangan '/executive/dashboard'
 * - Jika dia Supir Lapangan  -> Antarkan ke ruangan '/my/payroll'
 * 
 * Dengan begitu, masing-masing peran langsung melihat data yang relevan bagi mereka
 * tanpa tersesat ke ruangan yang bukan haknya.
 * ============================================================================
 */
class DashboardController extends Controller
{
    /**
     * Mengarahkan Pengguna ke Dashboard yang Sesuai dengan Perannya
     * 
     * Alur Kerja Bernomor:
     * (1) Dapatkan data user yang saat ini sedang login.
     * (2) Cek nilai peran/role pengguna (Admin, Pimpinan, atau Supir).
     * (3) Cari rute dashboard tujuan yang tepat sesuai role tersebut.
     * (4) Jika rute terdaftar resmi, lakukan pengalihan (redirect).
     * (5) Jika peran belum memiliki dashboard, cegat dengan pesan peringatan (403).
     */
    public function index(Request $request): RedirectResponse
    {
        // (1) Ambil identitas pengguna login
        $user = $request->user();

        // (2) Pastikan tipe role terbaca dengan benar dari enum
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role) ?? UserRole::Supir;

        // (3) Tentukan nama rute dashboard sesuai peran
        $route = $role->dashboardRoute();

        // (4) Pengecekan keamanan: apakah rute tersebut benar-benar ada di sistem
        if (! Route::has($route)) {
            // (5) Tolak akses jika portal belum disediakan
            abort(403, 'Portal untuk role ini belum tersedia.');
        }

        // Alihkan halaman ke dashboard tujuan
        return redirect()->route($route);
    }
}
