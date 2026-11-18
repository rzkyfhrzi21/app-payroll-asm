<?php

namespace Tests\Feature\Admin;

use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Services\PeriodeGajiLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Uji endpoint Admin untuk tutup buku & pengajuan ke Pimpinan.
 *
 * Aturan otorisasi: Admin hanya boleh MENGAJUKAN (draft -> pending).
 * Status `approved` tetap eksklusif milik Pimpinan.
 */
class TutupBukuPayrollTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function periodeBerjalanDenganSatuSJ(string $statusSj = 'terverifikasi'): PeriodeGaji
    {
        $data = $this->siapkanDataMaster();

        $periode = app(PeriodeGajiLifecycleService::class)->bukaPeriodeBaru(Carbon::parse('2026-10-21'));

        Ritase::query()->create([
            'id_periode_gaji' => $periode->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-10-25',
            'jumlah_rit' => 2,
            'tonase_kg' => 28480,
            'tarif_per_ton' => 150000,
            'total_angkutan' => 4272000,
            'uang_jalan' => 4400000,
            'status' => $statusSj,
        ]);

        return $periode;
    }

    public function test_tutup_buku_mengubah_status_menjadi_pending_bukan_approved(): void
    {
        $periode = $this->periodeBerjalanDenganSatuSJ();

        $this->actingAs($this->penggunaAdmin())
            ->patchJson("/admin/payroll/{$periode->kode}/tutup-buku")
            ->assertOk()
            ->assertJsonPath('success', true);

        $periode->refresh();

        // Admin hanya boleh MENGAJUKAN; ACC tetap wewenang Pimpinan.
        $this->assertSame('pending', $periode->status);
        $this->assertNotNull($periode->tanggal_selesai, 'Tutup buku wajib mengisi tanggal_selesai.');
        $this->assertNull($periode->disetujui_pada, 'Admin tidak boleh mengisi jejak persetujuan.');
    }

    public function test_tutup_buku_membuka_periode_berikutnya(): void
    {
        $periode = $this->periodeBerjalanDenganSatuSJ();

        $this->actingAs($this->penggunaAdmin())
            ->patchJson("/admin/payroll/{$periode->kode}/tutup-buku")
            ->assertOk();

        $berikutnya = app(PeriodeGajiLifecycleService::class)->ambilPeriodeBerjalan();

        $this->assertNotNull($berikutnya, 'Tutup buku wajib membuat periode baru.');
        $this->assertSame(
            $periode->fresh()->tanggal_selesai->copy()->addDay()->toDateString(),
            $berikutnya->tanggal_mulai->toDateString(),
        );
    }

    public function test_tutup_buku_diblokir_bila_masih_ada_surat_jalan_belum_terverifikasi(): void
    {
        $periode = $this->periodeBerjalanDenganSatuSJ('menunggu_verifikasi');

        $this->actingAs($this->penggunaAdmin())
            ->patchJson("/admin/payroll/{$periode->kode}/tutup-buku")
            ->assertStatus(422);

        $this->assertSame('draft', $periode->fresh()->status);
        $this->assertNull($periode->fresh()->tanggal_selesai, 'Periode tidak boleh tertutup saat verifikasi belum tuntas.');
    }

    public function test_buka_periode_baru_ditolak_bila_masih_ada_periode_berjalan(): void
    {
        $this->periodeBerjalanDenganSatuSJ();

        $this->actingAs($this->penggunaAdmin())
            ->postJson('/admin/payroll/periode-baru')
            ->assertStatus(422);

        $this->assertSame(1, PeriodeGaji::query()->whereNull('tanggal_selesai')->count());
    }

    public function test_hanya_admin_yang_boleh_tutup_buku(): void
    {
        $periode = $this->periodeBerjalanDenganSatuSJ();

        $this->actingAs($this->penggunaPimpinan())
            ->patchJson("/admin/payroll/{$periode->kode}/tutup-buku")
            ->assertStatus(403);

        $this->assertSame('draft', $periode->fresh()->status);
    }

    public function test_pimpinan_tidak_bisa_menutup_buku_lewat_akun_admin(): void
    {
        $periode = $this->periodeBerjalanDenganSatuSJ();

        $this->actingAs($this->penggunaSupir())
            ->patchJson("/admin/payroll/{$periode->kode}/tutup-buku")
            ->assertStatus(403);
    }
}
