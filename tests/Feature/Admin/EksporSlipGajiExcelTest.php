<?php

namespace Tests\Feature\Admin;

use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Services\SlipGajiExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;
use ZipArchive;

/**
 * Ekspor slip gaji .xlsx: satu sheet per supir, tanpa sheet panduan.
 * Struktur mengikuti dokumen acuan `dokumen/Slip Gaji - KUWAT SLAMET - Jan-26.xlsx`.
 */
class EksporSlipGajiExcelTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    /**
     * Siapkan satu periode dengan dua supir yang masing-masing punya ritase + baris gaji.
     *
     * @return array{periode: PeriodeGaji, daftar: \Illuminate\Support\Collection<int, Penggajian>}
     */
    private function siapkanDuaSlip(): array
    {
        $data = $this->siapkanDataMaster();
        $periode = $data['periode'];

        $supirKedua = $this->buatSupir([
            'nomor_induk_karyawan' => 'NIP-UJI-2',
            'nama_lengkap' => 'supir kedua uji',
        ]);

        $trukKedua = \App\Models\Truk::query()->create([
            'id_jenis_truk' => $data['jenisTruk']->id,
            'nomor_polisi' => 'BE 9999 ZZ',
            'merek_model' => 'HINO 500',
            'status' => 'aktif',
        ]);

        $daftarPenggajian = collect();

        foreach ([
            ['karyawan' => $data['karyawan'], 'truk' => $data['truk'], 'rit' => 2, 'tonase' => 28480],
            ['karyawan' => $supirKedua, 'truk' => $trukKedua, 'rit' => 1, 'tonase' => 25000],
        ] as $urutan => $baris) {
            Ritase::query()->create([
                'id_periode_gaji' => $periode->id,
                'id_karyawan' => $baris['karyawan']->id,
                'id_truk' => $baris['truk']->id,
                'id_rute' => $data['rute']->id,
                'tanggal_ritase' => '2026-01-0'.($urutan + 5),
                'jumlah_rit' => $baris['rit'],
                'tonase_kg' => $baris['tonase'],
                'tarif_per_ton' => 150000,
                'total_angkutan' => 4272000,
                'uang_jalan' => 2200000,
                'status' => 'terverifikasi',
            ]);

            $daftarPenggajian->push(Penggajian::query()->create([
                'id_periode_gaji' => $periode->id,
                'id_karyawan' => $baris['karyawan']->id,
                'id_truk' => $baris['truk']->id,
                'total_rit' => $baris['rit'],
                'total_tonase_kg' => $baris['tonase'],
                'total_angkutan_kotor' => 4272000,
                'potongan_operasional' => 0,
                'angkutan_bersih' => 4272000,
                'total_uang_jalan' => 2200000,
                'biaya_sparepart' => 6462000,
                'hasil_bersih_armada' => 41766581,
                'persentase_komisi_bp' => 1000,
                'gaji_komisi' => 4176658,
                'potongan_bpjs' => 129000,
                'potongan_kasbon' => 500000,
                'gaji_bersih' => 3547658,
            ]));
        }

        return ['periode' => $periode, 'daftar' => $daftarPenggajian];
    }

    public function test_berkas_dibuat_dengan_satu_sheet_per_supir(): void
    {
        $siap = $this->siapkanDuaSlip();

        $path = app(SlipGajiExcelService::class)->bangunBerkas($siap['periode'], $siap['daftar']);
        $this->assertFileExists($path);

        $arsip = new ZipArchive();
        $this->assertTrue($arsip->open($path) === true);

        $workbook = (string) $arsip->getFromName('xl/workbook.xml');
        preg_match_all('/<sheet name="([^"]+)"/', $workbook, $cocok);
        $arsip->close();
        @unlink($path);

        $this->assertCount(2, $cocok[1], 'Jumlah sheet harus sama dengan jumlah supir.');
        $this->assertSame('BUDI SANTOSO', html_entity_decode($cocok[1][0]), 'Sheet pertama = supir utama (helper uji).');
        $this->assertSame('SUPIR KEDUA UJI', html_entity_decode($cocok[1][1]));
    }

    public function test_tidak_ada_sheet_panduan_dan_tidak_ada_baris_petunjuk(): void
    {
        $siap = $this->siapkanDuaSlip();

        $path = app(SlipGajiExcelService::class)->bangunBerkas($siap['periode'], $siap['daftar']);

        $arsip = new ZipArchive();
        $arsip->open($path);
        $semuaTeks = (string) $arsip->getFromName('xl/sharedStrings.xml');
        $workbook = (string) $arsip->getFromName('xl/workbook.xml');
        $arsip->close();
        @unlink($path);

        $this->assertStringNotContainsString('PANDUAN', strtoupper($workbook));
        $this->assertStringNotContainsString('PETUNJUK', strtoupper($workbook));
        $this->assertStringNotContainsString('Petunjuk Penggunaan', $semuaTeks);
        $this->assertStringNotContainsString('>VENDOR<', $semuaTeks, 'Konsep vendor sudah dihapus.');
        $this->assertStringNotContainsString('Persentase Komisi', $semuaTeks, 'Persentase komisi dilarang tampil.');
    }

    public function test_isi_slip_memuat_rincian_dan_rekapitulasi_gaji(): void
    {
        $siap = $this->siapkanDuaSlip();

        $path = app(SlipGajiExcelService::class)->bangunBerkas($siap['periode'], $siap['daftar']);

        $arsip = new ZipArchive();
        $arsip->open($path);
        $teks = (string) $arsip->getFromName('xl/sharedStrings.xml');
        $sheet1 = (string) $arsip->getFromName('xl/worksheets/sheet1.xml');
        $arsip->close();
        @unlink($path);

        // Judul & label rekapitulasi wajib ada.
        foreach ([
            'RINCIAN GAJI SUPIR',
            'NAMA SUPIR',
            'PERIODE',
            'JUMLAH',
            'REKAPITULASI',
            'Hasil Bersih Armada',
            'Gaji Komisi (Gross Pay)',
            'GAJI BERSIH DITERIMA',
        ] as $label) {
            $this->assertStringContainsString($label, $teks, "Label \"{$label}\" wajib ada pada slip.");
        }

        // Nilai gaji bersih harus tertulis sebagai nilai jadi (bukan formula kosong).
        $this->assertStringContainsString('<v>3547658</v>', $sheet1);
    }

    public function test_sel_diisi_nilai_bukan_formula_agar_bisa_diimpor_ulang(): void
    {
        $siap = $this->siapkanDuaSlip();

        $path = app(SlipGajiExcelService::class)->bangunBerkas($siap['periode'], $siap['daftar']);

        $arsip = new ZipArchive();
        $arsip->open($path);
        $sheet1 = (string) $arsip->getFromName('xl/worksheets/sheet1.xml');
        $arsip->close();
        @unlink($path);

        $this->assertStringNotContainsString('<f>', $sheet1, 'Sel tidak boleh berisi formula tanpa nilai cache.');
        $this->assertStringNotContainsString('<f ', $sheet1);
    }

    public function test_nama_sheet_dipotong_maksimal_31_karakter(): void
    {
        $siap = $this->siapkanDuaSlip();

        // Nama supir sangat panjang & mengandung karakter terlarang sheet Excel.
        $siap['daftar'][0]->karyawan->update([
            'nama_lengkap' => 'SUPIR DENGAN NAMA SANGAT PANJANG SEKALI/A/B/C',
        ]);

        $path = app(SlipGajiExcelService::class)->bangunBerkas(
            $siap['periode'],
            Penggajian::query()->with(['karyawan', 'truk'])->whereIn('id', $siap['daftar']->pluck('id'))->get(),
        );

        $arsip = new ZipArchive();
        $arsip->open($path);
        $workbook = (string) $arsip->getFromName('xl/workbook.xml');
        $arsip->close();
        @unlink($path);

        preg_match_all('/<sheet name="([^"]+)"/', $workbook, $cocok);

        foreach ($cocok[1] as $nama) {
            $this->assertLessThanOrEqual(31, mb_strlen(html_entity_decode($nama)), 'Nama sheet maksimal 31 karakter.');
            $this->assertStringNotContainsString('/', $nama, 'Karakter "/" terlarang pada nama sheet.');
        }
    }

    public function test_berkas_hasil_ekspor_dapat_dibaca_kembali_oleh_importer(): void
    {
        $siap = $this->siapkanDuaSlip();

        $path = app(SlipGajiExcelService::class)->bangunBerkas($siap['periode'], $siap['daftar']);

        $berkas = new \Illuminate\Http\UploadedFile($path, 'slip-uji.xlsx', null, null, true);
        $hasil = app(\App\Services\ExcelPayrollMigrationService::class)
            ->imporBerkasMigrasi($berkas, 'auto', 'belum_cair');

        @unlink($path);

        $this->assertSame(2, $hasil['jumlah_slip'], 'Kedua sheet supir harus terbaca kembali.');
        $this->assertSame(2, $hasil['jumlah_ritase'], 'Dua baris pengantaran (satu per sheet) harus terbaca.');
        $this->assertContains($siap['periode']->kode, $hasil['daftar_periode']);
    }

    public function test_ekspor_ditolak_bila_periode_tanpa_baris_gaji(): void
    {
        $data = $this->siapkanDataMaster();

        $this->actingAs($this->penggunaAdmin())
            ->get("/admin/reports/{$data['periode']->kode}/export")
            ->assertStatus(404);
    }

    public function test_endpoint_ekspor_mengirim_berkas_xlsx(): void
    {
        $siap = $this->siapkanDuaSlip();

        $respon = $this->actingAs($this->penggunaAdmin())
            ->get("/admin/reports/{$siap['periode']->kode}/export");

        $respon->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $respon->headers->get('content-type'),
        );
        $this->assertStringContainsString('.xlsx', (string) $respon->headers->get('content-disposition'));
    }
}
