<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Karyawan;
use App\Models\PeriodeGaji;
use App\Models\Truk;
use App\Services\PayrollDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $periodeAktif = $layananDataPayroll->ambilPeriodeTerbaru();

        return view('profile.edit', [
            'user' => $request->user(),
            'ringkasan' => [
                'supir_aktif' => Karyawan::where('status', 'aktif')->count(),
                'jumlah_truk' => Truk::where('status', 'aktif')->count(),
                'jumlah_periode' => PeriodeGaji::count(),
                'menunggu_acc' => PeriodeGaji::where('status', 'diajukan')->count(),
                'periode_disetujui' => PeriodeGaji::where('status', 'disetujui')->count(),
                'periode_aktif' => $layananDataPayroll->formatPeriode($periodeAktif),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(
        ProfileUpdateRequest $request,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|\Illuminate\Http\JsonResponse {
        $request->user()->fill($request->validated());

        if ($request->hasFile('foto_profil')) {
            $request->user()->foto_profil = $layananMediaGambar->validasiDanSimpanWebp(
                $request->file('foto_profil'),
                'profil-pengguna',
                'foto_profil'
            );
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();
        $pesanSukses = "Profil akun '{$request->user()->name}' ({$request->user()->email}) berhasil diperbarui.";
        $muatanToast = [
            'success' => true,
            'type' => 'success',
            'title' => 'Profil Akun Diperbarui',
            'action' => 'UPDATE_PROFIL_AKUN',
            'status' => 'COMPLETED',
            'message' => $pesanSukses,
            'data' => [
                'id' => $request->user()->id,
                'nama' => $request->user()->name,
            ],
            'redirect' => route('profile.edit'),
            'timestamp' => now()->toIso8601String(),
        ];

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Interaksi-Ajax') === '1') {
            $request->session()->flash('status', 'Profil berhasil diperbarui.');
            $request->session()->flash('toast', $muatanToast);

            return response()->json($muatanToast);
        }

        return Redirect::route('profile.edit')
            ->with('status', 'Profil berhasil diperbarui.')
            ->with('toast', $muatanToast);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
