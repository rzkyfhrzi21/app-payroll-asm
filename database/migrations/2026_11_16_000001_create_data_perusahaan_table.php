<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel singleton data_perusahaan: identitas, kontak, logo, rekening,
     * serta penanda tangan resmi pada slip gaji dan laporan perusahaan.
     */
    public function up(): void
    {
        Schema::create('data_perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan', 150);
            $table->string('kode_perusahaan', 30)->nullable();
            $table->string('logo_perusahaan', 255)->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('no_telepon', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('nama_pemilik', 120);
            $table->string('jabatan_pemilik', 80)->nullable();
            $table->string('ttd_pemilik', 255)->nullable();
            $table->string('nama_bank', 80)->nullable();
            $table->string('no_rekening', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_perusahaan');
    }
};
