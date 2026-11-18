<?php

namespace App\Http\Requests;

use App\Models\BiayaOperasional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PERUBAHAN BIAYA OPERASIONAL ARMADA
 * ==============================================================================
 * Kelas ini memeriksa perubahan data nota kuitansi operasional armada truk.
 * ==============================================================================
 */
class UpdateBiayaOperasionalRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang boleh memperbarui biaya operasional.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi pembaruan biaya operasional.
     */
    public function rules(): array
    {
        return [
            'id_truk' => ['required', 'integer', 'exists:truk,id'],                                   // Unit armada
            'id_karyawan' => ['nullable', 'integer', 'exists:karyawan,id'],                           // Supir pemegang
            'tanggal_biaya' => ['required', 'date'],                                                  // Tanggal transaksi
            'kategori' => ['required', Rule::in(array_keys(BiayaOperasional::daftarKategori()))],     // Kategori pengeluaran
            'keterangan' => ['nullable', 'string', 'max:200'],                                        // Deskripsi
            'nominal' => ['required', 'integer', 'min:1'],                                            // Nominal biaya baru
            'nomor_nota' => ['nullable', 'string', 'max:50'],                                         // Nomor nota
            'status' => ['required', Rule::in(array_keys(BiayaOperasional::daftarStatus()))],         // Status pembayaran
            'foto_nota' => ['nullable', 'file', 'max:2048'],                                          // Bukti nota baru
        ];
    }

    public function messages(): array
    {
        return [
            'id_truk.required' => 'Unit armada wajib dipilih.',
            'tanggal_biaya.required' => 'Tanggal pengeluaran wajib diisi.',
            'kategori.required' => 'Kategori pengeluaran wajib dipilih.',
            'nominal.required' => 'Nominal pengeluaran wajib diisi.',
            'nominal.min' => 'Nominal pengeluaran harus lebih dari 0.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nominal' => $this->input('nominal') !== null && $this->input('nominal') !== ''
                ? (int) preg_replace('/\D/', '', (string) $this->input('nominal'))
                : null,
            'id_karyawan' => $this->input('id_karyawan') ?: null,
        ]);
    }
}
