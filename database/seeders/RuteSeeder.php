<?php

namespace Database\Seeders;

use App\Models\Rute;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

class RuteSeeder extends Seeder
{
    /**
     * Mengisi rute hauling dari data contoh.
     * Nama rute tidak disimpan karena tidak ada kolomnya pada ERD.
     */
    public function run(): void
    {
        foreach (DemoData::routes() as $dataRute) {
            Rute::updateOrCreate(
                ['kode' => $dataRute['code']],
                [
                    'material' => 'BATU BARA',
                    'tarif_per_ton' => $dataRute['rate'],
                    'uang_jalan_default' => $dataRute['trip_money'],
                ],
            );
        }
    }
}
