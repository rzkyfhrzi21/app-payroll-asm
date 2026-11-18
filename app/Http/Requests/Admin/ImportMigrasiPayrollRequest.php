<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI IMPORT SINKRONISASI BERKAS EXCEL GAJI
 * ==============================================================================
 * Kelas ini memeriksa saat Admin mengunggah berkas Excel/CSV rekap penggajian supir:
 * 1. Berkas wajib berupa file spreadsheet dengan ukuran maksimal 25 MB.
 * 2. Target periode wajib ditentukan (apakah periode yang sudah ada atau membuat periode baru).
 * 3. Status pencairan dan centang pernyataan konfirmasi wajib disetujui pengguna.
 * ==============================================================================
 */
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
            'berkas_excel' => ['required', 'file', 'max:25600', 'extensions:xlsx,csv,txt'], // Maksimal 25 MB, hanya format yang didukung parser
            'target_periode' => ['required', 'string', 'max:40'],                               // Kode periode target
            'kode_periode_baru' => ['nullable', 'required_if:target_periode,baru', 'string', 'max:30'], // Kode jika buat baru
            'status_pencairan' => ['required', 'string', 'in:belum_cair,sudah_cair'],           // Status pencairan gaji
            'tanggal_pencairan' => ['nullable', 'date'],                                       // Tanggal pencairan dana
            'konfirmasi_pencairan' => ['nullable'],
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
            'berkas_excel.extensions' => 'Format berkas harus .xlsx, .csv, atau .txt. Berkas .xls lama harap disimpan ulang sebagai .xlsx.',
            'target_periode.required' => 'Pilihan periode penggajian target wajib dipilih.',
            'kode_periode_baru.required_if' => 'Kode periode baru wajib diisi apabila memilih buat siklus periode baru.',
            'status_pencairan.required' => 'Status pencairan gaji wajib dipilih (Sudah Dicairkan atau Belum Dicairkan).',
            'status_pencairan.in' => 'Status pencairan gaji tidak valid.',
            'konfirmasi_pencairan.accepted' => 'Anda wajib mencentang pernyataan konfirmasi status pencairan dana gajian.',
        ];
    }
}
