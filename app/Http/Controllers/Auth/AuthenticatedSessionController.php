<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $pengguna = $request->user();
        $urlTujuan = session()->pull('url.intended', route('dashboard', absolute: false));
        $pesanSukses = 'Selamat datang kembali, '.($pengguna?->name ?? 'Pengguna').'. Sesi kerja Anda telah terverifikasi.';

        $muatanToast = [
            'success' => true,
            'type' => 'success',
            'title' => 'Berhasil Masuk ke Sistem',
            'action' => 'AUTH_LOGIN',
            'status' => 'COMPLETED',
            'message' => $pesanSukses,
            'data' => [
                'id' => $pengguna?->id,
                'nama' => $pengguna?->name,
                'peran' => $pengguna?->role?->value,
            ],
            'redirect' => $urlTujuan,
            'timestamp' => now()->toIso8601String(),
        ];

        $request->session()->flash('toast', $muatanToast);

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Interaksi-Ajax') === '1') {
            return response()->json($muatanToast);
        }

        return redirect()->to($urlTujuan);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
