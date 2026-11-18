<?php

namespace Tests\Feature\Admin;

use App\Models\Karyawan;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Endpoint AJAX tabel lintas peran: filter/cari/paginasi diproses di server,
 * respons berisi potongan HTML baris siap dipasang, dan URL tidak berubah.
 */
class InteraksiTabelAjaxTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function dataRitase(int $jumlah = 5, string $status = 'terverifikasi'): array
    {
        $data = $this->siapkanDataMaster();

        for ($i = 1; $i <= $jumlah; $i++) {
            Ritase::query()->create([
                'id_periode_gaji' => $data['periode']->id,
                'id_karyawan' => $data['karyawan']->id,
                'id_truk' => $data['truk']->id,
                'id_rute' => $data['rute']->id,
                'tanggal_ritase' => '2026-01-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'jumlah_rit' => 1,
                'tonase_kg' => 25000,
                'tarif_per_ton' => 150000,
                'total_angkutan' => 3750000,
                'uang_jalan' => 200000,
                'status' => $status,
            ]);
        }

        return $data;
    }

    private function ajax(string $url, array $muatan)
    {
        return $this->withHeaders([
            'X-Interaksi-Ajax' => '1',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->postJson($url, $muatan);
    }

    public function test_admin_mendapat_baris_html_dan_meta_halaman(): void
    {
        $this->dataRitase(5);

        $respon = $this->actingAs($this->penggunaAdmin())->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => 'ritase',
            'cari' => '',
            'filter' => [],
            'halaman' => 1,
            'per_halaman' => 25,
        ]);

        $respon->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.entitas', 'ritase')
            ->assertJsonPath('data.meta.total', 5)
            ->assertJsonPath('data.meta.current_page', 1);

        $html = (string) $respon->json('data.rows_html');
        $this->assertStringContainsString('<tr', $html, 'Respons wajib berisi potongan baris <tr>.');
        $this->assertStringContainsString('Pilih Aksi', $html, 'Kolom aksi memakai dropdown bersama.');
    }

    public function test_paginasi_dihitung_di_server(): void
    {
        $this->dataRitase(5);

        $respon = $this->actingAs($this->penggunaAdmin())->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => 'ritase',
            'filter' => [],
            'halaman' => 2,
            'per_halaman' => 2,
        ]);

        $respon->assertOk()
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.last_page', 3)
            ->assertJsonPath('data.meta.from', 3)
            ->assertJsonPath('data.meta.to', 4);
    }

    public function test_pencarian_dan_filter_diproses_di_server(): void
    {
        $data = $this->dataRitase(3);

        // Buat supir & ritase kedua yang tidak boleh muncul saat difilter.
        $supirLain = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-LAIN', 'nama_lengkap' => 'SUPIR LAIN']);
        Ritase::query()->create([
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $supirLain->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-15',
            'jumlah_rit' => 1,
            'tonase_kg' => 20000,
            'tarif_per_ton' => 150000,
            'total_angkutan' => 3000000,
            'uang_jalan' => 150000,
            'status' => 'terverifikasi',
        ]);

        $respon = $this->actingAs($this->penggunaAdmin())->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => 'ritase',
            'cari' => '',
            'filter' => ['supir' => 'NIP-LAIN'],
            'halaman' => 1,
            'per_halaman' => 25,
        ]);

        $respon->assertOk()->assertJsonPath('data.meta.total', 1);

        // Filter status juga harus bekerja di server.
        $responStatus = $this->actingAs($this->penggunaAdmin())->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => 'ritase',
            'filter' => ['status' => 'dibatalkan'],
            'halaman' => 1,
            'per_halaman' => 25,
        ]);

        $responStatus->assertOk()->assertJsonPath('data.meta.total', 0);
    }

    public function test_supir_hanya_melihat_surat_jalan_miliknya(): void
    {
        $data = $this->dataRitase(2);
        $supirLain = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-ORANG-LAIN', 'nama_lengkap' => 'ORANG LAIN']);

        Ritase::query()->create([
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $supirLain->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-20',
            'jumlah_rit' => 1,
            'tonase_kg' => 20000,
            'tarif_per_ton' => 150000,
            'total_angkutan' => 3000000,
            'uang_jalan' => 150000,
            'status' => 'terverifikasi',
        ]);

        // Supir pertama login (dibuat oleh siapkanDataMaster lewat buatSupir).
        $pengguna = \App\Models\User::query()
            ->where('id', $data['karyawan']->id_pengguna)
            ->first();
        $this->assertNotNull($pengguna, 'Akun supir uji harus ada.');

        $respon = $this->actingAs($pengguna)->ajax('/my/interaksi/tabel', [
            'entitas' => 'ritase',
            'filter' => [],
            'halaman' => 1,
            'per_halaman' => 25,
        ]);

        $respon->assertOk();
        $this->assertSame(2, $respon->json('data.meta.total'), 'Supir hanya boleh melihat 2 surat jalan miliknya, bukan 3.');
    }

    public function test_supir_tidak_boleh_membaca_tabel_master(): void
    {
        $data = $this->siapkanDataMaster();

        $pengguna = \App\Models\User::query()->findOrFail($data['karyawan']->id_pengguna);

        $this->actingAs($pengguna)->ajax('/my/interaksi/tabel', [
            'entitas' => 'karyawan',
            'filter' => [],
        ])->assertStatus(403);
    }

    public function test_pimpinan_boleh_membaca_tetapi_admin_tetap_terpisah(): void
    {
        $data = $this->dataRitase(2);

        $this->actingAs($this->penggunaPimpinan())->ajax('/executive/interaksi/tabel', [
            'entitas' => 'ritase',
            'filter' => [],
            'halaman' => 1,
            'per_halaman' => 25,
        ])->assertOk()->assertJsonPath('data.meta.total', 2);

        // Supir tidak boleh memakai endpoint admin (ditolak).
        $penggunaSupir = \App\Models\User::query()->findOrFail($data['karyawan']->id_pengguna);
        $statusTolak = $this->actingAs($penggunaSupir)->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => 'ritase',
            'filter' => [],
        ])->getStatusCode();

        $this->assertContains($statusTolak, [302, 403], 'Endpoint admin wajib menolak peran supir.');
    }

    public function test_kolom_urut_liar_tidak_menyebabkan_error_sql(): void
    {
        $this->dataRitase(2);

        $this->actingAs($this->penggunaAdmin())->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => 'ritase',
            'filter' => [],
            'sort_by' => 'gaji_bersih; DROP TABLE ritase;--',
            'sort_dir' => 'DESC',
            'halaman' => 1,
            'per_halaman' => 25,
        ])->assertOk();

        // Tabel harus tetap ada.
        $this->assertSame(2, Ritase::query()->count());
    }

    public function test_entitas_tidak_dikenal_ditolak(): void
    {
        $this->siapkanDataMaster();

        $this->actingAs($this->penggunaAdmin())->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => 'tabel_rahasia',
            'filter' => [],
        ])->assertStatus(403);
    }

    public function test_url_halaman_tidak_berubah_setelah_filter(): void
    {
        $this->dataRitase(3);

        // Halaman index tetap dirender SSR tanpa query string.
        $respon = $this->actingAs($this->penggunaAdmin())->get('/admin/operational/trips');
        $respon->assertOk();

        // Filter tidak lagi memakai form GET (data-filter-server).
        $this->assertStringNotContainsString('data-filter-server', (string) $respon->getContent());
        // Komponen AJAX wajib terpasang.
        $this->assertStringContainsString('tabelAjaxServer', (string) $respon->getContent());
    }
}
