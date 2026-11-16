<?php

namespace Tests\Feature\Executive;

use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\User;
use App\Support\ResponInteraksiStandar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Pengujian halaman Pimpinan/Direksi sesuai BRIEF_UNIT_TEST &
 * BRIEF_STANDAR_INTERAKSI_DAN_KEAMANAN:
 *
 * - Halaman tampil HTTP 200 dan memakai data dinamis dari database.
 * - Aksi otorisasi mengembalikan JSON baku (success/type/title/action/...).
 * - Keamanan: akses tanpa sesi, peran salah, serta header noindex.
 * - Performa: tidak ada N+1 pada tabel rincian.
 */
class HalamanPimpinanTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function siapkanPeriodePenggajian(): PeriodeGaji
    {
        $data = $this->siapkanDataMaster();
        $periode = $data['periode'];
        $this->buatRitase($data);

        Penggajian::query()->create([
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
            'total_tunjangan' => 0,
            'potongan_bpjs' => 129000,
            'potongan_kasbon' => 0,
            'gaji_bersih' => 278200,
        ]);

        return $periode->refresh();
    }

    public function test_seluruh_halaman_pimpinan_dapat_di_render(): void
    {
        $periode = $this->siapkanPeriodePenggajian();
        $this->actingAs($this->penggunaPimpinan());

        $halaman = [
            '/executive/dashboard',
            '/executive/dashboard?periode='.$periode->kode,
            '/executive/payroll',
            '/executive/payroll?periode='.$periode->kode,
            '/executive/reports',
            '/executive/reports?periode='.$periode->kode,
            '/executive/trips',
            '/executive/trips?periode='.$periode->kode,
        ];

        foreach ($halaman as $alamat) {
            $this->get($alamat)->assertOk();
        }
    }

    public function test_halaman_pimpinan_memakai_data_dinamis_dari_database(): void
    {
        $periode = $this->siapkanPeriodePenggajian();
        $this->actingAs($this->penggunaPimpinan());

        $penggajian = Penggajian::query()->where('id_periode_gaji', $periode->id)->firstOrFail();
        $namaSupir = $penggajian->karyawan?->nama_lengkap;

        $html = $this->get('/executive/payroll?periode='.$periode->kode)->assertOk()->getContent();

        $this->assertIsString($namaSupir);
        $this->assertStringContainsString(e($namaSupir), $html);

        // Tidak boleh ada penanda data contoh pada halaman Pimpinan.
        $this->assertStringNotContainsString('mark-dummy', $html);
        $this->assertStringNotContainsString('Ir. Hendra Wijaya', $html);
    }

    public function test_tahap_pemilihan_periode_tampil_sebelum_rincian_supir(): void
    {
        $periode = $this->siapkanPeriodePenggajian();
        $this->actingAs($this->penggunaPimpinan());

        // Tanpa ?periode= hanya daftar periode yang tampil.
        $tanpaPeriode = $this->get('/executive/payroll')->assertOk()->getContent();
        $this->assertStringContainsString('Pilih Periode Penggajian', $tanpaPeriode);
        $this->assertStringContainsString($periode->kode, $tanpaPeriode);
        $this->assertStringNotContainsString('ACC Semua yang Valid', $tanpaPeriode);

        // Setelah periode dipilih, rincian supir muncul.
        $denganPeriode = $this->get('/executive/payroll?periode='.$periode->kode)->assertOk()->getContent();
        $this->assertStringContainsString('Permintaan ACC Penggajian dari Admin HRD', $denganPeriode);
    }

    public function test_otorisasi_acc_mengembalikan_json_standar_dan_mengubah_status(): void
    {
        $periode = $this->siapkanPeriodePenggajian();
        $this->actingAs($this->penggunaPimpinan());

        $respon = $this->patchJson('/executive/payroll/'.$periode->kode.'/approve')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', 'success')
            ->assertJsonPath('status', 'COMPLETED');

        $respon->assertJsonStructure([
            'success', 'type', 'title', 'action', 'status', 'message', 'data', 'redirect', 'timestamp',
        ]);

        $this->assertSame('approved', $periode->refresh()->status);
        $this->assertNotNull($periode->tanggal_bayar);
    }

    public function test_penolakan_pimpinan_mengembalikan_periode_ke_draf(): void
    {
        $periode = $this->siapkanPeriodePenggajian();
        $periode->update(['status' => 'pending']);
        $this->actingAs($this->penggunaPimpinan());

        $this->patchJson('/executive/payroll/'.$periode->kode.'/reject', [
            'alasan_penolakan' => 'Nota bengkel unit BE 8639 AAU belum dilampirkan.',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('action', 'EXECUTIVE_REJECT_PAYROLL');

        $this->assertSame('draft', $periode->refresh()->status);
    }

    public function test_halaman_pimpinan_tanpa_sesi_ditolak(): void
    {
        $this->get('/executive/dashboard')->assertRedirect('/login');
        $this->getJson('/executive/dashboard')->assertStatus(401);
        $this->patchJson('/executive/payroll/Jan-26/approve')->assertStatus(401);
    }

    public function test_peran_selain_pimpinan_tidak_boleh_masuk(): void
    {
        $this->siapkanDataMaster();

        $admin = $this->penggunaAdmin();
        $this->actingAs($admin)->get('/executive/payroll')->assertRedirect('/login');
        $this->actingAs($admin)->patchJson('/executive/payroll/JAN-26/approve')->assertStatus(403);

        $supir = $this->penggunaSupir(['email' => 'supir.portal@payroll.test']);
        $this->actingAs($supir)->get('/executive/dashboard')->assertRedirect('/login');
    }

    public function test_header_keamanan_dan_noindex_pada_halaman_pimpinan(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaPimpinan());

        $this->get('/executive/dashboard')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('noindex, nofollow', false);
    }

    public function test_tidak_ada_n_plus_one_pada_halaman_otorisasi(): void
    {
        $periode = $this->siapkanPeriodePenggajian();
        $this->actingAs($this->penggunaPimpinan());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/executive/payroll?periode='.$periode->kode)->assertOk();

        $jumlahKueri = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Pagar longgar namun tetap menangkap ledakan N+1 (puluhan/ratusan query).
        $this->assertLessThan(
            80,
            $jumlahKueri,
            "Terdeteksi potensi N+1: {$jumlahKueri} query pada /executive/payroll."
        );
    }

    public function test_payload_xss_di_catatan_revisi_ter_escape(): void
    {
        $periode = $this->siapkanPeriodePenggajian();
        $this->actingAs($this->penggunaPimpinan());

        $payload = '<script>alert("XSS")</script>';

        $this->patchJson('/executive/payroll/'.$periode->kode.'/reject', [
            'alasan_penolakan' => $payload,
        ])->assertOk();

        $html = $this->get('/executive/dashboard?periode='.$periode->kode)->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $html);
    }

    public function test_metode_http_salah_ditolak(): void
    {
        $periode = $this->siapkanPeriodePenggajian();
        $this->actingAs($this->penggunaPimpinan());

        // Endpoint ACC hanya menerima PATCH.
        $this->getJson('/executive/payroll/'.$periode->kode.'/approve')->assertStatus(405);
    }
}
