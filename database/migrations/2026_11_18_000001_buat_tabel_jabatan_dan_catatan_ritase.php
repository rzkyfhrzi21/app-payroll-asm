<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahap 5 & 6:
     * - Tabel `jabatan` untuk master jabatan supir beserta skema komisi & BPJS default.
     * - Kolom `catatan_pengantaran` pada tabel `ritase` untuk konfirmasi pengantaran supir.
     */
    public function up(): void
    {
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

        if (Schema::hasTable('ritase') && ! Schema::hasColumn('ritase', 'catatan_pengantaran')) {
            Schema::table('ritase', function (Blueprint $table): void {
                $table->string('catatan_pengantaran', 255)->nullable()->after('foto_surat_jalan');
            });
        }

        // Seed jabatan bawaan dan sinkronkan dari data karyawan yang sudah ada
        if (Schema::hasTable('jabatan')) {
            $sekarang = now();
            $jabatanBawaan = [
                [
                    'kode' => 'JBT-TRN',
                    'nama' => 'SUPIR TRONTON',
                    'persentase_komisi_bp' => 1000,
                    'potongan_bpjs_default' => 129000,
                    'status' => 'aktif',
                ],
                [
                    'kode' => 'JBT-DT',
                    'nama' => 'SUPIR DUMP TRUCK',
                    'persentase_komisi_bp' => 1000,
                    'potongan_bpjs_default' => 129000,
                    'status' => 'aktif',
                ],
                [
                    'kode' => 'JBT-TRL',
                    'nama' => 'SUPIR TRAILER',
                    'persentase_komisi_bp' => 1000,
                    'potongan_bpjs_default' => 129000,
                    'status' => 'aktif',
                ],
            ];

            foreach ($jabatanBawaan as $baris) {
                $sudahAda = DB::table('jabatan')->where('nama', $baris['nama'])->exists();
                if (! $sudahAda) {
                    DB::table('jabatan')->insert(array_merge($baris, [
                        'created_at' => $sekarang,
                        'updated_at' => $sekarang,
                    ]));
                }
            }

            if (Schema::hasTable('karyawan')) {
                $jabatanKaryawan = DB::table('karyawan')
                    ->select('jabatan', DB::raw('MAX(persentase_komisi_bp) as komisi_bp'), DB::raw('MAX(potongan_bpjs_bulanan) as bpjs'))
                    ->whereNotNull('jabatan')
                    ->where('jabatan', '!=', '')
                    ->groupBy('jabatan')
                    ->get();

                $urutanTambahan = 1;
                foreach ($jabatanKaryawan as $barisKaryawan) {
                    $namaJabatan = strtoupper(trim((string) $barisKaryawan->jabatan));
                    if ($namaJabatan === '') {
                        continue;
                    }

                    $sudahAda = DB::table('jabatan')->where('nama', $namaJabatan)->exists();
                    if (! $sudahAda) {
                        $kodeOtomatis = 'JBT-'.str_pad((string) $urutanTambahan, 3, '0', STR_PAD_LEFT);
                        while (DB::table('jabatan')->where('kode', $kodeOtomatis)->exists()) {
                            $urutanTambahan++;
                            $kodeOtomatis = 'JBT-'.str_pad((string) $urutanTambahan, 3, '0', STR_PAD_LEFT);
                        }

                        DB::table('jabatan')->insert([
                            'kode' => $kodeOtomatis,
                            'nama' => $namaJabatan,
                            'persentase_komisi_bp' => (int) ($barisKaryawan->komisi_bp ?: 1000),
                            'potongan_bpjs_default' => (int) ($barisKaryawan->bpjs ?: 129000),
                            'status' => 'aktif',
                            'created_at' => $sekarang,
                            'updated_at' => $sekarang,
                        ]);
                        $urutanTambahan++;
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ritase') && Schema::hasColumn('ritase', 'catatan_pengantaran')) {
            Schema::table('ritase', fn (Blueprint $table) => $table->dropColumn('catatan_pengantaran'));
        }

        Schema::dropIfExists('jabatan');
    }
};
