<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Batas percobaan gagal login: maksimal 5x dalam 15 menit / 900 detik (BRIEF Bab 10.1).
     */
    public const BATAS_PERCOBAAN_MAKSIMAL = 5;

    public const DURASI_KUNCI_DETIK = 900;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:4', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::DURASI_KUNCI_DETIK);
            $sisaPercobaan = max(0, self::BATAS_PERCOBAAN_MAKSIMAL - RateLimiter::attempts($this->throttleKey()));

            $pesanGalat = $sisaPercobaan > 0
                ? "Email atau kata sandi tidak sesuai. Sisa percobaan sebelum akun dikunci 15 menit: {$sisaPercobaan}x."
                : 'Batas maksimal 5x percobaan login gagal telah terlampaui. Akun dikunci sementara selama 15 menit.';

            $this->session()->flash('toast', [
                'success' => false,
                'type' => 'error',
                'title' => 'Autentikasi Gagal',
                'action' => 'AUTH_LOGIN_FAILED',
                'status' => 'INVALID_CREDENTIALS',
                'message' => $pesanGalat,
                'timestamp' => now()->toIso8601String(),
            ]);

            throw ValidationException::withMessages([
                'email' => $pesanGalat,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::BATAS_PERCOBAAN_MAKSIMAL)) {
            return;
        }

        event(new Lockout($this));

        $detikTersisa = RateLimiter::availableIn($this->throttleKey());
        $menitTersisa = (int) ceil($detikTersisa / 60);
        $pesanTerkunci = "Terlalu banyak percobaan login gagal (maksimal 5x/15 menit). Silakan coba kembali dalam {$menitTersisa} menit ({$detikTersisa} detik).";

        if ($this->expectsJson() || $this->ajax() || $this->header('X-Interaksi-Ajax') === '1') {
            throw new ThrottleRequestsException($pesanTerkunci, null, [
                'Retry-After' => (string) $detikTersisa,
                'X-RateLimit-Limit' => (string) self::BATAS_PERCOBAAN_MAKSIMAL,
            ]);
        }

        $this->session()->flash('toast', [
            'success' => false,
            'type' => 'error',
            'title' => 'Akun Dikunci Sementara (15 Menit)',
            'action' => 'RATE_LIMIT_EXCEEDED',
            'status' => 'BLOCKED',
            'message' => $pesanTerkunci,
            'timestamp' => now()->toIso8601String(),
        ]);

        throw ValidationException::withMessages([
            'email' => $pesanTerkunci,
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
