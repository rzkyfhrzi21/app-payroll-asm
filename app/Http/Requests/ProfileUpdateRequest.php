<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\FormatNomorTelepon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'nomor_telepon' => ['nullable', 'string', 'regex:'.FormatNomorTelepon::POLA_VALIDASI],
            'alamat' => ['nullable', 'string', 'max:255'],
            'foto_profil' => ['nullable', 'file', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_telepon.regex' => 'Nomor telepon harus diawali angka 8 setelah kode +62 dan hanya berisi angka (contoh: 812-8930-1123).',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nomor_telepon')) {
            $this->merge([
                'nomor_telepon' => FormatNomorTelepon::keStandar($this->input('nomor_telepon')),
            ]);
        }
    }
}
