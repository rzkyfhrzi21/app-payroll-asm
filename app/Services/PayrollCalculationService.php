<?php

namespace App\Services;

use App\Models\AngsuranKasbon;
use App\Models\BiayaOperasional;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ============================================================================
 * LAYANAN: JURU HITUNG PUSAT GAJI SUPIR BATUBARA (PAYROLL CALCULATION SERVICE)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kalkulator Utama & Juru Timbang Gaji Resmi Perusahaan".
 * Semua rumus matematika penggajian supir batubara dipusatkan di sini
 * (Single Source of Truth) agar tidak ada perbedaan hitungan antara tampilan
 * di layar HP supir, layar admin, maupun laporan pimpinan.
 * 
 * Perhitungan Menggunakan 4 Tahap Berjenjang (Bebas Koma / Bilangan Bulat Rupiah):
 * ----------------------------------------------------------------------------
 * Tahap 1 (Angkutan Bersih):
 *   total_angkutan_kotor (berat tonase x tarif rute) - potongan_operasional
 * 
 * Tahap 2 (Hasil Bersih Armada Truk):
 *   angkutan_bersih - total_uang_jalan_supir - nota_bon_sparepart_bengkel
 * 
 * Tahap 3 (Bagi Hasil Komisi Supir):
 *   hasil_bersih_armada x persentase_komisi (standar 1000 basis poin = 10,00%)
 * 
 * Tahap 4 (Gaji Bersih / Uang Diterima Supir):
 *   gaji_komisi - potongan_bpjs - cicilan_kasbon
 *
 * Catatan sementara: fitur tunjangan dihapus dari seluruh perhitungan.
 * 
 * BATAS WEWENANG OTORISASI:
 * - Admin/HRD HANYA BOLEH mengajukan draf gaji ('draft' -> 'pending').
 * - HANYA Pimpinan/Direktur yang berhak memberikan persetujuan ACC ('approved').
 * ============================================================================
 */
class PayrollCalculationService
{
    /**
     * Hitung Seluruh Komponen Slip Gaji Supir (Presisi Bulat Rupiah BIGINT)
     * 
     * Alur Kerja Bernomor:
     * (1) Hitung Tahap 1: Omzet kotor angkutan dikurangi potongan operasional rute.
     * (2) Hitung Tahap 2: Angkutan bersih dikurangi uang saku jalan dan nota sparepart truk.
     * (3) Hitung Tahap 3: Hitung bagi hasil komisi supir (dibulatkan ke Rupiah terdekat).
     * (4) Hitung Tahap 4: Kurangi komisi dengan iuran BPJS dan angsuran kasbon.
     * (5) Pastikan seluruh nominal tidak ada yang minus (dijaga minimal Rp 0 via max(0, ...)).
     *
     * @param  array<string, int|float|string|null>  $komponen
     * @return array<string, int>
     */
    public function hitungKomponenGaji(array $komponen): array
    {
        $totalRit = max(0, (int) ($komponen['total_rit'] ?? 0));
        $totalTonaseKg = max(0, (int) ($komponen['total_tonase_kg'] ?? 0));

        // (1) Tahap 1: Omzet Angkutan Kotor dikurangi Potongan Operasional = Pendapatan Angkutan Bersih
        $totalAngkutanKotor  = max(0, (int) ($komponen['total_angkutan_kotor'] ?? 0));
        $potonganOperasional = max(0, (int) ($komponen['potongan_operasional'] ?? 0));
        $angkutanBersih      = max(0, $totalAngkutanKotor - $potonganOperasional);

        // (2) Tahap 2: Angkutan Bersih dikurangi Uang Jalan dan Nota Bon Sparepart = Hasil Bersih Armada
        $totalUangJalan    = max(0, (int) ($komponen['total_uang_jalan'] ?? 0));
        $biayaSparepart    = max(0, (int) ($komponen['biaya_sparepart'] ?? 0));
        $hasilBersihArmada = max(0, $angkutanBersih - $totalUangJalan - $biayaSparepart);

        // (3) Tahap 3: Hasil Bersih Armada dikalikan persentase komisi basis poin (1000 bp = 10%)
        $persentaseKomisiBp = max(0, (int) ($komponen['persentase_komisi_bp'] ?? 1000));
        $gajiKomisi         = (int) round(($hasilBersihArmada * $persentaseKomisiBp) / 10000);

        // (4) Tahap 4: Komisi dikurangi BPJS dan Cicilan Kasbon = Gaji Bersih
        $potonganBpjs         = max(0, (int) ($komponen['potongan_bpjs'] ?? 0));
        $potonganKasbon       = max(0, (int) ($komponen['potongan_kasbon'] ?? 0));
        $potonganHistorisLain = max(0, (int) ($komponen['potongan_historis_lain'] ?? 0));

        // (5) Angka final yang akan ditransfer ke rekening supir
        $gajiBersih = max(0, $gajiKomisi - $potonganBpjs - $potonganKasbon - $potonganHistorisLain);

        return [
            'total_rit'            => $totalRit,
            'total_tonase_kg'      => $totalTonaseKg,
            'total_angkutan_kotor' => $totalAngkutanKotor,
            'potongan_operasional' => $potonganOperasional,
            'angkutan_bersih'      => $angkutanBersih,
            'total_uang_jalan'     => $totalUangJalan,
            'biaya_sparepart'      => $biayaSparepart,
            'hasil_bersih_armada'  => $hasilBersihArmada,
            'persentase_komisi_bp' => $persentaseKomisiBp,
            'gaji_komisi'          => $gajiKomisi,
            'potongan_bpjs'        => $potonganBpjs,
            'potongan_kasbon'      => $potonganKasbon,
            'gaji_bersih'          => $gajiBersih,
        ];
    }

