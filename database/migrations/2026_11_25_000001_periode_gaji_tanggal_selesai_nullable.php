<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Periode gaji kini mengikuti tutup buku (cut-off) manual, bukan paksa tanggal 1 s/d akhir bulan.
 *
 * tanggal_selesai = NULL berarti periode masih BERJALAN (belum ditutup Admin).
 * Saat tutup buku, Admin mengisi tanggal_selesai = hari ini, lalu sistem otomatis
 * membuat periode berikutnya mulai H+1 agar tidak ada tanggal yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('periode_gaji')) {
            return;
        }

        Schema::table('periode_gaji', function (Blueprint $table): void {
            $table->date('tanggal_selesai')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('periode_gaji')) {
            return;
        }

        // Kembalikan NOT NULL; baris yang masih NULL diisi tanggal hari ini agar aman.
        \Illuminate\Support\Facades\DB::table('periode_gaji')
            ->whereNull('tanggal_selesai')
            ->update(['tanggal_selesai' => now()->toDateString()]);

        Schema::table('periode_gaji', function (Blueprint $table): void {
            $table->date('tanggal_selesai')->nullable(false)->change();
        });
    }
};
