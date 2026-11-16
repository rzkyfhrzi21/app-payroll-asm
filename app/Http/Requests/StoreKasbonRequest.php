<?php

namespace App\Http\Requests;

use App\Models\Kasbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreKasbonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Kasbon diambil sebagian di awal lalu dipotong dari gaji pada tanggal gajian.
     * Maksimal Rp 1.000.000 dengan cicilan paling lama 3 bulan.
     */
    public function rules(): array
    {
        return [
            'id_karyawan' => ['required', 'integer', 'exists:karyawan,id'],
            'total_pinjaman' => ['required', 'integer', 'min:50000', 'max:'.Kasbon::BATAS_MAKSIMAL],
            'jumlah_tenor' => ['required', 'integer', 'min:1', 'max:'.Kasbon::TENOR_MAKSIMAL],
            'tanggal_pengambilan' => ['required', 'date'],
            'id_periode_gaji_mulai' => ['nullable', 'integer', 'exists:periode_gaji,id'],
            'keterangan' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_karyawan.required' => 'Supir wajib dipilih.',
            'total_pinjaman.required' => 'Nominal kasbon wajib diisi.',
            'total_pinjaman.min' => 'Nominal kasbon minimal Rp 50.000.',
            'total_pinjaman.max' => 'Nominal kasbon maksimal Rp '.number_format(Kasbon::BATAS_MAKSIMAL, 0, ',', '.').'.',
            'jumlah_tenor.required' => 'Jumlah cicilan wajib dipilih.',
            'jumlah_tenor.max' => 'Cicilan maksimal '.Kasbon::TENOR_MAKSIMAL.' bulan.',
            'tanggal_pengambilan.required' => 'Tanggal pengambilan wajib diisi.',
            'tanggal_pengambilan.date' => 'Format tanggal tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'total_pinjaman' => $this->normalisasiRupiah($this->input('total_pinjaman')),
            'id_periode_gaji_mulai' => $this->input('id_periode_gaji_mulai') ?: null,
        ]);
    }

    private function normalisasiRupiah(mixed $nilai): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (int) preg_replace('/\D/', '', (string) $nilai);
    }
}