    /**
     * Rekonsiliasi seluruh baris penggajian pada suatu periode gaji di dalam transaksi database.
     *
     * - Hanya menghitung `ritase` yang sudah berstatus `'terverifikasi'`.
     * - Jika periode belum terkunci (`!in_array($periode->status, ['approved', 'paid'], true)`),
     *   layanan menyinkronkan jumlah rit/tonase/angkutan kotor/uang jalan dari ritase
     *   terverifikasi saat ini sambil menjaga `$selisihHistoris` untuk slip historis.
     *
     * @return array{jumlah_supir: int, total_rit: int, total_gaji_bersih: int}
     */
    public function rekonsiliasiPeriode(PeriodeGaji $periode): array
    {
        return DB::transaction(function () use ($periode): array {
            $penggajianTersedia = Penggajian::query()
                ->with('karyawan')
                ->where('id_periode_gaji', $periode->id)
                ->get()
                ->keyBy('id_karyawan');

            $periodeTerkunci = in_array($periode->status, ['approved', 'paid'], true);

            $ritasePerKaryawan = Ritase::query()
                ->where('id_periode_gaji', $periode->id)
                ->where('status', 'terverifikasi')
                ->get()
                ->groupBy('id_karyawan');

            // Cabang A: Bentuk baris penggajian awal dari ritase terverifikasi bila periode belum memiliki baris sama sekali.
            if ($penggajianTersedia->isEmpty()) {
                if (! $periodeTerkunci) {
                    foreach ($ritasePerKaryawan as $idKaryawan => $daftarRitase) {
                        $penggajianBaru = $this->buatBarisPenggajianDariRitase($periode, (int) $idKaryawan, $daftarRitase);
                        if ($penggajianBaru !== null) {
                            $penggajianTersedia->put((int) $idKaryawan, $penggajianBaru);
                        }
                    }
                }
            } else {
                $karyawanDenganRitase = $periodeTerkunci
                    ? collect()
                    : Ritase::query()
                        ->where('id_periode_gaji', $periode->id)
                        ->distinct()
                        ->pluck('id_karyawan')
                        ->flip();

                // Cabang B: Sinkronkan ulang seluruh baris penggajian yang sudah ada.
                foreach ($penggajianTersedia as $penggajian) {
                    // Pertahankan selisih potongan historis (jika slip berasal dari impor/seed lama
                    // yang memiliki penyesuaian di luar BPJS & kasbon) agar gaji_bersih tidak berubah sepihak.
                    $selisihHistoris = max(
                        0,
                        ((int) $penggajian->gaji_komisi - (int) $penggajian->potongan_bpjs - (int) $penggajian->potongan_kasbon)
                        - (int) $penggajian->gaji_bersih
                    );

                    $totalRit = (int) $penggajian->total_rit;
                    $totalTonaseKg = (int) $penggajian->total_tonase_kg;
                    $totalAngkutanKotor = (int) $penggajian->total_angkutan_kotor;
                    $totalUangJalan = (int) $penggajian->total_uang_jalan;

                    if (! $periodeTerkunci && $karyawanDenganRitase->has($penggajian->id_karyawan)) {
                        $daftarRitase = $ritasePerKaryawan->get($penggajian->id_karyawan, collect());
                        $totalRit = (int) $daftarRitase->sum('jumlah_rit');
                        $totalTonaseKg = (int) $daftarRitase->sum('tonase_kg');
                        $totalAngkutanKotor = (int) $daftarRitase->sum('total_angkutan');
                        $totalUangJalan = (int) $daftarRitase->sum('uang_jalan');
                    }

                    $hasilHitung = $this->hitungKomponenGaji([
                        'total_rit' => $totalRit,
                        'total_tonase_kg' => $totalTonaseKg,
                        'total_angkutan_kotor' => $totalAngkutanKotor,
                        'potongan_operasional' => (int) $penggajian->potongan_operasional,
                        'total_uang_jalan' => $totalUangJalan,
                        'biaya_sparepart' => (int) $penggajian->biaya_sparepart,
                        'persentase_komisi_bp' => (int) ($penggajian->persentase_komisi_bp ?: 1000),
                        'potongan_bpjs' => (int) $penggajian->potongan_bpjs,
                        'potongan_kasbon' => (int) $penggajian->potongan_kasbon,
                        'potongan_historis_lain' => $selisihHistoris,
                    ]);

                    $penggajian->fill($hasilHitung);
                    if ($penggajian->isDirty()) {
                        $penggajian->save();
                    }
                }

                // Tambahkan baris baru untuk supir yang baru memiliki ritase terverifikasi setelah kalkulasi sebelumnya.
                if (! $periodeTerkunci) {
                    foreach ($ritasePerKaryawan as $idKaryawan => $daftarRitase) {
                        if ($penggajianTersedia->has((int) $idKaryawan)) {
                            continue;
                        }

                        $penggajianBaru = $this->buatBarisPenggajianDariRitase($periode, (int) $idKaryawan, $daftarRitase);
                        if ($penggajianBaru !== null) {
                            $penggajianTersedia->put((int) $idKaryawan, $penggajianBaru);
                        }
                    }
                }
            }

            return [
                'jumlah_supir' => $penggajianTersedia->count(),
                'total_rit' => (int) $penggajianTersedia->sum('total_rit'),
                'total_gaji_bersih' => (int) $penggajianTersedia->sum('gaji_bersih'),
            ];
        });
    }

