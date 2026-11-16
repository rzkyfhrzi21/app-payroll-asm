<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menyeragamkan data teks lama menjadi HURUF BESAR.
     * Kolom yang dikecualikan: email, password, role, remember_token.
     */
    public function up(): void
    {
        $target = [
            'karyawan' => ['nomor_induk_karyawan', 'nama_lengkap', 'jabatan', 'vendor', 'nama_bank', 'nomor_rekening'],
            'truk' => ['nomor_polisi', 'merek_model', 'vendor'],
            'rute' => ['kode', 'material', 'alamat_asal', 'alamat_tujuan'],
            'jenis_truk' => ['nama', 'merek_model'],
            'kasbon' => ['keterangan'],
            'users' => ['name', 'nomor_telepon', 'alamat'],
            'ritase' => ['catatan'],
        ];

        foreach ($target as $tabel => $daftarKolom) {
            foreach ($daftarKolom as $kolom) {
                if (! $this->adaKolom($tabel, $kolom)) {
                    continue;
                }

                DB::table($tabel)
                    ->whereNotNull($kolom)
                    ->update([$kolom => DB::raw('UPPER('.$kolom.')')]);
            }
        }
    }

    public function down(): void
    {
        // Tidak dapat dikembalikan: kapitalisasi asli tidak tersimpan.
    }

    private function adaKolom(string $tabel, string $kolom): bool
    {
        return DB::getSchemaBuilder()->hasColumn($tabel, $kolom);
    }
};
