<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penggajian: rekap & snapshot gaji per supir per periode.
     */
    public function up(): void
    {
        Schema::create('penggajian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_periode_gaji')->constrained('periode_gaji');
            $table->foreignId('id_karyawan')->constrained('karyawan');
            $table->foreignId('id_truk')->constrained('truk');
            $table->tinyInteger('total_rit')->default(0);
            $table->bigInteger('total_tonase_kg')->default(0);
            $table->bigInteger('total_angkutan_kotor')->default(0);
            $table->bigInteger('potongan_operasional')->default(0);
            $table->bigInteger('angkutan_bersih')->default(0);
            $table->bigInteger('total_uang_jalan')->default(0);
            $table->bigInteger('biaya_sparepart')->default(0);
            $table->bigInteger('hasil_bersih_armada')->default(0);
            $table->smallInteger('persentase_komisi_bp')->default(1000);
            $table->bigInteger('gaji_komisi')->default(0);
            $table->bigInteger('total_tunjangan')->default(0);
            $table->bigInteger('potongan_bpjs')->default(0);
            $table->bigInteger('potongan_kasbon')->default(0);
            $table->bigInteger('gaji_bersih')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penggajian');
    }
};
