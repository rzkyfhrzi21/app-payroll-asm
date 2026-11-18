<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: MONITORING PERJALANAN RITASE EKSEKUTIF (TRIP MONITOR CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Layar Radar Pengawasan Truk Lapangan di Meja Direktur".
 * Di sini pimpinan dapat melihat rekapitulasi harian pergerakan armada:
 * tanggal berapa saja pengiriman batubara paling padat, berapa tonase yang terangkut
 * per hari, dan siapa saja supir tangguh yang sedang bertugas di lapangan.
 * ============================================================================
 */
class TripMonitorController extends Controller
{
    /**
     * Menampilkan Dasbor Monitor Ritase Lapangan
     * 
     * Alur Kerja Bernomor:
     * (1) Tentukan periode aktif atau rentang tanggal kustom yang ingin dipantau.
     * (2) Ambil baris surat jalan ritase yang terjadi pada rentang tersebut.
     * (3) Kelompokkan data per tanggal untuk membentuk grafik statistik harian.
     * (4) Buka tampilan antarmuka 'executive.trips'.
     */
    public function index(Request $permintaan, PayrollDataService $layananDataPayroll): View
    {
        $kodePeriode = $permintaan->query('periode');
        $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode(is_string($kodePeriode) ? $kodePeriode : null)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        // Filter tanggal opsional: saat diisi, data diambil dari tanggal tersebut
        // walau berada di luar periode (nilai bisa kosong bila tak ada ritase).
        $tanggalDari = $this->tanggalValid($permintaan->query('tanggal_dari'));
        $tanggalSampai = $this->tanggalValid($permintaan->query('tanggal_sampai'));

        $barisRitase = $layananDataPayroll->barisRitaseOperasional($periode);

        if ($tanggalDari !== null || $tanggalSampai !== null) {
            $barisRitase = $layananDataPayroll->barisRitaseRentangTanggal($tanggalDari, $tanggalSampai);
        }

        $rekapHarian = collect($barisRitase)
            ->filter(fn (array $r): bool => ! empty($r['date_iso']))
            ->groupBy('date_iso')
            ->sortKeys()
            ->map(function (\Illuminate\Support\Collection $kelompok, string $iso): array {
                $totalRit = (int) $kelompok->sum('rit');
                $totalTonase = round((float) $kelompok->sum(fn (array $r): float => (float) str_replace(',', '.', (string) ($r['tonnage'] ?? 0))), 2);
                $daftarSupir = $kelompok->pluck('driver')->filter()->unique()->values()->all();
                $tglObj = \Illuminate\Support\Carbon::parse($iso);

                return [
                    'iso'          => $iso,
                    'label'        => $tglObj->format('d M'),
                    'hari'         => $tglObj->format('d'),
                    'total_rit'    => $totalRit,
                    'total_tonase' => $totalTonase,
                    'jumlah_supir' => count($daftarSupir),
                    'daftar_supir' => $daftarSupir,
                ];
            })
            ->values()
            ->all();

        return view('executive.trips', [
            'period' => $layananDataPayroll->formatPeriode($periode),
            'periodeModel' => $periode,
            'summary' => $layananDataPayroll->ringkasanRitase($barisRitase),
            // Hanya baris awal yang dikirim ke Blade; sisanya dimuat via AJAX.
            'rows' => array_slice($barisRitase, 0, 50),
            'jumlahSeluruhBaris' => count($barisRitase),
            // Opsi filter diambil dari SELURUH baris, bukan hanya 50 baris awal.
            'pilihanSupir' => collect($barisRitase)
                ->mapWithKeys(fn (array $baris) => [($baris['nip'] ?? '—') => ($baris['driver'] ?? '—')])
                ->sort()
                ->all(),
            'pilihanRute' => collect($barisRitase)->pluck('route')->filter()->unique()->sort()->values()->all(),
            'rekapHarian' => $rekapHarian,
            'overview' => $layananDataPayroll->ringkasanEksekutif($periode),
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'modeRentangTanggal' => $tanggalDari !== null || $tanggalSampai !== null,
        ]);
    }

    /**
     * Memvalidasi format tanggal Y-m-d dari query string.
     */
    private function tanggalValid(mixed $nilai): ?string
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        $tanggal = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', trim($nilai));

        return $tanggal !== false ? $tanggal->toDateString() : null;
    }
}
