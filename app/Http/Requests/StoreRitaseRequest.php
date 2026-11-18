<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PEMBUATAN SURAT JALAN / RITASE BARU
 * ==============================================================================
 * Kelas ini bekerja seperti loket penimbangan dan penerbitan tiket jalan:
 * 1. Hanya Admin HRD/Operasional yang berhak membuat Surat Jalan baru (`authorize`).
 * 2. Memeriksa kelayakan data perjalanan batubara:
 *    - Supir, truk, dan rute harus terdaftar di database (`exists`).
 *    - Muatan batubara harus masuk akal (antara 0.01 ton hingga maksimal 100 ton).
 *    - Foto surat jalan bertanda tangan maksimal 10 lembar (masing-masing maks 2 MB).
 *    - Status harus salah satu dari 6 status resmi (`draft`, `terbit`, `sedang_dikirim`, dll).
 * ==============================================================================
 */
class StoreRitaseRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang boleh menambah Surat Jalan.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi input formulir surat jalan baru.
     */
    public function rules(): array
    {
        // Mendukung upload foto satuan maupun banyak foto (array)
        $aturanFoto = is_array($this->file('foto_surat_jalan')) || is_array($this->input('foto_surat_jalan'))
            ? [
                'foto_surat_jalan' => ['nullable', 'array', 'max:10'],            // Maksimal 10 foto sekaligus
                'foto_surat_jalan.*' => ['file', 'image', 'max:2048'],           // Maksimal 2 MB per file
            ]
            : [
                'foto_surat_jalan' => ['nullable', 'file', 'image', 'max:2048'],  // Upload 1 foto maksimal 2 MB
            ];

        return array_merge([
            'id_periode_gaji' => ['required', 'integer', 'exists:periode_gaji,id'], // Wajib terikat ke periode aktif
            'id_karyawan' => ['required', 'integer', 'exists:karyawan,id'],         // Supir harus terdaftar
            'id_truk' => ['required', 'integer', 'exists:truk,id'],                 // Unit armada harus terdaftar
            'id_rute' => ['required', 'integer', 'exists:rute,id'],                 // Jalur hauling harus ada
            'tanggal_ritase' => ['required', 'date'],                               // Tanggal keberangkatan
            'jumlah_rit' => ['required', 'integer', 'min:1', 'max:20'],             // Jumlah trip dalam 1 DO
            'tonase_ton' => ['required', 'numeric', 'min:0.01', 'max:100'],         // Berat muatan batubara (Ton)
            'tarif_per_ton' => ['nullable', 'integer', 'min:1'],                    // Tarif angkut rupiah bulat
            'uang_jalan' => ['nullable', 'integer', 'min:0'],                       // Uang jalan kas/solar
            'nama_penerima' => ['nullable', 'string', 'max:120'],                   // Nama petugas timbangan penerima
            'status' => ['required', 'in:draft,terbit,sedang_dikirim,menunggu_verifikasi,terverifikasi,dibatalkan'],
            'hapus_foto' => ['nullable', 'array'],                                  // Daftar foto lama yang mau dibuang
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
