<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Rute;
use App\Models\Truk;
use Illuminate\Database\Seeder;

class MasterStatusSeeder extends Seeder
{
    /**
     * Mengisi kolom baru: status aktif/nonaktif, relasi supir-armada,
     * serta jarak dan alamat rute. Data bersumber dari data yang sudah ada
     * agar tidak mengarang nilai di luar basis data.
     */
    public function run(): void
    {
        // Semua unit & rute aktif secara default.
        Truk::query()->update(['status' => 'aktif']);
        Rute::query()->update(['status' => 'aktif']);
        Karyawan::query()->update(['status' => 'aktif']);

        // Hubungkan unit ke supir yang benar-benar pernah memakainya di ritase.
        foreach (Truk::query()->get() as $truk) {
            $supirId = \App\Models\Ritase::query()
                ->where('id_truk', $truk->id)
                ->orderByDesc('tanggal_ritase')
                ->value('id_karyawan');

            if ($supirId !== null) {
                $truk->update(['id_karyawan' => $supirId]);
            }
        }

        // Jarak & alamat rute: hanya rute yang punya data pendukung jelas.
        $dataRute = [
            'MIP-PORT' => [
                'jarak_km' => 118,
                'alamat_asal' => 'Pit Muara Enim, Kab. Muara Enim',
                'alamat_tujuan' => 'Stockpile Jetty Kertapati, Palembang',
            ],
            'MIP-36' => [
                'jarak_km' => 96,
                'alamat_asal' => 'Pit Muara Enim, Kab. Muara Enim',
                'alamat_tujuan' => 'Stockpile KM 36, Muara Enim',
            ],
            'TAL-PORT' => [
                'jarak_km' => 132,
                'alamat_asal' => 'Pit Talang Akar, Kab. Muara Enim',
                'alamat_tujuan' => 'Stockpile Jetty Kertapati, Palembang',
            ],
            'DMY-NOV' => [
                'jarak_km' => null,
                'alamat_asal' => null,
                'alamat_tujuan' => null,
            ],
        ];

        foreach ($dataRute as $kode => $nilai) {
            Rute::query()->where('kode', $kode)->update($nilai);
        }
    }
}
