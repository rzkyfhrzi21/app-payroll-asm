<?php

namespace Tests\Feature\Admin;

use App\Models\BiayaOperasional;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\Penggajian;
use App\Models\Ritase;
use App\Models\Truk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * BRIEF_UNIT_TEST.md Bab 6 (tabel AJAX, pagination, live search) & Bab 8 (N+1).
 */
class TabelAjaxAdminTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    /**
     * @return array<int, array{0: string}>
     */
    public static function daftarEntitas(): array
    {
        return [
            ['karyawan'], ['truk'], ['rute'], ['ritase'],
            ['biaya'], ['kasbon'], ['penggajian'], ['periode_gaji'],
        ];
    }

    #[DataProvider('daftarEntitas')]
    public function test_endpoint_tabel_mengembalikan_skema_json_baku(string $entitas): void
    {
        $data = $this->siapkanDataMaster();
        $this->buatRitase($data);
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => $entitas,
            'per_halaman' => 10,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'type', 'title', 'action', 'status', 'message', 'timestamp',
                'data' => [
                    'rows',
                    'meta' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
                ],
            ]);
    }

    /**
     * BRIEF Bab 6.1: hard cap pagination maksimal 50/100 baris.
     */
    public function test_pagination_membatasi_per_halaman_maksimal_seratus(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $respon = $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => 'karyawan',
            'per_halaman' => 9999,
        ])->assertOk();

        $this->assertLessThanOrEqual(100, $respon->json('data.meta.per_page'));
        $this->assertSame(1, $respon->json('data.meta.current_page'));
    }

    public function test_halaman_minimal_satu_meski_diminta_nol(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => 'karyawan',
            'halaman' => 0,
            'per_halaman' => 0,
        ])->assertOk()->assertJsonPath('data.meta.current_page', 1);
    }

    /**
     * Live search harus menyaring baris sesuai kata kunci.
     */
    public function test_live_search_menyaring_baris(): void
    {
        $data = $this->siapkanDataMaster();
        $this->buatSupir(['nomor_induk_karyawan' => 'NIP-002', 'nama_lengkap' => 'SUPARMAN']);
        $this->actingAs($this->penggunaAdmin());

        $respon = $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => 'karyawan',
            'cari' => 'SUPARMAN',
        ])->assertOk();

        $this->assertSame(1, $respon->json('data.meta.total'));
        $this->assertSame('SUPARMAN', $respon->json('data.rows.0.nama_lengkap'));
        $this->assertNotSame($data['karyawan']->nama_lengkap, $respon->json('data.rows.0.nama_lengkap'));
    }

    /**
     * BRIEF Bab 8: whitelist kolom sorting harus menolak kolom asing.
     */
    public function test_sorting_memakai_whitelist_kolom(): void
    {
        $this->siapkanDataMaster();
        $this->buatSupir(['nomor_induk_karyawan' => 'NIP-002', 'nama_lengkap' => 'ANWAR']);
        $this->actingAs($this->penggunaAdmin());

        $respon = $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => 'karyawan',
            'sort_by' => 'password',
            'sort_dir' => 'DESC',
        ])->assertOk();

        // Kolom asing diabaikan -> jatuh ke default 'id' DESC (baris terbaru dulu).
        $this->assertSame('NIP-002', $respon->json('data.rows.0.nomor_induk_karyawan'));

        $respon = $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => 'karyawan',
            'sort_by' => 'nama_lengkap',
            'sort_dir' => 'ASC',
        ])->assertOk();

        $this->assertSame('ANWAR', $respon->json('data.rows.0.nama_lengkap'));
    }

    #[DataProvider('daftarEntitas')]
    public function test_tabel_tidak_mengalami_n_plus_one(string $entitas): void
    {
        $data = $this->siapkanDataMaster();

        foreach (range(1, 8) as $urutan) {
            $this->buatRitase($data, ['tanggal_ritase' => '2026-01-'.str_pad((string) $urutan, 2, '0', STR_PAD_LEFT)]);
        }

        $this->actingAs($this->penggunaAdmin());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => $entitas,
            'per_halaman' => 50,
        ])->assertOk();

        $jumlahQuery = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            6,
            $jumlahQuery,
            "Entitas {$entitas} menjalankan {$jumlahQuery} query (indikasi N+1)."
        );
    }

    /**
     * Filter kontekstual per entitas tetap berjalan.
     */
    public function test_filter_status_dan_relasi_berjalan(): void
    {
        $data = $this->siapkanDataMaster();
        $this->buatSupir([
            'nomor_induk_karyawan' => 'NIP-900',
            'nama_lengkap' => 'NONAKTIF SATU',
            'status' => 'nonaktif',
        ]);
        $this->actingAs($this->penggunaAdmin());

        $respon = $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => 'karyawan',
            'filter' => ['status' => 'nonaktif'],
        ])->assertOk();

        $this->assertSame(1, $respon->json('data.meta.total'));
        $this->assertSame('NONAKTIF SATU', $respon->json('data.rows.0.nama_lengkap'));

        $respon = $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => 'karyawan',
            'filter' => ['unit' => 'pegang'],
        ])->assertOk();

        $this->assertSame(1, $respon->json('data.meta.total'));
        $this->assertSame('NIP-001', $respon->json('data.rows.0.nomor_induk_karyawan'));
    }

    /**
     * Entitas tak dikenal jatuh ke daftar karyawan (default aman).
     */
    public function test_entitas_tidak_dikenal_memakai_default_karyawan(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/interaksi/tabel', [
            'entitas' => 'tabel_rahasia',
        ])->assertOk()->assertJsonPath('action', 'FETCH_TABEL_TABEL_RAHASIA');
    }

    /**
     * Data uji wajib bersih: pastikan tidak ada sisa baris dari entitas lain.
     */
    public function test_jumlah_baris_tabel_sesuai_data_uji(): void
    {
        $data = $this->siapkanDataMaster();
        $this->buatRitase($data);
        $this->actingAs($this->penggunaAdmin());

        $this->assertSame(1, Karyawan::query()->count());
        $this->assertSame(1, Truk::query()->count());
        $this->assertSame(1, Ritase::query()->count());
        $this->assertSame(0, Kasbon::query()->count());
        $this->assertSame(0, BiayaOperasional::query()->count());
        $this->assertSame(0, Penggajian::query()->count());
    }
}
