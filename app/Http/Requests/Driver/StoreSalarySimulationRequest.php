<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalarySimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupir() === true;
    }

    public function rules(): array
    {
        return [
            'nama_simulasi' => ['required', 'string', 'max:150'],
            'id_periode_gaji' => ['nullable', 'integer', 'exists:periode_gaji,id'],
            'mode_potongan' => ['required', 'string', 'in:riil,manual'],
            'surat_jalan' => ['required', 'array', 'min:1', 'max:31'],
            'surat_jalan.*.rute_id' => ['nullable', 'integer', 'exists:rute,id'],
            'surat_jalan.*.nama_rute' => ['required', 'string', 'max:150'],
            'surat_jalan.*.tonase_ton' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'surat_jalan.*.tarif_per_ton' => ['required', 'integer', 'min:0'],
            'surat_jalan.*.uang_jalan' => ['required', 'integer', 'min:0'],
            'biaya_sparepart' => ['nullable', 'integer', 'min:0'],
            'potongan_bpjs' => ['nullable', 'integer', 'min:0'],
            'potongan_kasbon' => ['nullable', 'integer', 'min:0'],
            'total_tunjangan' => ['nullable', 'integer', 'min:0'],
            'potongan_lain' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_simulasi.required' => 'Nama atau judul simulasi wajib diisi.',
            'nama_simulasi.max' => 'Nama simulasi maksimal 150 karakter.',
            'surat_jalan.required' => 'Daftar surat jalan simulasi wajib diisi minimal 1 surat jalan.',
            'surat_jalan.min' => 'Minimal 1 baris surat jalan untuk simulasi.',
            'surat_jalan.max' => 'Simulasi surat jalan dibatasi maksimal 31 pengantaran per bulan.',
            'surat_jalan.*.nama_rute.required' => 'Nama rute pada setiap surat jalan wajib terisi.',
            'surat_jalan.*.tonase_ton.required' => 'Tonase muatan wajib diisi.',
            'surat_jalan.*.tonase_ton.min' => 'Tonase muatan minimal 0,01 ton.',
            'surat_jalan.*.tonase_ton.max' => 'Tonase muatan maksimal 100 ton per surat jalan.',
            'surat_jalan.*.tarif_per_ton.required' => 'Tarif per ton wajib diisi.',
            'surat_jalan.*.uang_jalan.required' => 'Uang jalan wajib diisi.',
        ];
    }
}
