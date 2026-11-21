<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: DASHBOARD EKSEKUTIF PIMPINAN (EXECUTIVE DASHBOARD CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Ruang Kendali Utama & Meja Kerja Direktur Utama / Pimpinan".
 * Di ruangan digital inilah pimpinan memantau kesehatan bisnis tambang secara makro:
 * grafik tren kenaikan/penurunan omzet bulanan, jalur rute mana yang paling menguntungkan,
 * armada truk mana yang menghabiskan biaya suku cadang paling boros,
 * serta melihat apakah ada pengajuan draf gaji dari staf admin yang menunggu ACC.
 * ============================================================================
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan Beranda Dashboard Pimpinan
     * 
     * Alur Kerja Bernomor:
     * (1) Tentukan periode yang dipilih pimpinan (default periode terbaru).
     * (2) Kumpulkan statistik tren omzet, komposisi biaya operasional, dan unit boros sparepart.
     * (3) Buka tampilan antarmuka 'executive.dashboard'.
     */
    public function index(Request $permintaan, PayrollDataService $layananDataPayroll): View
    {
        $periode = $this->tentukanPeriode($permintaan, $layananDataPayroll);

        return view('executive.dashboard', [
            'period'         => $layananDataPayroll->formatPeriode($periode),
            'periodOptions'  => $layananDataPayroll->daftarOpsiPeriode(),
            'periodeModel'   => $periode,
            'summary'        => $layananDataPayroll->ringkasanEksekutif($periode),
            'trend'          => $layananDataPayroll->trenPeriodeEksekutif($periode),
            'trenBulanan'    => $layananDataPayroll->trenBulananEksekutif($periode),
            'trenTahunan'    => $layananDataPayroll->trenTahunanEksekutif($periode),
            'routeBreakdown' => $layananDataPayroll->rekapRuteEksekutif($periode),
            'cost'           => $layananDataPayroll->komposisiBiayaEksekutif($periode),
            'auditUnit'      => $layananDataPayroll->unitBebanSparepartTertinggi($periode),
            'kebutuhanKasGaji'=> $layananDataPayroll->kebutuhanKasGajiSeluruhPeriode(),
            'ringkasanKasbon'=> $layananDataPayroll->ringkasanKasbonDariSql(),
            'sudahAcc'       => in_array($periode?->status, ['approved', 'paid'], true),
        ]);
    }

    /**
     * Menentukan Periode Bulan Penggajian yang Sedang Dilihat
     * Mendukung peninjauan periode masa lampau melalui query '?periode=...'.
     */
    private function tentukanPeriode(Request $permintaan, PayrollDataService $layananDataPayroll)
    {
        $kodePeriode = $permintaan->query('periode');

        return $layananDataPayroll->ambilPeriodeBerdasarkanKode(is_string($kodePeriode) ? $kodePeriode : null)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();
    }
}
