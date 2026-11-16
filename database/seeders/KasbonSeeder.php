<?php

namespace Database\Seeders;

use App\Models\Kasbon;
use App\Models\Karyawan;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

class KasbonSeeder extends Seeder
{
    /**
     * Mengisi kasbon dari data contoh.
     * Kolom tanggal & keperluan tidak ada pada ERD sehingga tidak disimpan.
     */
    public function run(): void
    {
        foreach (DemoData::loans() as $dataKasbon) {
            $karyawan = Karyawan::where('nomor_induk_karyawan', $dataKasbon['nip'])->first();

            if ($karyawan === null) {
                continue;
            }

            Kasbon::updateOrCreate(
                [
                    'id_karyawan' => $karyawan->id,
                    'total_pinjaman' => $dataKasbon['principal'],
                ],
                [
                    'nominal_angsuran' => $dataKasbon['installment'],
                    'sisa_pinjaman' => $dataKasbon['remaining'],
                    'status' => $dataKasbon['remaining'] <= 0 ? 'lunas' : 'approved',
                ],
            );
        }
    }
}
