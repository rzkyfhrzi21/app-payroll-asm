<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menyelaraskan tanggal ritase & biaya operasional agar berada di dalam
     * siklus bulanan periodenya (tanggal 1 s/d akhir bulan).
     *
     * Urutan hari dipertahankan: data lama yang tersebar di beberapa bulan
     * dipetakan ke hari ke-1..ke-N pada bulan periode, sehingga jumlah ritase
     * dan total tonase tidak berubah.
     */
    public function up(): void
    {
        foreach (DB::table('periode_gaji')->orderBy('id')->get() as $periode) {
            $awal = Carbon::parse((string) $periode->tanggal_mulai);
            $akhirBulan = $awal->copy()->endOfMonth();
            $jumlahHari = $awal->daysInMonth;

            // --- Ritase ---
            $daftarRitase = DB::table('ritase')
                ->where('id_periode_gaji', $periode->id)
                ->orderBy('tanggal_ritase')
                ->orderBy('id')
                ->get(['id', 'tanggal_ritase']);

            $hariUnik = $daftarRitase->pluck('tanggal_ritase')->unique()->sort()->values();

            if ($hariUnik->isNotEmpty()) {
                $diLuarSiklus = $hariUnik->contains(function (string $tanggal) use ($awal, $akhirBulan): bool {
                    $t = Carbon::parse($tanggal);

                    return $t->lt($awal) || $t->gt($akhirBulan);
                });

                if ($diLuarSiklus) {
                    $peta = [];
                    foreach ($hariUnik as $indeks => $tanggalLama) {
                        $hariBaru = min($jumlahHari, $indeks + 1);
                        $peta[$tanggalLama] = $awal->copy()->day($hariBaru)->toDateString();
                    }

                    foreach ($daftarRitase as $ritase) {
                        $baru = $peta[(string) $ritase->tanggal_ritase] ?? null;

                        if ($baru !== null && $baru !== $ritase->tanggal_ritase) {
                            DB::table('ritase')->where('id', $ritase->id)->update(['tanggal_ritase' => $baru]);
                        }
                    }
                }
            }

            // --- Biaya operasional ---
            $daftarBiaya = DB::table('biaya_operasional')
                ->where('id_periode_gaji', $periode->id)
                ->orderBy('tanggal_biaya')
                ->orderBy('id')
                ->get(['id', 'tanggal_biaya']);

            foreach ($daftarBiaya as $biaya) {
                $tanggal = Carbon::parse((string) $biaya->tanggal_biaya);

                if ($tanggal->betweenIncluded($awal, $akhirBulan)) {
                    continue;
                }

                // Selisih bulan dipetakan agar tanggal tetap masuk akal (hari sama, bulan periode).
                $hari = min($tanggal->day, $jumlahHari);

                DB::table('biaya_operasional')->where('id', $biaya->id)->update([
                    'tanggal_biaya' => $awal->copy()->day($hari)->toDateString(),
                ]);
            }

            // --- Angsuran kasbon ---
            DB::table('angsuran_kasbon')
                ->whereNotNull('id_periode_gaji')
                ->where('id_periode_gaji', $periode->id)
                ->orderBy('id')
                ->get(['id', 'tanggal_rencana'])
                ->each(function (object $angsuran) use ($awal, $akhirBulan, $jumlahHari): void {
                    if ($angsuran->tanggal_rencana === null) {
                        return;
                    }

                    $tanggal = Carbon::parse((string) $angsuran->tanggal_rencana);

                    if ($tanggal->betweenIncluded($awal, $akhirBulan)) {
                        return;
                    }

                    DB::table('angsuran_kasbon')->where('id', $angsuran->id)->update([
                        'tanggal_rencana' => $awal->copy()->day(min($tanggal->day, $jumlahHari))->toDateString(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        // Penyelarasan bersifat satu arah.
    }
};
