<?php

namespace Database\Seeders;

use App\Models\PeriodeGaji;
use Illuminate\Database\Seeder;

class PeriodeGajiSeeder extends Seeder
{
    /**
     * Membuat periode gaji Jan-26. Rentang tanggal mengikuti data ritase
     * pada slip gaji (21 Agu 2026 - 18 Sep 2026).
     */
    public function run(): void
    {
        PeriodeGaji::updateOrCreate(
            ['kode' => 'Jan-26'],
            [
                'tanggal_mulai' => '2026-08-21',
                'tanggal_selesai' => '2026-09-18',
                'tanggal_bayar' => '2026-09-25',
                'status' => 'draft',
            ],
        );
    }
}
