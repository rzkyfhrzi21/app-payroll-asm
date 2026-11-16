<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pembersih = static function (mixed $nilai): int {
            $angka = preg_replace('/\D/', '', (string) $nilai);

            return $angka === '' || $angka === null ? 0 : (int) $angka;
        };

        $dataSiap = [];
        foreach (['biaya_sparepart', 'total_tunjangan', 'potongan_bpjs'] as $kolom) {
            if ($this->has($kolom)) {
                $dataSiap[$kolom] = $pembersih($this->input($kolom));
            }
        }

        if ($dataSiap !== []) {
            $this->merge($dataSiap);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'biaya_sparepart' => ['required', 'integer', 'min:0', 'max:999999999'],
            'total_tunjangan' => ['required', 'integer', 'min:0', 'max:999999999'],
            'potongan_bpjs' => ['required', 'integer', 'min:0', 'max:999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'biaya_sparepart.required' => 'Nominal potongan sparepart wajib diisi.',
            'biaya_sparepart.integer' => 'Nominal potongan sparepart harus berupa angka bulat Rupiah.',
            'biaya_sparepart.min' => 'Nominal potongan sparepart tidak boleh negatif.',
            'total_tunjangan.required' => 'Nominal tunjangan wajib diisi.',
            'total_tunjangan.integer' => 'Nominal tunjangan harus berupa angka bulat Rupiah.',
            'total_tunjangan.min' => 'Nominal tunjangan tidak boleh negatif.',
            'potongan_bpjs.required' => 'Nominal potongan BPJS wajib diisi.',
            'potongan_bpjs.integer' => 'Nominal potongan BPJS harus berupa angka bulat Rupiah.',
            'potongan_bpjs.min' => 'Nominal potongan BPJS tidak boleh negatif.',
        ];
    }
}
