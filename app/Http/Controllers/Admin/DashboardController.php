<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BiayaOperasional;
use App\Models\Karyawan;
use App\Models\Ritase;
use App\Models\Truk;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: DASHBOARD UTAMA ADMIN HRD (ADMIN DASHBOARD)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Papan Informasi & Meja Kerja Harian Staf Personalia (Admin HRD)".
 * Saat admin datang ke kantor di pagi hari dan menyalakan komputer, layar inilah yang pertama kali dilihat.
 * Halaman ini menjawab pertanyaan: "Apa saja tugas dan pekerjaan saya hari ini?".
 * 
 * Informasi yang Disajikan:
 * 1. Jumlah supir yang aktif dan truk yang menganggur/tanpa supir.
 * 2. Daftar surat jalan ritase yang menunggu diperiksa fotonya.
 * 3. Berapa kasbon baru yang diajukan supir dan perlu diproses.
 * 4. Peringatan dini jika ada pengeluaran sparepart yang terlalu besar.
 * ============================================================================
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan Beranda Dashboard Admin
     * 
     * Alur Kerja Bernomor:
     * (1) Cari tahu buku periode gajian mana yang dipilih (atau ambil periode bulan terbaru).
     * (2) Hitung ringkasan gaji, kasbon supir, dan status armada kendaraan.
     * (3) Ambil 5 aktivitas surat jalan pengiriman batubara yang paling baru.
     * (4) Susun antrean tugas apa saja yang mendesak untuk segera dikerjakan admin.
     * (5) Buka antarmuka Blade 'admin.dashboard' beserta seluruh data tersebut.
     */
    public function index(Request $permintaan, PayrollDataService $layananDataPayroll): View
    {
        // (1) Tentukan periode gaji aktif
        $periode = $layananDataPayroll->ambilPeriodeTerbaru();
        $kodePeriode = $permintaan->query('periode');

        if (is_string($kodePeriode) && $kodePeriode !== '') {
            $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $periode;
        }

        // (2) Kumpulkan ringkasan angka statistik
        $ringkasanPayroll = $layananDataPayroll->ringkasanPayroll($periode);

        // (3), (4), (5) Kirim seluruh paket data ke tampilan layar
        return view('admin.dashboard', [
            'period'               => $layananDataPayroll->formatPeriode($periode),
            'periodOptions'        => $layananDataPayroll->daftarOpsiPeriode(),
            'ringkasanPayroll'     => $ringkasanPayroll,
            'ringkasanKasbon'      => $layananDataPayroll->ringkasanKasbon($layananDataPayroll->daftarKasbon()),
            'ringkasanArmada'      => $this->ringkasanArmada(),
            'ringkasanPengeluaran' => $this->ringkasanPengeluaran($periode?->id),
            'aktivitasTerbaru'     => $this->aktivitasTerbaru(),
        ]);
    }

    /**
     * Menghitung Ringkasan Status Unit Truk & Supir
     * Mengetahui berapa truk yang sedang jalan, rusak/nonaktif, atau belum ada supirnya.
     *
     * @return array<string, mixed>
     */
    private function ringkasanArmada(): array
    {
        // (1) Hitung kondisi unit truk di garasi
        $agregat = Truk::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif")
            ->selectRaw("SUM(CASE WHEN status <> 'aktif' THEN 1 ELSE 0 END) as nonaktif")
            ->selectRaw('SUM(CASE WHEN id_karyawan IS NULL THEN 1 ELSE 0 END) as tanpa_supir')
            ->first();

        // (2) Hitung jumlah supir
        $totalSupir = Karyawan::query()->count();
        $supirAktif = Karyawan::query()->where('status', 'aktif')->count();

        // (3) Supir aktif yang belum dipasangkan dengan unit truk fisik mana pun
        $supirTanpaUnit = Karyawan::query()
            ->where('status', 'aktif')
            ->whereDoesntHave('truk')
            ->count();

        // (4) Kembalikan rincian angka bulat
        return [
            'total'            => (int) ($agregat->total ?? 0),
            'aktif'            => (int) ($agregat->aktif ?? 0),
            'nonaktif'         => (int) ($agregat->nonaktif ?? 0),
            'tanpa_supir'      => (int) ($agregat->tanpa_supir ?? 0),
            'supir_total'      => $totalSupir,
            'supir_aktif'      => $supirAktif,
            'supir_nonaktif'   => max(0, $totalSupir - $supirAktif),
            'supir_tanpa_unit' => $supirTanpaUnit,
        ];
    }

    /**
     * Menghitung Total Pengeluaran Bon Operasional Armada
     * Menghitung total kuitansi belanja solar, ban, dan sparepart pada bulan berjalan.
     *
     * @return array<string, mixed>
     */
    private function ringkasanPengeluaran(?int $idPeriode): array
    {
        $kueri = BiayaOperasional::query();

        // Batasi hanya untuk periode bulan yang sedang dipilih
        if ($idPeriode !== null) {
            $kueri->where('id_periode_gaji', $idPeriode);
        }

        $agregat = $kueri
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw('COALESCE(SUM(nominal), 0) as total')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
            ->first();

        return [
            'jumlah' => (int) ($agregat->jumlah ?? 0), // Berapa lembar nota yang masuk
            'total'  => (int) ($agregat->total ?? 0),  // Total rupiah semua nota
            'draft'  => (int) ($agregat->draft ?? 0),  // Jumlah nota yang belum diverifikasi
        ];
    }

    /**
     * Mengambil 5 Riwayat Pengantaran Ritase Paling Baru
     * Berguna sebagai monitor siaran langsung agar admin tahu pergerakan truk di lapangan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function aktivitasTerbaru(): array
    {
        return Ritase::query()
            ->with(['karyawan:id,nama_lengkap,nomor_induk_karyawan', 'truk:id,nomor_polisi', 'rute:id,kode'])
            ->orderByDesc('tanggal_ritase')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Ritase $ritase) => [
                'tanggal' => $ritase->tanggal_ritase?->format('d/m/Y') ?? '—',
                'supir'   => $ritase->karyawan?->nama_lengkap ?? 'TANPA SUPIR',
                'nip'     => $ritase->karyawan?->nomor_induk_karyawan ?? '—',
                'plat'    => $ritase->truk?->nomor_polisi ?? '—',
                'rute'    => $ritase->rute?->kode ?? '—',
                'tonase'  => number_format(((int) $ritase->tonase_kg) / 1000, 2, ',', '.'),
                'status'  => $ritase->status,
            ])
            ->all();
    }
}
