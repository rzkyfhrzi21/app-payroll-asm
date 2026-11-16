<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Karyawan;
use App\Models\Rute;
use App\Models\Truk;
use Illuminate\Support\Collection;

/**
 * Katalog halaman (berdasarkan route web) untuk pencarian global di topbar.
 * Hasil dibatasi sesuai peran pengguna yang sedang login.
 */
class PencarianNavigasiService
{
    /**
     * Daftar halaman yang dapat dicari, dikelompokkan menurut peran.
     *
     * @return array<int, array<string, mixed>>
     */
    public function katalogHalaman(): array
    {
        return [
            // ---------- Admin / HRD ----------
            [
                'peran' => [UserRole::Admin],
                'label' => 'Payroll & Approval',
                'keterangan' => 'Perhitungan gaji periode, slip supir, approval pimpinan',
                'route' => 'admin.payroll.index',
                'ikon' => 'payments',
                'grup' => 'Admin',
                'kata_kunci' => ['payroll', 'gaji', 'slip', 'upah', 'approval', 'periode', 'komisi'],
            ],
            [
                'peran' => [UserRole::Admin],
                'label' => 'Data Supir',
                'keterangan' => 'Identitas supir, NIP, rekening, status kepegawaian',
                'route' => 'admin.master.employees.index',
                'ikon' => 'badge',
                'grup' => 'Master',
                'kata_kunci' => ['supir', 'driver', 'karyawan', 'pegawai', 'nip', 'rekening'],
            ],
            [
                'peran' => [UserRole::Admin],
                'label' => 'Unit Armada Truk',
                'keterangan' => 'Daftar unit, jenis truk, supir pemegang, tonase diangkut',
                'route' => 'admin.master.trucks.index',
                'ikon' => 'local_shipping',
                'grup' => 'Master',
                'kata_kunci' => ['truk', 'armada', 'unit', 'kendaraan', 'nopol', 'plat', 'jenis'],
            ],
            [
                'peran' => [UserRole::Admin],
                'label' => 'Tarif Rute & Jalur Angkutan',
                'keterangan' => 'Jarak, alamat asal-tujuan, tarif per ton, uang jalan',
                'route' => 'admin.master.routes.index',
                'ikon' => 'alt_route',
                'grup' => 'Master',
                'kata_kunci' => ['rute', 'jalur', 'tarif', 'koridor', 'jarak', 'alamat', 'material'],
            ],
            [
                'peran' => [UserRole::Admin],
                'label' => 'Operasional Ritase',
                'keterangan' => 'Input dan pantau ritase harian serta surat jalan',
                'route' => 'admin.operational.trips.index',
                'ikon' => 'route',
                'grup' => 'Operasional',
                'kata_kunci' => ['ritase', 'rit', 'trip', 'surat jalan', 'do', 'angkut', 'tonase'],
            ],
            [
                'peran' => [UserRole::Admin],
                'label' => 'Kasbon & Potongan',
                'keterangan' => 'Pengajuan kasbon, cicilan, dan potongan gaji supir',
                'route' => 'admin.operational.loans.index',
                'ikon' => 'account_balance_wallet',
                'grup' => 'Operasional',
                'kata_kunci' => ['kasbon', 'pinjam', 'pinjaman', 'cicilan', 'angsuran', 'potongan', 'hutang'],
            ],
            // [
            //     'peran' => [UserRole::Admin],
            //     'label' => 'Kalender Operasional',
            //     'keterangan' => 'Jadwal pengantaran, cut-off payroll, dan agenda armada',
            //     'route' => 'admin.operational.calendar.index',
            //     'ikon' => 'calendar_month',
            //     'grup' => 'Operasional',
            //     'kata_kunci' => ['kalender', 'jadwal', 'agenda', 'cut off', 'tanggal'],
            // ],
            [
                'peran' => [UserRole::Admin],
                'label' => 'Memo Debet Biaya Armada',
                'keterangan' => 'Pembebanan biaya sparepart dan perawatan unit',
                'route' => 'admin.operational.expenses.index',
                'ikon' => 'receipt_long',
                'grup' => 'Operasional',
                'kata_kunci' => ['memo', 'debet', 'biaya', 'sparepart', 'bengkel', 'perawatan'],
            ],
            [
                'peran' => [UserRole::Admin],
                'label' => 'Laporan & Slip Gaji',
                'keterangan' => 'Arsip periode, cetak slip, dan ekspor laporan',
                'route' => 'admin.reports.index',
                'ikon' => 'summarize',
                'grup' => 'Keuangan',
                'kata_kunci' => ['laporan', 'rekap', 'arsip', 'ekspor', 'cetak', 'pdf', 'excel'],
            ],

            // ---------- Pimpinan / Direksi ----------
            [
                'peran' => [UserRole::Pimpinan],
                'label' => 'Dashboard Eksekutif',
                'keterangan' => 'Ringkasan biaya, tren, dan antrean approval',
                'route' => 'executive.dashboard',
                'ikon' => 'analytics',
                'grup' => 'Eksekutif',
                'kata_kunci' => ['dashboard', 'ringkasan', 'analitik', 'tren', 'biaya'],
            ],
            [
                'peran' => [UserRole::Pimpinan],
                'label' => 'Review Perhitungan Gaji',
                'keterangan' => 'Setujui atau tolak payroll periode',
                'route' => 'executive.payroll.index',
                'ikon' => 'fact_check',
                'grup' => 'Eksekutif',
                'kata_kunci' => ['review', 'approval', 'acc', 'setujui', 'tolak', 'gaji', 'payroll'],
            ],
            [
                'peran' => [UserRole::Pimpinan],
                'label' => 'Laporan Finansial',
                'keterangan' => 'Rekapitulasi penggajian dan ekspor laporan',
                'route' => 'executive.reports.index',
                'ikon' => 'summarize',
                'grup' => 'Eksekutif',
                'kata_kunci' => ['laporan', 'finansial', 'rekap', 'ekspor', 'pdf'],
            ],
            // [
            //     'peran' => [UserRole::Pimpinan],
            //     'label' => 'Kalender Eksekutif',
            //     'keterangan' => 'Agenda operasional dan siklus payroll',
            //     'route' => 'executive.calendar',
            //     'ikon' => 'calendar_month',
            //     'grup' => 'Eksekutif',
            //     'kata_kunci' => ['kalender', 'agenda', 'jadwal'],
            // ],
            [
                'peran' => [UserRole::Pimpinan],
                'label' => 'Pemantauan Ritase',
                'keterangan' => 'Pantau pengangkutan dan surat jalan (read-only)',
                'route' => 'executive.trips',
                'ikon' => 'route',
                'grup' => 'Eksekutif',
                'kata_kunci' => ['ritase', 'trip', 'pantau', 'monitor', 'angkut'],
            ],
            [
                'peran' => [UserRole::Pimpinan],
                'label' => 'Profil Perusahaan',
                'keterangan' => 'Identitas perusahaan, logo resmi, rekening, dan tanda tangan pimpinan',
                'route' => 'executive.company.edit',
                'ikon' => 'business',
                'grup' => 'Eksekutif',
                'kata_kunci' => ['perusahaan', 'profil', 'identitas', 'logo', 'ttd', 'tanda tangan', 'rekening', 'bank'],
            ],

            // ---------- Supir / Karyawan ----------
            [
                'peran' => [UserRole::Supir],
                'label' => 'Slip Gaji Saya',
                'keterangan' => 'Rincian komisi, potongan, dan gaji bersih',
                'route' => 'driver.dashboard',
                'ikon' => 'payments',
                'grup' => 'Portal Supir',
                'kata_kunci' => ['gaji', 'slip', 'komisi', 'potongan', 'upah', 'payroll'],
            ],
            [
                'peran' => [UserRole::Supir],
                'label' => 'Riwayat Ritase & DO',
                'keterangan' => 'Daftar surat jalan dan tonase yang sudah diantar',
                'route' => 'driver.trips.index',
                'ikon' => 'receipt_long',
                'grup' => 'Portal Supir',
                'kata_kunci' => ['ritase', 'rit', 'surat jalan', 'do', 'tonase', 'riwayat'],
            ],
            [
                'peran' => [UserRole::Supir],
                'label' => 'Buku Kasbon',
                'keterangan' => 'Sisa pinjaman, cicilan, dan riwayat potongan',
                'route' => 'driver.loans.index',
                'ikon' => 'account_balance_wallet',
                'grup' => 'Portal Supir',
                'kata_kunci' => ['kasbon', 'pinjam', 'cicilan', 'angsuran', 'hutang'],
            ],
            [
                'peran' => [UserRole::Supir],
                'label' => 'Simulasi & Kalkulator Gaji',
                'keterangan' => 'Simulasi perhitungan take-home pay berdasarkan estimasi surat jalan',
                'route' => 'driver.simulation.index',
                'ikon' => 'calculate',
                'grup' => 'Portal Supir',
                'kata_kunci' => ['simulasi', 'kalkulator', 'hitung', 'gaji', 'target', 'potongan', 'komisi'],
            ],
            [
                'peran' => [UserRole::Supir],
                'label' => 'Profil & Armada',
                'keterangan' => 'Data pribadi, rekening payroll, dan unit yang dipegang',
                'route' => 'driver.profile.edit',
                'ikon' => 'badge',
                'grup' => 'Portal Supir',
                'kata_kunci' => ['profil', 'akun', 'rekening', 'armada', 'data pribadi', 'sim'],
            ],
        ];
    }

