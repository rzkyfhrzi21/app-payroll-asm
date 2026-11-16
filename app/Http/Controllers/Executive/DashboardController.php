<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $permintaan, PayrollDataService $layananDataPayroll): View
    {
        $periode = $this->tentukanPeriode($permintaan, $layananDataPayroll);
        $daftarAntrean = $layananDataPayroll->antreanPersetujuanEksekutif($periode);

        return view('executive.dashboard', [
            'period' => $layananDataPayroll->formatPeriode($periode),
            'periodOptions' => $layananDataPayroll->daftarOpsiPeriode(),
            'periodeModel' => $periode,
            'summary' => $layananDataPayroll->ringkasanEksekutif($periode),
            'trend' => $layananDataPayroll->trenPeriodeEksekutif($periode),
            'trenBulanan' => $layananDataPayroll->trenBulananEksekutif($periode),
            'trenTahunan' => $layananDataPayroll->trenTahunanEksekutif($periode),
            'routeBreakdown' => $layananDataPayroll->rekapRuteEksekutif($periode),
            'cost' => $layananDataPayroll->komposisiBiayaEksekutif($periode),
            'queue' => $daftarAntrean,
            'auditUnit' => $layananDataPayroll->unitBebanSparepartTertinggi($periode),
            'ringkasanKasbon' => $layananDataPayroll->ringkasanKasbon($layananDataPayroll->daftarKasbon()),
            'sudahAcc' => in_array($periode?->status, ['approved', 'paid'], true),
        ]);
    }

    /**
     * Periode aktif default adalah periode terbaru; query ?periode= tetap dihormati
     * supaya Pimpinan bisa meninjau periode lampau.
     */
    private function tentukanPeriode(Request $permintaan, PayrollDataService $layananDataPayroll)
    {
        $kodePeriode = $permintaan->query('periode');

        return $layananDataPayroll->ambilPeriodeBerdasarkanKode(is_string($kodePeriode) ? $kodePeriode : null)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();
    }
}
