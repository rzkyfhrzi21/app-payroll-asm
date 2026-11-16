<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AngsuranKasbon;
use App\Models\JenisTruk;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\Truk;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ExcelPayrollMigrationService
{
    private PayrollCalculationService $layananKalkulasiGaji;

    public function __construct(?PayrollCalculationService $layananKalkulasiGaji = null)
    {
        $this->layananKalkulasiGaji = $layananKalkulasiGaji ?? new PayrollCalculationService;
    }

    /**
     * Memproses berkas upload (.csv, .txt, atau .xlsx) yang berisi satu atau banyak
     * blok/sheet "RINCIAN GAJI SUPIR" ke tabel periode_gaji, ritase, dan penggajian.
     *
     * @return array{
     *     jumlah_slip: int,
     *     jumlah_ritase: int,
     *     daftar_periode: array<int, string>,
     *     status_pencairan: string,
     *     total_gaji_bersih: int
     * }
     */
    public function imporBerkasMigrasi(
        UploadedFile $berkas,
        string $targetPeriode = 'auto',
        string $statusPencairan = 'belum_cair',
        ?string $kodePeriodeBaru = null,
        ?string $tanggalPencairan = null,
    ): array {
        $daftarMatriksBaris = $this->ekstrakMatriksDariBerkas($berkas);
        $daftarBlokSlip = $this->pecahMenjadiBlokSlip($daftarMatriksBaris);

        if ($daftarBlokSlip === []) {
            throw new \RuntimeException('Format berkas tidak dikenali. Pastikan berkas memuat blok "RINCIAN GAJI SUPIR" dan tabel Tanggal / No. Pol / Tonase.');
        }

        return DB::transaction(function () use (
            $daftarBlokSlip,
            $targetPeriode,
            $statusPencairan,
            $kodePeriodeBaru,
            $tanggalPencairan
        ): array {
            $jumlahSlipDiproses = 0;
            $jumlahRitaseDiproses = 0;
            $totalGajiBersihDiproses = 0;
            $periodeTerdampak = [];

            foreach ($daftarBlokSlip as $indeksBlok => $blok) {
                $kodePeriode = $this->tentukanKodePeriode(
                    $blok['kode_periode'],
                    $targetPeriode,
                    $kodePeriodeBaru
                );

                $periodeGaji = $this->ambilAtauBuatPeriode(
                    $kodePeriode,
                    $blok['ritase'],
                    $statusPencairan,
                    $tanggalPencairan
                );

                $periodeTerdampak[$periodeGaji->kode] = $periodeGaji->kode;

                $truk = $this->ambilAtauBuatTruk(
                    $blok['nomor_polisi'],
                    $blok['merek_truk']
                );

                $karyawan = $this->ambilAtauBuatKaryawan(
                    $blok['nama_supir'],
                    $truk,
                    $periodeGaji,
                    $indeksBlok + 1
                );

                $statusTiketRitase = $statusPencairan === 'sudah_cair' ? 'terverifikasi' : 'draft';

                Ritase::query()
                    ->where('id_periode_gaji', $periodeGaji->id)
                    ->where('id_karyawan', $karyawan->id)
                    ->delete();

                $totalTonaseKgHitung = 0;
                $totalAngkutanHitung = 0;
                $totalUangJalanHitung = 0;
                $jumlahRitHitung = 0;

                foreach ($blok['ritase'] as $barisRit) {
                    $rute = $this->ambilAtauBuatRute(
                        $barisRit['keterangan'],
                        $barisRit['material'],
                        $barisRit['tarif_per_ton'],
                        $barisRit['uang_jalan'],
                        $periodeGaji->kode
                    );

                    $tanggalRitase = $this->normalisasiTanggalRitase(
                        $barisRit['tanggal'],
                        $periodeGaji->kode
                    );

                    Ritase::create([
                        'id_periode_gaji' => $periodeGaji->id,
                        'id_karyawan' => $karyawan->id,
                        'id_truk' => $truk->id,
                        'id_rute' => $rute->id,
                        'tanggal_ritase' => $tanggalRitase,
                        'jumlah_rit' => $barisRit['jumlah_rit'],
                        'tonase_kg' => $barisRit['tonase_kg'],
                        'volume_liter' => null,
                        'tarif_per_ton' => $barisRit['tarif_per_ton'],
                        'total_angkutan' => $barisRit['total_angkutan'],
                        'uang_jalan' => $barisRit['uang_jalan'],
                        'status' => $statusTiketRitase,
                    ]);

                    $jumlahRitHitung += $barisRit['jumlah_rit'];
                    $totalTonaseKgHitung += $barisRit['tonase_kg'];
                    $totalAngkutanHitung += $barisRit['total_angkutan'];
                    $totalUangJalanHitung += $barisRit['uang_jalan'];
                    $jumlahRitaseDiproses++;
                }

                $rekap = $blok['rekap'];
                $angkutanKotor = $rekap['total_angkutan_kotor'] > 0
                    ? $rekap['total_angkutan_kotor']
                    : $totalAngkutanHitung;
                $potonganOperasional = $rekap['potongan_operasional'];
                $totalUangJalan = $rekap['total_uang_jalan'] > 0
                    ? $rekap['total_uang_jalan']
                    : $totalUangJalanHitung;
                $biayaSparepart = $rekap['biaya_sparepart'];
                $persentaseKomisiBp = $rekap['persentase_komisi_bp'] > 0
                    ? $rekap['persentase_komisi_bp']
                    : 1000;
                $totalTunjangan = $rekap['total_tunjangan'];
                $potonganBpjs = $rekap['potongan_bpjs'];

                // Sesuai AGENTS.md 3.6: DILARANG memakai angka manual pada penggajian.potongan_kasbon;
                // nilainya berasal dari SUM(angsuran_kasbon.nominal) pada periode berjalan.
                $potonganKasbon = (int) AngsuranKasbon::query()
                    ->where('id_periode_gaji', $periodeGaji->id)
                    ->whereHas('kasbon', fn ($q) => $q->where('id_karyawan', $karyawan->id))
                    ->sum('nominal');

                // Gunakan PayrollCalculationService::hitungKomponenGaji() sebagai sumber tunggal
                // rumus penggajian ketika sel hasil rekap Excel kosong atau belum terisi.
                $hasilPerhitunganRumus = $this->layananKalkulasiGaji->hitungKomponenGaji([
                    'total_rit' => $jumlahRitHitung,
                    'total_tonase_kg' => $totalTonaseKgHitung,
                    'total_angkutan_kotor' => $angkutanKotor,
                    'potongan_operasional' => $potonganOperasional,
                    'total_uang_jalan' => $totalUangJalan,
                    'biaya_sparepart' => $biayaSparepart,
                    'persentase_komisi_bp' => $persentaseKomisiBp,
                    'total_tunjangan' => $totalTunjangan,
                    'potongan_bpjs' => $potonganBpjs,
                    'potongan_kasbon' => $potonganKasbon,
                ]);

                // Prioritaskan nilai sel rekap Excel historis apabila sudah terisi (> 0).
                // Apabila sel tahap lanjutan kosong (0) tetapi sel tahap sebelumnya di-override,
                // hitung ulang tahap lanjutan tetap melalui hitungKomponenGaji().
                $angkutanBersih = $rekap['angkutan_bersih'] > 0
                    ? $rekap['angkutan_bersih']
                    : $hasilPerhitunganRumus['angkutan_bersih'];

                $hasilBersihArmada = $rekap['hasil_bersih_armada'] > 0
                    ? $rekap['hasil_bersih_armada']
                    : ($angkutanBersih !== $hasilPerhitunganRumus['angkutan_bersih']
                        ? $this->layananKalkulasiGaji->hitungKomponenGaji([
                            'total_angkutan_kotor' => $angkutanBersih,
                            'potongan_operasional' => 0,
                            'total_uang_jalan' => $totalUangJalan,
                            'biaya_sparepart' => $biayaSparepart,
                        ])['hasil_bersih_armada']
                        : $hasilPerhitunganRumus['hasil_bersih_armada']);

                $gajiKomisi = $rekap['gaji_komisi'] > 0
                    ? $rekap['gaji_komisi']
                    : ($hasilBersihArmada !== $hasilPerhitunganRumus['hasil_bersih_armada']
                        ? $this->layananKalkulasiGaji->hitungKomponenGaji([
                            'total_angkutan_kotor' => $hasilBersihArmada,
                            'persentase_komisi_bp' => $persentaseKomisiBp,
                        ])['gaji_komisi']
                        : $hasilPerhitunganRumus['gaji_komisi']);

                $gajiBersih = $rekap['gaji_bersih'] > 0
                    ? $rekap['gaji_bersih']
                    : ($gajiKomisi !== $hasilPerhitunganRumus['gaji_komisi']
                        ? $this->layananKalkulasiGaji->hitungKomponenGaji([
                            'total_angkutan_kotor' => $gajiKomisi,
                            'persentase_komisi_bp' => 10000,
                            'total_tunjangan' => $totalTunjangan,
                            'potongan_bpjs' => $potonganBpjs,
                            'potongan_kasbon' => $potonganKasbon,
                        ])['gaji_bersih']
                        : $hasilPerhitunganRumus['gaji_bersih']);

                Penggajian::updateOrCreate(
                    [
                        'id_periode_gaji' => $periodeGaji->id,
                        'id_karyawan' => $karyawan->id,
                    ],
                    [
                        'id_truk' => $truk->id,
                        'total_rit' => $hasilPerhitunganRumus['total_rit'],
                        'total_tonase_kg' => $hasilPerhitunganRumus['total_tonase_kg'],
                        'total_angkutan_kotor' => $hasilPerhitunganRumus['total_angkutan_kotor'],
                        'potongan_operasional' => $hasilPerhitunganRumus['potongan_operasional'],
                        'angkutan_bersih' => $angkutanBersih,
                        'total_uang_jalan' => $hasilPerhitunganRumus['total_uang_jalan'],
                        'biaya_sparepart' => $hasilPerhitunganRumus['biaya_sparepart'],
                        'hasil_bersih_armada' => $hasilBersihArmada,
                        'persentase_komisi_bp' => $hasilPerhitunganRumus['persentase_komisi_bp'],
                        'gaji_komisi' => $gajiKomisi,
                        'total_tunjangan' => $hasilPerhitunganRumus['total_tunjangan'],
                        'potongan_bpjs' => $hasilPerhitunganRumus['potongan_bpjs'],
                        'potongan_kasbon' => $hasilPerhitunganRumus['potongan_kasbon'],
                        'gaji_bersih' => $gajiBersih,
                    ]
                );

                $jumlahSlipDiproses++;
                $totalGajiBersihDiproses += $gajiBersih;
            }

            return [
                'jumlah_slip' => $jumlahSlipDiproses,
                'jumlah_ritase' => $jumlahRitaseDiproses,
                'daftar_periode' => array_values($periodeTerdampak),
                'status_pencairan' => $statusPencairan,
                'total_gaji_bersih' => $totalGajiBersihDiproses,
            ];
        });
    }

    /**
     * Ekspor data periode (atau semua periode) ke format CSV "RINCIAN GAJI SUPIR"
     * yang identik dengan struktur Excel fisik sehingga dapat dibuka di Excel maupun diimpor ulang.
     */
    public function eksporFormatMigrasi(?string $kodePeriode = null): StreamedResponse
    {
        $kueriPeriode = PeriodeGaji::query()->orderByDesc('tanggal_selesai')->orderByDesc('id');
        if ($kodePeriode !== null && $kodePeriode !== '' && $kodePeriode !== 'all') {
            $kueriPeriode->where('kode', $kodePeriode);
        }

        $daftarPeriode = $kueriPeriode->get();
        $labelBerkas = ($kodePeriode !== null && $kodePeriode !== '' && $kodePeriode !== 'all')
            ? strtolower($kodePeriode)
            : 'semua-periode';
        $namaBerkas = "rincian-gaji-supir-{$labelBerkas}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($daftarPeriode): void {
            $keluaran = fopen('php://output', 'w');
            if ($keluaran === false) {
                return;
            }

            fwrite($keluaran, "\xEF\xBB\xBF");

            foreach ($daftarPeriode as $periode) {
                $daftarPenggajian = Penggajian::query()
                    ->with(['karyawan', 'truk'])
                    ->where('id_periode_gaji', $periode->id)
                    ->orderBy('id')
                    ->get();

                $ritasePerKaryawan = Ritase::query()
                    ->with('rute')
                    ->where('id_periode_gaji', $periode->id)
                    ->orderByDesc('tanggal_ritase')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('id_karyawan');

                foreach ($daftarPenggajian as $penggajian) {
                    $karyawan = $penggajian->karyawan;
                    $truk = $penggajian->truk;
                    $koleksiRitase = $ritasePerKaryawan->get($penggajian->id_karyawan, collect());

                    fputcsv($keluaran, ['RINCIAN GAJI SUPIR', '', '', '', '', '', '', '', '', '', '', '']);
                    fputcsv($keluaran, array_fill(0, 12, ''));
                    fputcsv($keluaran, [
                        'NAMA SUPIR',
                        $karyawan?->nama_lengkap ?? 'SUPIR',
                        '',
                        '',
                        'MERK',
                        $truk?->merek_model ?? 'HINO PUTIH',
                        '',
                        '',
                        '',
                        '',
                        'PERIODE',
                        $periode->kode,
                    ]);
                    fputcsv($keluaran, [
                        '',
                        '',
                        '',
                        '',
                        'PLAT',
                        $truk?->nomor_polisi ?? '-',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ]);
                    fputcsv($keluaran, array_fill(0, 12, ''));
                    fputcsv($keluaran, ['Tanggal', 'No. Pol', 'Material', 'Vol', '', '', 'Angkutan', '', '', 'Angkutan', 'Uang Jalan', 'Keterangan']);
                    fputcsv($keluaran, ['', '', '', 'Rit', 'Ton', 'M³', 'Rit', '/ Ton', '/ M³', '', '', '']);

                    foreach ($koleksiRitase as $ritase) {
                        $tonaseTon = round(((int) $ritase->tonase_kg) / 1000, 2);
                        $kodeRute = $ritase->rute?->kode ?? '-';
                        if ($kodeRute === 'DMY-NOV') {
                            $kodeRute = '-';
                        }

                        fputcsv($keluaran, [
                            $ritase->tanggal_ritase?->format('Y-m-d') ?? '',
                            $truk?->nomor_polisi ?? '',
                            $ritase->rute?->material ?? 'BATU BARA',
                            (int) $ritase->jumlah_rit,
                            $tonaseTon,
                            '',
                            '',
                            (int) $ritase->tarif_per_ton,
                            '',
                            (int) $ritase->total_angkutan,
                            (int) $ritase->uang_jalan,
                            $kodeRute,
                        ]);
                    }

                    fputcsv($keluaran, array_fill(0, 12, ''));
                    $totalTonaseRingkas = round(((int) $penggajian->total_tonase_kg) / 1000, 2);
                    fputcsv($keluaran, [
                        'JUMLAH',
                        '',
                        '',
                        (int) $penggajian->total_rit,
                        $totalTonaseRingkas,
                        '',
                        '',
                        '',
                        '',
                        (int) $penggajian->total_angkutan_kotor,
                        (int) $penggajian->total_uang_jalan,
                        '',
                    ]);
                    fputcsv($keluaran, array_fill(0, 12, ''));
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'REKAPITULASI', '', '', '', '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Jumlah Angkutan', '', '', (int) $penggajian->total_angkutan_kotor, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Potongan Operasional', '', '', (int) $penggajian->potongan_operasional, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Jumlah Angkutan (setelah operasional)', '', '', (int) $penggajian->angkutan_bersih, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Jumlah Uang Jalan', '', '', (int) $penggajian->total_uang_jalan, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Biaya Service & S.part', '', '', (int) $penggajian->biaya_sparepart, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Pend', '', '', (int) $penggajian->angkutan_bersih, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Uang Jalan (uj)', '', '', (int) $penggajian->total_uang_jalan, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'S.part', '', '', (int) $penggajian->biaya_sparepart, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Hasil Bersih Armada', '', '', (int) $penggajian->hasil_bersih_armada, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Persentase Komisi', '', '', ((int) $penggajian->persentase_komisi_bp) / 10000, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Gaji Komisi (Gross Pay)', '', '', (int) $penggajian->gaji_komisi, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Tunjangan', '', '', (int) $penggajian->total_tunjangan, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Potongan BPJS', '', '', (int) $penggajian->potongan_bpjs, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'Potongan Kasbon', '', '', (int) $penggajian->potongan_kasbon, '']);
                    fputcsv($keluaran, ['', '', '', '', '', '', '', 'GAJI BERSIH DITERIMA', '', '', (int) $penggajian->gaji_bersih, '']);
                    fputcsv($keluaran, array_fill(0, 12, ''));
                }
            }

            fclose($keluaran);
        }, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Unduh berkas contoh dummy migrasi (8 slip supir: Jan-26 & Nov-25).
     */
    public function unduhBerkasDummyMigrasi(): StreamedResponse
    {
        $lokasiDummy = storage_path('app/migrasi-dummy-excel.csv');
        if (! is_file($lokasiDummy)) {
            return $this->eksporFormatMigrasi('all');
        }

        return response()->streamDownload(function () use ($lokasiDummy): void {
            $keluaran = fopen('php://output', 'w');
            if ($keluaran === false) {
                return;
            }
            fwrite($keluaran, "\xEF\xBB\xBF");
            fwrite($keluaran, (string) file_get_contents($lokasiDummy));
            fclose($keluaran);
        }, 'Data_Dummy_Migrasi_Penggajian_8_Supir.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Unduh template kosong format RINCIAN GAJI SUPIR (.csv).
     */
    public function unduhTemplateKosong(): StreamedResponse
    {
        $namaBerkas = 'template-migrasi-rincian-gaji-supir.csv';

        return response()->streamDownload(function (): void {
            $keluaran = fopen('php://output', 'w');
            if ($keluaran === false) {
                return;
            }

            fwrite($keluaran, "\xEF\xBB\xBF");
            $barisTemplate = [
                ['RINCIAN GAJI SUPIR', '', '', '', '', '', '', '', '', '', '', ''],
                ['', '', '', '', '', '', '', '', '', '', '', ''],
                ['NAMA SUPIR', 'KUWAT SLAMET', '', '', 'MERK', 'HINO PUTIH', '', '', '', '', 'PERIODE', 'Jan-26'],
                ['', '', '', '', 'PLAT', 'BE 8639 AAU', '', '', '', '', '', ''],
                ['', '', '', '', '', '', '', '', '', '', '', ''],
                ['Tanggal', 'No. Pol', 'Material', 'Vol', '', '', 'Angkutan', '', '', 'Angkutan', 'Uang Jalan', 'Keterangan'],
                ['', '', '', 'Rit', 'Ton', 'M³', 'Rit', '/ Ton', '/ M³', '', '', ''],
                ['2026-09-18', 'BE 8639 AAU', 'BATU BARA', '1', '28.48', '', '', '158760', '', '4521485', '2250000', 'MIP-PORT'],
                ['', '', '', '', '', '', '', '', '', '', '', ''],
                ['JUMLAH', '', '', '1', '28.48', '', '', '', '', '4521485', '2250000', ''],
                ['', '', '', '', '', '', '', '', '', '', '', ''],
                ['', '', '', '', '', '', '', 'REKAPITULASI', '', '', '', ''],
                ['', '', '', '', '', '', '', 'Jumlah Angkutan', '', '', '4521485', ''],
                ['', '', '', '', '', '', '', 'Potongan Operasional', '', '', '0', ''],
                ['', '', '', '', '', '', '', 'Jumlah Angkutan (setelah operasional)', '', '', '4521485', ''],
                ['', '', '', '', '', '', '', 'Jumlah Uang Jalan', '', '', '2250000', ''],
                ['', '', '', '', '', '', '', 'Biaya Service & S.part', '', '', '0', ''],
                ['', '', '', '', '', '', '', 'Pend', '', '', '4521485', ''],
                ['', '', '', '', '', '', '', 'Uang Jalan (uj)', '', '', '2250000', ''],
                ['', '', '', '', '', '', '', 'S.part', '', '', '0', ''],
                ['', '', '', '', '', '', '', 'Hasil Bersih Armada', '', '', '2271485', ''],
                ['', '', '', '', '', '', '', 'Persentase Komisi', '', '', '0.1', ''],
                ['', '', '', '', '', '', '', 'Gaji Komisi (Gross Pay)', '', '', '227149', ''],
                ['', '', '', '', '', '', '', 'Tunjangan', '', '', '0', ''],
                ['', '', '', '', '', '', '', 'Potongan BPJS', '', '', '129000', ''],
                ['', '', '', '', '', '', '', 'Potongan Kasbon', '', '', '0', ''],
                ['', '', '', '', '', '', '', 'GAJI BERSIH DITERIMA', '', '', '98149', ''],
            ];

            foreach ($barisTemplate as $baris) {
                fputcsv($keluaran, $baris);
            }

            fclose($keluaran);
        }, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, array<int, array<int, string>>>
     */
    private function ekstrakMatriksDariBerkas(UploadedFile $berkas): array
    {
        $ekstensi = strtolower((string) $berkas->getClientOriginalExtension());
        $lokasiSementara = $berkas->getRealPath();

        if ($lokasiSementara === false) {
            throw new \RuntimeException('Berkas unggahan tidak dapat dibaca oleh server.');
        }

        if ($ekstensi === 'xlsx') {
            return $this->bacaMatriksXlsx($lokasiSementara);
        }

        return [$this->bacaMatriksCsv($lokasiSementara)];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function bacaMatriksCsv(string $lokasiBerkas): array
    {
        $isiMentah = (string) file_get_contents($lokasiBerkas);
        $isiBersih = preg_replace('/^\xEF\xBB\xBF/', '', $isiMentah) ?? $isiMentah;

        $daftarBaris = [];
        $aliran = fopen('php://temp', 'r+');
        if ($aliran === false) {
            return [];
        }

        fwrite($aliran, $isiBersih);
        rewind($aliran);

        $barisPertama = fgets($aliran);
        rewind($aliran);

        $pemisah = ($barisPertama !== false && substr_count($barisPertama, ';') > substr_count($barisPertama, ','))
            ? ';'
            : ',';

        while (($kolom = fgetcsv($aliran, 0, $pemisah)) !== false) {
            $daftarBaris[] = array_map(fn ($nilai) => trim((string) $nilai), $kolom);
        }

        fclose($aliran);

        return $daftarBaris;
    }

    /**
     * Membaca berkas .xlsx multi-sheet menggunakan ZipArchive + SimpleXMLElement bawaan PHP.
     *
     * @return array<int, array<int, array<int, string>>>
     */
    private function bacaMatriksXlsx(string $lokasiBerkas): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi ZipArchive belum aktif untuk membaca berkas .xlsx. Silakan gunakan format .csv.');
        }

        $arsip = new ZipArchive;
        if ($arsip->open($lokasiBerkas) !== true) {
            throw new \RuntimeException('Berkas .xlsx tidak dapat dibuka atau rusak.');
        }

        $kamusTeks = [];
        $xmlShared = $arsip->getFromName('xl/sharedStrings.xml');
        if (is_string($xmlShared) && $xmlShared !== '') {
            $elemenShared = @simplexml_load_string($xmlShared);
            if ($elemenShared instanceof SimpleXMLElement) {
                foreach ($elemenShared->si as $itemSi) {
                    if (isset($itemSi->t)) {
                        $kamusTeks[] = (string) $itemSi->t;
                    } else {
                        $gabungan = '';
                        foreach ($itemSi->r as $bagianR) {
                            $gabungan .= (string) ($bagianR->t ?? '');
                        }
                        $kamusTeks[] = $gabungan;
                    }
                }
            }
        }

        $daftarSheet = [];
        for ($indeks = 0; $indeks < $arsip->numFiles; $indeks++) {
            $namaEntri = $arsip->getNameIndex($indeks);
            if (is_string($namaEntri) && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $namaEntri)) {
                $daftarSheet[] = $namaEntri;
            }
        }
        natsort($daftarSheet);

        $semuaMatriks = [];
        foreach ($daftarSheet as $namaSheet) {
            $xmlSheet = $arsip->getFromName($namaSheet);
            if (! is_string($xmlSheet) || $xmlSheet === '') {
                continue;
            }

            $elemenSheet = @simplexml_load_string($xmlSheet);
            if (! ($elemenSheet instanceof SimpleXMLElement) || ! isset($elemenSheet->sheetData->row)) {
                continue;
            }

            $matriksSheet = [];
            foreach ($elemenSheet->sheetData->row as $barisXml) {
                $barisData = array_fill(0, 14, '');
                foreach ($barisXml->c as $selXml) {
                    $referensi = (string) ($selXml['r'] ?? '');
                    $indeksKolom = $this->ubahHurufKolomKeIndeks($referensi);
                    $tipeSel = (string) ($selXml['t'] ?? '');
                    $nilaiMentah = isset($selXml->v) ? (string) $selXml->v : '';

                    if ($tipeSel === 's') {
                        $nilaiSel = $kamusTeks[(int) $nilaiMentah] ?? '';
                    } elseif ($tipeSel === 'inlineStr' && isset($selXml->is->t)) {
                        $nilaiSel = (string) $selXml->is->t;
                    } else {
                        $nilaiSel = $nilaiMentah;
                    }

                    $barisData[$indeksKolom] = trim($nilaiSel);
                }
                $matriksSheet[] = $barisData;
            }

            if ($matriksSheet !== []) {
                $semuaMatriks[] = $matriksSheet;
            }
        }

        $arsip->close();

        return $semuaMatriks;
    }

    private function ubahHurufKolomKeIndeks(string $referensiSel): int
    {
        if (! preg_match('/^([A-Z]+)/i', $referensiSel, $cocok)) {
            return 0;
        }

        $huruf = strtoupper($cocok[1]);
        $indeks = 0;
        $panjang = strlen($huruf);

        for ($i = 0; $i < $panjang; $i++) {
            $indeks = $indeks * 26 + (ord($huruf[$i]) - ord('A') + 1);
        }

        return max(0, $indeks - 1);
    }

    /**
     * Memecah satu atau banyak sheet menjadi daftar blok slip gaji supir.
     *
     * @param  array<int, array<int, array<int, string>>>  $daftarMatriks
     * @return array<int, array<string, mixed>>
     */
    private function pecahMenjadiBlokSlip(array $daftarMatriks): array
    {
        $kumpulanPotonganBaris = [];

        foreach ($daftarMatriks as $matriks) {
            $potonganSaatIni = [];
            foreach ($matriks as $baris) {
                $kolomPertama = strtoupper(trim($baris[0] ?? ''));
                if (str_contains($kolomPertama, 'RINCIAN GAJI SUPIR') && $potonganSaatIni !== []) {
                    $kumpulanPotonganBaris[] = $potonganSaatIni;
                    $potonganSaatIni = [];
                }
                $potonganSaatIni[] = $baris;
            }

            if ($potonganSaatIni !== []) {
                $kumpulanPotonganBaris[] = $potonganSaatIni;
            }
        }

        $daftarBlokValid = [];
        foreach ($kumpulanPotonganBaris as $potonganBaris) {
            $blokTerurai = $this->uraikanSatuBlokSlip($potonganBaris);
            if ($blokTerurai !== null && count($blokTerurai['ritase']) > 0) {
                $daftarBlokValid[] = $blokTerurai;
            }
        }

        return $daftarBlokValid;
    }

    /**
     * @param  array<int, array<int, string>>  $daftarBaris
     * @return array<string, mixed>|null
     */
    private function uraikanSatuBlokSlip(array $daftarBaris): ?array
    {
        $namaSupir = '';
        $merekTruk = 'HINO PUTIH';
        $kodePeriode = '';
        $nomorPolisiHeader = '';
        $nomorPolisiBaris = '';

        $daftarRitase = [];
        $rekap = [
            'total_angkutan_kotor' => 0,
            'potongan_operasional' => 0,
            'angkutan_bersih' => 0,
            'total_uang_jalan' => 0,
            'biaya_sparepart' => 0,
            'hasil_bersih_armada' => 0,
            'persentase_komisi_bp' => 1000,
            'gaji_komisi' => 0,
            'total_tunjangan' => 0,
            'potongan_bpjs' => 0,
            'potongan_kasbon' => 0,
            'gaji_bersih' => 0,
        ];

        foreach ($daftarBaris as $baris) {
            $kol0 = trim($baris[0] ?? '');
            $kol0Atas = strtoupper($kol0);

            if ($kol0Atas === 'NAMA SUPIR') {
                $namaSupir = trim($baris[1] ?? '');
                if (strtoupper(trim($baris[4] ?? '')) === 'MERK') {
                    $merekTruk = trim($baris[5] ?? '') ?: 'HINO PUTIH';
                }
                if (strtoupper(trim($baris[10] ?? '')) === 'PERIODE') {
                    $kodePeriode = trim($baris[11] ?? '');
                }
                continue;
            }

            // Baris header "PLAT" memuat nomor polisi unit; format lama juga
            // memakai label VENDOR, jadi keduanya tetap dikenali.
            if ($kol0Atas === 'PLAT') {
                $nomorPolisiHeader = trim($baris[1] ?? '');
                continue;
            }

            if ($kol0Atas === 'VENDOR') {
                if (strtoupper(trim($baris[4] ?? '')) === 'PLAT') {
                    $nomorPolisiHeader = trim($baris[5] ?? '');
                }
                continue;
            }

            // Cek apakah baris ini adalah baris transaksi ritase (kolom 0 berisi tanggal)
            $tanggalTerurai = $this->uraikanTanggalSel($kol0);
            if ($tanggalTerurai !== null) {
                $nopolBaris = trim($baris[1] ?? '');
                if ($nopolBaris !== '' && ! str_contains($nopolBaris, '*')) {
                    $nomorPolisiBaris = $nopolBaris;
                }

                $material = trim($baris[2] ?? '') ?: 'BATU BARA';
                $jumlahRit = max(1, (int) $this->bersihkanAngkaBulat($baris[3] ?? '1'));
                $tonaseFloat = $this->bersihkanAngkaDesimal($baris[4] ?? '0');
                $tonaseKg = (int) round($tonaseFloat * 1000);
                $tarifPerTon = $this->bersihkanAngkaBulat($baris[7] ?? '0');
                $totalAngkutan = $this->bersihkanAngkaBulat($baris[9] ?? '0');
                if ($totalAngkutan === 0 && $tonaseFloat > 0 && $tarifPerTon > 0) {
                    $totalAngkutan = (int) round($tonaseFloat * $tarifPerTon);
                }
                $uangJalan = $this->bersihkanAngkaBulat($baris[10] ?? '0');
                $keterangan = trim($baris[11] ?? '') ?: '-';

                if ($tonaseKg > 0) {
                    $daftarRitase[] = [
                        'tanggal' => $tanggalTerurai,
                        'material' => $material,
                        'jumlah_rit' => $jumlahRit,
                        'tonase_kg' => $tonaseKg,
                        'tarif_per_ton' => $tarifPerTon,
                        'total_angkutan' => $totalAngkutan,
                        'uang_jalan' => $uangJalan,
                        'keterangan' => $keterangan,
                    ];
                }
                continue;
            }

            // Cek apakah baris ini adalah bagian REKAPITULASI (label di kolom 7, nilai di kolom 10)
            $labelRekap = strtolower(trim($baris[7] ?? ''));
            $nilaiRekapMentah = trim($baris[10] ?? '');
            if ($labelRekap !== '' && $nilaiRekapMentah !== '') {
                if ($labelRekap === 'jumlah angkutan') {
                    $rekap['total_angkutan_kotor'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif ($labelRekap === 'potongan operasional') {
                    $rekap['potongan_operasional'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif (str_starts_with($labelRekap, 'jumlah angkutan (setelah')) {
                    $rekap['angkutan_bersih'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif ($labelRekap === 'jumlah uang jalan') {
                    $rekap['total_uang_jalan'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif (str_contains($labelRekap, 'service') || $labelRekap === 's.part') {
                    $rekap['biaya_sparepart'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif ($labelRekap === 'hasil bersih armada') {
                    $rekap['hasil_bersih_armada'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif ($labelRekap === 'persentase komisi') {
                    $desimalKomisi = $this->bersihkanAngkaDesimal($nilaiRekapMentah);
                    $rekap['persentase_komisi_bp'] = $desimalKomisi <= 1
                        ? (int) round($desimalKomisi * 10000)
                        : (int) round($desimalKomisi * 100);
                } elseif (str_starts_with($labelRekap, 'gaji komisi')) {
                    $rekap['gaji_komisi'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif ($labelRekap === 'tunjangan') {
                    $rekap['total_tunjangan'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif ($labelRekap === 'potongan bpjs') {
                    $rekap['potongan_bpjs'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif ($labelRekap === 'potongan kasbon') {
                    $rekap['potongan_kasbon'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                } elseif (str_starts_with($labelRekap, 'gaji bersih')) {
                    $rekap['gaji_bersih'] = $this->bersihkanAngkaBulat($nilaiRekapMentah);
                }
            }
        }

        if ($daftarRitase === []) {
            return null;
        }

        $nomorPolisiFinal = $nomorPolisiBaris !== ''
            ? $nomorPolisiBaris
            : ($nomorPolisiHeader !== '' ? $nomorPolisiHeader : 'BE 0000 XX');

        return [
            'nama_supir' => $namaSupir !== '' ? $namaSupir : 'SUPIR MIGRASI',
            'merek_truk' => $merekTruk,
            'kode_periode' => $kodePeriode !== '' ? $kodePeriode : 'Jan-26',
            'nomor_polisi' => strtoupper($nomorPolisiFinal),
            'ritase' => $daftarRitase,
            'rekap' => $rekap,
        ];
    }

    private function tentukanKodePeriode(
        string $kodeDariBerkas,
        string $targetPeriode,
        ?string $kodePeriodeBaru
    ): string {
        if ($targetPeriode === 'baru' && $kodePeriodeBaru !== null && trim($kodePeriodeBaru) !== '') {
            return trim($kodePeriodeBaru);
        }

        if ($targetPeriode !== '' && $targetPeriode !== 'auto' && $targetPeriode !== 'baru') {
            return trim($targetPeriode);
        }

        return trim($kodeDariBerkas) !== '' ? trim($kodeDariBerkas) : 'Jan-26';
    }

    /**
     * @param  array<int, array<string, mixed>>  $daftarRitase
     */
    private function ambilAtauBuatPeriode(
        string $kodePeriode,
        array $daftarRitase,
        string $statusPencairan,
        ?string $tanggalPencairan
    ): PeriodeGaji {
        $periode = PeriodeGaji::query()->where('kode', $kodePeriode)->first();

        $statusTarget = $statusPencairan === 'sudah_cair' ? 'paid' : 'draft';

        if ($periode !== null) {
            if ($statusPencairan === 'sudah_cair') {
                $periode->status = 'paid';
                if ($tanggalPencairan !== null && trim($tanggalPencairan) !== '') {
                    $periode->tanggal_bayar = Carbon::parse($tanggalPencairan);
                }
                $periode->save();
            }

            return $periode;
        }

        $daftarTanggal = array_map(
            fn (array $item) => $this->normalisasiTanggalRitase($item['tanggal'], $kodePeriode),
            $daftarRitase
        );
        sort($daftarTanggal);

        $tanggalMulai = $daftarTanggal[0] ?? now()->startOfMonth()->toDateString();
        $tanggalSelesai = $daftarTanggal[count($daftarTanggal) - 1] ?? now()->endOfMonth()->toDateString();
        $tanggalBayar = ($tanggalPencairan !== null && trim($tanggalPencairan) !== '')
            ? Carbon::parse($tanggalPencairan)->toDateString()
            : Carbon::parse($tanggalSelesai)->addDays(5)->toDateString();

        return PeriodeGaji::create([
            'kode' => $kodePeriode,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'tanggal_bayar' => $tanggalBayar,
            'status' => $statusTarget,
        ]);
    }

    private function ambilAtauBuatTruk(string $nomorPolisi, string $merekModel): Truk
    {
        $nopolBersih = strtoupper(trim($nomorPolisi));
        $truk = Truk::query()->where('nomor_polisi', $nopolBersih)->first();

        if ($truk !== null) {
            return $truk;
        }

        $jenisTrukDefault = JenisTruk::query()->first();

        return Truk::create([
            'id_jenis_truk' => $jenisTrukDefault?->id,
            'nomor_polisi' => $nopolBersih,
            'merek_model' => strtoupper(trim($merekModel)) ?: 'HINO PUTIH',
            'status' => 'aktif',
        ]);
    }

    private function ambilAtauBuatKaryawan(
        string $namaDariSlip,
        Truk $truk,
        PeriodeGaji $periodeGaji,
        int $urutanSlip
    ): Karyawan {
        // 1. Prioritaskan karyawan yang sudah memegang truk tersebut pada penggajian/ritase
        $penggajianTruk = Penggajian::query()
            ->where('id_truk', $truk->id)
            ->orderByDesc('id')
            ->first();

        if ($penggajianTruk?->karyawan !== null) {
            return $penggajianTruk->karyawan;
        }

        $ritaseTruk = Ritase::query()
            ->where('id_truk', $truk->id)
            ->orderByDesc('id')
            ->first();

        if ($ritaseTruk?->karyawan !== null) {
            return $ritaseTruk->karyawan;
        }

        // 2. Cari berdasarkan nama lengkap persis
        $namaBersih = strtoupper(trim($namaDariSlip));
        $namaTanpaSpasi = str_replace(' ', '', $namaBersih);

        $karyawanNama = Karyawan::query()
            ->where('nama_lengkap', $namaBersih)
            ->orWhere('nama_lengkap', $namaTanpaSpasi)
            ->first();

        if ($karyawanNama !== null) {
            return $karyawanNama;
        }

        // 3. Buat karyawan baru jika belum ada di master
        $kodeNikUnik = sprintf('DRV-MIG-%s-%02d', strtoupper(preg_replace('/[^A-Z0-9]/i', '', $periodeGaji->kode)), $urutanSlip);
        while (Karyawan::withTrashed()->where('nomor_induk_karyawan', $kodeNikUnik)->exists()) {
            $urutanSlip++;
            $kodeNikUnik = sprintf('DRV-MIG-%s-%02d', strtoupper(preg_replace('/[^A-Z0-9]/i', '', $periodeGaji->kode)), $urutanSlip);
        }

        $emailUnik = strtolower($kodeNikUnik).'@payroll.test';
        $pengguna = User::firstOrCreate(
            ['email' => $emailUnik],
            [
                'name' => $namaBersih,
                'password' => 'password',
                'role' => UserRole::Supir,
            ]
        );

        return Karyawan::create([
            'id_pengguna' => $pengguna->id,
            'nomor_induk_karyawan' => $kodeNikUnik,
            'nama_lengkap' => $namaBersih,
            'persentase_komisi_bp' => 1000,
            'potongan_bpjs_bulanan' => 129000,
            'status' => 'aktif',
        ]);
    }

    private function ambilAtauBuatRute(
        string $kodeKeterangan,
        string $material,
        int $tarifPerTon,
        int $uangJalan,
        string $kodePeriode
    ): Rute {
        $kodeBersih = strtoupper(trim($kodeKeterangan));
        if ($kodeBersih === '' || $kodeBersih === '-') {
            $kodeBersih = strtoupper($kodePeriode) === 'NOV-25' ? 'DMY-NOV' : 'MIP-PORT';
        }

        $rute = Rute::query()->where('kode', $kodeBersih)->first();
        if ($rute !== null) {
            return $rute;
        }

        return Rute::create([
            'kode' => $kodeBersih,
            'asal' => 'PIT TAMBANG',
            'tujuan' => 'STOCKPILE PELABUHAN',
            'jarak_km' => 120,
            'material' => strtoupper(trim($material)) ?: 'BATU BARA',
            'tarif_per_ton' => $tarifPerTon > 0 ? $tarifPerTon : 150000,
            'uang_jalan_default' => $uangJalan > 0 ? $uangJalan : 2000000,
            'status_aktif' => true,
        ]);
    }

    private function uraikanTanggalSel(string $teks): ?string
    {
        $teksBersih = trim($teks);
        if ($teksBersih === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $teksBersih)) {
            return $teksBersih;
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $teksBersih, $cocok)) {
            return sprintf('%04d-%02d-%02d', (int) $cocok[3], (int) $cocok[2], (int) $cocok[1]);
        }

        // Serial tanggal Excel (mis. 45962 -> 2025-11-01)
        if (ctype_digit($teksBersih) && (int) $teksBersih > 40000 && (int) $teksBersih < 60000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $teksBersih)->toDateString();
        }

        return null;
    }

    private function normalisasiTanggalRitase(string $tanggal, string $kodePeriode): string
    {
        // Sesuai catatan SlipGajiSeeder: baris 2026-09-28 pada slip Jan-26 adalah hasil rekonstruksi foto 28/08 (2026-08-28)
        if (strtoupper($kodePeriode) === 'JAN-26' && $tanggal === '2026-09-28') {
            return '2026-08-28';
        }

        return $tanggal;
    }

    private function bersihkanAngkaBulat(string $nilai): int
    {
        $angkaDesimal = $this->bersihkanAngkaDesimal($nilai);

        return (int) round($angkaDesimal);
    }

    private function bersihkanAngkaDesimal(string $nilai): float
    {
        $teks = trim($nilai);
        if ($teks === '' || $teks === '-') {
            return 0.0;
        }

        $teks = preg_replace('/[^0-9,.\-]/', '', $teks) ?? '';
        if ($teks === '') {
            return 0.0;
        }

        if (str_contains($teks, ',') && str_contains($teks, '.')) {
            if (strrpos($teks, ',') > strrpos($teks, '.')) {
                $teks = str_replace('.', '', $teks);
                $teks = str_replace(',', '.', $teks);
            } else {
                $teks = str_replace(',', '', $teks);
            }
        } elseif (str_contains($teks, ',')) {
            $teks = str_replace(',', '.', $teks);
        }

        return (float) $teks;
    }
}
