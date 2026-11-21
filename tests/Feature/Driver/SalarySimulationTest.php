<?php

namespace Tests\Feature\Driver;

use App\Models\PeriodeGaji;
use App\Models\Rute;
use App\Models\SimulasiGaji;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SalarySimulationTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function bikinRute(array $atribut = []): Rute
    {
        return Rute::create(array_merge([
            'kode' => 'RTE-'.rand(100, 999),
            'material' => 'BATUBARA',
            'tarif_per_ton' => 150000,
            'uang_jalan_default' => 2000000,
            'jarak_km' => 200,
            'alamat_asal' => 'LAHAT',
            'alamat_tujuan' => 'PALEMBANG',
            'status' => 'aktif',
        ], $atribut));
    }

    public function test_supir_dapat_mengakses_halaman_simulasi_gaji(): void
    {
        $karyawan = $this->buatSupir();

        $respon = $this->actingAs($karyawan->pengguna)->get('/my/simulation');

        $respon->assertOk();
        $respon->assertSee('Simulasi Perhitungan Gaji Bersih');
        $respon->assertSee('Daftar Surat Jalan Simulasi');
        $respon->assertSee('Estimasi Gaji Bersih');
    }

    public function test_supir_dapat_menyimpan_simulasi_gaji(): void
    {
        $karyawan = $this->buatSupir();
        $rute = $this->bikinRute();
        $periode = PeriodeGaji::query()->create([
            'kode' => 'Nov-26',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'draft',
        ]);

        $payload = [
            'nama_simulasi' => 'Target 5 Rit Pengantaran',
            'id_periode_gaji' => $periode->id,
            'mode_potongan' => 'manual',
            'biaya_sparepart' => 500000,
            'potongan_bpjs' => 129000,
            'potongan_kasbon' => 200000,
            'potongan_lain' => 0,
            'surat_jalan' => [
                [
                    'rute_id' => $rute->id,
                    'nama_rute' => $rute->kode,
                    'tonase_ton' => 28.50,
                    'tarif_per_ton' => 150000,
                    'uang_jalan' => 2000000,
                    'tanggal' => '2026-11-05',
                ],
                [
                    'rute_id' => $rute->id,
                    'nama_rute' => $rute->kode,
                    'tonase_ton' => 29.50,
                    'tarif_per_ton' => 150000,
                    'uang_jalan' => 2000000,
                    'tanggal' => '2026-11-06',
                ],
            ],
        ];

        $respon = $this->actingAs($karyawan->pengguna)
            ->post('/my/simulation', $payload, [
                'Accept' => 'application/json',
                'X-Interaksi-Ajax' => '1',
            ]);

        $respon->assertOk();
        $respon->assertJsonPath('success', true);
        $respon->assertJsonPath('action', 'SAVE_SALARY_SIMULATION');

        $this->assertDatabaseHas('simulasi_gaji', [
            'id_karyawan' => $karyawan->id,
            'nama_simulasi' => 'TARGET 5 RIT PENGANTARAN',
            'total_rit' => 2,
            'mode_potongan' => 'manual',
        ]);
    }

    public function test_simulasi_lebih_dari_31_surat_jalan_ditolak_validasi(): void
    {
        $karyawan = $this->buatSupir();
        $rute = $this->bikinRute();

        $suratJalan = [];
        for ($i = 1; $i <= 32; $i++) {
            $suratJalan[] = [
                'rute_id' => $rute->id,
                'nama_rute' => 'Rute '.$i,
                'tonase_ton' => 25.00,
                'tarif_per_ton' => 150000,
                'uang_jalan' => 2000000,
            ];
        }

        $respon = $this->actingAs($karyawan->pengguna)
            ->postJson('/my/simulation', [
                'nama_simulasi' => 'Simulasi Melampaui Batas',
                'mode_potongan' => 'riil',
                'surat_jalan' => $suratJalan,
            ]);

        $respon->assertStatus(422);
        $respon->assertJsonValidationErrors(['surat_jalan']);
    }

    public function test_supir_dapat_menghapus_simulasi_miliknya(): void
    {
        $karyawan = $this->buatSupir();

        $simulasi = SimulasiGaji::create([
            'id_karyawan' => $karyawan->id,
            'nama_simulasi' => 'SIMULASI DIHAPUS',
            'data_surat_jalan' => [],
            'total_rit' => 1,
            'total_tonase_kg' => 25000,
            'total_angkutan_kotor' => 3750000,
            'total_uang_jalan' => 2000000,
            'biaya_sparepart' => 0,
            'gaji_komisi' => 175000,
            'gaji_bersih' => 175000,
        ]);

        $respon = $this->actingAs($karyawan->pengguna)
            ->deleteJson('/my/simulation/'.$simulasi->id, [], [
                'X-Interaksi-Ajax' => '1',
            ]);

        $respon->assertOk();
        $respon->assertJsonPath('action', 'DELETE_SALARY_SIMULATION');

        $this->assertSoftDeleted('simulasi_gaji', ['id' => $simulasi->id]);
    }

    public function test_supir_lain_tidak_boleh_menghapus_simulasi_orang_lain(): void
    {
        $karyawan1 = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-TEST-001', 'nama_lengkap' => 'SUPIR SATU']);
        $karyawan2 = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-TEST-002', 'nama_lengkap' => 'SUPIR DUA']);

        $simulasi = SimulasiGaji::create([
            'id_karyawan' => $karyawan1->id,
            'nama_simulasi' => 'SIMULASI MILIK SATU',
            'data_surat_jalan' => [],
        ]);

        $respon = $this->actingAs($karyawan2->pengguna)
            ->delete('/my/simulation/'.$simulasi->id);

        $respon->assertStatus(403);
        $this->assertDatabaseHas('simulasi_gaji', ['id' => $simulasi->id, 'deleted_at' => null]);
    }

    public function test_peran_admin_dan_pimpinan_ditolak_mengakses_simulasi_supir(): void
    {
        $admin = $this->penggunaAdmin();
        $pimpinan = $this->penggunaPimpinan();

        $this->actingAs($admin)->getJson('/my/simulation')->assertStatus(403);
        $this->actingAs($pimpinan)->getJson('/my/simulation')->assertStatus(403);
    }
}
