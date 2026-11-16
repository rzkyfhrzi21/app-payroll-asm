<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed akun demo untuk login multi-role.
     * Password seluruh akun: password
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@payroll.test'],
            [
                'name' => 'Siti Rahmawati',
                'password' => 'password',
                'role' => UserRole::Admin,
            ],
        );

        User::updateOrCreate(
            ['email' => 'pimpinan@payroll.test'],
            [
                'name' => 'Bpk. Handoko',
                'password' => 'password',
                'role' => UserRole::Pimpinan,
            ],
        );

        User::updateOrCreate(
            ['email' => 'supir@payroll.test'],
            [
                'name' => 'Kuwat Slamet',
                'password' => 'password',
                'role' => UserRole::Supir,
            ],
        );

        $this->call([
            PenggunaSeeder::class,
            TrukSeeder::class,
            RuteSeeder::class,
            KaryawanSeeder::class,
            PeriodeGajiSeeder::class,
            KasbonSeeder::class,
            RitaseSeeder::class,
            PenggajianSeeder::class,
            SlipGajiSeeder::class,
            SlipDummySeeder::class,
            MasterStatusSeeder::class,
            NormalisasiModelTrukSeeder::class,
            DataPerusahaanSeeder::class,
        ]);
    }
}
