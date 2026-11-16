<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\User;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

class KaryawanSeeder extends Seeder
{
    /**
     * Mengisi data induk karyawan dari data contoh.
     * Field yang tidak punya kolom di ERD (status, plafon kasbon, verified)
     * tidak disimpan di sini.
     */
    public function run(): void
    {
        foreach (DemoData::employees() as $dataKaryawan) {
            $pengguna = User::where('name', $dataKaryawan['name'])
                ->where('role', 'supir')
                ->firstOrFail();

            $angkaKomisi = (float) explode('%', $dataKaryawan['commission'])[0];

            Karyawan::updateOrCreate(
                ['nomor_induk_karyawan' => $dataKaryawan['nip']],
                [
                    'id_pengguna' => $pengguna->id,
                    'nama_lengkap' => $dataKaryawan['name'],
                    'persentase_komisi_bp' => (int) round($angkaKomisi * 100),
                    'nama_bank' => $dataKaryawan['bank'],
                    'nomor_rekening' => $dataKaryawan['account'],
                    'potongan_bpjs_bulanan' => $dataKaryawan['bpjs'],
                ],
            );
        }
    }
}
