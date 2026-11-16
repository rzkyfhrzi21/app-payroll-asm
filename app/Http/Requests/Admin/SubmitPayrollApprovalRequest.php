<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPayrollApprovalRequest extends FormRequest
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
            // Hanya penarikan pengajuan yang boleh dilakukan Admin/HRD.
            // Persetujuan (approve) adalah kewenangan Pimpinan/Direksi.
            'mode' => ['required', 'string', 'in:reject'],
            'alasan_penolakan' => ['nullable', 'required_if:mode,reject', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mode.required' => 'Mode keputusan wajib dipilih.',
            'mode.in' => 'Admin/HRD hanya dapat menarik pengajuan. Persetujuan adalah kewenangan Pimpinan/Direksi.',
            'alasan_penolakan.required_if' => 'Alasan penarikan atau catatan revisi wajib diisi.',
            'alasan_penolakan.max' => 'Alasan revisi maksimal 500 karakter.',
        ];
    }
}
