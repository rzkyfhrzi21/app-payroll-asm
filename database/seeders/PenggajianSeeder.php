<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Truk;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

class PenggajianSeeder extends Seeder
{
    /**
     * Mengisi snapshot penggajian dari data contoh.
     * Angka diturunkan mengikuti rumus ERD:
     * kotor -> bersih -> hasil bersih armada -> gaji komisi -> gaji bersih.
     */
    public function run(): void
    {
        $periodeGaji = PeriodeGaji::where('kode', 'Jan-26')->firstOrFail();

        foreach (DemoData::payrollRows() as $dataGaji) {
            $karyawan = Karyawan::where('nama_lengkap', $dataGaji['name'])->first();
            if ($karyawan === null) {
                continue;
            }

            $truk = Truk::firstOrCreate(
                ['nomor_polisi' => $dataGaji['plate']],
                [
                    'merek_model' => $dataGaji['truck'],
                ],
            );

            $hasilBersihArmada = (int) $dataGaji['net_result'];
            $totalUangJalan = (int) $dataGaji['trip_money'];
            $persentaseKomisiBp = (int) $karyawan->persentase_komisi_bp;
            $gajiKomisi = (int) round($hasilBersihArmada * $persentaseKomisiBp / 10000);
            $potonganBpjs = (int) $karyawan->potongan_bpjs_bulanan;
            $potonganKasbon = max(0, (int) $dataGaji['deduction'] - $potonganBpjs);
            $totalAngkutanKotor = $hasilBersihArmada + $totalUangJalan;

            Penggajian::updateOrCreate(
                [
                    'id_periode_gaji' => $periodeGaji->id,
                    'id_karyawan' => $karyawan->id,
                ],
                [
                    'id_truk' => $truk->id,
                    'total_rit' => (int) $dataGaji['rit'],
                    'total_tonase_kg' => (int) round((float) str_replace(',', '.', $dataGaji['tonnage']) * 1000),
                    'total_angkutan_kotor' => $totalAngkutanKotor,
                    'potongan_operasional' => 0,
                    'angkutan_bersih' => $totalAngkutanKotor,
                    'total_uang_jalan' => $totalUangJalan,
                    'biaya_sparepart' => 0,
                    'hasil_bersih_armada' => $hasilBersihArmada,
                    'persentase_komisi_bp' => $persentaseKomisiBp,
                    'gaji_komisi' => $gajiKomisi,
                    'total_tunjangan' => 0,
                    'potongan_bpjs' => $potonganBpjs,
                    'potongan_kasbon' => $potonganKasbon,
                    'gaji_bersih' => $gajiKomisi - $potonganBpjs - $potonganKasbon,
                ],
            );
        }
    }
}
