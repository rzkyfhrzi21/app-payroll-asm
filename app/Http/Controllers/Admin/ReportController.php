<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportMigrasiPayrollRequest;
use App\Models\Penggajian;
use App\Services\ExcelPayrollMigrationService;
use App\Services\PayrollDataService;
use App\Services\SlipGajiExcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ============================================================================
 * CONTROLLER: LEMARI ARSIP & LAPORAN PENGGAJIAN (PAYROLL REPORT CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Lemari Arsip Kaca & Mesin Cetak Dokumen Keuangan Kantor".
 * Setiap kali periode penggajian bulanan telah selesai dihitung dan disetujui,
 * laporannya akan disimpan rapi di lemari ini. Dari sini, staf admin atau auditor
 * dapat melihat kembali data historis bulan-bulan yang lalu, membuka rincian
 * slip gaji supir, atau mengekspor berkas ke format file Excel / CSV
 * untuk diserahkan ke bagian perpajakan atau direksi.
 * ============================================================================
 */
class ReportController extends Controller
{
    /**
     * Menampilkan Daftar Arsip Laporan Seluruh Periode
     * 
     * Alur Kerja Bernomor:
     * (1) Ambil data arsip periode yang sudah pernah dibuat dari PayrollDataService.
     * (2) Tangkap filter bulan, tahun, status, dan pencarian dari URL.
     * (3) Buka tampilan antarmuka 'admin.reports.index' dengan data arsip lengkap.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $dataArsip = $layananDataPayroll->arsipLaporanPeriode();

        $filterBulan = is_string($request->query('bulan')) && $request->query('bulan') !== ''
            ? $request->query('bulan')
            : 'all';
        $filterTahun = is_string($request->query('tahun')) && $request->query('tahun') !== ''
            ? $request->query('tahun')
            : 'all';
        $filterStatus = is_string($request->query('status')) && $request->query('status') !== ''
            ? $request->query('status')
            : 'all';
        $kataKunci = is_string($request->query('cari'))
            ? trim($request->query('cari'))
            : '';

        return view('admin.reports.index', [
            'ringkasan' => $dataArsip['ringkasan'],
            'opsiTahun' => $dataArsip['opsi_tahun'],
            'barisPeriode' => $dataArsip['baris_periode'],
            'opsiPeriode' => $layananDataPayroll->daftarOpsiPeriode(),
            'filterAwal' => [
                'bulan' => $filterBulan,
                'tahun' => $filterTahun,
                'status' => $filterStatus,
                'cari' => $kataKunci,
            ],
        ]);
    }

    /**
     * Menampilkan Rincian Detail Laporan Satu Periode Tertentu
     * 
     * Alur Kerja Bernomor:
     * (1) Cari periode yang diminta berdasarkan kode bulannya (misal: "Jan-26").
     * (2) Pastikan periode tersebut benar-benar ada di database (jika tidak, tampilkan error 404).
     * (3) Ambil rincian lengkap per supir (ritase, tonase, uang jalan, komisi, dan gaji bersih).
     * (4) Buka tampilan 'admin.reports.show'.
     */
    public function show(Request $request, string $periode, PayrollDataService $layananDataPayroll): View
    {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($periode)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        abort_if($periodeModel === null, 404, 'Periode penggajian tidak ditemukan.');

        $dataRincian = $layananDataPayroll->rincianLaporanPeriode($periodeModel);

        return view('admin.reports.show', [
            'periode' => $dataRincian['periode'],
            'ringkasan' => $dataRincian['ringkasan'],
            'daftarSupir' => $dataRincian['daftar_supir'],
            'opsiPeriode' => $layananDataPayroll->daftarOpsiPeriode(),
            'cariAwal' => is_string($request->query('cari')) ? trim($request->query('cari')) : '',
        ]);
    }

    public function exportArsip(PayrollDataService $layananDataPayroll): StreamedResponse
    {
        $dataArsip = $layananDataPayroll->arsipLaporanPeriode();
        $barisPeriode = $dataArsip['baris_periode'];
        $namaBerkas = 'arsip-periode-penggajian-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($barisPeriode): void {
            $keluaran = fopen('php://output', 'w');
            if ($keluaran === false) {
                return;
            }

            fwrite($keluaran, "\xEF\xBB\xBF");
            fputcsv($keluaran, [
                'No',
                'Kode Periode',
                'Rentang Siklus',
                'Cut-Off',
                'Jumlah Supir',
                'Total Rit',
                'Total Tonase (Ton)',
                'Total Angkutan Kotor (IDR)',
                'Total Uang Jalan (IDR)',
                'Total Potongan Kasbon (IDR)',
                'Total Net Pay (IDR)',
                'Status ACC',
                'Tanggal ACC',
            ]);

            foreach ($barisPeriode as $indeks => $baris) {
                fputcsv($keluaran, [
                    $indeks + 1,
                    $baris['kode'],
                    $baris['rentang_tanggal'],
                    $baris['tanggal_cut_off'],
                    $baris['jumlah_supir'],
                    $baris['total_rit'],
                    $baris['total_tonase_label'],
                    $baris['total_angkutan_kotor'],
                    $baris['total_uang_jalan'],
                    $baris['total_kasbon'],
                    $baris['total_net_pay'],
                    $baris['status_label'],
                    $baris['tanggal_acc'],
                ]);
            }

            fclose($keluaran);
        }, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Ekspor gaji per supir per periode dalam format Slip Gaji .xlsx.
     *
     * Satu berkas berisi satu sheet per supir, mengikuti struktur berkas acuan
     * `dokumen/Slip Gaji - KUWAT SLAMET - Jan-26.xlsx` tanpa sheet panduan.
     */
    public function exportPeriode(
        string $periode,
        PayrollDataService $layananDataPayroll,
        SlipGajiExcelService $layananSlipExcel,
    ): BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($periode)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        abort_if($periodeModel === null, 404, 'Periode penggajian tidak ditemukan.');

        $daftarPenggajian = Penggajian::query()
            ->with(['karyawan', 'truk'])
            ->where('id_periode_gaji', $periodeModel->id)
            ->orderBy('id')
            ->get();

        if ($daftarPenggajian->isEmpty()) {
            abort(404, 'Periode ini belum memiliki baris penggajian sehingga slip tidak dapat diekspor.');
        }

        $lokasiBerkas = $layananSlipExcel->bangunBerkas($periodeModel, $daftarPenggajian);
        $namaBerkas = 'slip-gaji-'.strtolower($periodeModel->kode).'-'.now()->format('Ymd-His').'.xlsx';

        return response()
            ->download($lokasiBerkas, $namaBerkas, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function cetakRekapPeriode(string $periode, PayrollDataService $layananDataPayroll): View
    {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($periode)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        abort_if($periodeModel === null, 404, 'Periode penggajian tidak ditemukan.');

        $dataRincian = $layananDataPayroll->rincianLaporanPeriode($periodeModel);

        return view('admin.reports.print-periode', [
            'periode' => $dataRincian['periode'],
            'ringkasan' => $dataRincian['ringkasan'],
            'daftarSupir' => $dataRincian['daftar_supir'],
        ]);
    }

    public function cetakSlipSupir(string $periode, Penggajian $penggajian, PayrollDataService $layananDataPayroll): View
    {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($periode)
            ?? $penggajian->periodeGaji;

        abort_if($periodeModel === null, 404, 'Periode penggajian tidak ditemukan.');

        $dataRincian = $layananDataPayroll->rincianLaporanPeriode($periodeModel);
        $dataSupir = collect($dataRincian['daftar_supir'])
            ->firstWhere('id_penggajian', $penggajian->id)
            ?? collect($dataRincian['daftar_supir'])->first();

        abort_if($dataSupir === null, 404, 'Data slip gaji supir tidak ditemukan.');

        return view('admin.reports.slip', [
            'periode' => $dataRincian['periode'],
            'supir' => $dataSupir,
        ]);
    }

    public function imporMigrasi(
        ImportMigrasiPayrollRequest $request,
        ExcelPayrollMigrationService $layananMigrasi
    ): RedirectResponse {
        $dataValid = $request->validated();

        try {
            $hasilImpor = $layananMigrasi->imporBerkasMigrasi(
                berkas: $request->file('berkas_excel'),
                targetPeriode: (string) $dataValid['target_periode'],
                statusPencairan: (string) $dataValid['status_pencairan'],
                kodePeriodeBaru: $dataValid['kode_periode_baru'] ?? null,
                tanggalPencairan: $dataValid['tanggal_pencairan'] ?? null,
            );
        } catch (\Throwable $galat) {
            return back()
                ->withInput()
                ->withErrors(['berkas_excel' => $galat->getMessage()]);
        }

        $labelStatusDana = $hasilImpor['status_pencairan'] === 'sudah_cair'
            ? 'SUDAH DICAIRKAN (Lunas / Terbayar)'
            : 'BELUM DICAIRKAN (Draf / Menunggu ACC)';
        $daftarPeriodeTeks = implode(', ', $hasilImpor['daftar_periode']);
        $totalGajiFormatted = 'Rp '.number_format($hasilImpor['total_gaji_bersih'], 0, ',', '.');
        $pesanImpor = "Berhasil mengimpor {$hasilImpor['jumlah_slip']} slip gaji supir ({$hasilImpor['jumlah_ritase']} baris ritase) untuk periode [{$daftarPeriodeTeks}] dengan status dana: {$labelStatusDana} — Total Gaji Bersih: {$totalGajiFormatted}.";

        return back()
            ->with('status', $pesanImpor)
            ->with('toast', [
                'success' => true,
                'type' => 'success',
                'title' => 'Sinkronisasi Migrasi Excel Berhasil',
                'action' => 'IMPORT_MIGRASI_EXCEL',
                'status' => 'COMPLETED',
                'message' => $pesanImpor,
                'data' => $hasilImpor,
                'timestamp' => now()->toIso8601String(),
            ]);
    }

    public function unggahPreviewMigrasi(
        ImportMigrasiPayrollRequest $request,
        ExcelPayrollMigrationService $layananMigrasi
    ): RedirectResponse {
        $dataValid = $request->validated();

        try {
            $staging = $layananMigrasi->analisisStagingMigrasi(
                berkas: $request->file('berkas_excel'),
                targetPeriode: (string) $dataValid['target_periode'],
                statusPencairan: (string) $dataValid['status_pencairan'],
                kodePeriodeBaru: $dataValid['kode_periode_baru'] ?? null,
                tanggalPencairan: $dataValid['tanggal_pencairan'] ?? null,
            );
        } catch (\Throwable $galat) {
            return back()
                ->withInput()
                ->withErrors(['berkas_excel' => $galat->getMessage()]);
        }

        return redirect()->route('admin.reports.migrasi.tampil-preview', ['token' => $staging['token']]);
    }

    public function tampilPreviewMigrasi(
        string $token,
        ExcelPayrollMigrationService $layananMigrasi
    ): View|RedirectResponse {
        $staging = $layananMigrasi->ambilDataStaging($token);

        if ($staging === null) {
            return redirect()
                ->route('admin.reports.index')
                ->with('galat', 'Sesi pratinjau migrasi telah kedaluwarsa atau tidak ditemukan. Silakan unggah berkas kembali.');
        }

        $daftarJenisTruk = \App\Models\JenisTruk::query()->where('status', 'aktif')->get();
        $daftarSupirDatabase = \App\Models\Karyawan::query()->with('pengguna')->where('status', 'aktif')->orderBy('nama_lengkap')->get();
        $daftarTrukDatabase = \App\Models\Truk::query()->with('jenisTruk')->where('status', 'aktif')->orderBy('nomor_polisi')->get();

        return view('admin.reports.preview-migrasi', [
            'staging' => $staging,
            'daftarJenisTruk' => $daftarJenisTruk,
            'daftarSupirDatabase' => $daftarSupirDatabase,
            'daftarTrukDatabase' => $daftarTrukDatabase,
            'token' => $token,
        ]);
    }

    public function eksekusiPreviewMigrasi(
        Request $request,
        ExcelPayrollMigrationService $layananMigrasi
    ): RedirectResponse {
        $token = (string) $request->input('token');
        $keputusanSupir = (array) $request->input('supir', []);
        $keputusanUnit = (array) $request->input('unit', []);
        $keputusanRute = (array) $request->input('rute', []);

        try {
            $hasilImpor = $layananMigrasi->eksekusiStagingMigrasi(
                token: $token,
                keputusanSupir: $keputusanSupir,
                keputusanUnit: $keputusanUnit,
                keputusanRute: $keputusanRute,
            );
        } catch (ValidationException $galatValidasi) {
            return back()
                ->withInput()
                ->withErrors($galatValidasi->errors());
        } catch (\Throwable $galat) {
            return back()
                ->withInput()
                ->with('galat', $galat->getMessage());
        }

        $labelStatusDana = $hasilImpor['status_pencairan'] === 'sudah_cair'
            ? 'SUDAH DICAIRKAN (Lunas / Terbayar)'
            : 'BELUM DICAIRKAN (Draf / Menunggu ACC)';
        $daftarPeriodeTeks = implode(', ', $hasilImpor['daftar_periode']);
        $totalGajiFormatted = 'Rp '.number_format($hasilImpor['total_gaji_bersih'], 0, ',', '.');
        $pesanImpor = "Berhasil mengimpor {$hasilImpor['jumlah_slip']} slip gaji supir ({$hasilImpor['jumlah_ritase']} baris ritase, {$hasilImpor['jumlah_dilewati']} dilewati) untuk periode [{$daftarPeriodeTeks}] dengan status dana: {$labelStatusDana} — Total Gaji Bersih: {$totalGajiFormatted}.";

        return redirect()
            ->route('admin.reports.index')
            ->with('status', $pesanImpor)
            ->with('toast', [
                'success' => true,
                'type' => 'success',
                'title' => 'Migrasi Data Excel Berhasil',
                'action' => 'IMPORT_MIGRASI_EXCEL',
                'status' => 'COMPLETED',
                'message' => $pesanImpor,
                'data' => $hasilImpor,
                'timestamp' => now()->toIso8601String(),
            ]);
    }

    public function eksekusiAsyncMigrasi(
        Request $request,
        ExcelPayrollMigrationService $layananMigrasi
    ): \Illuminate\Http\JsonResponse {
        // Impor ribuan baris ritase bisa memakan waktu lebih dari batas 30 detik PHP.
        // Tanpa ini, request terputus di tengah transaksi dan bilah progres tidak pernah tuntas.
        @set_time_limit(0);
        @ignore_user_abort(true);

        $token = (string) $request->input('token');
        $keputusanSupir = (array) $request->input('supir', []);
        $keputusanUnit = (array) $request->input('unit', []);
        $keputusanRute = (array) $request->input('rute', []);

        try {
            $hasilImpor = $layananMigrasi->eksekusiStagingMigrasi(
                token: $token,
                keputusanSupir: $keputusanSupir,
                keputusanUnit: $keputusanUnit,
                keputusanRute: $keputusanRute,
            );
        } catch (ValidationException $galatValidasi) {
            $pesan = collect($galatValidasi->errors())->flatten()->first() ?? 'Validasi impor gagal.';
            $layananMigrasi->catatProgress($token, 0, $pesan, true);

            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'message' => $pesan,
                'errors' => $galatValidasi->errors(),
            ], 422);
        } catch (\Throwable $galat) {
            $layananMigrasi->catatProgress($token, 0, $galat->getMessage(), true);

            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'message' => $galat->getMessage(),
            ], 500);
        }

        $labelStatusDana = $hasilImpor['status_pencairan'] === 'sudah_cair'
            ? 'SUDAH DICAIRKAN (Lunas / Terbayar)'
            : 'BELUM DICAIRKAN (Draf / Menunggu ACC)';
        $daftarPeriodeTeks = implode(', ', $hasilImpor['daftar_periode']);
        $totalGajiFormatted = 'Rp '.number_format($hasilImpor['total_gaji_bersih'], 0, ',', '.');
        $pesanImpor = "Berhasil mengimpor {$hasilImpor['jumlah_slip']} slip gaji supir ({$hasilImpor['jumlah_ritase']} baris ritase, {$hasilImpor['jumlah_dilewati']} dilewati) untuk periode [{$daftarPeriodeTeks}] dengan status dana: {$labelStatusDana} — Total Gaji Bersih: {$totalGajiFormatted}.";

        // Toast sukses TIDAK di-flash ke session di sini. Endpoint ini dipanggil lewat
        // AJAX dan halaman pratinjau sudah menampilkan toast sebelum redirect, sehingga
        // flash akan memunculkan notifikasi kedua yang sama setelah pindah halaman.
        return response()->json([
            'success' => true,
            'status' => 'selesai',
            'persen' => 100,
            'message' => $pesanImpor,
            'data' => $hasilImpor,
            'redirect' => route('admin.reports.index'),
        ]);
    }

    public function statusProgressMigrasi(
        string $token,
        ExcelPayrollMigrationService $layananMigrasi
    ): \Illuminate\Http\JsonResponse {
        return response()->json($layananMigrasi->ambilProgress($token));
    }

    public function tambahUnitAjax(Request $request): \Illuminate\Http\JsonResponse
    {
        $tervalidasi = $request->validate([
            'nomor_polisi' => ['required', 'string', 'max:20', 'unique:truk,nomor_polisi'],
            'merek_model' => ['required', 'string', 'max:100'],
            'id_jenis_truk' => ['required', 'exists:jenis_truk,id'],
        ]);

        $truk = \App\Models\Truk::create([
            'nomor_polisi' => strtoupper(trim($tervalidasi['nomor_polisi'])),
            'merek_model' => strtoupper(trim($tervalidasi['merek_model'])),
            'id_jenis_truk' => (int) $tervalidasi['id_jenis_truk'],
            'status' => 'aktif',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Unit armada {$truk->nomor_polisi} berhasil didaftarkan.",
            'truk' => [
                'id' => $truk->id,
                'nomor_polisi' => $truk->nomor_polisi,
                'merek_model' => $truk->merek_model,
                'jenis_truk' => $truk->jenisTruk?->nama ?? 'TRONTON',
            ],
        ]);
    }

    public function eksporMigrasi(
        Request $request,
        ExcelPayrollMigrationService $layananMigrasi,
        PayrollDataService $layananDataPayroll,
        SlipGajiExcelService $layananSlipExcel,
    ): BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse {
        $jenisFormat = (string) $request->query('format', 'multi_sheet');
        $kodePeriode = is_string($request->query('periode')) ? trim($request->query('periode')) : 'all';

        // Format rincian lengkap -> .xlsx satu sheet per supir (tanpa sheet panduan).
        if ($jenisFormat === 'multi_sheet' && $kodePeriode !== '' && $kodePeriode !== 'all') {
            return $this->exportPeriode($kodePeriode, $layananDataPayroll, $layananSlipExcel);
        }

        if ($jenisFormat === 'single_summary') {
            if ($kodePeriode !== '' && $kodePeriode !== 'all') {
                return $this->exportPeriode($kodePeriode, $layananDataPayroll, $layananSlipExcel);
            }

            return $this->exportArsip($layananDataPayroll);
        }

        // Multi-periode tetap memakai CSV agar bisa diimpor ulang lintas periode.
        return $layananMigrasi->eksporFormatMigrasi($kodePeriode);
    }

    public function unduhTemplateMigrasi(ExcelPayrollMigrationService $layananMigrasi): \Symfony\Component\HttpFoundation\BinaryFileResponse|StreamedResponse
    {
        $berkasXlsx = base_path('dokumen/CONTOH FORMAT Slip Gaji.xlsx');
        if (is_file($berkasXlsx)) {
            return response()->download($berkasXlsx, 'Template_Kosong_Slip_Gaji.xlsx');
        }

        return $layananMigrasi->unduhTemplateKosong();
    }

    public function unduhDummyMigrasi(ExcelPayrollMigrationService $layananMigrasi): \Symfony\Component\HttpFoundation\BinaryFileResponse|StreamedResponse
    {
        // Berkas contoh HARUS berisi data supaya bisa langsung diuji unggah,
        // berbeda dari template kosong pada unduhTemplateMigrasi().
        $berkasXlsx = base_path('dokumen/DUMMY Slip Gaji.xlsx');
        if (is_file($berkasXlsx)) {
            return response()->download($berkasXlsx, 'CONTOH_FORMAT_Slip_Gaji.xlsx');
        }

        return $layananMigrasi->unduhBerkasDummyMigrasi();
    }
}
