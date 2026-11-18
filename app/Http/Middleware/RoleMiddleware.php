<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: SATPAM PEMERIKSA KARTU AKSES PERAN (ROLE MIDDLEWARE)
 * ==============================================================================
 * Kelas ini bertugas seperti petugas sekuriti di pos depan kantor logistik batubara:
 * 1. Setiap ada orang (user) yang mau masuk ke ruangan tertentu (rute web),
 *    satpam memeriksa kartu identitasnya: Apakah dia Admin HRD, Pimpinan/Direktur, atau Supir Truk?
 * 2. Jika supir mencoba masuk ke ruangan otorisasi gaji Direktur (`/executive/payroll`),
 *    satpam langsung menghentikannya, mengunci pintu (logout aman), dan menyuruhnya kembali
 *    ke pos login depan dengan pesan peringatan ramah.
 * 3. Jika peran pengguna cocok dengan izin ruangan (`$roles`), satpam mempersilakan masuk.
 * ==============================================================================
 */
class RoleMiddleware
{
    /**
     * Batasi akses route berdasarkan role pengguna.
     * Contoh pemakaian: ->middleware('role:admin') atau 'role:admin,pimpinan'.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // (1) Periksa apakah pengguna sudah login
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        // (2) Ambil nilai peran enum pengguna (admin, pimpinan, atau supir)
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role) ?? UserRole::Supir;

        // (3) Cocokkan dengan daftar peran yang diizinkan untuk rute ini
        if ($roles !== [] && ! in_array($role->value, $roles, true)) {
            if ($request->expectsJson()) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }

            // Bila tidak berhak, bersihkan sesi dan minta login ulang
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun Anda tidak memiliki akses ke halaman tersebut. Silakan masuk kembali.']);
        }

        // (4) Izin cocok, silakan lanjutkan membuka halaman
        return $next($request);
    }
}
