<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PENAMBAHAN TRAYEK RUTE BARU
 * ==============================================================================
 * Kelas ini memeriksa data saat Admin membuat rute pengiriman batubara baru:
 * 1. Kode rute harus unik (misal: RUTE-PIT-JETTY-01).
 * 2. Tarif per ton wajib berupa angka bulat positif lebih dari 0.
 * 3. Uang jalan standar supir tidak boleh bernilai minus/negatif.
 * ==============================================================================
 */
class StoreRuteRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang boleh menambah trayek rute.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi rute pengangkutan baru.
     */
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:20', 'unique:rute,kode', 'regex:/^[A-Z0-9-]+$/'], // Kode rute unik
            'material' => ['required', 'string', 'max:50'],                                          // Jenis muatan (Batubara)
            'tarif_per_ton' => ['required', 'integer', 'min:1'],                                     // Tarif rupiah per ton
            'uang_jalan_default' => ['required', 'integer', 'min:0'],                                // Uang jalan standar
            'jarak_km' => ['nullable', 'integer', 'min:0', 'max:100000'],                            // Jarak tempuh KM
            'alamat_asal' => ['nullable', 'string', 'max:150'],                                      // Lokasi muat / pit
            'alamat_tujuan' => ['nullable', 'string', 'max:150'],                                    // Lokasi bongkar / jetty
            'status' => ['required', 'in:aktif,nonaktif'],                                           // Status rute aktif
        ];
    }

    public function messages(): array
    {
        return [
            'kode.required' => 'Kode rute wajib diisi.',
            'kode.unique' => 'Kode rute sudah digunakan.',
            'kode.regex' => 'Kode rute hanya boleh huruf, angka, dan tanda hubung.',
            'material.required' => 'Material wajib diisi.',
            'tarif_per_ton.required' => 'Tarif per ton wajib diisi dan harus lebih dari 0.',
            'tarif_per_ton.min' => 'Tarif per ton wajib diisi dan harus lebih dari 0.',
            'uang_jalan_default.min' => 'Uang jalan tidak boleh negatif.',
            'jarak_km.integer' => 'Jarak harus berupa angka kilometer.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Kapitalisasi ditangani trait NormalisasiHurufBesar pada model.
        $this->merge([
            'kode' => preg_replace('/\s+/', '-', trim((string) $this->input('kode'))),
            'material' => trim((string) $this->input('material')),
            'tarif_per_ton' => $this->normalisasiRupiah($this->input('tarif_per_ton')),
            'uang_jalan_default' => $this->normalisasiRupiah($this->input('uang_jalan_default')) ?? 0,
            'jarak_km' => $this->input('jarak_km') !== null && $this->input('jarak_km') !== ''
                ? (int) preg_replace('/\D/', '', (string) $this->input('jarak_km'))
                : null,
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
