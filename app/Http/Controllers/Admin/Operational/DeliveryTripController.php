<?php

namespace App\Http\Controllers\Admin\Operational;

use App\Http\Controllers\Concerns\MengaturPaginasiTabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRitaseRequest;
use App\Http\Requests\UpdateRitaseRequest;
use App\Models\Ritase;
use App\Services\ExcelPayrollMigrationService;
use App\Services\PayrollDataService;
use App\Services\StandarMediaGambarService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ============================================================================
 * CONTROLLER: OPERASIONAL SURAT JALAN & RITASE (DELIVERY TRIP CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Loket Penugasan Pengiriman & Pemeriksaan Surat Jalan Fisik".
 * Di loket inilah admin membagikan surat jalan (ritase) kepada supir batubara.
 * Ketika supir kembali membawa foto surat jalan bertanda tangan dari pelabuhan,
 * admin mencocokkan berat tonase timbangan dan memeriksa cap tanda tangan basah,
 * lalu menandai statusnya menjadi 'terverifikasi'.
 * 
 * Syarat Masuk Gaji:
 * Hanya surat jalan yang lolos verifikasi di loket ini yang boleh dihitung
 * uang angkutannya ke dalam slip gaji bulanan supir!
 * ============================================================================
 */
class DeliveryTripController extends Controller
{
    // Menggunakan trait paginasi tabel dan standar notifikasi toast
    use MengaturPaginasiTabel, ResponInteraksiStandar;

    /**
     * Menampilkan Daftar Perjalanan Pengiriman Surat Jalan
     * 
     * Alur Kerja Bernomor:
     * (1) Tentukan periode bulan penggajian aktif.
     * (2) Tangkap saringan filter (supir, rute, status verifikasi, rentang tanggal).
     * (3) Ambil daftar ritase terpaginasi dari database.
     * (4) Buka tampilan 'admin.operational.trips.index'.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $kodePeriode = $request->query('periode');
        $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode(is_string($kodePeriode) ? $kodePeriode : null)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();
        $perHalaman = $this->ambilJumlahBarisPerHalaman($request);
        $filter = [
            'periode'         => $periode?->kode ?? 'all',
            'cari'            => $this->ambilKataKunciPencarian($request),
            'supir'           => $this->ambilFilterTeks($request, 'supir'),
            'rute'            => $this->ambilFilterTeks($request, 'rute'),
            'jenis'           => $this->ambilFilterTeks($request, 'jenis'),
            'status'          => $this->ambilFilterTeks($request, 'status'),
            'tanggal_dari'    => $this->ambilFilterTanggal($request, 'tanggal_dari'),
            'tanggal_sampai'  => $this->ambilFilterTanggal($request, 'tanggal_sampai'),
        ];

        return view('admin.operational.trips.index', [
            'rows'               => $layananDataPayroll->barisRitaseOperasionalTerpaginasi($periode, $filter, $perHalaman),
            'summary'            => $layananDataPayroll->ringkasanRitasePeriode($periode),
            'summaryKeseluruhan' => $layananDataPayroll->ringkasanRitaseKeseluruhan(),
            'period'             => $layananDataPayroll->formatPeriode($periode),
            'periodOptions'      => $layananDataPayroll->daftarOpsiPeriode(),
            'drivers'            => $layananDataPayroll->pilihanSupirRitase(),
            'trucks'             => $layananDataPayroll->pilihanTrukBiaya(),
            'routes'             => $layananDataPayroll->pilihanRuteRitase(),
            'jenisTrukOptions'   => $layananDataPayroll->pilihanJenisTrukRitase(),
            'statusOptions' => Ritase::daftarStatus(),
            'periodeAktifId' => $periode?->id,
            'filter' => $filter,
            'perPage' => $perHalaman,
        ]);
    }

    public function store(
        StoreRitaseRequest $permintaan,
        PayrollDataService $layananDataPayroll,
        StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|JsonResponse {
        $data = $permintaan->validated();
        $ritase = $layananDataPayroll->buatRitase($data);
        if ($permintaan->hasFile('foto_surat_jalan')) {
            $daftarFoto = $this->susunDaftarFotoSuratJalan($permintaan, [], $layananMediaGambar);
            if ($daftarFoto !== []) {
                $ritase->update(['foto_surat_jalan' => $daftarFoto]);
            }
        }
        $ritase->load(['karyawan', 'rute', 'periodeGaji']);
        $tanggalFormatted = $ritase->tanggal_ritase?->format('d/m/Y') ?? '-';
        $namaSupir = $ritase->karyawan?->nama_lengkap ?? '-';
        $nomorSj = $ritase->nomor_surat_jalan;
        $parameterPeriode = $ritase->periodeGaji?->kode ? ['periode' => $ritase->periodeGaji->kode] : [];

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.trips.index',
            pesan: "Surat jalan {$nomorSj} ({$tanggalFormatted}) berhasil dibuat untuk supir {$namaSupir}.",
            judul: 'Surat Jalan Berhasil Dibuat',
            aksi: 'CREATE_RITASE',
            dataTambahan: [
                'id' => $ritase->id,
                'nomor_surat_jalan' => $nomorSj,
                'nama_supir' => $namaSupir,
                'url_cetak' => route('admin.operational.trips.print', $ritase->id),
            ],
            parameterRute: $parameterPeriode,
        );
    }
    public function cetak(Ritase $trip): View
    {
        $trip->load(['karyawan', 'karyawan.pengguna', 'truk', 'truk.jenisTruk', 'rute', 'periodeGaji']);

        return view('admin.operational.trips.print', [
            'trip' => $trip,
            'periode' => $trip->periodeGaji,
            'karyawan' => $trip->karyawan,
            'truk' => $trip->truk,
            'rute' => $trip->rute,
        ]);
    }


    public function update(
        UpdateRitaseRequest $permintaan,
        Ritase $trip,
        PayrollDataService $layananDataPayroll,
        StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|JsonResponse {
        $layananDataPayroll->perbaruiRitase($trip, $permintaan->validated());
        $fotoLama = is_array($trip->foto_surat_jalan) ? $trip->foto_surat_jalan : [];
        $hapusFoto = $permintaan->input('hapus_foto', []);
        $adaPenghapusan = is_array($hapusFoto) && $hapusFoto !== [];

        if ($permintaan->hasFile('foto_surat_jalan') || $adaPenghapusan) {
            $daftarFoto = $this->susunDaftarFotoSuratJalan($permintaan, $fotoLama, $layananMediaGambar);
            $trip->update([
                'foto_surat_jalan' => $daftarFoto === [] ? null : $daftarFoto,
            ]);
        }
        $trip->refresh()->load(['karyawan', 'rute', 'periodeGaji']);
        $nomorSj = $trip->nomor_surat_jalan;
        $parameterPeriode = $trip->periodeGaji?->kode ? ['periode' => $trip->periodeGaji->kode] : [];

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.trips.index',
            pesan: "Data surat jalan '{$nomorSj}' ({$trip->karyawan?->nama_lengkap}) berhasil diperbarui.",
            judul: 'Surat Jalan Diperbarui',
            aksi: 'UPDATE_RITASE',
            dataTambahan: [
                'id' => $trip->id,
                'nomor_surat_jalan' => $nomorSj,
            ],
            parameterRute: $parameterPeriode,
        );
    }

    /**
     * Ritase pada periode yang sudah disetujui/dibayar tidak boleh dihapus.
     */
    public function destroy(Request $permintaan, Ritase $trip): RedirectResponse|JsonResponse
    {
        $trip->load(['rute', 'periodeGaji']);
        $nomorSj = $trip->nomor_surat_jalan;
        $parameterPeriode = $trip->periodeGaji?->kode ? ['periode' => $trip->periodeGaji->kode] : [];

        $periodeTerkunci = $trip->periodeGaji !== null
            && in_array($trip->periodeGaji->status, ['approved', 'paid'], true);

        if ($periodeTerkunci) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.operational.trips.index',
                pesan: "Surat jalan '{$nomorSj}' berada pada periode {$trip->periodeGaji->kode} yang sudah difinalisasi/dibayar. Data tidak dapat dihapus.",
                judul: 'Penghapusan Surat Jalan Ditolak',
                aksi: 'DELETE_RITASE_LOCKED',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $trip->delete();

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.trips.index',
            pesan: "Surat jalan '{$nomorSj}' berhasil dihapus dari database.",
            judul: 'Surat Jalan Dihapus',
            aksi: 'DELETE_RITASE',
            dataTambahan: ['nomor_surat_jalan' => $nomorSj],
            parameterRute: $parameterPeriode,
        );
    }

    /**
     * Mengubah status banyak surat jalan sekaligus dari checkbox tabel.
     */
    public function statusMassal(Request $permintaan): RedirectResponse|JsonResponse
    {
        $data = $permintaan->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:ritase,id'],
            'status' => ['required', 'in:draft,terbit,sedang_dikirim,menunggu_verifikasi,terverifikasi,dibatalkan'],
        ]);

        $jumlahBaris = Ritase::query()->whereIn('id', $data['ids'])->update(['status' => $data['status']]);
        app(PayrollDataService::class)->bersihkanSinggahan();
        $daftarStatus = array_merge([
            'draft' => 'Draf (Belum Terbit)',
            'terbit' => 'Terbit (Siap Dikirim Supir)',
            'sedang_dikirim' => 'Sedang Dikirim',
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'terverifikasi' => 'Terverifikasi',
            'dibatalkan' => 'Dibatalkan',
        ], Ritase::daftarStatus());
        $labelStatus = $daftarStatus[$data['status']] ?? $data['status'];

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.trips.index',
            pesan: "Status {$jumlahBaris} surat jalan berhasil diubah menjadi {$labelStatus}.",
            judul: 'Status Surat Jalan Massal Diperbarui',
            aksi: 'BULK_STATUS_RITASE',
            dataTambahan: ['jumlah' => $jumlahBaris, 'status' => $data['status']],
        );
    }

    public function export(Request $request, ExcelPayrollMigrationService $layananMigrasi): StreamedResponse
    {
        $kodePeriode = is_string($request->query('periode')) && $request->query('periode') !== ''
            ? $request->query('periode')
            : 'all';

        return $layananMigrasi->eksporFormatMigrasi($kodePeriode);
    }

    /**
     * Menyusun daftar path foto surat jalan akhir dari foto lama (dikurangi hapus_foto)
     * dan foto baru yang dikonversi ke .webp.
     *
     * @param  array<int, string>  $fotoLama
     * @return array<int, string>
     */
    private function susunDaftarFotoSuratJalan(
        Request $permintaan,
        array $fotoLama,
        StandarMediaGambarService $layananMediaGambar
    ): array {
        $hapusFotoMasuk = $permintaan->input('hapus_foto', []);
        $daftarHapus = is_array($hapusFotoMasuk)
            ? array_values(array_filter($hapusFotoMasuk, fn (mixed $item): bool => is_string($item) && trim($item) !== ''))
            : [];

        $fotoTersisa = array_values(array_filter(
            $fotoLama,
            fn (string $jalur): bool => ! in_array($jalur, $daftarHapus, true)
        ));

        $fotoBaru = $layananMediaGambar->simpanBanyakWebp(
            $permintaan->file('foto_surat_jalan'),
            'surat-jalan',
            'foto_surat_jalan'
        );

        return array_values(array_unique(array_merge($fotoTersisa, $fotoBaru)));
    }
}
