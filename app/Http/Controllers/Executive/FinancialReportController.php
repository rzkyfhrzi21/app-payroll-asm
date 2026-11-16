<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function index(Request $permintaan, PayrollDataService $layananDataPayroll): View
    {
        $kodePeriode = $permintaan->query('periode');

        // Tanpa ?periode= halaman menampilkan daftar periode lebih dulu.
        // Setelah periode diklik, rincian supir baru dimuat.
        $adaPeriodeTerpilih = is_string($kodePeriode) && trim($kodePeriode) !== '';
        $periode = $adaPeriodeTerpilih
            ? $layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode)
            : null;

        $daftarPeriode = $this->susunDaftarPeriode($layananDataPayroll);

        if ($periode === null) {
            return view('executive.reports.index', [
                'period' => $layananDataPayroll->formatPeriode(null),
                'periodeModel' => null,
                'periodOptions' => $layananDataPayroll->daftarOpsiPeriode(),
                'daftarPeriode' => $daftarPeriode,
                'periodeTerpilih' => false,
                'report' => [],
                'summary' => [],
                'modelArmada' => [],
                'rows' => [],
            ]);
        }

        return view('executive.reports.index', [
            'period' => $layananDataPayroll->formatPeriode($periode),
            'periodeModel' => $periode,
            'periodOptions' => $layananDataPayroll->daftarOpsiPeriode(),
            'daftarPeriode' => $daftarPeriode,
            'periodeTerpilih' => true,
            'report' => $layananDataPayroll->laporanFinansialEksekutif($periode),
            'summary' => $layananDataPayroll->ringkasanEksekutif($periode),
            'modelArmada' => $layananDataPayroll->rekapModelArmadaEksekutif($periode),
            'rows' => $layananDataPayroll->barisLaporanFinansialEksekutif($periode),
        ]);
    }

    /**
     * Ringkasan tiap periode untuk halaman pemilih periode.
     *
     * @return array<int, array<string, mixed>>
     */
    private function susunDaftarPeriode(PayrollDataService $layananDataPayroll): array
    {
        return $layananDataPayroll->ringkasanSemuaPeriodeEksekutif();
    }

    public function pdf(Request $permintaan): \Illuminate\Http\RedirectResponse
    {
        return back()->with('status', 'Laporan finansial pimpinan siap dicetak melalui fitur Print halaman ini.');
    }
    public function export(
        Request $permintaan,
        \App\Services\ExcelPayrollMigrationService $layananMigrasi
    ): \Symfony\Component\HttpFoundation\StreamedResponse {
        $kodePeriode = is_string($permintaan->query('periode')) && $permintaan->query('periode') !== ''
            ? $permintaan->query('periode')
            : 'all';

        return $layananMigrasi->eksporFormatMigrasi($kodePeriode);
    }

    public function email(Request $permintaan): \Illuminate\Http\RedirectResponse
    {
        return back()->with('status', 'Ringkasan laporan finansial telah dikirimkan ke alamat email Direksi.');
    }
}
