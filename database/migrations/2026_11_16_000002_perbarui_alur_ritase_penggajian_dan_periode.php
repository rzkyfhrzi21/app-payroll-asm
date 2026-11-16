<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Memperbarui alur Surat Jalan (ritase), otorisasi bertahap per supir (penggajian),
     * dan metadata persetujuan/pencairan/penolakan pada periode gaji.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable('ritase')) {
            if (! Schema::hasColumn('ritase', 'nama_penerima')) {
                $kolomSetelah = match (true) {
                    Schema::hasColumn('ritase', 'catatan_pengantaran') => 'catatan_pengantaran',
                    Schema::hasColumn('ritase', 'foto_surat_jalan') => 'foto_surat_jalan',
                    default => 'uang_jalan',
                };

                Schema::table('ritase', function (Blueprint $table) use ($kolomSetelah): void {
                    $table->string('nama_penerima', 120)->nullable()->after($kolomSetelah);
                });
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE ritase MODIFY COLUMN status ENUM('draft','menunggu_verifikasi','terverifikasi','dibatalkan') NOT NULL DEFAULT 'draft'");
            } else {
                Schema::table('ritase', function (Blueprint $table): void {
                    $table->string('status', 30)->default('draft')->change();
                });
            }
        }

        if (Schema::hasTable('penggajian')) {
            Schema::table('penggajian', function (Blueprint $table): void {
                if (! Schema::hasColumn('penggajian', 'status')) {
                    $table->string('status', 20)->default('draft')->after('gaji_bersih');
                }
                if (! Schema::hasColumn('penggajian', 'id_penyetuju')) {
                    $table->foreignId('id_penyetuju')->nullable()->after('status')
                        ->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('penggajian', 'disetujui_pada')) {
                    $table->timestamp('disetujui_pada')->nullable()->after('id_penyetuju');
                }
                if (! Schema::hasColumn('penggajian', 'dicairkan_pada')) {
                    $table->timestamp('dicairkan_pada')->nullable()->after('disetujui_pada');
                }
            });

            if (Schema::hasTable('periode_gaji')) {
                $daftarPeriodeApproved = DB::table('periode_gaji')->where('status', 'approved')->pluck('id');
                if ($daftarPeriodeApproved->isNotEmpty()) {
                    DB::table('penggajian')
                        ->whereIn('id_periode_gaji', $daftarPeriodeApproved)
                        ->update(['status' => 'approved']);
                }

                $daftarPeriodePaid = DB::table('periode_gaji')->where('status', 'paid')->pluck('id');
                if ($daftarPeriodePaid->isNotEmpty()) {
                    DB::table('penggajian')
                        ->whereIn('id_periode_gaji', $daftarPeriodePaid)
                        ->update(['status' => 'paid']);
                }
            }
        }

        if (Schema::hasTable('periode_gaji')) {
            Schema::table('periode_gaji', function (Blueprint $table): void {
                if (! Schema::hasColumn('periode_gaji', 'id_penyetuju')) {
                    $table->foreignId('id_penyetuju')->nullable()->after('status')
                        ->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('periode_gaji', 'disetujui_pada')) {
                    $table->timestamp('disetujui_pada')->nullable()->after('id_penyetuju');
                }
                if (! Schema::hasColumn('periode_gaji', 'dicairkan_pada')) {
                    $table->timestamp('dicairkan_pada')->nullable()->after('disetujui_pada');
                }
                if (! Schema::hasColumn('periode_gaji', 'catatan_penolakan')) {
                    $table->text('catatan_penolakan')->nullable()->after('dicairkan_pada');
                }
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable('periode_gaji')) {
            Schema::table('periode_gaji', function (Blueprint $table): void {
                if (Schema::hasColumn('periode_gaji', 'id_penyetuju')) {
                    $table->dropForeign(['id_penyetuju']);
                }

                $kolomDihapus = array_values(array_filter(
                    ['id_penyetuju', 'disetujui_pada', 'dicairkan_pada', 'catatan_penolakan'],
                    fn (string $kolom): bool => Schema::hasColumn('periode_gaji', $kolom)
                ));

                if ($kolomDihapus !== []) {
                    $table->dropColumn($kolomDihapus);
                }
            });
        }

        if (Schema::hasTable('penggajian')) {
            Schema::table('penggajian', function (Blueprint $table): void {
                if (Schema::hasColumn('penggajian', 'id_penyetuju')) {
                    $table->dropForeign(['id_penyetuju']);
                }

                $kolomDihapus = array_values(array_filter(
                    ['status', 'id_penyetuju', 'disetujui_pada', 'dicairkan_pada'],
                    fn (string $kolom): bool => Schema::hasColumn('penggajian', $kolom)
                ));

                if ($kolomDihapus !== []) {
                    $table->dropColumn($kolomDihapus);
                }
            });
        }

        if (Schema::hasTable('ritase')) {
            DB::table('ritase')
                ->where('status', 'menunggu_verifikasi')
                ->update(['status' => 'draft']);

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE ritase MODIFY COLUMN status ENUM('draft','terverifikasi','dibatalkan') NOT NULL DEFAULT 'terverifikasi'");
            } else {
                Schema::table('ritase', function (Blueprint $table): void {
                    $table->string('status', 30)->default('terverifikasi')->change();
                });
            }

            if (Schema::hasColumn('ritase', 'nama_penerima')) {
                Schema::table('ritase', function (Blueprint $table): void {
                    $table->dropColumn('nama_penerima');
                });
            }
        }
    }
};
