<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $daftarIndeks = [
            'karyawan' => [
                'idx_karyawan_status_vendor' => ['status', 'vendor'],
                'idx_karyawan_created_at' => ['created_at'],
            ],
            'truk' => [
                'idx_truk_status_vendor' => ['status', 'vendor'],
                'idx_truk_created_at' => ['created_at'],
            ],
            'rute' => [
                'idx_rute_status_material' => ['status', 'material'],
                'idx_rute_created_at' => ['created_at'],
            ],
            'ritase' => [
                'idx_ritase_periode_status' => ['id_periode_gaji', 'status'],
                'idx_ritase_tanggal' => ['tanggal_ritase'],
            ],
            'biaya_operasional' => [
                'idx_biaya_periode_kat_status' => ['id_periode_gaji', 'kategori', 'status'],
                'idx_biaya_tanggal' => ['tanggal_biaya'],
            ],
            'kasbon' => [
                'idx_kasbon_karyawan_status' => ['id_karyawan', 'status'],
                'idx_kasbon_tanggal' => ['tanggal_pengambilan'],
            ],
            'periode_gaji' => [
                'idx_periode_status_selesai' => ['status', 'tanggal_selesai'],
            ],
            'penggajian' => [
                'idx_penggajian_periode_gaji_bersih' => ['id_periode_gaji', 'gaji_bersih'],
            ],
        ];

        foreach ($daftarIndeks as $namaTabel => $indeksTabel) {
            if (! Schema::hasTable($namaTabel)) {
                continue;
            }

            Schema::table($namaTabel, function (Blueprint $table) use ($namaTabel, $indeksTabel): void {
                foreach ($indeksTabel as $namaIndeks => $kolom) {
                    if (! Schema::hasIndex($namaTabel, $namaIndeks)) {
                        $table->index($kolom, $namaIndeks);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $daftarIndeks = [
            'karyawan' => ['idx_karyawan_status_vendor', 'idx_karyawan_created_at'],
            'truk' => ['idx_truk_status_vendor', 'idx_truk_created_at'],
            'rute' => ['idx_rute_status_material', 'idx_rute_created_at'],
            'ritase' => ['idx_ritase_periode_status', 'idx_ritase_tanggal'],
            'biaya_operasional' => ['idx_biaya_periode_kat_status', 'idx_biaya_tanggal'],
            'kasbon' => ['idx_kasbon_karyawan_status', 'idx_kasbon_tanggal'],
            'periode_gaji' => ['idx_periode_status_selesai'],
            'penggajian' => ['idx_penggajian_periode_gaji_bersih'],
        ];

        foreach ($daftarIndeks as $namaTabel => $indeksTabel) {
            if (! Schema::hasTable($namaTabel)) {
                continue;
            }

            Schema::table($namaTabel, function (Blueprint $table) use ($namaTabel, $indeksTabel): void {
                foreach ($indeksTabel as $namaIndeks) {
                    if (Schema::hasIndex($namaTabel, $namaIndeks)) {
                        $table->dropIndex($namaIndeks);
                    }
                }
            });
        }
    }
};
