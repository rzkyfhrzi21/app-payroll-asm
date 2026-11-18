<?php

namespace App\Support;

/**
 * ============================================================================
 * HELPER: PENYERAGAMAN FORMAT NOMOR TELEPON (PHONE FORMAT HELPER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Alat Penertib Nomor Kontak WhatsApp / Telepon".
 * Pengguna sering mengetik nomor HP dengan format berbeda-beda:
 * ada yang "0812...", "+62812...", atau "62812...".
 * Helper ini merapikan nomor tersebut agar seragam dalam format standar internasional (+62...)
 * saat disimpan di database, dan membuang kode negara saat ditampilkan di formulir
 * agar supir tidak bingung.
 * ============================================================================
 */
final class FormatNomorTelepon
{
    public const POLA_VALIDASI = '/^\+628[0-9]{8,11}$/';

    public static function keStandar(?string $nilai): ?string
    {
        $digit = preg_replace('/\D/', '', (string) $nilai) ?? '';

        if ($digit === '') {
            return null;
        }

        // Terima ketikan "+62...", "62...", maupun awalan lokal "0...".
        if (str_starts_with($digit, '62')) {
            $digit = substr($digit, 2);
        }

        $digit = ltrim($digit, '0');

        // Nilai tidak valid tetap dikembalikan agar aturan regex menolaknya
        // dengan pesan yang jelas, bukan diam-diam dikosongkan.
        return '+62'.$digit;
    }

    /**
     * Angka lokal tanpa kode negara, dipakai sebagai nilai input form.
     */
    public static function keLokal(?string $nilai): string
    {
        $standar = self::keStandar($nilai);

        return $standar === null ? '' : substr($standar, 3);
    }

    /**
     * Tampilan manusiawi, misalnya "+62 812-8930-1123".
     */
    public static function tampil(?string $nilai): string
    {
        $lokal = self::keLokal($nilai);

        if ($lokal === '') {
            return '—';
        }

        if (strlen($lokal) <= 3) {
            return '+62 '.$lokal;
        }

        $awalan = substr($lokal, 0, 3);
        $tengah = substr($lokal, 3, 4);
        $akhir = substr($lokal, 7);

        return trim('+62 '.$awalan.'-'.$tengah.'-'.$akhir, '-');
    }
}
