<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel karyawan: data induk supir / karyawan.
     */
    public function up(): void
    {
        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nomor_induk_karyawan', 20)->unique();
            $table->string('nama_lengkap', 150);
            $table->string('jabatan', 100);
            $table->smallInteger('persentase_komisi_bp')->default(1000);
            $table->string('vendor', 100)->nullable();
            $table->string('nama_bank', 50)->nullable();
            $table->string('nomor_rekening', 30)->nullable();
            $table->bigInteger('potongan_bpjs_bulanan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};
