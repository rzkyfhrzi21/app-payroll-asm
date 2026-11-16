<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportMigrasiPayrollRequest;
use App\Models\Penggajian;
use App\Services\ExcelPayrollMigrationService;
use App\Services\PayrollDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
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

    public function exportPeriode(string $periode, PayrollDataService $layananDataPayroll): StreamedResponse
    {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($periode)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        abort_if($periodeModel === null, 404, 'Periode penggajian tidak ditemukan.');

        $dataRincian = $layananDataPayroll->rincianLaporanPeriode($periodeModel);
        $daftarSupir = $dataRincian['daftar_supir'];
        $namaBerkas = 'rekap-gaji-supir-'.strtolower($periodeModel->kode).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($periodeModel, $daftarSupir): void {
            $keluaran = fopen('php://output', 'w');
            if ($keluaran === false) {
                return;
            }

            fwrite($keluaran, "\xEF\xBB\xBF");
            fputcsv($keluaran, [
                'No',
                'Periode',
                'NIP Supir',
                'Nama Supir',
                'Nopol Truk',
                'Model Truk',
                'Total Rit',
                'Total Tonase (Ton)',
                'Angkutan Kotor (IDR)',
                'Uang Jalan (IDR)',
                'Biaya Sparepart (IDR)',
                'Hasil Bersih Armada (IDR)',
                'Komisi Supir 10% (IDR)',
                'Potongan BPJS (IDR)',
                'Potongan Kasbon (IDR)',
                'Gaji Bersih / Net Pay (IDR)',
                'Bank',
                'No. Rekening',
            ]);

            foreach ($daftarSupir as $indeks => $supir) {
                fputcsv($keluaran, [
                    $indeks + 1,
                    $periodeModel->kode,
                    $supir['nik'],
                    $supir['nama'],
                    $supir['nopol'],
                    $supir['model_truk'],
                    $supir['total_rit'],
                    $supir['total_tonase_label'],
                    $supir['angkutan_kotor'],
                    $supir['uang_jalan'],
                    $supir['biaya_sparepart'],
                    $supir['hasil_bersih_armada'],
                    $supir['gaji_komisi'],
                    $supir['potongan_bpjs'],
                    $supir['potongan_kasbon'],
                    $supir['gaji_bersih'],
                    $supir['bank'],
                    $supir['rekening'],
                ]);
            }

            fclose($keluaran);
        }, $namaBerkas, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    public function eksporMigrasi(
        Request $request,
        ExcelPayrollMigrationService $layananMigrasi,
        PayrollDataService $layananDataPayroll
    ): StreamedResponse {
        $jenisFormat = (string) $request->query('format', 'multi_sheet');
        $kodePeriode = is_string($request->query('periode')) ? trim($request->query('periode')) : 'all';

        if ($jenisFormat === 'single_summary') {
            if ($kodePeriode !== '' && $kodePeriode !== 'all') {
                return $this->exportPeriode($kodePeriode, $layananDataPayroll);
            }

            return $this->exportArsip($layananDataPayroll);
        }

        return $layananMigrasi->eksporFormatMigrasi($kodePeriode);
    }

    public function unduhTemplateMigrasi(ExcelPayrollMigrationService $layananMigrasi): StreamedResponse
    {
        return $layananMigrasi->unduhTemplateKosong();
    }

    public function unduhDummyMigrasi(ExcelPayrollMigrationService $layananMigrasi): StreamedResponse
    {
        return $layananMigrasi->unduhBerkasDummyMigrasi();
    }
}
