<?php

namespace Tests\Feature\Admin;

use App\Models\PeriodeGaji;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Sesuai docs/ACTIVITY_DIAGRAM.drawio: ACC penggajian adalah kewenangan
 * Pimpinan/Direksi. Admin/HRD hanya boleh mengajukan dan menarik pengajuan.
 */
class OtorisasiPayrollAdminTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function periodeDraft(): PeriodeGaji
    {
        $this->siapkanDataMaster();

        return PeriodeGaji::query()->orderByDesc('id')->firstOrFail();
    }

    public function test_admin_tidak_boleh_memberikan_acc(): void
    {
        $periode = $this->periodeDraft();
        $this->actingAs($this->penggunaAdmin());

        $this->postJson('/admin/payroll/'.$periode->kode.'/approval', [
            'mode' => 'approve',
        ])->assertStatus(422)->assertJsonPath('success', false);

        // Status tetap draft: ACC tidak pernah terjadi.
        $this->assertSame('draft', $periode->refresh()->status);
    }

    public function test_admin_boleh_mengajukan_ke_pimpinan(): void
    {
        $data = $this->siapkanDataMaster();
        $periode = $data['periode'];
        $this->buatRitase($data);
        $this->actingAs($this->penggunaAdmin());

        $this->postJson('/admin/payroll/'.$periode->kode.'/calculate')->assertOk();

        $this->patchJson('/admin/payroll/'.$periode->kode.'/submit')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('pending', $periode->refresh()->status);
    }

    public function test_admin_boleh_menarik_pengajuan(): void
    {
        $periode = $this->periodeDraft();
        $periode->update(['status' => 'pending']);
        $this->actingAs($this->penggunaAdmin());

        $this->postJson('/admin/payroll/'.$periode->kode.'/approval', [
            'mode' => 'reject',
            'alasan_penolakan' => 'Nota bengkel belum lengkap.',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('draft', $periode->refresh()->status);
    }

    public function test_halaman_payroll_admin_tidak_menampilkan_tombol_acc(): void
    {
        $periode = $this->periodeDraft();
        $this->actingAs($this->penggunaAdmin());

        $html = $this->get('/admin/payroll?periode='.$periode->kode)->assertOk()->getContent();

        $this->assertStringNotContainsString('ACC Penggajian', $html);
        $this->assertStringContainsString('Ajukan ke Pimpinan', $html);
    }
}
