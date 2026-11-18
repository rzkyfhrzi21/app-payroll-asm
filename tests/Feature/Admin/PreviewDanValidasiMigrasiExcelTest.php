<?php

namespace Tests\Feature\Admin;

use App\Models\AngsuranKasbon;
use App\Models\JenisTruk;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Truk;
use App\Models\User;
use App\Services\ExcelPayrollMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;
use ZipArchive;

/**
 * ==============================================================================
 * PENGUJIAN FITUR: PRATINJAU & VALIDASI MIGRASI EXCEL PENGGAJIAN
 * ==============================================================================
 * Menguji:
 * 1. Unggah berkas membuka tab preview baru dengan token staging di cache.
 * 2. Deteksi anomali: unit berbeda pada tanggal & rute sama, serta supir memakai banyak unit.
 * 3. Pembuatan NIK manual format DRF-{inisial}-{angka} & email default {NAMASUPIR}_ASM@gmail.com.
 * 4. Resolusi bentrok supir 3-arah: gabungkan ke yang ada, buat akun baru, atau lewati.
 * 5. Pilihan unit armada: daftarkan unit baru atau hubungkan ke unit ada.
 * 6. DB Transaction: kegagalan di tengah jalan membatalkan seluruh mutasi (Rollback).
 * 7. Pemblokiran impor ke periode yang sudah disetujui/dicairkan (terkunci).
 * 8. Impor sudah cair: jejak pencairan & penyelesaian angsuran kasbon.
 * 9. Validasi ekstensi berkas, pesan template kosong, dan unduh contoh vs template.
 * ==============================================================================
 */
class PreviewDanValidasiMigrasiExcelTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        JenisTruk::query()->firstOrCreate(
            ['nama' => 'TRONTON 10 RODA'],
            ['merek_model' => 'HINO 500 TI', 'status' => 'aktif']
        );
    }

    private function buatFileExcelDummy(array $daftarLembar): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_preview_') . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $typesXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Types xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\">\n<Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\"/>\n<Default Extension=\"xml\" ContentType=\"application/xml\"/>\n<Override PartName=\"/xl/workbook.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml\"/>\n";
        foreach ($daftarLembar as $i => $lembar) {
            $nomorSheet = $i + 1;
            $typesXml .= "<Override PartName=\"/xl/worksheets/sheet{$nomorSheet}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>\n";
        }
        $typesXml .= "</Types>";
        $zip->addFromString('[Content_Types].xml', $typesXml);

        $zip->addFromString('_rels/.rels', "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">\n<Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument\" Target=\"xl/workbook.xml\"/>\n</Relationships>");

        $relsXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">\n";
        foreach ($daftarLembar as $i => $lembar) {
            $nomorSheet = $i + 1;
            $relsXml .= "<Relationship Id=\"rId{$nomorSheet}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet{$nomorSheet}.xml\"/>\n";
        }
        $relsXml .= "</Relationships>";
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relsXml);

        $wbXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<workbook xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\">\n<sheets>\n";
        foreach ($daftarLembar as $i => $lembar) {
            $nomorSheet = $i + 1;
            $wbXml .= "<sheet name=\"{$lembar['nama_sheet']}\" sheetId=\"{$nomorSheet}\" r:id=\"rId{$nomorSheet}\"/>\n";
        }
        $wbXml .= "</sheets>\n</workbook>";
        $zip->addFromString('xl/workbook.xml', $wbXml);

        foreach ($daftarLembar as $i => $lembar) {
            $nomorSheet = $i + 1;
            $namaSupir = $lembar['nama_supir'] ?? 'SUPIR PREVIEW';
            $nopol = $lembar['nopol'] ?? 'BE 9999 ASM';
            $periode = $lembar['periode'] ?? 'Des-26';
            $ritase = $lembar['ritase'] ?? [];

            $sheetContent = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\"><sheetData>\n";
            $sheetContent .= "<row r=\"1\"><c r=\"A1\" t=\"inlineStr\"><is><t>RINCIAN GAJI SUPIR</t></is></c></row>\n";
            $sheetContent .= "<row r=\"3\"><c r=\"A3\" t=\"inlineStr\"><is><t>NAMA SUPIR</t></is></c><c r=\"B3\" t=\"inlineStr\"><is><t>{$namaSupir}</t></is></c><c r=\"E3\" t=\"inlineStr\"><is><t>MERK</t></is></c><c r=\"F3\" t=\"inlineStr\"><is><t>HINO 500 TI</t></is></c><c r=\"K3\" t=\"inlineStr\"><is><t>PERIODE</t></is></c><c r=\"L3\" t=\"inlineStr\"><is><t>{$periode}</t></is></c></row>\n";
            $sheetContent .= "<row r=\"4\"><c r=\"A4\" t=\"inlineStr\"><is><t>PLAT</t></is></c><c r=\"B4\" t=\"inlineStr\"><is><t>{$nopol}</t></is></c></row>\n";

            $rowIdx = 8;
            foreach ($ritase as $r) {
                $sheetContent .= "<row r=\"{$rowIdx}\">";
                $sheetContent .= "<c r=\"A{$rowIdx}\" t=\"inlineStr\"><is><t>{$r['tanggal']}</t></is></c>";
                $sheetContent .= "<c r=\"B{$rowIdx}\" t=\"inlineStr\"><is><t>{$r['nopol']}</t></is></c>";
                $sheetContent .= "<c r=\"C{$rowIdx}\" t=\"inlineStr\"><is><t>BATU BARA</t></is></c>";
                $sheetContent .= "<c r=\"D{$rowIdx}\"><v>{$r['rit']}</v></c>";
                $sheetContent .= "<c r=\"E{$rowIdx}\"><v>{$r['tonase']}</v></c>";
                $sheetContent .= "<c r=\"H{$rowIdx}\"><v>{$r['tarif']}</v></c>";
                $sheetContent .= "<c r=\"J{$rowIdx}\"><v>{$r['total']}</v></c>";
                $sheetContent .= "<c r=\"K{$rowIdx}\"><v>{$r['uang_jalan']}</v></c>";
                $sheetContent .= "<c r=\"L{$rowIdx}\" t=\"inlineStr\"><is><t>{$r['keterangan']}</t></is></c>";
                $sheetContent .= "</row>\n";
                $rowIdx++;
            }

            $sheetContent .= "<row r=\"{$rowIdx}\"><c r=\"H{$rowIdx}\" t=\"inlineStr\"><is><t>JUMLAH GAJI BERSIH</t></is></c><c r=\"K{$rowIdx}\"><v>2500000</v></c></row>\n";
            $sheetContent .= "</sheetData></worksheet>";

            $zip->addFromString("xl/worksheets/sheet{$nomorSheet}.xml", $sheetContent);
        }

        $zip->close();

        return new UploadedFile($tempPath, 'migrasi_preview_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_formula_nik_manual_dan_email_default_supir_baru(): void
    {
        $service = app(ExcelPayrollMigrationService::class);

        // Format NIK manual berdasarkan inisial: DRF-{INISIAL}-{2 ANGKA}
        $nikBambang = $service->generateDefaultNik('BAMBANG SUGIONO');
        $this->assertMatchesRegularExpression('/^DRF-BS-\d{2}$/', $nikBambang);

        $nikKuwat = $service->generateDefaultNik('KUWAT SLAMET');
        $this->assertMatchesRegularExpression('/^DRF-KS-\d{2}$/', $nikKuwat);

        $nikSingle = $service->generateDefaultNik('YUDI');
        $this->assertMatchesRegularExpression('/^DRF-YUD-\d{2}$/', $nikSingle);

        // Format Email default: NAMASUPIR_ASM@gmail.com (lowercase alfanumerik)
        $this->assertSame('bambangsugiono_asm@gmail.com', $service->generateDefaultEmail('BAMBANG SUGIONO'));
        $this->assertSame('kuwatslamet_asm@gmail.com', $service->generateDefaultEmail('KUWAT SLAMET'));
        $this->assertSame('jokop_asm@gmail.com', $service->generateDefaultEmail('JOKO P.'));

        // Jika email sudah ada, otomatis menambahkan nomor urut
        User::factory()->create(['email' => 'agussantoso_asm@gmail.com']);
        $this->assertSame('agussantoso2_asm@gmail.com', $service->generateDefaultEmail('AGUS SANTOSO'));
    }

    public function test_unggah_berkas_menghasilkan_pratinjau_staging_dengan_token(): void
    {
        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);

        $file = $this->buatFileExcelDummy([
            [
                'nama_sheet' => 'SUPIR PREVIEW',
                'nama_supir' => 'HERI SETIAWAN',
                'nopol' => 'BE 7777 ASM',
                'periode' => 'Des-26',
                'ritase' => [
                    [
                        'tanggal' => '2026-12-05',
                        'nopol' => 'BE 7777 ASM',
                        'rit' => 1,
                        'tonase' => 28.50,
                        'tarif' => 150000,
                        'total' => 4275000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'MIP-PORT',
                    ],
                ],
            ],
        ]);

        $response = $this->post('/admin/reports/migrasi/preview', [
            'berkas_excel' => $file,
            'target_periode' => 'auto',
            'status_pencairan' => 'belum_cair',
            'konfirmasi_pencairan' => '1',
        ]);

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringContainsString('/admin/reports/migrasi/preview/migrasi_', $targetUrl);

        // Buka halaman preview
        $previewResponse = $this->get($targetUrl);
        $previewResponse->assertOk();
        $html = $previewResponse->getContent();

        $this->assertStringContainsString('HERI SETIAWAN', $html);
        $this->assertStringContainsString('herisetiawan_asm@gmail.com', $html);
        $this->assertStringContainsString('DRF-HS-', $html);
        $this->assertStringContainsString('BE 7777 ASM', $html);
        $this->assertStringContainsString('Tabel Pratinjau Rekapitulasi &amp; Kalkulasi Gaji Supir', $html);
        $this->assertStringContainsString('Gabungkan ke Supir Terdaftar', $html);

        // Halaman pratinjau wajib memuat toast stack agar konfirmasi impor bisa
        // ditampilkan langsung sebelum pengguna dialihkan ke halaman laporan.
        $this->assertStringContainsString('toastStackManager', $html);
        $this->assertStringContainsString('window.tampilkanToast', $html);
        $this->assertStringContainsString('Migrasi Data Excel Berhasil', $html);
    }

    public function test_deteksi_anomali_unit_berbeda_pada_tanggal_dan_rute_sama(): void
    {
        $service = app(ExcelPayrollMigrationService::class);

        $file = $this->buatFileExcelDummy([
            [
                'nama_sheet' => 'SUPIR ANOMALI',
                'nama_supir' => 'BAMBANG HERMANTO',
                'nopol' => 'BE 1111 AA',
                'periode' => 'Des-26',
                'ritase' => [
                    // Rit 1: Truk A
                    [
                        'tanggal' => '2026-12-10',
                        'nopol' => 'BE 1111 AA',
                        'rit' => 1,
                        'tonase' => 28.00,
                        'tarif' => 150000,
                        'total' => 4200000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'RTE-KORIDOR-1',
                    ],
                    // Rit 2: Truk B di tanggal dan rute yang sama persis!
                    [
                        'tanggal' => '2026-12-10',
                        'nopol' => 'BE 2222 BB',
                        'rit' => 1,
                        'tonase' => 29.00,
                        'tarif' => 150000,
                        'total' => 4350000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'RTE-KORIDOR-1',
                    ],
                ],
            ],
        ]);

        $staging = $service->analisisStagingMigrasi($file, 'auto', 'belum_cair');

        $this->assertNotEmpty($staging['anomali']);
        $anomaliRute = collect($staging['anomali'])->firstWhere('tipe', 'unit_berbeda_tanggal_rute_sama');
        $this->assertNotNull($anomaliRute);
        $this->assertStringContainsString('BAMBANG HERMANTO', $anomaliRute['keterangan']);
        $this->assertStringContainsString('2026-12-10', $anomaliRute['keterangan']);
        $this->assertStringContainsString('BE 1111 AA', $anomaliRute['keterangan']);
        $this->assertStringContainsString('BE 2222 BB', $anomaliRute['keterangan']);
    }

    public function test_resolusi_bentrok_tiga_arah_gabungkan_buat_baru_dan_lewati(): void
    {
        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);
        $service = app(ExcelPayrollMigrationService::class);

        // 1. Siapkan 1 supir master yang sudah terdaftar sebelumnya di database
        $supirEksis = $this->buatSupir([
            'nama_lengkap' => 'KUWAT SLAMET MASTER',
            'nomor_induk_karyawan' => 'DRF-KS-99',
        ]);

        $file = $this->buatFileExcelDummy([
            // Supir A: di Excel bernama "KUWAT S", admin ingin menggabungkan ke KUWAT SLAMET MASTER
            [
                'nama_sheet' => 'SUPIR A',
                'nama_supir' => 'KUWAT S',
                'nopol' => 'BE 3333 CC',
                'periode' => 'Des-26',
                'ritase' => [
                    [
                        'tanggal' => '2026-12-01',
                        'nopol' => 'BE 3333 CC',
                        'rit' => 1,
                        'tonase' => 25.00,
                        'tarif' => 150000,
                        'total' => 3750000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'MIP-PORT',
                    ],
                ],
            ],
            // Supir B: orang baru, admin ingin membuatkan akun baru dengan NIK manual
            [
                'nama_sheet' => 'SUPIR B',
                'nama_supir' => 'DENI KURNIAWAN',
                'nopol' => 'BE 4444 DD',
                'periode' => 'Des-26',
                'ritase' => [
                    [
                        'tanggal' => '2026-12-02',
                        'nopol' => 'BE 4444 DD',
                        'rit' => 1,
                        'tonase' => 26.00,
                        'tarif' => 150000,
                        'total' => 3900000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'MIP-PORT',
                    ],
                ],
            ],
            // Supir C: admin ingin lewati
            [
                'nama_sheet' => 'SUPIR C',
                'nama_supir' => 'SUPIR LEWATI',
                'nopol' => 'BE 5555 EE',
                'periode' => 'Des-26',
                'ritase' => [
                    [
                        'tanggal' => '2026-12-03',
                        'nopol' => 'BE 5555 EE',
                        'rit' => 1,
                        'tonase' => 27.00,
                        'tarif' => 150000,
                        'total' => 4050000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'MIP-PORT',
                    ],
                ],
            ],
        ]);

        $staging = $service->analisisStagingMigrasi($file, 'auto', 'belum_cair');
        $token = $staging['token'];

        // Eksekusi dengan keputusan 3-arah:
        $response = $this->post('/admin/reports/migrasi/eksekusi', [
            'token' => $token,
            'supir' => [
                'KUWAT S' => [
                    'aksi' => 'gabungkan',
                    'id_karyawan_tujuan' => $supirEksis->id,
                ],
                'DENI KURNIAWAN' => [
                    'aksi' => 'buat_akun',
                    'email' => 'denikurniawan_asm@gmail.com',
                    'nip' => 'DRF-DK-77',
                ],
                'SUPIR LEWATI' => [
                    'aksi' => 'lewati',
                ],
            ],
        ]);

        $response->assertRedirect('/admin/reports');

        // 1. Supir A: tidak membuat karyawan baru, tapi penggajian terhubung ke $supirEksis
        $this->assertNull(Karyawan::query()->where('nama_lengkap', 'KUWAT S')->first());
        $penggajianGabung = Penggajian::query()->where('id_karyawan', $supirEksis->id)->first();
        $this->assertNotNull($penggajianGabung);
        $this->assertSame(1, (int) $penggajianGabung->total_rit);

        // 2. Supir B: dibuatkan akun baru dengan NIK manual DRF-DK-77 dan email denikurniawan_asm@gmail.com
        $userB = User::query()->where('email', 'denikurniawan_asm@gmail.com')->first();
        $this->assertNotNull($userB);
        $karyawanB = Karyawan::query()->where('nama_lengkap', 'DENI KURNIAWAN')->first();
        $this->assertNotNull($karyawanB);
        $this->assertSame('DRF-DK-77', $karyawanB->nomor_induk_karyawan);

        // 3. Supir C: dilewati dan tidak ada baris di database
        $this->assertNull(Karyawan::query()->where('nama_lengkap', 'SUPIR LEWATI')->first());
    }

    public function test_impor_ke_periode_yang_terkunci_ditolak_keras(): void
    {
        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);
        $service = app(ExcelPayrollMigrationService::class);

        PeriodeGaji::query()->create([
            'kode' => 'LOCKED-PERIODE',
            'tanggal_mulai' => '2026-05-01',
            'tanggal_selesai' => '2026-05-31',
            'status' => 'approved',
        ]);

        $file = $this->buatFileExcelDummy([
            [
                'nama_sheet' => 'SUPIR LOCK',
                'nama_supir' => 'SUPIR LOCK',
                'nopol' => 'BE 5555 EE',
                'periode' => 'LOCKED-PERIODE',
                'ritase' => [
                    [
                        'tanggal' => '2026-05-02',
                        'nopol' => 'BE 5555 EE',
                        'rit' => 1,
                        'tonase' => 25.00,
                        'tarif' => 150000,
                        'total' => 3750000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'MIP-PORT',
                    ],
                ],
            ],
        ]);

        $staging = $service->analisisStagingMigrasi($file, 'LOCKED-PERIODE', 'belum_cair');
        $this->assertTrue($staging['ringkasan']['periode_terkunci']);

        $response = $this->post('/admin/reports/migrasi/eksekusi', [
            'token' => $staging['token'],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_eksekusi_async_dan_polling_status_progress(): void
    {
        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);
        $service = app(ExcelPayrollMigrationService::class);

        $file = $this->buatFileExcelDummy([
            [
                'nama_sheet' => 'SUPIR ASYNC',
                'nama_supir' => 'SUPIR ASYNC',
                'nopol' => 'BE 8888 AS',
                'periode' => 'Des-26',
                'ritase' => [
                    [
                        'tanggal' => '2026-12-04',
                        'nopol' => 'BE 8888 AS',
                        'rit' => 1,
                        'tonase' => 25.00,
                        'tarif' => 150000,
                        'total' => 3750000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'MIP-PORT',
                    ],
                ],
            ],
        ]);

        $staging = $service->analisisStagingMigrasi($file, 'auto', 'belum_cair');
        $token = $staging['token'];

        // Panggil status progress sebelum eksekusi
        $progressAwal = $this->getJson('/admin/reports/migrasi/status-progress/'.$token);
        $progressAwal->assertOk()->assertJsonPath('status', 'belum_mulai');

        // Panggil eksekusi async di latar belakang
        $response = $this->postJson('/admin/reports/migrasi/eksekusi-async', [
            'token' => $token,
            'supir' => [
                'SUPIR ASYNC' => [
                    'aksi' => 'buat_akun',
                    'email' => 'supirasync_asm@gmail.com',
                    'nip' => 'DRF-SA-88',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'selesai')
            ->assertJsonPath('persen', 100);

        // Status progress di cache sekarang selesai 100%
        $progressAkhir = $this->getJson('/admin/reports/migrasi/status-progress/'.$token);
        $progressAkhir->assertOk()
            ->assertJsonPath('status', 'selesai')
            ->assertJsonPath('persen', 100);
    }

    public function test_deteksi_anomali_supir_memakai_banyak_unit_pada_tanggal_sama(): void
    {
        $service = app(ExcelPayrollMigrationService::class);

        $file = $this->buatFileExcelDummy([
            [
                'nama_sheet' => 'SUPIR MULTI UNIT',
                'nama_supir' => 'SUPIR MULTI UNIT',
                'nopol' => 'BE 1111 AA',
                'periode' => 'Des-26',
                'ritase' => [
                    [
                        'tanggal' => '2026-12-11',
                        'nopol' => 'BE 1111 AA',
                        'rit' => 1,
                        'tonase' => 28.00,
                        'tarif' => 150000,
                        'total' => 4200000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'RTE-A',
                    ],
                    [
                        'tanggal' => '2026-12-11',
                        'nopol' => 'BE 2222 BB',
                        'rit' => 1,
                        'tonase' => 29.00,
                        'tarif' => 150000,
                        'total' => 4350000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'RTE-B',
                    ],
                ],
            ],
        ]);

        $staging = $service->analisisStagingMigrasi($file, 'auto', 'belum_cair');

        $anomaliBanyakUnit = collect($staging['anomali'])->firstWhere('tipe', 'supir_banyak_unit');
        $this->assertNotNull($anomaliBanyakUnit, 'Anomali supir memakai banyak unit harus terdeteksi.');
        $this->assertStringContainsString('SUPIR MULTI UNIT', $anomaliBanyakUnit['keterangan']);
        $this->assertStringContainsString('2026-12-11', $anomaliBanyakUnit['keterangan']);
        $this->assertStringContainsString('BE 1111 AA', $anomaliBanyakUnit['keterangan']);
        $this->assertStringContainsString('BE 2222 BB', $anomaliBanyakUnit['keterangan']);
    }

    public function test_impor_sudah_cair_melunasi_angsuran_kasbon_dan_mengisi_jejak_pencairan(): void
    {
        $supir = $this->buatSupir([
            'nama_lengkap' => 'SUPIR KASBON',
            'nomor_induk_karyawan' => 'NIP-KASBON',
        ]);

        $kasbon = Kasbon::query()->create([
            'id_karyawan' => $supir->id,
            'tanggal_pengambilan' => '2026-11-01',
            'total_pinjaman' => 1_000_000,
            'nominal_angsuran' => 500_000,
            'jumlah_tenor' => 2,
            'sisa_pinjaman' => 1_000_000,
            'status' => 'approved',
        ]);

        $angsuran = AngsuranKasbon::query()->create([
            'id_kasbon' => $kasbon->id,
            'id_periode_gaji' => null,
            'urutan' => 1,
            'nominal' => 500_000,
            // Tanggal rencana potong berada DI DALAM rentang periode Des-26
            // (periode kini mengikuti tanggal nyata berkas: 20 Des 2026).
            'tanggal_rencana' => '2026-12-20',
            'status' => 'terjadwal',
        ]);

        $file = $this->buatFileExcelDummy([
            [
                'nama_sheet' => 'SUPIR KASBON',
                'nama_supir' => 'SUPIR KASBON',
                'nopol' => 'BE 6060 KK',
                'periode' => 'Des-26',
                'ritase' => [
                    [
                        'tanggal' => '2026-12-20',
                        'nopol' => 'BE 6060 KK',
                        'rit' => 1,
                        'tonase' => 25.00,
                        'tarif' => 150000,
                        'total' => 3750000,
                        'uang_jalan' => 200000,
                        'keterangan' => 'MIP-PORT',
                    ],
                ],
            ],
        ]);

        $service = app(ExcelPayrollMigrationService::class);
        $staging = $service->analisisStagingMigrasi($file, 'auto', 'sudah_cair', null, '2026-12-31');

        $service->eksekusiStagingMigrasi($staging['token']);

        $periode = PeriodeGaji::query()->where('kode', 'Des-26')->first();
        $this->assertNotNull($periode);
        $this->assertSame('paid', $periode->status);
        $this->assertNotNull($periode->dicairkan_pada);

        $penggajian = Penggajian::query()->where('id_karyawan', $supir->id)->first();
        $this->assertNotNull($penggajian);
        $this->assertSame('paid', $penggajian->status);
        $this->assertNotNull($penggajian->dicairkan_pada);

        $angsuran->refresh();
        $this->assertSame('terpotong', $angsuran->status);
        $this->assertSame('2026-12-31', $angsuran->tanggal_potong?->toDateString());
        $this->assertSame($periode->id, (int) $angsuran->id_periode_gaji);
        $kasbon->refresh();
        $this->assertSame(500_000, (int) $kasbon->sisa_pinjaman);
    }

    public function test_berkas_berekstensi_xls_ditolak_oleh_validasi_server(): void
    {
        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);

        $response = $this->post('/admin/reports/migrasi/preview', [
            'berkas_excel' => UploadedFile::fake()->create('rekap-lama.xls', 10, 'application/vnd.ms-excel'),
            'target_periode' => 'auto',
            'status_pencairan' => 'belum_cair',
            'konfirmasi_pencairan' => '1',
        ]);

        $response->assertSessionHasErrors('berkas_excel');
    }

    public function test_template_kosong_memberi_pesan_galat_yang_ramah(): void
    {
        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);

        $file = $this->buatFileExcelDummy([
            [
                'nama_sheet' => 'SUPIR KOSONG',
                'nama_supir' => 'SUPIR KOSONG',
                'nopol' => 'BE 0000 KK',
                'periode' => 'Des-26',
                'ritase' => [],
            ],
        ]);

        $response = $this->post('/admin/reports/migrasi/preview', [
            'berkas_excel' => $file,
            'target_periode' => 'auto',
            'status_pencairan' => 'belum_cair',
            'konfirmasi_pencairan' => '1',
        ]);

        $response->assertSessionHasErrors('berkas_excel');
        $pesan = session('errors')->get('berkas_excel')[0];
        $this->assertStringContainsString('template kosong', $pesan);
    }

    public function test_unduh_contoh_format_dan_template_mengunduh_berkas_xlsx(): void
    {
        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);

        $res1 = $this->get('/admin/reports/migrasi/dummy');
        $res1->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $res1->headers->get('Content-Type'));
        $this->assertStringContainsString('CONTOH_FORMAT_Slip_Gaji.xlsx', (string) $res1->headers->get('Content-Disposition'));
        $this->assertGreaterThan(100_000, $res1->getFile()->getSize(), 'Contoh format harus berisi data, bukan template kosong.');

        $res2 = $this->get('/admin/reports/migrasi/template');
        $res2->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $res2->headers->get('Content-Type'));
        $this->assertStringContainsString('Template_Kosong_Slip_Gaji.xlsx', (string) $res2->headers->get('Content-Disposition'));
        $this->assertLessThan(100_000, $res2->getFile()->getSize(), 'Template kosong tidak boleh berisi data contoh.');
    }
}
