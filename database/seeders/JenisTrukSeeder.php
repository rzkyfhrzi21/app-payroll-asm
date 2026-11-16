<?php

namespace Database\Seeders;

use App\Models\JenisTruk;
use App\Models\Truk;
use Illuminate\Database\Seeder;

class JenisTrukSeeder extends Seeder
{
    /**
     * Membuat master jenis truk dari model armada yang sudah ada,
     * lalu menautkan setiap unit ke jenisnya.
     */
    public function run(): void
    {
        $daftarModel = Truk::query()
            ->select('merek_model')
            ->distinct()
            ->orderBy('merek_model')
            ->pluck('merek_model');

        $pemetaanNama = [
            'Hino 500 TI Putih' => 'Hino 500 Tronton',
            'Hino 500 TI Hijau' => 'Hino 500 Tronton',
            'Hino Putih' => 'Hino Tronton',
            'Hino Hijau' => 'Hino Tronton',
            'Fuso Fighter 220PS' => 'Fuso Fighter',
            'Fuso Orange' => 'Fuso Fighter',
            'Isuzu Giga FVZ' => 'Isuzu Giga',
            'Panzer' => 'Panzer',
        ];

        foreach ($daftarModel as $model) {
            $namaJenis = $pemetaanNama[$model] ?? $model;

            $jenis = JenisTruk::query()->firstOrCreate(
                ['nama' => $namaJenis],
                ['merek_model' => $model, 'status' => 'aktif'],
            );

            Truk::query()
                ->where('merek_model', $model)
                ->update(['id_jenis_truk' => $jenis->id]);
        }

        // Pastikan setiap unit punya jenis.
        $jenisCadangan = JenisTruk::query()->firstOrCreate(
            ['nama' => 'Lainnya'],
            ['merek_model' => 'Tidak diketahui', 'status' => 'aktif'],
        );

        Truk::query()->whereNull('id_jenis_truk')->update(['id_jenis_truk' => $jenisCadangan->id]);
    }
}
