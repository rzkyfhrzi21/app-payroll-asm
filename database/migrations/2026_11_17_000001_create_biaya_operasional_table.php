<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel biaya_operasional: pengeluaran armada (sparepart, BBM, tol, dll).
     * Terikat ke unit truk dan periode gaji agar ikut tertagih saat
     * pengajuan penggajian ke Pimpinan.
     */
    public function up(): void
    {
        Schema::create('biaya_operasional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_periode_gaji')->constrained('periode_gaji')->cascadeOnDelete();
            $table->foreignId('id_truk')->constrained('truk');
            $table->foreignId('id_karyawan')->nullable()->constrained('karyawan')->nullOnDelete();
            $table->date('tanggal_biaya');
            $table->enum('kategori', ['sparepart', 'bbm', 'tol', 'retribusi', 'servis', 'lain_lain'])->default('sparepart');
            $table->string('keterangan', 200)->nullable();
            $table->bigInteger('nominal');
            $table->string('nomor_nota', 50)->nullable();
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak'])->default('draft');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['id_periode_gaji', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biaya_operasional');
    }
};
