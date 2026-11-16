<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status verifikasi ritase untuk rekonsiliasi timbangan.
     * Nomor surat jalan tidak disimpan — dibentuk saat tampil dari kode rute + id.
     */
    public function up(): void
    {
        Schema::table('ritase', function (Blueprint $table) {
            $table->enum('status', ['draft', 'terverifikasi', 'dibatalkan'])
                ->default('terverifikasi')
                ->after('uang_jalan');
        });
    }

    public function down(): void
    {
        Schema::table('ritase', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
