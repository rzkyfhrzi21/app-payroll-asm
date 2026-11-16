<?php

use App\Support\FormatNomorTelepon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menyeragamkan nomor telepon pengguna lama ke format internasional
     * "+62" diikuti digit lokal berawalan 8 (contoh: +6281289301123).
     * Nilai yang tidak dapat ditafsirkan dibiarkan apa adanya.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('nomor_telepon')
            ->where('nomor_telepon', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($daftarPengguna): void {
                foreach ($daftarPengguna as $pengguna) {
                    $standar = FormatNomorTelepon::keStandar($pengguna->nomor_telepon);

                    if ($standar === null || $standar === $pengguna->nomor_telepon) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $pengguna->id)
                        ->update(['nomor_telepon' => $standar]);
                }
            });
    }

    public function down(): void
    {
        // Normalisasi bersifat satu arah; nilai lama tidak disimpan.
    }
};
