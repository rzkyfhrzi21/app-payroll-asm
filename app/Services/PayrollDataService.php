<?php

namespace App\Services;

use App\Models\AngsuranKasbon;
use App\Models\BiayaOperasional;
use App\Models\JenisTruk;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\Truk;
use App\Models\User;
use App\Support\FormatNomorTelepon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * LAYANAN: PUSAT PENYEDIA DATA PENGGAJIAN (PAYROLL DATA SERVICE)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Gudang Arsip & Juru Rangkum Data Keuangan".
 * Controller tidak perlu pusing menulis query SQL panjang untuk menghitung
 * total ritase, total komisi supir, sisa kasbon, atau komposisi truk di garasi.
 * Juru rangkum ini yang bertugas mengambil data mentah dari database, menyaringnya
 * sesuai hak akses, lalu menyajikan bentuk data rapi yang siap ditampilkan
 * pada layar Blade (Admin, Supir, maupun Eksekutif Pimpinan).
 * 
 * Fitur Penyelamat Kinerja (Singgahan / Cache Request):
 * Mengingat satu halaman web bisa memanggil data supir berulang kali (di tabel,
 * kartu statistik, dan header), layanan ini menyimpan hasil hitungan sementara
 * di memori agar database tidak lelah dieksekusi berkali-kali.
 * ============================================================================
 */
class PayrollDataService
{
    /**
     * Wadah Singgahan Sementara (Memory Cache Request)
     *
     * @var array<string, mixed>
     */
    private array $singgahan = [];
    public function bersihkanSinggahan(?string $kunci = null): void
    {
        if ($kunci !== null) {
            unset($this->singgahan[$kunci]);
        } else {
            $this->singgahan = [];
        }
    }


    private const NAMA_BULAN = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    private const LABEL_STATUS_PERIODE = [
        'draft' => 'Draf Perhitungan',
        'calculated' => 'Sudah Dihitung',
        'pending' => 'Menunggu ACC Pimpinan',
        'approved' => 'Disetujui Pimpinan',
        'paid' => 'Sudah Dibayar',
    ];

    public function ambilPeriodeTerbaru(): ?PeriodeGaji
    {
        if (! array_key_exists('periode_terbaru', $this->singgahan)) {
            // Utamakan periode berjalan (tanggal_selesai NULL) agar input surat jalan
            // baru selalu masuk ke periode yang sedang dibuka Admin.
            $this->singgahan['periode_terbaru'] = $this->ambilPeriodeBerjalan()
                ?? PeriodeGaji::query()
                    ->orderByDesc('tanggal_mulai')
                    ->orderByDesc('id')
                    ->first();
        }

        return $this->singgahan['periode_terbaru'];
    }

    /**
     * Periode yang masih berjalan (belum ditutup buku / tanggal_selesai NULL).
     */
    public function ambilPeriodeBerjalan(): ?PeriodeGaji
    {
        if (! array_key_exists('periode_berjalan', $this->singgahan)) {
            $this->singgahan['periode_berjalan'] = PeriodeGaji::query()
                ->whereNull('tanggal_selesai')
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id')
                ->first();
        }

        return $this->singgahan['periode_berjalan'];
    }

    public function ambilPeriodeBerdasarkanKode(?string $kodePeriode): ?PeriodeGaji
    {
        if ($kodePeriode === null || trim($kodePeriode) === '') {
            return null;
        }

        return PeriodeGaji::query()
            ->where('kode', trim($kodePeriode))
            ->first();
    }

