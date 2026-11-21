<?php

namespace Tests\Feature\Executive;

use App\Mail\SlipGajiDicairkanMail;
use App\Models\Penggajian;
use App\Services\PengirimWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Notifikasi pencairan gaji ke supir (email SMTP + WhatsApp Fonnte).
 *
 * Yang diuji hanya perilaku yang bisa dilihat pengguna:
 * - Saat Pimpinan mencairkan gaji, email slip terkirim ke alamat supir.
 * - Saat FONNTE_TOKEN terisi, pesan WhatsApp dikirim ke nomor supir.
 * - Saat token kosong, fitur WA dilewati tanpa menggagalkan pencairan.
 */
class NotifikasiPencairanGajiTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    /**
     * @return array{periode: \App\Models\PeriodeGaji, penggajian: Penggajian}
     */
    private function siapkanGajiDisetujui(): array
    {
        $data = $this->siapkanDataMaster();
        $periode = $data['periode'];
        $periode->update(['status' => 'approved']);

        $penggunaSupir = $data['karyawan']->pengguna;
        $penggunaSupir->update([
            'email' => 'supir.notifikasi@payroll.test',
            'nomor_telepon' => '+6281234567890',
        ]);

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
            'status' => 'approved',
        ]);

        return ['periode' => $periode->refresh(), 'penggajian' => $penggajian];
    }

    public function test_pencairan_gaji_mengirim_email_slip_ke_supir(): void
    {
        Mail::fake();
        Http::fake();

        ['periode' => $periode, 'penggajian' => $penggajian] = $this->siapkanGajiDisetujui();
        $this->actingAs($this->penggunaPimpinan());

        $this->kirimAjax('PATCH', '/executive/payroll/'.$periode->kode.'/disburse', [
            'ids' => [$penggajian->id],
        ])->assertOk()
            ->assertJsonPath('action', 'EXECUTIVE_DISBURSE_PAYROLL')
            ->assertJsonPath('data.notifikasi.email_terkirim', 1);

        $this->assertSame('paid', $penggajian->fresh()->status);

        Mail::assertSent(SlipGajiDicairkanMail::class, function (SlipGajiDicairkanMail $surat): bool {
            return $surat->hasTo('supir.notifikasi@payroll.test')
                && $surat->rincian['gaji_bersih'] === 278200
                && str_contains($surat->envelope()->subject, 'Sudah Dicairkan');
        });
    }

    public function test_pencairan_gaji_mengirim_whatsapp_fonnte_bila_token_terisi(): void
    {
        Mail::fake();
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success'], 200)]);

        config([
            'services.fonnte.token' => 'token-uji-fonnte',
            'services.fonnte.aktif' => true,
        ]);

        ['periode' => $periode, 'penggajian' => $penggajian] = $this->siapkanGajiDisetujui();
        $this->actingAs($this->penggunaPimpinan());

        $this->kirimAjax('PATCH', '/executive/payroll/'.$periode->kode.'/disburse', [
            'ids' => [$penggajian->id],
        ])->assertOk()->assertJsonPath('data.notifikasi.wa_terkirim', 1);

        Http::assertSent(function ($permintaan): bool {
            return $permintaan->url() === 'https://api.fonnte.com/send'
                && $permintaan['target'] === '6281234567890'
                && $permintaan->hasHeader('Authorization', 'token-uji-fonnte')
                && str_contains((string) $permintaan['message'], '278.200');
        });
    }

    public function test_token_fonnte_kosong_tidak_menggagalkan_pencairan(): void
    {
        Mail::fake();
        Http::fake();

        config([
            'services.fonnte.token' => '',
            'services.fonnte.aktif' => true,
        ]);

        ['periode' => $periode, 'penggajian' => $penggajian] = $this->siapkanGajiDisetujui();
        $this->actingAs($this->penggunaPimpinan());

        $this->kirimAjax('PATCH', '/executive/payroll/'.$periode->kode.'/disburse', [
            'ids' => [$penggajian->id],
        ])->assertOk()->assertJsonPath('data.notifikasi.wa_terkirim', 0);

        $this->assertSame('paid', $penggajian->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_gagal_kirim_whatsapp_tidak_membatalkan_pencairan(): void
    {
        Mail::fake();
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'device disconnected'], 200)]);

        config([
            'services.fonnte.token' => 'token-uji-fonnte',
            'services.fonnte.aktif' => true,
        ]);

        ['periode' => $periode, 'penggajian' => $penggajian] = $this->siapkanGajiDisetujui();
        $this->actingAs($this->penggunaPimpinan());

        $this->kirimAjax('PATCH', '/executive/payroll/'.$periode->kode.'/disburse', [
            'ids' => [$penggajian->id],
        ])->assertOk()->assertJsonPath('data.notifikasi.wa_terkirim', 0);

        $this->assertSame('paid', $penggajian->fresh()->status);
        $this->assertSame('paid', $periode->fresh()->status);
    }

    public function test_nomor_telepon_dinormalisasi_ke_format_fonnte(): void
    {
        $layanan = app(PengirimWhatsAppService::class);

        $this->assertSame('6281234567890', $layanan->nomorUntukFonnte('0812-3456-7890'));
        $this->assertSame('6281234567890', $layanan->nomorUntukFonnte('+62 812 3456 7890'));
        $this->assertNull($layanan->nomorUntukFonnte(null));
        $this->assertNull($layanan->nomorUntukFonnte('   '));
    }
}
