<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AngsuranKasbon;
use App\Models\JenisTruk;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\Truk;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use App\Support\SiklusPeriodeGaji;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * ============================================================================
 * LAYANAN: MIGRASI DOKUMEN EXCEL / CSV PAYROLL (EXCEL MIGRATION SERVICE)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Alat Penyedot & Penerjemah Dokumen Excel / Kertas Pembukuan Lama".
 * Sebelum sistem berbasis web ini dibuat, kantor mencatat slip gaji supir di lembaran
 * spreadsheet Excel (.xlsx / .csv). Layanan ini bertugas membaca lembar demi lembar
 * rincian ritase, nama supir, nomor polisi, dan tonase dari file Excel tersebut,
 * lalu memasukkannya secara otomatis ke tabel database MySQL yang baru tanpa perlu
 * staf mengetik ulang satu per satu.
 * ============================================================================
 */
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
            throw new \RuntimeException('Format berkas tidak dikenali atau lembar data transaksi belum diisi. Pastikan lembar input data (seperti "INPUT DATA SLIP" atau sheet nama supir) memuat blok "RINCIAN GAJI SUPIR" dan baris transaksi ritase valid. Lembar panduan/petunjuk/rekapitulasi otomatis dilewati sistem.');
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
     * Membuat format email default supir baru sesuai standar:
     * NAMASUPIR_ASM@gmail.com (disanitasi menjadi lowercase dan bebas tabrakan).
     */
    public function generateDefaultEmail(string $namaSupir, ?array &$daftarEmailTerpakai = null): string
    {
        $namaBersih = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $namaSupir));
        if ($namaBersih === '') {
            $namaBersih = 'supir';
        }

        $emailDasar = $namaBersih.'_asm@gmail.com';
        $emailAkhir = $emailDasar;
        $nomor = 2;

        // Bila daftar email sudah dimuat sebelumnya (mode analisis staging),
        // kecocokan diperiksa di memori agar tidak menembak database berkali-kali.
        if ($daftarEmailTerpakai !== null) {
            while (in_array(strtolower($emailAkhir), $daftarEmailTerpakai, true)) {
                $emailAkhir = $namaBersih.$nomor.'_asm@gmail.com';
                $nomor++;
            }

            $daftarEmailTerpakai[] = strtolower($emailAkhir);

            return $emailAkhir;
        }

        while (User::where('email', $emailAkhir)->exists()) {
            $emailAkhir = $namaBersih.$nomor.'_asm@gmail.com';
            $nomor++;
        }

        return $emailAkhir;
    }

    /**
     * Membuat Nomor Induk Karyawan (NIP/NIK) format manual berdasarkan inisial supir:
     * DRF-{INISIAL}-{2 DIGIT ANGKA ACAK} (contoh: DRF-KS-48, DRF-BS-18).
     */
    public function generateDefaultNik(string $namaSupir, ?array &$daftarNikTerpakai = null): string
    {
        $bagian = array_values(array_filter(preg_split('/\s+/', strtoupper(trim((string) preg_replace('/[^A-Z0-9\s]/', ' ', $namaSupir))))));

        if (empty($bagian)) {
            $inisial = 'SPR';
        } elseif (count($bagian) === 1) {
            $kata = $bagian[0];
            $inisial = strlen($kata) >= 2 ? substr($kata, 0, 3) : str_pad($kata, 2, 'X');
        } else {
            $inisial = substr($bagian[0], 0, 1).substr($bagian[1], 0, 1);
        }

        $nomor = rand(10, 99);
        $nik = "DRF-{$inisial}-{$nomor}";
        $percobaan = 0;

        if ($daftarNikTerpakai !== null) {
            while (in_array($nik, $daftarNikTerpakai, true) && $percobaan < 100) {
                $nomor = rand(10, 99);
                $nik = "DRF-{$inisial}-{$nomor}";
                $percobaan++;
            }

            if ($percobaan >= 100) {
                $nik = sprintf('DRF-%s-%03d', $inisial, count($daftarNikTerpakai) + 1);
            }

            $daftarNikTerpakai[] = $nik;

            return $nik;
        }

        while (Karyawan::withTrashed()->where('nomor_induk_karyawan', $nik)->exists() && $percobaan < 100) {
            $nomor = rand(10, 99);
            $nik = "DRF-{$inisial}-{$nomor}";
            $percobaan++;
        }

        if ($percobaan >= 100) {
            $nik = sprintf('DRF-%s-%03d', $inisial, Karyawan::withTrashed()->count() + 1);
        }

        return $nik;
    }

    /**
     * Menganalisis berkas migrasi Excel/CSV sebelum disimpan ke database (Staging Preview).
     *
     * Mengecek apakah supir, unit armada, rute, dan periode sudah ada atau belum,
     * serta mendeteksi anomali operasional seperti tanggal & rute sama dengan armada berbeda.
     *
     * @return array<string, mixed>
     */
    public function analisisStagingMigrasi(
        UploadedFile $berkas,
        string $targetPeriode = 'auto',
        string $statusPencairan = 'belum_cair',
        ?string $kodePeriodeBaru = null,
        ?string $tanggalPencairan = null,
    ): array {
        $daftarMatriksBaris = $this->ekstrakMatriksDariBerkas($berkas);
        $daftarBlokSlip = $this->pecahMenjadiBlokSlip($daftarMatriksBaris);

        if ($daftarBlokSlip === []) {
            throw new \RuntimeException('Berkas tidak memuat satu pun transaksi ritase yang terisi. Jika Anda mengunggah template kosong, isi minimal 1 baris tanggal, jumlah rit, dan tonase muatan (lebih dari 0) terlebih dahulu. Jika memakai format lain, pastikan berkas memuat blok "RINCIAN GAJI SUPIR" pada lembar data supir.');
        }

        $daftarSupirResolusi = [];
        $daftarUnitResolusi = [];
        $daftarRuteResolusi = [];
        $daftarPeriodeResolusi = [];
        $daftarAnomali = [];

        // Data master dimuat SEKALI ke memori. Tanpa ini setiap blok slip menembak
        // database satu per satu sehingga berkas besar melewati batas 30 detik.
        $petaPeriodeGaji = PeriodeGaji::query()->get(['kode', 'status'])->keyBy('kode');
        $petaKaryawan = Karyawan::query()->with('pengguna')->get()->keyBy('nama_lengkap');
        $petaTruk = Truk::query()->with('jenisTruk')->get()->keyBy('nomor_polisi');
        $petaRute = Rute::query()->get()->keyBy('kode');
        $daftarEmailTerpakai = User::query()->pluck('email')->map(fn ($email) => strtolower((string) $email))->all();
        $daftarNikTerpakai = Karyawan::withTrashed()->pluck('nomor_induk_karyawan')->all();

        $totalRitaseSemua = 0;
        $totalTonaseSemua = 0;
        $totalGajiBersihEstimasi = 0;
        $adaPeriodeTerkunci = false;

        foreach ($daftarBlokSlip as $indeksBlok => &$blok) {
            $kodePeriode = $this->tentukanKodePeriode(
                $blok['kode_periode'],
                $targetPeriode,
                $kodePeriodeBaru
            );
            $blok['kode_periode_efektif'] = $kodePeriode;

            // 1. Pengecekan Periode
            if (! isset($daftarPeriodeResolusi[$kodePeriode])) {
                $periodeModel = $petaPeriodeGaji->get($kodePeriode);
                $terkunci = in_array($periodeModel?->status, ['approved', 'paid'], true);
                if ($terkunci) {
                    $adaPeriodeTerkunci = true;
                    $daftarAnomali[] = [
                        'tipe' => 'periode_terkunci',
                        'tingkat' => 'bahaya',
                        'judul' => "Periode {$kodePeriode} Terkunci",
                        'keterangan' => "Periode {$kodePeriode} sudah berstatus {$periodeModel->status} (Disetujui/Lunas). Data penggajian tidak boleh diimpor ke periode yang sudah terkunci.",
                    ];
                }

                $daftarPeriodeResolusi[$kodePeriode] = [
                    'kode' => $kodePeriode,
                    'terdaftar' => $periodeModel !== null,
                    'status' => $periodeModel?->status ?? 'belum_ada',
                    'status_label' => $periodeModel !== null ? $this->labelStatusPeriode($periodeModel->status) : 'Periode Baru (Akan Dibuat)',
                    'terkunci' => $terkunci,
                ];
            }

            // 2. Pengecekan Supir
            $namaSupir = strtoupper(trim($blok['nama_supir']));
            if ($namaSupir === '') {
                $namaSupir = 'SUPIR MIGRASI #'.($indeksBlok + 1);
            }
            $blok['nama_supir_efektif'] = $namaSupir;

            if (! isset($daftarSupirResolusi[$namaSupir])) {
                $karyawan = $petaKaryawan->get($namaSupir)
                    ?? $petaKaryawan->get(str_replace(' ', '', $namaSupir));

                $terdaftar = $karyawan !== null;
                $emailDefault = $this->generateDefaultEmail($namaSupir, $daftarEmailTerpakai);
                $nipDefault = $this->generateDefaultNik($namaSupir, $daftarNikTerpakai);

                // Deteksi kemungkinan supir yang mirip namanya jika tidak persis sama
                $kemungkinanMirip = null;
                if (! $terdaftar) {
                    $kataPertama = explode(' ', $namaSupir)[0] ?? '';
                    if (strlen($kataPertama) >= 3) {
                        $kandidat = $petaKaryawan
                            ->first(fn (Karyawan $orang) => $orang->status === 'aktif'
                                && str_starts_with((string) $orang->nama_lengkap, $kataPertama));
                        if ($kandidat) {
                            $kemungkinanMirip = [
                                'id' => $kandidat->id,
                                'nama' => $kandidat->nama_lengkap,
                                'nip' => $kandidat->nomor_induk_karyawan,
                            ];
                        }
                    }
                }

                $daftarSupirResolusi[$namaSupir] = [
                    'nama' => $namaSupir,
                    'terdaftar' => $terdaftar,
                    'id_karyawan' => $karyawan?->id,
                    'nip' => $karyawan?->nomor_induk_karyawan ?? '—',
                    'email_terdaftar' => $karyawan?->pengguna?->email,
                    'email_default' => $emailDefault,
                    'nip_default' => $nipDefault,
                    'password_default' => 'password',
                    'status_karyawan' => $karyawan?->status ?? 'nonaktif',
                    'kemungkinan_mirip' => $kemungkinanMirip,
                    'jumlah_slip' => 0,
                    'aksi_default' => $terdaftar ? 'terdaftar' : ($kemungkinanMirip ? 'gabungkan' : 'buat_akun'),
                    'id_karyawan_tujuan' => $kemungkinanMirip['id'] ?? null,
                ];
            }
            $daftarSupirResolusi[$namaSupir]['jumlah_slip']++;

            // 3. Pengecekan Unit Armada (Truk)
            $nopol = strtoupper(trim($blok['nomor_polisi']));
            if ($nopol === '') {
                $nopol = 'BE 0000 ASM';
            }
            $blok['nomor_polisi_efektif'] = $nopol;

            if (! isset($daftarUnitResolusi[$nopol])) {
                $truk = $petaTruk->get($nopol);
                $terdaftar = $truk !== null;

                $daftarUnitResolusi[$nopol] = [
                    'nomor_polisi' => $nopol,
                    'terdaftar' => $terdaftar,
                    'id_truk' => $truk?->id,
                    'merek_model' => $truk?->merek_model ?? (strtoupper(trim($blok['merek_truk'])) ?: 'HINO PUTIH'),
                    'jenis_truk' => $truk?->jenisTruk?->nama ?? 'TRONTON',
                    'jumlah_penggunaan' => 0,
                    'aksi_default' => $terdaftar ? 'terdaftar' : 'daftar_baru',
                ];
            }
            $daftarUnitResolusi[$nopol]['jumlah_penggunaan']++;

            // 4. Hitung ritase dan anomali per baris
            $penggunaanTrukPerTanggal = [];
            $penggunaanRuteTrukPerTanggal = [];

            $totalTonaseKgBlok = 0;
            $totalAngkutanBlok = 0;
            $totalUangJalanBlok = 0;
            $jumlahRitBlok = 0;

            foreach ($blok['ritase'] as $barisRit) {
                $tgl = $this->normalisasiTanggalRitase($barisRit['tanggal'], $kodePeriode);
                $kodeRute = strtoupper(trim($barisRit['keterangan']));
                if ($kodeRute === '' || $kodeRute === '-') {
                    $kodeRute = strtoupper($kodePeriode) === 'NOV-25' ? 'DMY-NOV' : 'MIP-PORT';
                }

                // Cek Rute
                if (! isset($daftarRuteResolusi[$kodeRute])) {
                    $ruteModel = $petaRute->get($kodeRute);
                    $daftarRuteResolusi[$kodeRute] = [
                        'kode' => $kodeRute,
                        'terdaftar' => $ruteModel !== null,
                        'id_rute' => $ruteModel?->id,
                        'material' => $ruteModel?->material ?? ($barisRit['material'] ?: 'BATU BARA'),
                        'tarif_per_ton' => $ruteModel?->tarif_per_ton ?? $barisRit['tarif_per_ton'],
                        'uang_jalan_default' => $ruteModel?->uang_jalan_default ?? $barisRit['uang_jalan'],
                        'aksi_default' => $ruteModel !== null ? 'terdaftar' : 'daftar_baru',
                    ];
                }

                // Gunakan nomor polisi per baris (bila ada) atau fallback ke plat header
                $nopolRitase = ! empty($barisRit['nomor_polisi']) ? strtoupper(trim($barisRit['nomor_polisi'])) : $nopol;
                if (! isset($daftarUnitResolusi[$nopolRitase])) {
                    $trukBaris = $petaTruk->get($nopolRitase);
                    $daftarUnitResolusi[$nopolRitase] = [
                        'nomor_polisi' => $nopolRitase,
                        'terdaftar' => $trukBaris !== null,
                        'id_truk' => $trukBaris?->id,
                        'merek_model' => $trukBaris?->merek_model ?? (strtoupper(trim($blok['merek_truk'])) ?: 'HINO PUTIH'),
                        'jenis_truk' => $trukBaris?->jenisTruk?->nama ?? 'TRONTON',
                        'jumlah_penggunaan' => 0,
                        'aksi_default' => $trukBaris !== null ? 'terdaftar' : 'daftar_baru',
                    ];
                }
                $daftarUnitResolusi[$nopolRitase]['jumlah_penggunaan']++;

                // Pelacakan anomali 1: supir & tanggal sama menggunakan unit berbeda
                $penggunaanTrukPerTanggal[$tgl][$nopolRitase] = true;

                // Pelacakan anomali 2: tanggal & supir & rute sama tapi unit berbeda
                $kunciRuteTanggal = $tgl.'|'.$kodeRute;
                $penggunaanRuteTrukPerTanggal[$kunciRuteTanggal][$nopolRitase] = true;

                // Pelacakan anomali 3: selisih nilai angkutan
                $tonaseFloat = $barisRit['tonase_kg'] / 1000;
                $tarifPerTon = $barisRit['tarif_per_ton'];
                $totalAngkutan = $barisRit['total_angkutan'];
                if ($tonaseFloat > 0 && $tarifPerTon > 0 && $totalAngkutan > 0) {
                    $kalkulasiSistem = (int) round($tonaseFloat * $tarifPerTon);
                    if (abs($kalkulasiSistem - $totalAngkutan) > 1000) {
                        $daftarAnomali[] = [
                            'tipe' => 'selisih_angkutan',
                            'tingkat' => 'info',
                            'judul' => 'Selisih Nilai Angkutan di Excel',
                            'keterangan' => "Supir {$namaSupir} pada {$tgl} (Rute {$kodeRute}): Nilai angkutan Excel Rp ".number_format($totalAngkutan, 0, ',', '.')." berselisih dengan hitungan tonase x tarif (Rp ".number_format($kalkulasiSistem, 0, ',', '.').").",
                        ];
                    }
                }

                $jumlahRitBlok += $barisRit['jumlah_rit'];
                $totalTonaseKgBlok += $barisRit['tonase_kg'];
                $totalAngkutanBlok += $barisRit['total_angkutan'];
                $totalUangJalanBlok += $barisRit['uang_jalan'];
            }

            // Evaluasi anomali supir memakai lebih dari satu unit armada pada tanggal yang sama
            foreach ($penggunaanTrukPerTanggal as $tanggalBanyakUnit => $daftarPlatTanggal) {
                if (count($daftarPlatTanggal) > 1) {
                    $daftarAnomali[] = [
                        'tipe' => 'supir_banyak_unit',
                        'tingkat' => 'peringatan',
                        'judul' => 'Anomali Supir Memakai Banyak Unit',
                        'keterangan' => "Supir {$namaSupir} pada tanggal {$tanggalBanyakUnit} mengoperasikan lebih dari satu unit armada (".implode(', ', array_keys($daftarPlatTanggal)).").",
                    ];
                }
            }

            // Evaluasi anomali tanggal + supir + rute sama tapi unit berbeda
            foreach ($penggunaanRuteTrukPerTanggal as $kunci => $daftarPlat) {
                if (count($daftarPlat) > 1) {
                    [$tglAnomali, $ruteAnomali] = explode('|', $kunci, 2);
                    $daftarAnomali[] = [
                        'tipe' => 'unit_berbeda_tanggal_rute_sama',
                        'tingkat' => 'peringatan',
                        'judul' => 'Anomali Unit Berbeda pada Tanggal & Rute Sama',
                        'keterangan' => "Supir {$namaSupir} pada tanggal {$tglAnomali} di rute {$ruteAnomali} tercatat menggunakan armada berbeda (".implode(', ', array_keys($daftarPlat)).").",
                    ];
                }
            }

            // Estimasi gaji bersih menggunakan PayrollCalculationService
            $rekap = $blok['rekap'];
            $kalkulasiGaji = $this->layananKalkulasiGaji->hitungKomponenGaji([
                'total_rit' => $jumlahRitBlok,
                'total_tonase_kg' => $totalTonaseKgBlok,
                'total_angkutan_kotor' => $rekap['total_angkutan_kotor'] > 0 ? $rekap['total_angkutan_kotor'] : $totalAngkutanBlok,
                'potongan_operasional' => $rekap['potongan_operasional'],
                'total_uang_jalan' => $rekap['total_uang_jalan'] > 0 ? $rekap['total_uang_jalan'] : $totalUangJalanBlok,
                'biaya_sparepart' => $rekap['biaya_sparepart'],
                'persentase_komisi_bp' => $rekap['persentase_komisi_bp'] > 0 ? $rekap['persentase_komisi_bp'] : 1000,
                'potongan_bpjs' => $rekap['potongan_bpjs'],
                'potongan_kasbon' => $rekap['potongan_kasbon'],
            ]);

            $blok['estimasi_kalkulasi'] = $kalkulasiGaji;
            $gajiBersihFinal = $rekap['gaji_bersih'] > 0 ? $rekap['gaji_bersih'] : $kalkulasiGaji['gaji_bersih'];
            $blok['gaji_bersih_final'] = $gajiBersihFinal;

            $totalRitaseSemua += count($blok['ritase']);
            $totalTonaseSemua += $totalTonaseKgBlok;
            $totalGajiBersihEstimasi += $gajiBersihFinal;
        }
        unset($blok);

        $token = 'migrasi_'.Str::random(40);
        $jumlahSupirBelum = collect($daftarSupirResolusi)->where('terdaftar', false)->count();
        $jumlahUnitBelum = collect($daftarUnitResolusi)->where('terdaftar', false)->count();
        $jumlahRuteBelum = collect($daftarRuteResolusi)->where('terdaftar', false)->count();

        $payloadStaging = [
            'token' => $token,
            'dibuat_pada' => now()->toIso8601String(),
            'nama_berkas' => (string) $berkas->getClientOriginalName(),
            'ukuran_berkas' => (int) $berkas->getSize(),
            'target_periode' => $targetPeriode,
            'status_pencairan' => $statusPencairan,
            'tanggal_pencairan' => $tanggalPencairan,
            'kode_periode_baru' => $kodePeriodeBaru,
            'ringkasan' => [
                'total_slip' => count($daftarBlokSlip),
                'total_ritase' => $totalRitaseSemua,
                'total_tonase_kg' => $totalTonaseSemua,
                'total_tonase_label' => number_format($totalTonaseSemua / 1000, 2, ',', '.').' Ton',
                'total_gaji_bersih' => $totalGajiBersihEstimasi,
                'supir_belum_terdaftar' => $jumlahSupirBelum,
                'unit_belum_terdaftar' => $jumlahUnitBelum,
                'rute_belum_terdaftar' => $jumlahRuteBelum,
                'jumlah_anomali' => count($daftarAnomali),
                'periode_terkunci' => $adaPeriodeTerkunci,
            ],
            'anomali' => $daftarAnomali,
            'daftar_supir' => array_values($daftarSupirResolusi),
            'daftar_unit' => array_values($daftarUnitResolusi),
            'daftar_rute' => array_values($daftarRuteResolusi),
            'daftar_periode' => array_values($daftarPeriodeResolusi),
            'daftar_blok' => $daftarBlokSlip,
        ];

        Cache::put("migrasi_staging_{$token}", $payloadStaging, now()->addHours(2));

        return $payloadStaging;
    }
    /**
     * Mencatat status progress proses impor migrasi ke cache untuk dipantau UI.
     *
     * @param  array<string, mixed>|null  $hasil
     */
    public function catatProgress(string $token, int $persen, string $pesan, bool $selesai = false, ?array $hasil = null): void
    {
        // Progres disimpan di penyimpanan berkas, bukan database: impor berjalan di dalam
        // satu transaksi sehingga tulisan ke tabel cache tidak akan terlihat oleh request
        // polling sampai transaksi selesai (bilah progres tampak macet).
        Cache::store('file')->put("migrasi_progress_{$token}", [
            'status' => $selesai ? 'selesai' : 'memproses',
            'persen' => min(100, max(0, $persen)),
            'pesan' => $pesan,
            'selesai' => $selesai,
            'hasil' => $hasil,
        ], 300);
    }

    /**
     * Mengambil status progress saat ini dari cache.
     *
     * @return array<string, mixed>
     */
    public function ambilProgress(string $token): array
    {
        return Cache::store('file')->get("migrasi_progress_{$token}", [
            'status' => 'belum_mulai',
            'persen' => 0,
            'pesan' => 'Menunggu memulai pemrosesan...',
            'selesai' => false,
            'hasil' => null,
        ]);
    }

    /**
     * Mengambil data staging migrasi dari cache berdasarkan token.
     */
    public function ambilDataStaging(string $token): ?array
    {
        return Cache::get("migrasi_staging_{$token}");
    }

    /**
     * Mengeksekusi impor data staging ke database setelah admin memeriksa pratinjau.
     */
    public function eksekusiStagingMigrasi(
        string $token,
        array $keputusanSupir = [],
        array $keputusanUnit = [],
        array $keputusanRute = [],
        ?callable $onProgress = null
    ): array {
        $this->catatProgress($token, 5, 'Menyiapkan pendaftaran unit armada & rute...');
        $staging = $this->ambilDataStaging($token);

        if ($staging === null) {
            throw ValidationException::withMessages([
                'berkas_excel' => ['Sesi pratinjau migrasi telah kedaluwarsa atau tidak ditemukan. Silakan unggah berkas kembali.'],
            ]);
        }

        if (! empty($staging['ringkasan']['periode_terkunci'])) {
            throw ValidationException::withMessages([
                'target_periode' => ['Periode target berstatus Disetujui (Approved) atau Dicairkan (Paid). Data tidak dapat diimpor ke periode yang sudah terkunci.'],
            ]);
        }

        $daftarBlokSlip = $staging['daftar_blok'];
        $targetPeriode = $staging['target_periode'];
        $statusPencairan = $staging['status_pencairan'];
        $kodePeriodeBaru = $staging['kode_periode_baru'];
        $tanggalPencairan = $staging['tanggal_pencairan'];

        $hasilImpor = DB::transaction(function () use (
            $staging,
            $token,
            $daftarBlokSlip,
            $targetPeriode,
            $statusPencairan,
            $kodePeriodeBaru,
            $tanggalPencairan,
            $keputusanSupir,
            $keputusanUnit,
            $keputusanRute
        ): array {
            // Periksa ulang kunci periode DI DALAM transaksi. Pratinjau bisa berumur
            // hingga 2 jam; bila Pimpinan memberi ACC setelah pratinjau dibuat,
            // impor tetap harus ditolak agar data periode terkunci tidak berubah.
            $kodePeriodeTarget = [];
            foreach ($daftarBlokSlip as $blok) {
                $kodeTarget = $blok['kode_periode_efektif'] ?? $this->tentukanKodePeriode(
                    (string) ($blok['kode_periode'] ?? ''),
                    $targetPeriode,
                    $kodePeriodeBaru
                );

                if ($kodeTarget !== null && $kodeTarget !== '') {
                    $kodePeriodeTarget[$kodeTarget] = true;
                }
            }

            $daftarPeriodeTerkunci = PeriodeGaji::query()
                ->whereIn('kode', array_keys($kodePeriodeTarget))
                ->lockForUpdate()
                ->get(['kode', 'status'])
                ->whereIn('status', ['approved', 'paid'])
                ->pluck('kode')
                ->all();

            if ($daftarPeriodeTerkunci !== []) {
                throw ValidationException::withMessages([
                    'target_periode' => ['Periode '.implode(', ', $daftarPeriodeTerkunci).' sudah berstatus Disetujui (Approved) atau Dicairkan (Paid) saat impor dijalankan. Data tidak dapat diimpor ke periode yang sudah terkunci.'],
                ]);
            }

            // 0. PRELOAD DATA MASTER SEKALI SAJA.
            // Database aplikasi berada di server remote (±140 ms per query), jadi setiap
            // lookup per slip/per baris ritase wajib dihindari agar impor tidak habis
            // puluhan menit atau menembus max_execution_time.
            $jenisTrukDefault = JenisTruk::query()->first();

            $petaTruk = Truk::query()->get()->keyBy('nomor_polisi');
            $petaRute = Rute::query()->get()->keyBy('kode');
            $petaPeriodeGaji = PeriodeGaji::query()->get()->keyBy('kode');
            $petaKaryawanNama = Karyawan::query()->get()->keyBy('nama_lengkap');
            $petaKaryawanNamaRapat = $petaKaryawanNama
                ->keyBy(fn (Karyawan $orang) => str_replace(' ', '', (string) $orang->nama_lengkap));
            $daftarEmailTerpakai = User::query()->pluck('email')->map(fn ($email) => strtolower((string) $email))->all();
            $daftarNikTerpakai = Karyawan::withTrashed()->pluck('nomor_induk_karyawan')->all();

            // Seluruh akun hasil migrasi memakai kata sandi awal yang sama,
            // cukup satu kali proses bcrypt yang relatif mahal.
            $kataSandiDefault = Hash::make('password');

            // 1. Daftarkan unit armada baru yang disetujui admin
            foreach ($staging['daftar_unit'] as $unitInfo) {
                $nopol = $unitInfo['nomor_polisi'];
                $keputusan = $keputusanUnit[$nopol]['aksi'] ?? ($unitInfo['terdaftar'] ? 'terdaftar' : 'daftar_baru');

                if ($keputusan === 'daftar_baru' && ! $unitInfo['terdaftar'] && ! $petaTruk->has($nopol)) {
                    $idJenisTruk = ! empty($keputusanUnit[$nopol]['id_jenis_truk'])
                        ? (int) $keputusanUnit[$nopol]['id_jenis_truk']
                        : ($jenisTrukDefault?->id);

                    $merekModel = ! empty($keputusanUnit[$nopol]['merek_model'])
                        ? strtoupper(trim((string) $keputusanUnit[$nopol]['merek_model']))
                        : $unitInfo['merek_model'];

                    $trukBaru = Truk::create([
                        'nomor_polisi' => $nopol,
                        'merek_model' => $merekModel ?: 'HINO PUTIH',
                        'id_jenis_truk' => $idJenisTruk,
                        'status' => 'aktif',
                    ]);

                    $petaTruk->put($trukBaru->nomor_polisi, $trukBaru);
                }
            }

            // 2. Daftarkan rute baru yang disetujui admin
            foreach ($staging['daftar_rute'] as $ruteInfo) {
                $kodeRute = $ruteInfo['kode'];
                $keputusan = $keputusanRute[$kodeRute]['aksi'] ?? ($ruteInfo['terdaftar'] ? 'terdaftar' : 'daftar_baru');

                if ($keputusan === 'daftar_baru' && ! $ruteInfo['terdaftar'] && ! $petaRute->has($kodeRute)) {
                    $ruteBaru = Rute::create([
                        'kode' => $kodeRute,
                        'material' => $ruteInfo['material'] ?: 'BATU BARA',
                        'tarif_per_ton' => $ruteInfo['tarif_per_ton'] > 0 ? $ruteInfo['tarif_per_ton'] : 150000,
                        'uang_jalan_default' => $ruteInfo['uang_jalan_default'] > 0 ? $ruteInfo['uang_jalan_default'] : 2000000,
                        'jarak_km' => 120,
                        'alamat_asal' => 'LOKASI ASAL',
                        'alamat_tujuan' => 'LOKASI TUJUAN',
                        'status' => 'aktif',
                    ]);

                    $petaRute->put($ruteBaru->kode, $ruteBaru);
                }
            }

            // Penolong resolusi memakai memori, bukan query berulang.
            $resolusikanTruk = function (string $nomorPolisi, string $merekModel) use (&$petaTruk, $jenisTrukDefault, $keputusanUnit): Truk {
                $nopolBersih = strtoupper(trim($nomorPolisi)) ?: 'BE 0000 ASM';

                $trukTerdaftar = $petaTruk->get($nopolBersih);
                if ($trukTerdaftar !== null) {
                    return $trukTerdaftar;
                }

                $idTrukTujuan = (int) ($keputusanUnit[$nopolBersih]['id_truk_tujuan'] ?? 0);
                if ($idTrukTujuan > 0) {
                    $trukTujuan = Truk::query()->find($idTrukTujuan);
                    if ($trukTujuan !== null) {
                        $petaTruk->put($nopolBersih, $trukTujuan);

                        return $trukTujuan;
                    }
                }

                $trukBaru = Truk::create([
                    'id_jenis_truk' => $jenisTrukDefault?->id,
                    'nomor_polisi' => $nopolBersih,
                    'merek_model' => strtoupper(trim($merekModel)) ?: 'HINO PUTIH',
                    'status' => 'aktif',
                ]);

                $petaTruk->put($nopolBersih, $trukBaru);

                return $trukBaru;
            };

            $resolusikanRute = function (string $kodeKeterangan, string $material, int $tarifPerTon, int $uangJalan, string $kodePeriode) use (&$petaRute): Rute {
                $kodeBersih = strtoupper(trim($kodeKeterangan));
                if ($kodeBersih === '' || $kodeBersih === '-') {
                    $kodeBersih = strtoupper($kodePeriode) === 'NOV-25' ? 'DMY-NOV' : 'MIP-PORT';
                }

                $ruteTerdaftar = $petaRute->get($kodeBersih);
                if ($ruteTerdaftar !== null) {
                    return $ruteTerdaftar;
                }

                $ruteBaru = $this->ambilAtauBuatRute($kodeBersih, $material, $tarifPerTon, $uangJalan, $kodePeriode);
                $petaRute->put($ruteBaru->kode, $ruteBaru);

                return $ruteBaru;
            };

            $cariKaryawan = function (string $namaSupir) use (&$petaKaryawanNama, &$petaKaryawanNamaRapat): ?Karyawan {
                return $petaKaryawanNama->get($namaSupir)
                    ?? $petaKaryawanNamaRapat->get(str_replace(' ', '', $namaSupir));
            };

            $buatKaryawan = function (?string $emailDiminta, ?string $nipDiminta, string $namaSupir) use (
                &$daftarEmailTerpakai,
                &$daftarNikTerpakai,
                &$petaKaryawanNama,
                &$petaKaryawanNamaRapat,
                $kataSandiDefault
            ): Karyawan {
                $emailKhusus = $emailDiminta !== null && trim($emailDiminta) !== ''
                    ? strtolower(trim($emailDiminta))
                    : $this->generateDefaultEmail($namaSupir, $daftarEmailTerpakai);

                $nipKhusus = $nipDiminta !== null && trim($nipDiminta) !== ''
                    ? strtoupper(trim($nipDiminta))
                    : $this->generateDefaultNik($namaSupir, $daftarNikTerpakai);

                // NIP yang diketik admin bisa bentrok dengan data lama; cari alternatif
                // tanpa menembak database (daftar NIK sudah dimuat di memori).
                if (in_array($nipKhusus, $daftarNikTerpakai, true)) {
                    $nipKhusus = $this->generateDefaultNik($namaSupir, $daftarNikTerpakai);
                } else {
                    $daftarNikTerpakai[] = $nipKhusus;
                }

                if (! in_array(strtolower($emailKhusus), $daftarEmailTerpakai, true)) {
                    $daftarEmailTerpakai[] = strtolower($emailKhusus);
                }

                $pengguna = User::firstOrCreate(
                    ['email' => $emailKhusus],
                    [
                        'name' => $namaSupir,
                        'password' => $kataSandiDefault,
                        'role' => UserRole::Supir,
                    ]
                );

                $karyawanBaru = Karyawan::create([
                    'id_pengguna' => $pengguna->id,
                    'nomor_induk_karyawan' => $nipKhusus,
                    'nama_lengkap' => $namaSupir,
                    'persentase_komisi_bp' => 1000,
                    'potongan_bpjs_bulanan' => 129000,
                    'status' => 'aktif',
                ]);

                $petaKaryawanNama->put($karyawanBaru->nama_lengkap, $karyawanBaru);
                $petaKaryawanNamaRapat->put(str_replace(' ', '', (string) $karyawanBaru->nama_lengkap), $karyawanBaru);

                return $karyawanBaru;
            };

            $jumlahSlipDiproses = 0;
            $jumlahSlipDilewati = 0;
            $jumlahRitaseDiproses = 0;
            $periodeTerdampak = [];
            $persenTerakhirDilaporkan = 5;

            // Penampung penulisan massal: satu INSERT per 200 baris, bukan per baris.
            $barisRitaseBaru = [];
            $agregatSlip = [];
            $pasanganPeriodeKaryawan = [];
            $periodeSudahDiperbarui = [];
            $waktuSekarang = now();
            $totalBlokSlip = count($daftarBlokSlip);

            // Tanggal pencairan historis dipakai untuk audit & pemotongan kasbon.
            $waktuPencairan = $statusPencairan === 'sudah_cair'
                ? (($tanggalPencairan !== null && trim((string) $tanggalPencairan) !== '')
                    ? Carbon::parse($tanggalPencairan)
                    : now())
                : null;

            foreach ($daftarBlokSlip as $indeksBlok => $blok) {
                $namaSupir = $blok['nama_supir_efektif'] ?? strtoupper(trim($blok['nama_supir']));

                $persen = (int) round(15 + ((($indeksBlok + 1) / max(1, $totalBlokSlip)) * 80));
                if ($persen >= $persenTerakhirDilaporkan + 5) {
                    $persenTerakhirDilaporkan = $persen;
                    $this->catatProgress($token, $persen, "Memproses slip supir {$namaSupir} (".($indeksBlok + 1).'/'.$totalBlokSlip.')...');
                }

                $keputusan = $keputusanSupir[$namaSupir]['aksi'] ?? 'buat_akun';

                // Jika supir dipilih untuk dilewati, jangan impor sheet ini
                if ($keputusan === 'lewati') {
                    $jumlahSlipDilewati++;

                    continue;
                }

                $kodePeriode = $this->tentukanKodePeriode(
                    $blok['kode_periode'],
                    $targetPeriode,
                    $kodePeriodeBaru
                );

                $periodeGaji = $petaPeriodeGaji->get($kodePeriode);
                if ($periodeGaji === null) {
                    $periodeGaji = $this->ambilAtauBuatPeriode(
                        $kodePeriode,
                        $blok['ritase'],
                        $statusPencairan,
                        $tanggalPencairan
                    );
                    $petaPeriodeGaji->put($kodePeriode, $periodeGaji);
                } elseif ($statusPencairan === 'sudah_cair' && ! isset($periodeSudahDiperbarui[$kodePeriode])) {
                    $periodeGaji->status = 'paid';
                    if ($tanggalPencairan !== null && trim($tanggalPencairan) !== '') {
                        $periodeGaji->tanggal_bayar = Carbon::parse($tanggalPencairan);
                    }
                    $periodeGaji->dicairkan_pada = $waktuPencairan;
                    $periodeGaji->save();
                    $periodeSudahDiperbarui[$kodePeriode] = true;
                }

                $periodeTerdampak[$periodeGaji->kode] = $periodeGaji->kode;

                $nopolHeader = $blok['nomor_polisi_efektif'] ?? strtoupper(trim($blok['nomor_polisi']));
                $trukHeader = $resolusikanTruk($nopolHeader, $blok['merek_truk'] ?? 'HINO PUTIH');

                // Resolusi supir: gabungkan ke data yang ada / buat baru / gunakan supir terdaftar
                $karyawan = null;

                if ($keputusan === 'gabungkan' && ! empty($keputusanSupir[$namaSupir]['id_karyawan_tujuan'])) {
                    $karyawan = Karyawan::query()->find((int) $keputusanSupir[$namaSupir]['id_karyawan_tujuan']);
                }

                $karyawan ??= $cariKaryawan($namaSupir);

                if ($karyawan === null) {
                    $karyawan = $buatKaryawan(
                        $keputusanSupir[$namaSupir]['email'] ?? null,
                        $keputusanSupir[$namaSupir]['nip'] ?? null,
                        $namaSupir
                    );
                }

                $pasanganPeriodeKaryawan[$periodeGaji->id][$karyawan->id] = true;

                $totalTonaseKgSlip = 0;
                $totalAngkutanSlip = 0;
                $totalUangJalanSlip = 0;
                $jumlahRitSlip = 0;

                foreach ($blok['ritase'] as $barisRit) {
                    $rute = $resolusikanRute(
                        (string) ($barisRit['keterangan'] ?? ''),
                        (string) ($barisRit['material'] ?? ''),
                        (int) $barisRit['tarif_per_ton'],
                        (int) $barisRit['uang_jalan'],
                        $periodeGaji->kode
                    );

                    // Nomor polisi per baris dipakai bila ada (sebelumnya semua baris
                    // dipaksa memakai plat header sehingga unit tercampur).
                    $nopolBaris = ! empty($barisRit['nomor_polisi'])
                        ? strtoupper(trim((string) $barisRit['nomor_polisi']))
                        : $trukHeader->nomor_polisi;

                    $trukBaris = $resolusikanTruk($nopolBaris, $blok['merek_truk'] ?? 'HINO PUTIH');

                    $barisRitaseBaru[] = [
                        'id_periode_gaji' => $periodeGaji->id,
                        'id_karyawan' => $karyawan->id,
                        'id_truk' => $trukBaris->id,
                        'id_rute' => $rute->id,
                        'tanggal_ritase' => $this->normalisasiTanggalRitase($barisRit['tanggal'], $periodeGaji->kode),
                        'jumlah_rit' => (int) $barisRit['jumlah_rit'],
                        'tonase_kg' => (int) $barisRit['tonase_kg'],
                        'volume_liter' => null,
                        'tarif_per_ton' => (int) $barisRit['tarif_per_ton'],
                        'total_angkutan' => (int) $barisRit['total_angkutan'],
                        'uang_jalan' => (int) $barisRit['uang_jalan'],
                        // Data migrasi adalah riwayat yang sudah terjadi & ditandatangani fisik.
                        // Status SJ dibuat terverifikasi agar ikut terhitung rekonsiliasi gaji dan
                        // tidak memblokir pengajuan periode; status dana diatur di baris penggajian.
                        'status' => 'terverifikasi',
                        'created_at' => $waktuSekarang,
                        'updated_at' => $waktuSekarang,
                    ];

                    $jumlahRitSlip += (int) $barisRit['jumlah_rit'];
                    $totalTonaseKgSlip += (int) $barisRit['tonase_kg'];
                    $totalAngkutanSlip += (int) $barisRit['total_angkutan'];
                    $totalUangJalanSlip += (int) $barisRit['uang_jalan'];
                    $jumlahRitaseDiproses++;
                }

                // Bila satu supir punya lebih dari satu sheet pada periode yang sama,
                // seluruh slip digabung menjadi satu baris penggajian agar tidak saling menimpa.
                $kunciSlip = $periodeGaji->id.'|'.$karyawan->id;

                if (! isset($agregatSlip[$kunciSlip])) {
                    $agregatSlip[$kunciSlip] = [
                        'id_periode_gaji' => $periodeGaji->id,
                        'id_karyawan' => $karyawan->id,
                        'id_truk' => $trukHeader->id,
                        'jumlah_rit' => 0,
                        'total_tonase_kg' => 0,
                        'total_angkutan' => 0,
                        'total_uang_jalan' => 0,
                        'rekap' => [
                            'total_angkutan_kotor' => 0,
                            'potongan_operasional' => 0,
                            'total_uang_jalan' => 0,
                            'biaya_sparepart' => 0,
                            'persentase_komisi_bp' => 0,
                            'potongan_bpjs' => 0,
                            'potongan_kasbon' => 0,
                            'gaji_bersih' => 0,
                        ],
                    ];
                }

                $agregatSlip[$kunciSlip]['jumlah_rit'] += $jumlahRitSlip;
                $agregatSlip[$kunciSlip]['total_tonase_kg'] += $totalTonaseKgSlip;
                $agregatSlip[$kunciSlip]['total_angkutan'] += $totalAngkutanSlip;
                $agregatSlip[$kunciSlip]['total_uang_jalan'] += $totalUangJalanSlip;

                foreach ($agregatSlip[$kunciSlip]['rekap'] as $kolomRekap => $nilaiRekapLama) {
                    $nilaiRekapBaru = (int) ($blok['rekap'][$kolomRekap] ?? 0);

                    $agregatSlip[$kunciSlip]['rekap'][$kolomRekap] = $kolomRekap === 'persentase_komisi_bp'
                        ? max($nilaiRekapLama, $nilaiRekapBaru)
                        : $nilaiRekapLama + $nilaiRekapBaru;
                }

                $jumlahSlipDiproses++;
            }

            $daftarIdPeriode = array_keys($pasanganPeriodeKaryawan);

            if ($daftarIdPeriode !== []) {
                // 3. Bersihkan ritase lama supir pada periode terkait: satu query per periode
                foreach ($pasanganPeriodeKaryawan as $idPeriode => $daftarKaryawan) {
                    Ritase::query()
                        ->where('id_periode_gaji', $idPeriode)
                        ->whereIn('id_karyawan', array_keys($daftarKaryawan))
                        ->delete();
                }

                // 4. Simpan seluruh baris ritase secara massal
                foreach (array_chunk($barisRitaseBaru, 200) as $potonganBarisRitase) {
                    Ritase::query()->insert($potonganBarisRitase);
                }
            }

            // 5. Seluruh angsuran kasbon supir terkait dimuat sekali, lalu dipetakan di memori.
            //    Kriteria potongan mengikuti PayrollCalculationService::hitungPotonganKasbonPeriode():
            //    angsuran tertaut ke periode ATAU tanggal_rencana jatuh di dalam rentang periode.
            $anggotaPeriode = [];
            foreach ($pasanganPeriodeKaryawan as $idPeriode => $daftarKaryawan) {
                $modelPeriode = $petaPeriodeGaji->firstWhere('id', $idPeriode);

                foreach (array_keys($daftarKaryawan) as $idKaryawan) {
                    $anggotaPeriode[] = [
                        'id_periode' => (int) $idPeriode,
                        'id_karyawan' => (int) $idKaryawan,
                        'tanggal_mulai' => $modelPeriode?->tanggal_mulai?->toDateString(),
                        'tanggal_selesai' => $modelPeriode?->tanggal_selesai?->toDateString(),
                    ];
                }
            }

            $daftarIdKaryawan = array_values(array_unique(array_column($anggotaPeriode, 'id_karyawan')));

            $angsuranKasbonTersedia = $daftarIdKaryawan === []
                ? collect()
                : AngsuranKasbon::query()
                    ->join('kasbon', 'kasbon.id', '=', 'angsuran_kasbon.id_kasbon')
                    ->whereNull('kasbon.deleted_at')
                    ->whereIn('kasbon.status', ['approved', 'lunas'])
                    ->whereIn('kasbon.id_karyawan', $daftarIdKaryawan)
                    ->select([
                        'angsuran_kasbon.id',
                        'angsuran_kasbon.id_kasbon',
                        'angsuran_kasbon.id_periode_gaji',
                        'angsuran_kasbon.status',
                        'angsuran_kasbon.nominal',
                        'angsuran_kasbon.tanggal_rencana',
                        'kasbon.id_karyawan as id_karyawan',
                    ])
                    ->get();

            $cocokDenganPeriode = function (object $angsuran, array $anggota): bool {
                if ((int) $angsuran->id_karyawan !== $anggota['id_karyawan']) {
                    return false;
                }

                if ((int) $angsuran->id_periode_gaji === $anggota['id_periode']) {
                    return true;
                }

                if ($angsuran->tanggal_rencana === null
                    || $anggota['tanggal_mulai'] === null
                    || $anggota['tanggal_selesai'] === null) {
                    return false;
                }

                $tanggalRencana = $angsuran->tanggal_rencana->toDateString();

                return $tanggalRencana >= $anggota['tanggal_mulai']
                    && $tanggalRencana <= $anggota['tanggal_selesai'];
            };

            $petaPotonganKasbon = [];
            foreach ($anggotaPeriode as $anggota) {
                $totalPotongan = 0;

                foreach ($angsuranKasbonTersedia as $angsuran) {
                    if ($cocokDenganPeriode($angsuran, $anggota)) {
                        $totalPotongan += (int) $angsuran->nominal;
                    }
                }

                $petaPotonganKasbon[$anggota['id_periode'].'|'.$anggota['id_karyawan']] = $totalPotongan;
            }

            // 6. Baris penggajian memakai data yang sudah dimuat di memori
            $penggajianTersedia = $daftarIdPeriode === []
                ? collect()
                : Penggajian::query()
                    ->whereIn('id_periode_gaji', $daftarIdPeriode)
                    ->get()
                    ->keyBy(fn (Penggajian $baris) => $baris->id_periode_gaji.'|'.$baris->id_karyawan);

            $totalGajiBersihDiproses = 0;

            foreach ($agregatSlip as $slip) {
                $rekap = $slip['rekap'];

                $angkutanKotor = $rekap['total_angkutan_kotor'] > 0
                    ? $rekap['total_angkutan_kotor']
                    : $slip['total_angkutan'];
                $potonganOperasional = $rekap['potongan_operasional'];
                $totalUangJalan = $rekap['total_uang_jalan'] > 0
                    ? $rekap['total_uang_jalan']
                    : $slip['total_uang_jalan'];
                $biayaSparepart = $rekap['biaya_sparepart'];
                $persentaseKomisiBp = $rekap['persentase_komisi_bp'] > 0
                    ? $rekap['persentase_komisi_bp']
                    : 1000;

                $kunciPenggajian = $slip['id_periode_gaji'].'|'.$slip['id_karyawan'];
                $potonganKasbon = (int) ($petaPotonganKasbon[$kunciPenggajian] ?? 0);

                $hasilPerhitunganRumus = $this->layananKalkulasiGaji->hitungKomponenGaji([
                    'total_rit' => $slip['jumlah_rit'],
                    'total_tonase_kg' => $slip['total_tonase_kg'],
                    'total_angkutan_kotor' => $angkutanKotor,
                    'potongan_operasional' => $potonganOperasional,
                    'total_uang_jalan' => $totalUangJalan,
                    'biaya_sparepart' => $biayaSparepart,
                    'persentase_komisi_bp' => $persentaseKomisiBp,
                    'potongan_bpjs' => $rekap['potongan_bpjs'],
                    'potongan_kasbon' => $potonganKasbon,
                ]);

                $gajiBersih = $rekap['gaji_bersih'] > 0
                    ? $rekap['gaji_bersih']
                    : $hasilPerhitunganRumus['gaji_bersih'];

                $atributPenggajian = [
                    'id_truk' => $slip['id_truk'],
                    'total_rit' => $hasilPerhitunganRumus['total_rit'],
                    'total_tonase_kg' => $hasilPerhitunganRumus['total_tonase_kg'],
                    'total_angkutan_kotor' => $hasilPerhitunganRumus['total_angkutan_kotor'],
                    'potongan_operasional' => $hasilPerhitunganRumus['potongan_operasional'],
                    'angkutan_bersih' => $hasilPerhitunganRumus['angkutan_bersih'],
                    'total_uang_jalan' => $hasilPerhitunganRumus['total_uang_jalan'],
                    'biaya_sparepart' => $hasilPerhitunganRumus['biaya_sparepart'],
                    'hasil_bersih_armada' => $hasilPerhitunganRumus['hasil_bersih_armada'],
                    'persentase_komisi_bp' => $hasilPerhitunganRumus['persentase_komisi_bp'],
                    'gaji_komisi' => $hasilPerhitunganRumus['gaji_komisi'],
                    'potongan_bpjs' => $hasilPerhitunganRumus['potongan_bpjs'],
                    'potongan_kasbon' => $hasilPerhitunganRumus['potongan_kasbon'],
                    'gaji_bersih' => $gajiBersih,
                    'status' => $statusPencairan === 'sudah_cair' ? 'paid' : 'draft',
                ];

                // Impor historis "sudah dicairkan" wajib meninggalkan jejak waktu cair.
                if ($waktuPencairan !== null) {
                    $atributPenggajian['dicairkan_pada'] = $waktuPencairan;
                }

                $penggajian = $penggajianTersedia->get($kunciPenggajian);

                if ($penggajian !== null) {
                    $penggajian->fill($atributPenggajian);
                    if ($penggajian->isDirty()) {
                        $penggajian->save();
                    }
                } else {
                    $penggajian = Penggajian::create(array_merge([
                        'id_periode_gaji' => $slip['id_periode_gaji'],
                        'id_karyawan' => $slip['id_karyawan'],
                    ], $atributPenggajian));
                    $penggajianTersedia->put($kunciPenggajian, $penggajian);
                }

                $totalGajiBersihDiproses += $gajiBersih;
            }

            // 7. Pencairan historis: tandai angsuran kasbon sebagai terpotong dan kurangi sisa pinjaman.
            //    Tanpa ini baris gaji berstatus "paid" tetapi utang kasbon supir tidak pernah lunas.
            if ($waktuPencairan !== null && $angsuranKasbonTersedia->isNotEmpty()) {
                $idAngsuranTerpotong = [];
                $nominalPerKasbon = [];
                $idAngsuranPerPeriode = [];

                foreach ($anggotaPeriode as $anggota) {
                    foreach ($angsuranKasbonTersedia as $angsuran) {
                        if ($angsuran->status !== 'terjadwal' || ! $cocokDenganPeriode($angsuran, $anggota)) {
                            continue;
                        }

                        // Satu angsuran hanya boleh mengurangi sisa pinjaman sekali,
                        // meski cocok lewat tautan periode maupun tanggal rencana.
                        if (isset($idAngsuranTerpotong[(int) $angsuran->id])) {
                            continue;
                        }

                        $idAngsuranTerpotong[(int) $angsuran->id] = true;
                        $idKasbonAngsuran = (int) $angsuran->id_kasbon;
                        $nominalPerKasbon[$idKasbonAngsuran] = ($nominalPerKasbon[$idKasbonAngsuran] ?? 0) + (int) $angsuran->nominal;

                        if ($angsuran->id_periode_gaji === null) {
                            $idAngsuranPerPeriode[$anggota['id_periode']][] = (int) $angsuran->id;
                        }
                    }
                }

                if ($idAngsuranTerpotong !== []) {
                    AngsuranKasbon::query()
                        ->whereIn('id', array_keys($idAngsuranTerpotong))
                        ->update([
                            'status' => 'terpotong',
                            'tanggal_potong' => $waktuPencairan->toDateString(),
                        ]);

                    // Angsuran yang belum terhubung ke periode diikatkan ke periode yang sedang diimpor.
                    foreach ($idAngsuranPerPeriode as $idPeriodeAngsuran => $daftarIdAngsuran) {
                        AngsuranKasbon::query()
                            ->whereIn('id', $daftarIdAngsuran)
                            ->whereNull('id_periode_gaji')
                            ->update(['id_periode_gaji' => $idPeriodeAngsuran]);
                    }

                    $petaKasbon = Kasbon::query()
                        ->whereIn('id', array_keys($nominalPerKasbon))
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    foreach ($nominalPerKasbon as $idKasbon => $nominalPotong) {
                        $kasbon = $petaKasbon->get($idKasbon);
                        if ($kasbon === null) {
                            continue;
                        }

                        $sisaBaru = max(0, (int) $kasbon->sisa_pinjaman - $nominalPotong);
                        $kasbon->update([
                            'sisa_pinjaman' => $sisaBaru,
                            'status' => $sisaBaru === 0 ? 'lunas' : $kasbon->status,
                        ]);
                    }
                }
            }

            return [
                'jumlah_slip' => $jumlahSlipDiproses,
                'jumlah_dilewati' => $jumlahSlipDilewati,
                'jumlah_ritase' => $jumlahRitaseDiproses,
                'daftar_periode' => array_values($periodeTerdampak),
                'status_pencairan' => $statusPencairan,
                'total_gaji_bersih' => $totalGajiBersihDiproses,
            ];
        });

        $this->catatProgress($token, 100, 'Seluruh data berhasil disimpan ke database!', true, $hasilImpor);
        Cache::forget("migrasi_staging_{$token}");

        return $hasilImpor;
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

        $daftarSheetXml = [];
        $xmlWb = $arsip->getFromName('xl/workbook.xml');
        $xmlRels = $arsip->getFromName('xl/_rels/workbook.xml.rels');

        if (is_string($xmlWb) && $xmlWb !== '' && is_string($xmlRels) && $xmlRels !== '') {
            $wbObj = @simplexml_load_string($xmlWb);
            $relsObj = @simplexml_load_string($xmlRels);

            if ($wbObj instanceof SimpleXMLElement && $relsObj instanceof SimpleXMLElement) {
                $petaRelasi = [];
                foreach ($relsObj->Relationship as $rel) {
                    $idRel = (string) ($rel['Id'] ?? '');
                    $targetRel = (string) ($rel['Target'] ?? '');
                    if ($idRel !== '' && $targetRel !== '') {
                        $targetBersih = str_starts_with($targetRel, '/') ? substr($targetRel, 1) : $targetRel;
                        if (! str_starts_with($targetBersih, 'xl/')) {
                            $targetBersih = 'xl/'.$targetBersih;
                        }
                        $petaRelasi[$idRel] = $targetBersih;
                    }
                }

                if (isset($wbObj->sheets->sheet)) {
                    foreach ($wbObj->sheets->sheet as $sheetElemen) {
                        $namaSheet = trim((string) ($sheetElemen['name'] ?? ''));
                        $rId = (string) $sheetElemen->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                        $targetFile = $petaRelasi[$rId] ?? '';

                        if ($targetFile !== '') {
                            // Filter nama sheet yang diabaikan (panduan, referensi, rekapitulasi eksekutif)
                            $namaSheetUpper = strtoupper($namaSheet);
                            if (
                                str_contains($namaSheetUpper, 'PANDUAN')
                                || str_contains($namaSheetUpper, 'PETUNJUK')
                                || str_contains($namaSheetUpper, 'BATASAN')
                                || str_contains($namaSheetUpper, 'CONTOH FORMAT')
                                || str_contains($namaSheetUpper, 'CONTOH VISUAL')
                                || str_contains($namaSheetUpper, 'CONTOH PANDUAN')
                                || str_contains($namaSheetUpper, 'REKAPITULASI')
                                || str_starts_with($namaSheetUpper, 'REKAP')
                                || str_contains($namaSheetUpper, 'README')
                                || str_contains($namaSheetUpper, 'INFO')
                            ) {
                                continue;
                            }

                            $daftarSheetXml[] = $targetFile;
                        }
                    }
                }
            }
        }

        // Fallback jika pemetaan workbook.xml tidak ditemukan
        if ($daftarSheetXml === []) {
            for ($indeks = 0; $indeks < $arsip->numFiles; $indeks++) {
                $namaEntri = $arsip->getNameIndex($indeks);
                if (is_string($namaEntri) && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $namaEntri)) {
                    $daftarSheetXml[] = $namaEntri;
                }
            }
            natsort($daftarSheetXml);
        }

        $semuaMatriks = [];
        foreach ($daftarSheetXml as $namaSheet) {
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
                if (str_contains($kolomPertama, 'CONTOH FORMAT') || str_contains($kolomPertama, 'PANDUAN') || str_contains($kolomPertama, 'JANGAN DIIMPOR')) {
                    continue;
                }
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

        if (str_contains(strtoupper(trim($daftarBaris[0][0] ?? '')), 'CONTOH FORMAT') || str_contains(strtoupper(trim($daftarBaris[0][0] ?? '')), 'PANDUAN')) {
            return null;
        }
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
            'potongan_bpjs' => 0,
            'potongan_kasbon' => 0,
            'gaji_bersih' => 0,
        ];

        foreach ($daftarBaris as $baris) {
            $kol0 = trim($baris[0] ?? '');
            $kol0Atas = strtoupper($kol0);

            // Cek apakah baris ini memuat label identitas supir, truk, periode, atau nomor polisi
            for ($idxCol = 0; $idxCol < count($baris); $idxCol++) {
                $selUpper = strtoupper(trim($baris[$idxCol] ?? ''));
                if (($selUpper === 'NAMA SUPIR' || $selUpper === 'NAMA') && isset($baris[$idxCol + 1]) && trim($baris[$idxCol + 1]) !== '') {
                    $namaSupir = trim($baris[$idxCol + 1]);
                }
                if ($selUpper === 'MERK' && isset($baris[$idxCol + 1]) && trim($baris[$idxCol + 1]) !== '') {
                    $merekTruk = trim($baris[$idxCol + 1]) ?: 'HINO PUTIH';
                }
                if ($selUpper === 'PERIODE' && isset($baris[$idxCol + 1]) && trim($baris[$idxCol + 1]) !== '') {
                    $kodePeriode = trim($baris[$idxCol + 1]);
                }
                if (($selUpper === 'PLAT' || $selUpper === 'NO. POL' || $selUpper === 'NOPOL') && isset($baris[$idxCol + 1]) && trim($baris[$idxCol + 1]) !== '') {
                    $nomorPolisiHeader = trim($baris[$idxCol + 1]);
                }
            }

            if ($kol0Atas === 'NAMA SUPIR' || $kol0Atas === 'PLAT') {
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
                        'nomor_polisi' => strtoupper(trim($nopolBaris)) ?: ($nomorPolisiHeader ?: null),
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

        $namaSupirBersih = trim($namaSupir);
        if ($namaSupirBersih === '' || str_starts_with($namaSupirBersih, '[') || str_ends_with($namaSupirBersih, ']') || strtoupper($namaSupirBersih) === '[NAMA SUPIR]') {
            return null;
        }

        $nomorPolisiFinal = $nomorPolisiBaris !== ''
            ? $nomorPolisiBaris
            : ($nomorPolisiHeader !== '' ? $nomorPolisiHeader : 'BE 0000 XX');

        $nopolCek = strtoupper(trim($nomorPolisiFinal));
        if ($nopolCek === '' || str_starts_with($nopolCek, '[') || str_ends_with($nopolCek, ']') || $nopolCek === '[NO POLISI]') {
            return null;
        }

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

        $kodeBersih = trim($kodeDariBerkas);

        // Jika berupa serial tanggal numerik Excel (misal: 46356), konversi ke format Mmm-yy
        if (ctype_digit($kodeBersih) && (int) $kodeBersih > 40000 && (int) $kodeBersih < 60000) {
            $tanggal = Carbon::create(1899, 12, 30)->addDays((int) $kodeBersih);

            return SiklusPeriodeGaji::kodeDariTanggal($tanggal);
        }

        return $kodeBersih !== '' ? $kodeBersih : 'Jan-26';
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
                $periode->dicairkan_pada = ($tanggalPencairan !== null && trim($tanggalPencairan) !== '')
                    ? Carbon::parse($tanggalPencairan)
                    : now();
                $periode->save();
            }

            return $periode;
        }

        // Rentang periode historis diambil dari tanggal NYATA di dalam berkas,
        // bukan dipaksa tanggal 1 s/d akhir bulan (sistem kini memakai tutup buku manual).
        $daftarTanggal = array_map(
            fn (array $item) => $this->normalisasiTanggalRitase($item['tanggal'], $kodePeriode),
            $daftarRitase
        );
        sort($daftarTanggal);

        $tanggalAcuan = SiklusPeriodeGaji::tanggalDariKode($kodePeriode);
        $tanggalMulai = $daftarTanggal[0]
            ?? $tanggalAcuan?->copy()->startOfMonth()->toDateString()
            ?? now()->toDateString();
        $tanggalSelesai = $daftarTanggal === []
            ? ($tanggalAcuan?->copy()->endOfMonth()->toDateString() ?? now()->toDateString())
            : $daftarTanggal[count($daftarTanggal) - 1];
        $tanggalBayar = ($tanggalPencairan !== null && trim($tanggalPencairan) !== '')
            ? Carbon::parse($tanggalPencairan)->toDateString()
            : Carbon::parse($tanggalSelesai)->addDays(5)->toDateString();

        return PeriodeGaji::create([
            'kode' => $kodePeriode,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'tanggal_bayar' => $tanggalBayar,
            'status' => $statusTarget,
            'dicairkan_pada' => $statusPencairan === 'sudah_cair'
                ? (($tanggalPencairan !== null && trim($tanggalPencairan) !== '')
                    ? Carbon::parse($tanggalPencairan)
                    : now())
                : null,
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
        $namaBersih = strtoupper(trim($namaDariSlip));
        $namaTanpaSpasi = str_replace(' ', '', $namaBersih);

        // 1. Prioritaskan pencarian berdasarkan nama lengkap supir yang tertera di slip
        if ($namaBersih !== '' && $namaBersih !== 'SUPIR MIGRASI') {
            $karyawanNama = Karyawan::query()
                ->where('nama_lengkap', $namaBersih)
                ->orWhere('nama_lengkap', $namaTanpaSpasi)
                ->first();

            if ($karyawanNama !== null) {
                return $karyawanNama;
            }
        }

        // 2. Jika nama di slip kosong atau generik (SUPIR MIGRASI), periksa riwayat supir truk sebelumnya
        if ($namaBersih === '' || $namaBersih === 'SUPIR MIGRASI') {
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
        }

        // 3. Buat supir/karyawan baru jika belum terdaftar di database master
        $namaKaryawanBaru = $namaBersih !== '' ? $namaBersih : 'SUPIR MIGRASI';
        $kodeNikUnik = $this->generateDefaultNik($namaKaryawanBaru);
        $emailUnik = $this->generateDefaultEmail($namaKaryawanBaru);

        $pengguna = User::firstOrCreate(
            ['email' => $emailUnik],
            [
                'name' => $namaKaryawanBaru,
                'password' => Hash::make('password'),
                'role' => UserRole::Supir,
            ]
        );

        return Karyawan::create([
            'id_pengguna' => $pengguna->id,
            'nomor_induk_karyawan' => $kodeNikUnik,
            'nama_lengkap' => $namaKaryawanBaru,
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
            'material' => strtoupper(trim($material)) ?: 'BATU BARA',
            'tarif_per_ton' => $tarifPerTon > 0 ? $tarifPerTon : 150000,
            'uang_jalan_default' => $uangJalan > 0 ? $uangJalan : 2000000,
            'jarak_km' => 120,
            'alamat_asal' => 'LOKASI ASAL',
            'alamat_tujuan' => 'LOKASI TUJUAN',
            'status' => 'aktif',
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

    private function labelStatusPeriode(?string $status): string
    {
        return match ($status) {
            'approved' => 'Disetujui',
            'paid' => 'Sudah Dicairkan',
            'pending' => 'Menunggu Persetujuan',
            default => 'Draf',
        };
    }
}