    /**
     * Membentuk satu baris penggajian awal dari kumpulan ritase terverifikasi milik seorang supir.
     *
     * @param  Collection<int, Ritase>  $daftarRitase
     */
    private function buatBarisPenggajianDariRitase(PeriodeGaji $periode, int $idKaryawan, Collection $daftarRitase): ?Penggajian
    {
        $karyawan = Karyawan::query()->find($idKaryawan);
        $ritPertama = $daftarRitase->first();
        $idTruk = (int) ($karyawan?->id_truk ?? $ritPertama?->id_truk ?? 0);

        if ($karyawan === null || $idTruk <= 0) {
            return null;
        }

        $potonganKasbonPeriode = $this->hitungPotonganKasbonPeriode($karyawan->id, $periode);
        $biayaSparepartPeriode = $this->hitungSparepartTrukPeriode($idTruk, $periode);

        $hasilHitung = $this->hitungKomponenGaji([
            'total_rit' => (int) $daftarRitase->sum('jumlah_rit'),
            'total_tonase_kg' => (int) $daftarRitase->sum('tonase_kg'),
            'total_angkutan_kotor' => (int) $daftarRitase->sum('total_angkutan'),
            'potongan_operasional' => 0,
            'total_uang_jalan' => (int) $daftarRitase->sum('uang_jalan'),
            'biaya_sparepart' => $biayaSparepartPeriode,
            'persentase_komisi_bp' => (int) ($karyawan->persentase_komisi_bp ?: 1000),
            'potongan_bpjs' => (int) $karyawan->potongan_bpjs_bulanan,
            'potongan_kasbon' => $potonganKasbonPeriode,
        ]);

        return Penggajian::query()->create(array_merge($hasilHitung, [
            'id_periode_gaji' => $periode->id,
            'id_karyawan' => $karyawan->id,
            'id_truk' => $idTruk,
            'status' => 'draft',
        ]));
    }

