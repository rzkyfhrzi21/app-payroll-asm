<?php

namespace Database\Seeders;

use App\Models\DataPerusahaan;
use Illuminate\Database\Seeder;

class DataPerusahaanSeeder extends Seeder
{
    public function run(): void
    {
        if (DataPerusahaan::query()->exists()) {
            return;
        }

        DataPerusahaan::create(DataPerusahaan::nilaiBawaan());
    }
}
