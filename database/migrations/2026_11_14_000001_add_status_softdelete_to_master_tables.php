<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah soft delete, status aktif/nonaktif, relasi supir-armada,
     * dan detail rute (jarak + alamat asal/tujuan).
     */
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif')->after('jabatan');
            $table->softDeletes();
        });

        Schema::table('truk', function (Blueprint $table) {
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif')->after('vendor');
            $table->foreignId('id_karyawan')->nullable()->after('status')->constrained('karyawan')->nullOnDelete();
            $table->softDeletes();
        });

        Schema::table('rute', function (Blueprint $table) {
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif')->after('uang_jalan_default');
            $table->unsignedInteger('jarak_km')->nullable()->after('status');
            $table->string('alamat_asal', 150)->nullable()->after('jarak_km');
            $table->string('alamat_tujuan', 150)->nullable()->after('alamat_asal');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('rute', function (Blueprint $table) {
            $table->dropColumn(['status', 'jarak_km', 'alamat_asal', 'alamat_tujuan']);
            $table->dropSoftDeletes();
        });

        Schema::table('truk', function (Blueprint $table) {
            $table->dropForeign(['id_karyawan']);
            $table->dropColumn(['status', 'id_karyawan']);
            $table->dropSoftDeletes();
        });

        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropSoftDeletes();
        });
    }
};
