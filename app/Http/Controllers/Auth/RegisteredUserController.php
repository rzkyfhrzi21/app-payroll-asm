<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: PENDAFTARAN PENGGUNA BARU (USER REGISTRATION CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Meja Pendaftaran Anggota Baru di Pintu Lobi".
 * Jika fitur pendaftaran dibuka, calon pengguna dapat mengisi nama, email,
 * dan kata sandi baru untuk membuat akun di aplikasi.
 * ============================================================================
 */
class RegisteredUserController extends Controller
{
    /**
     * Menampilkan Formulir Pendaftaran Akun Baru
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Memproses Penyimpanan Akun Pengguna Baru
     * 
     * Alur Kerja Bernomor:
     * (1) Validasi nama lengkap, keunikan email (tidak boleh kembar), dan kata sandi.
     * (2) Buatkan baris akun user baru di tabel 'users' dengan kata sandi terenkripsi.
     * (3) Picu event 'Registered' (misal untuk mengirim email selamat datang).
     * (4) Langsung login-kan akun baru tersebut dan arahkan ke dashboard.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
