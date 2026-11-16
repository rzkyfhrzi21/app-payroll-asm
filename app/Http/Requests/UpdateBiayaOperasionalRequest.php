<?php

namespace App\Http\Requests;

use App\Models\BiayaOperasional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBiayaOperasionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'id_truk' => ['required', 'integer', 'exists:truk,id'],
            'id_karyawan' => ['nullable', 'integer', 'exists:karyawan,id'],
            'tanggal_biaya' => ['required', 'date'],
            'kategori' => ['required', Rule::in(array_keys(BiayaOperasional::daftarKategori()))],
            'keterangan' => ['nullable', 'string', 'max:200'],
            'nominal' => ['required', 'integer', 'min:1'],
            'nomor_nota' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(array_keys(BiayaOperasional::daftarStatus()))],
            'foto_nota' => ['nullable', 'file', 'max:2048'],
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
