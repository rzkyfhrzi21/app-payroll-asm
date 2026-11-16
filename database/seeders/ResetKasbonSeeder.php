<?php

namespace Database\Seeders;

use App\Models\AngsuranKasbon;
use App\Models\Kasbon;
use App\Models\Penggajian;
use Illuminate\Database\Seeder;

class ResetKasbonSeeder extends Seeder
{
    /**
     * Membersihkan skema kasbon lama (multi-angsuran tanpa jadwal)
     * agar mulai bersih dengan skema cicilan baru.
     * Dipanggil manual: php artisan db:seed --class=ResetKasbonSeeder
     */
    public function run(): void
    {
        AngsuranKasbon::query()->delete();
        Kasbon::withTrashed()->forceDelete();

        // Snapshot potongan kasbon pada slip lama dikosongkan agar konsisten
        // dengan skema baru (potongan dihitung dari angsuran_kasbon).
        Penggajian::query()->update(['potongan_kasbon' => 0]);

        $this->command?->info('Kasbon lama dibersihkan. Potongan kasbon pada slip direset ke 0.');
    }
}
