<?php

namespace Tests\Feature\Admin;

use App\Models\Truk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * BRIEF_UNIT_TEST.md Bab 9 (Noindex & header keamanan), Bab 7 (POST only),
 * serta verifikasi render seluruh halaman admin sebagai halaman utuh (HTTP 200).
 */
class HalamanAdminTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    /**
     * Seluruh halaman admin wajib dapat dibuka Admin HRD.
     *
     * @return array<string, array{0: string}>
     */
    public static function daftarHalamanAdmin(): array
    {
        return [
            'dashboard' => ['/admin/dashboard'],
            'dashboard dengan periode' => ['/admin/dashboard?periode=JAN-26'],
            'payroll' => ['/admin/payroll'],
            'payroll dengan periode' => ['/admin/payroll?periode=JAN-26'],
            'master supir' => ['/admin/master/employees'],
            'master armada' => ['/admin/master/trucks'],
            'master rute' => ['/admin/master/routes'],
            'operasional surat jalan' => ['/admin/operational/trips'],
            'operasional biaya' => ['/admin/operational/expenses'],
            'operasional kasbon' => ['/admin/operational/loans'],
            'laporan arsip' => ['/admin/reports'],
        ];
    }

    #[DataProvider('daftarHalamanAdmin')]
    public function test_halaman_admin_dapat_dirender_tanpa_galat(string $alamat): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $respon = $this->get($alamat);

        $respon->assertOk();
        $respon->assertSee('noindex', false);
        $respon->assertHeader('X-Content-Type-Options', 'nosniff');
        $respon->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respon->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_seluruh_halaman_admin_memakai_sidebar_dan_toast_stack(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $html = $this->get('/admin/master/employees')->getContent();

        $this->assertStringContainsString('admin_sidebar_collapsed', $html);
        $this->assertStringContainsString('toastStackManager', $html);
        $this->assertStringContainsString('lightboxPreviewManager', $html);
    }

    /**
     * BRIEF Bab 7.1: setiap tabel daftar wajib punya kolom "No" paling kiri
     * dan tombol Detail di samping aksi lain.
     */
    public function test_tabel_master_memiliki_kolom_no_dan_tombol_detail(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        foreach (['/admin/master/employees', '/admin/master/trucks', '/admin/master/routes', '/admin/operational/loans'] as $alamat) {
            $html = $this->get($alamat)->assertOk()->getContent();

            $this->assertStringContainsString('>No<', $html, "Kolom No tidak ditemukan pada {$alamat}.");
            $this->assertStringContainsString('Detail', $html, "Tombol Detail tidak ditemukan pada {$alamat}.");
        }
    }

    /**
     * BRIEF Bab 4: seluruh form mutasi wajib membawa token CSRF.
     */
    public function test_form_mutasi_membawa_token_csrf_dan_modal_standar(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $html = $this->get('/admin/master/trucks')->getContent();

        $this->assertStringContainsString('_token', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('modalTrukDetail', $html);
    }

    /**
     * BRIEF Bab 3.3: input pencarian memakai debounce (Alpine, atau 300ms pada
     * komponen tabel AJAX), dan tombol Reset Filter selalu tersedia.
     */
    public function test_pencarian_memakai_debounce_dan_tombol_reset_filter(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        foreach (['/admin/master/employees', '/admin/operational/trips', '/admin/reports'] as $alamat) {
            $html = $this->get($alamat)->assertOk()->getContent();

            $memakaiDebounce = str_contains($html, 'debounce.300ms')
                || str_contains($html, 'data-filter-debounce')
                || str_contains($html, 'tabelAjaxServer');

            $this->assertTrue($memakaiDebounce, "Debounce pencarian tidak ada pada {$alamat}.");

            // Reset Filter muncul otomatis ketika ada filter aktif (AGENTS.md 3.7).
            $htmlDenganFilter = $this->get($alamat.'?cari=BUDI')->assertOk()->getContent();
            $this->assertStringContainsString('Reset Filter', $htmlDenganFilter, "Tombol Reset Filter tidak muncul saat filter aktif pada {$alamat}.");
        }
    }

    /**
     * Halaman detail supir dipanggil lewat AJAX dan harus mengembalikan JSON.
     */
    public function test_detail_supir_mengembalikan_json_ringkasan(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('GET', '/admin/master/employees/'.$data['karyawan']->id.'/detail')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['ringkasan', 'pengeluaran', 'kasbon'],
            ]);
    }

    /**
     * Endpoint tabel hanya menerima AJAX POST (BRIEF Bab 7.1).
     */
    public function test_endpoint_tabel_menolak_get(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $respon = $this->get('/admin/interaksi/tabel');

        $this->assertContains($respon->getStatusCode(), [405, 404]);
    }

    /**
     * Data master yang masih terikat transaksi tidak boleh dihapus (AGENTS.md 3.5).
     */
    public function test_penghapusan_master_terikat_ditolak_server_side(): void
    {
        $data = $this->siapkanDataMaster();
        $this->buatRitase($data);
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('DELETE', '/admin/master/employees/'.$data['karyawan']->id)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('action', 'DELETE_KARYAWAN_LOCKED');

        $this->kirimAjax('DELETE', '/admin/master/trucks/'.$data['truk']->id)
            ->assertStatus(422)
            ->assertJsonPath('action', 'DELETE_TRUK_LOCKED');

        $this->kirimAjax('DELETE', '/admin/master/routes/'.$data['rute']->id)
            ->assertStatus(422)
            ->assertJsonPath('action', 'DELETE_RUTE_LOCKED');

        $this->kirimAjax('DELETE', '/admin/master/jenis-truk/'.$data['jenisTruk']->id)
            ->assertStatus(422)
            ->assertJsonPath('action', 'DELETE_JENIS_TRUK_LOCKED');

        $this->assertNotNull(Truk::query()->find($data['truk']->id));
    }

    /**
     * Nonaktifkan adalah alternatif sah ketika hapus ditolak.
     */
    public function test_nonaktifkan_master_mengubah_status(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('PATCH', '/admin/master/trucks/'.$data['truk']->id.'/status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'nonaktif');

        $this->assertSame('nonaktif', $data['truk']->fresh()->status);
    }
}
