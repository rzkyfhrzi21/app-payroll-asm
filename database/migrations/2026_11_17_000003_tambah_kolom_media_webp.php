<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'foto_profil')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('foto_profil', 255)->nullable()->after('email');
            });
        }

        if (Schema::hasTable('karyawan') && ! Schema::hasColumn('karyawan', 'foto_profil')) {
            Schema::table('karyawan', function (Blueprint $table): void {
                $table->string('foto_profil', 255)->nullable()->after('nama_lengkap');
            });
        }

        if (Schema::hasTable('truk') && ! Schema::hasColumn('truk', 'foto_unit')) {
            Schema::table('truk', function (Blueprint $table): void {
                $table->string('foto_unit', 255)->nullable()->after('merek_model');
            });
        }

        if (Schema::hasTable('biaya_operasional') && ! Schema::hasColumn('biaya_operasional', 'foto_nota')) {
            Schema::table('biaya_operasional', function (Blueprint $table): void {
                $table->string('foto_nota', 255)->nullable()->after('nomor_nota');
            });
        }

        if (Schema::hasTable('ritase') && ! Schema::hasColumn('ritase', 'foto_surat_jalan')) {
            Schema::table('ritase', function (Blueprint $table): void {
                $table->string('foto_surat_jalan', 255)->nullable()->after('uang_jalan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'foto_profil')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('foto_profil'));
        }
        if (Schema::hasTable('karyawan') && Schema::hasColumn('karyawan', 'foto_profil')) {
            Schema::table('karyawan', fn (Blueprint $t) => $t->dropColumn('foto_profil'));
        }
        if (Schema::hasTable('truk') && Schema::hasColumn('truk', 'foto_unit')) {
            Schema::table('truk', fn (Blueprint $t) => $t->dropColumn('foto_unit'));
        }
        if (Schema::hasTable('biaya_operasional') && Schema::hasColumn('biaya_operasional', 'foto_nota')) {
            Schema::table('biaya_operasional', fn (Blueprint $t) => $t->dropColumn('foto_nota'));
        }
        if (Schema::hasTable('ritase') && Schema::hasColumn('ritase', 'foto_surat_jalan')) {
            Schema::table('ritase', fn (Blueprint $t) => $t->dropColumn('foto_surat_jalan'));
        }
    }
};
