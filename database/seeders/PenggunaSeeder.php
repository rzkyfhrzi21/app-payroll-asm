<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

class PenggunaSeeder extends Seeder
{
    /**
     * Membuat akun login untuk setiap supir pada data contoh.
     * Hanya kolom yang ada di ERD yang diisi (nama, email, kata sandi, peran, nomor telepon).
     */
    public function run(): void
    {
        foreach (DemoData::employees() as $dataKaryawan) {
            $alamatEmail = $dataKaryawan['nip'] === 'DRV-KAFA-042'
                ? 'supir@payroll.test'
                : strtolower(str_replace(' ', '.', $dataKaryawan['name'])).'@payroll.test';

            User::updateOrCreate(
                ['email' => $alamatEmail],
                [
                    'name' => $dataKaryawan['name'],
                    'password' => 'password',
                    'role' => UserRole::Supir,
                    'nomor_telepon' => $dataKaryawan['phone'],
                ],
            );
        }
    }
}
