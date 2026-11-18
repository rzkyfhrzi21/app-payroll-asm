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

/**
 * ============================================================================
 * CONTROLLER: KELOLA PROFIL AKUN PENGGUNA (PROFILE CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Ruang Administrasi Pengaturan Kartu Identitas Akun".
 * Setiap pegawai (Admin, Pimpinan, maupun Supir) dapat masuk ke ruangan ini
 * untuk memperbarui nama mereka, mengganti alamat email, mengunggah pas foto baru,
 * atau menutup/menonaktifkan akun mereka sendiri.
 * ============================================================================
 */
class ProfileController extends Controller
{
    /**
     * Menampilkan Formulir Profil Akun
     * 
     * Alur Kerja Bernomor:
     * (1) Dapatkan data identitas akun yang sedang membuka halaman ini.
     * (2) Ambil ringkasan statistik kantor (jumlah supir aktif, truk aktif, dan periode aktif).
     * (3) Buka tampilan antarmuka (view) 'profile.edit' beserta data ringkasan tersebut.
     */
    public function edit(Request $request, PayrollDataService $layananDataPayroll): View
    {
        // (1) Ambil buku periode gajian yang paling baru
        $periodeAktif = $layananDataPayroll->ambilPeriodeTerbaru();

        // (2) & (3) Render halaman Blade profil
        return view('profile.edit', [
            'user' => $request->user(),
            'ringkasan' => [
                'supir_aktif'       => Karyawan::where('status', 'aktif')->count(),     // Total supir bekerja
                'jumlah_truk'       => Truk::where('status', 'aktif')->count(),         // Total truk siap jalan
                'jumlah_periode'    => PeriodeGaji::count(),                            // Total arsip bulan gaji
                'menunggu_acc'      => PeriodeGaji::where('status', 'diajukan')->count(),// Periode draf antre ACC
                'periode_disetujui' => PeriodeGaji::where('status', 'disetujui')->count(),// Periode sudah sah
                'periode_aktif'     => $layananDataPayroll->formatPeriode($periodeAktif),// Nama label bulan aktif
            ],
        ]);
    }

    /**
     * Memperbarui Informasi Biodata & Foto Profil Akun
     * 
     * Alur Kerja Bernomor:
     * (1) Validasi ketikan pengguna (nama dan email wajib valid) via Form Request.
     * (2) Jika pengguna memilih file foto baru, kompresi otomatis ke format WebP agar hemat memori server.
     * (3) Jika alamat email diubah, batalkan status verifikasi email lama untuk keamanan.
     * (4) Simpan perubahan ke tabel database MySQL.
     * (5) Siapkan paket notifikasi Toast Sukses dan kirim kembali ke browser (AJAX / Redirect).
     */
    public function update(
        ProfileUpdateRequest $request,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|\Illuminate\Http\JsonResponse {
        // (1) Isi perubahan teks yang sudah lolos sensor validasi
        $request->user()->fill($request->validated());

        // (2) Upload berkas foto fisik jika dilampirkan oleh pengguna
        if ($request->hasFile('foto_profil')) {
            $request->user()->foto_profil = $layananMediaGambar->validasiDanSimpanWebp(
                $request->file('foto_profil'),
                'profil-pengguna',
                'foto_profil'
            );
        }

        // (3) Reset status verifikasi bila email diganti
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // (4) Simpan perubahan ke database
        $request->user()->save();

        // (5) Rangkai pesan notifikasi toast ramah pengguna
        $pesanSukses = "Profil akun '{$request->user()->name}' ({$request->user()->email}) berhasil diperbarui.";
        $muatanToast = [
            'success'   => true,
            'type'      => 'success',
            'title'     => 'Profil Akun Diperbarui',
            'action'    => 'UPDATE_PROFIL_AKUN',
            'status'    => 'COMPLETED',
            'message'   => $pesanSukses,
            'data'      => [
                'id'   => $request->user()->id,
                'nama' => $request->user()->name,
            ],
            'redirect'  => route('profile.edit'),
            'timestamp' => now()->toIso8601String(),
        ];

        // Respons instan jika form dikirim lewat AJAX tanpa refresh layar
        if ($request->expectsJson() || $request->ajax() || $request->header('X-Interaksi-Ajax') === '1') {
            $request->session()->flash('status', 'Profil berhasil diperbarui.');
            $request->session()->flash('toast', $muatanToast);

            return response()->json($muatanToast);
        }

        // Respons bawaan jika formulir dikirim normal
        return Redirect::route('profile.edit')
            ->with('status', 'Profil berhasil diperbarui.')
            ->with('toast', $muatanToast);
    }

    /**
     * Menghapus Akun Pengguna Secara Mandiri
     * 
     * Alur Kerja Bernomor:
     * (1) Minta konfirmasi kata sandi aktif pengguna untuk mencegah penghapusan tidak disengaja.
     * (2) Keluarkan pengguna dari sistem (logout).
     * (3) Hapus baris data pengguna dari tabel database.
     * (4) Hanguskan tiket sesi login browser dan arahkan kembali ke halaman depan.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // (1) Validasi kecocokan kata sandi saat ini
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // (2) Logout keluar sistem
        Auth::logout();

        // (3) Hapus data akun
        $user->delete();

        // (4) Hanguskan sesi browser dan acak ulang token keamanan
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
