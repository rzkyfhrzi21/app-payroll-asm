<?php

namespace App\Support;

/**
 * ============================================================================
 * HELPER: STANDAR ATURAN KEKUATAN KATA SANDI (PASSWORD POLICY HELPER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Standar Kunci Gembok Rumah yang Kokoh".
 * Agar akun aplikasi tidak mudah ditebak oleh orang iseng, helper ini mewajibkan
 * setiap kata sandi memiliki panjang minimal 8 karakter, serta harus mengandung
 * kombinasi huruf, angka, dan simbol (misal: "Drv#4829").
 * ============================================================================
 */
final class AturanKataSandi
{
    public const POLA = '/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/';

    /**
     * @return array<int, string>
     */
    public static function aturan(bool $wajib = true): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'string',
            'min:8',
            'max:100',
            'regex:'.self::POLA,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function pesan(string $kunci = 'password'): array
    {
        return [
            "{$kunci}.required" => 'Kata sandi wajib diisi.',
            "{$kunci}.min" => 'Kata sandi minimal 8 karakter.',
            "{$kunci}.regex" => 'Kata sandi wajib memuat huruf, angka, dan karakter khusus (contoh: Drv#4829).',
        ];
    }
}
