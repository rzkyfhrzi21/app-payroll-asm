<?php

namespace App\Support;

use App\Models\PeriodeGaji;
use Illuminate\Support\Carbon;

/**
 * Aturan siklus periode penggajian: setiap periode WAJIB dimulai tanggal 1
 * dan berakhir pada hari terakhir bulan yang sama.
 *
 * Kelas ini menjadi satu-satunya sumber kebenaran agar tanggal tidak
 * berbeda-beda antar halaman.
 */
final class SiklusPeriodeGaji
{
    /**
     * Merapikan tanggal mulai & selesai menjadi awal dan akhir bulan.
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
