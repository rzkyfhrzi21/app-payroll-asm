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
                'tanggal_mulai' => '2026-01-01',
                'tanggal_selesai' => '2026-01-31',
                'tanggal_bayar' => '2026-02-05',
                'status' => 'draft',
            ],
        );
    }
}
