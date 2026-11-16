<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\Truk;
use Illuminate\Database\Seeder;

class SlipGajiSeeder extends Seeder
{
    /**
     * Mengisi data persis dari file:
     * docs/Slip Gaji - KUWAT SLAMET - Jan-26.xlsx (sheet "Slip Gaji").
     *
     * - 21 baris ritase (baris 8-28)
     * - 14 baris rekapitulasi (baris 36-50)
     *
     * Catatan: sel A22 berisi serial 46293 (28 Sep 2026), sedangkan
     * keterangan pada sheet menyebut baris tersebut 28/08. Dipakai 2026-08-28.
     */
    public function run(): void
    {
        $periodeGaji = PeriodeGaji::updateOrCreate(
            ['kode' => 'Jan-26'],
            [
                'tanggal_mulai' => '2026-08-21',
                'tanggal_selesai' => '2026-09-18',
                'tanggal_bayar' => '2026-09-25',
                'status' => 'draft',
            ],
        );

        $karyawan = Karyawan::where('nomor_induk_karyawan', 'DRV-KAFA-042')->firstOrFail();
        $truk = Truk::where('nomor_polisi', 'BE 8639 AAU')->firstOrFail();

        $daftarRitase = [
            ['tanggal' => '2026-09-18', 'tonase_kg' => 28480, 'tarif_per_ton' => 158760, 'total_angkutan' => 4521485, 'uang_jalan' => 2250000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-17', 'tonase_kg' => 29680, 'tarif_per_ton' => 158760, 'total_angkutan' => 4711997, 'uang_jalan' => 2300000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-16', 'tonase_kg' => 25760, 'tarif_per_ton' => 158760, 'total_angkutan' => 4089658, 'uang_jalan' => 2100000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-14', 'tonase_kg' => 27360, 'tarif_per_ton' => 158760, 'total_angkutan' => 4343674, 'uang_jalan' => 2200000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-13', 'tonase_kg' => 29120, 'tarif_per_ton' => 158760, 'total_angkutan' => 4623091, 'uang_jalan' => 2300000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-12', 'tonase_kg' => 30120, 'tarif_per_ton' => 158760, 'total_angkutan' => 4781851, 'uang_jalan' => 2200000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-10', 'tonase_kg' => 29560, 'tarif_per_ton' => 158760, 'total_angkutan' => 4692946, 'uang_jalan' => 2200000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-08', 'tonase_kg' => 29180, 'tarif_per_ton' => 119880, 'total_angkutan' => 3498098, 'uang_jalan' => 2000000, 'kode_rute' => 'MIP-36'],
            ['tanggal' => '2026-09-08', 'tonase_kg' => 29960, 'tarif_per_ton' => 119880, 'total_angkutan' => 3591605, 'uang_jalan' => 2100000, 'kode_rute' => 'MIP-36'],
            ['tanggal' => '2026-09-07', 'tonase_kg' => 30520, 'tarif_per_ton' => 158760, 'total_angkutan' => 4845355, 'uang_jalan' => 2200000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-05', 'tonase_kg' => 28580, 'tarif_per_ton' => 158760, 'total_angkutan' => 4537361, 'uang_jalan' => 2150000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-02', 'tonase_kg' => 28560, 'tarif_per_ton' => 158760, 'total_angkutan' => 4534186, 'uang_jalan' => 2050000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-09-01', 'tonase_kg' => 30080, 'tarif_per_ton' => 119880, 'total_angkutan' => 3605990, 'uang_jalan' => 1700000, 'kode_rute' => 'MIP-36'],
            ['tanggal' => '2026-08-30', 'tonase_kg' => 30760, 'tarif_per_ton' => 158760, 'total_angkutan' => 4883458, 'uang_jalan' => 2000000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-08-28', 'tonase_kg' => 28520, 'tarif_per_ton' => 153360, 'total_angkutan' => 4373827, 'uang_jalan' => 2050000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-08-27', 'tonase_kg' => 28670, 'tarif_per_ton' => 158760, 'total_angkutan' => 4551649, 'uang_jalan' => 2050000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-08-26', 'tonase_kg' => 31050, 'tarif_per_ton' => 158760, 'total_angkutan' => 4929498, 'uang_jalan' => 2000000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-08-26', 'tonase_kg' => 27820, 'tarif_per_ton' => 153360, 'total_angkutan' => 4266475, 'uang_jalan' => 1900000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-08-24', 'tonase_kg' => 28040, 'tarif_per_ton' => 153360, 'total_angkutan' => 4300214, 'uang_jalan' => 1950000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-08-22', 'tonase_kg' => 29480, 'tarif_per_ton' => 158760, 'total_angkutan' => 4680245, 'uang_jalan' => 2100000, 'kode_rute' => 'MIP-PORT'],
            ['tanggal' => '2026-08-21', 'tonase_kg' => 28440, 'tarif_per_ton' => 158760, 'total_angkutan' => 4515134, 'uang_jalan' => 2050000, 'kode_rute' => 'MIP-PORT'],
        ];

        // Ganti seluruh ritase periode ini agar tidak dobel saat seeder dijalankan ulang.
        Ritase::where('id_periode_gaji', $periodeGaji->id)
            ->where('id_karyawan', $karyawan->id)
            ->delete();

        foreach ($daftarRitase as $dataRitase) {
            $rute = Rute::where('kode', $dataRitase['kode_rute'])->firstOrFail();

            Ritase::create([
                'id_periode_gaji' => $periodeGaji->id,
                'id_karyawan' => $karyawan->id,
                'id_truk' => $truk->id,
                'id_rute' => $rute->id,
                'tanggal_ritase' => $dataRitase['tanggal'],
                'jumlah_rit' => 1,
                'tonase_kg' => $dataRitase['tonase_kg'],
                'volume_liter' => null,
                'tarif_per_ton' => $dataRitase['tarif_per_ton'],
                'total_angkutan' => $dataRitase['total_angkutan'],
                'uang_jalan' => $dataRitase['uang_jalan'],
                'status' => 'terverifikasi',
            ]);
        }

        $rekapGaji = [
            'total_rit' => count($daftarRitase),
            // Slip menampilkan 610 ton (pembulatan). Nilai kg diambil dari jumlah 21 rit.
            'total_tonase_kg' => array_sum(array_column($daftarRitase, 'tonase_kg')),
            'total_angkutan_kotor' => 92877797,
            'potongan_operasional' => 799216,
            'angkutan_bersih' => 92078581,
            'total_uang_jalan' => 43850000,
            'biaya_sparepart' => 6462000,
            'hasil_bersih_armada' => 41766581,
            'persentase_komisi_bp' => 1000,
            'gaji_komisi' => 4176658,
            'total_tunjangan' => 0,
            'potongan_bpjs' => 129000,
            'potongan_kasbon' => 500000,
            'gaji_bersih' => 3547658,
        ];

        Penggajian::updateOrCreate(
            [
                'id_periode_gaji' => $periodeGaji->id,
                'id_karyawan' => $karyawan->id,
            ],
            array_merge($rekapGaji, [
                'id_truk' => $truk->id,
            ]),
        );
    }
}
