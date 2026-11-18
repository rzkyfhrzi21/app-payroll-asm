<?php

namespace App\Services;

use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Support\SiklusPeriodeGaji;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ============================================================================
 * LAYANAN: SIKLUS BUKA/TUTUP BUKU PERIODE GAJI (PERIODE LIFECYCLE SERVICE)
 * ============================================================================
 *
 * Analogi Sederhana:
 * Ibarat "Buku Kas Harian di Meja Kasir": kasir membuka satu lembar halaman baru
 * saat mulai menghitung, lalu menutupnya saat jam tutup buku tiba. Halaman yang
 * sudah ditutup disimpan ke lemari arsip dan TIDAK PERNAH diubah lagi.
 *
 * Aturan yang dijaga layanan ini:
 * 1. Hanya boleh ada SATU periode berjalan (`tanggal_selesai` NULL) pada satu waktu.
 * 2. Rentang periode tidak boleh berlubang: periode baru mulai H+1 dari tutup buku.
 * 3. Saat tutup buku, sistem otomatis membuka periode berikutnya.
 * 4. Kode periode final mengikuti BULAN TANGGAL TUTUP BUKU (mis. tutup 18 Nov -> "Nov-26").
 * ============================================================================
 */
class PeriodeGajiLifecycleService
{
    /**
     * Ambil periode yang masih berjalan (belum ditutup), bila ada.
     */
    public function ambilPeriodeBerjalan(): ?PeriodeGaji
    {
        return PeriodeGaji::query()
            ->whereNull('tanggal_selesai')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Tanggal mulai untuk periode baru: H+1 dari tutup buku periode terakhir.
     * Bila belum ada periode sama sekali, pakai tanggal hari ini.
     */
    public function tanggalMulaiBerikutnya(): Carbon
    {
        $periodeTerakhir = PeriodeGaji::query()
            ->whereNotNull('tanggal_selesai')
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->first();

        if ($periodeTerakhir === null || $periodeTerakhir->tanggal_selesai === null) {
            return Carbon::now()->startOfDay();
        }

        return $periodeTerakhir->tanggal_selesai->copy()->addDay()->startOfDay();
    }

    /**
     * Buka periode baru. Ditolak bila masih ada periode berjalan.
     *
     * @throws ValidationException
     */
    public function bukaPeriodeBaru(?Carbon $tanggalMulai = null): PeriodeGaji
    {
        $periodeBerjalan = $this->ambilPeriodeBerjalan();

        if ($periodeBerjalan !== null) {
            throw ValidationException::withMessages([
                'periode' => [
                    "Masih ada periode {$periodeBerjalan->kode} yang belum ditutup buku. "
                    .'Tutup buku periode tersebut terlebih dahulu sebelum membuka periode baru.',
                ],
            ]);
        }

        $mulai = ($tanggalMulai ?? $this->tanggalMulaiBerikutnya())->copy()->startOfDay();
        $kode = $this->kodeTersedia(SiklusPeriodeGaji::kodeDariTanggal($mulai));

        return PeriodeGaji::create([
            'kode' => $kode,
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => null,
            'tanggal_bayar' => null,
            'status' => 'draft',
        ]);
    }

    /**
     * Tutup buku periode: isi tanggal selesai, finalkan kode, lalu buka periode berikutnya.
     *
     * Catatan: pemanggil (controller) WAJIB sudah memvalidasi surat jalan terverifikasi
     * dan menjalankan rekonsiliasi gaji sebelum memanggil metode ini.
     *
     * @throws ValidationException
     */
    public function tutupBuku(PeriodeGaji $periode, ?Carbon $tanggalTutup = null): PeriodeGaji
    {
        if ($periode->tanggal_selesai !== null) {
            throw ValidationException::withMessages([
                'periode' => ["Periode {$periode->kode} sudah ditutup buku pada {$periode->tanggal_selesai->toDateString()}."],
            ]);
        }

        $tutup = ($tanggalTutup ?? Carbon::now())->copy()->startOfDay();

        if ($periode->tanggal_mulai !== null && $tutup->lt($periode->tanggal_mulai)) {
            throw ValidationException::withMessages([
                'periode' => ['Tanggal tutup buku tidak boleh lebih awal dari tanggal mulai periode.'],
            ]);
        }

        return DB::transaction(function () use ($periode, $tutup): PeriodeGaji {
            // Kode periode final mengikuti bulan tanggal tutup buku.
            $kodeFinal = $this->kodeTersedia(
                SiklusPeriodeGaji::kodeDariTanggal($tutup),
                $periode->id,
            );

            $periode->kode = $kodeFinal;
            $periode->tanggal_selesai = $tutup->toDateString();
            $periode->tanggal_bayar = $periode->tanggal_bayar ?? $tutup->toDateString();
            $periode->save();

            // Selalu buka periode berikutnya agar tidak ada tanggal yang hilang.
            $this->bukaPeriodeBaru($tutup->copy()->addDay());

            return $periode->refresh();
        });
    }

    /**
     * Buka kembali periode yang sudah ditutup (untuk menambah surat jalan susulan).
     * Periode sesudahnya yang masih kosong (belum punya ritase) ikut dibatalkan
     * agar rentang tetap rapat dan hanya ada satu periode berjalan.
     *
     * @throws ValidationException
     */
    public function bukaKembaliPeriode(PeriodeGaji $periode): PeriodeGaji
    {
        if (in_array($periode->status, ['approved', 'paid'], true)) {
            throw ValidationException::withMessages([
                'periode' => ["Periode {$periode->kode} sudah disetujui/dicairkan sehingga tidak dapat dibuka kembali."],
            ]);
        }

        // Periode sesudahnya (bila ada) harus masih kosong agar bisa dibatalkan.
        $periodeSesudahnya = PeriodeGaji::query()
            ->where('id', '!=', $periode->id)
            ->where('tanggal_mulai', '>', $periode->tanggal_mulai?->toDateString() ?? '1000-01-01')
            ->orderBy('tanggal_mulai')
            ->first();

        if ($periodeSesudahnya !== null) {
            $jumlahRitase = Ritase::query()
                ->where('id_periode_gaji', $periodeSesudahnya->id)
                ->count();

            if ($jumlahRitase > 0) {
                throw ValidationException::withMessages([
                    'periode' => [
                        "Periode {$periode->kode} tidak dapat dibuka kembali karena periode sesudahnya "
                        ."({$periodeSesudahnya->kode}) sudah berisi {$jumlahRitase} surat jalan.",
                    ],
                ]);
            }

            if (in_array($periodeSesudahnya->status, ['approved', 'paid'], true)) {
                throw ValidationException::withMessages([
                    'periode' => [
                        "Periode {$periode->kode} tidak dapat dibuka kembali karena periode sesudahnya "
                        ."({$periodeSesudahnya->kode}) sudah disetujui/dicairkan.",
                    ],
                ]);
            }
        }

        return DB::transaction(function () use ($periode, $periodeSesudahnya): PeriodeGaji {
            // Batalkan periode kosong sesudahnya agar tidak ada dua periode berjalan.
            if ($periodeSesudahnya !== null) {
                $periodeSesudahnya->penggajian()->delete();
                $periodeSesudahnya->delete();
            }

            $periode->tanggal_selesai = null;
            $periode->save();

            return $periode->refresh();
        });
    }

    /**
     * Pastikan kode periode unik. Bila sudah dipakai, tambahkan sufiks -2, -3, dst.
     */
    private function kodeTersedia(string $kodeDasar, ?int $idDikecualikan = null): string
    {
        $kode = $kodeDasar;
        $urutan = 2;

        while (PeriodeGaji::query()
            ->where('kode', $kode)
            ->when($idDikecualikan !== null, fn ($q) => $q->where('id', '!=', $idDikecualikan))
            ->exists()) {
            $kode = $kodeDasar.'-'.$urutan;
            $urutan++;
        }

        return $kode;
    }
}
