<?php

namespace App\Http\Requests;

use App\Models\BiayaOperasional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PENCATATAN BIAYA OPERASIONAL ARMADA
 * ==============================================================================
 * Kelas ini memeriksa saat Admin mencatat pengeluaran uang jalan atau bengkel armada:
 * 1. Unit truk wajib dipilih (harus terdaftar di database).
 * 2. Kategori pengeluaran harus sah (sparepart, servis, oli, ban, retribusi, bbm).
 *    Catatan: Pengeluaran sparepart/servis akan menjadi faktor pemotong komisi supir!
 * 3. Nominal rupiah dibersihkan dari titik/koma dan wajib lebih dari 0.
 * ==============================================================================
 */
class StoreBiayaOperasionalRequest extends FormRequest
{
    /**
     * Memastikan hanya Admin yang boleh menginput pengeluaran operasional.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Aturan validasi pengeluaran operasional baru.
     */
    public function rules(): array
    {
        return [
            'id_periode_gaji' => ['required', 'integer', 'exists:periode_gaji,id'],                   // Periode pembukuan
            'id_truk' => ['required', 'integer', 'exists:truk,id'],                                   // Unit truk yang keluar biaya
            'id_karyawan' => ['nullable', 'integer', 'exists:karyawan,id'],                           // Supir yang membawa (opsional)
            'tanggal_biaya' => ['required', 'date'],                                                  // Tanggal transaksi nota
            'kategori' => ['required', Rule::in(array_keys(BiayaOperasional::daftarKategori()))],     // Kategori biaya
            'keterangan' => ['nullable', 'string', 'max:200'],                                        // Deskripsi keperluan
            'nominal' => ['required', 'integer', 'min:1'],                                            // Nominal biaya bulat
            'nomor_nota' => ['nullable', 'string', 'max:50'],                                         // Nomor kuitansi / nota
            'status' => ['required', Rule::in(array_keys(BiayaOperasional::daftarStatus()))],         // Status lunas / pending
            'foto_nota' => ['nullable', 'file', 'max:2048'],                                          // Foto kuitansi maks 2 MB
        ];
    }

    public function messages(): array
    {
        return [
            'id_truk.required' => 'Unit armada wajib dipilih.',
            'tanggal_biaya.required' => 'Tanggal pengeluaran wajib diisi.',
            'kategori.required' => 'Kategori pengeluaran wajib dipilih.',
            'nominal.required' => 'Nominal pengeluaran wajib diisi.',
            'nominal.min' => 'Nominal pengeluaran harus lebih dari 0.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nominal' => $this->input('nominal') !== null && $this->input('nominal') !== ''
                ? (int) preg_replace('/\D/', '', (string) $this->input('nominal'))
                : null,
            'id_karyawan' => $this->input('id_karyawan') ?: null,
        ]);
    }
}
