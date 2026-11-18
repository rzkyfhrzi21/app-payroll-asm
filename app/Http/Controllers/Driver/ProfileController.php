<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\PayrollDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: PROFIL PRIBADI SUPIR (DRIVER PROFILE CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kamar Ganti & Kartu Tanda Anggota (KTA) Supir Tambang".
 * Di halaman ini supir dapat memeriksa data kelengkapan kerja mereka:
 * masa berlaku SIM, status pemeriksaan kesehatan MCU tambang,
 * status kelaikan truk yang mereka bawa, serta mengunggah pas foto diri terbaru.
 * ============================================================================
 */
class ProfileController extends Controller
{
    /**
     * Menampilkan Halaman Profil Biodata Supir
     * 
     * Alur Kerja Bernomor:
     * (1) Identifikasi profil supir dari akun login.
     * (2) Ambil data kepatuhan kelaikan armada truk dan kepatuhan standar tambang.
     * (3) Buka antarmuka Blade 'driver.profile.index'.
     */
    public function edit(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        $periodeBerjalan = $layananDataPayroll->ambilPeriodeBerjalan();
        $kodePeriode = $request->query('periode');
        $periode = (is_string($kodePeriode) && trim($kodePeriode) !== '')
            ? ($layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $periodeBerjalan ?? $layananDataPayroll->ambilPeriodeTerbaru())
            : ($periodeBerjalan ?? $layananDataPayroll->ambilPeriodeTerbaru());

        return view('driver.profile.index', [
            'user'            => $request->user(),
            'period'          => $layananDataPayroll->formatPeriode($periode),
            'profile'         => $layananDataPayroll->profilSupir($request->user()),
            'truckCompliance' => $layananDataPayroll->kepatuhanArmada($karyawan),
            'mineCompliance'  => $layananDataPayroll->kepatuhanTambang($karyawan),
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
