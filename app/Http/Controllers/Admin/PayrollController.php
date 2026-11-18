<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\MengaturPaginasiTabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubmitPayrollApprovalRequest;
use App\Http\Requests\Admin\UpdatePayrollDriverRequest;
use App\Models\Penggajian;
use App\Services\PayrollCalculationService;
use App\Services\PayrollDataService;
use App\Services\PeriodeGajiLifecycleService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: REKONSILIASI & PENGHITUNGAN GAJI ADMIN (ADMIN PAYROLL WORKBENCH)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Meja Kerja Juru Hitung & Rekonsiliasi Gaji (Admin HRD/Payroll)".
 * Di sinilah admin mengumpulkan semua surat jalan ritase supir yang sudah terverifikasi,
 * menghitung ulang total tonase muatan, mengurangi uang jalan dan nota sparepart,
 * menghitung bagi hasil komisi supir, memotong iuran BPJS, dan memotong cicilan kasbon.
 * 
 * BATAS WEWENANG OTORISASI (WAJIB DIPATUHI):
 * 1. Admin/HRD DILARANG memberikan persetujuan ACC gaji!
 * 2. Admin HANYA bertugas menghitung dan "Mengajukan Draf" (status 'draft' -> 'pending').
 * 3. Hak veto mutlak untuk mengesahkan (ACC / 'approved') sepenuhnya milik Pimpinan/Direktur.
 * ============================================================================
 */
class PayrollController extends Controller
{
    // Menggunakan trait pembantu paginasi tabel dan format respon notifikasi toast standar
    use MengaturPaginasiTabel, ResponInteraksiStandar;

