<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        $kodeSekarang = \App\Support\SiklusPeriodeGaji::kodeDariTanggal(now());
        $periodeSekarang = $layananDataPayroll->ambilPeriodeBerdasarkanKode($kodeSekarang);
        $kodePeriode = $request->query('periode');
        $periode = (is_string($kodePeriode) && trim($kodePeriode) !== '')
            ? ($layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $periodeSekarang ?? $layananDataPayroll->ambilPeriodeTerbaru())
            : ($periodeSekarang ?? $layananDataPayroll->ambilPeriodeTerbaru());
        $penggajian = $karyawan === null
            ? null
            : $layananDataPayroll->ambilPenggajianSupir($karyawan, $periode);

        return view('driver.dashboard', [
            'period' => $layananDataPayroll->formatPeriode($periode),
            'profile' => $layananDataPayroll->profilSupir($request->user()),
            'payroll' => $layananDataPayroll->payrollSupir($penggajian, $karyawan),
            'trips' => $layananDataPayroll->ritaseSupir($karyawan, $periode),
        ]);
    }

    public function cetakSlip(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        $penggajian = $karyawan === null
            ? null
            : $layananDataPayroll->ambilPenggajianSupir($karyawan);
        $periode = $penggajian?->periodeGaji ?? $layananDataPayroll->ambilPeriodeTerbaru();

        abort_if(
            $periode === null || ! in_array($periode->status, ['approved', 'paid', 'disetujui', 'dibayar'], true),
            403,
            'Slip gaji supir belum diterbitkan. Slip gaji hanya dapat diakses setelah periode disetujui Pimpinan.'
        );
        abort_if($penggajian === null, 404, 'Data penggajian supir belum tersedia pada periode ini.');

        $dataRincian = $layananDataPayroll->rincianLaporanPeriode($periode);
        $dataSupir = collect($dataRincian['daftar_supir'])
            ->firstWhere('id_penggajian', $penggajian->id)
            ?? collect($dataRincian['daftar_supir'])->first();

        abort_if($dataSupir === null, 404, 'Data rincian slip gaji supir tidak ditemukan.');

        return view('admin.reports.slip', [
            'periode' => $dataRincian['periode'],
            'supir' => $dataSupir,
        ]);
    }
}

