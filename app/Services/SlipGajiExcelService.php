<?php

namespace App\Services;

use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use RuntimeException;
use ZipArchive;

/**
 * ============================================================================
 * LAYANAN: PENULIS BERKAS EXCEL SLIP GAJI (.XLSX)
 * ============================================================================
 *
 * Analogi Sederhana:
 * Ibarat "Mesin Fotokopi Map Gaji per Supir". Setiap supir mendapat SATU lembar
 * (sheet) berisi kop "RINCIAN GAJI SUPIR", identitas supir, tabel pengantaran
 * harian, baris JUMLAH, lalu blok REKAPITULASI sampai GAJI BERSIH DITERIMA.
 *
 * Mengikuti format berkas acuan `dokumen/Slip Gaji - KUWAT SLAMET - Jan-26.xlsx`:
 * - Satu sheet per supir, nama sheet = nama supir (maks 31 karakter).
 * - TIDAK ada sheet panduan/petunjuk, dan baris "Keterangan & Petunjuk" dibuang.
 * - Baris VENDOR dibuang karena konsep vendor sudah dihapus dari sistem.
 * - Sel diisi NILAI JADI (bukan formula) agar berkas hasil ekspor bisa diimpor
 *   kembali lewat fitur migrasi Excel yang membaca tag <v>.
 *
 * Ditulis manual memakai ZipArchive agar tidak menambah dependensi baru
 * (pola yang sama dengan pembaca Excel di ExcelPayrollMigrationService).
 * ============================================================================
 */
class SlipGajiExcelService
{
    /** Jumlah kolom format slip (A..L). */
    private const JUMLAH_KOLOM = 12;

