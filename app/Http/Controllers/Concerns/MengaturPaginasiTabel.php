<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Helper bersama untuk tabel admin yang memakai paginasi server-side.
 * Semua nilai filter dibaca dari query string agar URL bisa dibagikan/di-refresh.
 */
trait MengaturPaginasiTabel
{
    /**
     * Jumlah baris per halaman yang diizinkan (whitelist ketat).
     */
    protected function ambilJumlahBarisPerHalaman(Request $permintaan, int $bawaan = 25): int
    {
        $nilai = (int) $permintaan->query('per_page', $bawaan);

        return in_array($nilai, [25, 50, 100], true) ? $nilai : $bawaan;
    }

    /**
     * Kata kunci pencarian bebas.
     */
    protected function ambilKataKunciPencarian(Request $permintaan): string
    {
        return trim((string) $permintaan->query('cari', ''));
    }

    /**
     * Nilai filter teks; string kosong dianggap "semua".
     */
    protected function ambilFilterTeks(Request $permintaan, string $nama, string $bawaan = 'all'): string
    {
        $nilai = trim((string) $permintaan->query($nama, $bawaan));

        return $nilai === '' ? $bawaan : $nilai;
    }

    /**
     * Filter tanggal ISO (YYYY-MM-DD); nilai tidak valid diabaikan.
     */
    protected function ambilFilterTanggal(Request $permintaan, string $nama): ?string
    {
        $nilai = trim((string) $permintaan->query($nama, ''));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) === 1 ? $nilai : null;
    }
}
