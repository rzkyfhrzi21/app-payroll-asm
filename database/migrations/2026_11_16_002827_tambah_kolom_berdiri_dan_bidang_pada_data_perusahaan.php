<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('data_perusahaan')) {
            Schema::table('data_perusahaan', function (Blueprint $table) {
                if (! Schema::hasColumn('data_perusahaan', 'tanggal_berdiri')) {
                    $table->date('tanggal_berdiri')->nullable()->after('kode_perusahaan');
                }
                if (! Schema::hasColumn('data_perusahaan', 'bidang_usaha')) {
                    $table->string('bidang_usaha', 150)->nullable()->after('tanggal_berdiri');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_perusahaan')) {
            Schema::table('data_perusahaan', function (Blueprint $table) {
                $drop = [];
                if (Schema::hasColumn('data_perusahaan', 'bidang_usaha')) {
                    $drop[] = 'bidang_usaha';
                }
                if (Schema::hasColumn('data_perusahaan', 'tanggal_berdiri')) {
                    $drop[] = 'tanggal_berdiri';
                }
                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }
    }
};
