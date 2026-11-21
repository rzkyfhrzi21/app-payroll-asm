<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Models\Penggajian;
use App\Services\PayrollCalculationService;
use App\Services\PayrollDataService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: TINJAUAN & OTORISASI GAJI PIMPINAN (EXECUTIVE PAYROLL REVIEW)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Meja Tanda Tangan Basah & Pulpen Sakti Direktur Utama".
 * Setelah staf admin selesai menghitung seluruh ritase dan mengajukan draf gaji,
 * bundel dokumen tersebut diletakkan di meja pimpinan ini.
 * 
 * Hak Prerogatif Pimpinan:
 * 1. Memberikan ACC (Persetujuan) : Bisa disetujui satu per satu (satuan),
 *    beberapa supir via centang checkbox, atau sekaligus "ACC Semua" ('approved').
 * 2. Menolak / Mengembalikan Draf : Jika ada angka yang mencurigakan (misal bon
 *    sparepart janggal), pimpinan menolak draf tersebut kembali ke status 'draft'
 *    disertai catatan alasan penolakan agar admin memperbaikinya.
 * 3. Eksekusi Pencairan Dana     : Menekan tombol "Cairkan Gaji" ('paid') yang
 *    otomatis memotong jadwal cicilan kasbon periode berjalan dan mengurangi utang supir.
 * ============================================================================
 */
class PayrollReviewController extends Controller
{
    // Menggunakan trait respon interaksi notifikasi toast standar
    use ResponInteraksiStandar;

    /**
     * Menampilkan Halaman Tinjauan Penggajian Pimpinan
     * 
     * Alur Kerja Bernomor:
     * (1) Cek apakah pimpinan sudah memilih kode periode di URL (?periode=...).
     * (2) Jika belum memilih, tampilkan kartu daftar periode pengajuan lebih dulu.
     * (3) Jika sudah memilih periode, tampilkan lembar verifikasi lengkap per supir.
     */
    public function index(Request $permintaan, PayrollDataService $layananDataPayroll): View
    {
        $kodePeriode = $permintaan->query('periode');

        // Tanpa ?periode= Pimpinan melihat daftar periode pengajuan lebih dulu.
        $adaPeriodeTerpilih = is_string($kodePeriode) && trim($kodePeriode) !== '';
        $periode = $adaPeriodeTerpilih
            ? $layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode)
            : null;

        if ($periode === null) {
            return view('executive.payroll.index', $this->dataPemilihPeriode($layananDataPayroll));
        }

