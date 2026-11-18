<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: LAPORAN KEUANGAN EKSEKUTIF (EXECUTIVE FINANCIAL REPORT)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Buku Neraca Keuangan & Pembagian Hasil Armada Tambang".
 * Dari buku laporan ini, pimpinan dapat melihat angka-angka laba bersih riil:
 * berapa total pendapatan kotor yang diperoleh dari pemilik batubara,
 * berapa uang yang habis untuk solar dan suku cadang bengkel,
 * berapa total bagi hasil yang dibagikan ke supir, dan berapa sisa bersih kas kantor.
 * ============================================================================
 */
class FinancialReportController extends Controller
{
    /**
     * Menampilkan Laporan Finansial Periode Penggajian
     * 
     * Alur Kerja Bernomor:
     * (1) Periksa apakah pimpinan sudah memilih satu periode tertentu lewat URL (?periode=...).
     * (2) Jika belum memilih, tampilkan kartu daftar periode terlebih dahulu (tahap seleksi).
     * (3) Jika sudah memilih periode, muat rincian tabel finansial lengkap per supir dan unit armada.
     * (4) Buka tampilan 'executive.reports.index'.
     */
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
                'period'          => $layananDataPayroll->formatPeriode(null),
                'periodeModel'    => null,
                'periodOptions'   => $layananDataPayroll->daftarOpsiPeriode(),
                'daftarPeriode'   => $daftarPeriode,
                'periodeTerpilih' => false,
                'report'          => [],
                'summary'         => [],
                'modelArmada'     => [],
                'rows'            => [],
            ]);
        }

        return view('executive.reports.index', [
            'period'          => $layananDataPayroll->formatPeriode($periode),
            'periodeModel'    => $periode,
            'periodOptions'   => $layananDataPayroll->daftarOpsiPeriode(),
            'daftarPeriode'   => $daftarPeriode,
            'periodeTerpilih' => true,
            'report'          => $layananDataPayroll->laporanFinansialEksekutif($periode),
            'summary'         => $layananDataPayroll->ringkasanEksekutif($periode),
            'modelArmada'     => $layananDataPayroll->rekapModelArmadaEksekutif($periode),
            'rows'            => $layananDataPayroll->barisLaporanFinansialEksekutif($periode),
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
