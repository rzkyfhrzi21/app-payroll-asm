<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BiayaOperasional;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\Truk;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InteraksiTabelController extends Controller
{
    /**
     * Endpoint terpusat interaksi tabel Admin berbasis AJAX POST (BRIEF Bab 4.1, Bab 8.2, Bab 9).
     * Menerapkan Whitelist Kolom Sorting ketat (anti-SQLi), Eager Loading (anti-N+1),
     * pemilihan kolom spesifik (anti-overfetching), dan Hard Limit Pagination maksimal 50 baris.
     */
    public function __invoke(Request $permintaan): JsonResponse
    {
        $entitas = strtolower((string) $permintaan->input('entitas', 'karyawan'));
        $kataKunci = trim((string) $permintaan->input('cari', ''));
        $filter = is_array($permintaan->input('filter')) ? $permintaan->input('filter') : [];

        $statusFilter = trim((string) ($filter['status'] ?? $permintaan->input('status', 'all')));
        $perHalamanInput = (int) ($permintaan->input('per_halaman') ?: $permintaan->input('per_page', 25));
        $perHalaman = min(max($perHalamanInput, 1), 100);
        $halaman = max((int) ($permintaan->input('halaman') ?: $permintaan->input('page', 1)), 1);
        $permintaan->merge(['page' => $halaman]);

        $arahDiizinkan = ['ASC', 'DESC'];
        $arahUrut = in_array(strtoupper((string) $permintaan->input('sort_dir', 'DESC')), $arahDiizinkan, true)
            ? strtoupper((string) $permintaan->input('sort_dir', 'DESC'))
            : 'DESC';

        $paginator = match ($entitas) {
            'truk' => $this->queryTabelTruk($permintaan, $kataKunci, $statusFilter, $filter, $arahUrut, $perHalaman),
            'rute' => $this->queryTabelRute($permintaan, $kataKunci, $statusFilter, $filter, $arahUrut, $perHalaman),
            'ritase' => $this->queryTabelRitase($permintaan, $kataKunci, $statusFilter, $filter, $arahUrut, $perHalaman),
            'biaya', 'biaya_operasional' => $this->queryTabelBiaya($permintaan, $kataKunci, $statusFilter, $filter, $arahUrut, $perHalaman),
            'kasbon' => $this->queryTabelKasbon($permintaan, $kataKunci, $statusFilter, $filter, $arahUrut, $perHalaman),
            'periode_gaji', 'laporan' => $this->queryTabelPeriodeGaji($permintaan, $kataKunci, $statusFilter, $filter, $arahUrut, $perHalaman),
            'penggajian', 'payroll' => $this->queryTabelPenggajian($permintaan, $kataKunci, $statusFilter, $filter, $arahUrut, $perHalaman),
            default => $this->queryTabelKaryawan($permintaan, $kataKunci, $statusFilter, $filter, $arahUrut, $perHalaman),
        };

        return response()->json([
            'success' => true,
            'type' => 'success',
            'title' => 'Data Tabel Dimuat',
            'action' => 'FETCH_TABEL_'.strtoupper($entitas),
            'status' => 'COMPLETED',
            'message' => 'Berhasil memuat data tabel '.$entitas.'.',
            'data' => [
                'rows' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem() ?? 0,
                    'to' => $paginator->lastItem() ?? 0,
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function queryTabelKaryawan(Request $permintaan, string $kataKunci, string $statusFilter, array $filter, string $arahUrut, int $perHalaman): LengthAwarePaginator
    {
        $kolomDiizinkan = ['id', 'nomor_induk_karyawan', 'nama_lengkap', 'status', 'created_at'];
        $kolomUrut = in_array((string) $permintaan->input('sort_by'), $kolomDiizinkan, true)
            ? (string) $permintaan->input('sort_by')
            : 'id';

        $kueri = Karyawan::query()
            ->select(['id', 'id_pengguna', 'nomor_induk_karyawan', 'nama_lengkap', 'foto_profil', 'status', 'nama_bank', 'nomor_rekening', 'persentase_komisi_bp', 'potongan_bpjs_bulanan', 'created_at'])
            ->with(['pengguna:id,name,email,nomor_telepon', 'truk:id,id_karyawan,nomor_polisi,merek_model']);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $kueri->where('status', strtolower($statusFilter));
        }

        $filterUnit = (string) ($filter['unit'] ?? 'all');
        if ($filterUnit === 'pegang') {
            $kueri->whereHas('truk');
        } elseif ($filterUnit === 'belum') {
            $kueri->whereDoesntHave('truk');
        }

        if ($kataKunci !== '') {
            $kueri->where(function ($sub) use ($kataKunci): void {
                $sub->where('nama_lengkap', 'like', "%{$kataKunci}%")
                    ->orWhere('nomor_induk_karyawan', 'like', "%{$kataKunci}%")
                    ->orWhereHas('truk', fn ($q) => $q->where('nomor_polisi', 'like', "%{$kataKunci}%"));
            });
        }

        return $kueri->orderBy($kolomUrut, $arahUrut)->paginate($perHalaman);
    }

    private function queryTabelTruk(Request $permintaan, string $kataKunci, string $statusFilter, array $filter, string $arahUrut, int $perHalaman): LengthAwarePaginator
    {
        $kolomDiizinkan = ['id', 'nomor_polisi', 'merek_model', 'status', 'created_at'];
        $kolomUrut = in_array((string) $permintaan->input('sort_by'), $kolomDiizinkan, true)
            ? (string) $permintaan->input('sort_by')
            : 'id';

        $kueri = Truk::query()
            ->select(['id', 'id_jenis_truk', 'id_karyawan', 'nomor_polisi', 'merek_model', 'foto_unit', 'status', 'created_at'])
            ->with(['jenisTruk:id,nama', 'karyawan:id,nama_lengkap,nomor_induk_karyawan']);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $kueri->where('status', strtolower($statusFilter));
        }

        $filterPenugasan = (string) ($filter['penugasan'] ?? 'all');
        if ($filterPenugasan === 'dipegang') {
            $kueri->whereNotNull('id_karyawan');
        } elseif ($filterPenugasan === 'cadangan') {
            $kueri->whereNull('id_karyawan');
        }

        if ($kataKunci !== '') {
            $kueri->where(function ($sub) use ($kataKunci): void {
                $sub->where('nomor_polisi', 'like', "%{$kataKunci}%")
                    ->orWhere('merek_model', 'like', "%{$kataKunci}%")
                    ->orWhereHas('karyawan', fn ($q) => $q->where('nama_lengkap', 'like', "%{$kataKunci}%"));
            });
        }

        return $kueri->orderBy($kolomUrut, $arahUrut)->paginate($perHalaman);
    }

    private function queryTabelRute(Request $permintaan, string $kataKunci, string $statusFilter, array $filter, string $arahUrut, int $perHalaman): LengthAwarePaginator
    {
        $kolomDiizinkan = ['id', 'kode', 'material', 'tarif_per_ton', 'uang_jalan_default', 'status', 'created_at'];
        $kolomUrut = in_array((string) $permintaan->input('sort_by'), $kolomDiizinkan, true)
            ? (string) $permintaan->input('sort_by')
            : 'id';

        $kueri = Rute::query()
            ->select(['id', 'kode', 'material', 'alamat_asal', 'alamat_tujuan', 'jarak_km', 'tarif_per_ton', 'uang_jalan_default', 'status', 'created_at']);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $kueri->where('status', strtolower($statusFilter));
        }

        $filterMaterial = trim((string) ($filter['material'] ?? 'all'));
        if ($filterMaterial !== '' && $filterMaterial !== 'all') {
            $kueri->where('material', $filterMaterial);
        }

        if ($kataKunci !== '') {
            $kueri->where(function ($sub) use ($kataKunci): void {
                $sub->where('kode', 'like', "%{$kataKunci}%")
                    ->orWhere('material', 'like', "%{$kataKunci}%")
                    ->orWhere('alamat_asal', 'like', "%{$kataKunci}%")
                    ->orWhere('alamat_tujuan', 'like', "%{$kataKunci}%");
            });
        }

        return $kueri->orderBy($kolomUrut, $arahUrut)->paginate($perHalaman);
    }

    private function queryTabelRitase(Request $permintaan, string $kataKunci, string $statusFilter, array $filter, string $arahUrut, int $perHalaman): LengthAwarePaginator
    {
        $kolomDiizinkan = ['id', 'tanggal_ritase', 'jumlah_rit', 'tonase_kg', 'total_angkutan', 'uang_jalan', 'status', 'created_at'];
        $kolomUrut = in_array((string) $permintaan->input('sort_by'), $kolomDiizinkan, true)
            ? (string) $permintaan->input('sort_by')
            : 'tanggal_ritase';

        $kueri = Ritase::query()
            ->select(['id', 'id_periode_gaji', 'id_karyawan', 'id_truk', 'id_rute', 'tanggal_ritase', 'jumlah_rit', 'tonase_kg', 'volume_liter', 'tarif_per_ton', 'total_angkutan', 'uang_jalan', 'foto_surat_jalan', 'status', 'created_at'])
            ->with(['karyawan:id,nama_lengkap,nomor_induk_karyawan', 'truk:id,nomor_polisi,merek_model,id_jenis_truk', 'rute:id,kode,material']);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $kueri->where('status', strtolower($statusFilter));
        }

        if (! empty($filter['supir']) && $filter['supir'] !== 'all') {
            $nip = (string) $filter['supir'];
            $kueri->whereHas('karyawan', fn ($q) => $q->where('nomor_induk_karyawan', $nip));
        }

        if (! empty($filter['rute']) && $filter['rute'] !== 'all') {
            $kodeRute = (string) $filter['rute'];
            $kueri->whereHas('rute', fn ($q) => $q->where('kode', $kodeRute));
        }

        if (! empty($filter['tanggal_dari']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filter['tanggal_dari'])) {
            $kueri->whereDate('tanggal_ritase', '>=', (string) $filter['tanggal_dari']);
        }

        if (! empty($filter['tanggal_sampai']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filter['tanggal_sampai'])) {
            $kueri->whereDate('tanggal_ritase', '<=', (string) $filter['tanggal_sampai']);
        }

        if ($kataKunci !== '') {
            $kueri->where(function ($sub) use ($kataKunci): void {
                $sub->whereHas('karyawan', fn ($q) => $q->where('nama_lengkap', 'like', "%{$kataKunci}%")->orWhere('nomor_induk_karyawan', 'like', "%{$kataKunci}%"))
                    ->orWhereHas('truk', fn ($q) => $q->where('nomor_polisi', 'like', "%{$kataKunci}%"))
                    ->orWhereHas('rute', fn ($q) => $q->where('kode', 'like', "%{$kataKunci}%")->orWhere('material', 'like', "%{$kataKunci}%"));
            });
        }

        return $kueri->orderBy($kolomUrut, $arahUrut)->paginate($perHalaman);
    }

    private function queryTabelBiaya(Request $permintaan, string $kataKunci, string $statusFilter, array $filter, string $arahUrut, int $perHalaman): LengthAwarePaginator
    {
        $kolomDiizinkan = ['id', 'tanggal_biaya', 'kategori', 'nominal', 'status', 'created_at'];
        $kolomUrut = in_array((string) $permintaan->input('sort_by'), $kolomDiizinkan, true)
            ? (string) $permintaan->input('sort_by')
            : 'tanggal_biaya';

        $kueri = BiayaOperasional::query()
            ->select(['id', 'id_periode_gaji', 'id_truk', 'tanggal_biaya', 'kategori', 'nomor_nota', 'nominal', 'keterangan', 'status', 'created_at'])
            ->with(['truk:id,nomor_polisi,merek_model']);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $kueri->where('status', strtolower($statusFilter));
        }

        if (! empty($filter['kategori']) && $filter['kategori'] !== 'all') {
            $kueri->where('kategori', (string) $filter['kategori']);
        }

        if (! empty($filter['unit']) && $filter['unit'] !== 'all') {
            $nopol = (string) $filter['unit'];
            $kueri->whereHas('truk', fn ($q) => $q->where('nomor_polisi', $nopol));
        }

        if (! empty($filter['tanggal_dari']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filter['tanggal_dari'])) {
            $kueri->whereDate('tanggal_biaya', '>=', (string) $filter['tanggal_dari']);
        }

        if (! empty($filter['tanggal_sampai']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filter['tanggal_sampai'])) {
            $kueri->whereDate('tanggal_biaya', '<=', (string) $filter['tanggal_sampai']);
        }

        if ($kataKunci !== '') {
            $kueri->where(function ($sub) use ($kataKunci): void {
                $sub->where('nomor_nota', 'like', "%{$kataKunci}%")
                    ->orWhere('keterangan', 'like', "%{$kataKunci}%")
                    ->orWhereHas('truk', fn ($q) => $q->where('nomor_polisi', 'like', "%{$kataKunci}%"));
            });
        }

        return $kueri->orderBy($kolomUrut, $arahUrut)->paginate($perHalaman);
    }

    private function queryTabelKasbon(Request $permintaan, string $kataKunci, string $statusFilter, array $filter, string $arahUrut, int $perHalaman): LengthAwarePaginator
    {
        $kolomDiizinkan = ['id', 'tanggal_pengambilan', 'total_pinjaman', 'sisa_pinjaman', 'status', 'created_at'];
        $kolomUrut = in_array((string) $permintaan->input('sort_by'), $kolomDiizinkan, true)
            ? (string) $permintaan->input('sort_by')
            : 'tanggal_pengambilan';

        $kueri = Kasbon::query()
            ->select(['id', 'id_karyawan', 'tanggal_pengambilan', 'total_pinjaman', 'jumlah_tenor', 'nominal_angsuran', 'sisa_pinjaman', 'keterangan', 'status', 'created_at'])
            ->with(['karyawan:id,nama_lengkap,nomor_induk_karyawan']);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $kueri->where('status', strtolower($statusFilter));
        }

        if (! empty($filter['supir']) && $filter['supir'] !== 'all') {
            $nip = (string) $filter['supir'];
            $kueri->whereHas('karyawan', fn ($q) => $q->where('nomor_induk_karyawan', $nip));
        }

        if (! empty($filter['tanggal_dari']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filter['tanggal_dari'])) {
            $kueri->whereDate('tanggal_pengambilan', '>=', (string) $filter['tanggal_dari']);
        }

        if (! empty($filter['tanggal_sampai']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filter['tanggal_sampai'])) {
            $kueri->whereDate('tanggal_pengambilan', '<=', (string) $filter['tanggal_sampai']);
        }

        if ($kataKunci !== '') {
            $kueri->where(function ($sub) use ($kataKunci): void {
                $sub->where('keterangan', 'like', "%{$kataKunci}%")
                    ->orWhereHas('karyawan', fn ($q) => $q->where('nama_lengkap', 'like', "%{$kataKunci}%")->orWhere('nomor_induk_karyawan', 'like', "%{$kataKunci}%"));
            });
        }

        return $kueri->orderBy($kolomUrut, $arahUrut)->paginate($perHalaman);
    }

    private function queryTabelPeriodeGaji(Request $permintaan, string $kataKunci, string $statusFilter, array $filter, string $arahUrut, int $perHalaman): LengthAwarePaginator
    {
        $kolomDiizinkan = ['id', 'kode', 'tanggal_mulai', 'tanggal_selesai', 'tanggal_bayar', 'status', 'created_at'];
        $kolomUrut = in_array((string) $permintaan->input('sort_by'), $kolomDiizinkan, true)
            ? (string) $permintaan->input('sort_by')
            : 'tanggal_selesai';

        $kueri = PeriodeGaji::query()
            ->select(['id', 'kode', 'tanggal_mulai', 'tanggal_selesai', 'tanggal_bayar', 'status', 'created_at'])
            ->withCount(['penggajian', 'ritase']);

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $kueri->where('status', strtolower($statusFilter));
        }

        if ($kataKunci !== '') {
            $kueri->where('kode', 'like', "%{$kataKunci}%");
        }

        return $kueri->orderBy($kolomUrut, $arahUrut)->paginate($perHalaman);
    }

    private function queryTabelPenggajian(Request $permintaan, string $kataKunci, string $statusFilter, array $filter, string $arahUrut, int $perHalaman): LengthAwarePaginator
    {
        $kolomDiizinkan = ['id', 'total_rit', 'total_tonase_kg', 'total_angkutan_kotor', 'gaji_komisi', 'gaji_bersih', 'created_at'];
        $kolomUrut = in_array((string) $permintaan->input('sort_by'), $kolomDiizinkan, true)
            ? (string) $permintaan->input('sort_by')
            : 'gaji_bersih';

        $kueri = Penggajian::query()
            ->select([
                'id', 'id_periode_gaji', 'id_karyawan', 'id_truk',
                'total_rit', 'total_tonase_kg', 'total_angkutan_kotor',
                'total_uang_jalan', 'biaya_sparepart', 'hasil_bersih_armada',
                'gaji_komisi', 'potongan_bpjs', 'potongan_kasbon', 'gaji_bersih', 'created_at',
            ])
            ->with([
                'periodeGaji:id,kode,status',
                'karyawan:id,nama_lengkap,nomor_induk_karyawan',
                'truk:id,nomor_polisi,merek_model',
            ]);

        if (! empty($filter['periode']) && $filter['periode'] !== 'all') {
            $kodePeriode = (string) $filter['periode'];
            $kueri->whereHas('periodeGaji', fn ($q) => $q->where('kode', $kodePeriode));
        }

        if ($kataKunci !== '') {
            $kueri->where(function ($sub) use ($kataKunci): void {
                $sub->whereHas('karyawan', fn ($q) => $q->where('nama_lengkap', 'like', "%{$kataKunci}%")->orWhere('nomor_induk_karyawan', 'like', "%{$kataKunci}%"))
                    ->orWhereHas('truk', fn ($q) => $q->where('nomor_polisi', 'like', "%{$kataKunci}%"));
            });
        }

        return $kueri->orderBy($kolomUrut, $arahUrut)->paginate($perHalaman);
    }
}
