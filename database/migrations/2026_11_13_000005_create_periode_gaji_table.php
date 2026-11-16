<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel periode_gaji: periode penggajian bulanan.
     */
    public function up(): void
    {
        Schema::create('periode_gaji', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->date('tanggal_bayar')->nullable();
            $table->enum('status', ['draft', 'calculated', 'pending', 'approved', 'paid'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_gaji');
    }
};
