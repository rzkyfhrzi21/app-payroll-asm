<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\MengaturPaginasiTabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubmitPayrollApprovalRequest;
use App\Http\Requests\Admin\UpdatePayrollDriverRequest;
use App\Models\Penggajian;
use App\Services\PayrollCalculationService;
use App\Services\PayrollDataService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    use MengaturPaginasiTabel, ResponInteraksiStandar;

    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $kodePeriode = $request->query('periode');
        $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode(is_string($kodePeriode) ? $kodePeriode : null)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        $perHalaman = $this->ambilJumlahBarisPerHalaman($request);
        $filter = [
            'cari' => $this->ambilKataKunciPencarian($request),
            'fleet' => $this->ambilFilterTeks($request, 'fleet'),
        ];

        return view('admin.payroll.index', [
            'period' => $layananDataPayroll->formatPeriode($periode),
            'summary' => $layananDataPayroll->ringkasanPayroll($periode),
            'rows' => $layananDataPayroll->barisPayrollTerpaginasi($periode, $filter, $perHalaman),
            'periodOptions' => $layananDataPayroll->daftarOpsiPeriode(),
            'filter' => $filter,
            'perPage' => $perHalaman,
        ]);
    }
    public function buatPeriodeBaru(Request $permintaan): RedirectResponse|JsonResponse
    {
        $tanggalInput = $permintaan->input('tanggal', now()->toDateString());
        $tanggalAcuan = \Illuminate\Support\Carbon::parse((string) $tanggalInput);
        $rentang = \App\Support\SiklusPeriodeGaji::rentangBulan($tanggalAcuan);
        $kode = \App\Support\SiklusPeriodeGaji::kodeDariTanggal($tanggalAcuan);

        $periode = \App\Models\PeriodeGaji::query()->firstOrCreate(
            ['kode' => $kode],
            [
                'tanggal_mulai' => $rentang['tanggal_mulai'],
                'tanggal_selesai' => $rentang['tanggal_selesai'],
                'tanggal_bayar' => $tanggalAcuan->copy()->endOfMonth()->addDays(5)->toDateString(),
                'status' => 'draft',
            ]
        );

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.payroll.index',
            pesan: "Periode baru {$periode->kode} ({$rentang['tanggal_mulai']} s/d {$rentang['tanggal_selesai']}) berhasil dibuat dan siap digunakan.",
            judul: 'Periode Baru Dibuat',
            aksi: 'CREATE_PERIODE_GAJI',
            dataTambahan: ['kode' => $periode->kode, 'id' => $periode->id],
            parameterRute: ['periode' => $periode->kode],
        );
    }


    public function calculate(
        Request $request,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasiPayroll
    ): JsonResponse|RedirectResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Periode Tidak Ditemukan',
                pesan: "Periode penggajian \"{$period}\" tidak ditemukan di database.",
                aksi: 'calculate',
                kodeHttp: 404,
            );
        }

        $ringkasan = $layananKalkulasiPayroll->rekonsiliasiPeriode($periodeModel);
        $totalBersihLabel = 'Rp '.number_format($ringkasan['total_gaji_bersih'], 0, ',', '.');

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Kalkulasi Ulang Gaji Selesai',
            pesan: "Rekonsiliasi periode {$periodeModel->kode} berhasil ({$ringkasan['jumlah_supir']} supir, {$ringkasan['total_rit']} rit, total gaji bersih {$totalBersihLabel}).",
            aksi: 'calculate',
            data: $ringkasan,
            redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
        );
    }

    public function approval(
        SubmitPayrollApprovalRequest $request,
        string $period,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasiPayroll
    ): JsonResponse|RedirectResponse {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Periode Tidak Ditemukan',
                pesan: "Periode penggajian \"{$period}\" tidak ditemukan.",
                aksi: 'approval',
                kodeHttp: 404,
            );
        }

        $tervalidasi = $request->validated();
        $alasan = trim((string) ($tervalidasi['alasan_penolakan'] ?? ''));

        // Sesuai ACTIVITY_DIAGRAM.drawio: ACC hanya milik Pimpinan/Direksi.
        // Admin/HRD hanya boleh menarik pengajuan (kembali ke draft).
        $periodeDiperbarui = $layananKalkulasiPayroll->prosesPersetujuanPeriode($periodeModel, 'reject');

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Catatan Revisi Dikirim',
            pesan: "Penggajian periode {$periodeDiperbarui->kode} dikembalikan ke status Draf untuk revisi: \"{$alasan}\".",
            aksi: 'reject',
            data: ['kode' => $periodeDiperbarui->kode, 'status' => $periodeDiperbarui->status],
            redirectUrl: route('admin.payroll.index', ['periode' => $periodeDiperbarui->kode]),
        );
    }

    public function updateDriver(
        UpdatePayrollDriverRequest $request,
        string $period,
        Penggajian $penggajian,
        PayrollCalculationService $layananKalkulasiPayroll
    ): JsonResponse|RedirectResponse {
        $penggajian->loadMissing(['karyawan', 'periodeGaji', 'truk']);
        $kodePeriode = $penggajian->periodeGaji?->kode ?? $period;
        $namaSupir = $penggajian->karyawan?->nama_lengkap ?? 'Supir';
        $nopol = $penggajian->truk?->nomor_polisi ?? '-';

        $diperbarui = $layananKalkulasiPayroll->perbaruiKomponenSupir($penggajian, $request->validated());
        $gajiBersihLabel = 'Rp '.number_format((int) $diperbarui->gaji_bersih, 0, ',', '.');

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Komponen Gaji Supir Diperbarui',
            pesan: "Rincian gaji supir \"{$namaSupir}\" ({$nopol}) pada periode {$kodePeriode} berhasil disimpan (Net Pay: {$gajiBersihLabel}).",
            aksi: 'update',
            data: [
                'id_penggajian' => $diperbarui->id,
                'gaji_bersih' => (int) $diperbarui->gaji_bersih,
                'gaji_komisi' => (int) $diperbarui->gaji_komisi,
                'hasil_bersih_armada' => (int) $diperbarui->hasil_bersih_armada,
            ],
            redirectUrl: route('admin.payroll.index', ['periode' => $kodePeriode]),
        );
    }

    public function destroyDriver(
        Request $request,
        string $period,
        Penggajian $penggajian
    ): JsonResponse|RedirectResponse {
        $penggajian->loadMissing(['karyawan', 'periodeGaji', 'truk']);
        $periodeModel = $penggajian->periodeGaji;
        $kodePeriode = $periodeModel?->kode ?? $period;
        $namaSupir = $penggajian->karyawan?->nama_lengkap ?? 'Supir';
        $nopol = $penggajian->truk?->nomor_polisi ?? '-';

        if ($periodeModel !== null && in_array($periodeModel->status, ['approved', 'paid'], true)) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Periode Sudah Terkunci',
                pesan: "Baris gaji \"{$namaSupir}\" ({$nopol}) tidak dapat dikeluarkan karena periode {$kodePeriode} sudah berstatus disetujui/dibayar.",
                aksi: 'delete',
                kodeHttp: 422,
            );
        }

        $jumlahRitase = $periodeModel
            ? $periodeModel->ritase()->where('id_karyawan', $penggajian->id_karyawan)->count()
            : 0;

        if ($jumlahRitase > 0) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Supir Memiliki Ritase Aktif',
                pesan: "Supir \"{$namaSupir}\" ({$nopol}) tidak dapat dihapus dari periode {$kodePeriode} karena masih mengikat {$jumlahRitase} surat jalan ritase terverifikasi.",
                aksi: 'delete',
                kodeHttp: 422,
            );
        }

        $penggajian->delete();

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Baris Gaji Dikeluarkan',
            pesan: "Supir \"{$namaSupir}\" ({$nopol}) berhasil dikeluarkan dari perhitungan gaji periode {$kodePeriode}.",
            aksi: 'delete',
            redirectUrl: route('admin.payroll.index', ['periode' => $kodePeriode]),
        );
    }

    public function submit(Request $request, string $period, PayrollDataService $layananDataPayroll): JsonResponse|RedirectResponse
    {
        $periodeModel = $layananDataPayroll->ambilPeriodeBerdasarkanKode($period)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periodeModel === null) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Periode Tidak Ditemukan',
                pesan: "Periode penggajian \"{$period}\" tidak ditemukan.",
                aksi: 'submit',
                kodeHttp: 404,
            );
        }

        $jumlahSjBelumVerifikasi = $periodeModel->ritase()
            ->whereIn('status', ['draft', 'terbit', 'sedang_dikirim', 'menunggu_verifikasi'])
            ->count();

        if ($jumlahSjBelumVerifikasi > 0) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Pengajuan Payroll Ditolak',
                pesan: "Masih ada {$jumlahSjBelumVerifikasi} surat jalan pada periode {$periodeModel->kode} yang belum diverifikasi (Draf / Terbit / Sedang Dikirim / Menunggu Verifikasi). Selesaikan verifikasi seluruh surat jalan terlebih dahulu.",
                aksi: 'submit',
                statusKode: 'UNVERIFIED_TRIPS',
                kodeHttp: 422,
                redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
            );
        }

        $jumlahBarisGaji = $periodeModel->penggajian()->count();

        if ($jumlahBarisGaji === 0) {
            return ResponInteraksiStandar::gagal(
                request: $request,
                judul: 'Data Gaji Belum Dihitung',
                pesan: "Periode {$periodeModel->kode} belum memiliki baris gaji supir. Jalankan Hitung Gaji terlebih dahulu sebelum mengajukan ke Pimpinan.",
                aksi: 'submit',
                statusKode: 'EMPTY_PAYROLL',
                kodeHttp: 422,
                redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
            );
        }

        $periodeModel->update([
            'status' => 'pending',
            'catatan_penolakan' => null,
        ]);

        return ResponInteraksiStandar::sukses(
            request: $request,
            judul: 'Pengajuan Payroll Dikirim',
            pesan: "Penggajian periode {$periodeModel->kode} berhasil diajukan ke Pimpinan untuk persetujuan.",
            aksi: 'submit',
            redirectUrl: route('admin.payroll.index', ['periode' => $periodeModel->kode]),
        );
    }

    public function import(Request $request, string $period): RedirectResponse
    {
        return redirect()->route('admin.payroll.index', ['periode' => $period]);
    }

    public function export(Request $request, string $period): RedirectResponse
    {
        return redirect()->route('admin.reports.periode.export', ['periode' => $period]);
    }
}
