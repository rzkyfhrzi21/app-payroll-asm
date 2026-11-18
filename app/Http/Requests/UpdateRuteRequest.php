<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PERUBAHAN TRAYEK RUTE ANGKUTAN
 * ==============================================================================
 * Kelas ini memeriksa perubahan data koridor rute (misal penyesuaian tarif per ton
 * karena kenaikan harga solar, atau perubahan uang jalan default).
 * ==============================================================================
 */
class UpdateRuteRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang boleh mengedit trayek rute.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi pembaruan rute.
     */
    public function rules(): array
    {
        $rute = $this->route('route');

        return [
            'kode' => [
                'required', 'string', 'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('rute', 'kode')->ignore($rute?->id), // Abaikan id rute sendiri
            ],
            'material' => ['required', 'string', 'max:50'],       // Jenis muatan batubara
            'tarif_per_ton' => ['required', 'integer', 'min:1'],  // Tarif per ton baru
            'uang_jalan_default' => ['required', 'integer', 'min:0'], // Uang jalan standar baru
            'jarak_km' => ['nullable', 'integer', 'min:0', 'max:100000'], // Jarak KM
            'alamat_asal' => ['nullable', 'string', 'max:150'],   // Alamat muat
            'alamat_tujuan' => ['nullable', 'string', 'max:150'], // Alamat bongkar
            'status' => ['required', 'in:aktif,nonaktif'],        // Status rute aktif/tutup
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
