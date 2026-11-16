<?php

namespace Database\Seeders;

use App\Models\AngsuranKasbon;
use App\Models\Karyawan;
use App\Models\Kasbon;
use Illuminate\Database\Seeder;

class KasbonSeeder extends Seeder
{
    /**
     * Mengisi kasbon sesuai skema cicilan baru:
     * - Batas maksimal pinjaman Rp 1.000.000 (Kasbon::BATAS_MAKSIMAL)
     * - Tenor maksimal 3 bulan (Kasbon::TENOR_MAKSIMAL)
     * - Jadwal cicilan tercatat di tabel angsuran_kasbon
     */
    public function run(): void
    {
        $karyawanMi = Karyawan::where('nomor_induk_karyawan', 'DRV-KAFA-110')->first();

        if ($karyawanMi !== null) {
            $kasbon = Kasbon::updateOrCreate(
                [
                    'id_karyawan' => $karyawanMi->id,
                    'total_pinjaman' => 1_000_000,
                ],
                [
                    'nominal_angsuran' => 500_000,
                    'jumlah_tenor' => 2,
                    'sisa_pinjaman' => 0,
                    'status' => 'lunas',
                    'tanggal_pengambilan' => '2026-11-14',
                    'keterangan' => 'KEPERLUAN KELUARGA',
                ]
            );

            // Angsuran ke-1 (sudah terpotong)
            AngsuranKasbon::updateOrCreate(
                [
                    'id_kasbon' => $kasbon->id,
                    'urutan' => 1,
                ],
                [
                    'nominal' => 500_000,
                    'tanggal_rencana' => '2026-11-14',
                    'tanggal_potong' => '2026-11-14',
                    'status' => 'terpotong',
                ]
            );

            // Angsuran ke-2 (sudah terpotong)
            AngsuranKasbon::updateOrCreate(
                [
                    'id_kasbon' => $kasbon->id,
                    'urutan' => 2,
                ],
                [
                    'nominal' => 500_000,
                    'tanggal_rencana' => '2026-12-14',
                    'tanggal_potong' => '2026-11-14',
                    'status' => 'terpotong',
                ]
            );
        }
    }
}
