<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ritase: satu baris = satu kali angkut.
     */
    public function up(): void
    {
        Schema::create('ritase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_periode_gaji')->constrained('periode_gaji')->cascadeOnDelete();
            $table->foreignId('id_karyawan')->constrained('karyawan');
            $table->foreignId('id_truk')->constrained('truk');
            $table->foreignId('id_rute')->constrained('rute');
            $table->date('tanggal_ritase');
            $table->tinyInteger('jumlah_rit')->default(1);
            $table->bigInteger('tonase_kg');
            $table->bigInteger('volume_liter')->nullable();
            $table->bigInteger('tarif_per_ton');
            $table->bigInteger('total_angkutan');
            $table->bigInteger('uang_jalan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ritase');
    }
};
