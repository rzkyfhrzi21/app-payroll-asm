<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * ============================================================================
 * TRAIT: PENGATUR HALAMAN & SARINGAN TABEL (PAGINATION & FILTER HELPER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Alat Pengatur Jumlah Baris & Kaca Pembesar di Meja Kerja".
 * Saat staf admin melihat daftar tabel yang berisi ratusan supir atau ribuan
 * surat jalan, aplikasi tidak boleh menampilkan semuanya sekaligus agar komputer
 * tidak lemot.
 * 
 * Trait ini membantu controller:
 * 1. Membatasi tampilan per halaman (25, 50, atau 100 baris).
 * 2. Menangkap kata kunci ketikan di kotak pencarian.
 * 3. Menangkap pilihan filter (status aktif, kategori pengeluaran, rentang tanggal).
 * ============================================================================
 */
trait MengaturPaginasiTabel
{
    /**
     * Membaca Jumlah Baris yang Ingin Ditampilkan Per Halaman
     * Hanya mengizinkan angka resmi (25, 50, 100) demi menjaga performa database.
     */
    protected function ambilJumlahBarisPerHalaman(Request $permintaan, int $bawaan = 25): int
    {
        $nilai = (int) $permintaan->query('per_page', $bawaan);

        // Validasi whitelist: jika pengguna memasukkan angka sembarangan, kembalikan ke angka standar
        return in_array($nilai, [25, 50, 100], true) ? $nilai : $bawaan;
    }

    /**
     * Membaca Ketikan Teks dari Kotak Pencarian Tabel
     */
    protected function ambilKataKunciPencarian(Request $permintaan): string
    {
        return trim((string) $permintaan->query('cari', ''));
    }

    /**
     * Membaca Pilihan Saringan Teks (Filter Status / Kategori)
     * Jika kotak filter tidak dipilih, sistem otomatis menganggap "tampilkan semua".
     */
    protected function ambilFilterTeks(Request $permintaan, string $nama, string $bawaan = 'all'): string
    {
        $nilai = trim((string) $permintaan->query($nama, $bawaan));

        return $nilai === '' ? $bawaan : $nilai;
    }

    /**
     * Membaca Saringan Tanggal Kalender (Format Baku: YYYY-MM-DD)
     * Memastikan teks tanggal benar-benar valid sebelum dimasukkan ke query MySQL.
     */
    protected function ambilFilterTanggal(Request $permintaan, string $nama): ?string
    {
        $nilai = trim((string) $permintaan->query($nama, ''));

        // Cek pola 4 digit tahun - 2 digit bulan - 2 digit tanggal
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) === 1 ? $nilai : null;
    }
}
