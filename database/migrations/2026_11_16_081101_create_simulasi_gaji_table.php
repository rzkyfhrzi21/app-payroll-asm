<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('simulasi_gaji', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('karyawan')->cascadeOnDelete();
            $table->foreignId('id_periode_gaji')->nullable()->constrained('periode_gaji')->nullOnDelete();
            $table->string('nama_simulasi', 150);
            $table->json('data_surat_jalan');
            $table->unsignedSmallInteger('total_rit')->default(0);
            $table->unsignedBigInteger('total_tonase_kg')->default(0);
            $table->unsignedBigInteger('total_angkutan_kotor')->default(0);
            $table->unsignedBigInteger('total_uang_jalan')->default(0);
            $table->unsignedBigInteger('biaya_sparepart')->default(0);
            $table->unsignedBigInteger('gaji_komisi')->default(0);
            $table->unsignedBigInteger('total_tunjangan')->default(0);
            $table->unsignedBigInteger('potongan_bpjs')->default(0);
            $table->unsignedBigInteger('potongan_kasbon')->default(0);
            $table->unsignedBigInteger('potongan_lain')->default(0);
            $table->unsignedBigInteger('gaji_bersih')->default(0);
            $table->string('mode_potongan', 20)->default('riil');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_karyawan', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulasi_gaji');
    }
};
