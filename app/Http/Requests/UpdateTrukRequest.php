<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $truk = $this->route('truck');

        return [
            'nomor_polisi' => [
                'required', 'string', 'max:15',
                'regex:/^[A-Z]{1,2}\s?[0-9]{1,4}\s?[A-Z]{0,3}$/',
                Rule::unique('truk', 'nomor_polisi')->ignore($truk?->id),
            ],
            'merek_model' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'id_karyawan' => ['nullable', 'integer', 'exists:karyawan,id'],
            'id_jenis_truk' => ['required', 'integer', 'exists:jenis_truk,id'],
            'foto_unit' => ['nullable', 'file', 'max:2048'],
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
