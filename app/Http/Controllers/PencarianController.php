<?php

namespace App\Http\Controllers;

use App\Services\PencarianNavigasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ============================================================================
 * CONTROLLER: KOTAK PENCARIAN CEPAT (TOPBAR SEARCH CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Asisten Pribadi di Meja Resepsionis Atas (Topbar)".
 * Ketika pengguna mengetik beberapa huruf di kotak pencarian (misal: "Hino", "Budi", "Slip"),
 * asisten ini sigap mencarikan jalan pintas ke menu atau data yang dimaksud,
 * dan langsung memilah hasilnya agar supir tidak bisa mengintip data keuangan pimpinan.
 * ============================================================================
 */
class PencarianController extends Controller
{
    /**
     * Endpoint Pencarian Cepat Global (Dipanggil via AJAX)
     * 
     * Alur Kerja Bernomor:
     * (1) Baca kata kunci yang diketik pengguna dari URL (?q=...).
     * (2) Kenali peran/role pengguna yang sedang aktif (Admin, Pimpinan, atau Supir).
     * (3) Minta bantuan PencarianNavigasiService untuk menyaring menu & data yang cocok.
     * (4) Kirimkan hasil pencarian kembali ke browser dalam format data JSON.
     */
    public function __invoke(Request $request, PencarianNavigasiService $layananPencarian): JsonResponse
    {
        // (1) Ambil teks kata kunci pencarian
        $kataKunci = (string) $request->query('q', '');

        // (2) Ambil hak akses/role pengguna saat ini
        $peran = $request->user()?->role;

        // (3) & (4) Jalankan pencarian dan kembalikan respon data JSON
        return response()->json([
            'kata_kunci' => $kataKunci,
            'peran'      => $peran?->value,
            'hasil'      => $layananPencarian->cari($kataKunci, $peran),
        ]);
    }
}
