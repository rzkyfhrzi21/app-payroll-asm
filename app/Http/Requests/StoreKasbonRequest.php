<?php

namespace App\Http\Requests;

use App\Models\Kasbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PENGAJUAN PINJAMAN KASBON SUPIR
 * ==============================================================================
 * Kelas ini bertugas sebagai pemeriksa formulir permohonan kasbon di meja kasir:
 * 1. Hanya Admin yang boleh menginput pengajuan kasbon baru.
 * 2. Menerapkan batas aman keuangan perusahaan (Sesuai AGENTS.md Bab 3.6):
 *    - Kasbon adalah pinjaman uang di muka yang dipotong kembali saat gajian.
 *    - Batas maksimal pinjaman Rp 1.000.000 (tidak boleh lebih!).
 *    - Tenor cicilan maksimal 3 bulan (1, 2, atau 3 kali potong).
 * 3. Menormalkan teks rupiah (misal "Rp 1.000.000" dibersihkan menjadi integer murni 1000000).
 * ==============================================================================
 */
class StoreKasbonRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang boleh mengajukan kasbon supir.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi pengajuan kasbon baru.
     */
    public function rules(): array
    {
        return [
            'id_karyawan' => ['required', 'integer', 'exists:karyawan,id'],                                     // Supir peminjam harus terdaftar
            'total_pinjaman' => ['required', 'integer', 'min:50000', 'max:'.Kasbon::BATAS_MAKSIMAL],            // Min 50rb, Maks 1jt rupiah
            'jumlah_tenor' => ['required', 'integer', 'min:1', 'max:'.Kasbon::TENOR_MAKSIMAL],                  // Cicilan 1 s.d. 3 bulan
            'tanggal_pengambilan' => ['required', 'date'],                                                      // Tanggal uang kasbon diserahkan
            'id_periode_gaji_mulai' => ['nullable', 'integer', 'exists:periode_gaji,id'],                       // Periode pertama mulai potong gaji
            'keterangan' => ['nullable', 'string', 'max:150'],                                                  // Alasan keperluan kasbon
        ];
    }

    public function messages(): array
    {
        return [
            'id_karyawan.required' => 'Supir wajib dipilih.',
            'total_pinjaman.required' => 'Nominal kasbon wajib diisi.',
            'total_pinjaman.min' => 'Nominal kasbon minimal Rp 50.000.',
            'total_pinjaman.max' => 'Nominal kasbon maksimal Rp '.number_format(Kasbon::BATAS_MAKSIMAL, 0, ',', '.').'.',
            'jumlah_tenor.required' => 'Jumlah cicilan wajib dipilih.',
            'jumlah_tenor.max' => 'Cicilan maksimal '.Kasbon::TENOR_MAKSIMAL.' bulan.',
            'tanggal_pengambilan.required' => 'Tanggal pengambilan wajib diisi.',
            'tanggal_pengambilan.date' => 'Format tanggal tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'total_pinjaman' => $this->normalisasiRupiah($this->input('total_pinjaman')),
            'id_periode_gaji_mulai' => $this->input('id_periode_gaji_mulai') ?: null,
        ]);
    }

    private function normalisasiRupiah(mixed $nilai): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (int) preg_replace('/\D/', '', (string) $nilai);
    }
}
