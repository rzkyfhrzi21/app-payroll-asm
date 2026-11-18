<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ==============================================================================
 * KOMENTAR ORANG TUA: VALIDASI PENYIMPANAN SKENARIO SIMULASI GAJI SUPIR
 * ==============================================================================
 * Kelas ini memeriksa data saat supir menyimpan skenario target ritase ke database:
 * 1. Hanya pengguna bertipe Supir (`isSupir() === true`) yang berhak memanggil endpoint ini.
 * 2. Baris surat jalan simulasi dibatasi antara 1 sampai 31 pengantaran (sesuai hari dalam sebulan).
 * 3. Tonase per surat jalan harus masuk akal (0.01 ton hingga 100 ton per baris).
 * ==============================================================================
 */
class StoreSalarySimulationRequest extends FormRequest
{
    /**
     * Memastikan hanya supir yang boleh menyimpan simulasi gaji miliknya.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSupir() === true;
    }

    /**
     * Aturan validasi penyimpanan skenario simulasi gaji.
     */
    public function rules(): array
    {
        return [
            'nama_simulasi' => ['required', 'string', 'max:150'],                                      // Judul simulasi target supir
            'id_periode_gaji' => ['nullable', 'integer', 'exists:periode_gaji,id'],                     // Periode rujukan
            'mode_potongan' => ['required', 'string', 'in:riil,manual'],                               // Mode potongan kasbon/bpjs
            'surat_jalan' => ['required', 'array', 'min:1', 'max:31'],                                  // Minimal 1, maksimal 31 rit
            'surat_jalan.*.rute_id' => ['nullable', 'integer', 'exists:rute,id'],                      // ID rute master
            'surat_jalan.*.nama_rute' => ['required', 'string', 'max:150'],                             // Nama rute
            'surat_jalan.*.tonase_ton' => ['required', 'numeric', 'min:0.01', 'max:100'],               // Tonase (Ton)
            'surat_jalan.*.tarif_per_ton' => ['required', 'integer', 'min:0'],                          // Tarif angkut
            'surat_jalan.*.uang_jalan' => ['required', 'integer', 'min:0'],                             // Uang saku jalan
            'biaya_sparepart' => ['nullable', 'integer', 'min:0'],                                      // Potongan sparepart
            'potongan_bpjs' => ['nullable', 'integer', 'min:0'],                                        // Potongan BPJS
            'potongan_kasbon' => ['nullable', 'integer', 'min:0'],                                      // Potongan kasbon
            'total_tunjangan' => ['nullable', 'integer', 'min:0'],                                      // Tunjangan bonus
            'potongan_lain' => ['nullable', 'integer', 'min:0'],                                        // Potongan lain-lain
            'catatan' => ['nullable', 'string', 'max:500'],                                             // Catatan pribadi supir
        ];
    }

    public function messages(): array
    {
        return [
            'nama_simulasi.required' => 'Nama atau judul simulasi wajib diisi.',
            'nama_simulasi.max' => 'Nama simulasi maksimal 150 karakter.',
            'surat_jalan.required' => 'Daftar surat jalan simulasi wajib diisi minimal 1 surat jalan.',
            'surat_jalan.min' => 'Minimal 1 baris surat jalan untuk simulasi.',
            'surat_jalan.max' => 'Simulasi surat jalan dibatasi maksimal 31 pengantaran per bulan.',
            'surat_jalan.*.nama_rute.required' => 'Nama rute pada setiap surat jalan wajib terisi.',
            'surat_jalan.*.tonase_ton.required' => 'Tonase muatan wajib diisi.',
            'surat_jalan.*.tonase_ton.min' => 'Tonase muatan minimal 0,01 ton.',
            'surat_jalan.*.tonase_ton.max' => 'Tonase muatan maksimal 100 ton per surat jalan.',
            'surat_jalan.*.tarif_per_ton.required' => 'Tarif per ton wajib diisi.',
            'surat_jalan.*.uang_jalan.required' => 'Uang jalan wajib diisi.',
        ];
    }
}
