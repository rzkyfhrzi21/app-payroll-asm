<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PERUBAHAN DATA SURAT JALAN / RITASE
 * ==============================================================================
 * Kelas ini memeriksa keabsahan saat Admin mengedit surat jalan yang sudah ada
 * (misal merevisi tonase timbangan akhir pelabuhan, menambah foto bukti, atau mengubah status).
 * ==============================================================================
 */
class UpdateRitaseRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang berhak memperbarui surat jalan.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi perubahan surat jalan.
     */
    public function rules(): array
    {
        $aturanFoto = is_array($this->file('foto_surat_jalan')) || is_array($this->input('foto_surat_jalan'))
            ? [
                'foto_surat_jalan' => ['nullable', 'array', 'max:10'],            // Maksimal 10 foto
                'foto_surat_jalan.*' => ['file', 'image', 'max:2048'],           // Maksimal 2 MB per file
            ]
            : [
                'foto_surat_jalan' => ['nullable', 'file', 'image', 'max:2048'],  // 1 file maksimal 2 MB
            ];

        return array_merge([
            'id_karyawan' => ['required', 'integer', 'exists:karyawan,id'],         // Supir harus sah
            'id_truk' => ['required', 'integer', 'exists:truk,id'],                 // Unit armada harus sah
            'id_rute' => ['required', 'integer', 'exists:rute,id'],                 // Rute harus sah
            'tanggal_ritase' => ['required', 'date'],                               // Tanggal pengantaran
            'jumlah_rit' => ['required', 'integer', 'min:1', 'max:20'],             // Jumlah trip
            'tonase_ton' => ['required', 'numeric', 'min:0.01', 'max:100'],         // Tonase muatan
            'tarif_per_ton' => ['nullable', 'integer', 'min:1'],                    // Tarif angkut
            'uang_jalan' => ['nullable', 'integer', 'min:0'],                       // Uang jalan
            'nama_penerima' => ['nullable', 'string', 'max:120'],                   // Nama penerima
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
