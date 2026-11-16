<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghapus konsep vendor dari sistem.
     *
     * Vendor sebelumnya dipakai sebagai penanda pemilik/mitra armada pada
     * tabel karyawan dan truk. Seluruh armada kini dianggap milik perusahaan
     * sendiri, sehingga kolom, indeks, dan nilainya dihapus permanen.
     */
    public function up(): void
    {
        // Indeks komposit harus dilepas lebih dulu karena kolomnya akan dihapus.
        $this->lepasIndeks('karyawan', 'idx_karyawan_status_vendor');
        $this->lepasIndeks('truk', 'idx_truk_status_vendor');

        foreach (['karyawan', 'truk'] as $tabel) {
            if (Schema::hasTable($tabel) && Schema::hasColumn($tabel, 'vendor')) {
                Schema::table($tabel, function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('vendor');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['karyawan', 'truk'] as $tabel) {
            if (Schema::hasTable($tabel) && ! Schema::hasColumn($tabel, 'vendor')) {
                Schema::table($tabel, function (Blueprint $blueprint): void {
                    $blueprint->string('vendor', 100)->nullable();
                });
            }
        }

        // Kembalikan indeks komposit yang dipakai optimasi daftar master.
        foreach ([
            'karyawan' => 'idx_karyawan_status_vendor',
            'truk' => 'idx_truk_status_vendor',
        ] as $tabel => $namaIndeks) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, 'vendor')) {
                continue;
            }

            if ($this->indeksAda($tabel, $namaIndeks)) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $blueprint) use ($namaIndeks): void {
                $blueprint->index(['status', 'vendor'], $namaIndeks);
            });
        }
    }

    /**
     * Melepas indeks bila ada. Aman untuk MySQL maupun SQLite (lingkungan uji).
     */
    private function lepasIndeks(string $tabel, string $namaIndeks): void
    {
        if (! Schema::hasTable($tabel)) {
            return;
        }

        if (! $this->indeksAda($tabel, $namaIndeks)) {
            return;
        }

        Schema::table($tabel, function (Blueprint $blueprint) use ($namaIndeks): void {
            $blueprint->dropIndex($namaIndeks);
        });
    }

    /**
     * Memeriksa keberadaan indeks lewat driver yang tersedia.
     */
    private function indeksAda(string $tabel, string $namaIndeks): bool
    {
        $koneksi = Schema::getConnection();
        $driver = $koneksi->getDriverName();

        if ($driver === 'sqlite') {
            $hasil = $koneksi->select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$tabel, $namaIndeks]
            );

            return $hasil !== [];
        }

        $hasil = $koneksi->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$tabel, $namaIndeks]
        );

        return $hasil !== [];
    }
};
