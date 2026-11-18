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

        $emailAuthor = strtolower(trim((string) config('app.author.email', 'septiaranabila559@gmail.com')));
        if ($emailAuthor !== '') {
            User::updateOrCreate(
                ['email' => $emailAuthor],
                [
                    'name' => config('app.author.nama', 'Nabela Septiara'),
                    'password' => 'password',
                    'role' => UserRole::Admin,
                    'nomor_telepon' => config('app.author.whatsapp', '085760898417'),
                    'alamat' => config('app.author.alamat', 'Kosan Dakwah'),
                ],
            );
        }

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
            JenisTrukSeeder::class,
            BiayaOperasionalSeeder::class,
            DataPerusahaanSeeder::class,
        ]);
    }
}