    /**
     * Perbarui komponen gaji seorang supir dan hitung ulang seluruh tahap turunannya.
     *
     * Dipanggil saat Admin mengubah sparepart/BPJS di Drawer Detail Payroll,
     * pembaruan BPJS pada modul Kasbon & Potongan, maupun saat supir
     * mengonfirmasi tonase timbangan surat jalan.
     *
     * @param  array<string, int|float|string|null>  $input
     */
    public function perbaruiKomponenSupir(Penggajian $penggajian, array $input): Penggajian
    {
        // Hitung selisih historis sebelum komponen diperbarui agar penyesuaian bawaan
        // pada slip historis tetap terjaga setelah gaji dihitung ulang.
        $selisihHistoris = max(
            0,
            ((int) $penggajian->gaji_komisi - (int) $penggajian->potongan_bpjs - (int) $penggajian->potongan_kasbon)
            - (int) $penggajian->gaji_bersih
        );

        // Terapkan perubahan input (biaya_sparepart, potongan_bpjs) bila disediakan,
        // lalu delegasikan perhitungan Tahap 1-4 ke hitungKomponenGaji().
        $hasilHitung = $this->hitungKomponenGaji([
            'total_rit' => (int) $penggajian->total_rit,
            'total_tonase_kg' => (int) $penggajian->total_tonase_kg,
            'total_angkutan_kotor' => (int) $penggajian->total_angkutan_kotor,
            'potongan_operasional' => (int) $penggajian->potongan_operasional,
            'total_uang_jalan' => (int) $penggajian->total_uang_jalan,
            'biaya_sparepart' => array_key_exists('biaya_sparepart', $input)
                ? (int) $input['biaya_sparepart']
                : (int) $penggajian->biaya_sparepart,
            'persentase_komisi_bp' => (int) ($penggajian->persentase_komisi_bp ?: 1000),
            'potongan_bpjs' => array_key_exists('potongan_bpjs', $input)
                ? (int) $input['potongan_bpjs']
                : (int) $penggajian->potongan_bpjs,
            'potongan_kasbon' => (int) $penggajian->potongan_kasbon,
            'potongan_historis_lain' => $selisihHistoris,
        ]);

        $penggajian->fill($hasilHitung);
        $penggajian->save();

        return $penggajian->refresh();
    }

