<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripMonitorController extends Controller
{
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
                    'iso' => $iso,
                    'label' => $tglObj->format('d M'),
                    'hari' => $tglObj->format('d'),
                    'total_rit' => $totalRit,
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
            'rows' => $barisRitase,
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
