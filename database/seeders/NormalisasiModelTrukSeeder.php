<?php

namespace Database\Seeders;

use App\Models\Truk;
use App\Services\PayrollDataService;
use Illuminate\Database\Seeder;

class NormalisasiModelTrukSeeder extends Seeder
{
    /**
     * Menyeragamkan penulisan merek/model armada.
     * Kasus nyata: "HINO PUTIH" (10 unit) dan "Hino Putih" (1 unit)
     * sebenarnya model yang sama, tapi terhitung dua model berbeda.
     */
    public function run(): void
    {
        $layanan = app(PayrollDataService::class);

        foreach (Truk::query()->get() as $truk) {
            $modelRapi = $layanan->normalisasiNamaModel($truk->merek_model);

            if ($modelRapi !== $truk->merek_model) {
                echo "  {$truk->nomor_polisi}: '{$truk->merek_model}' -> '{$modelRapi}'\n";
                $truk->update(['merek_model' => $modelRapi]);
            }
        }
    }
}