    /**
     * Bangun berkas .xlsx satu sheet per supir dan kembalikan path sementaranya.
     *
     * @param  iterable<int, Penggajian>  $daftarPenggajian
     */
    public function bangunBerkas(PeriodeGaji $periode, iterable $daftarPenggajian): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZipArchive belum aktif sehingga berkas .xlsx tidak dapat dibuat.');
        }

        $daftarSheet = [];

        foreach ($daftarPenggajian as $penggajian) {
            $daftarSheet[] = $this->susunSheet($periode, $penggajian);
        }

        if ($daftarSheet === []) {
            throw new RuntimeException('Tidak ada baris penggajian pada periode ini sehingga slip tidak dapat dibuat.');
        }

        $pathSementara = tempnam(sys_get_temp_dir(), 'slip-gaji-').'.xlsx';

        // Indeks shared string WAJIB global lintas sheet, jika tidak, teks akan
        // menunjuk ke entri yang salah ketika berkas dibuka Excel.
        $petaTeksGlobal = [];
        foreach ($daftarSheet as $sheet) {
            foreach ($sheet['baris'] as $baris) {
                foreach ($baris as $sel) {
                    if (is_string($sel) && $sel !== '' && ! isset($petaTeksGlobal[$sel])) {
                        $petaTeksGlobal[$sel] = count($petaTeksGlobal);
                    }
                }
            }
        }

        $arsip = new ZipArchive();
        if ($arsip->open($pathSementara, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Berkas Excel sementara tidak dapat dibuat.');
        }

        $arsip->addFromString('[Content_Types].xml', $this->xmlContentTypes(count($daftarSheet)));
        $arsip->addFromString('_rels/.rels', $this->xmlRelUtama());
        $arsip->addFromString('xl/workbook.xml', $this->xmlWorkbook($daftarSheet));
        $arsip->addFromString('xl/_rels/workbook.xml.rels', $this->xmlRelWorkbook(count($daftarSheet)));
        $arsip->addFromString('xl/styles.xml', $this->xmlStyles());
        $arsip->addFromString('xl/sharedStrings.xml', $this->xmlSharedStrings($petaTeksGlobal));

        foreach ($daftarSheet as $indeks => $sheet) {
            $arsip->addFromString(
                'xl/worksheets/sheet'.($indeks + 1).'.xml',
                $this->xmlSheet($sheet['baris'], $petaTeksGlobal),
            );
        }

        $arsip->close();

        return $pathSementara;
    }

    /**
     * Susun isi satu sheet untuk satu supir.
     *
     * Struktur mengikuti berkas acuan:
     * R1 judul | R3-R4 identitas | R6-R7 header tabel | R8.. data |
     * baris JUMLAH | blok REKAPITULASI (kolom H label, kolom K nilai).
     *
     * @return array{nama: string, baris: array<int, array<int, mixed>>}
     */
    private function susunSheet(PeriodeGaji $periode, Penggajian $penggajian): array
    {
        $karyawan = $penggajian->karyawan;
        $truk = $penggajian->truk;
        $namaSupir = $karyawan?->nama_lengkap ?? 'SUPIR';
        $nomorPolisi = $truk?->nomor_polisi ?? '-';
        $merekModel = $truk?->merek_model ?? '-';

        $daftarRitase = Ritase::query()
            ->with('rute')
            ->where('id_periode_gaji', $periode->id)
            ->where('id_karyawan', $penggajian->id_karyawan)
            ->orderByDesc('tanggal_ritase')
            ->orderByDesc('id')
            ->get();

        $baris = [];
        $baris[] = ['RINCIAN GAJI SUPIR'];
        $baris[] = [];
        $baris[] = ['NAMA SUPIR', $namaSupir, '', '', 'MERK', $merekModel, '', '', '', '', 'PERIODE', $periode->kode];
        $baris[] = ['', '', '', '', 'PLAT', $nomorPolisi];
        $baris[] = [];
        $baris[] = ['Tanggal', 'No. Pol', 'Material', 'Vol', '', '', 'Angkutan', '', '', 'Angkutan', 'Uang Jalan', 'Keterangan'];
        $baris[] = ['', '', '', 'Rit', 'Ton', 'M³', 'Rit', '/ Ton', '/ M³', '', '', ''];

        foreach ($daftarRitase as $ritase) {
            $kodeRute = $ritase->rute?->kode ?? '';
            if ($kodeRute === 'DMY-NOV') {
                $kodeRute = '';
            }

            $baris[] = [
                $ritase->tanggal_ritase?->format('Y-m-d') ?? '',
                $nomorPolisi,
                $ritase->rute?->material ?? '',
                (int) $ritase->jumlah_rit,
                round(((int) $ritase->tonase_kg) / 1000, 2),
                '',
                '',
                (int) $ritase->tarif_per_ton,
                '',
                (int) $ritase->total_angkutan,
                (int) $ritase->uang_jalan,
                $kodeRute,
            ];
        }

        $baris[] = [];
        $baris[] = [
            'JUMLAH',
            '',
            '',
            (int) $penggajian->total_rit,
            round(((int) $penggajian->total_tonase_kg) / 1000, 2),
            '',
            '',
            '',
            '',
            (int) $penggajian->total_angkutan_kotor,
            (int) $penggajian->total_uang_jalan,
            '',
        ];
        $baris[] = [];
        $baris[] = ['', '', '', '', '', '', '', 'REKAPITULASI'];
        $baris[] = ['', '', '', '', '', '', '', 'Jumlah Angkutan', '', '', (int) $penggajian->total_angkutan_kotor];
        $baris[] = ['', '', '', '', '', '', '', 'Potongan Operasional', '', '', (int) $penggajian->potongan_operasional];
        $baris[] = ['', '', '', '', '', '', '', 'Jumlah Angkutan (setelah operasional)', '', '', (int) $penggajian->angkutan_bersih];
        $baris[] = ['', '', '', '', '', '', '', 'Jumlah Uang Jalan', '', '', (int) $penggajian->total_uang_jalan];
        $baris[] = ['', '', '', '', '', '', '', 'Biaya Service & S.part', '', '', (int) $penggajian->biaya_sparepart];
        $baris[] = ['', '', '', '', '', '', '', 'Pend', '', '', (int) $penggajian->angkutan_bersih];
        $baris[] = ['', '', '', '', '', '', '', 'Uang Jalan (uj)', '', '', (int) $penggajian->total_uang_jalan];
        $baris[] = ['', '', '', '', '', '', '', 'S.part', '', '', (int) $penggajian->biaya_sparepart];
        $baris[] = ['', '', '', '', '', '', '', 'Hasil Bersih Armada', '', '', (int) $penggajian->hasil_bersih_armada];
        // Persentase komisi tidak ditampilkan sebagai angka persen (AGENTS.md).
        $baris[] = ['', '', '', '', '', '', '', 'Gaji Komisi (Gross Pay)', '', '', (int) $penggajian->gaji_komisi];
        $baris[] = ['', '', '', '', '', '', '', 'Tunjangan', '', '', (int) $penggajian->total_tunjangan];
        $baris[] = ['', '', '', '', '', '', '', 'Potongan BPJS', '', '', (int) $penggajian->potongan_bpjs];
        $baris[] = ['', '', '', '', '', '', '', 'Potongan Kasbon', '', '', (int) $penggajian->potongan_kasbon];
        $baris[] = ['', '', '', '', '', '', '', 'GAJI BERSIH DITERIMA', '', '', (int) $penggajian->gaji_bersih];

        return [
            'nama' => $this->namaSheetAman($namaSupir),
            'baris' => $baris,
        ];
    }

    /**
     * Nama sheet Excel: maksimal 31 karakter, tanpa karakter terlarang, unik.
     */
    private function namaSheetAman(string $nama): string
    {
        $bersih = preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $nama) ?? $nama;
        $bersih = trim(preg_replace('/\s+/u', ' ', $bersih) ?? $bersih);

        if ($bersih === '') {
            $bersih = 'SLIP';
        }

        return mb_substr($bersih, 0, 31);
    }

    // =========================================================================
    // Bagian XML pembentuk berkas .xlsx
    // =========================================================================

    private function xmlContentTypes(int $jumlahSheet): string
    {
        $overrideSheet = '';
        for ($i = 1; $i <= $jumlahSheet; $i++) {
            $overrideSheet .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" '
                .'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            .$overrideSheet
            .'</Types>';
    }

    private function xmlRelUtama(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, array{nama: string, baris: array<int, array<int, mixed>>}>  $daftarSheet
     */
    private function xmlWorkbook(array $daftarSheet): string
    {
        $sheetXml = '';
        foreach ($daftarSheet as $indeks => $sheet) {
            $sheetXml .= '<sheet name="'.$this->escape($sheet['nama']).'" sheetId="'.($indeks + 1).'" r:id="rId'.($indeks + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheetXml.'</sheets>'
            .'</workbook>';
    }

    private function xmlRelWorkbook(int $jumlahSheet): string
    {
        $relSheet = '';
        for ($i = 1; $i <= $jumlahSheet; $i++) {
            $relSheet .= '<Relationship Id="rId'.$i.'" '
                .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                .'Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $idStyle = $jumlahSheet + 1;
        $idShared = $jumlahSheet + 2;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relSheet
            .'<Relationship Id="rId'.$idStyle.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rId'.$idShared.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            .'</Relationships>';
    }

    private function xmlStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3">'
            .'<font><sz val="10"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="12"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE7EEF7"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5">'
            // 0: normal
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: judul (bold besar)
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            // 2: label bold + border
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            // 3: angka ribuan + border
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            // 4: teks + border
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /**
     * Bangun isi sharedStrings.xml dari peta teks global.
     *
     * @param  array<string, int>  $petaTeks
     */
    private function xmlSharedStrings(array $petaTeks): string
    {
        $items = '';
        foreach (array_keys($petaTeks) as $teks) {
            $items .= '<si><t xml:space="preserve">'.$this->escape((string) $teks).'</t></si>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'count="'.count($petaTeks).'" uniqueCount="'.count($petaTeks).'">'.$items.'</sst>';
    }

    /**
     * @param  array<string, int>  $petaTeksGlobal  indeks shared string global lintas sheet
     * @param  array<int, array<int, mixed>>  $baris
     */
    private function xmlSheet(array $baris, array $petaTeksGlobal): string
    {
        $lebarKolom = '<cols>'
            .'<col min="1" max="1" width="12" customWidth="1"/>'
            .'<col min="2" max="2" width="13" customWidth="1"/>'
            .'<col min="3" max="3" width="12" customWidth="1"/>'
            .'<col min="4" max="4" width="7" customWidth="1"/>'
            .'<col min="5" max="5" width="9" customWidth="1"/>'
            .'<col min="6" max="7" width="7" customWidth="1"/>'
            .'<col min="8" max="8" width="11" customWidth="1"/>'
            .'<col min="9" max="9" width="8" customWidth="1"/>'
            .'<col min="10" max="11" width="14" customWidth="1"/>'
            .'<col min="12" max="12" width="12" customWidth="1"/>'
            .'</cols>';

        $isiBaris = '';

        foreach ($baris as $indeksBaris => $daftarSel) {
            $nomorBaris = $indeksBaris + 1;
            $selXml = '';

            foreach (array_values($daftarSel) as $indeksKolom => $nilai) {
                $hurufKolom = $this->hurufKolom($indeksKolom);

                if ($nilai === null || $nilai === '') {
                    continue;
                }

                // Baris judul memakai style 1, header tabel (baris ke-6/7) style 2.
                $gaya = 0;
                if ($nomorBaris === 1) {
                    $gaya = 1;
                } elseif ($nomorBaris === 6 || $nomorBaris === 7) {
                    $gaya = 2;
                }

                if (is_int($nilai) || is_float($nilai)) {
                    $selXml .= '<c r="'.$hurufKolom.$nomorBaris.'" s="'.$gaya.'"><v>'
                        .(is_float($nilai) ? rtrim(rtrim(number_format($nilai, 2, '.', ''), '0'), '.') : $nilai)
                        .'</v></c>';
                } else {
                    $selXml .= '<c r="'.$hurufKolom.$nomorBaris.'" s="'.$gaya.'" t="s"><v>'
                        .$petaTeksGlobal[$nilai].'</v></c>';
                }
            }

            if ($selXml !== '') {
                $isiBaris .= '<row r="'.$nomorBaris.'">'.$selXml.'</row>';
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$lebarKolom
            .'<sheetData>'.$isiBaris.'</sheetData>'
            .'</worksheet>';
    }

    private function hurufKolom(int $indeksNolBasis): string
    {
        $huruf = '';
        $angka = $indeksNolBasis + 1;

        while ($angka > 0) {
            $sisa = ($angka - 1) % 26;
            $huruf = chr(65 + $sisa).$huruf;
            $angka = intdiv($angka - 1, 26);
        }

        return $huruf;
    }

    private function escape(string $teks): string
    {
        // Bersihkan karakter kontrol yang tidak valid di XML.
        $bersih = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $teks) ?? $teks;

        return htmlspecialchars($bersih, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
