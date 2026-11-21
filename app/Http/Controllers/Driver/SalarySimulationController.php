<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreSalarySimulationRequest;
use App\Models\BiayaOperasional;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\SimulasiGaji;
use App\Services\PayrollCalculationService;
use App\Services\PayrollDataService;
use App\Support\ResponInteraksiStandar;
use App\Support\SiklusPeriodeGaji;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: SIMULASI HITUNG GAJI MANDIRI SUPIR (SALARY SIMULATION CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kalkulator & Papan Coret-Coretan Target Kerja Supir".
 * Supir batubara sering membuat target pribadi:
 * "Bulan depan ada kebutuhan anak sekolah, kalau saya narik 25 rit ke Dermaga Kertapati
 * dengan rata-rata 30 ton per rit, berapa uang bersih yang akan saya kantongi?".
 * 
 * Penggunaan Mandiri:
 * Supir dapat menyimpan hasil coret-coretan ini untuk motivasi kerja harian.
 * Data di sini bersifat pribadi dan TIDAK AKAN mengubah pembukuan resmi kantor.
 * ============================================================================
 */
class SalarySimulationController extends Controller
{
    // Menggunakan trait respon interaksi standar
    use ResponInteraksiStandar;

    /**
     * Menampilkan Halaman Kalkulator Simulasi Gaji
     * 
     * Alur Kerja Bernomor:
     * (1) Pastikan akun yang login benar-benar supir tambang aktif.
     * (2) Ambil daftar rute perjalanan aktif beserta tarif per ton dan uang jalannya.
     * (3) Ambil riwayat simulasi yang pernah disimpan supir sebelumnya.
     * (4) Buka tampilan antarmuka 'driver.simulation.index'.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        abort_if($karyawan === null, 403, 'Akun Anda belum terhubung dengan data karyawan supir aktif.');

        $periodeBerjalan = $layananDataPayroll->ambilPeriodeBerjalan();
        $periode = $request->query('periode')
            ? $layananDataPayroll->ambilPeriodeBerdasarkanKode($request->query('periode'))
            : ($periodeBerjalan ?? $layananDataPayroll->ambilPeriodeTerbaru());

        // Master rute aktif untuk pilihan dropdown
        $daftarRute = Rute::query()
            ->where('status', 'aktif')
            ->orderBy('kode')
            ->get(['id', 'kode', 'material', 'tarif_per_ton', 'uang_jalan_default', 'jarak_km', 'alamat_asal', 'alamat_tujuan'])
            ->map(fn (Rute $rute): array => [
                'id'            => $rute->id,
                'nama'          => $rute->kode.($rute->material ? ' - '.$rute->material : ''),
                'tarif_per_ton' => (int) $rute->tarif_per_ton,
                'uang_jalan'    => (int) ($rute->uang_jalan_default ?? 0),
                'jarak_km'      => (int) $rute->jarak_km,
                'kota_asal'     => $rute->alamat_asal ?? '-',
                'kota_tujuan'   => $rute->alamat_tujuan ?? '-',
            ])
            ->values()
            ->all();

        // Data riil supir bulan ini untuk nilai acuan (data riil vs manual)
        $ringkasanKasbon = $layananDataPayroll->kasbonSupir($karyawan, $periode);
        $potonganKasbonRiil = (int) ($ringkasanKasbon['potongan_periode_ini'] ?? 0);
        $potonganBpjsRiil = (int) ($karyawan->potongan_bpjs ?? 0);

        // Biaya sparepart armada pegangan supir bulan ini
        $trukPegangan = $karyawan->truk()->first();
        $biayaSparepartRiil = 0;
        if ($trukPegangan !== null && $periode !== null) {
            $biayaSparepartRiil = (int) BiayaOperasional::query()
                ->where('id_truk', $trukPegangan->id)
                ->where('id_periode_gaji', $periode->id)
                ->where('status', 'disetujui')
                ->where('kategori', 'sparepart')
                ->sum('nominal');
        }

        // Ritase riil bulan ini yang sudah terverifikasi (opsi salin data cepat)
        $ritaseRiil = [];
        if ($periode !== null) {
            $ritaseRiil = Ritase::query()
                ->with('rute')
                ->where('id_karyawan', $karyawan->id)
                ->where('id_periode_gaji', $periode->id)
                ->where('status', 'terverifikasi')
                ->orderBy('tanggal_ritase')
                ->get()
                ->map(fn (Ritase $ritase): array => [
                    'rute_id' => $ritase->id_rute,
                    'nama_rute' => $ritase->rute?->kode ?? 'Rute Standar',
                    'tarif_per_ton' => (int) $ritase->tarif_per_ton,
                    'uang_jalan' => (int) $ritase->uang_jalan,
                    'tanggal' => $ritase->tanggal_ritase?->format('Y-m-d') ?? now()->toDateString(),
                ])
                ->values()
                ->all();
        }

        // Daftar riwayat simulasi yang pernah disimpan supir
        $daftarSimulasi = SimulasiGaji::query()
            ->where('id_karyawan', $karyawan->id)
            ->latest()
            ->take(10)
            ->get();

        return view('driver.simulation.index', [
            'period' => $layananDataPayroll->formatPeriode($periode),
            'profile' => $layananDataPayroll->profilSupir($request->user()),
            'daftarRute' => $daftarRute,
            'ritaseRiil' => $ritaseRiil,
            'daftarSimulasi' => $daftarSimulasi,
            'dataRiil' => [
                'bpjs' => $potonganBpjsRiil,
                'kasbon' => $potonganKasbonRiil,
                'sparepart' => $biayaSparepartRiil,
                'ada_truk' => $trukPegangan !== null,
                'nopol_truk' => $trukPegangan?->nomor_polisi ?? '-',
            ],
        ]);
    }

    public function store(
        StoreSalarySimulationRequest $request,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasi,
    ): RedirectResponse|JsonResponse {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        abort_if($karyawan === null, 403, 'Akun Anda belum terhubung dengan data karyawan supir aktif.');

        $dataValid = $request->validated();
        $daftarSuratJalanInput = $dataValid['surat_jalan'];

        $totalTonaseKg = 0;
        $totalAngkutanKotor = 0;
        $totalUangJalan = 0;
        $totalRit = 0;
        $dataSuratJalanTersimpan = [];

        foreach ($daftarSuratJalanInput as $item) {
            $tonaseKg = (int) round(((float) $item['tonase_ton']) * 1000);
            $tarifPerTon = (int) $item['tarif_per_ton'];
            $uangJalan = (int) $item['uang_jalan'];
            $angkutanKotor = (int) round(($tonaseKg * $tarifPerTon) / 1000);

            $totalRit++;
            $totalTonaseKg += $tonaseKg;
            $totalAngkutanKotor += $angkutanKotor;
            $totalUangJalan += $uangJalan;

            $dataSuratJalanTersimpan[] = [
                'rute_id' => $item['rute_id'] ?? null,
                'nama_rute' => $item['nama_rute'],
                'tonase_ton' => (float) $item['tonase_ton'],
                'tonase_kg' => $tonaseKg,
                'tarif_per_ton' => $tarifPerTon,
                'uang_jalan' => $uangJalan,
                'angkutan_kotor' => $angkutanKotor,
                'tanggal' => $item['tanggal'] ?? now()->toDateString(),
            ];
        }

        $biayaSparepart = (int) ($dataValid['biaya_sparepart'] ?? 0);
        $potonganBpjs = (int) ($dataValid['potongan_bpjs'] ?? 0);
        $potonganKasbon = (int) ($dataValid['potongan_kasbon'] ?? 0);
        $potonganLain = (int) ($dataValid['potongan_lain'] ?? 0);

        // Gunakan layanan kalkulasi resmi 4 tahap
        $hasilHitung = $layananKalkulasi->hitungKomponenGaji([
            'total_rit' => $totalRit,
            'total_tonase_kg' => $totalTonaseKg,
            'total_angkutan_kotor' => $totalAngkutanKotor,
            'potongan_operasional' => 0,
            'total_uang_jalan' => $totalUangJalan,
            'biaya_sparepart' => $biayaSparepart,
            'persentase_komisi_bp' => 1000, // Basis poin 10%
            'potongan_bpjs' => $potonganBpjs,
            'potongan_kasbon' => $potonganKasbon,
            'potongan_historis_lain' => $potonganLain,
        ]);

        $simulasi = SimulasiGaji::create([
            'id_karyawan' => $karyawan->id,
            'id_periode_gaji' => $dataValid['id_periode_gaji'] ?? null,
            'nama_simulasi' => $dataValid['nama_simulasi'],
            'data_surat_jalan' => $dataSuratJalanTersimpan,
            'total_rit' => $hasilHitung['total_rit'],
            'total_tonase_kg' => $hasilHitung['total_tonase_kg'],
            'total_angkutan_kotor' => $hasilHitung['total_angkutan_kotor'],
            'total_uang_jalan' => $hasilHitung['total_uang_jalan'],
            'biaya_sparepart' => $hasilHitung['biaya_sparepart'],
            'gaji_komisi' => $hasilHitung['gaji_komisi'],
            'potongan_bpjs' => $hasilHitung['potongan_bpjs'],
            'potongan_kasbon' => $hasilHitung['potongan_kasbon'],
            'potongan_lain' => $potonganLain,
            'gaji_bersih' => $hasilHitung['gaji_bersih'],
            'mode_potongan' => $dataValid['mode_potongan'],
            'catatan' => $dataValid['catatan'] ?? null,
        ]);

        $gajiBersihFormat = 'Rp '.number_format($simulasi->gaji_bersih, 0, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $request,
            ruteTujuan: 'driver.simulation.index',
            pesan: "Simulasi gaji \"{$simulasi->nama_simulasi}\" berhasil disimpan! Estimasi gaji bersih: {$gajiBersihFormat}.",
            judul: 'Simulasi Gaji Berhasil Disimpan',
            aksi: 'SAVE_SALARY_SIMULATION',
            dataTambahan: [
                'id' => $simulasi->id,
                'nama_simulasi' => $simulasi->nama_simulasi,
                'gaji_bersih' => $simulasi->gaji_bersih,
            ],
        );
    }

    public function destroy(
        SimulasiGaji $simulasi,
        Request $request,
        PayrollDataService $layananDataPayroll,
    ): RedirectResponse|JsonResponse {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        abort_if($karyawan === null || $simulasi->id_karyawan !== $karyawan->id, 403, 'Akses ditolak.');

        $nama = $simulasi->nama_simulasi;
        $simulasi->delete();

        return $this->responSuksesStandar(
            permintaan: $request,
            ruteTujuan: 'driver.simulation.index',
            pesan: "Simulasi gaji \"{$nama}\" berhasil dihapus.",
            judul: 'Simulasi Dihapus',
            aksi: 'DELETE_SALARY_SIMULATION',
        );
    }
}
