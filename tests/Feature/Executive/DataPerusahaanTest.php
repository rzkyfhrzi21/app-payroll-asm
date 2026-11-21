<?php

namespace Tests\Feature\Executive;

use App\Models\DataPerusahaan;
use App\Models\Penggajian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class DataPerusahaanTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    public function test_pimpinan_dapat_membuka_halaman_profil_perusahaan_dan_memperbarui_data(): void
    {
        $pimpinan = $this->penggunaPimpinan();

        $this->actingAs($pimpinan)
            ->get('/executive/company')
            ->assertOk()
            ->assertSee('Profil Perusahaan')
            ->assertSee('Preview Kop Surat');

        $respon = $this->actingAs($pimpinan)->patch('/executive/company', [
            'nama_perusahaan' => 'pt nusantara batubara jaya',
            'kode_perusahaan' => 'nbj',
            'alamat' => 'jl. sudirman no 88, muara enim',
            'kota' => 'muara enim',
            'no_telepon' => '0734-421000',
            'email' => 'Finance@NusantaraBatubara.co.id',
            'nama_pemilik' => 'h. rahmat hidayat',
            'jabatan_pemilik' => 'komisaris utama',
            'nama_bank' => 'bank mandiri',
            'no_rekening' => '1120009988776',
        ]);

        $respon->assertRedirect(route('executive.company.edit'));

        $perusahaan = DataPerusahaan::query()->firstOrFail();

        $this->assertSame('PT NUSANTARA BATUBARA JAYA', $perusahaan->nama_perusahaan);
        $this->assertSame('NBJ', $perusahaan->kode_perusahaan);
        $this->assertSame('JL. SUDIRMAN NO 88, MUARA ENIM', $perusahaan->alamat);
        $this->assertSame('MUARA ENIM', $perusahaan->kota);
        $this->assertSame('0734-421000', $perusahaan->no_telepon);
        $this->assertSame('Finance@NusantaraBatubara.co.id', $perusahaan->email);
        $this->assertSame('H. RAHMAT HIDAYAT', $perusahaan->nama_pemilik);
        $this->assertSame('KOMISARIS UTAMA', $perusahaan->jabatan_pemilik);
        $this->assertSame('BANK MANDIRI', $perusahaan->nama_bank);
        $this->assertSame('1120009988776', $perusahaan->no_rekening);
        $this->assertSame(1, DataPerusahaan::query()->count());
    }

    public function test_peran_admin_dan_supir_ditolak_mengakses_profil_perusahaan(): void
    {
        $admin = $this->penggunaAdmin();
        $supir = $this->penggunaSupir(['email' => 'supir.company@payroll.test']);

        $this->actingAs($admin)->get('/executive/company')->assertRedirect('/login');
        $this->actingAs($admin)->getJson('/executive/company')->assertStatus(403);
        $this->actingAs($admin)->patch('/executive/company', [
            'nama_perusahaan' => 'PT TIDAK SAH',
            'nama_pemilik' => 'BUKAN PIMPINAN',
        ])->assertRedirect('/login');
        $this->actingAs($admin)->patchJson('/executive/company', [
            'nama_perusahaan' => 'PT TIDAK SAH',
            'nama_pemilik' => 'BUKAN PIMPINAN',
        ])->assertStatus(403);

        $this->actingAs($supir)->get('/executive/company')->assertRedirect('/login');
        $this->actingAs($supir)->getJson('/executive/company')->assertStatus(403);
        $this->actingAs($supir)->patch('/executive/company', [
            'nama_perusahaan' => 'PT TIDAK SAH',
            'nama_pemilik' => 'BUKAN PIMPINAN',
        ])->assertRedirect('/login');
        $this->actingAs($supir)->patchJson('/executive/company', [
            'nama_perusahaan' => 'PT TIDAK SAH',
            'nama_pemilik' => 'BUKAN PIMPINAN',
        ])->assertStatus(403);
    }

    public function test_validasi_gagal_bila_nama_perusahaan_atau_nama_pemilik_kosong(): void
    {
        $pimpinan = $this->penggunaPimpinan();

        $this->actingAs($pimpinan)
            ->from('/executive/company')
            ->patch('/executive/company', [
                'nama_perusahaan' => '',
                'nama_pemilik' => '',
            ])
            ->assertRedirect('/executive/company')
            ->assertSessionHasErrors(['nama_perusahaan', 'nama_pemilik']);

        $this->actingAs($pimpinan)
            ->patchJson('/executive/company', [
                'nama_perusahaan' => '   ',
                'nama_pemilik' => '   ',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nama_perusahaan', 'nama_pemilik']);
    }

    public function test_tanggal_berdiri_kosong_tidak_menggagalkan_penyimpanan(): void
    {
        $pimpinan = $this->penggunaPimpinan();

        // Browser mengirim string kosong untuk input <input type="date"> yang dibiarkan kosong.
        // Kolom DATE MySQL menolak '' sehingga dulu memicu QueryException (simpan gagal).
        $respon = $this->actingAs($pimpinan)->patch('/executive/company', [
            'nama_perusahaan' => 'pt contoh tanggal kosong',
            'nama_pemilik' => 'pimpinan contoh',
            'tanggal_berdiri' => '',
        ]);

        $respon->assertRedirect(route('executive.company.edit'));
        $respon->assertSessionHasNoErrors();

        $perusahaan = DataPerusahaan::query()->firstOrFail();
        $this->assertSame('PT CONTOH TANGGAL KOSONG', $perusahaan->nama_perusahaan);
        $this->assertNull($perusahaan->tanggal_berdiri);
    }

    public function test_slip_gaji_dan_laporan_menampilkan_data_perusahaan_dari_database(): void
    {
        DataPerusahaan::query()->create([
            'nama_perusahaan' => 'pt sriwijaya angkutan energi',
            'kode_perusahaan' => 'sae',
            'alamat' => 'jl. ahmad yani no 10',
            'kota' => 'lahat',
            'no_telepon' => '0731-321000',
            'email' => 'hrd@sae-coal.test',
            'nama_pemilik' => 'ir. hendra gunawan',
            'jabatan_pemilik' => 'direktur operasional',
            'nama_bank' => 'bank bca',
            'no_rekening' => '0219988771',
        ]);

        $data = $this->siapkanDataMaster();
        $periode = $data['periode'];
        $this->buatRitase($data);

        $penggajian = Penggajian::query()->create([
            'id_periode_gaji' => $periode->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'total_rit' => 2,
            'total_tonase_kg' => 28480,
            'total_angkutan_kotor' => 4272000,
            'potongan_operasional' => 0,
            'angkutan_bersih' => 4272000,
            'total_uang_jalan' => 200000,
            'biaya_sparepart' => 0,
            'hasil_bersih_armada' => 4072000,
            'persentase_komisi_bp' => 1000,
            'gaji_komisi' => 407200,
            'potongan_bpjs' => 129000,
            'potongan_kasbon' => 0,
            'gaji_bersih' => 278200,
        ]);

        $admin = $this->penggunaAdmin();

        $this->actingAs($admin)
            ->get("/admin/reports/{$periode->kode}/slip/{$penggajian->id}")
            ->assertOk()
            ->assertSee('PT SRIWIJAYA ANGKUTAN ENERGI')
            ->assertSee('IR. HENDRA GUNAWAN')
            ->assertSee('DIREKTUR OPERASIONAL');

        $this->actingAs($admin)
            ->get("/admin/reports/{$periode->kode}/cetak")
            ->assertOk()
            ->assertSee('PT SRIWIJAYA ANGKUTAN ENERGI')
            ->assertSee('IR. HENDRA GUNAWAN');

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('PT SRIWIJAYA ANGKUTAN ENERGI');
    }
}