    /**
     * Menampilkan Lembar Kerja Penggajian Periode Berjalan
     * 
     * Alur Kerja Bernomor:
     * (1) Cari tahu buku periode mana yang dibuka admin (default ke periode terbaru).
     * (2) Tangkap kata kunci pencarian dan filter armada dari URL.
     * (3) Ambil ringkasan total uang gaji dan daftar baris gaji supir terpaginasi.
     * (4) Buka tampilan antarmuka 'admin.payroll.index'.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        // (1) Tentukan periode yang aktif
        $kodePeriode = $request->query('periode');
        $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode(is_string($kodePeriode) ? $kodePeriode : null)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        // (2) Baca konfigurasi filter dan paginasi
        $perHalaman = $this->ambilJumlahBarisPerHalaman($request);
        $filter = [
            'cari'  => $this->ambilKataKunciPencarian($request),
            'fleet' => $this->ambilFilterTeks($request, 'fleet'),
        ];

        // (3) & (4) Render tampilan Blade Payroll
        return view('admin.payroll.index', [
            'period'         => $layananDataPayroll->formatPeriode($periode),
            'summary'        => $layananDataPayroll->ringkasanPayroll($periode),
            'rows'           => $layananDataPayroll->barisPayrollTerpaginasi($periode, $filter, $perHalaman),
            'periodOptions'  => $layananDataPayroll->daftarOpsiPeriode(),
            'filter'         => $filter,
            'perPage'        => $perHalaman,
        ]);
    }

    /**
     * Membuka Buku Periode Penggajian Baru (Siklus Buka/Tutup Buku Manual)
     *
     * Alur Kerja Bernomor:
     * (1) Tolak bila masih ada periode berjalan (maksimal satu periode terbuka).
     * (2) Periode baru dimulai H+1 dari tanggal tutup buku periode terakhir.
     * (3) tanggal_selesai dibiarkan NULL (periode berjalan) sampai Admin tutup buku.
     * (4) Berikan notifikasi toast sukses bahwa periode siap dipakai.
     */
    public function buatPeriodeBaru(
        Request $permintaan,
        PeriodeGajiLifecycleService $layananSiklusPeriode,
    ): RedirectResponse|JsonResponse {
        // (1) & (2) Buat periode baru; service menolak bila masih ada yang berjalan.
        $periode = $layananSiklusPeriode->bukaPeriodeBaru();

        // (4) Kirim respon sukses standar
        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.payroll.index',
            pesan: "Periode {$periode->kode} dibuka mulai {$periode->tanggal_mulai->toDateString()}. Tutup buku saat tanggal gajian tiba.",
            judul: 'Periode Baru Dibuka',
            aksi: 'CREATE_PERIODE_GAJI',
            dataTambahan: ['kode' => $periode->kode, 'id' => $periode->id],
            parameterRute: ['periode' => $periode->kode],
        );
    }

    /**
     * Tutup Buku & Ajukan ke Pimpinan (Satu Tombol)
     *
     * Alur Kerja Bernomor:
     * (1) Cari periode yang diminta.
     * (2) Tolak (422) bila masih ada surat jalan belum terverifikasi.
     * (3) Hitung ulang gaji periode (rekonsiliasi).
     * (4) Tutup buku: tanggal_selesai = hari ini, kode final = bulan tutup buku,
     *     lalu otomatis buka periode berikutnya.
     * (5) Ubah status periode menjadi 'pending' (menunggu ACC Pimpinan).
     */
    public function tutupBukuDanAjukan(
        Request $permintaan,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasiPayroll,
        PeriodeGajiLifecycleService $layananSiklusPeriode,
    ): RedirectResponse|JsonResponse {
        // (1) Periode yang diminta
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeBerjalan()
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return ResponInteraksiStandar::gagal(
                request: $permintaan,
                judul: 'Periode Tidak Ditemukan',
                pesan: "Periode penggajian \"{$period}\" tidak ditemukan.",
                aksi: 'tutup-buku',
                kodeHttp: 404,
            );
        }

        // (2) Seluruh surat jalan wajib sudah terverifikasi.
        $jumlahSjBelumVerifikasi = $periodeModel->ritase()
            ->whereIn('status', ['draft', 'terbit', 'sedang_dikirim', 'menunggu_verifikasi'])
            ->count();

        if ($jumlahSjBelumVerifikasi > 0) {
            return ResponInteraksiStandar::gagal(
                request: $permintaan,
                judul: 'Tutup Buku Ditolak',
                pesan: "Masih ada {$jumlahSjBelumVerifikasi} surat jalan pada periode {$periodeModel->kode} yang belum diverifikasi. Selesaikan verifikasi seluruh surat jalan terlebih dahulu.",
                aksi: 'tutup-buku',
                statusKode: 'UNVERIFIED_TRIPS',
                kodeHttp: 422,
                redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
            );
        }

        // (3) Hitung ulang agar angka yang diajukan adalah angka final.
        $layananKalkulasiPayroll->rekonsiliasiPeriode($periodeModel);

        // (4) Tutup buku + buka periode berikutnya.
        try {
            $periodeFinal = $layananSiklusPeriode->tutupBuku($periodeModel);
        } catch (ValidationException $galat) {
            return ResponInteraksiStandar::gagal(
                request: $permintaan,
                judul: 'Tutup Buku Ditolak',
                pesan: (string) (collect($galat->errors())->flatten()->first() ?? 'Tutup buku tidak dapat diproses.'),
                aksi: 'tutup-buku',
                kodeHttp: 422,
                redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
            );
        }

        // (5) Ajukan ke Pimpinan.
        $periodeFinal->update([
            'status' => 'pending',
            'catatan_penolakan' => null,
        ]);

        $jumlahBaris = $periodeFinal->penggajian()->count();

        return ResponInteraksiStandar::sukses(
            request: $permintaan,
            judul: 'Tutup Buku Selesai & Diajukan',
            pesan: "Periode {$periodeFinal->kode} ditutup buku per {$periodeFinal->tanggal_selesai->toDateString()} "
                ."({$jumlahBaris} supir) dan diajukan ke Pimpinan. Periode berikutnya sudah dibuka otomatis.",
            aksi: 'tutup-buku',
            data: [
                'kode' => $periodeFinal->kode,
                'status' => $periodeFinal->status,
                'tanggal_selesai' => $periodeFinal->tanggal_selesai?->toDateString(),
            ],
            redirectUrl: route('admin.payroll.index', ['periode' => $periodeFinal->kode]),
        );
    }

    /**
     * Buka Kembali Periode yang sudah ditutup (untuk surat jalan susulan).
     */
    public function bukaKembaliPeriode(
        Request $permintaan,
        string $period,
        PayrollDataService $layananDataPayroll,
        PeriodeGajiLifecycleService $layananSiklusPeriode,
    ): RedirectResponse|JsonResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period);

        if ($periodeModel === null) {
            return ResponInteraksiStandar::gagal(
                request: $permintaan,
                judul: 'Periode Tidak Ditemukan',
                pesan: "Periode penggajian \"{$period}\" tidak ditemukan.",
                aksi: 'buka-kembali',
                kodeHttp: 404,
            );
        }

        try {
            $periodeDibuka = $layananSiklusPeriode->bukaKembaliPeriode($periodeModel);
        } catch (ValidationException $galat) {
            return ResponInteraksiStandar::gagal(
                request: $permintaan,
                judul: 'Periode Tidak Dapat Dibuka Kembali',
                pesan: (string) (collect($galat->errors())->flatten()->first() ?? 'Periode tidak dapat dibuka kembali.'),
                aksi: 'buka-kembali',
                kodeHttp: 422,
                redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
            );
        }

        return ResponInteraksiStandar::sukses(
            request: $permintaan,
            judul: 'Periode Dibuka Kembali',
            pesan: "Periode {$periodeDibuka->kode} kembali berjalan sejak {$periodeDibuka->tanggal_mulai->toDateString()}. Jangan lupa tutup buku lagi setelah selesai.",
            aksi: 'buka-kembali',
            data: ['kode' => $periodeDibuka->kode],
            redirectUrl: route('admin.payroll.index', ['periode' => $periodeDibuka->kode]),
        );
    }


    public function calculate(
        Request $request,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasiPayroll
    ): JsonResponse|RedirectResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Periode Tidak Ditemukan',
                pesan: "Periode penggajian \"{$period}\" tidak ditemukan di database.",
                aksi: 'calculate',
                kodeHttp: 404,
            );
        }

        $ringkasan = $layananKalkulasiPayroll->rekonsiliasiPeriode($periodeModel);
        $totalBersihLabel = 'Rp '.number_format($ringkasan['total_gaji_bersih'], 0, ',', '.');

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Kalkulasi Ulang Gaji Selesai',
            pesan: "Rekonsiliasi periode {$periodeModel->kode} berhasil ({$ringkasan['jumlah_supir']} supir, {$ringkasan['total_rit']} rit, total gaji bersih {$totalBersihLabel}).",
            aksi: 'calculate',
            data: $ringkasan,
            redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
        );
    }

    public function approval(
        SubmitPayrollApprovalRequest $request,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasiPayroll
    ): JsonResponse|RedirectResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Periode Tidak Ditemukan',
                pesan: "Periode penggajian \"{$period}\" tidak ditemukan.",
                aksi: 'approval',
                kodeHttp: 404,
            );
        }

        $tervalidasi = $request->validated();
        $alasan = trim((string) ($tervalidasi['alasan_penolakan'] ?? ''));

        // Sesuai ACTIVITY_DIAGRAM.drawio: ACC hanya milik Pimpinan/Direksi.
        // Admin/HRD hanya boleh menarik pengajuan (kembali ke draft).
        $periodeDiperbarui = $layananKalkulasiPayroll->prosesPersetujuanPeriode($periodeModel, 'reject');

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Catatan Revisi Dikirim',
            pesan: "Penggajian periode {$periodeDiperbarui->kode} dikembalikan ke status Draf untuk revisi: \"{$alasan}\".",
            aksi: 'reject',
            data: ['kode' => $periodeDiperbarui->kode, 'status' => $periodeDiperbarui->status],
            redirectUrl: route('admin.payroll.index', ['periode' => $periodeDiperbarui->kode]),
        );
    }

    public function updateDriver(
        UpdatePayrollDriverRequest $request,
        string $period,
        Penggajian $penggajian,
        PayrollCalculationService $layananKalkulasiPayroll
    ): JsonResponse|RedirectResponse {
        $penggajian->loadMissing(['karyawan', 'periodeGaji', 'truk']);
        $kodePeriode = $penggajian->periodeGaji?->kode ?? $period;
        $namaSupir = $penggajian->karyawan?->nama_lengkap ?? 'Supir';
        $nopol = $penggajian->truk?->nomor_polisi ?? '-';

        $diperbarui = $layananKalkulasiPayroll->perbaruiKomponenSupir($penggajian, $request->validated());
        $gajiBersihLabel = 'Rp '.number_format((int) $diperbarui->gaji_bersih, 0, ',', '.');

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Komponen Gaji Supir Diperbarui',
            pesan: "Rincian gaji supir \"{$namaSupir}\" ({$nopol}) pada periode {$kodePeriode} berhasil disimpan (Net Pay: {$gajiBersihLabel}).",
            aksi: 'update',
            data: [
                'id_penggajian' => $diperbarui->id,
                'gaji_bersih' => (int) $diperbarui->gaji_bersih,
                'gaji_komisi' => (int) $diperbarui->gaji_komisi,
                'hasil_bersih_armada' => (int) $diperbarui->hasil_bersih_armada,
            ],
            redirectUrl: route('admin.payroll.index', ['periode' => $kodePeriode]),
        );
    }

    public function destroyDriver(
        Request $request,
        string $period,
        Penggajian $penggajian
    ): JsonResponse|RedirectResponse {
        $penggajian->loadMissing(['karyawan', 'periodeGaji', 'truk']);
        $periodeModel = $penggajian->periodeGaji;
        $kodePeriode = $periodeModel?->kode ?? $period;
        $namaSupir = $penggajian->karyawan?->nama_lengkap ?? 'Supir';
        $nopol = $penggajian->truk?->nomor_polisi ?? '-';

        if ($periodeModel !== null && in_array($periodeModel->status, ['approved', 'paid'], true)) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Periode Sudah Terkunci',
                pesan: "Baris gaji \"{$namaSupir}\" ({$nopol}) tidak dapat dikeluarkan karena periode {$kodePeriode} sudah berstatus disetujui/dibayar.",
                aksi: 'delete',
                kodeHttp: 422,
            );
        }

        $jumlahRitase = $periodeModel
            ? $periodeModel->ritase()->where('id_karyawan', $penggajian->id_karyawan)->count()
            : 0;

        if ($jumlahRitase > 0) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Supir Memiliki Ritase Aktif',
                pesan: "Supir \"{$namaSupir}\" ({$nopol}) tidak dapat dihapus dari periode {$kodePeriode} karena masih mengikat {$jumlahRitase} surat jalan ritase terverifikasi.",
                aksi: 'delete',
                kodeHttp: 422,
            );
        }

        $penggajian->delete();

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Baris Gaji Dikeluarkan',
            pesan: "Supir \"{$namaSupir}\" ({$nopol}) berhasil dikeluarkan dari perhitungan gaji periode {$kodePeriode}.",
            aksi: 'delete',
            redirectUrl: route('admin.payroll.index', ['periode' => $kodePeriode]),
        );
    }

    public function submit(Request $request, string $period, PayrollDataService $layananDataPayroll): JsonResponse|RedirectResponse
    {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Periode Tidak Ditemukan',
                pesan: "Periode penggajian \"{$period}\" tidak ditemukan.",
                aksi: 'submit',
                kodeHttp: 404,
            );
        }

        $jumlahSjBelumVerifikasi = $periodeModel->ritase()
            ->whereIn('status', ['draft', 'terbit', 'sedang_dikirim', 'menunggu_verifikasi'])
            ->count();

        if ($jumlahSjBelumVerifikasi > 0) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Pengajuan Payroll Ditolak',
                pesan: "Masih ada {$jumlahSjBelumVerifikasi} surat jalan pada periode {$periodeModel->kode} yang belum diverifikasi (Draf / Terbit / Sedang Dikirim / Menunggu Verifikasi). Selesaikan verifikasi seluruh surat jalan terlebih dahulu.",
                aksi: 'submit',
                statusKode: 'UNVERIFIED_TRIPS',
                kodeHttp: 422,
                redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
            );
        }

        $jumlahBarisGaji = $periodeModel->penggajian()->count();

        if ($jumlahBarisGaji === 0) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Data Gaji Belum Dihitung',
                pesan: "Periode {$periodeModel->kode} belum memiliki baris gaji supir. Jalankan Hitung Gaji terlebih dahulu sebelum mengajukan ke Pimpinan.",
                aksi: 'submit',
                statusKode: 'EMPTY_PAYROLL',
                kodeHttp: 422,
                redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
            );
        }

        $periodeModel->update([
            'status' => 'pending',
            'catatan_penolakan' => null,
        ]);

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Pengajuan Payroll Dikirim',
            pesan: "Penggajian periode {$periodeModel->kode} berhasil diajukan ke Pimpinan untuk persetujuan.",
            aksi: 'submit',
            redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
        );
    }

    public function import(Request $request, string $period): RedirectResponse
    {
        return redirect()->route('admin.payroll.index', ['periode' => $period]);
    }

    public function export(Request $request, string $period): RedirectResponse
    {
        return redirect()->route('admin.reports.periode.export', ['periode' => $period]);
    }
}
