<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skema kasbon ber-cicilan:
     * - kolom baru pada `kasbon` (tanggal ambil, tenor, periode mulai, keterangan)
     * - tabel `angsuran_kasbon` untuk jadwal cicilan per periode
     * - tabel `jenis_truk` untuk master kategori/model armada
     */
    public function up(): void
    {
        Schema::table('kasbon', function (Blueprint $table) {
            $table->date('tanggal_pengambilan')->nullable()->after('id_karyawan');
            $table->foreignId('id_periode_gaji_mulai')->nullable()->after('tanggal_pengambilan')
                ->constrained('periode_gaji')->nullOnDelete();
            $table->tinyInteger('jumlah_tenor')->unsigned()->default(1)->after('nominal_angsuran');
            $table->string('keterangan', 150)->nullable()->after('jumlah_tenor');
            $table->softDeletes();
        });

        Schema::create('angsuran_kasbon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kasbon')->constrained('kasbon')->cascadeOnDelete();
            $table->foreignId('id_periode_gaji')->nullable()->constrained('periode_gaji')->nullOnDelete();
            $table->tinyInteger('urutan')->unsigned();
            $table->bigInteger('nominal');
            $table->date('tanggal_rencana');
            $table->date('tanggal_potong')->nullable();
            $table->enum('status', ['terjadwal', 'terpotong', 'dibatalkan'])->default('terjadwal');
            $table->string('catatan', 150)->nullable();
            $table->timestamps();

            $table->unique(['id_kasbon', 'urutan']);
        });

        Schema::create('jenis_truk', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->string('merek_model', 100);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_truk');
        Schema::dropIfExists('angsuran_kasbon');

        Schema::table('kasbon', function (Blueprint $table) {
            $table->dropForeign(['id_periode_gaji_mulai']);
            $table->dropColumn(['tanggal_pengambilan', 'id_periode_gaji_mulai', 'jumlah_tenor', 'keterangan']);
        });
    }
};
