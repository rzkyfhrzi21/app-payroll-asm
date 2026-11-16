<?php

use App\Support\SiklusPeriodeGaji;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menyeragamkan seluruh periode penggajian menjadi siklus bulan penuh:
     * tanggal 1 sampai hari terakhir bulan yang sama.
     *
     * Bulan acuan diambil dari kode periode bila dapat dibaca (contoh "Jan-26"
     * berarti Januari 2026), jika tidak dari tanggal_mulai yang tersimpan.
     */
    public function up(): void
    {
        $daftarPeriode = DB::table('periode_gaji')->orderBy('id')->get();

        foreach ($daftarPeriode as $periode) {
            $acuan = $this->tanggalAcuan($periode);

            if ($acuan === null) {
                continue;
            }

            $rentang = SiklusPeriodeGaji::rentangBulan($acuan);

            DB::table('periode_gaji')->where('id', $periode->id)->update([
                'tanggal_mulai' => $rentang['tanggal_mulai'],
                'tanggal_selesai' => $rentang['tanggal_selesai'],
            ]);
        }
    }

    public function down(): void
    {
        // Normalisasi bersifat satu arah; tanggal lama tidak disimpan.
    }

    /**
     * Menentukan tanggal acuan dari kode periode ("Jan-26") atau tanggal mulai.
     */
    private function tanggalAcuan(object $periode): ?Carbon
    {
        $kode = trim((string) ($periode->kode ?? ''));

        if ($kode !== '' && preg_match('/^([A-Za-z]+)[- ]?(\d{2,4})$/', $kode, $cocok) === 1) {
            $namaBulan = $this->nomorBulan($cocok[1]);
            $tahun = (int) $cocok[2];
            $tahun = $tahun < 100 ? 2000 + $tahun : $tahun;

            if ($namaBulan !== null) {
                return Carbon::create($tahun, $namaBulan, 1)->startOfMonth();
            }
        }

        if (! empty($periode->tanggal_mulai)) {
            return Carbon::parse((string) $periode->tanggal_mulai);
        }

        return null;
    }

    /**
     * Menerjemahkan nama bulan Indonesia/Inggris menjadi nomor bulan.
     */
    private function nomorBulan(string $nama): ?int
    {
        $peta = [
            'jan' => 1, 'januari' => 1, 'january' => 1,
            'feb' => 2, 'februari' => 2, 'february' => 2,
            'mar' => 3, 'maret' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'mei' => 5, 'may' => 5,
            'jun' => 6, 'juni' => 6, 'june' => 6,
            'jul' => 7, 'juli' => 7, 'july' => 7,
            'agu' => 8, 'agt' => 8, 'agustus' => 8, 'aug' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9,
            'okt' => 10, 'oktober' => 10, 'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'des' => 12, 'desember' => 12, 'dec' => 12, 'december' => 12,
        ];

        return $peta[mb_strtolower($nama)] ?? null;
    }
};
