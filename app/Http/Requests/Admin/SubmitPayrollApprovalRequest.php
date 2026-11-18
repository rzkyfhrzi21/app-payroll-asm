<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI BATAS KEWENANGAN OTORISASI ADMIN (IN:REJECT ONLY)
 * ==============================================================================
 * Kelas ini merupakan penjaga gawang aturan ketat otorisasi (Sesuai AGENTS.md Bab 3.2):
 * 1. Admin/HRD DILARANG KERAS memberikan ACC pada periode penggajian.
 * 2. Endpoint Admin `/admin/payroll/{periode}/approval` HANYA MENERIMA mode `reject`
 *    (menarik pengajuan atau meminta revisi).
 * 3. Setiap permintaan mode `approve` dari Admin wajib ditolak mentah-mentah (422) di sini!
 * ==============================================================================
 */
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
            // Persetujuan (approve) adalah kewenangan mutlak Pimpinan/Direksi!
            'mode' => ['required', 'string', 'in:reject'],                                       // Admin hanya boleh 'reject'
            'alasan_penolakan' => ['nullable', 'required_if:mode,reject', 'string', 'max:500'], // Catatan revisi wajib
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
