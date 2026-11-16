<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel kasbon: pinjaman / kasbon supir.
     */
    public function up(): void
    {
        Schema::create('kasbon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('karyawan');
            $table->bigInteger('total_pinjaman');
            $table->bigInteger('nominal_angsuran');
            $table->bigInteger('sisa_pinjaman');
            $table->enum('status', ['pending', 'approved', 'rejected', 'lunas'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon');
    }
};
