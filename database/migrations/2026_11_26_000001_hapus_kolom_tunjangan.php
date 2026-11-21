<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan sementara: fitur tunjangan dihapus dari sistem penggajian.
     *
     * Kolom `total_tunjangan` pada `penggajian` dan `simulasi_gaji` tidak lagi
     * dipakai karena seluruh komponen tunjangan dihapus dari perhitungan,
     * antarmuka, laporan, dan impor/ekspor Excel. Seluruh nilai lama memang 0.
     */
    public function up(): void
    {
        foreach (['penggajian', 'simulasi_gaji'] as $tabel) {
            if (Schema::hasTable($tabel) && Schema::hasColumn($tabel, 'total_tunjangan')) {
                Schema::table($tabel, function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('total_tunjangan');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['penggajian', 'simulasi_gaji'] as $tabel) {
            if (Schema::hasTable($tabel) && ! Schema::hasColumn($tabel, 'total_tunjangan')) {
                Schema::table($tabel, function (Blueprint $blueprint): void {
                    $blueprint->bigInteger('total_tunjangan')->default(0);
                });
            }
        }
    }
};
