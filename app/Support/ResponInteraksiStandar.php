<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Standar respon interaksi CRUD (BRIEF_STANDAR_INTERAKSI_DAN_KEAMANAN.md Bab 1.2).
 *
 * Tersedia dua gaya pemakaian yang menghasilkan muatan (payload) identik:
 * - Statis: `ResponInteraksiStandar::sukses(...)` / `::gagal(...)`, dipakai bila
 *   kelas pemanggil tidak memakai trait ini (mis. PayrollController).
 * - Instance: `$this->responSuksesStandar(...)` / `$this->responGalatStandar(...)`,
 *   dipakai controller yang sudah `use ResponInteraksiStandar`.
 */
trait ResponInteraksiStandar
{
    /**
     * Respon sukses dengan URL tujuan yang sudah berbentuk URL (dipakai statis).
     *
     * @param  array<string, mixed>  $data
     */
    public static function sukses(
        Request $request,
        string $judul,
        string $pesan,
        string $aksi = 'MUTASI_DATA',
        array $data = [],
        ?string $redirectUrl = null,
    ): JsonResponse|RedirectResponse {
        $urlTujuan = $redirectUrl ?? url()->previous();

        $muatanToast = [
            'success' => true,
            'type' => 'success',
            'title' => $judul,
            'action' => $aksi,
            'status' => 'COMPLETED',
            'message' => $pesan,
            'data' => $data,
            'redirect' => $urlTujuan,
            'timestamp' => now()->toIso8601String(),
        ];

        if (self::apakahPermintaanAjax($request)) {
            if ($request->hasSession()) {
                $request->session()->flash('status', $pesan);
                $request->session()->flash('toast', $muatanToast);
            }

            return response()->json($muatanToast);
        }

        return redirect()
            ->to($urlTujuan)
            ->with('status', $pesan)
            ->with('toast', $muatanToast);
    }

    /**
     * Respon galat dengan URL tujuan yang sudah berbentuk URL (dipakai statis).
     *
     * @param  array<string, array<int, string>>  $daftarGalat
     */
    public static function gagal(
        Request $request,
        string $judul,
        string $pesan,
        string $aksi = 'OPERATION_FAILED',
        string $statusKode = 'FAILED',
        array $daftarGalat = [],
        int $kodeHttp = 422,
        ?string $redirectUrl = null,
    ): JsonResponse|RedirectResponse {
        $urlTujuan = $redirectUrl ?? url()->previous();

        $muatanToast = [
            'success' => false,
            'type' => 'error',
            'title' => $judul,
            'action' => $aksi,
            'status' => $statusKode,
            'message' => $pesan,
            'errors' => $daftarGalat,
            'redirect' => $urlTujuan,
            'timestamp' => now()->toIso8601String(),
        ];

        if (self::apakahPermintaanAjax($request)) {
            return response()->json($muatanToast, $kodeHttp);
        }

        $pengalihan = redirect()->to($urlTujuan)->withInput();

        if ($daftarGalat !== []) {
            $pengalihan = $pengalihan->withErrors($daftarGalat);
        }

        return $pengalihan
            ->with('galat', $pesan)
            ->with('toast', $muatanToast);
    }

    /**
     * Menghasilkan respon sukses terstandarisasi (JSON untuk AJAX POST, Redirect + Toast untuk SSR).
     * Mengikuti standar BRIEF_STANDAR_INTERAKSI_DAN_KEAMANAN.md Bab 1.2.
     *
     * @param  array<string, mixed>  $dataTambahan
     * @param  array<string, mixed>  $parameterRute
     */
    protected function responSuksesStandar(
        Request $permintaan,
        string $ruteTujuan,
        string $pesan,
        string $judul = 'Berhasil Menyimpan Data',
        string $aksi = 'MUTASI_DATA',
        array $dataTambahan = [],
        array $parameterRute = [],
    ): JsonResponse|RedirectResponse {
        return self::sukses(
            request: $permintaan,
            judul: $judul,
            pesan: $pesan,
            aksi: $aksi,
            data: $dataTambahan,
            redirectUrl: route($ruteTujuan, $parameterRute),
        );
    }

    /**
     * Menghasilkan respon galat/error terstandarisasi (JSON 422/400 untuk AJAX POST, Redirect + Toast untuk SSR).
     *
     * @param  array<string, array<int, string>>  $daftarGalat
     * @param  array<string, mixed>  $parameterRute
     */
    protected function responGalatStandar(
        Request $permintaan,
        string $ruteTujuan,
        string $pesan,
        string $judul = 'Operasi Tidak Dapat Diproses',
        string $aksi = 'OPERATION_FAILED',
        string $statusKode = 'FAILED',
        array $daftarGalat = [],
        int $kodeHttp = 422,
        array $parameterRute = [],
    ): JsonResponse|RedirectResponse {
        $memakaiRute = $ruteTujuan !== '';

        return self::gagal(
            request: $permintaan,
            judul: $judul,
            pesan: $pesan,
            aksi: $aksi,
            statusKode: $statusKode,
            daftarGalat: $daftarGalat,
            kodeHttp: $kodeHttp,
            redirectUrl: $memakaiRute ? route($ruteTujuan, $parameterRute) : url()->previous(),
        );
    }

    protected static function apakahPermintaanAjax(Request $permintaan): bool
    {
        return $permintaan->expectsJson()
            || $permintaan->ajax()
            || $permintaan->header('X-Interaksi-Ajax') === '1';
    }
}
