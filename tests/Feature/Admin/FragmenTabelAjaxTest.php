<?php

namespace Tests\Feature\Admin;

use App\Models\Karyawan;
use App\Models\Ritase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Semua entitas tabel yang dikonversi ke AJAX harus bisa dirender lewat endpoint,
 * memakai fragmen Blade yang sama dengan halaman SSR.
 */
class FragmenTabelAjaxTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function ajax(string $url, array $muatan)
    {
        return $this->withHeaders([
            'X-Interaksi-Ajax' => '1',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->postJson($url, $muatan);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function entitasProvider(): array
    {
        return [
            'karyawan' => ['karyawan'],
            'truk' => ['truk'],
            'rute' => ['rute'],
            'ritase' => ['ritase'],
            'biaya' => ['biaya'],
            'kasbon' => ['kasbon'],
            'penggajian' => ['penggajian'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('entitasProvider')]
    public function test_entitas_memiliki_fragmen_dan_dapat_dirender(string $entitas): void
    {
        $this->siapkanDataMaster();

        $respon = $this->actingAs($this->penggunaAdmin())->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => $entitas,
            'filter' => [],
            'halaman' => 1,
            'per_halaman' => 10,
        ]);

        // Boleh kosong (belum ada data), tetapi TIDAK boleh 501 (fragmen tidak ada).
        $respon->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.entitas', $entitas);

        $this->assertArrayHasKey('rows_html', (array) $respon->json('data'));
        $this->assertArrayHasKey('meta', (array) $respon->json('data'));
    }

    public function test_fragmen_pimpinan_tanpa_kolom_aksi(): void
    {
        $data = $this->siapkanDataMaster();

        Ritase::query()->create([
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-10',
            'jumlah_rit' => 1,
            'tonase_kg' => 25000,
            'tarif_per_ton' => 150000,
            'total_angkutan' => 3750000,
            'uang_jalan' => 200000,
            'status' => 'terverifikasi',
        ]);

        $responPimpinan = $this->actingAs($this->penggunaPimpinan())->ajax('/executive/interaksi/tabel', [
            'entitas' => 'ritase',
            'filter' => [],
            'halaman' => 1,
            'per_halaman' => 10,
        ]);

        $responPimpinan->assertOk();
        $htmlPimpinan = (string) $responPimpinan->json('data.rows_html');

        $this->assertStringContainsString('<tr', $htmlPimpinan);
        $this->assertStringNotContainsString('Pilih Aksi', $htmlPimpinan, 'Pimpinan hanya membaca: tidak boleh ada kolom aksi.');

        // Admin tetap mendapat kolom aksi.
        $responAdmin = $this->actingAs($this->penggunaAdmin())->ajax('/admin/interaksi/tabel-ajax', [
            'entitas' => 'ritase',
            'filter' => [],
            'halaman' => 1,
            'per_halaman' => 10,
        ]);

        $responAdmin->assertOk();
        $this->assertStringContainsString('Pilih Aksi', (string) $responAdmin->json('data.rows_html'));
    }

    public function test_halaman_pimpinan_tidak_memuat_seluruh_baris_sekaligus(): void
    {
        $data = $this->siapkanDataMaster();

        // 60 baris: halaman SSR hanya boleh memuat 50 baris awal.
        for ($i = 1; $i <= 60; $i++) {
            Ritase::query()->create([
                'id_periode_gaji' => $data['periode']->id,
                'id_karyawan' => $data['karyawan']->id,
                'id_truk' => $data['truk']->id,
                'id_rute' => $data['rute']->id,
                'tanggal_ritase' => '2026-01-'.str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT),
                'jumlah_rit' => 1,
                'tonase_kg' => 25000,
                'tarif_per_ton' => 150000,
                'total_angkutan' => 3750000,
                'uang_jalan' => 200000,
                'status' => 'terverifikasi',
            ]);
        }

        $html = $this->actingAs($this->penggunaPimpinan())
            ->get('/executive/trips')
            ->assertOk()
            ->getContent();

        // Komponen AJAX wajib terpasang dan SSR tidak memuat seluruh 60 baris.
        $this->assertStringContainsString('tabelAjaxServer', (string) $html);
        $this->assertStringContainsString('tbodyPemantauan', (string) $html);
    }
}
