<?php

namespace Database\Seeders;

use App\Models\BiayaOperasional;
use App\Models\PeriodeGaji;
use App\Models\Truk;
use Illuminate\Database\Seeder;

class BiayaOperasionalSeeder extends Seeder
{
    /**
     * Mengisi biaya operasional armada contoh untuk periode Jan-26.
     */
    public function run(): void
    {
        $periode = PeriodeGaji::where('kode', 'Jan-26')->first();
        if (! $periode) {
            return;
        }

        $daftarTruk = Truk::query()->get();
        if ($daftarTruk->isEmpty()) {
            return;
        }

        $truk1 = $daftarTruk->firstWhere('nomor_polisi', 'BG 8711 KL') ?? $daftarTruk->first();
        $truk2 = $daftarTruk->skip(1)->first() ?? $truk1;

        $daftarBiaya = [
            [
                'id_truk' => $truk1->id,
                'tanggal_biaya' => '2026-01-14',
                'kategori' => 'retribusi',
                'keterangan' => 'OLI',
                'nominal' => 189_000,
                'status' => 'disetujui',
            ],
            [
                'id_truk' => $truk2->id,
                'tanggal_biaya' => '2026-01-14',
                'kategori' => 'tol',
                'keterangan' => 'BIAYA TOL TRANS SUMATERA',
                'nominal' => 189_000,
                'status' => 'disetujui',
            ],
            [
                'id_truk' => $truk1->id,
                'tanggal_biaya' => '2026-01-15',
                'kategori' => 'tol',
                'keterangan' => 'BIAYA TOL TRANS SUMATERA',
                'nominal' => 189_000,
                'status' => 'disetujui',
            ],
        ];

        foreach ($daftarBiaya as $item) {
            BiayaOperasional::updateOrCreate(
                [
                    'id_periode_gaji' => $periode->id,
                    'id_truk' => $item['id_truk'],
                    'tanggal_biaya' => $item['tanggal_biaya'],
                    'kategori' => $item['kategori'],
                ],
                $item
            );
        }
    }
}
