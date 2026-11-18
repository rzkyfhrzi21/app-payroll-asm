<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteraksiTabelPeran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * ============================================================================
 * CONTROLLER: ENDPOINT AJAX TABEL UNTUK SELURUH PERAN
 * ============================================================================
 *
 * Analogi Sederhana:
 * Satu loket pelayanan yang melayani tiga jenis pelanggan (Admin, Pimpinan, Supir).
 * Loket memeriksa kartu identitas pelanggan, lalu hanya menyerahkan map data yang
 * boleh dilihat pelanggan tersebut.
 *
 * Kunci desain: baris tabel dirender memakai FRAGMEN BLADE YANG SAMA dengan
 * halaman aslinya (resources/views/components/tabel-baris/*). Dengan begitu
 * markup tidak pernah terduplikasi: sekali ubah fragmen, halaman SSR dan AJAX
 * sama-sama berubah.
 *
 * Kontrak respons:
 * - rows_html : potongan HTML <tr> siap dipasang ke <tbody>
 * - meta      : current_page, last_page, per_page, total, from, to
 * ============================================================================
 */
class InteraksiTabelPeranController extends Controller
{
    use InteraksiTabelPeran;

    public function __invoke(Request $permintaan): JsonResponse
    {
        $pengguna = $permintaan->user();
        $peran = $pengguna?->role?->value ?? 'tamu';

        $entitas = strtolower(trim((string) $permintaan->input('entitas', '')));
        $entitasDiizinkan = $this->entitasDiizinkan($peran);

        if ($entitas === '' || ! in_array($entitas, $entitasDiizinkan, true)) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'title' => 'Akses Tabel Ditolak',
                'action' => 'FETCH_TABEL_DITOLAK',
                'status' => 'FORBIDDEN',
                'message' => "Peran Anda tidak diizinkan membaca tabel \"{$entitas}\".",
                'timestamp' => now()->toIso8601String(),
            ], 403);
        }

        $kataKunci = trim((string) $permintaan->input('cari', ''));
        $filter = is_array($permintaan->input('filter')) ? $permintaan->input('filter') : [];
        $perHalaman = min(max((int) ($permintaan->input('per_halaman', 25) ?: 25), 1), 100);
        $halaman = max((int) ($permintaan->input('halaman', 1) ?: 1), 1);
        $permintaan->merge(['page' => $halaman]);

        $arahUrut = strtoupper((string) $permintaan->input('sort_dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $paginator = $this->bangunQueryTabel(
            entitas: $entitas,
            peran: $peran,
            kataKunci: $kataKunci,
            filter: $filter,
            kolomUrut: (string) $permintaan->input('sort_by', ''),
            arahUrut: $arahUrut,
            perHalaman: $perHalaman,
            permintaan: $permintaan,
        );

        // Sediakan data pendukung yang dibutuhkan fragmen baris (mis. opsi dropdown).
        $dataTambahan = $this->dataPendukungFragmen($entitas, $permintaan);

        $namaFragmen = $this->namaFragmen($entitas, $peran);

        if (! View::exists($namaFragmen)) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'title' => 'Fragmen Tabel Belum Tersedia',
                'action' => 'FETCH_TABEL_GAGAL',
                'status' => 'NOT_IMPLEMENTED',
                'message' => "Tabel \"{$entitas}\" belum memiliki fragmen baris.",
                'timestamp' => now()->toIso8601String(),
            ], 501);
        }

        $rowsHtml = '';

        foreach ($paginator->items() as $indeks => $baris) {
            $rowsHtml .= View::make($namaFragmen, array_merge($dataTambahan, [
                'baris' => $baris,
                'nomor' => ($paginator->firstItem() ?? 1) + $indeks,
                'peran' => $peran,
            ]))->render();
        }

        // Hasil kosong tetap harus memberi penjelasan di dalam tabel, bukan
        // <tbody> kosong yang terlihat seperti halaman rusak.
        if ($paginator->isEmpty()) {
            $rowsHtml = View::make('components.ui.table-empty', [
                'colspan' => max((int) $permintaan->input('kolom', 10), 1),
                'label' => $this->labelEntitas($entitas),
                'tampilkanReset' => true,
            ])->render();
        }

        return response()->json([
            'success' => true,
            'type' => 'success',
            'title' => 'Data Tabel Dimuat',
            'action' => 'FETCH_TABEL_'.strtoupper($entitas),
            'status' => 'COMPLETED',
            'message' => "Berhasil memuat data tabel {$entitas}.",
            'data' => [
                'entitas' => $entitas,
                'rows_html' => $rowsHtml,
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

    /**
     * Nama fragmen baris: fragmen khusus peran dipakai lebih dulu bila tersedia
     * (mis. pimpinan hanya membaca sehingga tanpa kolom aksi).
     */
    private function namaFragmen(string $entitas, string $peran): string
    {
        $khususPeran = 'components.tabel-baris.'.$entitas.'-'.$peran;

        return View::exists($khususPeran)
            ? $khususPeran
            : 'components.tabel-baris.'.$entitas;
    }

    /**
     * Sebutan entitas untuk pesan "data tidak ditemukan".
     */
    private function labelEntitas(string $entitas): string
    {
        return match ($entitas) {
            'ritase' => 'surat jalan',
            'karyawan' => 'supir',
            'truk' => 'unit armada',
            'rute' => 'rute',
            'biaya' => 'pengeluaran',
            'kasbon' => 'kasbon',
            'penggajian' => 'penggajian supir',
            'periode_gaji' => 'periode gaji',
            default => $entitas,
        };
    }

    /**
     * Data pendukung yang dibutuhkan fragmen baris (opsi dropdown, dll).
     *
     * @return array<string, mixed>
     */
    private function dataPendukungFragmen(string $entitas, Request $permintaan): array
    {
        return match ($entitas) {
            'ritase' => ['statusOptions' => \App\Models\Ritase::daftarStatus()],
            default => [],
        };
    }
}
