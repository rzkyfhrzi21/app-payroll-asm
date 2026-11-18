<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PENDAFTARAN UNIT TRUK ARMADA BARU
 * ==============================================================================
 * Kelas ini bertugas memeriksa kelengkapan saat Admin mendaftarkan truk batubara baru:
 * 1. Nomor polisi wajib unik dan sesuai pola plat nomor Indonesia (misal: BE 8639 AAU).
 * 2. Wajib memilih jenis truk yang sah (Dump Truck Tronton, Trailer, Fuso).
 * 3. Supir pemegang opsional (boleh dikosongkan bila truk masih cadangan di garasi).
 * 4. Larangan Vendor: Seluruh armada adalah aset milik perusahaan sendiri.
 * ==============================================================================
 */
class StoreTrukRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang boleh menambah armada truk.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi pendaftaran truk baru.
     */
    public function rules(): array
    {
        return [
            'nomor_polisi' => ['required', 'string', 'max:15', 'unique:truk,nomor_polisi', 'regex:/^[A-Z]{1,2}\s?[0-9]{1,4}\s?[A-Z]{0,3}$/'], // Plat nomor unik
            'merek_model' => ['required', 'string', 'max:100'],                                                                                   // Merek (Hino/Fuso)
            'status' => ['required', 'in:aktif,nonaktif'],                                                                                       // Status unit
            'id_karyawan' => ['nullable', 'integer', 'exists:karyawan,id'],                                                                       // Supir pemegang
            'id_jenis_truk' => ['required', 'integer', 'exists:jenis_truk,id'],                                                                  // Jenis kapasitas tonase
            'foto_unit' => ['nullable', 'file', 'max:2048'],                                                                                    // Foto truk maks 2 MB
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_polisi.required' => 'Nomor polisi wajib diisi.',
            'nomor_polisi.unique' => 'Nomor polisi sudah terdaftar pada unit lain.',
            'nomor_polisi.regex' => 'Format nomor polisi tidak valid. Contoh: BE 8639 AAU.',
            'merek_model.required' => 'Merek & model wajib diisi.',
            'id_karyawan.exists' => 'Supir yang dipilih tidak ditemukan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Kapitalisasi ditangani trait NormalisasiHurufBesar pada model.
        $this->merge([
            'nomor_polisi' => trim((string) $this->input('nomor_polisi')),
            'merek_model' => trim((string) $this->input('merek_model')),
            'id_karyawan' => $this->input('id_karyawan') ?: null,
        ]);
    }
}
