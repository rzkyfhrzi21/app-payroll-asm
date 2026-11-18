<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\PayrollDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: BERANDA UTAMA PORTAL SUPIR (DRIVER DASHBOARD CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Buku Catatan Saku Digital Pribadi Milik Supir".
 * Ketika supir membuka aplikasi di ponselnya, layar ini langsung memperlihatkan
 * pencapaian kerjanya di bulan ini: berapa kali sudah narik ritase batubara,
 * berapa total tonase yang berhasil diantar, berapa uang jalan yang sudah diambil,
 * dan perkiraan slip gaji yang akan cair.
 * 
 * Aturan Penerbitan Slip:
 * Tombol cetak slip gaji fisik HANYA BISA DIAKSES jika periode bulan tersebut
 * sudah resmi disetujui (ACC / 'approved') oleh Pimpinan/Direktur!
 * ============================================================================
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan Beranda Mandiri Supir
     * 
     * Alur Kerja Bernomor:
     * (1) Identifikasi profil supir dari akun login yang sedang aktif.
     * (2) Cari tahu buku periode bulan berjalan.
     * (3) Ambil data lembaran slip gaji supir dan riwayat surat jalannya.
     * (4) Buka tampilan antarmuka Blade 'driver.dashboard'.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        // (1) Ambil biodata supir
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());

        // (2) Tentukan periode yang dipilih (utamakan periode yang masih berjalan)
        $periodeBerjalan = $layananDataPayroll->ambilPeriodeBerjalan();
        $kodePeriode = $request->query('periode');
        $periode = (is_string($kodePeriode) && trim($kodePeriode) !== '')
            ? ($layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $periodeBerjalan ?? $layananDataPayroll->ambilPeriodeTerbaru())
            : ($periodeBerjalan ?? $layananDataPayroll->ambilPeriodeTerbaru());

        // (3) Ambil draf gaji supir
        $penggajian = $karyawan === null
            ? null
            : $layananDataPayroll->ambilPenggajianSupir($karyawan, $periode);

        // (4) Render tampilan dasbor supir
        return view('driver.dashboard', [
            'period'   => $layananDataPayroll->formatPeriode($periode),
            'profile'  => $layananDataPayroll->profilSupir($request->user()),
            'payroll'  => $layananDataPayroll->payrollSupir($penggajian, $karyawan),
            'trips'    => $layananDataPayroll->ritaseSupir($karyawan, $periode),
        ]);
    }

    /**
     * Mencetak Lembaran Slip Gaji Resmi Milik Supir
     * 
     * Alur Kerja Bernomor:
     * (1) Dapatkan data supir dan lembar slip gajinya.
     * (2) Pastikan periode gajian supir SUDAH DI-ACC oleh Pimpinan (jika belum, tolak akses 403).
     * (3) Buka tampilan cetak slip gaji 'admin.reports.slip'.
     */
    public function cetakSlip(Request $request, PayrollDataService $layananDataPayroll): View
    {
        // (1) Ambil biodata supir dan slip gaji
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        $penggajian = $karyawan === null
            ? null
            : $layananDataPayroll->ambilPenggajianSupir($karyawan);
        $periode = $penggajian?->periodeGaji ?? $layananDataPayroll->ambilPeriodeTerbaru();

        // (2) Kunci keamanan: Jangan izinkan cetak jika belum di-ACC pimpinan
        abort_if(
            $periode === null || ! in_array($periode->status, ['approved', 'paid', 'disetujui', 'dibayar'], true),
            403,
            'Slip gaji supir belum diterbitkan. Slip gaji hanya dapat diakses setelah periode disetujui Pimpinan.'
        );
        abort_if($penggajian === null, 404, 'Data penggajian supir belum tersedia pada periode ini.');

        // (3) Ambil rincian data cetak
        $dataRincian = $layananDataPayroll->rincianLaporanPeriode($periode);
        $dataSupir = collect($dataRincian['daftar_supir'])
            ->firstWhere('id_penggajian', $penggajian->id)
            ?? collect($dataRincian['daftar_supir'])->first();

        abort_if($dataSupir === null, 404, 'Data rincian slip gaji supir tidak ditemukan.');

        return view('admin.reports.slip', [
            'periode' => $dataRincian['periode'],
            'supir'   => $dataSupir,
        ]);
    }
}

