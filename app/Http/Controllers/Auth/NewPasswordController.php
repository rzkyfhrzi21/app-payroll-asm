<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: PEMBUATAN KATA SANDI BARU (NEW PASSWORD CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Bengkel Ganti Kunci Gembok Baru".
 * Saat pengguna lupa kata sandi dan mengklik link rahasia (token) yang dikirimkan
 * ke email mereka, layar ini akan terbuka. Pengguna diminta membuat kata sandi baru
 * yang kuat, mengonfirmasinya, dan sistem akan menggantikan kunci lama dengan yang baru.
 * ============================================================================
 */
class NewPasswordController extends Controller
{
    /**
     * Menampilkan Formulir Pembuatan Kata Sandi Baru
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Menyimpan Kata Sandi Baru yang Telah Divalidasi
     * 
     * Alur Kerja Bernomor:
     * (1) Validasi token rahasia, email, dan kekuatan kata sandi baru.
     * (2) Cek keaslian token reset melalui Password broker Laravel.
     * (3) Jika sah, enkripsi kata sandi baru (Hash) dan simpan ke database.
     * (4) Picu event PasswordReset dan arahkan kembali ke halaman login.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
