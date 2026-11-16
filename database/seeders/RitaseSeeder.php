<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\Truk;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

class RitaseSeeder extends Seeder
{
    /**
     * Mengisi ritase contoh milik Kuwat Slamet pada periode Jan-26.
     * Data contoh memakai tanggal harian, lalu ditempatkan pada Januari 2026.
     */
    public function run(): void
    {
        $periodeGaji = PeriodeGaji::where('kode', 'Jan-26')->firstOrFail();
        $karyawan = Karyawan::where('nomor_induk_karyawan', 'DRV-KAFA-042')->firstOrFail();
        $truk = Truk::where('nomor_polisi', 'BE 8639 AAU')->firstOrFail();
        $rute = Rute::where('kode', 'MIP-PORT')->firstOrFail();

        foreach (DemoData::payrollTrips() as $dataRitase) {
            $tanggalRitase = sprintf('2026-01-%02d', (int) substr($dataRitase['date'], 0, 2));
            $tonaseKg = (int) round((float) str_replace(',', '.', $dataRitase['tonnage']) * 1000);
            $tarifPerTon = (int) $dataRitase['rate'];

            Ritase::updateOrCreate(
                [
                    'id_periode_gaji' => $periodeGaji->id,
                    'id_karyawan' => $karyawan->id,
                    'tanggal_ritase' => $tanggalRitase,
                ],
                [
                    'id_truk' => $truk->id,
                    'id_rute' => $rute->id,
                    'jumlah_rit' => 1,
                    'tonase_kg' => $tonaseKg,
                    'volume_liter' => null,
                    'tarif_per_ton' => $tarifPerTon,
                    'total_angkutan' => (int) round($tonaseKg * $tarifPerTon / 1000),
                    'uang_jalan' => $rute->uang_jalan_default,
                ],
            );
        }
    }
}
