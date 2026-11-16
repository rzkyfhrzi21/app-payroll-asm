<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyelaraskan peran pengguna dan menghapus master jabatan.
     *
     * - Peran `karyawan` diganti menjadi `supir` (hanya ada admin, pimpinan, supir).
     * - Master `jabatan` dihapus karena skema komisi kini melekat per supir.
     * - Kolom `karyawan.jabatan` ikut dihapus.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('role', 'karyawan')->update(['role' => 'supir']);
        }

        // Lepas relasi jumlah supir sebelum tabel jabatan dibuang.
        if (Schema::hasTable('jabatan') && Schema::hasColumn('karyawan', 'jabatan')) {
            // Kolom karyawan.jabatan berupa teks bebas, bukan foreign key,
            // jadi tidak ada constraint yang perlu dilepas manual.
        }

        if (Schema::hasColumn('karyawan', 'jabatan')) {
            Schema::table('karyawan', function (Blueprint $table): void {
                $table->dropColumn('jabatan');
            });
        }

        Schema::dropIfExists('jabatan');
    }

    /**
     * Mengembalikan struktur sebelum penyelarasan.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('role', 'supir')->update(['role' => 'karyawan']);
        }

        if (Schema::hasTable('karyawan') && ! Schema::hasColumn('karyawan', 'jabatan')) {
            Schema::table('karyawan', function (Blueprint $table): void {
                $table->string('jabatan', 100)->default('SUPIR TRONTON')->after('nama_lengkap');
            });
        }

        if (! Schema::hasTable('jabatan')) {
            Schema::create('jabatan', function (Blueprint $table): void {
                $table->id();
                $table->string('kode', 20)->unique();
                $table->string('nama', 100)->unique();
                $table->smallInteger('persentase_komisi_bp')->default(1000);
                $table->bigInteger('potongan_bpjs_default')->default(129000);
                $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }
};