    /**
     * Proses persetujuan (ACC) satuan atau checkbox terpilih per supir oleh Pimpinan.
     *
     * Jika seluruh baris penggajian pada periode ini sudah berstatus `approved` / `paid`,
     * status periode otomatis naik menjadi `approved`.
     *
     * @param  array<int, int|string>  $daftarIdPenggajian
     */
    public function prosesPersetujuanSupir(PeriodeGaji $periode, array $daftarIdPenggajian, int $idPenyetuju): PeriodeGaji
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $daftarIdPenggajian), fn (int $id): bool => $id > 0)));

        return DB::transaction(function () use ($periode, $ids, $idPenyetuju): PeriodeGaji {
            $waktuSekarang = now();

            if ($ids !== []) {
                Penggajian::query()
                    ->where('id_periode_gaji', $periode->id)
                    ->whereIn('id', $ids)
                    ->where(function ($q): void {
                        $q->whereNull('status')->orWhere('status', '!=', 'paid');
                    })
                    ->update([
                        'status' => 'approved',
                        'id_penyetuju' => $idPenyetuju,
                        'disetujui_pada' => $waktuSekarang,
                    ]);
            }

            $totalBaris = Penggajian::query()->where('id_periode_gaji', $periode->id)->count();
            $belumDisetujui = Penggajian::query()
                ->where('id_periode_gaji', $periode->id)
                ->where(function ($q): void {
                    $q->whereNull('status')->orWhereNotIn('status', ['approved', 'paid']);
                })
                ->count();

            if ($totalBaris > 0 && $belumDisetujui === 0) {
                if ($periode->status !== 'paid') {
                    $periode->status = 'approved';
                }
                $periode->id_penyetuju = $idPenyetuju;
                $periode->disetujui_pada = $periode->disetujui_pada ?? $waktuSekarang;
                $periode->catatan_penolakan = null;
                if ($periode->tanggal_bayar === null) {
                    $periode->tanggal_bayar = $waktuSekarang->toDateString();
                }
                $periode->save();
            }

            return $periode->refresh();
        });
    }

    /**
     * Proses keputusan otorisasi (ACC / Revisi) pada periode penggajian.
     *
     * Alur status berjenjang:
     * 1. Admin mengajukan periode (`draft` -> `pending` via Admin\PayrollController::submit).
     * 2. Pimpinan meninjau di `/executive/payroll`:
     *    - `$mode === 'approve'`: ACC Pimpinan (`pending` -> `approved`) dan menetapkan
     *      `tanggal_bayar` hari ini bila belum terisi agar slip gaji sah diterbitkan.
     *    - Mode selain `'approve'` (mis. `'reject'`): dikembalikan ke status `draft` untuk
     *      revisi oleh Admin (juga dipakai Admin hanya untuk menarik pengajuan sendiri).
     */
    public function prosesPersetujuanPeriode(
        PeriodeGaji $periode,
        string $mode,
        ?int $idPenyetuju = null,
        ?string $catatanPenolakan = null,
    ): PeriodeGaji {
        return DB::transaction(function () use ($periode, $mode, $idPenyetuju, $catatanPenolakan): PeriodeGaji {
            $waktuSekarang = now();

            if ($mode === 'approve') {
                Penggajian::query()
                    ->where('id_periode_gaji', $periode->id)
                    ->where(function ($q): void {
                        $q->whereNull('status')->orWhere('status', '!=', 'paid');
                    })
                    ->update([
                        'status' => 'approved',
                        'id_penyetuju' => $idPenyetuju,
                        'disetujui_pada' => $waktuSekarang,
                    ]);

                $periode->status = 'approved';
                $periode->id_penyetuju = $idPenyetuju;
                $periode->disetujui_pada = $waktuSekarang;
                $periode->catatan_penolakan = null;
                if ($periode->tanggal_bayar === null) {
                    $periode->tanggal_bayar = $waktuSekarang->toDateString();
                }
            } else {
                // Penolakan/revisi Pimpinan atau penarikan pengajuan Admin mengembalikan status ke `draft`.
                Penggajian::query()
                    ->where('id_periode_gaji', $periode->id)
                    ->where(function ($q): void {
                        $q->whereNull('status')->orWhere('status', '!=', 'paid');
                    })
                    ->update([
                        'status' => 'draft',
                        'id_penyetuju' => null,
                        'disetujui_pada' => null,
                    ]);

                $periode->status = 'draft';
                $periode->catatan_penolakan = $catatanPenolakan !== null && trim($catatanPenolakan) !== ''
                    ? trim($catatanPenolakan)
                    : null;
            }

            $periode->save();

            return $periode->refresh();
        });
    }

    /**
     * Pencairan dana gaji (`paid`) untuk seluruh atau sebagian supir berstatus `approved`.
     *
     * Otomatis menandai `angsuran_kasbon` milik supir yang dicairkan pada periode berjalan
     * menjadi `terpotong` dan mengurangi `kasbon.sisa_pinjaman` (status `lunas` bila 0).
     *
     * @param  array<int, int|string>  $daftarIdPenggajian
     *
     * @throws ValidationException
     */
    public function cairkanPeriode(PeriodeGaji $periode, int $idPenyetuju, array $daftarIdPenggajian = []): PeriodeGaji
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $daftarIdPenggajian), fn (int $id): bool => $id > 0)));

        return DB::transaction(function () use ($periode, $idPenyetuju, $ids): PeriodeGaji {
            $kueri = Penggajian::query()
                ->where('id_periode_gaji', $periode->id)
                ->where('status', 'approved');

            if ($ids !== []) {
                $kueri->whereIn('id', $ids);
            }

            $barisSiapCair = $kueri->lockForUpdate()->get();

            if ($barisSiapCair->isEmpty()) {
                throw ValidationException::withMessages([
                    'penggajian' => ['Belum ada baris gaji supir berstatus Disetujui (ACC) yang siap dicairkan.'],
                ]);
            }

            $waktuSekarang = now();
            $tanggalPotong = Carbon::now()->toDateString();

            Penggajian::query()
                ->whereIn('id', $barisSiapCair->pluck('id')->all())
                ->update([
                    'status' => 'paid',
                    'dicairkan_pada' => $waktuSekarang,
                ]);

            $idKaryawanDicairkan = $barisSiapCair->pluck('id_karyawan')->unique()->values()->all();

            foreach ($idKaryawanDicairkan as $idKaryawan) {
                $daftarAngsuran = AngsuranKasbon::query()
                    ->whereHas('kasbon', fn ($q) => $q->where('id_karyawan', $idKaryawan)->whereIn('status', ['approved', 'lunas']))
                    ->where('status', 'terjadwal')
                    ->where(function ($q) use ($periode): void {
                        $q->where('id_periode_gaji', $periode->id);
                        if ($periode->tanggal_mulai && $periode->tanggal_selesai) {
                            $q->orWhereBetween('tanggal_rencana', [
                                $periode->tanggal_mulai->toDateString(),
                                $periode->tanggal_selesai->toDateString(),
                            ]);
                        }
                    })
                    ->lockForUpdate()
                    ->get();

                foreach ($daftarAngsuran as $angsuran) {
                    $angsuran->update([
                        'id_periode_gaji' => $angsuran->id_periode_gaji ?? $periode->id,
                        'status' => 'terpotong',
                        'tanggal_potong' => $tanggalPotong,
                    ]);

                    $kasbon = Kasbon::query()
                        ->where('id', $angsuran->id_kasbon)
                        ->lockForUpdate()
                        ->first();

                    if ($kasbon !== null) {
                        $sisaBaru = max(0, (int) $kasbon->sisa_pinjaman - (int) $angsuran->nominal);
                        $kasbon->update([
                            'sisa_pinjaman' => $sisaBaru,
                            'status' => $sisaBaru === 0 ? 'lunas' : $kasbon->status,
                        ]);
                    }
                }
            }

            $totalBaris = Penggajian::query()->where('id_periode_gaji', $periode->id)->count();
            $belumCair = Penggajian::query()
                ->where('id_periode_gaji', $periode->id)
                ->where(function ($q): void {
                    $q->whereNull('status')->orWhere('status', '!=', 'paid');
                })
                ->count();

            if ($totalBaris > 0 && $belumCair === 0) {
                $periode->status = 'paid';
                $periode->tanggal_bayar = $periode->tanggal_bayar ?? $waktuSekarang->toDateString();
                $periode->dicairkan_pada = $waktuSekarang;
                $periode->id_penyetuju = $periode->id_penyetuju ?? $idPenyetuju;
                $periode->save();
            }

            return $periode->refresh();
        });
    }

    /**
     * Hitung total jadwal angsuran kasbon supir pada periode berjalan (AGENTS.md 3.6).
     *
     * Sumber data wajib berasal dari akumulasi tabel `angsuran_kasbon` (bukan input manual),
     * memfilter kasbon aktif/lunas (`approved` / `lunas`) berdasarkan `id_periode_gaji` atau
     * `tanggal_rencana` dalam rentang siklus bulanan periode (karena cicilan masa depan dapat
     * memiliki `id_periode_gaji = NULL`).
     */
    public function hitungPotonganKasbonPeriode(int $idKaryawan, PeriodeGaji $periode): int
    {
        $kueri = AngsuranKasbon::query()
            ->whereHas('kasbon', fn ($q) => $q->where('id_karyawan', $idKaryawan)->whereIn('status', ['approved', 'lunas']))
            ->where(function ($q) use ($periode) {
                $q->where('id_periode_gaji', $periode->id);
                if ($periode->tanggal_mulai && $periode->tanggal_selesai) {
                    $q->orWhereBetween('tanggal_rencana', [
                        $periode->tanggal_mulai->toDateString(),
                        $periode->tanggal_selesai->toDateString(),
                    ]);
                }
            });

        return (int) $kueri->sum('nominal');
    }

    /**
     * Hitung total biaya sparepart yang sudah disetujui untuk truk pada periode berjalan.
     *
     * Hanya menjumlahkan catatan `biaya_operasional` milik truk terkait dengan
     * `kategori = 'sparepart'` dan `status = 'disetujui'` dalam rentang tanggal periode.
     */
    public function hitungSparepartTrukPeriode(int $idTruk, PeriodeGaji $periode): int
    {
        if ($periode->tanggal_mulai === null || $periode->tanggal_selesai === null) {
            return 0;
        }

        return (int) BiayaOperasional::query()
            ->where('id_truk', $idTruk)
            ->where('kategori', 'sparepart')
            ->where('status', 'disetujui')
            ->whereBetween('tanggal_biaya', [
                $periode->tanggal_mulai->toDateString(),
                $periode->tanggal_selesai->toDateString(),
            ])
            ->sum('nominal');
    }
}