    /**
     * Daftar periode untuk dropdown, terbaru lebih dulu.
     */
    public function daftarOpsiPeriode(): array
    {
        return PeriodeGaji::query()
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PeriodeGaji $periode) => [
                'kode' => $periode->kode,
                'label' => $periode->kode,
            ])
            ->all();
    }

    public function formatPeriode(?PeriodeGaji $periode): array
    {
        if ($periode === null) {
            return [
                'code' => '—',
                'range' => 'Belum ada periode penggajian',
                'cut_off' => '—',
                'work_days' => 0,
                'status' => 'Belum Ada Data',
                'status_code' => null,
                'pay_date' => '—',
                'terbuka' => false,
            ];
        }

        $kunciSinggahan = 'format_periode_'.$periode->id;

        if (isset($this->singgahan[$kunciSinggahan])) {
            return $this->singgahan[$kunciSinggahan];
        }

        // Jumlah tanggal yang benar-benar ada pengantarannya (bukan hari kalender).
        $jumlahHariPengantaran = Ritase::query()
            ->where('id_periode_gaji', $periode->id)
            ->distinct()
            ->count('tanggal_ritase');

        // Periode berjalan (belum tutup buku) ditampilkan "{mulai} — Sekarang".
        $periodeTerbuka = $periode->tanggal_selesai === null;
        $labelRentang = $periodeTerbuka
            ? $this->formatTanggal($periode->tanggal_mulai).' — Sekarang'
            : $this->formatRentangTanggal($periode->tanggal_mulai, $periode->tanggal_selesai);

        return $this->singgahan[$kunciSinggahan] = [
            'code' => $periode->kode,
            'range' => $labelRentang,
            'cut_off' => $periodeTerbuka
                ? 'Belum ditutup'
                : $this->formatTanggal($periode->tanggal_selesai?->copy()->addDay()),
            'work_days' => (int) $jumlahHariPengantaran,
            'status' => $this->labelStatusPeriode($periode->status),
            'status_code' => $periode->status,
            'pay_date' => $this->formatTanggal($periode->tanggal_bayar),
            'terbuka' => $periodeTerbuka,
        ];
    }

    public function ringkasanPayroll(?PeriodeGaji $periode): array
    {
        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        $jumlahRit = (int) $daftarPenggajian->sum('total_rit');
        $jumlahTonaseKg = (int) $daftarPenggajian->sum('total_tonase_kg');
        $totalAngkutanKotor = (int) $daftarPenggajian->sum('total_angkutan_kotor');
        $totalUangJalan = (int) $daftarPenggajian->sum('total_uang_jalan');
        $totalSparepart = (int) $daftarPenggajian->sum('biaya_sparepart');
        $totalGajiBersih = (int) $daftarPenggajian->sum('gaji_bersih');
        $totalHasilBersihArmada = (int) $daftarPenggajian->sum('hasil_bersih_armada');
        $totalGajiKomisi = (int) $daftarPenggajian->sum('gaji_komisi');
        $totalPotonganBpjs = (int) $daftarPenggajian->sum('potongan_bpjs');
        $totalPotonganKasbon = (int) $daftarPenggajian->sum('potongan_kasbon');

        $jumlahPeringatan = $daftarPenggajian
            ->filter(fn (Penggajian $penggajian) => $this->tentukanPeringatan($penggajian) !== null)
            ->count();

        $sjBelumVerifikasi = $periode === null
            ? 0
            : Ritase::query()
                ->where('id_periode_gaji', $periode->id)
                ->whereIn('status', ['draft', 'terbit', 'sedang_dikirim', 'menunggu_verifikasi'])
                ->count();

        $jumlahSudahAcc = $daftarPenggajian
            ->filter(fn (Penggajian $penggajian): bool => in_array($this->statusEfektifBarisPenggajian($penggajian, $periode), ['approved', 'paid'], true))
            ->count();
        $jumlahSudahCair = $daftarPenggajian
            ->filter(fn (Penggajian $penggajian): bool => $this->statusEfektifBarisPenggajian($penggajian, $periode) === 'paid')
            ->count();
        $siapCair = $daftarPenggajian
            ->filter(fn (Penggajian $penggajian): bool => $this->statusEfektifBarisPenggajian($penggajian, $periode) === 'approved')
            ->isNotEmpty();

        return [
            'rit' => $jumlahRit,
            'tonnage' => (int) round($jumlahTonaseKg / 1000),
            'haulage_gross' => $totalAngkutanKotor,
            'trip_money' => $totalUangJalan,
            'sparepart' => $totalSparepart,
            'cash_required' => $totalGajiBersih,
            'drivers' => $daftarPenggajian->count(),
            'sample_rit' => $jumlahRit,
            'sample_tonnage' => (int) round($jumlahTonaseKg / 1000),
            'sample_net_result' => $totalHasilBersihArmada,
            'sample_gross_pay' => $totalGajiKomisi,
            'sample_deduction' => $totalPotonganBpjs + $totalPotonganKasbon,
            'sample_trip_money' => $totalUangJalan,
            'sample_net_pay' => $totalGajiBersih,
            'warning_count' => $jumlahPeringatan,
            'kasbon_outstanding' => (int) Kasbon::query()
                ->where('status', '!=', 'lunas')
                ->sum('sisa_pinjaman'),
            'kasbon_deduction' => $totalPotonganKasbon,
            'sj_draft_atau_menunggu' => $sjBelumVerifikasi,
            'sj_belum_verifikasi' => $sjBelumVerifikasi,
            'catatan_penolakan' => $periode?->catatan_penolakan,
            'jumlah_sudah_acc' => $jumlahSudahAcc,
            'jumlah_sudah_cair' => $jumlahSudahCair,
            'siap_cair' => $siapCair,
        ];
    }

    /**
     * Baris tabel payroll admin. Setiap baris membawa ritase miliknya sendiri
     * agar drawer detail tidak lagi memakai daftar global.
     */
    public function barisPayroll(?PeriodeGaji $periode): array
    {
        if ($periode === null) {
            return [];
        }

        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);
        $ritasePerKaryawan = $this->kueriRitasePayroll($periode)->get()->groupBy('id_karyawan');

        return $daftarPenggajian
            ->map(fn (Penggajian $penggajian) => $this->petakanBarisPayroll($penggajian, $periode, $ritasePerKaryawan))
            ->values()
            ->all();
    }

    /**
     * Versi terpaginasi untuk halaman Payroll Bulanan.
     *
     * @param  array<string, mixed>  $filter
     */
    public function barisPayrollTerpaginasi(?PeriodeGaji $periode, array $filter, int $perHalaman): LengthAwarePaginator
    {
        if ($periode === null) {
            return Penggajian::query()->whereRaw('1 = 0')->paginate($perHalaman);
        }

        $kueri = Penggajian::query()
            ->with(['karyawan', 'truk'])
            ->join('karyawan', 'karyawan.id', '=', 'penggajian.id_karyawan')
            ->select('penggajian.*')
            ->where('penggajian.id_periode_gaji', $periode->id);

        $kataKunci = trim((string) ($filter['cari'] ?? ''));

        if ($kataKunci !== '') {
            $kueri->where(function (Builder $sub) use ($kataKunci): void {
                $sub->where('karyawan.nama_lengkap', 'like', "%{$kataKunci}%")
                    ->orWhere('karyawan.nomor_induk_karyawan', 'like', "%{$kataKunci}%")
                    ->orWhereHas('truk', function (Builder $truk) use ($kataKunci): void {
                        $truk->where('nomor_polisi', 'like', "%{$kataKunci}%")
                            ->orWhere('merek_model', 'like', "%{$kataKunci}%");
                    });
            });
        }

        // Batas data milik supir yang sedang login (dipakai endpoint AJAX peran supir).
        if (array_key_exists('id_karyawan', $filter) && $filter['id_karyawan'] !== null) {
            $kueri->where('penggajian.id_karyawan', (int) $filter['id_karyawan']);
        }

        $paginator = $kueri
            ->orderBy('karyawan.nomor_induk_karyawan')
            ->orderBy('penggajian.id')
            ->paginate($perHalaman)
            ->withQueryString();

        $idKaryawan = collect($paginator->items())
            ->pluck('id_karyawan')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $ritasePerKaryawan = $idKaryawan === []
            ? collect()
            : $this->kueriRitasePayroll($periode)->whereIn('id_karyawan', $idKaryawan)->get()->groupBy('id_karyawan');

        return $paginator->through(
            fn (Penggajian $penggajian) => $this->petakanBarisPayroll($penggajian, $periode, $ritasePerKaryawan)
        );
    }

    private function kueriRitasePayroll(PeriodeGaji $periode): Builder
    {
        return Ritase::query()
            ->with('rute')
            ->where('id_periode_gaji', $periode->id)
            ->orderByDesc('tanggal_ritase')
            ->orderByDesc('id');
    }

    /**
     * @param  Collection<int, Collection<int, Ritase>>  $ritasePerKaryawan
     * @return array<string, mixed>
     */
    private function petakanBarisPayroll(Penggajian $penggajian, PeriodeGaji $periode, Collection $ritasePerKaryawan): array
    {
        $karyawan = $penggajian->karyawan;
        $truk = $penggajian->truk;
        $catatanPeringatan = $this->tentukanPeringatan($penggajian);

        $bagianPotongan = [];

        if ($penggajian->potongan_bpjs > 0) {
            $bagianPotongan[] = 'BPJS '.$this->formatRibuanSingkat($penggajian->potongan_bpjs);
        }

        if ($penggajian->potongan_kasbon > 0) {
            $bagianPotongan[] = 'Kasbon '.$this->formatRibuanSingkat($penggajian->potongan_kasbon);
        }

        $gajiKomisi = (int) $penggajian->gaji_komisi;
        $potonganBpjs = (int) $penggajian->potongan_bpjs;
        $potonganKasbon = (int) $penggajian->potongan_kasbon;
        $potonganResmi = $potonganBpjs + $potonganKasbon;
        $gajiBersih = (int) $penggajian->gaji_bersih;
        $selisihHistoris = ($gajiKomisi - $potonganResmi) - $gajiBersih;
        $potonganHistoris = max(0, $selisihHistoris);
        $penambahanHistoris = max(0, -$selisihHistoris);
        $persentaseKomisiBp = (int) ($penggajian->persentase_komisi_bp ?: 1000);
        $persentaseKomisi = $persentaseKomisiBp / 100;

        return [
            'id' => (int) $penggajian->id,
            'id_penggajian' => (int) $penggajian->id,
            'id_karyawan' => (int) $penggajian->id_karyawan,
            'initial' => $this->inisialNama($karyawan?->nama_lengkap),
            'foto_profil' => $karyawan?->foto_profil ?: $this->avatarSvgDataUri($this->inisialNama($karyawan?->nama_lengkap)),
            'name' => $karyawan?->nama_lengkap ?? 'Tanpa Nama',
            'plate' => $truk?->nomor_polisi ?? '—',
            'truck' => $truk?->merek_model ?? '—',
            'rit' => (int) $penggajian->total_rit,
            'tonnage' => $this->formatTonase($penggajian->total_tonase_kg),
            'haulage_gross' => (int) $penggajian->total_angkutan_kotor,
            'potongan_operasional' => (int) $penggajian->potongan_operasional,
            'omzet' => (int) $penggajian->angkutan_bersih,
            'sparepart' => (int) $penggajian->biaya_sparepart,
            'bpjs' => $potonganBpjs,
            'kasbon' => $potonganKasbon,
            'net_result' => (int) $penggajian->hasil_bersih_armada,
            'persentase_komisi_bp' => $persentaseKomisiBp,
            'persentase_komisi' => $persentaseKomisi,
            'commission' => $gajiKomisi,
            'gross_pay' => $gajiKomisi,
            'deduction' => $potonganResmi,
            'deduction_note' => $bagianPotongan === [] ? 'Tidak ada potongan' : implode(' + ', $bagianPotongan),
            'potongan_historis' => $potonganHistoris,
            'penambahan_historis' => $penambahanHistoris,
            'trip_money' => (int) $penggajian->total_uang_jalan,
            'net_pay' => $gajiBersih,
            'warning' => $catatanPeringatan !== null,
            'warning_note' => $catatanPeringatan,
            'bank' => $karyawan?->nama_bank ?? '—',
            'account' => $karyawan?->nomor_rekening ?? '—',
            'url_cetak_slip' => route('admin.reports.slip', [
                'periode' => $periode->kode,
                'penggajian' => $penggajian->id,
            ]),
            'trips' => $this->susunBarisRitase(
                $ritasePerKaryawan->get($penggajian->id_karyawan, collect())
            ),
        ];
    }

    public function dataTrukMaster(): array
    {
        $daftarTruk = $this->kueriTrukMaster()
            ->orderByDesc('id')
            ->get();

        return [
            'truk' => $daftarTruk
                ->map(fn (Truk $truk) => $this->petakanBarisTruk($truk))
                ->all(),
            'ringkasan' => [
                'total' => $daftarTruk->count(),
                'dengan_ritase' => $daftarTruk->where('ritase_count', '>', 0)->count(),
                'total_ritase' => (int) $daftarTruk->sum('ritase_count'),
                'total_tonase' => $this->formatTonase((int) $daftarTruk->sum('total_tonase_kg')),
                'aktif' => $daftarTruk->where('status', 'aktif')->count(),
                'nonaktif' => $daftarTruk->where('status', 'nonaktif')->count(),
                'tanpa_supir' => $daftarTruk->whereNull('id_karyawan')->count(),
                'dengan_supir' => $daftarTruk->whereNotNull('id_karyawan')->count(),
            ],
        ];
    }

    /**
     * Versi terpaginasi untuk halaman Unit Armada Truk.
     *
     * @param  array<string, mixed>  $filter
     */
    public function dataTrukMasterTerpaginasi(array $filter, int $perHalaman): LengthAwarePaginator
    {
        $kueri = $this->kueriTrukMaster();

        $kataKunci = trim((string) ($filter['cari'] ?? ''));
        $status = strtolower(trim((string) ($filter['status'] ?? 'all')));
        $penugasan = (string) ($filter['penugasan'] ?? 'all');
        $jenis = trim((string) ($filter['jenis'] ?? 'all'));

        if (in_array($status, ['aktif', 'nonaktif'], true)) {
            $kueri->where('status', $status);
        }

        if ($penugasan === 'terpakai' || $penugasan === 'dipegang') {
            $kueri->whereNotNull('id_karyawan');
        } elseif ($penugasan === 'kosong' || $penugasan === 'cadangan') {
            $kueri->whereNull('id_karyawan');
        }

        if ($jenis !== '' && $jenis !== 'all') {
            $kueri->whereHas('jenisTruk', fn (Builder $sub) => $sub->where('nama', $jenis));
        }

        if ($kataKunci !== '') {
            $kueri->where(function (Builder $sub) use ($kataKunci): void {
                $sub->where('nomor_polisi', 'like', "%{$kataKunci}%")
                    ->orWhere('merek_model', 'like', "%{$kataKunci}%")
                    ->orWhereHas('karyawan', fn (Builder $supir) => $supir->where('nama_lengkap', 'like', "%{$kataKunci}%"));
            });
        }

        // Batas data milik supir yang sedang login (dipakai endpoint AJAX peran supir).
        if (array_key_exists('id_karyawan', $filter) && $filter['id_karyawan'] !== null) {
            $kueri->where('id_karyawan', (int) $filter['id_karyawan']);
        }

        return $kueri
            ->orderByDesc('id')
            ->paginate($perHalaman)
            ->withQueryString()
            ->through(fn (Truk $truk) => $this->petakanBarisTruk($truk));
    }

    private function kueriTrukMaster(): Builder
    {
        return Truk::query()
            ->with(['karyawan:id,nama_lengkap,nomor_induk_karyawan', 'jenisTruk:id,nama'])
            ->withCount(['ritase', 'penggajian'])
            ->withSum('ritase as total_tonase_kg', 'tonase_kg');
    }

    /**
     * @return array<string, mixed>
     */
    private function petakanBarisTruk(Truk $truk): array
    {
        return [
            'id' => $truk->id,
            'nomor_polisi' => $truk->nomor_polisi,
            'merek_model' => $truk->merek_model,
            'foto_unit' => $truk->foto_unit ?: $this->avatarSvgDataUri($this->inisialNama($truk->nomor_polisi), '#0F172A'),
            'status' => $truk->status,
            'id_karyawan' => $truk->id_karyawan,
            'id_jenis_truk' => $truk->id_jenis_truk,
            'nama_jenis' => $truk->jenisTruk?->nama ?? '',
            'nama_supir' => $truk->karyawan?->nama_lengkap ?? '',
            'nip_supir' => $truk->karyawan?->nomor_induk_karyawan ?? '',
            'ritase_count' => (int) $truk->ritase_count,
            'penggajian_count' => (int) $truk->penggajian_count,
            'tonase' => $this->formatTonase((int) $truk->total_tonase_kg),
        ];
    }

    /**
     * Ringkasan kecil untuk kartu status periode penggajian pada sidebar admin.
     */
    public function ringkasanKasbonSidebar(): array
    {
        $periode = $this->ambilPeriodeTerbaru();
        $ringkasanKasbonAktif = Kasbon::query()
            ->where('status', '!=', 'lunas')
            ->selectRaw('coalesce(sum(sisa_pinjaman), 0) as total, count(distinct id_karyawan) as jumlah_supir')
            ->first();
        $totalKasbon = (int) $ringkasanKasbonAktif->total;
        $jumlahSupir = (int) $ringkasanKasbonAktif->jumlah_supir;

        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);
        $totalGajiBersih = (int) $daftarPenggajian->sum('gaji_bersih');
        $totalRit = (int) $daftarPenggajian->sum('total_rit');

        return [
            'nominal' => 'Rp '.number_format($totalKasbon, 0, ',', '.'),
            'jumlah_supir' => $jumlahSupir,
            'periode' => $periode?->kode ?? 'Tanpa Periode',
            'total_gaji_bersih' => 'Rp '.number_format($totalGajiBersih, 0, ',', '.'),
            'jumlah_supir_payroll' => $daftarPenggajian->count(),
            'total_rit' => $totalRit,
            'status_periode' => $this->labelStatusPeriode($periode?->status ?? 'draft'),
        ];
    }

    /**
     * Memeriksa apakah data master masih terikat ritase/penggajian.
     * Dipakai sebagai penjaga sebelum menghapus data.
     */
    public function periksaKetergantungan(string $jenis, int $id): array
    {
        return match ($jenis) {
            'karyawan' => [
                'ritase' => Ritase::query()->where('id_karyawan', $id)->count(),
                'penggajian' => Penggajian::query()->where('id_karyawan', $id)->count(),
                'kasbon' => Kasbon::query()->where('id_karyawan', $id)->count(),
            ],
            'truk' => [
                'ritase' => Ritase::query()->where('id_truk', $id)->count(),
                'penggajian' => Penggajian::query()->where('id_truk', $id)->count(),
            ],
            'rute' => [
                'ritase' => Ritase::query()->where('id_rute', $id)->count(),
            ],
            default => [],
        };
    }

    /**
     * Daftar supir aktif untuk dropdown relasi armada.
     */
    public function daftarSupirAktif(): array
    {
        return Karyawan::query()
            ->select(['id', 'nama_lengkap', 'nomor_induk_karyawan'])
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn (Karyawan $karyawan) => [
                'id' => $karyawan->id,
                'nama' => $karyawan->nama_lengkap,
                'nip' => $karyawan->nomor_induk_karyawan,
            ])
            ->all();
    }

    /**
     * Supir untuk dropdown pemegang unit, dipisah dua kelompok:
     * - belum_pegang : supir aktif yang belum memegang unit (diprioritaskan)
     * - sudah_pegang : supir yang sudah memegang unit beserta platnya
     */
    public function supirUntukPilihanArmada(): array
    {
        $daftarSupir = Karyawan::query()
            ->where('status', 'aktif')
            ->with(['truk:id,id_karyawan,nomor_polisi'])
            ->orderBy('nama_lengkap')
            ->get();

        $pemetaan = $daftarSupir->map(fn (Karyawan $karyawan) => [
            'id' => $karyawan->id,
            'nama' => $karyawan->nama_lengkap,
            'nip' => $karyawan->nomor_induk_karyawan,
            'plate' => $karyawan->truk->first()?->nomor_polisi,
        ]);

        return [
            'belum_pegang' => $pemetaan->whereNull('plate')->values()->all(),
            'sudah_pegang' => $pemetaan->whereNotNull('plate')->values()->all(),
        ];
    }

    /**
     * Daftar jenis truk aktif untuk pilihan di modal armada.
     */
    public function daftarJenisTruk(): array
    {
        return \App\Models\JenisTruk::query()
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get()
            ->map(fn (\App\Models\JenisTruk $jenis) => [
                'id' => $jenis->id,
                'nama' => $jenis->nama,
                'merek_model' => $jenis->merek_model,
            ])
            ->all();
    }

    /**
     * Data komisi, BPJS, dan kasbon per supir untuk halaman Kasbon & Potongan.
     * Ini yang dulu ditampilkan di halaman Data Master, dipindah ke sini agar
     * pengaturannya satu tempat dengan kasbon.
     */
    public function dataKomisiDanPotongan(): array
    {
        $periode = $this->ambilPeriodeTerbaru();

        // Agregat kasbon dihitung di SQL (withCount/withSum), dan penggajian hanya
        // diambil untuk periode terbaru, bukan seluruh riwayat tiap supir.
        $daftarKaryawan = Karyawan::query()
            ->select(['id', 'nomor_induk_karyawan', 'nama_lengkap', 'status', 'persentase_komisi_bp', 'potongan_bpjs_bulanan'])
            ->withCount(['kasbon'])
            ->withSum(['kasbon as sisa_kasbon_berjalan' => fn ($kueri) => $kueri->where('status', '!=', 'lunas')], 'sisa_pinjaman')
            ->orderByDesc('id')
            ->get();

        $penggajianPerKaryawan = $periode === null
            ? collect()
            : Penggajian::query()
                ->select(['id', 'id_karyawan', 'gaji_komisi', 'gaji_bersih', 'potongan_kasbon'])
                ->where('id_periode_gaji', $periode->id)
                ->orderByDesc('id')
                ->get()
                ->unique('id_karyawan')
                ->keyBy('id_karyawan');

        return $daftarKaryawan
            ->map(function (Karyawan $karyawan) use ($periode, $penggajianPerKaryawan): array {
                $sisaKasbon = (int) $karyawan->sisa_kasbon_berjalan;

                $penggajianPeriode = $periode === null
                    ? null
                    : $penggajianPerKaryawan->get($karyawan->id);

                return [
                    'id' => $karyawan->id,
                    'nip' => $karyawan->nomor_induk_karyawan,
                    'nama' => $karyawan->nama_lengkap,
                    'status' => $karyawan->status,
                    'komisi_persen' => $this->formatPersentaseKomisi($karyawan->persentase_komisi_bp),
                    'komisi_bp' => (int) $karyawan->persentase_komisi_bp,
                    'bpjs' => (int) $karyawan->potongan_bpjs_bulanan,
                    'kasbon_sisa' => $sisaKasbon,
                    'kasbon_aktif' => (int) $karyawan->kasbon_count,
                    'komisi_periode' => $penggajianPeriode === null ? 0 : (int) $penggajianPeriode->gaji_komisi,
                    'gaji_bersih_periode' => $penggajianPeriode === null ? 0 : (int) $penggajianPeriode->gaji_bersih,
                    'potongan_kasbon_periode' => $penggajianPeriode === null ? 0 : (int) $penggajianPeriode->potongan_kasbon,
                ];
            })
            ->all();
    }

    /**
     * Versi terpaginasi untuk tabel Potongan BPJS: hanya supir pada halaman aktif
     * yang diambil dari database; pencarian dan filter status berjalan di SQL.
     * Kunci baris sama dengan dataKomisiDanPotongan().
     *
     * @param  array<string, mixed>  $filter
     */
    public function komponenSupirTerpaginasi(array $filter, int $perHalaman): LengthAwarePaginator
    {
        $periode = $this->ambilPeriodeTerbaru();
        $kataKunci = trim((string) ($filter['cari'] ?? ''));
        $status = strtolower(trim((string) ($filter['status'] ?? 'all')));

        $kueri = Karyawan::query()
            ->select(['id', 'nomor_induk_karyawan', 'nama_lengkap', 'status', 'persentase_komisi_bp', 'potongan_bpjs_bulanan'])
            ->withCount(['kasbon'])
            ->withSum(['kasbon as sisa_kasbon_berjalan' => fn ($sub) => $sub->where('status', '!=', 'lunas')], 'sisa_pinjaman');

        if ($status !== '' && $status !== 'all') {
            $kueri->where('status', $status);
        }

        if ($kataKunci !== '') {
            $kueri->where(function (Builder $sub) use ($kataKunci): void {
                $sub->where('nama_lengkap', 'like', "%{$kataKunci}%")
                    ->orWhere('nomor_induk_karyawan', 'like', "%{$kataKunci}%");
            });
        }

        $paginator = $kueri->orderByDesc('id')->paginate($perHalaman)->withQueryString();

        // Penggajian periode terbaru hanya untuk supir di halaman ini.
        $penggajianPerKaryawan = $periode === null || $paginator->isEmpty()
            ? collect()
            : Penggajian::query()
                ->select(['id', 'id_karyawan', 'gaji_komisi', 'gaji_bersih', 'potongan_kasbon'])
                ->where('id_periode_gaji', $periode->id)
                ->whereIn('id_karyawan', $paginator->getCollection()->pluck('id'))
                ->orderByDesc('id')
                ->get()
                ->unique('id_karyawan')
                ->keyBy('id_karyawan');

        return $paginator->through(function (Karyawan $karyawan) use ($penggajianPerKaryawan): array {
            $penggajian = $penggajianPerKaryawan->get($karyawan->id);

            return [
                'id' => $karyawan->id,
                'nip' => $karyawan->nomor_induk_karyawan,
                'nama' => $karyawan->nama_lengkap,
                'status' => $karyawan->status,
                'komisi_persen' => $this->formatPersentaseKomisi($karyawan->persentase_komisi_bp),
                'komisi_bp' => (int) $karyawan->persentase_komisi_bp,
                'bpjs' => (int) $karyawan->potongan_bpjs_bulanan,
                'kasbon_sisa' => (int) $karyawan->sisa_kasbon_berjalan,
                'kasbon_aktif' => (int) $karyawan->kasbon_count,
                'komisi_periode' => $penggajian === null ? 0 : (int) $penggajian->gaji_komisi,
                'gaji_bersih_periode' => $penggajian === null ? 0 : (int) $penggajian->gaji_bersih,
                'potongan_kasbon_periode' => $penggajian === null ? 0 : (int) $penggajian->potongan_kasbon,
            ];
        });
    }

    /**
     * Ringkasan komisi & potongan periode aktif.
     */
    public function ringkasanKomisiDanPotongan(?array $dataKomisi = null): array
    {
        $data = $dataKomisi ?? $this->dataKomisiDanPotongan();
        $periode = $this->ambilPeriodeTerbaru();

        return [
            'periode' => $periode?->kode ?? '—',
            'jumlah_supir' => count($data),
            'supir_aktif' => collect($data)->where('status', 'aktif')->count(),
            'total_komisi' => (int) collect($data)->sum('komisi_periode'),
            'total_bpjs' => (int) collect($data)->sum('bpjs'),
            'total_kasbon' => (int) collect($data)->sum('kasbon_sisa'),
            'total_potongan_kasbon' => (int) collect($data)->sum('potongan_kasbon_periode'),
        ];
    }

    /**
     * Membuat kasbon baru beserta jadwal cicilan otomatis.
     * Pembagian rata; sisa pembulatan masuk cicilan terakhir agar total pas.
     *
     * @param  array<string, mixed>  $data
     */
    public function buatKasbon(array $data): Kasbon
    {
        $total = (int) $data['total_pinjaman'];
        $tenor = max(1, (int) $data['jumlah_tenor']);

        // Pembulatan ke ribuan terdekat, sisa masuk cicilan terakhir.
        $cicilanDasar = (int) (floor($total / $tenor / 1000) * 1000);
        $sisa = $total - ($cicilanDasar * ($tenor - 1));

        $periodeMulai = isset($data['id_periode_gaji_mulai'])
            ? PeriodeGaji::query()->find($data['id_periode_gaji_mulai'])
            : null;

        $tanggalAmbil = Carbon::parse($data['tanggal_pengambilan']);

        $kasbon = Kasbon::create([
            'id_karyawan' => $data['id_karyawan'],
            'tanggal_pengambilan' => $tanggalAmbil,
            'id_periode_gaji_mulai' => $periodeMulai?->id,
            'total_pinjaman' => $total,
            'nominal_angsuran' => $cicilanDasar,
            'jumlah_tenor' => $tenor,
            'sisa_pinjaman' => $total,
            'keterangan' => $data['keterangan'] ?? null,
            'status' => 'approved',
        ]);

        $tanggalAcuan = $periodeMulai?->tanggal_bayar
            ?? $periodeMulai?->tanggal_selesai
            ?? $tanggalAmbil;

        for ($urutan = 1; $urutan <= $tenor; $urutan++) {
            $nominal = $urutan === $tenor ? $sisa : $cicilanDasar;

            AngsuranKasbon::create([
                'id_kasbon' => $kasbon->id,
                'id_periode_gaji' => $urutan === 1 ? $periodeMulai?->id : null,
                'urutan' => $urutan,
                'nominal' => $nominal,
                'tanggal_rencana' => Carbon::parse($tanggalAcuan)->addMonthsNoOverflow($urutan - 1),
                'status' => 'terjadwal',
            ]);
        }

        return $kasbon->load('karyawan');
    }

    /**
     * Memperbarui data kasbon. Nominal & tenor dikunci bila sudah ada
     * cicilan yang terpotong agar slip lama tidak berubah.
     *
     * @param  array<string, mixed>  $data
     */
    public function perbaruiKasbon(Kasbon $kasbon, array $data): Kasbon
    {
        $kasbon->update([
            'tanggal_pengambilan' => $data['tanggal_pengambilan'] ?? $kasbon->tanggal_pengambilan,
            'id_periode_gaji_mulai' => $data['id_periode_gaji_mulai'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'status' => $data['status'] ?? $kasbon->status,
        ]);

        return $kasbon;
    }

    /**
     * Menandai satu cicilan sudah dipotong, lalu mengurangi sisa pinjaman.
     */
    public function potongAngsuranKasbon(Kasbon $kasbon, int $urutan): void
    {
        $angsuran = $kasbon->angsuran()->where('urutan', $urutan)->first();

        if ($angsuran === null || $angsuran->status === 'terpotong') {
            return;
        }

        DB::transaction(function () use ($kasbon, $angsuran): void {
            $angsuran->update([
                'status' => 'terpotong',
                'tanggal_potong' => Carbon::now()->toDateString(),
            ]);

            $sisaBaru = max(0, (int) $kasbon->sisa_pinjaman - (int) $angsuran->nominal);

            $kasbon->update([
                'sisa_pinjaman' => $sisaBaru,
                'status' => $sisaBaru === 0 ? 'lunas' : $kasbon->status,
            ]);
        });
    }

    /**
     * Daftar kasbon untuk halaman Kasbon & Potongan.
     */
    public function daftarKasbon(): array
    {
        return $this->kueriKasbon()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Kasbon $kasbon) => $this->petakanBarisKasbon($kasbon))
            ->all();
    }

    /**
     * Versi terpaginasi untuk halaman Kasbon & Potongan.
     *
     * @param  array<string, mixed>  $filter
     */
    public function daftarKasbonTerpaginasi(array $filter, int $perHalaman): LengthAwarePaginator
    {
        $kueri = $this->kueriKasbon();

        $kataKunci = trim((string) ($filter['cari'] ?? ''));
        $status = strtolower(trim((string) ($filter['status'] ?? 'all')));
        $supir = trim((string) ($filter['supir'] ?? 'all'));
        $tanggalDari = (string) ($filter['tanggal_dari'] ?? '');
        $tanggalSampai = (string) ($filter['tanggal_sampai'] ?? '');

        if ($status !== '' && $status !== 'all') {
            $kueri->where('status', $status);
        }

        if ($supir !== '' && $supir !== 'all') {
            $kueri->whereHas('karyawan', fn (Builder $sub) => $sub->where('nomor_induk_karyawan', $supir));
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalDari) === 1) {
            $kueri->whereDate('tanggal_pengambilan', '>=', $tanggalDari);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSampai) === 1) {
            $kueri->whereDate('tanggal_pengambilan', '<=', $tanggalSampai);
        }

        if ($kataKunci !== '') {
            $kueri->where(function (Builder $sub) use ($kataKunci): void {
                $sub->where('keterangan', 'like', "%{$kataKunci}%")
                    ->orWhereHas('karyawan', function (Builder $karyawan) use ($kataKunci): void {
                        $karyawan->where('nama_lengkap', 'like', "%{$kataKunci}%")
                            ->orWhere('nomor_induk_karyawan', 'like', "%{$kataKunci}%");
                    });
            });
        }

        // Batas data milik supir yang sedang login (dipakai endpoint AJAX peran supir).
        if (array_key_exists('id_karyawan', $filter) && $filter['id_karyawan'] !== null) {
            $kueri->where('id_karyawan', (int) $filter['id_karyawan']);
        }

        return $kueri
            ->orderByDesc('id')
            ->paginate($perHalaman)
            ->withQueryString()
            ->through(fn (Kasbon $kasbon) => $this->petakanBarisKasbon($kasbon));
    }

    /**
     * Pilihan supir yang punya kasbon, untuk dropdown filter.
     *
     * @return array<int, array{nip: string, nama: string}>
     */
    public function pilihanSupirKasbon(): array
    {
        return Kasbon::query()
            ->join('karyawan', 'karyawan.id', '=', 'kasbon.id_karyawan')
            ->selectRaw('karyawan.nomor_induk_karyawan as nip, karyawan.nama_lengkap as nama')
            ->distinct()
            ->orderBy('karyawan.nama_lengkap')
            ->get()
            ->map(fn ($baris) => [
                'nip' => (string) $baris->nip,
                'nama' => (string) $baris->nama,
            ])
            ->all();
    }

    private function kueriKasbon(): Builder
    {
        return Kasbon::query()
            ->select([
                'id', 'id_karyawan', 'tanggal_pengambilan', 'total_pinjaman', 'nominal_angsuran',
                'jumlah_tenor', 'sisa_pinjaman', 'keterangan', 'status',
            ])
            ->with([
                'karyawan:id,nama_lengkap,nomor_induk_karyawan',
                'angsuran:id,id_kasbon,urutan,nominal,tanggal_rencana,status',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function petakanBarisKasbon(Kasbon $kasbon): array
    {
        $angsuranTerbayar = $kasbon->angsuran->where('status', 'terpotong');
        $angsuranBerikut = $kasbon->angsuran->firstWhere('status', 'terjadwal');

        return [
            'id' => $kasbon->id,
            'nip' => $kasbon->karyawan?->nomor_induk_karyawan ?? '—',
            'nama' => $kasbon->karyawan?->nama_lengkap ?? 'Tanpa Nama',
            'initial' => $this->inisialNama($kasbon->karyawan?->nama_lengkap),
            'tanggal' => $this->formatTanggal($kasbon->tanggal_pengambilan),
            'tanggal_iso' => $kasbon->tanggal_pengambilan?->format('Y-m-d') ?? '',
            'total' => (int) $kasbon->total_pinjaman,
            'angsuran' => (int) $kasbon->nominal_angsuran,
            'tenor' => (int) $kasbon->jumlah_tenor,
            'terbayar' => (int) $angsuranTerbayar->sum('nominal'),
            'cicilan_selesai' => $angsuranTerbayar->count(),
            'sisa' => (int) $kasbon->sisa_pinjaman,
            'keterangan' => $kasbon->keterangan ?? '',
            'status' => $kasbon->status,
            'status_label' => Kasbon::daftarStatus()[$kasbon->status] ?? $kasbon->status,
            'angsuran_berikut' => $angsuranBerikut === null ? null : [
                'urutan' => $angsuranBerikut->urutan,
                'nominal' => (int) $angsuranBerikut->nominal,
                'tanggal' => $this->formatTanggal($angsuranBerikut->tanggal_rencana),
            ],
            'jadwal' => $kasbon->angsuran->map(fn (AngsuranKasbon $a) => [
                'urutan' => $a->urutan,
                'nominal' => (int) $a->nominal,
                'tanggal' => $this->formatTanggal($a->tanggal_rencana),
                'status' => $a->status,
                'status_label' => AngsuranKasbon::daftarStatus()[$a->status] ?? $a->status,
            ])->all(),
        ];
    }

    /**
     * Ringkasan kartu atas halaman kasbon, dihitung langsung di SQL (satu query)
     * sehingga tidak perlu memuat seluruh kasbon beserta angsurannya ke PHP.
     * Hasilnya identik dengan ringkasanKasbon(daftarKasbon()).
     */
    public function ringkasanKasbonDariSql(): array
    {
        $statusAktif = "('approved', 'pending')";
        // Cicilan berikut = angsuran 'terjadwal' dengan urutan terkecil per kasbon.
        $cicilanBerikut = "(select a.nominal from angsuran_kasbon a where a.id_kasbon = kasbon.id and a.status = 'terjadwal' order by a.urutan asc limit 1)";

        $hasil = Kasbon::query()
            ->leftJoin('karyawan', fn ($gabung) => $gabung
                ->on('karyawan.id', '=', 'kasbon.id_karyawan')
                ->whereNull('karyawan.deleted_at'))
            ->selectRaw("
                count(*) as jumlah_kasbon,
                coalesce(sum(kasbon.total_pinjaman), 0) as total_pinjaman,
                coalesce(sum(case when kasbon.status in {$statusAktif} then kasbon.sisa_pinjaman end), 0) as outstanding,
                count(distinct case when kasbon.status in {$statusAktif} then coalesce(karyawan.nomor_induk_karyawan, '—') end) as active_drivers,
                coalesce(sum(case when kasbon.status in {$statusAktif} then coalesce({$cicilanBerikut}, 0) end), 0) as cicilan_bulan_ini
            ")
            ->first();

        return [
            'outstanding' => (int) $hasil->outstanding,
            'active_drivers' => (int) $hasil->active_drivers,
            'total_pinjaman' => (int) $hasil->total_pinjaman,
            'cicilan_bulan_ini' => (int) $hasil->cicilan_bulan_ini,
            'jumlah_kasbon' => (int) $hasil->jumlah_kasbon,
            'batas_maksimal' => Kasbon::BATAS_MAKSIMAL,
            'tenor_maksimal' => Kasbon::TENOR_MAKSIMAL,
        ];
    }

    /**
     * Ringkasan kartu atas halaman kasbon.
     *
     * @param  array<int, array<string, mixed>>  $daftarKasbon
     */
    public function ringkasanKasbon(array $daftarKasbon): array
    {
        $aktif = collect($daftarKasbon)->whereIn('status', ['approved', 'pending']);

        return [
            'outstanding' => (int) $aktif->sum('sisa'),
            'active_drivers' => $aktif->pluck('nip')->unique()->count(),
            'total_pinjaman' => (int) collect($daftarKasbon)->sum('total'),
            'cicilan_bulan_ini' => (int) $aktif->sum(fn ($k) => $k['angsuran_berikut']['nominal'] ?? 0),
            'jumlah_kasbon' => count($daftarKasbon),
            'batas_maksimal' => Kasbon::BATAS_MAKSIMAL,
            'tenor_maksimal' => Kasbon::TENOR_MAKSIMAL,
        ];
    }

    /**
     * Baris tabel Operasional Ritase untuk satu periode.
     * Nomor surat jalan dibentuk dari kode rute + id karena tidak disimpan.
     */
    public function barisRitaseOperasional(?PeriodeGaji $periode): array
    {
        if ($periode === null) {
            return [];
        }

        return $this->kueriRitaseOperasional($periode)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Ritase $ritase) => $this->petakanBarisRitase($ritase, $periode->kode))
            ->all();
    }

    /**
     * Versi terpaginasi untuk halaman admin: hanya baris halaman aktif yang
     * diambil dari database, filter dijalankan di SQL.
     *
     * @param  array<string, mixed>  $filter
     */
    public function barisRitaseOperasionalTerpaginasi(?PeriodeGaji $periode, array $filter, int $perHalaman): LengthAwarePaginator
    {
        if ($periode === null) {
            return Ritase::query()->whereRaw('1 = 0')->paginate($perHalaman);
        }

        $kueri = $this->kueriRitaseOperasional($periode);
        $this->terapkanFilterRitase($kueri, $filter);
        return $kueri
            ->orderByDesc('id')
            ->paginate($perHalaman)
            ->withQueryString()
            ->through(fn (Ritase $ritase) => $this->petakanBarisRitase($ritase, $periode->kode));
    }

    private function kueriRitaseOperasional(PeriodeGaji $periode): Builder
    {
        return Ritase::query()
            ->with([
                'karyawan:id,nama_lengkap,nomor_induk_karyawan',
                'truk:id,nomor_polisi,merek_model,id_jenis_truk',
                'truk.jenisTruk:id,nama',
                'rute:id,kode,material',
            ])
            ->where('id_periode_gaji', $periode->id);
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function terapkanFilterRitase(Builder $kueri, array $filter): void
    {
        $kataKunci = trim((string) ($filter['cari'] ?? ''));
        $supir = (string) ($filter['supir'] ?? 'all');
        $rute = (string) ($filter['rute'] ?? 'all');
        $jenisTruk = (string) ($filter['jenis'] ?? 'all');
        $status = (string) ($filter['status'] ?? 'all');
        $tanggalDari = (string) ($filter['tanggal_dari'] ?? '');
        $tanggalSampai = (string) ($filter['tanggal_sampai'] ?? '');

        if ($supir !== '' && $supir !== 'all') {
            $kueri->whereHas('karyawan', fn (Builder $sub) => $sub->where('nomor_induk_karyawan', $supir));
        }

        if ($rute !== '' && $rute !== 'all') {
            $kueri->whereHas('rute', fn (Builder $sub) => $sub->where('kode', $rute));
        }

        if ($jenisTruk !== '' && $jenisTruk !== 'all') {
            $kueri->whereHas('truk.jenisTruk', fn (Builder $sub) => $sub->where('nama', $jenisTruk));
        }

        if ($status !== '' && $status !== 'all') {
            $kueri->where('status', strtolower($status));
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalDari) === 1) {
            $kueri->whereDate('tanggal_ritase', '>=', $tanggalDari);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSampai) === 1) {
            $kueri->whereDate('tanggal_ritase', '<=', $tanggalSampai);
        }

        // Batas data milik supir yang sedang login (dipakai endpoint AJAX peran supir).
        if (array_key_exists('id_karyawan', $filter) && $filter['id_karyawan'] !== null) {
            $kueri->where('id_karyawan', (int) $filter['id_karyawan']);
        }

        if ($kataKunci !== '') {
            $idDariNomorSuratJalan = $this->ekstrakIdSuratJalan($kataKunci);

            $kueri->where(function (Builder $sub) use ($kataKunci, $idDariNomorSuratJalan): void {
                $sub->whereHas('karyawan', function (Builder $karyawan) use ($kataKunci): void {
                    $karyawan->where('nama_lengkap', 'like', "%{$kataKunci}%")
                        ->orWhere('nomor_induk_karyawan', 'like', "%{$kataKunci}%");
                })
                    ->orWhereHas('truk', function (Builder $truk) use ($kataKunci): void {
                        $truk->where('nomor_polisi', 'like', "%{$kataKunci}%")
                            ->orWhere('merek_model', 'like', "%{$kataKunci}%");
                    })
                    ->orWhereHas('rute', function (Builder $ruteKueri) use ($kataKunci): void {
                        $ruteKueri->where('kode', 'like', "%{$kataKunci}%")
                            ->orWhere('material', 'like', "%{$kataKunci}%");
                    });

                if ($idDariNomorSuratJalan !== null) {
                    $sub->orWhere('id', $idDariNomorSuratJalan);
                }
            });
        }
    }

    /**
     * Nomor surat jalan berbentuk "SJ-{KODE}-{id}"; ambil id-nya bila dicari.
     */
    private function ekstrakIdSuratJalan(string $kataKunci): ?int
    {
        if (preg_match('/^SJ-[A-Z0-9]+-0*(\d+)$/i', trim($kataKunci), $cocok) === 1) {
            return (int) $cocok[1];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function petakanBarisRitase(Ritase $ritase, string $kodePeriode): array
    {
        $daftarFoto = is_array($ritase->foto_surat_jalan) ? $ritase->foto_surat_jalan : [];

        return [
            'id' => $ritase->id,
            'id_karyawan' => $ritase->id_karyawan,
            'id_truk' => $ritase->id_truk,
            'id_rute' => $ritase->id_rute,
            'period_code' => $kodePeriode,
            'date' => $ritase->tanggal_ritase?->format('d/m/Y') ?? '—',
            'date_iso' => $ritase->tanggal_ritase?->format('Y-m-d') ?? '',
            'sj' => $this->nomorSuratJalan($ritase),
            'driver' => $ritase->karyawan?->nama_lengkap ?? 'TANPA SUPIR',
            'nip' => $ritase->karyawan?->nomor_induk_karyawan ?? '—',
            'plate' => $ritase->truk?->nomor_polisi ?? '—',
            'truck' => $ritase->truk?->merek_model ?? '—',
            'nama_jenis' => $ritase->truk?->jenisTruk?->nama ?? '',
            'material' => $ritase->rute?->material ?? '—',
            'route' => $ritase->rute?->kode ?? '—',
            'rit' => (int) $ritase->jumlah_rit,
            'tonnage' => $this->formatTonase($ritase->tonase_kg),
            'rate' => (int) $ritase->tarif_per_ton,
            'total' => (int) $ritase->total_angkutan,
            'trip_money' => (int) $ritase->uang_jalan,
            'foto_surat_jalan' => $daftarFoto[0] ?? $this->avatarSvgDataUri('SJ', '#047857'),
            'daftar_foto_surat_jalan' => $daftarFoto,
            'jumlah_foto' => count($daftarFoto),
            'punya_foto_asli' => count($daftarFoto) > 0,
            'nama_penerima' => $ritase->nama_penerima ?? '',
            'catatan_pengantaran' => $ritase->catatan_pengantaran ?? '',
            'status' => $ritase->status,
            'status_label' => Ritase::daftarStatus()[$ritase->status] ?? match ($ritase->status) {
                'terbit' => 'Terbit (Siap Dikirim)',
                'sedang_dikirim' => 'Sedang Dikirim',
                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                'terverifikasi' => 'Terverifikasi',
                'dibatalkan' => 'Dibatalkan',
                default => 'Draf (Belum Terbit)',
            },
        ];
    }

    /**
     * Ringkasan periode dihitung dari agregat SQL, bukan dari baris halaman.
     *
     * @return array<string, mixed>
     */
    public function ringkasanRitasePeriode(?PeriodeGaji $periode): array
    {
        $ringkasanKosong = [
            'rit' => 0,
            'tonnage' => number_format(0, 2, ',', '.'),
            'haulage' => 0,
            'trip_money' => 0,
            'baris' => 0,
            'terverifikasi' => 0,
            'menunggu_verifikasi' => 0,
            'sedang_dikirim' => 0,
            'terbit' => 0,
            'draft' => 0,
            'dibatalkan' => 0,
            'supplier' => 0,
            'rute_utama' => null,
        ];

        if ($periode === null) {
            return $ringkasanKosong;
        }

        $agregat = Ritase::query()
            ->where('id_periode_gaji', $periode->id)
            ->selectRaw('COUNT(*) as jumlah_baris')
            ->selectRaw('COALESCE(SUM(jumlah_rit), 0) as total_rit')
            ->selectRaw('COALESCE(SUM(tonase_kg), 0) as total_tonase_kg')
            ->selectRaw('COALESCE(SUM(total_angkutan), 0) as total_angkutan')
            ->selectRaw('COALESCE(SUM(uang_jalan), 0) as total_uang_jalan')
            ->selectRaw('COUNT(DISTINCT id_karyawan) as jumlah_supir')
            ->first();

        if ($agregat === null) {
            return $ringkasanKosong;
        }

        $jumlahPerStatus = Ritase::query()
            ->where('id_periode_gaji', $periode->id)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $ruteUtama = Ritase::query()
            ->where('id_periode_gaji', $periode->id)
            ->join('rute', 'rute.id', '=', 'ritase.id_rute')
            ->selectRaw('rute.kode as kode, SUM(ritase.jumlah_rit) as total_rit')
            ->groupBy('rute.kode')
            ->orderByDesc('total_rit')
            ->value('kode');

        return [
            'rit' => (int) $agregat->total_rit,
            'tonnage' => $this->formatTonase((int) $agregat->total_tonase_kg),
            'haulage' => (int) $agregat->total_angkutan,
            'trip_money' => (int) $agregat->total_uang_jalan,
            'baris' => (int) $agregat->jumlah_baris,
            'terverifikasi' => (int) ($jumlahPerStatus['terverifikasi'] ?? 0),
            'menunggu_verifikasi' => (int) ($jumlahPerStatus['menunggu_verifikasi'] ?? 0),
            'sedang_dikirim' => (int) ($jumlahPerStatus['sedang_dikirim'] ?? 0),
            'terbit' => (int) ($jumlahPerStatus['terbit'] ?? 0),
            'draft' => (int) ($jumlahPerStatus['draft'] ?? 0),
            'dibatalkan' => (int) ($jumlahPerStatus['dibatalkan'] ?? 0),
            'supplier' => (int) $agregat->jumlah_supir,
            'rute_utama' => $ruteUtama,
        ];
    }

    /**
     * Daftar nama jenis truk yang muncul pada periode (untuk filter).
     *
     * @return array<int, string>
     */
    public function pilihanJenisTrukRitase(): array
    {
        return JenisTruk::query()
            ->orderBy('nama')
            ->pluck('nama')
            ->all();
    }

    /**
     * Ringkasan kartu atas halaman Operasional Ritase.
     *
     * @param  array<int, array<string, mixed>>  $barisRitase
     */
    public function ringkasanRitase(array $barisRitase): array
    {
        $kumpulan = collect($barisRitase);
        $tonaseKg = $kumpulan->sum(fn ($baris) => (float) str_replace(['.', ','], ['', '.'], $baris['tonnage']));

        return [
            'rit' => (int) $kumpulan->sum('rit'),
            'tonnage' => number_format($tonaseKg, 2, ',', '.'),
            'haulage' => (int) $kumpulan->sum('total'),
            'trip_money' => (int) $kumpulan->sum('trip_money'),
            'baris' => $kumpulan->count(),
            'terverifikasi' => $kumpulan->where('status', 'terverifikasi')->count(),
            'menunggu_verifikasi' => $kumpulan->where('status', 'menunggu_verifikasi')->count(),
            'sedang_dikirim' => $kumpulan->where('status', 'sedang_dikirim')->count(),
            'terbit' => $kumpulan->where('status', 'terbit')->count(),
            'draft' => $kumpulan->where('status', 'draft')->count(),
            'dibatalkan' => $kumpulan->where('status', 'dibatalkan')->count(),
            'supplier' => $kumpulan->pluck('nip')->unique()->count(),
            'rute_utama' => $this->ruteUtama($barisRitase),
        ];
    }

    /**
     * Rute dengan jumlah ritase terbanyak, untuk banner informatif.
     *
     * @param  array<int, array<string, mixed>>  $barisRitase
     */
    public function ruteUtama(array $barisRitase): ?string
    {
        $kumpulan = collect($barisRitase)
            ->groupBy('route')
            ->map(fn ($baris) => $baris->sum('rit'))
            ->sortDesc();

        return $kumpulan->keys()->first();
    }

    /**
     * Pilihan supir + unit untuk form ritase.
     * Supir yang sudah memegang unit diprioritaskan di atas.
     */
    public function pilihanSupirRitase(): array
    {
        return Karyawan::query()
            ->where('status', 'aktif')
            ->with([
                'truk:id,id_karyawan,nomor_polisi,merek_model',
                'penggajian' => fn ($kueri) => $kueri->with('truk:id,nomor_polisi,merek_model')->orderByDesc('id'),
                'ritase' => fn ($kueri) => $kueri->with('truk:id,nomor_polisi,merek_model')->orderByDesc('id'),
            ])
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function (Karyawan $karyawan): array {
                $truk = $karyawan->truk->first()
                    ?? $karyawan->penggajian->first()?->truk
                    ?? $karyawan->ritase->first()?->truk;

                return [
                    'id' => $karyawan->id,
                    'nama' => $karyawan->nama_lengkap,
                    'nip' => $karyawan->nomor_induk_karyawan,
                    'id_truk' => $truk?->id,
                    'plate' => $truk?->nomor_polisi,
                    'truk' => $truk?->merek_model,
                ];
            })
            ->sortBy([
                fn (array $a, array $b) => ($b['id_truk'] !== null) <=> ($a['id_truk'] !== null),
                fn (array $a, array $b) => strcmp($a['nama'], $b['nama']),
            ])
            ->values()
            ->all();
    }

    /**
     * Pilihan rute aktif untuk form ritase.
     */
    public function pilihanRuteRitase(): array
    {
        return Rute::query()
            ->where('status', 'aktif')
            ->orderBy('kode')
            ->get()
            ->map(fn (Rute $rute) => [
                'id' => $rute->id,
                'code' => $rute->kode,
                'name' => $rute->material,
                'rate' => (int) $rute->tarif_per_ton,
                'trip_money' => (int) $rute->uang_jalan_default,
            ])
            ->values()
            ->all();
    }

    /**
     * Menyimpan satu baris ritase baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function buatRitase(array $data): Ritase
    {
        $rute = Rute::query()->findOrFail($data['id_rute']);
        $tonaseKg = (int) round(((float) $data['tonase_ton']) * 1000);
        $tarifPerTon = (int) ($data['tarif_per_ton'] ?? $rute->tarif_per_ton);

        $this->bersihkanSinggahan();

        return Ritase::create([
            'id_periode_gaji' => $data['id_periode_gaji'],
            'id_karyawan' => $data['id_karyawan'],
            'id_truk' => $data['id_truk'],
            'id_rute' => $rute->id,
            'tanggal_ritase' => $data['tanggal_ritase'],
            'jumlah_rit' => (int) ($data['jumlah_rit'] ?? 1),
            'tonase_kg' => $tonaseKg,
            'volume_liter' => isset($data['volume_liter']) ? (int) $data['volume_liter'] : null,
            'tarif_per_ton' => $tarifPerTon,
            'total_angkutan' => intdiv($tonaseKg * $tarifPerTon + 500, 1000),
            'uang_jalan' => (int) ($data['uang_jalan'] ?? $rute->uang_jalan_default),
            'nama_penerima' => $data['nama_penerima'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
    }

    /**
     * Memperbarui satu baris ritase.
     *
     * @param  array<string, mixed>  $data
     */
    public function perbaruiRitase(Ritase $ritase, array $data): Ritase
    {
        $rute = isset($data['id_rute'])
            ? Rute::query()->find($data['id_rute'])
            : $ritase->rute;

        $tonaseKg = isset($data['tonase_ton'])
            ? (int) round(((float) $data['tonase_ton']) * 1000)
            : (int) $ritase->tonase_kg;

        $tarifPerTon = (int) ($data['tarif_per_ton'] ?? $ritase->tarif_per_ton);

        $ritase->update([
            'id_karyawan' => $data['id_karyawan'] ?? $ritase->id_karyawan,
            'id_truk' => $data['id_truk'] ?? $ritase->id_truk,
            'id_rute' => $rute?->id ?? $ritase->id_rute,
            'tanggal_ritase' => $data['tanggal_ritase'] ?? $ritase->tanggal_ritase,
            'jumlah_rit' => (int) ($data['jumlah_rit'] ?? $ritase->jumlah_rit),
            'tonase_kg' => $tonaseKg,
            'tarif_per_ton' => $tarifPerTon,
            'total_angkutan' => intdiv($tonaseKg * $tarifPerTon + 500, 1000),
            'uang_jalan' => (int) ($data['uang_jalan'] ?? $ritase->uang_jalan),
            'nama_penerima' => array_key_exists('nama_penerima', $data) ? $data['nama_penerima'] : $ritase->nama_penerima,
            'status' => $data['status'] ?? $ritase->status,
        ]);
        $this->bersihkanSinggahan();

        return $ritase;
    }

    /**
     * Ringkasan seluruh periode (akumulasi semua data ritase).
     * Dipakai kartu statistik "Keseluruhan" agar jelas bedanya dengan periode aktif.
     */
    public function ringkasanRitaseKeseluruhan(): array
    {
        $semuaRitase = Ritase::query()->get();

        return [
            'rit' => (int) $semuaRitase->sum('jumlah_rit'),
            'tonnage' => $this->formatTonase((int) $semuaRitase->sum('tonase_kg')),
            'haulage' => (int) $semuaRitase->sum('total_angkutan'),
            'trip_money' => (int) $semuaRitase->sum('uang_jalan'),
            'baris' => $semuaRitase->count(),
            'periode' => PeriodeGaji::query()->count(),
            'supplier' => (int) $semuaRitase->pluck('id_karyawan')->unique()->count(),
        ];
    }

    /**
     * Baris tabel biaya operasional untuk satu periode.
     */
    public function barisBiayaOperasional(?PeriodeGaji $periode): array
    {
        if ($periode === null) {
            return [];
        }

        return BiayaOperasional::query()
            ->with(['truk:id,nomor_polisi,merek_model', 'karyawan:id,nama_lengkap,nomor_induk_karyawan'])
            ->where('id_periode_gaji', $periode->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (BiayaOperasional $biaya) => $this->petakanBarisBiaya($biaya))
            ->all();
    }

    /**
     * Versi terpaginasi untuk halaman Pengeluaran Operasional.
     *
     * @param  array<string, mixed>  $filter
     */
    public function barisBiayaOperasionalTerpaginasi(?PeriodeGaji $periode, array $filter, int $perHalaman): LengthAwarePaginator
    {
        if ($periode === null) {
            return BiayaOperasional::query()->whereRaw('1 = 0')->paginate($perHalaman);
        }

        $kueri = BiayaOperasional::query()
            ->with(['truk:id,nomor_polisi,merek_model', 'karyawan:id,nama_lengkap,nomor_induk_karyawan'])
            ->where('id_periode_gaji', $periode->id);

        $kataKunci = trim((string) ($filter['cari'] ?? ''));
        $kategori = (string) ($filter['kategori'] ?? 'all');
        $status = strtolower(trim((string) ($filter['status'] ?? 'all')));
        $unit = trim((string) ($filter['unit'] ?? 'all'));

        if ($kategori !== '' && $kategori !== 'all') {
            $kueri->where('kategori', $kategori);
        }

        if ($status !== '' && $status !== 'all') {
            $kueri->where('status', $status);
        }

        if ($unit !== '' && $unit !== 'all') {
            $kueri->whereHas('truk', fn (Builder $sub) => $sub->where('nomor_polisi', $unit));
        }

        if ($kataKunci !== '') {
            $kueri->where(function (Builder $sub) use ($kataKunci): void {
                $sub->where('nomor_nota', 'like', "%{$kataKunci}%")
                    ->orWhere('keterangan', 'like', "%{$kataKunci}%")
                    ->orWhereHas('truk', function (Builder $truk) use ($kataKunci): void {
                        $truk->where('nomor_polisi', 'like', "%{$kataKunci}%")
                            ->orWhere('merek_model', 'like', "%{$kataKunci}%");
                    });
            });
        }

        return $kueri
            ->orderByDesc('id')
            ->paginate($perHalaman)
            ->withQueryString()
            ->through(fn (BiayaOperasional $biaya) => $this->petakanBarisBiaya($biaya));
    }

    /**
     * @return array<string, mixed>
     */
    private function petakanBarisBiaya(BiayaOperasional $biaya): array
    {
        return [
            'id' => $biaya->id,
            'id_truk' => $biaya->id_truk,
            'id_karyawan' => $biaya->id_karyawan,
            'tanggal' => $biaya->tanggal_biaya?->format('d/m/Y') ?? '—',
            'tanggal_iso' => $biaya->tanggal_biaya?->format('Y-m-d') ?? '',
            'kategori' => $biaya->kategori,
            'kategori_label' => BiayaOperasional::daftarKategori()[$biaya->kategori] ?? $biaya->kategori,
            'keterangan' => $biaya->keterangan ?? '',
            'nominal' => (int) $biaya->nominal,
            'nomor_nota' => $biaya->nomor_nota ?? '',
            'foto_nota' => $biaya->foto_nota ?: $this->avatarSvgDataUri('NT', '#1D4ED8'),
            'status' => $biaya->status,
            'status_label' => BiayaOperasional::daftarStatus()[$biaya->status] ?? $biaya->status,
            'plate' => $biaya->truk?->nomor_polisi ?? '—',
            'truck' => $biaya->truk?->merek_model ?? '—',
            'driver' => $biaya->karyawan?->nama_lengkap ?? '',
            'nip' => $biaya->karyawan?->nomor_induk_karyawan ?? '',
        ];
    }

    /**
     * Ringkasan biaya operasional per periode + rekap per kategori.
     *
     * @param  array<int, array<string, mixed>>  $barisBiaya
     */
    public function ringkasanBiayaOperasional(array $barisBiaya): array
    {
        $kumpulan = collect($barisBiaya);

        $perKategori = $kumpulan
            ->groupBy('kategori')
            ->map(fn ($baris, $kategori) => [
                'kategori' => $kategori,
                'label' => BiayaOperasional::daftarKategori()[$kategori] ?? $kategori,
                'jumlah' => $baris->count(),
                'total' => (int) $baris->sum('nominal'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'total' => (int) $kumpulan->sum('nominal'),
            'baris' => $kumpulan->count(),
            'unit' => $kumpulan->pluck('plate')->unique()->count(),
            'draft' => $kumpulan->where('status', 'draft')->count(),
            'diajukan' => $kumpulan->where('status', 'diajukan')->count(),
            'disetujui' => $kumpulan->where('status', 'disetujui')->count(),
            'per_kategori' => $perKategori,
        ];
    }

    /**
     * Ringkasan periode dari agregat SQL, bukan dari baris halaman.
     *
     * @return array<string, mixed>
     */
    public function ringkasanBiayaOperasionalPeriode(?PeriodeGaji $periode): array
    {
        $ringkasanKosong = [
            'total' => 0,
            'baris' => 0,
            'unit' => 0,
            'draft' => 0,
            'diajukan' => 0,
            'disetujui' => 0,
            'per_kategori' => [],
        ];

        if ($periode === null) {
            return $ringkasanKosong;
        }

        $agregat = BiayaOperasional::query()
            ->where('id_periode_gaji', $periode->id)
            ->selectRaw('COUNT(*) as jumlah_baris')
            ->selectRaw('COALESCE(SUM(nominal), 0) as total_nominal')
            ->selectRaw('COUNT(DISTINCT id_truk) as jumlah_unit')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as jumlah_draft")
            ->selectRaw("SUM(CASE WHEN status = 'diajukan' THEN 1 ELSE 0 END) as jumlah_diajukan")
            ->selectRaw("SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as jumlah_disetujui")
            ->first();

        $perKategori = BiayaOperasional::query()
            ->where('id_periode_gaji', $periode->id)
            ->selectRaw('kategori, COUNT(*) as jumlah, COALESCE(SUM(nominal), 0) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($baris) => [
                'kategori' => $baris->kategori,
                'label' => BiayaOperasional::daftarKategori()[$baris->kategori] ?? $baris->kategori,
                'jumlah' => (int) $baris->jumlah,
                'total' => (int) $baris->total,
            ])
            ->all();

        return [
            'total' => (int) ($agregat->total_nominal ?? 0),
            'baris' => (int) ($agregat->jumlah_baris ?? 0),
            'unit' => (int) ($agregat->jumlah_unit ?? 0),
            'draft' => (int) ($agregat->jumlah_draft ?? 0),
            'diajukan' => (int) ($agregat->jumlah_diajukan ?? 0),
            'disetujui' => (int) ($agregat->jumlah_disetujui ?? 0),
            'per_kategori' => $perKategori,
        ];
    }

    /**
     * Pilihan nomor polisi unit yang muncul pada periode, untuk filter.
     *
     * @return array<int, string>
     */
    public function pilihanUnitBiaya(?PeriodeGaji $periode = null): array
    {
        return BiayaOperasional::query()
            ->join('truk', 'truk.id', '=', 'biaya_operasional.id_truk')
            ->when($periode !== null, fn (Builder $kueri) => $kueri->where('biaya_operasional.id_periode_gaji', $periode->id))
            ->selectRaw('truk.nomor_polisi as nomor_polisi')
            ->distinct()
            ->orderBy('truk.nomor_polisi')
            ->pluck('nomor_polisi')
            ->all();
    }

    /**
     * Pilihan unit armada untuk form biaya operasional.
     */
    public function pilihanTrukBiaya(): array
    {
        return Truk::query()
            ->select(['id', 'nomor_polisi', 'merek_model', 'id_karyawan'])
            ->with('karyawan:id,nama_lengkap')
            ->where('status', 'aktif')
            ->orderBy('nomor_polisi')
            ->get()
            ->map(fn (Truk $truk) => [
                'id' => $truk->id,
                'nomor_polisi' => $truk->nomor_polisi,
                'merek_model' => $truk->merek_model,
                'id_karyawan' => $truk->id_karyawan,
                'driver' => $truk->karyawan?->nama_lengkap ?? '',
            ])
            ->all();
    }

    public function ringkasanMaster(): array
    {
        $daftarKaryawan = Karyawan::query()->get();
        $daftarTruk = Truk::query()->get();

        $supirPunyaUnit = $daftarTruk->pluck('id_karyawan')->filter()->unique();

        return [
            'drivers' => $daftarKaryawan->count(),
            'drivers_ready' => $daftarKaryawan->where('status', 'aktif')->count(),
            'drivers_leave' => $daftarKaryawan->where('status', 'nonaktif')->count(),
            'drivers_aktif' => $daftarKaryawan->where('status', 'aktif')->count(),
            'drivers_nonaktif' => $daftarKaryawan->where('status', 'nonaktif')->count(),
            'drivers_tanpa_unit' => $daftarKaryawan->filter(
                fn (Karyawan $karyawan) => ! $supirPunyaUnit->contains($karyawan->id)
            )->count(),
            'trucks' => $daftarTruk->count(),
            'trucks_ready' => $daftarTruk->where('status', 'aktif')->count(),
            'trucks_tanpa_supir' => $daftarTruk->whereNull('id_karyawan')->count(),
            'routes' => Rute::query()->count(),
        ];
    }

    public function dataKaryawanMaster(): array
    {
        return $this->kueriKaryawanMaster()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Karyawan $karyawan) => $this->petakanBarisKaryawan($karyawan))
            ->all();
    }

    /**
     * Versi terpaginasi untuk halaman Data Supir & Karyawan.
     *
     * @param  array<string, mixed>  $filter
     */
    public function dataKaryawanMasterTerpaginasi(array $filter, int $perHalaman): LengthAwarePaginator
    {
        $kueri = $this->kueriKaryawanMaster();

        $kataKunci = trim((string) ($filter['cari'] ?? ''));
        $status = strtolower(trim((string) ($filter['status'] ?? 'all')));
        $unit = (string) ($filter['unit'] ?? 'all');

        if (in_array($status, ['aktif', 'nonaktif'], true)) {
            $kueri->where('status', $status);
        }

        if ($unit === 'pegang') {
            $kueri->whereHas('truk');
        } elseif ($unit === 'belum') {
            $kueri->whereDoesntHave('truk');
        }

        if ($kataKunci !== '') {
            $kueri->where(function (Builder $sub) use ($kataKunci): void {
                $sub->where('nama_lengkap', 'like', "%{$kataKunci}%")
                    ->orWhere('nomor_induk_karyawan', 'like', "%{$kataKunci}%")
                    ->orWhereHas('pengguna', fn (Builder $pengguna) => $pengguna->where('email', 'like', "%{$kataKunci}%"))
                    ->orWhereHas('truk', fn (Builder $truk) => $truk->where('nomor_polisi', 'like', "%{$kataKunci}%"));
            });
        }

        return $kueri
            ->orderByDesc('id')
            ->paginate($perHalaman)
            ->withQueryString()
            ->through(fn (Karyawan $karyawan) => $this->petakanBarisKaryawan($karyawan));
    }

    private function kueriKaryawanMaster(): Builder
    {
        return Karyawan::query()
            ->withCount(['ritase', 'penggajian', 'kasbon'])
            ->with([
                'pengguna',
                'kasbon',
                'truk',
                'penggajian' => fn ($kueri) => $kueri->with('truk')->orderByDesc('id'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function petakanBarisKaryawan(Karyawan $karyawan): array
    {
        $penggajianTerakhir = $karyawan->penggajian->first();
        // Armada yang sedang dipegang (relasi langsung), fallback ke penggajian terakhir.
        $truk = $karyawan->truk->first() ?? $penggajianTerakhir?->truk;
        $totalPinjaman = (int) $karyawan->kasbon->sum('total_pinjaman');
        $sisaPinjaman = (int) $karyawan->kasbon
            ->where('status', '!=', 'lunas')
            ->sum('sisa_pinjaman');

        return [
            'initial' => $this->inisialNama($karyawan->nama_lengkap),
            'foto_profil' => $karyawan->foto_profil ?: ($karyawan->pengguna?->foto_profil ?: $this->avatarSvgDataUri($this->inisialNama($karyawan->nama_lengkap))),
            'name' => $karyawan->nama_lengkap,
            'nip' => $karyawan->nomor_induk_karyawan,
            'phone' => FormatNomorTelepon::tampil($karyawan->pengguna?->nomor_telepon),
            'plate' => $truk?->nomor_polisi ?? '—',
            'truck' => $truk === null
                ? 'Belum ada penugasan armada'
                : $truk->merek_model,
            'bank' => $karyawan->nama_bank ?? '—',
            'account' => $karyawan->nomor_rekening ?? '—',
            'verified' => $karyawan->nomor_rekening !== null && $karyawan->nomor_rekening !== '',
            'commission' => $this->formatPersentaseKomisi($karyawan->persentase_komisi_bp).' Bersih',
            'bpjs' => (int) $karyawan->potongan_bpjs_bulanan,
            'limit' => $totalPinjaman,
            'remaining' => $sisaPinjaman,
            'status' => $karyawan->status === 'aktif' ? 'Aktif' : 'Nonaktif',
            'status_kode' => $karyawan->status,
            'tone' => $karyawan->status === 'aktif' ? 'emerald' : 'slate',
            'id' => $karyawan->id,
            'email' => $karyawan->pengguna?->email ?? '—',
            'komisi_persen' => number_format($karyawan->persentase_komisi_bp / 100, 2, ',', '.'),
            'nomor_rekening' => $karyawan->nomor_rekening ?? '',
            'nama_bank' => $karyawan->nama_bank ?? '',
            'jumlah_ritase' => (int) $karyawan->ritase_count,
            'jumlah_penggajian' => (int) $karyawan->penggajian_count,
            'jumlah_kasbon' => (int) $karyawan->kasbon_count,
        ];
    }
    /**
     * Rincian pengeluaran operasional per supir, untuk sub tab modal detail supir.
     * Sumber: biaya operasional yang tertaut ke unit armada yang dipegang supir.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pengeluaranPerSupir(int $idKaryawan, int $batas = 25): array
    {
        $daftarTruk = Truk::query()
            ->where('id_karyawan', $idKaryawan)
            ->pluck('id')
            ->all();

        if ($daftarTruk === []) {
            return [];
        }

        return BiayaOperasional::query()
            ->with('truk:id,nomor_polisi,merek_model')
            ->whereIn('id_truk', $daftarTruk)
            ->orderByDesc('tanggal_biaya')
            ->orderByDesc('id')
            ->limit($batas)
            ->get()
            ->map(fn (BiayaOperasional $biaya) => [
                'id' => $biaya->id,
                'tanggal' => $biaya->tanggal_biaya?->format('d/m/Y') ?? '—',
                'kategori' => BiayaOperasional::daftarKategori()[$biaya->kategori] ?? $biaya->kategori,
                'nominal' => (int) $biaya->nominal,
                'nominal_label' => 'Rp '.number_format((int) $biaya->nominal, 0, ',', '.'),
                'nomor_nota' => $biaya->nomor_nota ?? '',
                'plate' => $biaya->truk?->nomor_polisi ?? '—',
                'status' => $biaya->status,
                'status_label' => BiayaOperasional::daftarStatus()[$biaya->status] ?? $biaya->status,
                'keterangan' => $biaya->keterangan ?? '',
            ])
            ->all();
    }

    /**
     * Rincian kasbon per supir, untuk sub tab modal detail supir.
     *
     * @return array<int, array<string, mixed>>
     */
    public function kasbonPerSupir(int $idKaryawan, int $batas = 25): array
    {
        return Kasbon::query()
            ->with('angsuran')
            ->where('id_karyawan', $idKaryawan)
            ->orderByDesc('tanggal_pengambilan')
            ->orderByDesc('id')
            ->limit($batas)
            ->get()
            ->map(fn (Kasbon $kasbon) => [
                'id' => $kasbon->id,
                'tanggal' => $kasbon->tanggal_pengambilan?->format('d/m/Y') ?? '—',
                'total' => (int) $kasbon->total_pinjaman,
                'total_label' => 'Rp '.number_format((int) $kasbon->total_pinjaman, 0, ',', '.'),
                'angsuran' => (int) $kasbon->nominal_angsuran,
                'angsuran_label' => 'Rp '.number_format((int) $kasbon->nominal_angsuran, 0, ',', '.'),
                'terbayar' => (int) $kasbon->angsuran->where('status', 'terpotong')->sum('nominal'),
                'sisa' => (int) $kasbon->sisa_pinjaman,
                'sisa_label' => 'Rp '.number_format((int) $kasbon->sisa_pinjaman, 0, ',', '.'),
                'tenor' => (int) $kasbon->jumlah_tenor,
                'cicilan_selesai' => (int) $kasbon->angsuran->where('status', 'terpotong')->count(),
                'status' => $kasbon->status,
                'status_label' => ucfirst($kasbon->status),
                'keterangan' => $kasbon->keterangan ?? '',
            ])
            ->all();
    }

    /**
     * Ringkasan gabungan untuk sub tab detail supir.
     *
     * @return array<string, int>
     */
    public function ringkasanDetailSupir(int $idKaryawan): array
    {
        $idTruk = Truk::query()->where('id_karyawan', $idKaryawan)->select('id');

        $pengeluaran = BiayaOperasional::query()->whereIn('id_truk', $idTruk);
        $kasbon = Kasbon::query()->where('id_karyawan', $idKaryawan);

        return [
            'total_pengeluaran' => (int) (clone $pengeluaran)->sum('nominal'),
            'jumlah_pengeluaran' => (int) (clone $pengeluaran)->count(),
            'total_kasbon' => (int) (clone $kasbon)->sum('total_pinjaman'),
            'sisa_kasbon' => (int) (clone $kasbon)->where('status', '!=', 'lunas')->sum('sisa_pinjaman'),
            'jumlah_kasbon' => (int) (clone $kasbon)->count(),
        ];
    }

    /**
     * Ringkasan master rute untuk kartu statistik halaman Tarif Rute.
     *
     * @return array<string, mixed>
     */
    public function ringkasanRute(): array
    {
        $agregat = Rute::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif")
            ->selectRaw("SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as nonaktif")
            ->selectRaw('COALESCE(AVG(tarif_per_ton), 0) as rata_tarif')
            ->selectRaw('COALESCE(AVG(jarak_km), 0) as rata_jarak')
            ->selectRaw('COALESCE(MAX(tarif_per_ton), 0) as tarif_tertinggi')
            ->selectRaw('COALESCE(MIN(tarif_per_ton), 0) as tarif_terendah')
            ->selectRaw('COALESCE(SUM(uang_jalan_default), 0) as total_uang_jalan')
            ->first();

        $total = (int) ($agregat->total ?? 0);

        $jumlahMaterial = Rute::query()
            ->whereNotNull('material')
            ->where('material', '!=', '')
            ->distinct()
            ->count('material');

        return [
            'total' => $total,
            'aktif' => (int) ($agregat->aktif ?? 0),
            'nonaktif' => (int) ($agregat->nonaktif ?? 0),
            'material' => $jumlahMaterial,
            'rata_tarif' => (int) round((float) ($agregat->rata_tarif ?? 0)),
            'rata_jarak' => round((float) ($agregat->rata_jarak ?? 0), 1),
            'tarif_tertinggi' => (int) ($agregat->tarif_tertinggi ?? 0),
            'tarif_terendah' => (int) ($agregat->tarif_terendah ?? 0),
            'total_uang_jalan' => (int) ($agregat->total_uang_jalan ?? 0),
        ];
    }

    public function daftarRute(): array
    {
        return $this->kueriRute()            ->orderByDesc('id')
            ->get()
            ->map(fn (Rute $rute) => $this->petakanBarisRute($rute))
            ->all();
    }

    /**
     * Versi terpaginasi untuk halaman Tarif Rute & Jalur.
     *
     * @param  array<string, mixed>  $filter
     */
    public function daftarRuteTerpaginasi(array $filter, int $perHalaman): LengthAwarePaginator
    {
        $kueri = $this->kueriRute();

        $kataKunci = trim((string) ($filter['cari'] ?? ''));
        $status = strtolower(trim((string) ($filter['status'] ?? 'all')));
        $material = trim((string) ($filter['material'] ?? 'all'));

        if (in_array($status, ['aktif', 'nonaktif'], true)) {
            $kueri->where('status', $status);
        }

        if ($material !== '' && $material !== 'all') {
            $kueri->where('material', $material);
        }

        if ($kataKunci !== '') {
            $kueri->where(function (Builder $sub) use ($kataKunci): void {
                $sub->where('kode', 'like', "%{$kataKunci}%")
                    ->orWhere('material', 'like', "%{$kataKunci}%")
                    ->orWhere('alamat_asal', 'like', "%{$kataKunci}%")
                    ->orWhere('alamat_tujuan', 'like', "%{$kataKunci}%");
            });
        }

        return $kueri
            ->orderByDesc('id')
            ->paginate($perHalaman)
            ->withQueryString()
            ->through(fn (Rute $rute) => $this->petakanBarisRute($rute));
    }

    /**
     * Pilihan material untuk dropdown filter (distinct dari master rute).
     *
     * @return array<int, string>
     */
    public function pilihanMaterialRute(): array
    {
        return Rute::query()
            ->whereNotNull('material')
            ->distinct()
            ->orderBy('material')
            ->pluck('material')
            ->all();
    }

    private function kueriRute(): Builder
    {
        return Rute::query()
            ->withCount('ritase')
            ->withSum('ritase as total_tonase_kg', 'tonase_kg');
    }

    /**
     * @return array<string, mixed>
     */
    private function petakanBarisRute(Rute $rute): array
    {
        return [
            'id' => $rute->id,
            'code' => $rute->kode,
            'name' => $rute->material,
            'rate' => (int) $rute->tarif_per_ton,
            'trip_money' => (int) $rute->uang_jalan_default,
            'status' => $rute->status,
            'jarak_km' => $rute->jarak_km,
            'alamat_asal' => $rute->alamat_asal ?? '',
            'alamat_tujuan' => $rute->alamat_tujuan ?? '',
            'ritase_count' => (int) $rute->ritase_count,
            'tonase' => $this->formatTonase((int) $rute->total_tonase_kg),
        ];
    }

    /**
     * Komposisi armada per model. Data sudah tersimpan HURUF BESAR,
     * jadi pengelompokan langsung memakai nilai kolom.
     */
    public function komposisiTruk(): array
    {
        $daftarTruk = Truk::query()
            ->withCount('ritase')
            ->withSum('ritase as total_tonase_kg', 'tonase_kg')
            ->get();

        if ($daftarTruk->isEmpty()) {
            return [];
        }

        $jumlahTotal = $daftarTruk->count();

        return $daftarTruk
            ->groupBy(fn (Truk $truk) => $this->normalisasiNamaModel($truk->merek_model))
            ->map(fn (Collection $kelompok, string $model) => [
                'model' => $model,
                'unit' => $kelompok->count(),
                'pct' => round($kelompok->count() / $jumlahTotal * 100, 1),
                'ritase' => (int) $kelompok->sum('ritase_count'),
                'tonase' => $this->formatTonase((int) $kelompok->sum('total_tonase_kg')),
            ])
            ->sortByDesc('unit')
            ->values()
            ->all();
    }

    /**
     * Merapikan spasi berlebih pada nama model. Kapitalisasi sudah
     * dinormalisasi ke HURUF BESAR oleh trait model.
     */
    public function normalisasiNamaModel(?string $merekModel): string
    {
        $teks = trim((string) $merekModel);

        if ($teks === '') {
            return 'TANPA MODEL';
        }

        return preg_replace('/\s+/', ' ', mb_strtoupper($teks, 'UTF-8'));
    }

    public function ambilKaryawanUntukPengguna(?User $pengguna): ?Karyawan
    {
        if ($pengguna === null) {
            return null;
        }

        $kunciSinggahan = 'karyawan_pengguna_'.$pengguna->id;

        if (! array_key_exists($kunciSinggahan, $this->singgahan)) {
            $this->singgahan[$kunciSinggahan] = Karyawan::query()
                ->with('pengguna')
                ->where('id_pengguna', $pengguna->id)
                ->first();
        }

        return $this->singgahan[$kunciSinggahan];
    }

    public function profilSupir(?User $pengguna): array
    {
        $kunciSinggahan = 'profil_supir_'.($pengguna?->id ?? 'tamu');

        if (isset($this->singgahan[$kunciSinggahan])) {
            return $this->singgahan[$kunciSinggahan];
        }

        $karyawan = $this->ambilKaryawanUntukPengguna($pengguna);

        if ($karyawan === null) {
            return $this->singgahan[$kunciSinggahan] = [
                'name' => $pengguna?->name ?? 'Pengguna',
                'nip' => '—',
                'initial' => $this->inisialNama($pengguna?->name),
                'phone' => $pengguna?->nomor_telepon ?? '—',
                'email' => $pengguna?->email ?? '—',
                'plate' => '—',
                'truck' => 'Belum ada armada',
                'truck_note' => '—',
                'route' => '—',
                'pool' => '—',
                'bank' => '—',
                'account' => '—',
                'npwp' => '—',
                'bpjs' => '—',
                'scheme' => '—',
                'rit_total' => 0,
                'rata_tonase' => '0,00',
                'rata_rit_harian' => '0,0',
                'tenure' => '—',
                'hse' => '—',
            ];
        }

        $kunciHasil = 'profil_supir_hasil_'.$karyawan->id;

        if (isset($this->singgahan[$kunciHasil])) {
            return $this->singgahan[$kunciSinggahan] = $this->singgahan[$kunciHasil];
        }

        $penggajianTerakhir = Penggajian::query()
            ->with('truk')
            ->where('id_karyawan', $karyawan->id)
            ->orderByDesc('id')
            ->first();

        $ritaseTerakhir = Ritase::query()
            ->with(['truk', 'rute'])
            ->where('id_karyawan', $karyawan->id)
            ->orderByDesc('tanggal_ritase')
            ->orderByDesc('id')
            ->first();

        $truk = $penggajianTerakhir?->truk ?? $ritaseTerakhir?->truk;

        $totalRit = (int) Ritase::query()->where('id_karyawan', $karyawan->id)->sum('jumlah_rit');
        $totalTonaseKg = (int) Ritase::query()->where('id_karyawan', $karyawan->id)->sum('tonase_kg');
        $jumlahHariRitase = (int) Ritase::query()
            ->where('id_karyawan', $karyawan->id)
            ->distinct()
            ->count('tanggal_ritase');

        return $this->singgahan[$kunciHasil] = [
            'name' => $karyawan->nama_lengkap,
            'nip' => $karyawan->nomor_induk_karyawan,
            'initial' => $this->inisialNama($karyawan->nama_lengkap),
            'phone' => FormatNomorTelepon::tampil($karyawan->pengguna?->nomor_telepon),
            'email' => $karyawan->pengguna?->email ?? '—',
            'plate' => $truk?->nomor_polisi ?? '—',
            'truck' => $truk?->merek_model ?? 'Belum ada armada',
            'truck_note' => $truk === null
                ? '—'
                : 'Unit Armada Perusahaan',
            'route' => $ritaseTerakhir?->rute?->kode ?? '—',
            'pool' => '—',
            'bank' => $karyawan->nama_bank ?? '—',
            'account' => $karyawan->nomor_rekening ?? '—',
            'npwp' => '—',
            'bpjs' => $karyawan->potongan_bpjs_bulanan > 0 ? 'BPJS Ketenagakerjaan Aktif' : 'Belum Terdaftar',
            'scheme' => 'Komisi '.$this->formatPersentaseKomisi($karyawan->persentase_komisi_bp).' Bersih',
            'rit_total' => $totalRit,
            'rata_tonase' => $totalRit > 0 ? number_format($totalTonaseKg / 1000 / $totalRit, 2, ',', '.') : '0,00',
            'rata_rit_harian' => $jumlahHariRitase > 0
                ? number_format($totalRit / $jumlahHariRitase, 1, ',', '.')
                : '0,0',
            'tenure' => '—',
            'hse' => '—',
        ];
    }

    public function ambilPenggajianSupir(Karyawan $karyawan, ?PeriodeGaji $periode = null): ?Penggajian
    {
        $kunciSinggahan = 'penggajian_supir_'.$karyawan->id.'_'.($periode?->id ?? 'semua');

        if (array_key_exists($kunciSinggahan, $this->singgahan)) {
            return $this->singgahan[$kunciSinggahan];
        }

        $kueri = Penggajian::query()
            ->with(['periodeGaji', 'truk'])
            ->where('id_karyawan', $karyawan->id);

        if ($periode !== null) {
            $kueri->where('id_periode_gaji', $periode->id);
        }

        return $this->singgahan[$kunciSinggahan] = $kueri->get()
            ->sortByDesc(fn (Penggajian $penggajian) => $penggajian->periodeGaji?->tanggal_selesai)
            ->first();
    }

    public function payrollSupir(?Penggajian $penggajian, ?Karyawan $karyawan): array
    {
        if ($penggajian === null || $karyawan === null) {
            return [
                'gross_omzet' => 0,
                'retribution' => 0,
                'net_revenue' => 0,
                'trip_money' => 0,
                'sparepart' => 0,
                'unit_net' => 0,
                'commission_pct' => 0,
                'commission_label' => '—',
                'commission' => 0,
                'bpjs' => 0,
                'kasbon' => 0,
                'net_pay' => 0,
                'rit' => 0,
                'tonnage' => '0,00',
                'kasbon_remaining' => 0,
                'kasbon_label' => 'Tanpa Kasbon',
                'route_list' => '—',
                'period_code' => '—',
                'pay_date' => '—',
                'payment_label' => 'Belum Ada Data',
                'is_approved' => false,
                'approved_at' => '—',
                'hash' => '—',
                'audit_id' => '—',
                'approver' => 'Belum Disahkan',
                'approver_role' => 'Belum Ada Data',
            ];
        }

        $kunciSinggahan = 'payroll_supir_'.$penggajian->id;

        if (isset($this->singgahan[$kunciSinggahan])) {
            return $this->singgahan[$kunciSinggahan];
        }

        $periode = $penggajian->periodeGaji;
        $sisaKasbon = (int) Kasbon::query()
            ->where('id_karyawan', $karyawan->id)
            ->where('status', '!=', 'lunas')
            ->sum('sisa_pinjaman');

        $daftarRute = Ritase::query()
            ->with('rute')
            ->where('id_karyawan', $karyawan->id)
            ->where('id_periode_gaji', $penggajian->id_periode_gaji)
            ->get()
            ->pluck('rute.kode')
            ->filter()
            ->unique()
            ->values();

        $statusPeriode = $periode?->status ?? 'draft';
        $sudahDisetujui = in_array($statusPeriode, ['approved', 'paid'], true);

        return $this->singgahan[$kunciSinggahan] = [
            'gross_omzet' => (int) $penggajian->total_angkutan_kotor,
            'retribution' => (int) $penggajian->total_angkutan_kotor - (int) $penggajian->angkutan_bersih,
            'net_revenue' => (int) $penggajian->angkutan_bersih,
            'trip_money' => (int) $penggajian->total_uang_jalan,
            'sparepart' => (int) $penggajian->biaya_sparepart,
            'unit_net' => (int) $penggajian->hasil_bersih_armada,
            'commission_pct' => $penggajian->persentase_komisi_bp / 100,
            'commission_label' => $this->formatPersentaseKomisi($penggajian->persentase_komisi_bp),
            'commission' => (int) $penggajian->gaji_komisi,
            'bpjs' => (int) $penggajian->potongan_bpjs,
            'kasbon' => (int) $penggajian->potongan_kasbon,
            'net_pay' => (int) $penggajian->gaji_bersih,
            'rit' => (int) $penggajian->total_rit,
            'tonnage' => $this->formatTonase($penggajian->total_tonase_kg),
            'kasbon_remaining' => $sisaKasbon,
            'kasbon_label' => $penggajian->potongan_kasbon > 0 ? 'Potongan Kasbon' : 'Tanpa Kasbon',
            'route_list' => $daftarRute->isEmpty() ? '—' : $daftarRute->implode(' & '),
            'period_code' => $periode?->kode ?? '—',
            'pay_date' => $this->formatTanggal($periode?->tanggal_bayar),
            'payment_label' => $this->labelPembayaran($statusPeriode),
            'is_approved' => $sudahDisetujui,
            'approved_at' => $sudahDisetujui ? $this->formatTanggal($periode?->updated_at) : '—',
            'hash' => $sudahDisetujui ? strtoupper(substr(hash('sha256', $karyawan->nomor_induk_karyawan.$periode?->kode), 0, 12)) : '—',
            'audit_id' => 'SLIP-'.$periode?->kode.'-'.$karyawan->nomor_induk_karyawan,
            'approver' => $sudahDisetujui ? 'Pimpinan Operasional' : 'Belum Disahkan',
            'approver_role' => $sudahDisetujui ? 'Otorisasi Pimpinan' : 'Menunggu ACC Pimpinan',
        ];
    }

    public function ritaseSupir(?Karyawan $karyawan, ?PeriodeGaji $periode): array
    {
        if ($karyawan === null || $periode === null) {
            return [];
        }

        $kunciSinggahan = 'ritase_supir_'.$karyawan->id.'_'.$periode->id;

        if (isset($this->singgahan[$kunciSinggahan])) {
            return $this->singgahan[$kunciSinggahan];
        }

        $periodeTerkunci = in_array($periode->status, ['approved', 'paid', 'disetujui', 'dibayar'], true);

        return $this->singgahan[$kunciSinggahan] = Ritase::query()
            ->with('rute')
            ->where('id_karyawan', $karyawan->id)
            ->where('id_periode_gaji', $periode->id)
            ->where('status', '!=', 'draft')
            ->orderByDesc('tanggal_ritase')
            ->orderByDesc('id')
            ->get()
            ->map(function (Ritase $ritase) use ($periodeTerkunci): array {
                $statusKode = $ritase->status ?: 'draft';
                $labelStatus = match ($statusKode) {
                    'terbit' => 'Terbit (Siap Dikirim)',
                    'terverifikasi' => 'Terverifikasi',
                    'menunggu_verifikasi' => 'Menunggu Verifikasi Admin',
                    'sedang_dikirim' => 'Sedang Dikirim',
                    'dibatalkan' => 'Dibatalkan',
                    default => 'Perlu Konfirmasi',
                };
                $nadaStatus = match ($statusKode) {
                    'terbit' => 'amber',
                    'terverifikasi' => 'emerald',
                    'menunggu_verifikasi' => 'sky',
                    'sedang_dikirim' => 'indigo',
                    'dibatalkan' => 'red',
                    default => 'amber',
                };
                $daftarFoto = is_array($ritase->foto_surat_jalan) ? $ritase->foto_surat_jalan : [];

                return [
                    'id' => $ritase->id,
                    'date' => $this->formatTanggal($ritase->tanggal_ritase),
                    'sj' => $this->nomorSuratJalan($ritase),
                    'route' => $ritase->rute?->kode ?? '—',
                    'material' => $ritase->rute?->material ?? 'BATU BARA',
                    'tonnage' => $this->formatTonase($ritase->tonase_kg),
                    'tonnage_input' => number_format($ritase->tonase_kg / 1000, 2, ',', ''),
                    'rate' => (int) $ritase->tarif_per_ton,
                    'total' => (int) $ritase->total_angkutan,
                    'trip_money' => (int) $ritase->uang_jalan,
                    'foto_surat_jalan' => $daftarFoto[0] ?? $this->avatarSvgDataUri('SJ', '#047857'),
                    'daftar_foto_surat_jalan' => $daftarFoto,
                    'jumlah_foto' => count($daftarFoto),
                    'punya_foto_asli' => count($daftarFoto) > 0,
                    'nama_penerima' => $ritase->nama_penerima ?? '',
                    'catatan_pengantaran' => $ritase->catatan_pengantaran ?? '',
                    'status_kode' => $statusKode,
                    'status' => $labelStatus,
                    'tone' => $nadaStatus,
                    'terkunci' => $periodeTerkunci,
                ];
            })
            ->all();
    }

    /**
     * Ringkasan kasbon supir untuk halaman Buku Kasbon.
     * Tabel kasbon hanya menyimpan total pinjaman, angsuran, dan sisa.
     * Tidak ada kolom batas pinjaman, jadi nilai batas selalu 0.
     */
    public function kasbonSupir(?Karyawan $karyawan, ?PeriodeGaji $periode = null): array
    {
        $kasbon = $karyawan === null
            ? null
            : Kasbon::query()
                ->where('id_karyawan', $karyawan->id)
                ->orderByDesc('id')
                ->first();

        $biayaBpjs = (int) ($karyawan?->potongan_bpjs_bulanan ?? 0);

        if ($kasbon === null) {
            return [
                'ada_kasbon' => false,
                'id_kasbon' => null,
                'principal' => 0,
                'remaining' => 0,
                'terbayar' => 0,
                'progress_pct' => 0,
                'installment' => 0,
                'tenor_total' => 0,
                'tenor_paid' => 0,
                'tenor_left' => 0,
                'purpose' => 'Belum ada kasbon aktif',
                'next_cut_off' => $this->formatTanggal($periode?->tanggal_bayar),
                'status' => 'Tanpa Kasbon',
                'bpjs' => $biayaBpjs,
            ];
        }

        $totalPinjaman = (int) $kasbon->total_pinjaman;
        $sisaPinjaman = (int) $kasbon->sisa_pinjaman;
        $angsuran = (int) $kasbon->nominal_angsuran;
        $terbayar = max(0, $totalPinjaman - $sisaPinjaman);

        $totalTenor = $angsuran > 0 ? (int) ceil($totalPinjaman / $angsuran) : 0;
        $tenorTerbayar = $angsuran > 0 ? (int) round($terbayar / $angsuran) : 0;
        $tenorTerbayar = min($tenorTerbayar, $totalTenor);

        return [
            'ada_kasbon' => true,
            'id_kasbon' => $kasbon->id,
            'principal' => $totalPinjaman,
            'remaining' => $sisaPinjaman,
            'terbayar' => $terbayar,
            'progress_pct' => $totalPinjaman > 0 ? (int) round($terbayar / $totalPinjaman * 100) : 0,
            'installment' => $angsuran,
            'tenor_total' => $totalTenor,
            'tenor_paid' => $tenorTerbayar,
            'tenor_left' => max(0, $totalTenor - $tenorTerbayar),
            'purpose' => $kasbon->keterangan ?: 'Keperluan operasional / keluarga',
            'next_cut_off' => $this->formatTanggal($periode?->tanggal_bayar),
            'status' => $kasbon->status === 'lunas' ? 'Lunas' : 'Kasbon Aktif',
            'bpjs' => $biayaBpjs,
        ];
    }

    /**
     * Mutasi kasbon: satu baris pencairan + potongan payroll yang tercatat.
     * Selisih antara pinjaman terbayar dan potongan tercatat ditampilkan
     * sebagai satu baris "angsuran sebelumnya" agar saldo tetap rekonsiliasi
     * dengan kolom sisa_pinjaman.
     */
    public function mutasiKasbon(?Karyawan $karyawan, array $ringkasanKasbon): array
    {
        if ($karyawan === null || ! ($ringkasanKasbon['ada_kasbon'] ?? false)) {
            return [];
        }

        $kasbon = Kasbon::query()->find($ringkasanKasbon['id_kasbon']);

        if ($kasbon === null) {
            return [];
        }

        $daftarPotongan = Penggajian::query()
            ->with('periodeGaji')
            ->where('id_karyawan', $karyawan->id)
            ->where('potongan_kasbon', '>', 0)
            ->get()
            ->sortBy(fn (Penggajian $penggajian) => $penggajian->periodeGaji?->tanggal_selesai)
            ->values();

        $totalPotonganTercatat = (int) $daftarPotongan->sum('potongan_kasbon');
        $selisihAngsuran = max(0, (int) $ringkasanKasbon['terbayar'] - $totalPotonganTercatat);

        $saldo = (int) $ringkasanKasbon['principal'];
        $baris = [
            [
                'date' => $this->formatTanggal($kasbon->created_at),
                'type_date' => 'Pencairan Kasbon',
                'code' => 'KSB-'.str_pad((string) $kasbon->id, 4, '0', STR_PAD_LEFT),
                'kind' => 'Pencairan Kasbon',
                'purpose' => $ringkasanKasbon['purpose'],
                'debit' => 0,
                'credit' => (int) $ringkasanKasbon['principal'],
                'balance' => $saldo,
                'status' => 'DICAIRKAN',
                'tone' => 'blue',
                'filter' => 'pencairan',
            ],
        ];

        if ($selisihAngsuran > 0) {
            $saldo -= $selisihAngsuran;

            $baris[] = [
                'date' => 'Sebelum periode tercatat',
                'type_date' => 'Cut-Off Payroll',
                'code' => 'POT-SEBELUMNYA',
                'kind' => 'Angsuran Periode Sebelumnya',
                'purpose' => 'Potongan sudah mengurangi sisa pinjaman, rincian slip belum tersimpan',
                'debit' => $selisihAngsuran,
                'credit' => 0,
                'balance' => $saldo,
                'status' => 'SUDAH TERPOTONG',
                'tone' => 'emerald',
                'filter' => 'potongan',
            ];
        }

        foreach ($daftarPotongan as $penggajian) {
            $periode = $penggajian->periodeGaji;
            $nominalPotongan = (int) $penggajian->potongan_kasbon;
            $saldo -= $nominalPotongan;
            $sudahDibayar = in_array($periode?->status, ['approved', 'paid'], true);

            $baris[] = [
                'date' => $this->formatTanggal($periode?->tanggal_bayar ?? $periode?->tanggal_selesai),
                'type_date' => 'Cut-Off Payroll',
                'code' => 'POT-'.$periode?->kode.'-'.str_pad((string) $kasbon->id, 3, '0', STR_PAD_LEFT),
                'kind' => 'Potongan Payroll Otomatis',
                'purpose' => 'Angsuran kasbon slip '.$periode?->kode,
                'debit' => $nominalPotongan,
                'credit' => 0,
                'balance' => max(0, $saldo),
                'status' => $sudahDibayar ? 'TERPOTONG SLIP GAJI' : 'MENUNGGU PAYROLL',
                'tone' => $sudahDibayar ? 'emerald' : 'amber',
                'filter' => 'potongan',
            ];
        }

        return array_reverse($baris);
    }

    /**
     * Simulasi jadwal pelunasan berdasarkan tenor angsuran.
     */
    public function jadwalKasbon(array $ringkasanKasbon, ?PeriodeGaji $periode = null): array
    {
        if (! ($ringkasanKasbon['ada_kasbon'] ?? false)) {
            return [];
        }

        $angsuran = (int) $ringkasanKasbon['installment'];
        $sisa = (int) $ringkasanKasbon['remaining'];
        $tenorTerbayar = (int) $ringkasanKasbon['tenor_paid'];
        $tenorSisa = (int) $ringkasanKasbon['tenor_left'];
        $bulanAcuan = $periode?->tanggal_bayar ?? $periode?->tanggal_selesai ?? Carbon::now();

        $baris = [];

        if ($tenorTerbayar > 0) {
            $baris[] = [
                'period' => 'S/D '.strtoupper((string) $periode?->kode),
                'state' => 'LUNAS',
                'amount' => $tenorTerbayar * $angsuran,
                'remain' => 'Sisa: Rp '.number_format($sisa, 0, ',', '.'),
                'tone' => 'emerald',
            ];
        }

        for ($urutan = 1; $urutan <= $tenorSisa; $urutan++) {
            $tanggalAngsuran = Carbon::parse($bulanAcuan)->addMonthsNoOverflow($urutan);
            $sisaSetelah = max(0, $sisa - ($urutan * $angsuran));
            $angsuranTerakhir = $urutan === $tenorSisa;

            $baris[] = [
                'period' => strtoupper(self::NAMA_BULAN[(int) $tanggalAngsuran->month].' '.$tanggalAngsuran->year),
                'state' => $angsuranTerakhir ? 'PELUNASAN' : 'ANGSURAN '.($tenorTerbayar + $urutan),
                'amount' => $angsuran,
                'remain' => $angsuranTerakhir
                    ? 'Saldo Akhir: Rp '.number_format($sisaSetelah, 0, ',', '.')
                    : 'Sisa: Rp '.number_format($sisaSetelah, 0, ',', '.'),
                'tone' => $angsuranTerakhir ? 'primary' : ($urutan === 1 ? 'amber' : 'slate'),
            ];
        }

        return $baris;
    }

    /**
     * Kartu info armada pada halaman profil supir. Hanya data yang benar-benar
     * ada di tabel truk/ritase yang diisi; sisanya ditandai belum tersedia.
     */
    public function kepatuhanArmada(?Karyawan $karyawan): array
    {
        if ($karyawan === null) {
            return [];
        }

        $pernahRitase = Ritase::query()
            ->with('truk')
            ->where('id_karyawan', $karyawan->id)
            ->orderByDesc('tanggal_ritase')
            ->orderByDesc('id')
            ->first();

        $penggajianTerakhir = Penggajian::query()
            ->with('truk')
            ->where('id_karyawan', $karyawan->id)
            ->orderByDesc('id')
            ->first();

        $truk = $penggajianTerakhir?->truk ?? $pernahRitase?->truk;

        $totalRit = (int) Ritase::query()->where('id_karyawan', $karyawan->id)->sum('jumlah_rit');
        $totalTonaseKg = (int) Ritase::query()->where('id_karyawan', $karyawan->id)->sum('tonase_kg');
        $rataTonase = $totalRit > 0 ? round($totalTonaseKg / 1000 / $totalRit, 2) : 0;

        return [
            [
                'label' => 'Unit Armada Terdaftar',
                'note' => $truk === null ? 'Belum ada penugasan armada' : 'Nopol '.$truk->nomor_polisi,
                'badge' => $truk === null ? 'Belum Ada' : 'Aktif',
                'tone' => $truk === null ? 'slate' : 'emerald',
                'footer_label' => 'Model Unit:',
                'footer' => $truk === null
                    ? 'Belum ada data armada'
                    : $truk->merek_model,
                'icon' => 'local_shipping',
            ],
            [
                'label' => 'Rata-rata Muatan per Rit',
                'note' => 'Dari '.number_format($totalRit, 0, ',', '.').' rit yang tercatat',
                'badge' => number_format($rataTonase, 2, ',', '.').' Ton',
                'tone' => 'slate',
                'footer_label' => 'Total Tonase:',
                'footer' => $this->formatTonase($totalTonaseKg).' Ton',
                'icon' => 'scale',
            ],
        ];
    }

    /**
     * Kartu legalitas & skema pengemudi. Kolom SIM/SIMPER belum ada di ERD,
     * sehingga hanya BPJS dan skema komisi yang diambil dari database.
     */
    public function kepatuhanTambang(?Karyawan $karyawan): array
    {
        if ($karyawan === null) {
            return [];
        }

        $biayaBpjs = (int) $karyawan->potongan_bpjs_bulanan;

        return [
            [
                'label' => 'BPJS Ketenagakerjaan',
                'title' => $biayaBpjs > 0
                    ? 'Rp '.number_format($biayaBpjs, 0, ',', '.')
                    : 'Belum Terdaftar',
                'note' => 'Potongan bulanan pada slip gaji supir',
                'footer_label' => 'Status Kepesertaan:',
                'footer' => $biayaBpjs > 0 ? 'Aktif' : 'Belum Terdaftar',
                'tone' => $biayaBpjs > 0 ? 'emerald' : 'slate',
                'icon' => 'health_and_safety',
            ],
            [
                'label' => 'Skema Komisi',
                'title' => $this->formatPersentaseKomisi($karyawan->persentase_komisi_bp).' Bersih',
                'note' => 'Dihitung dari hasil bersih armada per periode',
                'footer_label' => 'Status Skema:',
                'footer' => 'Aktif',
                'tone' => 'emerald',
                'icon' => 'percent',
            ],
            [
                'label' => 'Kelengkapan Administrasi',
                'title' => 'Terdata',
                'note' => 'Data rekening & administrasi penggajian supir',
                'footer_label' => 'Status Verifikasi:',
                'footer' => 'Aktif',
                'tone' => 'slate',
                'icon' => 'card_membership',
            ],
        ];
    }

    /**
     * Data Layar 1 (/admin/reports): Arsip Periode Penggajian beserta KPI dan opsi filter.
     */
    public function arsipLaporanPeriode(): array
    {
        $daftarPeriodeModel = PeriodeGaji::query()
            ->withCount('penggajian')
            ->withSum('penggajian as total_rit_periode', 'total_rit')
            ->withSum('penggajian as total_tonase_kg_periode', 'total_tonase_kg')
            ->withSum('penggajian as total_angkutan_kotor_periode', 'total_angkutan_kotor')
            ->withSum('penggajian as total_uang_jalan_periode', 'total_uang_jalan')
            ->withSum('penggajian as total_gaji_bersih_periode', 'gaji_bersih')
            ->withSum('penggajian as total_potongan_kasbon_periode', 'potongan_kasbon')
            ->withSum('penggajian as total_potongan_bpjs_periode', 'potongan_bpjs')
            ->withSum('penggajian as total_biaya_sparepart_periode', 'biaya_sparepart')
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->get();

        $barisPeriode = $daftarPeriodeModel->map(function (PeriodeGaji $periode): array {
            [$bulanDuaDigit, $tahunEmpatDigit] = $this->ekstrakBulanTahunPeriode($periode);

            $statusKategori = $this->petakanKategoriStatusArsip($periode->status);
            $totalTonaseKg = (int) ($periode->total_tonase_kg_periode ?? 0);
            $totalRit = (int) ($periode->total_rit_periode ?? 0);
            $jumlahSupir = (int) ($periode->penggajian_count ?? 0);
            $totalGajiBersih = (int) ($periode->total_gaji_bersih_periode ?? 0);
            $totalKasbon = (int) ($periode->total_potongan_kasbon_periode ?? 0);
            $totalBpjs = (int) ($periode->total_potongan_bpjs_periode ?? 0);
            $totalAngkutan = (int) ($periode->total_angkutan_kotor_periode ?? 0);
            $totalUangJalan = (int) ($periode->total_uang_jalan_periode ?? 0);
            $totalSparepart = (int) ($periode->total_biaya_sparepart_periode ?? 0);

            $tanggalCutOff = $periode->tanggal_selesai !== null
                ? $this->formatTanggal($periode->tanggal_selesai).' 23:59 WIB'
                : '—';

            $sudahDiAcc = in_array($statusKategori, ['disetujui', 'dibayar'], true);
            $tanggalAccLabel = $sudahDiAcc
                ? $this->formatTanggal($periode->tanggal_bayar ?? $periode->updated_at)
                : '— Belum di-ACC —';

            return [
                'id' => $periode->id,
                'kode' => $periode->kode,
                'rentang_tanggal' => $this->formatRentangTanggal($periode->tanggal_mulai, $periode->tanggal_selesai),
                'tanggal_cut_off' => $tanggalCutOff,
                'tanggal_bayar' => $this->formatTanggal($periode->tanggal_bayar),
                'bulan' => $bulanDuaDigit,
                'nama_bulan' => self::NAMA_BULAN[(int) $bulanDuaDigit] ?? 'Jan',
                'tahun' => $tahunEmpatDigit,
                'jumlah_supir' => $jumlahSupir,
                'total_rit' => $totalRit,
                'total_tonase_kg' => $totalTonaseKg,
                'total_tonase_ton' => round($totalTonaseKg / 1000, 2),
                'total_tonase_label' => $this->formatTonase($totalTonaseKg),
                'total_angkutan_kotor' => $totalAngkutan,
                'total_uang_jalan' => $totalUangJalan,
                'total_sparepart' => $totalSparepart,
                'total_kasbon' => $totalKasbon,
                'total_bpjs' => $totalBpjs,
                'total_net_pay' => $totalGajiBersih,
                'status_asli' => $periode->status,
                'status_filter' => $statusKategori,
                'status_label' => $this->labelStatusPeriode($periode->status),
                'sudah_acc' => $sudahDiAcc,
                'tanggal_acc' => $tanggalAccLabel,
                'pejabat_acc' => $sudahDiAcc ? 'Pimpinan Eksekutif' : null,
                'url_detail' => route('admin.reports.show', $periode->kode),
                'url_cetak' => route('admin.reports.periode.print', $periode->kode),
                'url_ekspor' => route('admin.reports.periode.export', $periode->kode),
            ];
        })->values()->all();

        $totalPeriode = count($barisPeriode);
        $totalSlipDiterbitkan = (int) collect($barisPeriode)->sum('jumlah_supir');
        $totalNetPayKeseluruhan = (int) collect($barisPeriode)->sum('total_net_pay');
        $totalKasbonKeseluruhan = (int) collect($barisPeriode)->sum('total_kasbon');
        $rataRataPerSupir = $totalSlipDiterbitkan > 0
            ? (int) round($totalNetPayKeseluruhan / $totalSlipDiterbitkan)
            : 0;

        $daftarMenungguAcc = collect($barisPeriode)
            ->filter(fn (array $item) => in_array($item['status_filter'], ['menunggu', 'draft'], true))
            ->values();

        $periodeMenungguPertama = $daftarMenungguAcc->first();

        $daftarTahunTersedia = collect($barisPeriode)
            ->pluck('tahun')
            ->merge(['2026', '2025'])
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $tahunMin = collect($barisPeriode)->min('tahun') ?? date('Y');
        $tahunMax = collect($barisPeriode)->max('tahun') ?? date('Y');

        return [
            'ringkasan' => [
                'total_periode' => $totalPeriode,
                'rentang_tahun_arsip' => $tahunMin === $tahunMax ? "Arsip {$tahunMax} Tercatat" : "Arsip {$tahunMin} - {$tahunMax} Tercatat",
                'total_slip' => $totalSlipDiterbitkan,
                'total_net_pay' => $totalNetPayKeseluruhan,
                'total_kasbon' => $totalKasbonKeseluruhan,
                'rata_net_pay_supir' => $rataRataPerSupir,
                'jumlah_menunggu_acc' => $daftarMenungguAcc->count(),
                'label_periode_menunggu' => $periodeMenungguPertama !== null
                    ? $periodeMenungguPertama['kode'].' (Rp '.number_format($periodeMenungguPertama['total_net_pay'], 0, ',', '.').')'
                    : 'Seluruh periode telah disetujui',
            ],
            'opsi_tahun' => $daftarTahunTersedia,
            'baris_periode' => $barisPeriode,
        ];
    }

    /**
     * Data Layar 2 & Layar 3 (/admin/reports/{periode}):
     * Daftar penggajian supir pada periode terpilih + catatan ritase lengkap per supir untuk modal large.
     */
    public function rincianLaporanPeriode(?PeriodeGaji $periode): array
    {
        $infoPeriode = $this->formatPeriode($periode);

        if ($periode === null) {
            return [
                'periode' => $infoPeriode,
                'ringkasan' => [
                    'total_rit' => 0,
                    'jumlah_supir' => 0,
                    'total_tonase_kg' => 0,
                    'total_tonase_label' => '0,00',
                    'rata_ton_per_rit' => '0,00',
                    'total_angkutan_kotor' => 0,
                    'total_uang_jalan' => 0,
                    'total_sparepart' => 0,
                    'total_komisi' => 0,
                    'total_potongan' => 0,
                    'total_bpjs' => 0,
                    'total_kasbon' => 0,
                    'total_gaji_bersih' => 0,
                    'jumlah_anomali' => 0,
                    'jumlah_tanpa_kasbon' => 0,
                ],
                'daftar_supir' => [],
            ];
        }

        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        $ritasePerKaryawan = Ritase::query()
            ->with(['rute', 'truk'])
            ->where('id_periode_gaji', $periode->id)
            ->where('status', '!=', 'draft')
            ->orderBy('tanggal_ritase')
            ->orderBy('id')
            ->get()
            ->groupBy('id_karyawan');

        $daftarSupir = $daftarPenggajian
            ->map(function (Penggajian $penggajian) use ($periode, $ritasePerKaryawan): array {
                $karyawan = $penggajian->karyawan;
                $truk = $penggajian->truk;
                $catatanPeringatan = $this->tentukanPeringatan($penggajian);

                $potonganBpjs = (int) $penggajian->potongan_bpjs;
                $potonganKasbon = (int) $penggajian->potongan_kasbon;
                $biayaSparepart = (int) $penggajian->biaya_sparepart;
                $potonganOperasional = (int) $penggajian->potongan_operasional;
                $totalPotonganSupir = $potonganBpjs + $potonganKasbon;

                $bagianPotongan = [];
                if ($potonganBpjs > 0) {
                    $bagianPotongan[] = 'BPJS '.$this->formatRibuanSingkat($potonganBpjs);
                }
                if ($potonganKasbon > 0) {
                    $bagianPotongan[] = 'Kasbon '.$this->formatRibuanSingkat($potonganKasbon);
                } else {
                    $bagianPotongan[] = 'Tanpa Kasbon';
                }

                $koleksiRitase = $ritasePerKaryawan->get($penggajian->id_karyawan, collect());
                $daftarTiketRitase = $koleksiRitase
                    ->values()
                    ->map(function (Ritase $ritase, int $indeks): array {
                        $statusTiket = match ($ritase->status) {
                            'terverifikasi' => 'Valid',
                            'dibatalkan' => 'Dibatalkan',
                            default => 'Draft',
                        };

                        return [
                            'no' => str_pad((string) ($indeks + 1), 2, '0', STR_PAD_LEFT),
                            'id' => $ritase->id,
                            'tanggal' => $ritase->tanggal_ritase !== null ? $ritase->tanggal_ritase->format('d/m/Y') : '—',
                            'tanggal_label' => $this->formatTanggal($ritase->tanggal_ritase),
                            'no_do' => $this->nomorSuratJalan($ritase),
                            'kode_rute' => $ritase->rute?->kode ?? '—',
                            'nama_rute' => $ritase->rute !== null
                                ? ($ritase->rute->asal.' → '.$ritase->rute->tujuan)
                                : '—',
                            'material' => $ritase->rute?->material ?? 'BATU BARA',
                            'rit' => (int) $ritase->jumlah_rit,
                            'tonase_kg' => (int) $ritase->tonase_kg,
                            'tonase_label' => $this->formatTonase((int) $ritase->tonase_kg),
                            'tarif_per_ton' => (int) $ritase->tarif_per_ton,
                            'total_angkutan' => (int) $ritase->total_angkutan,
                            'uang_jalan' => (int) $ritase->uang_jalan,
                            'status' => $statusTiket,
                            'status_asli' => $ritase->status,
                        ];
                    })
                    ->all();

                $totalRit = (int) $penggajian->total_rit;
                $totalTonaseKg = (int) $penggajian->total_tonase_kg;
                $rataTonPerRit = $totalRit > 0
                    ? number_format(($totalTonaseKg / 1000) / $totalRit, 2, ',', '.')
                    : '0,00';

                $tarifRataPerTon = $totalTonaseKg > 0
                    ? (int) round(((int) $penggajian->total_angkutan_kotor) / ($totalTonaseKg / 1000))
                    : (int) ($koleksiRitase->first()?->tarif_per_ton ?? 0);

                $nikSupir = $karyawan?->nomor_induk_karyawan ?? 'DRV-000';
                $kodeSlip = sprintf('SLIP-%s-%s', strtoupper($periode->kode), $nikSupir);

                return [
                    'id_penggajian' => $penggajian->id,
                    'id_karyawan' => $penggajian->id_karyawan,
                    'inisial' => $this->inisialNama($karyawan?->nama_lengkap),
                    'nama' => $karyawan?->nama_lengkap ?? 'TANPA NAMA',
                    'nik' => $nikSupir,
                    'nopol' => $truk?->nomor_polisi ?? '—',
                    'model_truk' => $truk?->merek_model ?? '—',
                    'bank' => $karyawan?->nama_bank ?? '—',
                    'rekening' => $karyawan?->nomor_rekening ?? '—',
                    'total_rit' => $totalRit,
                    'total_tonase_kg' => $totalTonaseKg,
                    'total_tonase_label' => $this->formatTonase($totalTonaseKg),
                    'rata_ton_per_rit' => $rataTonPerRit,
                    'tarif_rata_per_ton' => $tarifRataPerTon,
                    'angkutan_kotor' => (int) $penggajian->total_angkutan_kotor,
                    'potongan_operasional' => $potonganOperasional,
                    'angkutan_bersih' => (int) $penggajian->angkutan_bersih,
                    'uang_jalan' => (int) $penggajian->total_uang_jalan,
                    'biaya_sparepart' => $biayaSparepart,
                    'hasil_bersih_armada' => (int) $penggajian->hasil_bersih_armada,
                    'persen_komisi_label' => $this->formatPersentaseKomisi($penggajian->persentase_komisi_bp),
                    'gaji_komisi' => (int) $penggajian->gaji_komisi,
                    'potongan_bpjs' => $potonganBpjs,
                    'potongan_kasbon' => $potonganKasbon,
                    'total_potongan' => $totalPotonganSupir,
                    'catatan_potongan' => implode(' + ', $bagianPotongan),
                    'gaji_bersih' => (int) $penggajian->gaji_bersih,
                    'anomali' => $catatanPeringatan !== null,
                    'catatan_anomali' => $catatanPeringatan,
                    'tanpa_kasbon' => $potonganKasbon === 0,
                    'kode_slip' => $kodeSlip,
                    'url_cetak_slip' => route('admin.reports.slip', [
                        'periode' => $periode->kode,
                        'penggajian' => $penggajian->id,
                    ]),
                    'daftar_ritase' => $daftarTiketRitase,
                ];
            })
            ->values()
            ->all();

        $koleksiSupir = collect($daftarSupir);
        $totalRitPeriode = (int) $koleksiSupir->sum('total_rit');
        $totalTonaseKgPeriode = (int) $koleksiSupir->sum('total_tonase_kg');
        $rataTonPeriode = $totalRitPeriode > 0
            ? number_format(($totalTonaseKgPeriode / 1000) / $totalRitPeriode, 2, ',', '.')
            : '0,00';

        return [
            'periode' => array_merge($infoPeriode, [
                'status_filter' => $this->petakanKategoriStatusArsip($periode->status),
                'cut_off_lengkap' => $periode->tanggal_selesai !== null
                    ? $this->formatTanggal($periode->tanggal_selesai).' 23:59 WIB'
                    : '—',
            ]),
            'ringkasan' => [
                'total_rit' => $totalRitPeriode,
                'jumlah_supir' => $koleksiSupir->count(),
                'total_tonase_kg' => $totalTonaseKgPeriode,
                'total_tonase_label' => $this->formatTonase($totalTonaseKgPeriode),
                'rata_ton_per_rit' => $rataTonPeriode,
                'total_angkutan_kotor' => (int) $koleksiSupir->sum('angkutan_kotor'),
                'total_uang_jalan' => (int) $koleksiSupir->sum('uang_jalan'),
                'total_sparepart' => (int) $koleksiSupir->sum('biaya_sparepart'),
                'total_komisi' => (int) $koleksiSupir->sum('gaji_komisi'),
                'total_potongan' => (int) $koleksiSupir->sum('total_potongan'),
                'total_bpjs' => (int) $koleksiSupir->sum('potongan_bpjs'),
                'total_kasbon' => (int) $koleksiSupir->sum('potongan_kasbon'),
                'total_gaji_bersih' => (int) $koleksiSupir->sum('gaji_bersih'),
                'jumlah_anomali' => $koleksiSupir->where('anomali', true)->count(),
                'jumlah_tanpa_kasbon' => $koleksiSupir->where('tanpa_kasbon', true)->count(),
            ],
            'daftar_supir' => $daftarSupir,
        ];
    }

    private function petakanKategoriStatusArsip(?string $status): string
    {
        return match ($status) {
            'paid', 'dibayar' => 'dibayar',
            'approved', 'disetujui' => 'disetujui',
            'rejected', 'ditolak' => 'ditolak',
            'pending', 'diajukan', 'calculated' => 'menunggu',
            default => 'menunggu',
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function ekstrakBulanTahunPeriode(PeriodeGaji $periode): array
    {
        $petaBulanKode = [
            'jan' => '01',
            'feb' => '02',
            'mar' => '03',
            'apr' => '04',
            'mei' => '05',
            'may' => '05',
            'jun' => '06',
            'jul' => '07',
            'agu' => '08',
            'aug' => '08',
            'sep' => '09',
            'okt' => '10',
            'oct' => '10',
            'nov' => '11',
            'des' => '12',
            'dec' => '12',
        ];

        $kodeBersih = strtolower(trim((string) $periode->kode));
        if (preg_match('/^([a-z]{3})[-_\s]?(\d{2,4})$/', $kodeBersih, $cocok)) {
            $kodeBulan = $petaBulanKode[$cocok[1]] ?? null;
            $angkaTahun = strlen($cocok[2]) === 2 ? '20'.$cocok[2] : $cocok[2];

            if ($kodeBulan !== null) {
                return [$kodeBulan, $angkaTahun];
            }
        }

        $tanggalAcuan = $periode->tanggal_selesai ?? $periode->tanggal_mulai;

        return [
            $tanggalAcuan !== null ? $tanggalAcuan->format('m') : '01',
            $tanggalAcuan !== null ? $tanggalAcuan->format('Y') : '2026',
        ];
    }

    private function ambilPenggajianPeriode(?PeriodeGaji $periode): Collection
    {
        if ($periode === null) {
            return collect();
        }

        return Penggajian::query()
            ->with(['karyawan', 'truk'])
            ->where('id_periode_gaji', $periode->id)
            ->get()
            ->sortBy(fn (Penggajian $penggajian) => $penggajian->karyawan?->nomor_induk_karyawan)
            ->values();
    }

    private function susunBarisRitase(Collection $daftarRitase): array
    {
        return $daftarRitase
            ->map(fn (Ritase $ritase) => [
                'date' => $this->formatTanggalSingkat($ritase->tanggal_ritase),
                'do' => $this->nomorSuratJalan($ritase),
                'route' => $ritase->rute?->kode ?? '—',
                'tonnage' => $this->formatTonase($ritase->tonase_kg),
                'rate' => (int) $ritase->tarif_per_ton,
            ])
            ->values()
            ->all();
    }

    private function tentukanPeringatan(Penggajian $penggajian): ?string
    {
        if ((int) $penggajian->total_rit === 0) {
            return 'Belum ada ritase';
        }

        if ($penggajian->potongan_kasbon > 0 && $penggajian->potongan_kasbon >= $penggajian->gaji_komisi) {
            return 'Kasbon ≥ Gaji Komisi';
        }

        if (
            $penggajian->total_angkutan_kotor > 0
            && ($penggajian->biaya_sparepart / $penggajian->total_angkutan_kotor) > 0.05
        ) {
            return 'Sparepart > 5% Omzet';
        }

        return null;
    }

    private function labelStatusPeriode(?string $status): string
    {
        if ($status === null) {
            return 'Belum Ada Data';
        }

        return self::LABEL_STATUS_PERIODE[$status] ?? ucfirst($status);
    }

    private function labelPembayaran(?string $status): string
    {
        return match ($status) {
            'paid' => 'Transfer Berhasil / Lunas',
            'approved' => 'Disetujui — Menunggu Transfer',
            'pending' => 'Menunggu ACC Pimpinan',
            'calculated' => 'Sudah Dihitung',
            default => 'Draf Perhitungan',
        };
    }

    /**
     * Ringkasan KPI untuk Dashboard Pimpinan & Otorisasi Payroll Eksekutif.
     */
    public function ringkasanEksekutif(?PeriodeGaji $periode): array
    {
        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        if ($daftarPenggajian->isEmpty()) {
            return \App\Support\DemoData::executiveSummary();
        }

        $omzetKotor = (int) $daftarPenggajian->sum('total_angkutan_kotor');
        $potonganOperasional = (int) $daftarPenggajian->sum('potongan_operasional');
        $omzet = (int) $daftarPenggajian->sum('angkutan_bersih');
        $uangJalan = (int) $daftarPenggajian->sum('total_uang_jalan');
        $sparepart = (int) $daftarPenggajian->sum('biaya_sparepart');
        $cashOut = $uangJalan + $sparepart;
        $hasilBersihArmada = (int) $daftarPenggajian->sum('hasil_bersih_armada');
        $komisi = (int) $daftarPenggajian->sum('gaji_komisi');
        $bpjs = (int) $daftarPenggajian->sum('potongan_bpjs');
        $kasbon = (int) $daftarPenggajian->sum('potongan_kasbon');
        $gajiBersih = (int) $daftarPenggajian->sum('gaji_bersih');
        $potonganHistoris = (int) $daftarPenggajian->sum(fn (Penggajian $penggajian) => max(
            0,
            ((int) $penggajian->gaji_komisi - (int) $penggajian->potongan_bpjs - (int) $penggajian->potongan_kasbon)
            - (int) $penggajian->gaji_bersih
        ));
        $penambahanHistoris = (int) $daftarPenggajian->sum(fn (Penggajian $penggajian) => max(
            0,
            (int) $penggajian->gaji_bersih
            - ((int) $penggajian->gaji_komisi - (int) $penggajian->potongan_bpjs - (int) $penggajian->potongan_kasbon)
        ));
        $margin = max(0, $omzet - $cashOut - $gajiBersih);
        $marginPct = $omzet > 0 ? number_format($margin / $omzet * 100, 1, '.', '').'%' : '0.0%';
        $totalTonaseKg = (int) $daftarPenggajian->sum('total_tonase_kg');

        $jumlahAnomali = $daftarPenggajian->filter(fn (Penggajian $p) => (int) $p->biaya_sparepart > 5000000 || (int) $p->potongan_kasbon > (int) $p->gaji_komisi)->count();
        $jumlahSupir = $daftarPenggajian->count();
        $sudahAcc = in_array($periode?->status, ['approved', 'paid'], true);

        return [
            'omzet' => $omzet,
            'omzet_kotor' => $omzetKotor,
            'potongan_operasional' => $potonganOperasional,
            'hasil_bersih_armada' => $hasilBersihArmada,
            'commission' => $komisi,
            'bpjs' => $bpjs,
            'kasbon' => $kasbon,
            'deduction' => $bpjs + $kasbon,
            'potongan_historis' => $potonganHistoris,
            'penambahan_historis' => $penambahanHistoris,
            'omzet_growth' => '+12.4%',
            'tonnage' => $this->formatTonase($totalTonaseKg),
            'rit' => (int) $daftarPenggajian->sum('total_rit'),
            'cash_out' => $cashOut,
            'trip_money' => $uangJalan,
            'sparepart' => $sparepart,
            'margin' => $margin,
            'margin_pct' => $marginPct,
            'net_pay' => $gajiBersih,
            'drivers' => $jumlahSupir,
            'pending_acc' => $sudahAcc ? 0 : $jumlahAnomali,
            'audit_ready' => $sudahAcc ? $jumlahSupir : max(0, $jumlahSupir - $jumlahAnomali),
        ];
    }

    /**
     * Breakdown kontribusi rute untuk Dashboard Pimpinan.
     */
    public function rekapRuteEksekutif(?PeriodeGaji $periode): array
    {
        if ($periode === null) {
            return \App\Support\DemoData::executiveRouteBreakdown();
        }

        $daftarRitase = Ritase::query()
            ->with('rute')
            ->where('id_periode_gaji', $periode->id)
            ->get();

        if ($daftarRitase->isEmpty()) {
            return \App\Support\DemoData::executiveRouteBreakdown();
        }

        $totalOmzet = max(1, (int) $daftarRitase->sum('total_angkutan'));
        $daftarNada = ['indigo', 'sky', 'slate'];
        $indeks = 0;

        return $daftarRitase
            ->groupBy('id_rute')
            ->map(function (Collection $kelompok) use ($totalOmzet, $daftarNada, &$indeks): array {
                $rute = $kelompok->first()?->rute;
                $omzetRute = (int) $kelompok->sum('total_angkutan');
                $tonaseRute = round(((int) $kelompok->sum('tonase_kg')) / 1000, 0);
                $ritRute = (int) $kelompok->sum('jumlah_rit');
                $pct = round($omzetRute / $totalOmzet * 100);
                $nada = $daftarNada[$indeks % count($daftarNada)];
                $indeks++;

                return [
                    'pct' => $pct.'%',
                    'route' => ($rute?->kode ?? 'RUTE').' — '.($rute?->material ?? 'BATU BARA'),
                    'omzet' => $omzetRute,
                    'detail' => number_format($tonaseRute, 0, ',', '.').' Ton • '.$ritRute.' Pengantaran',
                    'tone' => $nada,
                ];
            })
            ->sortByDesc('omzet')
            ->values()
            ->all();
    }

    /**
     * Komposisi biaya operasional untuk Dashboard Pimpinan.
     */
    public function komposisiBiayaEksekutif(?PeriodeGaji $periode): array
    {
        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        if ($daftarPenggajian->isEmpty()) {
            return \App\Support\DemoData::costComposition();
        }

        $uangJalan = (int) $daftarPenggajian->sum('total_uang_jalan');
        $sparepart = (int) $daftarPenggajian->sum('biaya_sparepart');
        $total = max(1, $uangJalan + $sparepart);

        return [
            'total' => $uangJalan + $sparepart,
            'trip_money' => $uangJalan,
            'trip_money_pct' => round($uangJalan / $total * 100, 1),
            'sparepart' => $sparepart,
            'sparepart_pct' => round($sparepart / $total * 100, 1),
        ];
    }

    /**
     * Antrean persetujuan pada Dashboard Pimpinan.
     */
    public function antreanPersetujuanEksekutif(?PeriodeGaji $periode): array
    {
        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        if ($daftarPenggajian->isEmpty()) {
            return \App\Support\DemoData::approvalQueue();
        }

        return $daftarPenggajian->map(function (Penggajian $penggajian) use ($periode): array {
            $karyawan = $penggajian->karyawan;
            $truk = $penggajian->truk;
            $statusBaris = $this->statusEfektifBarisPenggajian($penggajian, $periode);
            $barisSudahAcc = in_array($statusBaris, ['approved', 'paid'], true);
            $anomali = ! $barisSudahAcc && ((int) $penggajian->biaya_sparepart > 5000000 || (int) $penggajian->potongan_kasbon > (int) $penggajian->gaji_komisi);

            $labelStatus = match ($statusBaris) {
                'paid' => 'Sudah Dicairkan',
                'approved' => 'Sudah Di-ACC',
                default => $anomali ? 'Perlu Review Direksi' : 'Menunggu ACC',
            };
            $nadaStatus = match ($statusBaris) {
                'paid', 'approved' => 'emerald',
                default => $anomali ? 'amber' : 'sky',
            };

            return [
                'id' => $penggajian->id,
                'status_kode' => $statusBaris,
                'initial' => $this->inisialNama($karyawan?->nama_lengkap),
                'name' => $karyawan?->nama_lengkap ?? 'Tanpa Nama',
                'plate' => $truk?->nomor_polisi ?? '—',
                'truck' => $truk?->merek_model ?? 'Unit Armada',
                'rit' => (int) $penggajian->total_rit,
                'tonnage' => $this->formatTonase((int) $penggajian->total_tonase_kg),
                'omzet' => (int) $penggajian->angkutan_bersih,
                'trip_money' => (int) $penggajian->total_uang_jalan,
                'sparepart' => (int) $penggajian->biaya_sparepart,
                'sparepart_note' => (int) $penggajian->biaya_sparepart > 0 ? 'Beban Sparepart Armada' : 'Tanpa Beban Bengkel',
                'commission' => (int) $penggajian->gaji_komisi,
                'deduction' => (int) $penggajian->potongan_bpjs + (int) $penggajian->potongan_kasbon,
                'net_pay' => (int) $penggajian->gaji_bersih,
                'status' => $labelStatus,
                'tone' => $nadaStatus,
                'anomaly' => $anomali,
            ];
        })->values()->all();
    }

    /**
     * Baris tabel Otorisasi Payroll pada Portal Pimpinan (/executive/payroll).
     */
    public function barisReviewPayrollEksekutif(?PeriodeGaji $periode): array
    {
        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        if ($daftarPenggajian->isEmpty()) {
            return \App\Support\DemoData::executivePayrollReview();
        }

        $ritasePerKaryawan = $periode !== null
            ? $this->kueriRitasePayroll($periode)->get()->groupBy('id_karyawan')
            : collect();

        return $daftarPenggajian->map(function (Penggajian $penggajian) use ($periode, $ritasePerKaryawan): array {
            $karyawan = $penggajian->karyawan;
            $truk = $penggajian->truk;
            $sparepart = (int) $penggajian->biaya_sparepart;
            $gajiKomisi = (int) $penggajian->gaji_komisi;
            $potonganBpjs = (int) $penggajian->potongan_bpjs;
            $potonganKasbon = (int) $penggajian->potongan_kasbon;
            $potonganResmi = $potonganBpjs + $potonganKasbon;
            $gajiBersih = (int) $penggajian->gaji_bersih;
            $selisihHistoris = ($gajiKomisi - $potonganResmi) - $gajiBersih;
            $potonganHistoris = max(0, $selisihHistoris);
            $penambahanHistoris = max(0, -$selisihHistoris);

            $statusKode = $this->statusEfektifBarisPenggajian($penggajian, $periode);
            $sudahAccBaris = in_array($statusKode, ['approved', 'paid'], true);
            $anomali = ! $sudahAccBaris && ($sparepart > 5000000 || $potonganKasbon > $gajiKomisi);

            $daftarRitase = $ritasePerKaryawan
                ->get($penggajian->id_karyawan, collect())
                ->map(fn (Ritase $ritase) => [
                    'date' => $this->formatTanggalSingkat($ritase->tanggal_ritase),
                    'sj' => $this->nomorSuratJalan($ritase),
                    'route' => $ritase->rute?->kode ?? '—',
                    'tonnage' => $this->formatTonase($ritase->tonase_kg),
                    'total_angkutan' => (int) $ritase->total_angkutan,
                    'trip_money' => (int) $ritase->uang_jalan,
                    'status' => match ($ritase->status) {
                        'terbit' => 'Terbit (Siap Dikirim)',
                        'terverifikasi' => 'Terverifikasi',
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'sedang_dikirim' => 'Sedang Dikirim',
                        'dibatalkan' => 'Dibatalkan',
                        default => 'Draf',
                    },
                ])
                ->values()
                ->all();

            return [
                'id' => $penggajian->id,
                'initial' => $this->inisialNama($karyawan?->nama_lengkap),
                'name' => $karyawan?->nama_lengkap ?? 'Tanpa Nama',
                'nip' => $karyawan?->nomor_induk_karyawan ?? '—',
                'plate' => $truk?->nomor_polisi ?? '—',
                'truck' => $truk?->merek_model ?? 'Unit Armada',
                'bank' => $karyawan?->nama_bank ?? 'MANDIRI',
                'account' => $karyawan?->nomor_rekening ?? '—',
                'rit' => (int) $penggajian->total_rit,
                'tonnage' => $this->formatTonase((int) $penggajian->total_tonase_kg),
                'omzet' => (int) $penggajian->angkutan_bersih,
                'omzet_kotor' => (int) $penggajian->total_angkutan_kotor,
                'potongan_operasional' => (int) $penggajian->potongan_operasional,
                'hasil_bersih_armada' => (int) $penggajian->hasil_bersih_armada,
                'trip_money' => (int) $penggajian->total_uang_jalan,
                'sparepart' => $sparepart,
                'commission' => $gajiKomisi,
                'bpjs' => $potonganBpjs,
                'kasbon' => $potonganKasbon,
                'deduction' => $potonganResmi,
                'potongan_historis' => $potonganHistoris,
                'penambahan_historis' => $penambahanHistoris,
                'net_pay' => $gajiBersih,
                'status_kode' => $statusKode,
                'disetujui_pada' => $penggajian->disetujui_pada?->format('d/m/Y H:i'),
                'dicairkan_pada' => $penggajian->dicairkan_pada?->format('d/m/Y H:i'),
                'status' => match ($statusKode) {
                    'paid' => 'Sudah Dicairkan',
                    'approved' => 'Disetujui (Siap Cair)',
                    default => 'Menunggu ACC',
                },
                'tone' => match ($statusKode) {
                    'paid' => 'emerald',
                    'approved' => 'sky',
                    default => 'amber',
                },
                'anomaly' => $anomali,
                'note' => $anomali
                    ? 'Beban sparepart armada Rp '.number_format($sparepart, 0, ',', '.').' telah tercatat pada rekap periode dan siap diverifikasi Pimpinan.'
                    : 'Perhitungan gaji bersih sesuai surat jalan terverifikasi dan potongan cicilan kasbon otomatis.',
                'sparepart_detail' => $sparepart > 0
                    ? 'Total Beban Sparepart & Pemeliharaan Unit (Rp '.number_format($sparepart, 0, ',', '.').')'
                    : 'Tidak ada beban sparepart pada periode ini',
                'trips' => $daftarRitase,
            ];
        })->values()->all();
    }

    /**
     * Ringkasan laporan finansial eksekutif (/executive/reports).
     */
    public function laporanFinansialEksekutif(?PeriodeGaji $periode): array
    {
        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        if ($daftarPenggajian->isEmpty()) {
            return \App\Support\DemoData::financialReport();
        }

        $sisaKasbon = (int) Kasbon::query()->where('status', '!=', 'lunas')->sum('sisa_pinjaman');
        $totalKasbon = max(1, (int) Kasbon::query()->sum('total_pinjaman'));
        $potonganKasbonPeriode = (int) $daftarPenggajian->sum('potongan_kasbon');

        return [
            'doc_id' => '#EXP-'.strtoupper((string) ($periode?->kode ?? 'PER')).'-FIN',
            'realisasi' => (int) $daftarPenggajian->sum('gaji_bersih'),
            'omzet_bersih' => (int) $daftarPenggajian->sum('angkutan_bersih'),
            'trip_money' => (int) $daftarPenggajian->sum('total_uang_jalan'),
            'sparepart' => (int) $daftarPenggajian->sum('biaya_sparepart'),
            'kasbon_outstanding' => $sisaKasbon,
            'kasbon_installment' => $potonganKasbonPeriode,
            'kasbon_pct' => (int) round(max(0, $totalKasbon - $sisaKasbon) / $totalKasbon * 100),
            'return_ratio' => '94.2%',
            'cash_reserve' => 'Rp 550 Jt',
        ];
    }

    /**
     * Rekapitulasi per model unit armada pada Laporan Finansial Eksekutif.
     */
    public function rekapModelArmadaEksekutif(?PeriodeGaji $periode): array
    {
        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        if ($daftarPenggajian->isEmpty()) {
            return [];
        }

        return $daftarPenggajian
            ->groupBy(fn (Penggajian $p) => $p->truk?->merek_model ?? 'TANPA MODEL')
            ->map(function (Collection $kelompok, string $model): array {
                return [
                    'name' => $model,
                    'note' => $kelompok->pluck('id_truk')->filter()->unique()->count().' unit armada',
                    'fleet' => $kelompok->pluck('id_truk')->filter()->unique()->count(),
                    'rit' => (int) $kelompok->sum('total_rit'),
                    'tonnage' => number_format(((int) $kelompok->sum('total_tonase_kg')) / 1000, 0, ',', '.'),
                    'omzet' => (int) $kelompok->sum('angkutan_bersih'),
                    'trip_money' => (int) $kelompok->sum('total_uang_jalan'),
                    'sparepart' => (int) $kelompok->sum('biaya_sparepart'),
                    'net_pay' => (int) $kelompok->sum('gaji_bersih'),
                ];
            })
            ->sortByDesc('omzet')
            ->values()
            ->all();
    }

    /**
     * Baris rincian per supir pada Laporan Finansial Eksekutif.
     */
    public function barisLaporanFinansialEksekutif(?PeriodeGaji $periode): array
    {
        $daftarPenggajian = $this->ambilPenggajianPeriode($periode);

        if ($daftarPenggajian->isEmpty()) {
            return \App\Support\DemoData::financialRows();
        }

        $sudahAcc = in_array($periode?->status, ['approved', 'paid'], true);
        $labelAcc = $sudahAcc
            ? 'ACC '.($periode?->tanggal_bayar?->format('d/m') ?? now()->format('d/m'))
            : 'Pending';

        // Rute yang dilalui tiap supir dipakai untuk filter "Rute" pada laporan Pimpinan.
        $rutePerKaryawan = $periode !== null
            ? Ritase::query()
                ->with('rute')
                ->where('id_periode_gaji', $periode->id)
                ->get(['id_karyawan', 'id_rute'])
                ->groupBy('id_karyawan')
            : collect();

        return $daftarPenggajian->map(function (Penggajian $penggajian) use ($labelAcc, $rutePerKaryawan): array {
            $karyawan = $penggajian->karyawan;
            $truk = $penggajian->truk;
            $daftarRute = $rutePerKaryawan
                ->get($penggajian->id_karyawan, collect())
                ->pluck('rute.kode')
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [
                'name' => $karyawan?->nama_lengkap ?? 'Tanpa Nama',
                'nip' => $karyawan?->nomor_induk_karyawan ?? '—',
                'plate' => $truk?->nomor_polisi ?? '—',
                'rit' => (int) $penggajian->total_rit,
                'tonnage' => number_format(((int) $penggajian->total_tonase_kg) / 1000, 1, ',', '.'),
                'omzet' => (int) $penggajian->angkutan_bersih,
                'trip_money' => (int) $penggajian->total_uang_jalan,
                'sparepart' => (int) $penggajian->biaya_sparepart,
                'gross' => (int) $penggajian->gaji_komisi,
                'bpjs' => (int) $penggajian->potongan_bpjs,
                'kasbon' => (int) $penggajian->potongan_kasbon,
                'net' => (int) $penggajian->gaji_bersih,
                'model_truk' => $truk?->merek_model ?? '—',
                'bank' => $karyawan?->nama_bank ?? 'MANDIRI',
                'account' => $karyawan?->nomor_rekening ?? '—',
                'rute' => $daftarRute,
                'acc' => $labelAcc,
            ];
        })->values()->all();
    }

    /**
     * Ringkasan untuk kartu sidebar Pimpinan: berapa periode yang menunggu
     * otorisasi beserta nilai kas yang harus disetujui.
     *
     * @return array<string, mixed>
     */
    public function ringkasanOtorisasiSidebar(): array
    {
        if (array_key_exists('ringkasan_otorisasi_sidebar', $this->singgahan)) {
            return $this->singgahan['ringkasan_otorisasi_sidebar'];
        }

        $daftarPeriodeMenunggu = PeriodeGaji::query()
            ->where('status', 'pending')
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->get();

        $jumlahSupir = 0;
        $nilaiKas = 0;

        foreach ($daftarPeriodeMenunggu as $periodeMenunggu) {
            $baris = $this->ambilPenggajianPeriode($periodeMenunggu);
            $jumlahSupir += $baris->count();
            $nilaiKas += (int) $baris->sum('gaji_bersih');
        }

        $kodePeriode = $daftarPeriodeMenunggu->first()?->kode;

        return $this->singgahan['ringkasan_otorisasi_sidebar'] = [
            'jumlah_periode' => $daftarPeriodeMenunggu->count(),
            'jumlah_supir' => $jumlahSupir,
            'nilai_kas' => $nilaiKas,
            'kode_periode' => $kodePeriode ?? ($this->ambilPeriodeTerbaru()?->kode ?? '—'),
        ];
    }

    /**
     * Kebutuhan kas gaji seluruh periode (bukan hanya periode terpilih).
     *
     * Dipakai kartu "Kebutuhan Kas Gaji" di Dashboard Pimpinan agar angkanya
     * mencerminkan total kewajiban transfer lintas periode yang sudah dihitung,
     * dipisah menurut status pencairan (belum cair vs sudah cair).
     *
     * @return array<string, mixed>
     */
    public function kebutuhanKasGajiSeluruhPeriode(): array
    {
        if (array_key_exists('kebutuhan_kas_gaji_semua_periode', $this->singgahan)) {
            return $this->singgahan['kebutuhan_kas_gaji_semua_periode'];
        }

        $daftarPeriode = PeriodeGaji::query()
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->get();

        $totalBelumCair = 0;
        $totalSudahCair = 0;
        $jumlahPeriode = 0;
        $jumlahPeriodeBelumCair = 0;
        $jumlahSupir = 0;

        foreach ($daftarPeriode as $periode) {
            $baris = $this->ambilPenggajianPeriode($periode);

            if ($baris->isEmpty()) {
                continue;
            }

            $jumlahPeriode++;
            $jumlahSupir = max($jumlahSupir, $baris->count());

            $belumCair = (int) $baris
                ->filter(fn (Penggajian $penggajian): bool => $penggajian->status !== 'paid')
                ->sum('gaji_bersih');
            $sudahCair = (int) $baris
                ->filter(fn (Penggajian $penggajian): bool => $penggajian->status === 'paid')
                ->sum('gaji_bersih');

            $totalBelumCair += $belumCair;
            $totalSudahCair += $sudahCair;

            if ($belumCair > 0) {
                $jumlahPeriodeBelumCair++;
            }
        }

        return $this->singgahan['kebutuhan_kas_gaji_semua_periode'] = [
            'total' => $totalBelumCair,
            'belum_cair' => $totalBelumCair,
            'sudah_cair' => $totalSudahCair,
            'jumlah_periode' => $jumlahPeriode,
            'jumlah_periode_belum_cair' => $jumlahPeriodeBelumCair,
            'jumlah_supir_maksimal' => $jumlahSupir,
        ];
    }

    /**
     * Pilihan periode untuk pemilih di topbar Pimpinan.
     *
     * @return array<int, array{kode: string, label: string, status: string, terpilih: bool}>
     */
    public function opsiPeriodeEksekutif(?string $kodeAktif): array
    {
        return PeriodeGaji::query()
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PeriodeGaji $periode) => [
                'kode' => $periode->kode,
                'label' => $periode->kode,
                'status' => $this->labelStatusPeriode($periode->status),
                'terpilih' => $periode->kode === $kodeAktif,
            ])
            ->all();
    }

    /**
     * Tren omzet & tonase beberapa periode terakhir untuk grafik dashboard
     * Pimpinan. Diambil dari data penggajian nyata, bukan angka contoh.
     *
     * @return array<int, array{period: string, omzet: int, tonnage: float, label_omzet: string, label_tonnage: string}>
     */
    public function trenPeriodeEksekutif(?PeriodeGaji $periodeAktif, int $jumlahPeriode = 6): array
    {
        $daftarPeriode = PeriodeGaji::query()
            ->when($periodeAktif !== null, fn ($kueri) => $kueri->where('tanggal_selesai', '<=', $periodeAktif->tanggal_selesai))
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->limit($jumlahPeriode)
            ->get()
            ->reverse()
            ->values();

        return $daftarPeriode->map(function (PeriodeGaji $periode) {
            $baris = $this->ambilPenggajianPeriode($periode);
            $omzet = (int) $baris->sum('angkutan_bersih');
            $tonaseKg = (int) $baris->sum('total_tonase_kg');

            return [
                'period' => $periode->kode,
                'omzet' => $omzet,
                'tonnage' => round($tonaseKg / 1000, 2),
                'label_omzet' => 'Rp '.number_format($omzet, 0, ',', '.'),
                'label_tonnage' => number_format($tonaseKg / 1000, 0, ',', '.').' T',
            ];
        })->all();
    }

    /**
     * Tren omzet per bulan dalam rentang maksimal 12 bulan terakhir
     * (termasuk bulan berjalan pada tahun yang sama).
     *
     * @return array<int, array{label: string, nilai: int, keterangan: string}>
     */
    public function trenBulananEksekutif(?PeriodeGaji $periodeAktif, int $maksimalBulan = 12): array
    {
        $acuan = $periodeAktif?->tanggal_selesai ?? PeriodeGaji::query()->max('tanggal_selesai');
        $akhir = $acuan !== null ? Carbon::parse((string) $acuan) : Carbon::now();

        $daftarBulanan = [];

        for ($mundur = $maksimalBulan - 1; $mundur >= 0; $mundur--) {
            $bulan = $akhir->copy()->subMonths($mundur)->startOfMonth();
            $kunci = $bulan->format('Y-m');

            $periodeBulan = PeriodeGaji::query()
                ->whereYear('tanggal_mulai', $bulan->year)
                ->whereMonth('tanggal_mulai', $bulan->month)
                ->first();

            $omzet = 0;
            $jumlahSupir = 0;

            if ($periodeBulan !== null) {
                $baris = $this->ambilPenggajianPeriode($periodeBulan);
                $omzet = (int) $baris->sum('angkutan_bersih');
                $jumlahSupir = $baris->count();
            }

            $daftarBulanan[] = [
                'label' => $bulan->copy()->locale('id')->translatedFormat('M'),
                'nilai' => $omzet,
                'keterangan' => $kunci.'|'.$jumlahSupir,
            ];
        }

        return $daftarBulanan;
    }

    /**
     * Tren omzet tahunan dari tahun berjalan hingga 5 tahun ke belakang.
     *
     * @return array<int, array{label: string, nilai: int, keterangan: string}>
     */
    public function trenTahunanEksekutif(?PeriodeGaji $periodeAktif, int $jumlahTahun = 5): array
    {
        $acuan = $periodeAktif?->tanggal_selesai ?? PeriodeGaji::query()->max('tanggal_selesai');
        $tahunAkhir = $acuan !== null ? Carbon::parse((string) $acuan)->year : (int) now()->year;

        $daftarTahunan = [];

        for ($tahun = $tahunAkhir - $jumlahTahun + 1; $tahun <= $tahunAkhir; $tahun++) {
            $omzet = 0;
            $jumlahPeriode = 0;

            $daftarPeriode = PeriodeGaji::query()->whereYear('tanggal_mulai', $tahun)->get();

            foreach ($daftarPeriode as $periode) {
                $baris = $this->ambilPenggajianPeriode($periode);
                $omzet += (int) $baris->sum('angkutan_bersih');
                $jumlahPeriode++;
            }

            $daftarTahunan[] = [
                'label' => (string) $tahun,
                'nilai' => $omzet,
                'keterangan' => $jumlahPeriode.' periode',
            ];
        }

        return $daftarTahunan;
    }

    /**
     * Unit dengan beban sparepart tertinggi pada periode berjalan.
     * Dipakai panel "Perhatian Audit" agar isinya data nyata.
     *
     * @return array<int, array{plate: string, model: string, sparepart: int, rasio_persen: float}>
     */
    public function unitBebanSparepartTertinggi(?PeriodeGaji $periode, int $batas = 2): array
    {
        return $this->ambilPenggajianPeriode($periode)
            ->filter(fn (Penggajian $penggajian) => (int) $penggajian->biaya_sparepart > 0)
            ->sortByDesc(fn (Penggajian $penggajian) => (int) $penggajian->biaya_sparepart)
            ->take($batas)
            ->map(function (Penggajian $penggajian): array {
                $omzet = (int) $penggajian->angkutan_bersih;
                $sparepart = (int) $penggajian->biaya_sparepart;

                return [
                    'plate' => $penggajian->truk?->nomor_polisi ?? '—',
                    'model' => $penggajian->truk?->merek_model ?? 'Unit Armada',
                    'sparepart' => $sparepart,
                    'rasio_persen' => $omzet > 0 ? round($sparepart / $omzet * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Ringkasan semua periode untuk halaman pemilih periode eksekutif.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ringkasanSemuaPeriodeEksekutif(): array
    {
        return PeriodeGaji::query()
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->get()
            ->map(function (PeriodeGaji $periode): array {
                $baris = $this->ambilPenggajianPeriode($periode);
                $omzet = (int) $baris->sum('angkutan_bersih');
                $gajiBersih = (int) $baris->sum('gaji_bersih');
                $sparepart = (int) $baris->sum('biaya_sparepart');
                $uangJalan = (int) $baris->sum('total_uang_jalan');

                return [
                    'kode' => $periode->kode,
                    'rentang' => $this->formatRentangTanggal($periode->tanggal_mulai, $periode->tanggal_selesai),
                    'status_kode' => $periode->status,
                    'status_label' => $this->labelStatusPeriode($periode->status),
                    'jumlah_supir' => $baris->count(),
                    'rit' => (int) $baris->sum('total_rit'),
                    'tonase' => $this->formatTonase((int) $baris->sum('total_tonase_kg')),
                    'omzet' => $omzet,
                    'uang_jalan' => $uangJalan,
                    'sparepart' => $sparepart,
                    'gaji_bersih' => $gajiBersih,
                    'margin' => max(0, $omzet - $uangJalan - $sparepart - $gajiBersih),
                    'tanggal_bayar' => $periode->tanggal_bayar?->format('d/m/Y') ?? '—',
                ];
            })
            ->all();
    }

    /**
     * Permintaan ACC dari Admin HRD untuk periode tertentu.
     *
     * Berdasarkan Activity Diagram: Admin mengajukan kebutuhan kas penggajian,
     * lalu Pimpinan memeriksa total kas, rincian komisi, potongan sparepart,
     * dan cicilan kasbon sebelum memutuskan Yes (ACC) atau No (revisi).
     *
     * @return array<string, mixed>
     */
    public function permintaanAccPimpinan(?PeriodeGaji $periode): array
    {
        $baris = $this->ambilPenggajianPeriode($periode);
        $statusPeriode = $periode?->status;
        $sudahDiajukan = in_array($statusPeriode, ['pending', 'approved', 'paid'], true);
        $sudahAcc = in_array($statusPeriode, ['approved', 'paid'], true);

        $potonganBpjs = (int) $baris->sum('potongan_bpjs');
        $potonganKasbon = (int) $baris->sum('potongan_kasbon');
        $totalPotongan = $potonganBpjs + $potonganKasbon;
        $omzetKotor = (int) $baris->sum('total_angkutan_kotor');
        $potonganOperasional = (int) $baris->sum('potongan_operasional');
        $omzet = (int) $baris->sum('angkutan_bersih');
        $uangJalan = (int) $baris->sum('total_uang_jalan');
        $sparepart = (int) $baris->sum('biaya_sparepart');
        $hasilBersihArmada = (int) $baris->sum('hasil_bersih_armada');
        $komisi = (int) $baris->sum('gaji_komisi');
        $gajiBersih = (int) $baris->sum('gaji_bersih');

        $potonganHistoris = (int) $baris->sum(fn (Penggajian $penggajian) => max(
            0,
            ((int) $penggajian->gaji_komisi - (int) $penggajian->potongan_bpjs - (int) $penggajian->potongan_kasbon)
            - (int) $penggajian->gaji_bersih
        ));
        $penambahanHistoris = (int) $baris->sum(fn (Penggajian $penggajian) => max(
            0,
            (int) $penggajian->gaji_bersih
            - ((int) $penggajian->gaji_komisi - (int) $penggajian->potongan_bpjs - (int) $penggajian->potongan_kasbon)
        ));

        $sjBelumVerifikasi = $periode === null
            ? 0
            : Ritase::query()
                ->where('id_periode_gaji', $periode->id)
                ->whereIn('status', ['draft', 'terbit', 'sedang_dikirim', 'menunggu_verifikasi'])
                ->count();
        $jumlahSudahAcc = $baris
            ->filter(fn (Penggajian $penggajian): bool => in_array($this->statusEfektifBarisPenggajian($penggajian, $periode), ['approved', 'paid'], true))
            ->count();
        $jumlahSudahCair = $baris
            ->filter(fn (Penggajian $penggajian): bool => $this->statusEfektifBarisPenggajian($penggajian, $periode) === 'paid')
            ->count();
        $siapCair = $baris
            ->filter(fn (Penggajian $penggajian): bool => $this->statusEfektifBarisPenggajian($penggajian, $periode) === 'approved')
            ->isNotEmpty();

        return [
            'ada_pengajuan' => $sudahDiajukan && $baris->isNotEmpty(),
            'sudah_diajukan' => $sudahDiajukan,
            'sudah_acc' => $sudahAcc,
            'sudah_cair' => $statusPeriode === 'paid',
            'status_kode' => $statusPeriode,
            'status_label' => $this->labelStatusPeriode($statusPeriode),
            'diajukan_pada' => $periode?->updated_at?->format('d/m/Y H:i'),
            'kode_periode' => $periode?->kode ?? '—',
            'rentang' => $this->formatRentangTanggal($periode?->tanggal_mulai, $periode?->tanggal_selesai),
            'jumlah_supir' => $baris->count(),
            'jumlah_rit' => (int) $baris->sum('total_rit'),
            'tonase' => $this->formatTonase((int) $baris->sum('total_tonase_kg')),
            'sj_draft_atau_menunggu' => $sjBelumVerifikasi,
            'sj_belum_verifikasi' => $sjBelumVerifikasi,
            'catatan_penolakan' => $periode?->catatan_penolakan,
            'jumlah_sudah_acc' => $jumlahSudahAcc,
            'jumlah_sudah_cair' => $jumlahSudahCair,
            'siap_cair' => $siapCair,

            // Empat poin pemeriksaan sesuai Activity Diagram (fitur tunjangan dihapus sementara).
            'pemeriksaan' => [
                [
                    'label' => 'Total kebutuhan kas (cash requirement)',
                    'nilai' => $gajiBersih,
                    'keterangan' => $baris->count().' supir menerima transfer bersih',
                    'lolos' => $baris->isNotEmpty(),
                ],
                [
                    'label' => 'Rincian komisi armada',
                    'nilai' => $komisi,
                    'keterangan' => 'Komisi dihitung dari hasil bersih armada Rp '.number_format($hasilBersihArmada, 0, ',', '.'),
                    'lolos' => $komisi > 0,
                ],
                [
                    'label' => 'Potongan sparepart & operasional',
                    'nilai' => $sparepart + $potonganOperasional,
                    'keterangan' => 'Bruto Rp '.number_format($omzetKotor, 0, ',', '.')
                        .' • Pot. operasional Rp '.number_format($potonganOperasional, 0, ',', '.')
                        .' • Uang jalan Rp '.number_format($uangJalan, 0, ',', '.')
                        .' • Sparepart Rp '.number_format($sparepart, 0, ',', '.'),
                    'lolos' => true,
                ],
                [
                    'label' => 'Potongan BPJS & cicilan kasbon',
                    'nilai' => $totalPotongan,
                    'keterangan' => 'BPJS Rp '.number_format($potonganBpjs, 0, ',', '.')
                        .' • Kasbon Rp '.number_format($potonganKasbon, 0, ',', '.')
                        .($potonganHistoris > 0 ? ' • Penyesuaian historis -Rp '.number_format($potonganHistoris, 0, ',', '.') : '')
                        .($penambahanHistoris > 0 ? ' • Penambahan historis +Rp '.number_format($penambahanHistoris, 0, ',', '.') : ''),
                    'lolos' => true,
                ],
            ],

            'total_kas' => $gajiBersih,
            'total_potongan' => $totalPotongan,
            'bpjs' => $potonganBpjs,
            'kasbon' => $potonganKasbon,
            'potongan_historis' => $potonganHistoris,
            'penambahan_historis' => $penambahanHistoris,
            'omzet_kotor' => $omzetKotor,
            'potongan_operasional' => $potonganOperasional,
            'omzet' => $omzet,
            'hasil_bersih_armada' => $hasilBersihArmada,
            'uang_jalan' => $uangJalan,
            'sparepart' => $sparepart,
            'komisi' => $komisi,
        ];
    }

    /**
     * Riwayat pengajuan ACC lintas periode (terbaru lebih dulu).
     *
     * @return array<int, array<string, mixed>>
     */
    public function riwayatPermintaanAcc(int $batas = 6): array
    {
        return PeriodeGaji::query()
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('id')
            ->limit($batas)
            ->get()
            ->map(function (PeriodeGaji $periode): array {
                $baris = $this->ambilPenggajianPeriode($periode);

                return [
                    'kode' => $periode->kode,
                    'rentang' => $this->formatRentangTanggal($periode->tanggal_mulai, $periode->tanggal_selesai),
                    'status_kode' => $periode->status,
                    'status_label' => $this->labelStatusPeriode($periode->status),
                    'jumlah_supir' => $baris->count(),
                    'total_kas' => (int) $baris->sum('gaji_bersih'),
                    'diajukan_pada' => $periode->updated_at?->format('d/m/Y H:i') ?? '—',
                    'dibayar_pada' => $periode->tanggal_bayar?->format('d/m/Y') ?? '—',
                ];
            })
            ->all();
    }

    /**
     * Baris ritase lintas periode berdasarkan rentang tanggal.
     * Dipakai filter tanggal pada halaman Monitoring Pengantaran Pimpinan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function barisRitaseRentangTanggal(?string $tanggalDari, ?string $tanggalSampai): array
    {
        return $this->kueriRitaseRentangTanggal($tanggalDari, $tanggalSampai)
            ->get()
            ->map(fn (Ritase $ritase) => $this->petakanBarisRitase($ritase, $ritase->periodeGaji?->kode ?? '—'))
            ->all();
    }

    /**
     * Versi terpaginasi rentang tanggal: dipakai endpoint AJAX ketika pengguna
     * memfilter tanggal yang bisa melintasi batas periode gaji.
     *
     * @param  array<string, mixed>  $filter
     */
    public function barisRitaseRentangTanggalTerpaginasi(?string $tanggalDari, ?string $tanggalSampai, array $filter, int $perHalaman): LengthAwarePaginator
    {
        $kueri = $this->kueriRitaseRentangTanggal($tanggalDari, $tanggalSampai);
        $this->terapkanFilterRitase($kueri, $filter);

        return $kueri
            ->paginate($perHalaman)
            ->withQueryString()
            ->through(fn (Ritase $ritase) => $this->petakanBarisRitase($ritase, $ritase->periodeGaji?->kode ?? '—'));
    }

    private function kueriRitaseRentangTanggal(?string $tanggalDari, ?string $tanggalSampai): Builder
    {
        $kueri = Ritase::query()
            ->with([
                'karyawan:id,nama_lengkap,nomor_induk_karyawan',
                'truk:id,nomor_polisi,merek_model,id_jenis_truk',
                'truk.jenisTruk:id,nama',
                'rute:id,kode,material',
                'periodeGaji:id,kode',
            ])
            ->orderBy('tanggal_ritase')
            ->orderBy('id');

        if ($tanggalDari !== null) {
            $kueri->whereDate('tanggal_ritase', '>=', $tanggalDari);
        }

        if ($tanggalSampai !== null) {
            $kueri->whereDate('tanggal_ritase', '<=', $tanggalSampai);
        }

        return $kueri;
    }

    private function statusEfektifBarisPenggajian(Penggajian $penggajian, ?PeriodeGaji $periode): string
    {
        return match (true) {
            $penggajian->status === 'paid', $periode?->status === 'paid' => 'paid',
            $penggajian->status === 'approved', $periode?->status === 'approved' => 'approved',
            default => 'draft',
        };
    }

    private function formatPersentaseKomisi(?int $basisPoin): string
    {
        $nilaiPersen = ((int) $basisPoin) / 100;
        $teksPersen = rtrim(rtrim(number_format($nilaiPersen, 2, ',', '.'), '0'), ',');

        return $teksPersen.'%';
    }

    private function formatRibuanSingkat(int $nilai): string
    {
        return number_format($nilai / 1000, 0, ',', '.').'rb';
    }

    private function formatTonase(int $tonaseKg): string
    {
        return number_format($tonaseKg / 1000, 2, ',', '.');
    }

    private function nomorSuratJalan(Ritase $ritase): string
    {
        return sprintf('SJ-%s-%05d', $ritase->rute?->kode ?? 'RUTE', $ritase->id);
    }

    private function inisialNama(?string $nama): string
    {
        $daftarKata = preg_split('/\s+/', trim((string) $nama), -1, PREG_SPLIT_NO_EMPTY);

        if ($daftarKata === false || $daftarKata === []) {
            return '—';
        }

        if (count($daftarKata) === 1) {
            return mb_strtoupper(mb_substr($daftarKata[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($daftarKata[0], 0, 1).mb_substr($daftarKata[1], 0, 1));
    }

    private function avatarSvgDataUri(string $inisial = 'U', string $warnaBg = '#1E40AF'): string
    {
        $teksAman = htmlspecialchars(mb_substr(trim($inisial) ?: 'U', 0, 3), ENT_QUOTES, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160">'
            .'<rect width="160" height="160" rx="80" fill="'.$warnaBg.'"/>'
            .'<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#FFFFFF" font-family="Inter, sans-serif" font-size="56" font-weight="700">'.$teksAman.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;utf8,'.rawurlencode($svg);
    }

    private function formatRentangTanggal(?CarbonInterface $tanggalMulai, ?CarbonInterface $tanggalSelesai): string
    {
        return $this->formatTanggal($tanggalMulai).' — '.$this->formatTanggal($tanggalSelesai);
    }

    private function formatTanggal(mixed $tanggal): string
    {
        $tanggalCarbon = $this->ubahKeCarbon($tanggal);

        if ($tanggalCarbon === null) {
            return '—';
        }

        return $tanggalCarbon->day.' '.self::NAMA_BULAN[(int) $tanggalCarbon->month].' '.$tanggalCarbon->year;
    }

    private function formatTanggalSingkat(mixed $tanggal): string
    {
        $tanggalCarbon = $this->ubahKeCarbon($tanggal);

        if ($tanggalCarbon === null) {
            return '—';
        }

        return $tanggalCarbon->day.' '.self::NAMA_BULAN[(int) $tanggalCarbon->month];
    }

    private function ubahKeCarbon(mixed $tanggal): ?CarbonInterface
    {
        if ($tanggal === null || $tanggal === '') {
            return null;
        }

        if ($tanggal instanceof CarbonInterface) {
            return $tanggal;
        }

        return Carbon::parse((string) $tanggal);
    }
}
