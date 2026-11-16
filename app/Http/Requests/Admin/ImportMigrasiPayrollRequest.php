<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportMigrasiPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'berkas_excel' => ['required', 'file', 'max:25600'],
            'target_periode' => ['required', 'string', 'max:40'],
            'kode_periode_baru' => ['nullable', 'required_if:target_periode,baru', 'string', 'max:30'],
            'status_pencairan' => ['required', 'string', 'in:belum_cair,sudah_cair'],
            'tanggal_pencairan' => ['nullable', 'date'],
            'konfirmasi_pencairan' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'berkas_excel.required' => 'Berkas Excel / CSV migrasi wajib dipilih terlebih dahulu.',
            'berkas_excel.file' => 'Berkas yang diunggah tidak valid.',
            'berkas_excel.max' => 'Ukuran berkas maksimal 25 MB.',
            'target_periode.required' => 'Pilihan periode penggajian target wajib dipilih.',
            'kode_periode_baru.required_if' => 'Kode periode baru wajib diisi apabila memilih buat siklus periode baru.',
            'status_pencairan.required' => 'Status pencairan gaji wajib dipilih (Sudah Dicairkan atau Belum Dicairkan).',
            'status_pencairan.in' => 'Status pencairan gaji tidak valid.',
            'konfirmasi_pencairan.accepted' => 'Anda wajib mencentang pernyataan konfirmasi status pencairan dana gajian.',
        ];
    }
}
