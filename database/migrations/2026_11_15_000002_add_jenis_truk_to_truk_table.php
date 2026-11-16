<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menautkan unit armada ke master jenis truk.
     * Nullable agar unit lama tetap valid sebelum diisi.
     */
    public function up(): void
    {
        Schema::table('truk', function (Blueprint $table) {
            $table->foreignId('id_jenis_truk')->nullable()->after('id_karyawan')
                ->constrained('jenis_truk')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('truk', function (Blueprint $table) {
            $table->dropForeign(['id_jenis_truk']);
            $table->dropColumn('id_jenis_truk');
        });
    }
};