    /**
     * Cari halaman sesuai peran pengguna.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cariHalaman(string $kataKunci, ?UserRole $peran): array
    {
        $kunci = $this->normalisasi($kataKunci);

        if ($kunci === '') {
            return [];
        }

        return collect($this->katalogHalaman())
            ->filter(fn (array $halaman) => $peran !== null && in_array($peran, $halaman['peran'], true))
            ->map(function (array $halaman) use ($kunci): array {
                $skor = $this->hitungSkor($halaman, $kunci);

                return array_merge($halaman, [
                    'skor' => $skor,
                    'url' => route($halaman['route']),
                    'jenis' => 'halaman',
                ]);
            })
            ->filter(fn (array $halaman) => $halaman['skor'] > 0)
            ->sortByDesc('skor')
            ->values()
            ->all();
    }

    /**
     * Cari data master yang relevan dengan peran (supir, truk, rute).
     * Hasil mengarah ke halaman terkait dengan filter pencarian terisi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cariData(string $kataKunci, ?UserRole $peran): array
    {
        $kunci = trim($kataKunci);

        if ($kunci === '' || mb_strlen($kunci) < 2) {
            return [];
        }

        // Data master hanya relevan untuk admin.
        if ($peran !== UserRole::Admin) {
            return [];
        }

        $hasil = [];

        Karyawan::query()
            ->where(fn ($kueri) => $kueri
                ->where('nama_lengkap', 'like', '%'.$kunci.'%')
                ->orWhere('nomor_induk_karyawan', 'like', '%'.$kunci.'%'))
            ->limit(4)
            ->get()
            ->each(function (Karyawan $karyawan) use (&$hasil): void {
                $hasil[] = [
                    'label' => $karyawan->nama_lengkap,
                    'keterangan' => 'Supir • '.$karyawan->nomor_induk_karyawan,
                    'url' => route('admin.master.employees.index', ['cari' => $karyawan->nomor_induk_karyawan]),
                    'ikon' => 'person',
                    'grup' => 'Data Supir',
                    'jenis' => 'data',
                    'skor' => 0,
                ];
            });

        Truk::query()
            ->where(fn ($kueri) => $kueri
                ->where('nomor_polisi', 'like', '%'.$kunci.'%')
                ->orWhere('merek_model', 'like', '%'.$kunci.'%'))
            ->limit(4)
            ->get()
            ->each(function (Truk $truk) use (&$hasil): void {
                $hasil[] = [
                    'label' => $truk->nomor_polisi,
                    'keterangan' => 'Unit • '.$truk->merek_model,
                    'url' => route('admin.master.trucks.index', ['cari' => $truk->nomor_polisi]),
                    'ikon' => 'local_shipping',
                    'grup' => 'Data Armada',
                    'jenis' => 'data',
                    'skor' => 0,
                ];
            });

        Rute::query()
            ->where(fn ($kueri) => $kueri
                ->where('kode', 'like', '%'.$kunci.'%')
                ->orWhere('material', 'like', '%'.$kunci.'%'))
            ->limit(4)
            ->get()
            ->each(function (Rute $rute) use (&$hasil): void {
                $hasil[] = [
                    'label' => $rute->kode,
                    'keterangan' => 'Rute • '.$rute->material,
                    'url' => route('admin.master.routes.index', ['cari' => $rute->kode]),
                    'ikon' => 'alt_route',
                    'grup' => 'Data Rute',
                    'jenis' => 'data',
                    'skor' => 0,
                ];
            });

        return array_slice($hasil, 0, 8);
    }

    /**
     * Gabungan hasil halaman + data, halaman lebih diutamakan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cari(string $kataKunci, ?UserRole $peran): array
    {
        $halaman = $this->cariHalaman($kataKunci, $peran);
        $data = $this->cariData($kataKunci, $peran);

        return array_slice(array_merge($halaman, $data), 0, 12);
    }

    /**
     * Skor sederhana: cocok awal label > cocok sebagian label > kata kunci > keterangan.
     */
    private function hitungSkor(array $halaman, string $kunci): int
    {
        $label = $this->normalisasi($halaman['label']);
        $keterangan = $this->normalisasi($halaman['keterangan']);
        $skor = 0;

        if ($label === $kunci) {
            $skor += 100;
        } elseif (str_starts_with($label, $kunci)) {
            $skor += 70;
        } elseif (str_contains($label, $kunci)) {
            $skor += 50;
        }

        foreach ($halaman['kata_kunci'] as $kata) {
            $kata = $this->normalisasi($kata);

            if ($kata === $kunci) {
                $skor += 40;
            } elseif (str_starts_with($kata, $kunci)) {
                $skor += 25;
            } elseif (str_contains($kata, $kunci)) {
                $skor += 12;
            }
        }

        if (str_contains($keterangan, $kunci)) {
            $skor += 8;
        }

        return $skor;
    }

    private function normalisasi(string $teks): string
    {
        return mb_strtolower(trim($teks), 'UTF-8');
    }

    /**
     * Ringkasan jumlah halaman yang dapat diakses per peran (untuk debug/uji).
     *
     * @return array<string, int>
     */
    public function jumlahHalamanPerPeran(): array
    {
        $hasil = [];

        foreach (UserRole::cases() as $peran) {
            $hasil[$peran->value] = collect($this->katalogHalaman())
                ->filter(fn (array $halaman) => in_array($peran, $halaman['peran'], true))
                ->count();
        }

        return $hasil;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function katalogUntukPeran(?UserRole $peran): Collection
    {
        return collect($this->katalogHalaman())
            ->filter(fn (array $halaman) => $peran !== null && in_array($peran, $halaman['peran'], true))
            ->values();
    }
}
