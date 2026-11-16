<?php

namespace App\Http\Requests;

use App\Support\AturanKataSandi;
use App\Support\FormatNomorTelepon;
use Illuminate\Foundation\Http\FormRequest;

class StoreKaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi pendaftaran supir baru.
     * Email dipakai untuk akun login Portal Supir.
     */
    public function rules(): array
    {
        return [
            'nomor_induk_karyawan' => ['required', 'string', 'max:20', 'unique:karyawan,nomor_induk_karyawan'],
            'nama_lengkap' => ['required', 'string', 'min:3', 'max:150'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'persentase_komisi' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nama_bank' => ['nullable', 'string', 'max:50'],
            'nomor_rekening' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]{6,20}$/'],
            'potongan_bpjs_bulanan' => ['nullable', 'integer', 'min:0'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => AturanKataSandi::aturan(),
            'nomor_telepon' => ['nullable', 'string', 'regex:'.FormatNomorTelepon::POLA_VALIDASI],
            'foto_profil' => ['nullable', 'file', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return array_merge([
            'nomor_induk_karyawan.unique' => 'Nomor induk sudah dipakai karyawan lain.',
            'nomor_induk_karyawan.required' => 'Nomor induk karyawan wajib diisi.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi minimal 3 karakter.',
            'nama_lengkap.min' => 'Nama lengkap wajib diisi minimal 3 karakter.',
            'persentase_komisi.max' => 'Persentase komisi harus antara 0 sampai 100.',
            'persentase_komisi.min' => 'Persentase komisi harus antara 0 sampai 100.',
            'nomor_rekening.regex' => 'Nomor rekening harus 6 sampai 20 digit angka.',
            'email.unique' => 'Email sudah terdaftar. Gunakan email lain.',
            'email.required' => 'Email login supir wajib diisi.',
            'nomor_telepon.regex' => 'Nomor telepon harus diawali angka 8 setelah kode +62 dan hanya berisi angka (contoh: 812-8930-1123).',
        ], AturanKataSandi::pesan());
    }

    protected function prepareForValidation(): void
    {
        // Kapitalisasi ditangani trait NormalisasiHurufBesar pada model.
        $this->merge([
            'nomor_induk_karyawan' => trim((string) $this->input('nomor_induk_karyawan')),
            'nomor_telepon' => FormatNomorTelepon::keStandar($this->input('nomor_telepon')),
            'nomor_rekening' => preg_replace('/\D/', '', (string) $this->input('nomor_rekening')) ?: null,
            'persentase_komisi' => $this->normalisasiAngka($this->input('persentase_komisi')),
            'potongan_bpjs_bulanan' => $this->normalisasiAngka($this->input('potongan_bpjs_bulanan')),
        ]);
    }

    /**
     * Mengubah "10,50" atau "Rp 129.000" menjadi angka murni.
     */
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
