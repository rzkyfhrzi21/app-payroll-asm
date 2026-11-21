<?php

namespace Tests\Feature\Admin;

use App\Models\AngsuranKasbon;
use App\Models\BiayaOperasional;
use App\Models\Kasbon;
use App\Models\Penggajian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * ============================================================================
 * TEST: KONTRAK DATA TABEL AJAX (NILAI NYATA, BUKAN BARIS KOSONG)
 * ============================================================================
 *
 * Analogi Sederhana:
 * Ibarat memeriksa isi map yang dikirim petugas arsip lewat telepon. Test ini
 * memastikan map tersebut benar-benar berisi nama supir, nomor polisi, kode
 * rute, dan nominal uang — bukan lembaran kosong.
 *
 * Regresi yang dijaga:
 * Endpoint AJAX pernah mengirim model Eloquent padahal fragmen Blade memakai
 * array, sehingga tabel hasil filter tampil kosong/0. Test lama tidak menangkap
 * hal itu karena hanya memeriksa status 200 dan keberadaan <tr>.
 */
class KontrakDataTabelAjaxTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    /**
     * Ambil potongan HTML baris tabel untuk satu entitas.
     *
     * @param  array<string, mixed>  $filter
     */
    private function ambilBarisHtml(string $entitas, array $filter = []): string
    {
        $respon = $this->actingAs($this->penggunaAdmin())->kirimAjax('POST', '/admin/interaksi/tabel-ajax', [
            'entitas' => $entitas,
            'filter' => $filter,
            'halaman' => 1,
            'per_halaman' => 10,
            'kolom' => 13,
        ]);

        $respon->assertOk()->assertJsonPath('success', true);

        return (string) $respon->json('data.rows_html');
    }

    public function test_baris_karyawan_memuat_nama_dan_nip_sebenarnya(): void
    {
        $this->siapkanDataMaster();

        $html = $this->ambilBarisHtml('karyawan');

        $this->assertStringContainsString('BUDI SANTOSO', $html);
        $this->assertStringContainsString('NIP-001', $html);
    }

    public function test_baris_truk_memuat_nomor_polisi_sebenarnya(): void
    {
        $this->siapkanDataMaster();

        $html = $this->ambilBarisHtml('truk');

        $this->assertStringContainsString('BE 8639 AAU', $html);
        $this->assertStringContainsString('HINO 500', $html);
    }

    public function test_baris_rute_memuat_kode_dan_material_sebenarnya(): void
    {
        $this->siapkanDataMaster();

        $html = $this->ambilBarisHtml('rute');

        $this->assertStringContainsString('RTE-01', $html);
        $this->assertStringContainsString('BATUBARA', $html);
    }

    public function test_baris_ritase_memuat_supir_nopol_dan_nomor_surat_jalan(): void
    {
        $data = $this->siapkanDataMaster();
        $ritase = $this->buatRitase($data);

        $html = $this->ambilBarisHtml('ritase');

        $this->assertStringContainsString('BUDI SANTOSO', $html);
        $this->assertStringContainsString('BE 8639 AAU', $html);
        $this->assertStringContainsString('SJ-RTE-01-'.str_pad((string) $ritase->id, 5, '0', STR_PAD_LEFT), $html);
    }

    public function test_baris_biaya_memuat_keterangan_dan_nominal_sebenarnya(): void
    {
        $data = $this->siapkanDataMaster();

        BiayaOperasional::query()->create([
            'id_periode_gaji' => $data['periode']->id,
            'id_truk' => $data['truk']->id,
            'id_karyawan' => $data['karyawan']->id,
            'tanggal_biaya' => '2026-01-08',
            'kategori' => 'sparepart',
            'keterangan' => 'GANTI KAMPAS REM DEPAN',
            'nominal' => 1850000,
            'nomor_nota' => 'NT-2026-001',
            'status' => 'draft',
        ]);

        $html = $this->ambilBarisHtml('biaya');

        $this->assertStringContainsString('GANTI KAMPAS REM DEPAN', $html);
        $this->assertStringContainsString('NT-2026-001', $html);
        $this->assertStringContainsString('1.850.000', $html);
        $this->assertStringContainsString('BE 8639 AAU', $html);
    }

    public function test_baris_kasbon_memuat_nama_dan_nominal_sebenarnya(): void
    {
        $data = $this->siapkanDataMaster();

        $kasbon = Kasbon::query()->create([
            'id_karyawan' => $data['karyawan']->id,
            'tanggal_pengambilan' => '2026-01-06',
            'id_periode_gaji_mulai' => $data['periode']->id,
            'total_pinjaman' => 900000,
            'nominal_angsuran' => 300000,
            'jumlah_tenor' => 3,
            'sisa_pinjaman' => 900000,
            'keterangan' => 'BIAYA PENDIDIKAN ANAK',
            'status' => 'approved',
        ]);

        AngsuranKasbon::query()->create([
            'id_kasbon' => $kasbon->id,
            'urutan' => 1,
            'nominal' => 300000,
            'tanggal_rencana' => '2026-02-01',
            'status' => 'terjadwal',
        ]);

        $html = $this->ambilBarisHtml('kasbon');

        $this->assertStringContainsString('BUDI SANTOSO', $html);
        $this->assertStringContainsString('NIP-001', $html);
        $this->assertStringContainsString('900.000', $html);
        $this->assertStringContainsString('300.000', $html);
    }

    public function test_baris_penggajian_memuat_nama_nopol_dan_gaji_bersih_sebenarnya(): void
    {
        $data = $this->siapkanDataMaster();

        Penggajian::query()->create([
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'total_rit' => 25,
            'total_tonase_kg' => 712000,
            'total_angkutan_kotor' => 106800000,
            'potongan_operasional' => 0,
            'angkutan_bersih' => 106800000,
            'total_uang_jalan' => 5000000,
            'biaya_sparepart' => 1850000,
            'hasil_bersih_armada' => 99950000,
            'persentase_komisi_bp' => 1000,
            'gaji_komisi' => 9995000,
            'potongan_bpjs' => 129000,
            'potongan_kasbon' => 0,
            'gaji_bersih' => 9866000,
            'status' => 'draft',
        ]);

        $html = $this->ambilBarisHtml('penggajian');

        $this->assertStringContainsString('BUDI SANTOSO', $html);
        $this->assertStringContainsString('BE 8639 AAU', $html);
        $this->assertStringContainsString('9.866.000', $html);
    }

    public function test_pencarian_mengembalikan_baris_yang_sesuai_saja(): void
    {
        $this->siapkanDataMaster();

        $this->buatSupir([
            'nomor_induk_karyawan' => 'NIP-002',
            'nama_lengkap' => 'AGUS PRASETYO',
        ]);

        $html = $this->ambilBarisHtml('karyawan', ['cari' => 'AGUS']);

        $this->assertStringContainsString('AGUS PRASETYO', $html);
        $this->assertStringNotContainsString('BUDI SANTOSO', $html);
    }

    public function test_filter_periode_membatasi_ritase_ke_periode_yang_diminta(): void
    {
        $data = $this->siapkanDataMaster();
        $ritaseJanuari = $this->buatRitase($data);

        $periodeFebruari = \App\Models\PeriodeGaji::query()->create([
            'kode' => 'FEB-26',
            'tanggal_mulai' => '2026-02-01',
            'tanggal_selesai' => '2026-02-28',
            'tanggal_bayar' => '2026-03-01',
            'status' => 'draft',
        ]);

        $ritaseFebruari = $this->buatRitase([
            'periode' => $periodeFebruari,
            'karyawan' => $data['karyawan'],
            'truk' => $data['truk'],
            'rute' => $data['rute'],
        ], ['tanggal_ritase' => '2026-02-10']);

        $html = $this->ambilBarisHtml('ritase', ['periode' => 'JAN-26']);

        $this->assertStringContainsString(
            'SJ-RTE-01-'.str_pad((string) $ritaseJanuari->id, 5, '0', STR_PAD_LEFT),
            $html
        );
        $this->assertStringNotContainsString(
            'SJ-RTE-01-'.str_pad((string) $ritaseFebruari->id, 5, '0', STR_PAD_LEFT),
            $html,
            'Periode yang diminta tidak boleh bocor ke periode lain.'
        );
    }

    public function test_filter_periode_membatasi_biaya_ke_periode_yang_diminta(): void
    {
        $data = $this->siapkanDataMaster();

        $periodeFebruari = \App\Models\PeriodeGaji::query()->create([
            'kode' => 'FEB-26',
            'tanggal_mulai' => '2026-02-01',
            'tanggal_selesai' => '2026-02-28',
            'tanggal_bayar' => '2026-03-01',
            'status' => 'draft',
        ]);

        foreach ([['periode' => $data['periode'], 'nota' => 'NOTA-JANUARI'], ['periode' => $periodeFebruari, 'nota' => 'NOTA-FEBRUARI']] as $baris) {
            BiayaOperasional::query()->create([
                'id_periode_gaji' => $baris['periode']->id,
                'id_truk' => $data['truk']->id,
                'id_karyawan' => $data['karyawan']->id,
                'tanggal_biaya' => '2026-01-15',
                'kategori' => 'bbm',
                'keterangan' => 'ISI SOLAR',
                'nominal' => 500000,
                'nomor_nota' => $baris['nota'],
                'status' => 'draft',
            ]);
        }

        $html = $this->ambilBarisHtml('biaya', ['periode' => 'JAN-26']);

        $this->assertStringContainsString('NOTA-JANUARI', $html);
        $this->assertStringNotContainsString('NOTA-FEBRUARI', $html);
    }
}
