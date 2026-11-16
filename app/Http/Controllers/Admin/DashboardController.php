<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BiayaOperasional;
use App\Models\Kasbon;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Models\Ritase;
use App\Models\Truk;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard ringkas untuk Admin HRD.
 *
 * Halaman ini menjawab "apa yang perlu saya kerjakan hari ini?".
 * Lembar kerja perhitungan gaji tetap di /admin/payroll.
 */
class DashboardController extends Controller
{
    public function index(Request $permintaan, PayrollDataService $layananDataPayroll): View
    {
        $periode = $layananDataPayroll->ambilPeriodeTerbaru();
        $kodePeriode = $permintaan->query('periode');

        if (is_string($kodePeriode) && $kodePeriode !== '') {
            $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $periode;
        }

        $ringkasanPayroll = $layananDataPayroll->ringkasanPayroll($periode);

        return view('admin.dashboard', [
            'period' => $layananDataPayroll->formatPeriode($periode),
            'periodOptions' => $layananDataPayroll->daftarOpsiPeriode(),
            'ringkasanPayroll' => $ringkasanPayroll,
            'ringkasanKasbon' => $layananDataPayroll->ringkasanKasbon($layananDataPayroll->daftarKasbon()),
            'ringkasanArmada' => $this->ringkasanArmada(),
            'ringkasanPengeluaran' => $this->ringkasanPengeluaran($periode?->id),
            'aktivitasTerbaru' => $this->aktivitasTerbaru(),
            'antreanKerja' => $this->antreanKerja($periode, $ringkasanPayroll),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkasanArmada(): array
    {
        $agregat = Truk::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif")
            ->selectRaw("SUM(CASE WHEN status <> 'aktif' THEN 1 ELSE 0 END) as nonaktif")
            ->selectRaw('SUM(CASE WHEN id_karyawan IS NULL THEN 1 ELSE 0 END) as tanpa_supir')
            ->first();

        $totalSupir = Karyawan::query()->count();
        $supirAktif = Karyawan::query()->where('status', 'aktif')->count();
        // Supir aktif yang belum dipegangkan ke unit truk mana pun.
        $supirTanpaUnit = Karyawan::query()
            ->where('status', 'aktif')
            ->whereDoesntHave('truk')
            ->count();

        return [
            'total' => (int) ($agregat->total ?? 0),
            'aktif' => (int) ($agregat->aktif ?? 0),
            'nonaktif' => (int) ($agregat->nonaktif ?? 0),
            'tanpa_supir' => (int) ($agregat->tanpa_supir ?? 0),
            'supir_total' => $totalSupir,
            'supir_aktif' => $supirAktif,
            'supir_nonaktif' => max(0, $totalSupir - $supirAktif),
            'supir_tanpa_unit' => $supirTanpaUnit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkasanPengeluaran(?int $idPeriode): array
    {
        $kueri = BiayaOperasional::query();

        if ($idPeriode !== null) {
            $kueri->where('id_periode_gaji', $idPeriode);
        }

        $agregat = $kueri
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw('COALESCE(SUM(nominal), 0) as total')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
            ->first();

        return [
            'jumlah' => (int) ($agregat->jumlah ?? 0),
            'total' => (int) ($agregat->total ?? 0),
            'draft' => (int) ($agregat->draft ?? 0),
        ];
    }

    /**
     * Lima pengantaran terakhir sebagai panel aktivitas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function aktivitasTerbaru(): array
    {
        return Ritase::query()
            ->with(['karyawan:id,nama_lengkap,nomor_induk_karyawan', 'truk:id,nomor_polisi', 'rute:id,kode'])
            ->orderByDesc('tanggal_ritase')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Ritase $ritase) => [
                'tanggal' => $ritase->tanggal_ritase?->format('d/m/Y') ?? '—',
                'supir' => $ritase->karyawan?->nama_lengkap ?? 'TANPA SUPIR',
                'nip' => $ritase->karyawan?->nomor_induk_karyawan ?? '—',
                'plat' => $ritase->truk?->nomor_polisi ?? '—',
                'rute' => $ritase->rute?->kode ?? '—',
                'tonase' => number_format(((int) $ritase->tonase_kg) / 1000, 2, ',', '.'),
                'status' => $ritase->status,
            ])
            ->all();
    }

    /**
     * Daftar pekerjaan yang menunggu Admin HRD.
     *
     * @param  array<string, mixed>  $ringkasanPayroll
     * @return array<int, array<string, mixed>>
     */
    private function antreanKerja($periode, array $ringkasanPayroll): array
    {
        $jumlahHariIni = Ritase::query()
            ->whereDate('tanggal_ritase', now()->toDateString())
            ->count();

        $kasbonAktif = Kasbon::query()->whereIn('status', ['pending', 'approved'])->count();

        $antrean = [
            [
                'judul' => 'Perhitungan gaji belum final',
                'keterangan' => $periode?->kode !== null
                    ? 'Periode '.$periode->kode.' masih berstatus '.$ringkasanPayroll['drivers'].' supir terdaftar.'
                    : 'Belum ada periode penggajian aktif.',
                'ikon' => 'calculate',
                'tautan' => route('admin.payroll.index', $periode?->kode ? ['periode' => $periode->kode] : []),
                'label_tautan' => 'Buka Payroll',
                'tone' => 'primary',
            ],
            [
                'judul' => 'Kasbon menunggu tindakan',
                'keterangan' => $kasbonAktif.' kasbon berstatus menunggu atau berjalan.',
                'ikon' => 'account_balance_wallet',
                'tautan' => route('admin.operational.loans.index'),
                'label_tautan' => 'Kelola Kasbon',
                'tone' => $kasbonAktif > 0 ? 'amber' : 'emerald',
            ],
            [
                'judul' => 'Pengantaran tercatat hari ini',
                'keterangan' => $jumlahHariIni.' surat jalan dibuat pada '.now()->format('d/m/Y').'.',
                'ikon' => 'route',
                'tautan' => route('admin.operational.trips.index'),
                'label_tautan' => 'Lihat Pengantaran',
                'tone' => 'slate',
            ],
        ];

        if ($ringkasanPayroll['warning_count'] > 0) {
            $antrean[] = [
                'judul' => 'Perlu pemeriksaan',
                'keterangan' => $ringkasanPayroll['warning_count'].' baris gaji punya peringatan (kasbon menumpuk atau sparepart tinggi).',
                'ikon' => 'warning',
                'tautan' => route('admin.payroll.index', ['tab' => 'warning']),
                'label_tautan' => 'Lihat Peringatan',
                'tone' => 'red',
            ];
        }

        return $antrean;
    }
}
