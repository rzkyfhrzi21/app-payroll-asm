<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\PayrollDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        $kodeSekarang = \App\Support\SiklusPeriodeGaji::kodeDariTanggal(now());
        $periodeSekarang = $layananDataPayroll->ambilPeriodeBerdasarkanKode($kodeSekarang);
        $kodePeriode = $request->query('periode');
        $periode = (is_string($kodePeriode) && trim($kodePeriode) !== '')
            ? ($layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $periodeSekarang ?? $layananDataPayroll->ambilPeriodeTerbaru())
            : ($periodeSekarang ?? $layananDataPayroll->ambilPeriodeTerbaru());

        return view('driver.profile.index', [
            'user' => $request->user(),
            'period' => $layananDataPayroll->formatPeriode($periode),
            'profile' => $layananDataPayroll->profilSupir($request->user()),
            'truckCompliance' => $layananDataPayroll->kepatuhanArmada($karyawan),
            'mineCompliance' => $layananDataPayroll->kepatuhanTambang($karyawan),
        ]);
    }

    public function update(
        ProfileUpdateRequest $permintaan,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse {
        $pengguna = $permintaan->user();
        $dataValid = $permintaan->validated();

        $pengguna->fill($dataValid);
        if ($permintaan->hasFile('foto_profil')) {
            $pengguna->foto_profil = $layananMediaGambar->validasiDanSimpanWebp(
                $permintaan->file('foto_profil'),
                'profil-pengguna',
                'foto_profil'
            );
        }
        if ($pengguna->isDirty('email')) {
            $pengguna->email_verified_at = null;
        }
        $pengguna->save();

        $karyawan = $pengguna->supir;
        if ($karyawan !== null && isset($dataValid['name'])) {
            $karyawan->update(['nama_lengkap' => $dataValid['name']]);
        }

        return redirect()
            ->route('driver.profile.edit')
            ->with('status', 'Data profil & kontak berhasil diperbarui.');
    }
}
