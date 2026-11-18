<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Karyawan;
use App\Models\PeriodeGaji;
use App\Services\PayrollDataService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ============================================================================
 * TRAIT: OTORISASI & PEMBANGUNAN PAGINATOR TABEL PER PERAN
 * ============================================================================
 *
 * Analogi Sederhana:
 * Petugas arsip yang memeriksa kartu identitas (peran) lalu mengambil map
 * data dari lemari yang tepat. Untuk Admin dan Pimpinan, map diambil memakai
 * PENYUSUN YANG SAMA dengan halaman biasa (PayrollDataService), sehingga
 * baris hasil AJAX identik dengan baris hasil render server.
 *
 * Alur Kerja Bernomor:
 * (1) Periksa entitas yang boleh dibaca peran tersebut.
 * (2) Sisipkan kata kunci pencarian ke dalam filter (kontrak layanan).
 * (3) Batasi data milik supir yang sedang login (bila peran = supir).
 * (4) Tentukan periode aktif dari filter atau periode terbaru.
 * (5) Panggil penyusun baris yang sesuai; seluruh baris sudah berupa array
 *     siap pakai untuk fragmen Blade.
 * ============================================================================
 */
trait InteraksiTabelPeran
{
    /**
     * Daftar entitas yang boleh dibaca per peran.
     *
     * @return array<int, string>
     */
    protected function entitasDiizinkan(string $peran): array
    {
        return match ($peran) {
            'admin' => ['karyawan', 'truk', 'rute', 'ritase', 'biaya', 'biaya_operasional', 'kasbon', 'periode_gaji', 'laporan', 'penggajian', 'payroll'],
            'pimpinan' => ['karyawan', 'truk', 'rute', 'ritase', 'biaya', 'biaya_operasional', 'kasbon', 'periode_gaji', 'laporan', 'penggajian', 'payroll'],
            'supir' => ['ritase', 'kasbon', 'penggajian', 'payroll'],
            default => [],
        };
    }

    /**
     * Bangun paginator tabel sesuai entitas, peran, dan filter yang dikirim.
     *
     * @param  array<string, mixed>  $filter
     */
    protected function bangunQueryTabel(
        string $entitas,
        string $peran,
        string $kataKunci,
        array $filter,
        string $kolomUrut,
        string $arahUrut,
        int $perHalaman,
        Request $permintaan,
    ): LengthAwarePaginator {
        $layanan = app(PayrollDataService::class);

        // Kontrak layanan menaruh kata kunci di dalam filter. Kata kunci dari
        // kolom pencarian (top-level) menang; filter.cari dipakai sebagai
        // cadangan agar pemanggil API tidak wajib memakai dua bentuk.
        $filter['cari'] = $kataKunci !== '' ? $kataKunci : (string) ($filter['cari'] ?? '');

        // Supir hanya boleh membaca datanya sendiri.
        if ($peran === 'supir') {
            $filter['id_karyawan'] = $this->idKaryawanPengguna($permintaan) ?? 0;
        }

        $kodePeriode = isset($filter['periode']) && $filter['periode'] !== 'all'
            ? (string) $filter['periode']
            : null;

        $periode = $layanan->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $layanan->ambilPeriodeTerbaru();

        $tanggalDari = $this->tanggalTerisi($filter['tanggal_dari'] ?? null) ? (string) $filter['tanggal_dari'] : null;
        $tanggalSampai = $this->tanggalTerisi($filter['tanggal_sampai'] ?? null) ? (string) $filter['tanggal_sampai'] : null;

        // Rentang tanggal yang boleh melintasi batas periode hanya dipakai bila
        // pemanggil menyatakan eksplisit (halaman pemantauan Pimpinan). Halaman
        // lain tetap terikat periode agar hasil AJAX sama dengan hasil SSR.
        $lintasPeriode = (bool) ($filter['lintas_periode'] ?? false);

        return match ($entitas) {
            'karyawan' => $layanan->dataKaryawanMasterTerpaginasi($filter, $perHalaman),
            'truk' => $layanan->dataTrukMasterTerpaginasi($filter, $perHalaman),
            'rute' => $layanan->daftarRuteTerpaginasi($filter, $perHalaman),
            'kasbon' => $layanan->daftarKasbonTerpaginasi($filter, $perHalaman),
            'biaya', 'biaya_operasional' => $layanan->barisBiayaOperasionalTerpaginasi($periode, $filter, $perHalaman),
            'ritase' => $lintasPeriode
                ? $layanan->barisRitaseRentangTanggalTerpaginasi($tanggalDari, $tanggalSampai, $filter, $perHalaman)
                : $layanan->barisRitaseOperasionalTerpaginasi($periode, $filter, $perHalaman),
            'penggajian', 'payroll' => $layanan->barisPayrollTerpaginasi($periode, $filter, $perHalaman),
            'periode_gaji', 'laporan' => PeriodeGaji::query()->orderByDesc('id')->paginate($perHalaman),
            default => $layanan->dataKaryawanMasterTerpaginasi($filter, $perHalaman),
        };
    }

    /**
     * ID karyawan milik pengguna supir yang sedang login.
     */
    protected function idKaryawanPengguna(Request $permintaan): ?int
    {
        $pengguna = $permintaan->user() ?? Auth::user();

        if ($pengguna === null) {
            return null;
        }

        return Karyawan::query()->where('id_pengguna', $pengguna->id)->value('id');
    }

    /**
     * Tanggal filter dianggap terisi bila berformat YYYY-MM-DD.
     */
    private function tanggalTerisi(mixed $nilai): bool
    {
        return is_string($nilai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) === 1;
    }
}
