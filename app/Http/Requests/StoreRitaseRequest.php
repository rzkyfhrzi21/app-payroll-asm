<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRitaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $aturanFoto = is_array($this->file('foto_surat_jalan')) || is_array($this->input('foto_surat_jalan'))
            ? [
                'foto_surat_jalan' => ['nullable', 'array', 'max:10'],
                'foto_surat_jalan.*' => ['file', 'image', 'max:2048'],
            ]
            : [
                'foto_surat_jalan' => ['nullable', 'file', 'image', 'max:2048'],
            ];

        return array_merge([
            'id_periode_gaji' => ['required', 'integer', 'exists:periode_gaji,id'],
            'id_karyawan' => ['required', 'integer', 'exists:karyawan,id'],
            'id_truk' => ['required', 'integer', 'exists:truk,id'],
            'id_rute' => ['required', 'integer', 'exists:rute,id'],
            'tanggal_ritase' => ['required', 'date'],
            'jumlah_rit' => ['required', 'integer', 'min:1', 'max:20'],
            'tonase_ton' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'tarif_per_ton' => ['nullable', 'integer', 'min:1'],
            'uang_jalan' => ['nullable', 'integer', 'min:0'],
            'nama_penerima' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:draft,terbit,sedang_dikirim,menunggu_verifikasi,terverifikasi,dibatalkan'],
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['string', 'max:255'],
        ], $aturanFoto);
    }

    public function messages(): array
    {
        return [
            'id_karyawan.required' => 'Supir wajib dipilih.',
            'id_truk.required' => 'Unit armada wajib dipilih.',
            'id_rute.required' => 'Rute wajib dipilih.',
            'tanggal_ritase.required' => 'Tanggal ritase wajib diisi.',
            'tonase_ton.required' => 'Tonase wajib diisi.',
            'tonase_ton.min' => 'Tonase harus lebih dari 0 ton.',
            'tonase_ton.max' => 'Tonase tidak wajar (maksimal 100 ton per baris).',
            'jumlah_rit.min' => 'Jumlah rit minimal 1.',
            'nama_penerima.max' => 'Nama penerima maksimal 120 karakter.',
            'foto_surat_jalan.array' => 'Format unggahan foto surat jalan tidak valid.',
            'foto_surat_jalan.max' => (is_array($this->file('foto_surat_jalan')) || is_array($this->input('foto_surat_jalan')))
                ? 'Maksimal 10 foto surat jalan yang dapat diunggah sekaligus.'
                : 'Ukuran foto surat jalan maksimal 2 MB.',
            'foto_surat_jalan.file' => 'Foto surat jalan harus berupa berkas gambar yang valid.',
            'foto_surat_jalan.image' => 'Foto surat jalan harus berupa gambar (JPG, PNG, atau WEBP).',
            'foto_surat_jalan.*.file' => 'Setiap foto surat jalan harus berupa berkas gambar yang valid.',
            'foto_surat_jalan.*.image' => 'Setiap foto surat jalan harus berupa gambar (JPG, PNG, atau WEBP).',
            'foto_surat_jalan.*.max' => 'Ukuran setiap foto surat jalan maksimal 2 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tonase_ton' => $this->normalisasiAngka($this->input('tonase_ton')),
            'tarif_per_ton' => $this->normalisasiRupiah($this->input('tarif_per_ton')),
            'uang_jalan' => $this->normalisasiRupiah($this->input('uang_jalan')),
            'status' => $this->filled('status') ? $this->input('status') : 'draft',
        ]);
    }

    private function normalisasiAngka(mixed $nilai): ?float
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $nilai);
    }

    private function normalisasiRupiah(mixed $nilai): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (int) preg_replace('/\D/', '', (string) $nilai);
    }
}