        return view('executive.payroll.index', $this->dataTampilan($periode, $layananDataPayroll));
    }

    /**
     * Membuka Detail Penggajian Satu Periode Tertentu
     */
    public function show(string $period, PayrollDataService $layananDataPayroll): View
    {
        $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period);

        if ($periode === null) {
            return view('executive.payroll.index', $this->dataPemilihPeriode($layananDataPayroll));
        }

        return view('executive.payroll.index', $this->dataTampilan($periode, $layananDataPayroll));
    }

    /**
     * Data untuk tahap pemilihan periode (sebelum rincian supir ditampilkan).
     *
     * @return array<string, mixed>
     */
    private function dataPemilihPeriode(PayrollDataService $layananDataPayroll): array
    {
        return [
            'periodeTerpilih' => false,
            'period' => $layananDataPayroll->formatPeriode(null),
            'periodeModel' => null,
            'periodOptions' => $layananDataPayroll->daftarOpsiPeriode(),
            'daftarPeriode' => $layananDataPayroll->ringkasanSemuaPeriodeEksekutif(),
            'summary' => [],
            'rows' => [],
            'permintaanAcc' => [
                'ada_pengajuan' => false,
                'sudah_diajukan' => false,
                'sudah_acc' => false,
                'sudah_cair' => false,
                'status_kode' => null,
                'status_label' => 'Pilih Periode',
                'diajukan_pada' => null,
                'kode_periode' => '—',
                'rentang' => '—',
                'jumlah_supir' => 0,
                'jumlah_rit' => 0,
                'tonase' => '0,00',
                'sj_draft_atau_menunggu' => 0,
                'sj_belum_verifikasi' => 0,
                'catatan_penolakan' => null,
                'jumlah_sudah_acc' => 0,
                'jumlah_sudah_cair' => 0,
                'siap_cair' => false,
                'pemeriksaan' => [],
                'total_kas' => 0,
                'total_potongan' => 0,
                'omzet' => 0,
                'uang_jalan' => 0,
                'sparepart' => 0,
                'komisi' => 0,
            ],
            'riwayatPermintaanAcc' => $layananDataPayroll->riwayatPermintaanAcc(),
        ];
    }

    /**
     * Data bersama untuk halaman daftar & detail otorisasi penggajian.
     *
     * @return array<string, mixed>
     */
    private function dataTampilan(?\App\Models\PeriodeGaji $periode, PayrollDataService $layananDataPayroll): array
    {
        return [
            'periodeTerpilih' => true,
            'period' => $layananDataPayroll->formatPeriode($periode),
            'periodeModel' => $periode,
            'periodOptions' => $layananDataPayroll->daftarOpsiPeriode(),
            'daftarPeriode' => $layananDataPayroll->ringkasanSemuaPeriodeEksekutif(),
            'summary' => $layananDataPayroll->ringkasanEksekutif($periode),
            'rows' => $layananDataPayroll->barisReviewPayrollEksekutif($periode),
            'permintaanAcc' => $layananDataPayroll->permintaanAccPimpinan($periode),
            'riwayatPermintaanAcc' => $layananDataPayroll->riwayatPermintaanAcc(),
        ];
    }

    public function approve(
        Request $permintaan,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasi,
    ): RedirectResponse|JsonResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode penggajian '{$period}' tidak ditemukan.",
                judul: 'Otorisasi Gagal',
                aksi: 'EXECUTIVE_APPROVE_NOT_FOUND',
                kodeHttp: 404,
            );
        }

        // Kewenangan Pimpinan bersifat otorisasi atas pengajuan Admin HRD.
        // Periode yang masih draf berarti Admin belum mengajukan, jadi tidak boleh di-ACC.
        if ($periodeModel->status === 'draft') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode {$periodeModel->kode} belum diajukan oleh Admin HRD. Minta Admin menyelesaikan verifikasi surat jalan lalu mengajukan periode terlebih dahulu.",
                judul: 'Otorisasi ACC Ditolak',
                aksi: 'EXECUTIVE_APPROVE_NOT_SUBMITTED',
                statusKode: 'PERIOD_NOT_SUBMITTED',
                kodeHttp: 422,
                parameterRute: ['periode' => $periodeModel->kode],
            );
        }

        if ($permintaan->filled('id_penggajian')) {
            $tervalidasi = $permintaan->validate([
                'id_penggajian' => ['required', 'integer', 'exists:penggajian,id'],
            ]);
            $idPenggajian = (int) $tervalidasi['id_penggajian'];

            $penggajian = Penggajian::query()
                ->with('karyawan')
                ->where('id_periode_gaji', $periodeModel->id)
                ->where('id', $idPenggajian)
                ->first();

            if ($penggajian === null) {
                return $this->responGalatStandar(
                    permintaan: $permintaan,
                    ruteTujuan: 'executive.payroll.index',
                    pesan: "Data gaji supir tidak ditemukan pada periode {$periodeModel->kode}.",
                    judul: 'Otorisasi Satuan Ditolak',
                    aksi: 'EXECUTIVE_APPROVE_DRIVER_INVALID',
                    kodeHttp: 422,
                    parameterRute: ['periode' => $periodeModel->kode],
                );
            }

            if ($penggajian->status === 'paid') {
                $namaSupir = $penggajian->karyawan?->nama_lengkap ?? 'Supir';

                return $this->responGalatStandar(
                    permintaan: $permintaan,
                    ruteTujuan: 'executive.payroll.index',
                    pesan: "Gaji supir {$namaSupir} pada periode {$periodeModel->kode} sudah dicairkan (Paid) dan tidak dapat diubah lagi.",
                    judul: 'Otorisasi Satuan Ditolak',
                    aksi: 'EXECUTIVE_APPROVE_DRIVER_ALREADY_PAID',
                    statusKode: 'DRIVER_ALREADY_PAID',
                    kodeHttp: 422,
                    parameterRute: ['periode' => $periodeModel->kode],
                );
            }

            $periodeTerbaru = $layananKalkulasi->prosesPersetujuanSupir(
                $periodeModel,
                [$idPenggajian],
                (int) auth()->id(),
            );
            $namaSupir = $penggajian->karyawan?->nama_lengkap ?? 'Supir';

            return $this->responSuksesStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Gaji supir {$namaSupir} pada periode {$periodeTerbaru->kode} telah disetujui (ACC) oleh Pimpinan.",
                judul: 'ACC Supir Berhasil',
                aksi: 'EXECUTIVE_APPROVE_DRIVER_PAYROLL',
                dataTambahan: [
                    'periode' => $periodeTerbaru->kode,
                    'status' => $periodeTerbaru->status,
                    'id_penggajian' => $idPenggajian,
                ],
                parameterRute: ['periode' => $periodeTerbaru->kode],
            );
        }

        if ($periodeModel->status === 'paid') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode {$periodeModel->kode} sudah dicairkan (Paid) dan tidak dapat di-ACC ulang.",
                judul: 'Otorisasi ACC Ditolak',
                aksi: 'EXECUTIVE_APPROVE_ALREADY_PAID',
                statusKode: 'PERIOD_ALREADY_PAID',
                kodeHttp: 422,
                parameterRute: ['periode' => $periodeModel->kode],
            );
        }

        $periodeTerbaru = $layananKalkulasi->prosesPersetujuanPeriode($periodeModel, 'approve', (int) auth()->id());

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'executive.payroll.index',
            pesan: "Penggajian periode {$periodeTerbaru->kode} telah disetujui (ACC) oleh Pimpinan dan siap dicetak slip gajinya.",
            judul: 'Otorisasi ACC Pimpinan Berhasil',
            aksi: 'EXECUTIVE_APPROVE_PAYROLL',
            dataTambahan: [
                'periode' => $periodeTerbaru->kode,
                'status' => $periodeTerbaru->status,
            ],
            parameterRute: ['periode' => $periodeTerbaru->kode],
        );
    }

    public function reject(
        Request $permintaan,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasi,
    ): RedirectResponse|JsonResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode penggajian '{$period}' tidak ditemukan.",
                judul: 'Penolakan Gagal',
                aksi: 'EXECUTIVE_REJECT_NOT_FOUND',
                kodeHttp: 404,
            );
        }

        if ($periodeModel->status === 'paid') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode {$periodeModel->kode} sudah dicairkan (Paid) sehingga tidak dapat ditolak atau dikembalikan ke Draf.",
                judul: 'Penolakan Ditolak',
                aksi: 'EXECUTIVE_REJECT_ALREADY_PAID',
                statusKode: 'PERIOD_ALREADY_PAID',
                kodeHttp: 422,
                parameterRute: ['periode' => $periodeModel->kode],
            );
        }

        $alasanRevisi = trim((string) $permintaan->input('alasan_penolakan', $permintaan->input('note', 'Perlu koreksi data perhitungan gaji')));
        $periodeTerbaru = $layananKalkulasi->prosesPersetujuanPeriode($periodeModel, 'reject', (int) auth()->id(), $alasanRevisi);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'executive.payroll.index',
            pesan: "Penggajian periode {$periodeTerbaru->kode} dikembalikan ke status Draf untuk direvisi oleh Admin. Catatan Pimpinan: {$alasanRevisi}",
            judul: 'Permintaan Revisi Dikirim ke Admin',
            aksi: 'EXECUTIVE_REJECT_PAYROLL',
            dataTambahan: [
                'periode' => $periodeTerbaru->kode,
                'status' => $periodeTerbaru->status,
                'alasan' => $alasanRevisi,
            ],
            parameterRute: ['periode' => $periodeTerbaru->kode],
        );
    }

    public function approveBatch(
        Request $permintaan,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasi,
    ): RedirectResponse|JsonResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode penggajian '{$period}' tidak ditemukan.",
                judul: 'ACC Batch Gagal',
                aksi: 'EXECUTIVE_BATCH_APPROVE_NOT_FOUND',
                kodeHttp: 404,
            );
        }

        if ($periodeModel->status === 'draft') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode {$periodeModel->kode} belum diajukan oleh Admin HRD sehingga belum dapat di-ACC.",
                judul: 'ACC Batch Ditolak',
                aksi: 'EXECUTIVE_BATCH_APPROVE_NOT_SUBMITTED',
                statusKode: 'PERIOD_NOT_SUBMITTED',
                kodeHttp: 422,
                parameterRute: ['periode' => $periodeModel->kode],
            );
        }

        if ($periodeModel->status === 'paid') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode {$periodeModel->kode} sudah dicairkan (Paid) dan tidak dapat di-ACC ulang.",
                judul: 'ACC Batch Ditolak',
                aksi: 'EXECUTIVE_BATCH_APPROVE_ALREADY_PAID',
                statusKode: 'PERIOD_ALREADY_PAID',
                kodeHttp: 422,
                parameterRute: ['periode' => $periodeModel->kode],
            );
        }

        $tervalidasi = $permintaan->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:penggajian,id'],
        ]);

        $daftarId = array_values(array_unique(array_map('intval', $tervalidasi['ids'] ?? [])));

        if ($daftarId !== []) {
            $barisTerpilih = Penggajian::query()
                ->where('id_periode_gaji', $periodeModel->id)
                ->whereIn('id', $daftarId)
                ->get(['id', 'status']);

            if ($barisTerpilih->isEmpty()) {
                return $this->responGalatStandar(
                    permintaan: $permintaan,
                    ruteTujuan: 'executive.payroll.index',
                    pesan: "Baris gaji terpilih tidak ditemukan pada periode {$periodeModel->kode}.",
                    judul: 'ACC Terpilih Ditolak',
                    aksi: 'EXECUTIVE_BATCH_APPROVE_INVALID',
                    kodeHttp: 422,
                    parameterRute: ['periode' => $periodeModel->kode],
                );
            }

            $idValid = $barisTerpilih
                ->filter(fn (Penggajian $baris): bool => $baris->status !== 'paid')
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all();

            if ($idValid === []) {
                return $this->responGalatStandar(
                    permintaan: $permintaan,
                    ruteTujuan: 'executive.payroll.index',
                    pesan: "Seluruh baris gaji terpilih pada periode {$periodeModel->kode} sudah dicairkan (Paid) dan tidak dapat di-ACC ulang.",
                    judul: 'ACC Terpilih Ditolak',
                    aksi: 'EXECUTIVE_BATCH_APPROVE_ALREADY_PAID',
                    statusKode: 'DRIVER_ALREADY_PAID',
                    kodeHttp: 422,
                    parameterRute: ['periode' => $periodeModel->kode],
                );
            }

            $periodeTerbaru = $layananKalkulasi->prosesPersetujuanSupir($periodeModel, $idValid, (int) auth()->id());
            $jumlahDisetujui = count($idValid);

            return $this->responSuksesStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "{$jumlahDisetujui} baris gaji supir terpilih pada periode {$periodeTerbaru->kode} berhasil di-ACC oleh Pimpinan.",
                judul: 'ACC Terpilih Berhasil',
                aksi: 'EXECUTIVE_BATCH_APPROVE_PAYROLL',
                dataTambahan: [
                    'periode' => $periodeTerbaru->kode,
                    'status' => $periodeTerbaru->status,
                    'jumlah_disetujui' => $jumlahDisetujui,
                ],
                parameterRute: ['periode' => $periodeTerbaru->kode],
            );
        }

        $periodeTerbaru = $layananKalkulasi->prosesPersetujuanPeriode($periodeModel, 'approve', (int) auth()->id());

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'executive.payroll.index',
            pesan: "Seluruh rekap penggajian valid pada periode {$periodeTerbaru->kode} berhasil di-ACC secara batch oleh Pimpinan.",
            judul: 'ACC Batch Payroll Berhasil',
            aksi: 'EXECUTIVE_BATCH_APPROVE_PAYROLL',
            dataTambahan: [
                'periode' => $periodeTerbaru->kode,
                'status' => $periodeTerbaru->status,
            ],
            parameterRute: ['periode' => $periodeTerbaru->kode],
        );
    }

    public function disburse(
        Request $permintaan,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasi,
    ): RedirectResponse|JsonResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Periode penggajian '{$period}' tidak ditemukan.",
                judul: 'Pencairan Dana Gagal',
                aksi: 'EXECUTIVE_DISBURSE_NOT_FOUND',
                kodeHttp: 404,
            );
        }

        $tervalidasi = $permintaan->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:penggajian,id'],
        ]);

        $daftarId = array_values(array_unique(array_map('intval', $tervalidasi['ids'] ?? [])));

        if ($permintaan->has('password')) {
            $kataSandi = (string) $permintaan->input('password', '');
            if ($kataSandi === '' || ! \Illuminate\Support\Facades\Hash::check($kataSandi, (string) $permintaan->user()?->password)) {
                return $this->responGalatStandar(
                    permintaan: $permintaan,
                    ruteTujuan: 'executive.payroll.index',
                    pesan: 'Kata sandi otorisasi Pimpinan salah. Pencairan dana gaji dibatalkan dan status tidak berubah.',
                    judul: 'Verifikasi 2 Langkah Gagal',
                    aksi: 'EXECUTIVE_DISBURSE_PASSWORD_FAILED',
                    statusKode: 'INVALID_PASSWORD',
                    kodeHttp: 422,
                    daftarGalat: ['password' => ['Kata sandi otorisasi Pimpinan salah.']],
                    parameterRute: ['periode' => $periodeModel->kode],
                );
            }
        }

        if ($periodeModel->status === 'paid') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: "Seluruh gaji supir pada periode {$periodeModel->kode} sudah dicairkan (Paid). Tidak ada baris berstatus Disetujui (ACC) yang menunggu pencairan.",
                judul: 'Pencairan Dana Ditolak',
                aksi: 'EXECUTIVE_DISBURSE_ALREADY_PAID',
                statusKode: 'ALREADY_PAID',
                kodeHttp: 422,
                parameterRute: ['periode' => $periodeModel->kode],
            );
        }

        $kueriSiapCair = Penggajian::query()
            ->where('id_periode_gaji', $periodeModel->id)
            ->where('status', 'approved');

        if ($daftarId !== []) {
            $kueriSiapCair->whereIn('id', $daftarId);
        }

        if (! $kueriSiapCair->exists()) {
            $adaSudahPaid = Penggajian::query()
                ->where('id_periode_gaji', $periodeModel->id)
                ->when($daftarId !== [], fn ($q) => $q->whereIn('id', $daftarId))
                ->where('status', 'paid')
                ->exists();

            $pesan = $adaSudahPaid
                ? "Tidak ada baris gaji supir berstatus Disetujui (ACC) yang siap dicairkan pada periode {$periodeModel->kode} karena seluruh baris terkait sudah dicairkan (Paid)."
                : "Belum ada baris gaji supir berstatus Disetujui (ACC) yang siap dicairkan pada periode {$periodeModel->kode}.";

            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: $pesan,
                judul: 'Pencairan Dana Ditolak',
                aksi: 'EXECUTIVE_DISBURSE_NO_APPROVED_ROWS',
                statusKode: 'NO_APPROVED_ROWS',
                kodeHttp: 422,
                parameterRute: ['periode' => $periodeModel->kode],
            );
        }

        // Baris yang benar-benar berubah menjadi `paid` pada aksi ini; dipakai untuk notifikasi.
        $idPencairan = $kueriSiapCair->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        try {
            $periodeTerbaru = $layananKalkulasi->cairkanPeriode($periodeModel, (int) auth()->id(), $daftarId);
        } catch (ValidationException $galatValidasi) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'executive.payroll.index',
                pesan: collect($galatValidasi->errors())->flatten()->first()
                    ?? 'Belum ada gaji supir berstatus Disetujui (ACC) yang siap dicairkan.',
                judul: 'Pencairan Dana Ditolak',
                aksi: 'EXECUTIVE_DISBURSE_REJECTED',
                daftarGalat: $galatValidasi->errors(),
                kodeHttp: 422,
                parameterRute: ['periode' => $periodeModel->kode],
            );
        }

        // Notifikasi slip gaji ke email (SMTP) & WhatsApp (Fonnte) masing-masing supir.
        // Kegagalan kirim tidak membatalkan pencairan; hanya dicatat di log.
        $ringkasanNotifikasi = app(\App\Services\NotifikasiPencairanGajiService::class)
            ->kirimUntukPenggajian($periodeTerbaru, $idPencairan);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'executive.payroll.index',
            pesan: "Pencairan dana gaji periode {$periodeTerbaru->kode} berhasil diproses dan cicilan kasbon periode berjalan telah ditandai terpotong. Notifikasi terkirim ke {$ringkasanNotifikasi['email_terkirim']} email dan {$ringkasanNotifikasi['wa_terkirim']} WhatsApp supir.",
            judul: 'Pencairan Dana Berhasil',
            aksi: 'EXECUTIVE_DISBURSE_PAYROLL',
            dataTambahan: [
                'periode' => $periodeTerbaru->kode,
                'status' => $periodeTerbaru->status,
                'notifikasi' => $ringkasanNotifikasi,
            ],
            parameterRute: ['periode' => $periodeTerbaru->kode],
        );
    }
}

