<?php

namespace App\Http\Requests;

use App\Support\FormatNomorTelepon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * NIP tidak boleh diubah karena menjadi acuan slip & audit.
     */
    public function rules(): array
    {
        $karyawan = $this->route('employee');

        return [
            'nama_lengkap' => ['required', 'string', 'min:3', 'max:150'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'persentase_komisi' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nama_bank' => ['nullable', 'string', 'max:50'],
            'nomor_rekening' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]{6,20}$/'],
            'potongan_bpjs_bulanan' => ['nullable', 'integer', 'min:0'],
            'nomor_telepon' => ['nullable', 'string', 'regex:'.FormatNomorTelepon::POLA_VALIDASI],
            'foto_profil' => ['nullable', 'file', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi minimal 3 karakter.',
            'nama_lengkap.min' => 'Nama lengkap wajib diisi minimal 3 karakter.',
            'persentase_komisi.max' => 'Persentase komisi harus antara 0 sampai 100.',
            'persentase_komisi.min' => 'Persentase komisi harus antara 0 sampai 100.',
            'nomor_rekening.regex' => 'Nomor rekening harus 6 sampai 20 digit angka.',
            'nomor_telepon.regex' => 'Nomor telepon harus diawali angka 8 setelah kode +62 dan hanya berisi angka (contoh: 812-8930-1123).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_rekening' => preg_replace('/\D/', '', (string) $this->input('nomor_rekening')) ?: null,
            'persentase_komisi' => $this->normalisasiAngka($this->input('persentase_komisi')),
            'potongan_bpjs_bulanan' => $this->normalisasiAngka($this->input('potongan_bpjs_bulanan')),
        ]);

        if ($this->has('nomor_telepon')) {
            $this->merge([
                'nomor_telepon' => FormatNomorTelepon::keStandar($this->input('nomor_telepon')),
            ]);
        }
    }

    private function normalisasiAngka(mixed $nilai): ?float
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        $teks = preg_replace('/[^0-9,.-]/', '', (string) $nilai);
        $teks = str_replace('.', '', $teks);
        $teks = str_replace(',', '.', $teks);

        return is_numeric($teks) ? (float) $teks : null;
    }
}
