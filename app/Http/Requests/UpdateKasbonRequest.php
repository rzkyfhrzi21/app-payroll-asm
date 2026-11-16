<?php

namespace App\Http\Requests;

use App\Models\Kasbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKasbonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Hanya keterangan dan periode mulai yang boleh diubah.
     * Nominal dan tenor dikunci karena jadwal cicilan sudah terbentuk.
     */
    public function rules(): array
    {
        return [
            'keterangan' => ['nullable', 'string', 'max:150'],
            'tanggal_pengambilan' => ['required', 'date'],
            'id_periode_gaji_mulai' => ['nullable', 'integer', 'exists:periode_gaji,id'],
            'status' => ['required', 'in:pending,approved,rejected,lunas'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_pengambilan.required' => 'Tanggal pengambilan wajib diisi.',
            'status.required' => 'Status kasbon wajib dipilih.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_periode_gaji_mulai' => $this->input('id_periode_gaji_mulai') ?: null,
        ]);
    }
}
