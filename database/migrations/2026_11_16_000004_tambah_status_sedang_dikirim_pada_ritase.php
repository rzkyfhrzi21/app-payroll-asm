<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan status 'sedang_dikirim' pada kolom ritase.status
     * untuk menandai Surat Jalan yang sedang dalam perjalanan oleh supir.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ritase') || ! Schema::hasColumn('ritase', 'status')) {
            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE ritase MODIFY COLUMN status ENUM('draft','sedang_dikirim','menunggu_verifikasi','terverifikasi','dibatalkan') NOT NULL DEFAULT 'draft'");

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
            ->where('status', 'sedang_dikirim')
            ->update(['status' => 'draft']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE ritase MODIFY COLUMN status ENUM('draft','menunggu_verifikasi','terverifikasi','dibatalkan') NOT NULL DEFAULT 'draft'");

            return;
        }

        Schema::table('ritase', function (Blueprint $table): void {
            $table->string('status', 30)->default('draft')->change();
        });
    }
};
