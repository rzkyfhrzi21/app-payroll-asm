<?php

namespace App\Http\Requests;

use App\Models\Kasbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PERUBAHAN DATA KASBON SUPIR
 * ==============================================================================
 * Kelas ini memeriksa perubahan data pinjaman kasbon:
 * 1. Nominal pinjaman dan jumlah tenor cicilan DILARANG diubah saat update!
 *    Alasannya: Baris jadwal angsuran di tabel `angsuran_kasbon` sudah dibuat dan
 *    terikat dengan siklus tutup buku penggajian.
 * 2. Yang diizinkan diubah hanyalah keterangan keperluan, tanggal, periode mulai,
 *    atau status persetujuan kasbon.
 * ==============================================================================
 */
class UpdateKasbonRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang boleh memperbarui status kasbon.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi perubahan kasbon.
     */
    public function rules(): array
    {
        return [
            'keterangan' => ['nullable', 'string', 'max:150'],                                   // Catatan keterangan kasbon
            'tanggal_pengambilan' => ['required', 'date'],                                       // Tanggal kasbon
            'id_periode_gaji_mulai' => ['nullable', 'integer', 'exists:periode_gaji,id'],        // Periode awal potong gaji
            'status' => ['required', 'in:pending,approved,rejected,lunas'],                      // Status kasbon
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_pengambilan.required' => 'Tanggal pengambilan wajib diisi.',
            'status.required' => 'Status kasbon wajib dipilih.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_periode_gaji_mulai' => $this->input('id_periode_gaji_mulai') ?: null,
        ]);
    }
}
