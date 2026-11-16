<?php

namespace Database\Seeders;

use App\Models\Truk;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

class TrukSeeder extends Seeder
{
    /**
     * Mengisi unit armada dari data contoh karyawan.
     * Kolom yang tidak ada di ERD (mis. warna unit) tidak disimpan.
     */
    public function run(): void
    {
        foreach (DemoData::employees() as $dataKaryawan) {
            $merekModel = trim(preg_replace('/\s*\(.*\)\s*/', '', $dataKaryawan['truck']));

            Truk::updateOrCreate(
                ['nomor_polisi' => $dataKaryawan['plate']],
                [
                    'merek_model' => $merekModel,
                ],
            );
        }
    }
}
