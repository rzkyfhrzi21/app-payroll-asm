<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Ritase;
use App\Services\PayrollDataService;
use App\Services\StandarMediaGambarService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: TUGAS PENGANTARAN & KONFIRMASI SURAT JALAN (DRIVER TRIP CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Papan Tugas Pengemudi & Loket Serah Terima Surat Jalan Lapangan".
 * Saat admin kantor menerbitkan surat jalan pengiriman batubara, tugas tersebut
 * muncul di ponsel supir.
 * 
 * Alur Kerja Supir di Lapangan:
 * 1. Mulai Kirim        : Supir menekan tombol "Mulai Kirim" saat roda truk mulai berputar (status: 'sedang_dikirim').
 * 2. Tiba di Tujuan     : Saat tiba di pelabuhan/dermaga, supir menekan "Konfirmasi Sampai".
 * 3. Unggah Bukti Fisik : Supir wajib memotret surat jalan fisik yang sudah ada cap stempel tanda tangan penerima,
 *    mengisi nama penerima barang, lalu statusnya berubah menjadi 'menunggu_verifikasi' admin.
 * ============================================================================
 */
class TripHistoryController extends Controller
{
    // Menggunakan trait respon interaksi standar
    use ResponInteraksiStandar;

    /**
     * Menampilkan Daftar Tugas Surat Jalan Supir
     * 
     * Alur Kerja Bernomor:
     * (1) Identifikasi data supir dari akun pengguna aktif.
     * (2) Cari tahu buku periode bulan penggajian aktif.
     * (3) Ambil daftar perjalanan ritase yang ditugaskan kepada supir ini.
     * (4) Buka tampilan antarmuka 'driver.trips.index'.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        $periodeBerjalan = $layananDataPayroll->ambilPeriodeBerjalan();
        $kodePeriode = $request->query('periode');
        $periode = (is_string($kodePeriode) && trim($kodePeriode) !== '')
            ? ($layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $periodeBerjalan ?? $layananDataPayroll->ambilPeriodeTerbaru())
            : ($periodeBerjalan ?? $layananDataPayroll->ambilPeriodeTerbaru());
        $penggajian = $karyawan === null
            ? null
            : $layananDataPayroll->ambilPenggajianSupir($karyawan, $periode);

        return view('driver.trips.index', [
            'period'         => $layananDataPayroll->formatPeriode($periode),
            'period_options' => $layananDataPayroll->daftarOpsiPeriode(),
            'profile'        => $layananDataPayroll->profilSupir($request->user()),
            'payroll'        => $layananDataPayroll->payrollSupir($penggajian, $karyawan),
            'trips'          => $layananDataPayroll->ritaseSupir($karyawan, $periode),
        ]);
    }

    /**
     * Menandai Surat Jalan Mulai Berangkat / Dikirim oleh Supir
     * Mengubah status dari 'terbit' menjadi 'sedang_dikirim'.
     */
    public function mulaiPengiriman(
        Request $permintaan,
        Ritase $trip,
        PayrollDataService $layananDataPayroll,
    ): RedirectResponse|JsonResponse {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($permintaan->user());
        $trip->load(['rute', 'periodeGaji']);

        if ($karyawan === null || (int) $trip->id_karyawan !== (int) $karyawan->id) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.trips.index',
                pesan: 'Anda tidak memiliki akses untuk memulai pengiriman surat jalan milik pengemudi lain.',
                judul: 'Akses Ditolak',
                aksi: 'START_DELIVERY_FORBIDDEN',
                statusKode: 'START_DELIVERY_FORBIDDEN',
                kodeHttp: 403,
            );
        }

        $periode = $trip->periodeGaji;
        $periodeTerkunci = $periode !== null
            && in_array($periode->status, ['approved', 'paid', 'disetujui', 'dibayar'], true);

        if ($periodeTerkunci) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.trips.index',
                pesan: "Periode {$periode->kode} sudah difinalisasi/dibayar sehingga status surat jalan tidak dapat diubah lagi.",
                judul: 'Periode Sudah Terkunci',
                aksi: 'START_DELIVERY_LOCKED',
                statusKode: 'START_DELIVERY_LOCKED',
                kodeHttp: 422,
            );
        }

        $statusSaatIni = (string) ($trip->status ?: 'draft');

        if ($statusSaatIni === 'draft') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.trips.index',
                pesan: 'Surat jalan masih berstatus draf dan belum diterbitkan oleh Admin.',
                judul: 'Surat Jalan Belum Diterbitkan',
                aksi: 'START_DELIVERY_NOT_PUBLISHED',
                statusKode: 'START_DELIVERY_NOT_PUBLISHED',
                kodeHttp: 403,
            );
        }

        if ($statusSaatIni !== 'terbit') {
            $pesanStatus = match ($statusSaatIni) {
                'sedang_dikirim' => 'Surat jalan ini sudah berstatus Sedang Dikirim. Silakan lanjutkan konfirmasi sampai setelah barang tiba di tujuan.',
                'menunggu_verifikasi' => 'Surat jalan ini sudah dikonfirmasi sampai dan sedang menunggu verifikasi Admin.',
                'terverifikasi' => 'Surat jalan ini sudah terverifikasi oleh Admin dan tidak dapat dimulai ulang.',
                'dibatalkan' => 'Surat jalan ini sudah dibatalkan sehingga pengiriman tidak dapat dimulai.',
                default => 'Status surat jalan saat ini tidak mengizinkan aksi Mulai Kirim.',
            };

            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.trips.index',
                pesan: $pesanStatus,
                judul: 'Transisi Status Tidak Valid',
                aksi: 'START_DELIVERY_INVALID_STATUS',
                statusKode: 'INVALID_STATUS_TRANSITION',
                kodeHttp: 422,
            );
        }

        DB::transaction(function () use ($trip): void {
            $trip->update(['status' => 'sedang_dikirim']);
        });

        $trip->refresh();
        $kodeRute = $trip->rute?->kode ?? 'RUTE';
        $nomorSj = sprintf('SJ-%s-%05d', $kodeRute, $trip->id);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'driver.trips.index',
            pesan: "Pengiriman surat jalan {$nomorSj} (Rute {$kodeRute}) dimulai. Status diperbarui menjadi Sedang Dikirim.",
            judul: 'Pengiriman Dimulai',
            aksi: 'START_DELIVERY_TRIP',
            dataTambahan: [
                'id' => $trip->id,
                'nomor_surat_jalan' => $nomorSj,
                'status' => 'sedang_dikirim',
            ],
        );
    }

    /**
     * Konfirmasi barang berhasil diantarkan oleh supir berdasarkan surat jalan.
     * Mengizinkan supir mengunggah foto bukti surat jalan / tiket timbangan,
     * menyesuaikan tonase timbangan, dan menambahkan catatan pengantaran.
     */
    public function konfirmasi(
        Request $permintaan,
        Ritase $trip,
        StandarMediaGambarService $layananMediaGambar,
        PayrollDataService $layananDataPayroll,
    ): RedirectResponse|JsonResponse {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($permintaan->user());
        $trip->load(['rute', 'periodeGaji']);

        if ($karyawan === null || (int) $trip->id_karyawan !== (int) $karyawan->id) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.trips.index',
                pesan: 'Anda tidak memiliki akses untuk mengonfirmasi surat jalan milik pengemudi lain.',
                judul: 'Akses Ditolak',
                aksi: 'CONFIRM_TRIP_FORBIDDEN',
                statusKode: 'FORBIDDEN',
                kodeHttp: 403,
            );
        }

        $periode = $trip->periodeGaji;
        $periodeTerkunci = $periode !== null
            && in_array($periode->status, ['approved', 'paid', 'disetujui', 'dibayar'], true);

        if ($periodeTerkunci) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.trips.index',
                pesan: "Periode {$periode->kode} sudah difinalisasi/dibayar sehingga data surat jalan tidak dapat diubah lagi.",
                judul: 'Periode Sudah Terkunci',
                aksi: 'CONFIRM_TRIP_LOCKED',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $statusSaatIni = (string) ($trip->status ?: 'draft');

        if ($statusSaatIni === 'draft') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.trips.index',
                pesan: 'Surat jalan masih berstatus draf dan belum diterbitkan oleh Admin.',
                judul: 'Surat Jalan Belum Diterbitkan',
                aksi: 'CONFIRM_TRIP_NOT_PUBLISHED',
                statusKode: 'CONFIRM_TRIP_NOT_PUBLISHED',
                kodeHttp: 403,
            );
        }

        if (! in_array($statusSaatIni, ['terbit', 'sedang_dikirim', 'menunggu_verifikasi'], true)) {
            $pesanStatus = match ($statusSaatIni) {
                'terverifikasi' => 'Surat jalan ini sudah terverifikasi oleh Admin dan tidak dapat dikonfirmasi ulang.',
                'dibatalkan' => 'Surat jalan ini sudah dibatalkan sehingga pengantaran tidak dapat dikonfirmasi.',
                default => 'Status surat jalan saat ini tidak mengizinkan konfirmasi pengantaran.',
            };

            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.trips.index',
                pesan: $pesanStatus,
                judul: 'Status Surat Jalan Sudah Final',
                aksi: 'CONFIRM_TRIP_INVALID_STATUS',
                statusKode: 'INVALID_STATUS_TRANSITION',
                kodeHttp: 422,
            );
        }

        $tonaseMentah = str_replace('.', '', trim((string) $permintaan->input('tonase_ton', '')));
        $tonaseMentah = str_replace(',', '.', $tonaseMentah);
        if (is_numeric($tonaseMentah)) {
            $permintaan->merge(['tonase_ton' => (float) $tonaseMentah]);
        }

        $fotoLama = is_array($trip->foto_surat_jalan) ? $trip->foto_surat_jalan : [];
        $hapusFotoMasuk = $permintaan->input('hapus_foto', []);
        $daftarHapus = is_array($hapusFotoMasuk)
            ? array_values(array_filter($hapusFotoMasuk, fn (mixed $item): bool => is_string($item) && trim($item) !== ''))
            : [];
        $fotoTersisa = array_values(array_filter(
            $fotoLama,
            fn (string $jalur): bool => ! in_array($jalur, $daftarHapus, true)
        ));

        $berkasFoto = $permintaan->file('foto_surat_jalan');
        $adalahArrayFoto = is_array($berkasFoto) || is_array($permintaan->input('foto_surat_jalan'));

        $aturanFoto = $adalahArrayFoto
            ? [
                'foto_surat_jalan' => [
                    Rule::requiredIf(fn (): bool => $fotoTersisa === []),
                    'nullable',
                    'array',
                    'max:10',
                ],
                'foto_surat_jalan.*' => ['file', 'image', 'max:2048'],
            ]
            : [
                'foto_surat_jalan' => [
                    Rule::requiredIf(fn (): bool => $fotoTersisa === []),
                    'nullable',
                    'file',
                    'image',
                    'max:2048',
                ],
            ];

        $tervalidasi = $permintaan->validate(array_merge([
            'tonase_ton' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'nama_penerima' => ['required', 'string', 'max:120'],
            'catatan_pengantaran' => ['nullable', 'string', 'max:255'],
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['string', 'max:255'],
        ], $aturanFoto), [
            'tonase_ton.required' => 'Tonase timbangan wajib diisi.',
            'tonase_ton.numeric' => 'Tonase timbangan harus berupa angka.',
            'tonase_ton.min' => 'Tonase minimal 0,01 ton.',
            'tonase_ton.max' => 'Tonase maksimal 100 ton.',
            'nama_penerima.required' => 'Nama penerima barang wajib diisi.',
            'nama_penerima.max' => 'Nama penerima maksimal 120 karakter.',
            'catatan_pengantaran.max' => 'Catatan pengantaran maksimal 255 karakter.',
            'foto_surat_jalan.required' => 'Foto bukti surat jalan / TTD penerima wajib diunggah.',
            'foto_surat_jalan.array' => 'Format unggahan foto bukti surat jalan tidak valid.',
            'foto_surat_jalan.max' => $adalahArrayFoto
                ? 'Maksimal 10 foto bukti surat jalan yang dapat diunggah sekaligus.'
                : 'Ukuran foto bukti surat jalan maksimal 2 MB.',
            'foto_surat_jalan.file' => 'Bukti surat jalan harus berupa berkas gambar yang valid.',
            'foto_surat_jalan.image' => 'Bukti surat jalan harus berupa gambar (JPG, PNG, atau WEBP).',
            'foto_surat_jalan.*.file' => 'Setiap foto bukti surat jalan harus berupa berkas gambar yang valid.',
            'foto_surat_jalan.*.image' => 'Setiap foto bukti harus berupa gambar (JPG, PNG, atau WEBP).',
            'foto_surat_jalan.*.max' => 'Ukuran setiap foto bukti surat jalan maksimal 2 MB.',
        ]);

        $fotoBaru = $layananMediaGambar->simpanBanyakWebp(
            $berkasFoto,
            'surat-jalan',
            'foto_surat_jalan'
        );

        $daftarFotoAkhir = array_values(array_unique(array_merge($fotoTersisa, $fotoBaru)));

        if ($daftarFotoAkhir === []) {
            throw ValidationException::withMessages([
                'foto_surat_jalan' => ['Minimal 1 foto bukti surat jalan / TTD penerima harus tersimpan.'],
            ]);
        }

        $tonaseKgBaru = (int) round(((float) $tervalidasi['tonase_ton']) * 1000);
        $tarifPerTon = (int) $trip->tarif_per_ton;
        $totalAngkutanBaru = intdiv($tonaseKgBaru * $tarifPerTon + 500, 1000);

        $atributPerbarui = [
            'tonase_kg' => $tonaseKgBaru,
            'total_angkutan' => $totalAngkutanBaru,
            'nama_penerima' => $tervalidasi['nama_penerima'],
            'catatan_pengantaran' => $tervalidasi['catatan_pengantaran'] ?? $trip->catatan_pengantaran,
            'foto_surat_jalan' => $daftarFotoAkhir,
            'status' => 'menunggu_verifikasi',
        ];

        DB::transaction(function () use ($trip, $atributPerbarui): void {
            $trip->update($atributPerbarui);
        });

        $trip->refresh();
        $nomorSj = sprintf('SJ-%s-%05d', $trip->rute?->kode ?? 'RUTE', $trip->id);
        $tonaseFormatted = number_format($tonaseKgBaru / 1000, 2, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'driver.trips.index',
            pesan: "Konfirmasi pengantaran surat jalan {$nomorSj} ({$tonaseFormatted} Ton, diterima oleh {$trip->nama_penerima}) berhasil disimpan dan menunggu verifikasi Admin.",
            judul: 'Pengantaran Menunggu Verifikasi Admin',
            aksi: 'CONFIRM_DELIVERY_TRIP',
            dataTambahan: [
                'id' => $trip->id,
                'nomor_surat_jalan' => $nomorSj,
                'tonase_kg' => $tonaseKgBaru,
                'nama_penerima' => $trip->nama_penerima,
                'status' => $trip->status,
            ],
        );
    }
}
