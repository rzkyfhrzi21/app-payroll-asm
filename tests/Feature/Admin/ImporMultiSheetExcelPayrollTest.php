<?php

namespace Tests\Feature\Admin;

use App\Models\JenisTruk;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Truk;
use App\Services\ExcelPayrollMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;
use ZipArchive;

/**
 * ==============================================================================
 * PENGUJIAN FITUR: IMPOR MULTI-SHEET EXCEL PAYROLL DENGAN NAMA & PERIODE BERBEDA
 * ==============================================================================
 * Memastikan bahwa berkas Excel yang memiliki 2 sheet atau lebih bertuliskan
 * "RINCIAN GAJI SUPIR" dengan nama supir berbeda dan periode kalender berbeda:
 * 1. Seluruh sheet data transaksi tetap terhitung dan terproses secara utuh.
 * 2. Sheet panduan ("PANDUAN*") dan petunjuk ("PETUNJUK*") otomatis dilewati.
 * 3. Identitas supir dan siklus periode tersimpan terpisah tanpa saling menimpa.
 * ==============================================================================
 */
class ImporMultiSheetExcelPayrollTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan jenis truk dasar tersedia untuk unit armada baru
        JenisTruk::query()->firstOrCreate(
            ['nama' => 'TRONTON 10 RODA'],
            [
                'merek_model' => 'HINO 500 TI',
                'status' => 'aktif',
            ]
        );
    }

    /**
     * Helper pembuat file .xlsx uji coba multi-sheet menggunakan ZipArchive internal.
     *
     * @param  array<int, array{nama_sheet: string, nama_supir?: string, nopol?: string, periode?: string, ritase?: array<int, array<string, mixed>>, is_guide?: bool}>  $daftarLembar
     */
    private function buatBerkasExcelUji(array $daftarLembar): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_multisheet_') . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $typesXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";
        $typesXml .= "<Types xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\">\n";
        $typesXml .= "  <Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\"/>\n";
        $typesXml .= "  <Default Extension=\"xml\" ContentType=\"application/xml\"/>\n";
        $typesXml .= "  <Override PartName=\"/xl/workbook.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml\"/>\n";
        foreach ($daftarLembar as $i => $lembar) {
            $nomorSheet = $i + 1;
            $typesXml .= "  <Override PartName=\"/xl/worksheets/sheet{$nomorSheet}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>\n";
        }
        $typesXml .= "</Types>";
        $zip->addFromString('[Content_Types].xml', $typesXml);

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">\n  <Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument\" Target=\"xl/workbook.xml\"/>\n</Relationships>");

        // 3. xl/_rels/workbook.xml.rels
        $relsXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";
        $relsXml .= "<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">\n";
        foreach ($daftarLembar as $i => $lembar) {
            $nomorSheet = $i + 1;
            $relsXml .= "  <Relationship Id=\"rId{$nomorSheet}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet{$nomorSheet}.xml\"/>\n";
        }
        $relsXml .= "</Relationships>";
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relsXml);

        // 4. xl/workbook.xml
        $wbXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";
        $wbXml .= "<workbook xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\">\n";
        $wbXml .= "  <sheets>\n";
        foreach ($daftarLembar as $i => $lembar) {
            $nomorSheet = $i + 1;
            $wbXml .= "    <sheet name=\"{$lembar['nama_sheet']}\" sheetId=\"{$nomorSheet}\" r:id=\"rId{$nomorSheet}\"/>\n";
        }
        $wbXml .= "  </sheets>\n</workbook>";
        $zip->addFromString('xl/workbook.xml', $wbXml);

        // 5. xl/worksheets/sheetX.xml
        foreach ($daftarLembar as $i => $lembar) {
            $nomorSheet = $i + 1;

            if (! empty($lembar['is_guide'])) {
                $sheetContent = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";
                $sheetContent .= "<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\"><sheetData>\n";
                $sheetContent .= "  <row r=\"1\"><c r=\"A1\" t=\"inlineStr\"><is><t>{$lembar['nama_sheet']}</t></is></c></row>\n";
                $sheetContent .= "</sheetData></worksheet>";
                $zip->addFromString("xl/worksheets/sheet{$nomorSheet}.xml", $sheetContent);
                continue;
            }

            $namaSupir = $lembar['nama_supir'] ?? 'SUPIR UJI';
            $nopol = $lembar['nopol'] ?? 'BE 1001 ASM';
            $periode = $lembar['periode'] ?? 'Nov-26';
            $daftarRitase = $lembar['ritase'] ?? [];

            $sheetContent = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";
            $sheetContent .= "<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\"><sheetData>\n";
            $sheetContent .= "  <row r=\"1\"><c r=\"A1\" t=\"inlineStr\"><is><t>RINCIAN GAJI SUPIR</t></is></c></row>\n";
            $sheetContent .= "  <row r=\"3\"><c r=\"A3\" t=\"inlineStr\"><is><t>NAMA SUPIR</t></is></c><c r=\"B3\" t=\"inlineStr\"><is><t>{$namaSupir}</t></is></c><c r=\"E3\" t=\"inlineStr\"><is><t>MERK</t></is></c><c r=\"F3\" t=\"inlineStr\"><is><t>HINO 500 TI</t></is></c><c r=\"K3\" t=\"inlineStr\"><is><t>PERIODE</t></is></c><c r=\"L3\" t=\"inlineStr\"><is><t>{$periode}</t></is></c></row>\n";
            $sheetContent .= "  <row r=\"4\"><c r=\"A4\" t=\"inlineStr\"><is><t>PLAT</t></is></c><c r=\"B4\" t=\"inlineStr\"><is><t>{$nopol}</t></is></c></row>\n";

            $rowIdx = 8;
            foreach ($daftarRitase as $rit) {
                $tgl = $rit['tanggal'];
                $ton = $rit['tonase'];
                $tarif = $rit['tarif'];
                $ang = (int) round($ton * $tarif);
                $uj = $rit['uj'];
                $rute = $rit['rute'] ?? 'MIP-PORT';

                $sheetContent .= "  <row r=\"{$rowIdx}\">";
                $sheetContent .= "<c r=\"A{$rowIdx}\" t=\"inlineStr\"><is><t>{$tgl}</t></is></c>";
                $sheetContent .= "<c r=\"B{$rowIdx}\" t=\"inlineStr\"><is><t>{$nopol}</t></is></c>";
                $sheetContent .= "<c r=\"C{$rowIdx}\" t=\"inlineStr\"><is><t>BATU BARA</t></is></c>";
                $sheetContent .= "<c r=\"D{$rowIdx}\"><v>1</v></c>";
                $sheetContent .= "<c r=\"E{$rowIdx}\"><v>{$ton}</v></c>";
                $sheetContent .= "<c r=\"H{$rowIdx}\"><v>{$tarif}</v></c>";
                $sheetContent .= "<c r=\"J{$rowIdx}\"><v>{$ang}</v></c>";
                $sheetContent .= "<c r=\"K{$rowIdx}\"><v>{$uj}</v></c>";
                $sheetContent .= "<c r=\"L{$rowIdx}\" t=\"inlineStr\"><is><t>{$rute}</t></is></c>";
                $sheetContent .= "</row>\n";
                $rowIdx++;
            }

            $sheetContent .= "</sheetData></worksheet>";
            $zip->addFromString("xl/worksheets/sheet{$nomorSheet}.xml", $sheetContent);
        }

        $zip->close();

        return new UploadedFile($tempPath, 'test_multisheet_payroll.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Uji 1: Dua sheet atau lebih dengan nama supir berbeda dan periode kalender berbeda
     * wajib terhitung semua, menghasilkan slip gaji masing-masing supir pada periode yang sesuai.
     */
    public function test_impor_multi_sheet_dengan_supir_berbeda_dan_periode_berbeda_terhitung_semua(): void
    {
        $berkas = $this->buatBerkasExcelUji([
            [
                'nama_sheet' => 'INPUT DATA SLIP',
                'nama_supir' => 'KUWAT SLAMET',
                'nopol' => 'BE 8639 AAU',
                'periode' => 'Nov-26',
                'ritase' => [
                    ['tanggal' => '2026-11-15', 'tonase' => 28.50, 'tarif' => 158760, 'uj' => 2100000, 'rute' => 'MIP-PORT'],
                    ['tanggal' => '2026-11-20', 'tonase' => 29.00, 'tarif' => 158760, 'uj' => 2100000, 'rute' => 'MIP-PORT'],
                ],
            ],
            [
                'nama_sheet' => 'PANDUAN FORMAT VISUAL',
                'is_guide' => true,
            ],
            [
                'nama_sheet' => 'PETUNJUK & BATASAN KOLOM',
                'is_guide' => true,
            ],
            [
                'nama_sheet' => 'INPUT DATA SLIP 2',
                'nama_supir' => 'BUDI SANTOSO',
                'nopol' => 'BE 1002 ASM',
                'periode' => 'Des-26',
                'ritase' => [
                    ['tanggal' => '2026-12-05', 'tonase' => 30.00, 'tarif' => 119880, 'uj' => 1800000, 'rute' => 'MIP-36'],
                    ['tanggal' => '2026-12-10', 'tonase' => 31.00, 'tarif' => 119880, 'uj' => 1800000, 'rute' => 'MIP-36'],
                    ['tanggal' => '2026-12-15', 'tonase' => 29.50, 'tarif' => 119880, 'uj' => 1800000, 'rute' => 'MIP-36'],
                ],
            ],
        ]);

        $layanan = app(ExcelPayrollMigrationService::class);
        $hasil = $layanan->imporBerkasMigrasi($berkas, 'auto', 'belum_cair');

        // Pastikan jumlah slip dan ritase mencakup kedua sheet
        $this->assertSame(2, $hasil['jumlah_slip'], 'Kedua sheet supir wajib terhitung.');
        $this->assertSame(5, $hasil['jumlah_ritase'], 'Total 5 baris ritase dari kedua sheet wajib terproses.');
        $this->assertContains('Nov-26', $hasil['daftar_periode']);
        $this->assertContains('Des-26', $hasil['daftar_periode']);
        $this->assertGreaterThan(0, $hasil['total_gaji_bersih']);

        // Verifikasi Periode Nov-26 untuk Kuwat Slamet.
        // Rentang periode kini mengikuti tanggal NYATA di berkas (tutup buku manual),
        // bukan dipaksa tanggal 1 s/d akhir bulan.
        $periodeNov = PeriodeGaji::query()->where('kode', 'Nov-26')->first();
        $this->assertNotNull($periodeNov, 'Periode Nov-26 wajib otomatis terbentuk.');
        $this->assertSame('2026-11-15', $periodeNov->tanggal_mulai->toDateString());
        $this->assertSame('2026-11-20', $periodeNov->tanggal_selesai->toDateString());

        $penggajianKuwat = Penggajian::query()
            ->where('id_periode_gaji', $periodeNov->id)
            ->whereHas('karyawan', fn ($q) => $q->where('nama_lengkap', 'KUWAT SLAMET'))
            ->first();
        $this->assertNotNull($penggajianKuwat, 'Slip gaji Kuwat Slamet di Nov-26 wajib tersimpan.');
        $this->assertSame(2, $penggajianKuwat->total_rit);
        $this->assertGreaterThan(0, $penggajianKuwat->gaji_bersih);

        // Verifikasi Periode Des-26 untuk Budi Santoso (rentang mengikuti tanggal di berkas)
        $periodeDes = PeriodeGaji::query()->where('kode', 'Des-26')->first();
        $this->assertNotNull($periodeDes, 'Periode Des-26 wajib otomatis terbentuk.');
        $this->assertSame('2026-12-05', $periodeDes->tanggal_mulai->toDateString());
        $this->assertSame('2026-12-15', $periodeDes->tanggal_selesai->toDateString());

        $penggajianBudi = Penggajian::query()
            ->where('id_periode_gaji', $periodeDes->id)
            ->whereHas('karyawan', fn ($q) => $q->where('nama_lengkap', 'BUDI SANTOSO'))
            ->first();
        $this->assertNotNull($penggajianBudi, 'Slip gaji Budi Santoso di Des-26 wajib tersimpan.');
        $this->assertSame(3, $penggajianBudi->total_rit);
        $this->assertGreaterThan(0, $penggajianBudi->gaji_bersih);

        // Verifikasi kedua supir adalah entitas berbeda
        $this->assertNotSame($penggajianKuwat->id_karyawan, $penggajianBudi->id_karyawan);
    }

    /**
     * Uji 2: Dua sheet dengan supir berbeda namun pada periode yang sama (Nov-26)
     * dan menggunakan truk armada yang sama wajib tersimpan bersama tanpa saling menimpa.
     */
    public function test_impor_multi_sheet_dengan_supir_berbeda_pada_periode_sama_terhitung_tanpa_menimpa(): void
    {
        $berkas = $this->buatBerkasExcelUji([
            [
                'nama_sheet' => 'SUPIR KUWAT',
                'nama_supir' => 'KUWAT SLAMET',
                'nopol' => 'BE 8639 AAU',
                'periode' => 'Nov-26',
                'ritase' => [
                    ['tanggal' => '2026-11-10', 'tonase' => 28.00, 'tarif' => 158760, 'uj' => 2100000],
                ],
            ],
            [
                'nama_sheet' => 'SUPIR BAMBANG',
                'nama_supir' => 'BAMBANG SUGIONO',
                'nopol' => 'BE 8639 AAU', // Truk sama
                'periode' => 'Nov-26',
                'ritase' => [
                    ['tanggal' => '2026-11-12', 'tonase' => 29.00, 'tarif' => 158760, 'uj' => 2100000],
                ],
            ],
        ]);

        $layanan = app(ExcelPayrollMigrationService::class);
        $hasil = $layanan->imporBerkasMigrasi($berkas, 'auto', 'belum_cair');

        $this->assertSame(2, $hasil['jumlah_slip']);
        $this->assertSame(2, $hasil['jumlah_ritase']);
        $this->assertSame(['Nov-26'], $hasil['daftar_periode']);

        $periodeNov = PeriodeGaji::query()->where('kode', 'Nov-26')->firstOrFail();
        $daftarSlip = Penggajian::query()->where('id_periode_gaji', $periodeNov->id)->with('karyawan')->get();

        $this->assertCount(2, $daftarSlip, 'Kedua supir harus memiliki baris slip terpisah di periode Nov-26.');
        $namaSupirTersimpan = $daftarSlip->pluck('karyawan.nama_lengkap')->sort()->values()->all();
        $this->assertSame(['BAMBANG SUGIONO', 'KUWAT SLAMET'], $namaSupirTersimpan);
    }

    /**
     * Uji 3: Supir yang sama memiliki sheet di periode Nov-26 dan sheet di periode Des-26
     * wajib tercatat di masing-masing periode penggajian.
     */
    public function test_impor_multi_sheet_supir_sama_pada_dua_periode_berbeda(): void
    {
        $berkas = $this->buatBerkasExcelUji([
            [
                'nama_sheet' => 'KUWAT NOV',
                'nama_supir' => 'KUWAT SLAMET',
                'nopol' => 'BE 8639 AAU',
                'periode' => 'Nov-26',
                'ritase' => [
                    ['tanggal' => '2026-11-05', 'tonase' => 28.00, 'tarif' => 158760, 'uj' => 2100000],
                ],
            ],
            [
                'nama_sheet' => 'KUWAT DES',
                'nama_supir' => 'KUWAT SLAMET',
                'nopol' => 'BE 8639 AAU',
                'periode' => 'Des-26',
                'ritase' => [
                    ['tanggal' => '2026-12-05', 'tonase' => 28.50, 'tarif' => 158760, 'uj' => 2100000],
                ],
            ],
        ]);

        $layanan = app(ExcelPayrollMigrationService::class);
        $hasil = $layanan->imporBerkasMigrasi($berkas, 'auto', 'belum_cair');

        $this->assertSame(2, $hasil['jumlah_slip']);
        $this->assertSame(2, $hasil['jumlah_ritase']);
        $this->assertEqualsCanonicalizing(['Nov-26', 'Des-26'], $hasil['daftar_periode']);

        $karyawanKuwat = Karyawan::query()->where('nama_lengkap', 'KUWAT SLAMET')->firstOrFail();

        $slipKuwatNov = Penggajian::query()
            ->where('id_karyawan', $karyawanKuwat->id)
            ->whereHas('periodeGaji', fn ($q) => $q->where('kode', 'Nov-26'))
            ->first();
        $this->assertNotNull($slipKuwatNov, 'Kuwat harus punya slip di Nov-26.');

        $slipKuwatDes = Penggajian::query()
            ->where('id_karyawan', $karyawanKuwat->id)
            ->whereHas('periodeGaji', fn ($q) => $q->where('kode', 'Des-26'))
            ->first();
        $this->assertNotNull($slipKuwatDes, 'Kuwat harus punya slip di Des-26.');
    }

    /**
     * Uji 4: Sheet panduan dan petunjuk kamus data tidak pernah diproses sebagai slip supir.
     */
    public function test_sheet_panduan_dan_petunjuk_diabaikan_meski_memuat_teks(): void
    {
        $berkas = $this->buatBerkasExcelUji([
            [
                'nama_sheet' => 'PANDUAN FORMAT VISUAL',
                'is_guide' => true,
            ],
            [
                'nama_sheet' => 'PETUNJUK & BATASAN KOLOM',
                'is_guide' => true,
            ],
        ]);

        $layanan = app(ExcelPayrollMigrationService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Format berkas tidak dikenali atau lembar data transaksi belum diisi.');

        $layanan->imporBerkasMigrasi($berkas, 'auto', 'belum_cair');
    }

    /**
     * Uji 5: Endpoint controller POST /admin/reports/migration/import dapat menerima
     * berkas multi-sheet dan memberikan respon sukses dengan ringkasan slip & periode.
     */
    public function test_endpoint_controller_impor_migrasi_excel_berhasil_memproses_multi_sheet(): void
    {
        $this->actingAs($this->penggunaAdmin());

        $berkas = $this->buatBerkasExcelUji([
            [
                'nama_sheet' => 'INPUT DATA SLIP',
                'nama_supir' => 'KUWAT SLAMET',
                'nopol' => 'BE 8639 AAU',
                'periode' => 'Nov-26',
                'ritase' => [
                    ['tanggal' => '2026-11-15', 'tonase' => 28.50, 'tarif' => 158760, 'uj' => 2100000],
                ],
            ],
            [
                'nama_sheet' => 'INPUT DATA SLIP 2',
                'nama_supir' => 'BUDI SANTOSO',
                'nopol' => 'BE 1002 ASM',
                'periode' => 'Des-26',
                'ritase' => [
                    ['tanggal' => '2026-12-05', 'tonase' => 30.00, 'tarif' => 119880, 'uj' => 1800000],
                ],
            ],
        ]);

        $respon = $this->post('/admin/reports/migrasi/import', [
            'berkas_excel' => $berkas,
            'target_periode' => 'auto',
            'status_pencairan' => 'belum_cair',
            'konfirmasi_pencairan' => '1',
        ]);

        $respon->assertSessionHasNoErrors();
        $respon->assertRedirect();
        $respon->assertSessionHas('toast');

        $toast = session('toast');
        $this->assertTrue($toast['success']);
        $this->assertStringContainsString('2 slip gaji supir', $toast['message']);
        $this->assertStringContainsString('Nov-26, Des-26', $toast['message']);
    }
}
