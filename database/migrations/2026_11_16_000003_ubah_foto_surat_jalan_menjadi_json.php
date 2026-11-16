<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengubah kolom ritase.foto_surat_jalan dari VARCHAR(255) menjadi JSON array
     * agar satu Surat Jalan dapat menyimpan lebih dari satu foto bukti (.webp).
     */
    public function up(): void
    {
        if (! Schema::hasTable('ritase')) {
            return;
        }

        // Pada pengujian SQLite :memory: (RefreshDatabase), migrasi ini berjalan sebelum
        // 2026_11_17_000003_tambah_kolom_media_webp sehingga kolom dibuat langsung bertipe JSON.
        if (! Schema::hasColumn('ritase', 'foto_surat_jalan')) {
            Schema::table('ritase', function (Blueprint $table): void {
                $table->json('foto_surat_jalan')->nullable()->after('uang_jalan');
            });

            return;
        }

        // 1. Perlebar dulu menjadi TEXT agar seluruh nilai lama maupun JSON panjang aman.
        Schema::table('ritase', function (Blueprint $table): void {
            $table->text('foto_surat_jalan')->nullable()->change();
        });

        // 2. Normalisasi data eksisting sebelum tipe kolom diubah ke JSON.
        DB::table('ritase')
            ->select(['id', 'foto_surat_jalan'])
            ->whereNotNull('foto_surat_jalan')
            ->orderBy('id')
            ->chunkById(200, function ($daftarBaris): void {
                foreach ($daftarBaris as $baris) {
                    $nilaiBaru = $this->normalisasiNilaiKeJson($baris->foto_surat_jalan);

                    DB::table('ritase')
                        ->where('id', $baris->id)
                        ->update(['foto_surat_jalan' => $nilaiBaru]);
                }
            });

        // 3. Ubah tipe kolom menjadi JSON nullable.
        Schema::table('ritase', function (Blueprint $table): void {
            $table->json('foto_surat_jalan')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ritase') || ! Schema::hasColumn('ritase', 'foto_surat_jalan')) {
            return;
        }

        // Ubah ke TEXT terlebih dahulu agar MySQL tidak menolak string tunggal non-JSON.
        Schema::table('ritase', function (Blueprint $table): void {
            $table->text('foto_surat_jalan')->nullable()->change();
        });

        DB::table('ritase')
            ->select(['id', 'foto_surat_jalan'])
            ->whereNotNull('foto_surat_jalan')
            ->orderBy('id')
            ->chunkById(200, function ($daftarBaris): void {
                foreach ($daftarBaris as $baris) {
                    $nilaiTunggal = $this->ekstrakFotoPertama($baris->foto_surat_jalan);

                    DB::table('ritase')
                        ->where('id', $baris->id)
                        ->update(['foto_surat_jalan' => $nilaiTunggal]);
                }
            });

        Schema::table('ritase', function (Blueprint $table): void {
            $table->string('foto_surat_jalan', 255)->nullable()->change();
        });
    }

    private function normalisasiNilaiKeJson(mixed $nilaiMentah): ?string
    {
        if (! is_string($nilaiMentah)) {
            return null;
        }

        $teks = trim($nilaiMentah);
        if ($teks === '') {
            return null;
        }

        $hasilDecode = json_decode($teks, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($hasilDecode)) {
            $daftarValid = [];
            foreach ($hasilDecode as $elemen) {
                if (! is_string($elemen)) {
                    continue;
                }
                $jalur = trim($elemen);
                if ($jalur !== '' && ! in_array($jalur, $daftarValid, true)) {
                    $daftarValid[] = $jalur;
                }
            }

            return $daftarValid === []
                ? null
                : json_encode($daftarValid, JSON_UNESCAPED_SLASHES);
        }

        return json_encode([$teks], JSON_UNESCAPED_SLASHES);
    }

    private function ekstrakFotoPertama(mixed $nilaiMentah): ?string
    {
        if (! is_string($nilaiMentah)) {
            return null;
        }

        $teks = trim($nilaiMentah);
        if ($teks === '') {
            return null;
        }

        $hasilDecode = json_decode($teks, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($hasilDecode)) {
            foreach ($hasilDecode as $elemen) {
                if (is_string($elemen) && trim($elemen) !== '') {
                    return mb_substr(trim($elemen), 0, 255);
                }
            }

            return null;
        }

        return mb_substr($teks, 0, 255);
    }
};
