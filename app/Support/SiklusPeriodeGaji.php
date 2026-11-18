<?php

namespace App\Support;

use App\Models\PeriodeGaji;
use Illuminate\Support\Carbon;

/**
 * ============================================================================
 * HELPER: SIKLUS PERIODE PENGGAJIAN BULANAN (PAYROLL CYCLE HELPER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Penggaris Kalender Penggajian".
 * Aturan perusahaan batubara menetapkan bahwa satu periode gaji WAJIB dimulai
 * dari tanggal 1 dan berakhir di hari terakhir bulan yang sama (misal 1 - 31 Januari).
 * 
 * Helper ini menjadi satu-satunya sumber acuan (Single Source of Truth)
 * agar tidak ada admin yang membuat periode di tengah bulan (seperti 15 Januari - 15 Februari)
 * yang dapat mengacaukan pembukuan pajak dan slip gaji supir.
 * ============================================================================
 */
final class SiklusPeriodeGaji
{
    /**
     * Merapikan Tanggal Mulai & Selesai Menjadi Awal dan Akhir Bulan Penuh
     *
     * @return array{tanggal_mulai: string, tanggal_selesai: string}
     */
    public static function rentangBulan(mixed $tanggalAcuan): array
    {
        $acuan = $tanggalAcuan instanceof Carbon
            ? $tanggalAcuan->copy()
            : Carbon::parse((string) $tanggalAcuan);

        return [
            'tanggal_mulai' => $acuan->copy()->startOfMonth()->toDateString(),
            'tanggal_selesai' => $acuan->copy()->endOfMonth()->toDateString(),
        ];
    }

    /**
     * Kode periode standar dari sebuah tanggal, contoh: "Nov-25".
     */
    public static function kodeDariTanggal(mixed $tanggalAcuan): string
    {
        $acuan = $tanggalAcuan instanceof Carbon
            ? $tanggalAcuan->copy()
            : Carbon::parse((string) $tanggalAcuan);

        return $acuan->copy()->locale('id')->translatedFormat('M').'-'.$acuan->format('y');
    }


    /**
     * Konversi kode periode format "Mmm-yy" (misal: "Nov-26", "Des-26", "Jan-26")
     * menjadi objek Carbon di tanggal 1 bulan tersebut.
     */
    public static function tanggalDariKode(string $kode): ?Carbon
    {
        $kodeBersih = trim($kode);
        if ($kodeBersih === '') {
            return null;
        }

        // Peta singkatan bulan Bahasa Indonesia & Inggris ke nomor bulan (1-12)
        $petaBulan = [
            'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4,
            'MEI' => 5, 'MAY' => 5, 'JUN' => 6, 'JUL' => 7,
            'AGU' => 8, 'AUG' => 8, 'SEP' => 9, 'OKT' => 10, 'OCT' => 10,
            'NOV' => 11, 'DES' => 12, 'DEC' => 12,
        ];

        if (preg_match('/^([A-Za-z]{3})[-_ ]?(\d{2,4})$/', $kodeBersih, $cocok)) {
            $namaBulan = strtoupper($cocok[1]);
            $nomorBulan = $petaBulan[$namaBulan] ?? null;
            if ($nomorBulan === null) {
                return null;
            }

            $tahun = (int) $cocok[2];
            if ($tahun < 100) {
                $tahun += 2000;
            }

            return Carbon::create($tahun, $nomorBulan, 1)->startOfDay();
        }

        return null;
    }
    /**
     * Apakah periode sudah mengikuti aturan tanggal 1 s/d akhir bulan.
     */
    public static function sudahSesuai(PeriodeGaji $periode): bool
    {
        if ($periode->tanggal_mulai === null || $periode->tanggal_selesai === null) {
            return false;
        }

        return $periode->tanggal_mulai->day === 1
            && $periode->tanggal_selesai->isSameDay($periode->tanggal_selesai->copy()->endOfMonth());
    }
}
