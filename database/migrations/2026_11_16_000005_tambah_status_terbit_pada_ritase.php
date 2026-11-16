<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan status 'terbit' pada kolom ritase.status
     * untuk membedakan Surat Jalan draf (belum tampil ke supir) dengan Surat Jalan terbit (siap dikirim supir).
     */
    public function up(): void
    {
        if (! Schema::hasTable('ritase') || ! Schema::hasColumn('ritase', 'status')) {
            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE ritase MODIFY COLUMN status ENUM('draft','terbit','sedang_dikirim','menunggu_verifikasi','terverifikasi','dibatalkan') NOT NULL DEFAULT 'draft'");

            return;
        }

        Schema::table('ritase', function (Blueprint $table): void {
            $table->string('status', 30)->default('draft')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ritase') || ! Schema::hasColumn('ritase', 'status')) {
            return;
        }

        DB::table('ritase')
            ->where('status', 'terbit')
            ->update(['status' => 'draft']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE ritase MODIFY COLUMN status ENUM('draft','sedang_dikirim','menunggu_verifikasi','terverifikasi','dibatalkan') NOT NULL DEFAULT 'draft'");

            return;
        }

        Schema::table('ritase', function (Blueprint $table): void {
            $table->string('status', 30)->default('draft')->change();
        });
    }
};
