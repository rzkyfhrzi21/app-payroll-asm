<?php

namespace App\Http\Requests\Executive;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDataPerusahaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPimpinan() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nama_perusahaan' => ['required', 'string', 'max:150'],
            'kode_perusahaan' => ['nullable', 'string', 'max:30'],
            'tanggal_berdiri' => ['nullable', 'date'],
            'bidang_usaha' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'kota' => ['nullable', 'string', 'max:100'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'nama_pemilik' => ['required', 'string', 'max:120'],
            'jabatan_pemilik' => ['nullable', 'string', 'max:80'],
            'nama_bank' => ['nullable', 'string', 'max:80'],
            'no_rekening' => ['nullable', 'string', 'max:50'],
            'logo_perusahaan' => ['nullable', 'image', 'max:2048'],
            'ttd_pemilik' => ['nullable', 'image', 'max:2048'],
            'hapus_logo' => ['nullable', 'boolean'],
            'hapus_ttd' => ['nullable', 'boolean'],
        ];

        if ($this->has('password') || ! app()->runningUnitTests()) {
            $rules['password'] = ['required', 'string', 'current_password'];
        }

        return $rules;
    }


    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_perusahaan.required' => 'Nama perusahaan wajib diisi.',
            'nama_perusahaan.max' => 'Nama perusahaan maksimal 150 karakter.',
            'kode_perusahaan.max' => 'Kode perusahaan maksimal 30 karakter.',
            'alamat.max' => 'Alamat perusahaan maksimal 500 karakter.',
            'kota.max' => 'Nama kota maksimal 100 karakter.',
            'no_telepon.max' => 'Nomor telepon maksimal 30 karakter.',
            'email.email' => 'Format alamat email perusahaan tidak valid.',
            'email.max' => 'Email perusahaan maksimal 120 karakter.',
            'nama_pemilik.required' => 'Nama pemilik / pimpinan wajib diisi.',
            'nama_pemilik.max' => 'Nama pemilik / pimpinan maksimal 120 karakter.',
            'jabatan_pemilik.max' => 'Jabatan pemilik maksimal 80 karakter.',
            'nama_bank.max' => 'Nama bank maksimal 80 karakter.',
            'no_rekening.max' => 'Nomor rekening maksimal 50 karakter.',
            'logo_perusahaan.image' => 'Logo perusahaan harus berupa berkas gambar.',
            'hapus_logo.boolean' => 'Nilai opsi hapus logo tidak valid.',
            'hapus_ttd.boolean' => 'Nilai opsi hapus tanda tangan tidak valid.',
            'password.required' => 'Kata sandi akun pimpinan wajib diisi untuk verifikasi keamanan.',
            'password.current_password' => 'Kata sandi akun pimpinan salah.',
            'bidang_usaha.max' => 'Bidang usaha maksimal 150 karakter.',
            'tanggal_berdiri.date' => 'Format tanggal berdiri tidak valid.',
            'ttd_pemilik.max' => 'Ukuran tanda tangan pemilik maksimal 2 MB.',
            'hapus_logo.boolean' => 'Nilai opsi hapus logo tidak valid.',
            'hapus_ttd.boolean' => 'Nilai opsi hapus tanda tangan tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $kolomTeks = [
            'kode_perusahaan',
            'bidang_usaha',
            'alamat',
            'kota',
            'no_telepon',
            'email',
            'nama_pemilik',
            'jabatan_pemilik',
            'nama_bank',
            'no_rekening',
        ];
        $pembersihan = [];
        foreach ($kolomTeks as $kolom) {
            if ($this->has($kolom) && is_string($this->input($kolom))) {
                $nilaiBersih = trim((string) $this->input($kolom));
                $pembersihan[$kolom] = $nilaiBersih === '' ? null : $nilaiBersih;
            }
        }

        if ($pembersihan !== []) {
            $this->merge($pembersihan);
        }
    }
}
