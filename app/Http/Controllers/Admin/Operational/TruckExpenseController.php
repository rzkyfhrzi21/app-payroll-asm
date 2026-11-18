<?php

namespace App\Http\Controllers\Admin\Operational;

use App\Http\Controllers\Concerns\MengaturPaginasiTabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBiayaOperasionalRequest;
use App\Http\Requests\UpdateBiayaOperasionalRequest;
use App\Models\BiayaOperasional;
use App\Services\PayrollDataService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: BIAYA OPERASIONAL & BON BENGKEL (TRUCK EXPENSE CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kotak Arsip Bon Bengkel & Kuitansi Pengeluaran Truk".
 * Saat truk batubara beroperasi, timbul biaya lapangan seperti pengisian BBM solar,
 * ganti ban pecah, belanja onderdil (sparepart), struk tol, hingga retribusi resmi.
 * 
 * Pengaruh ke Slip Gaji:
 * Nota biaya kategori 'sparepart' yang disetujui akan diperhitungkan sebagai pengurang
 * hasil bersih armada truk sebelum dihitung persentase komisi bagi hasil supir.
 * ============================================================================
 */
class TruckExpenseController extends Controller
{
    // Menggunakan trait paginasi tabel dan standar notifikasi toast
    use MengaturPaginasiTabel, ResponInteraksiStandar;

    /**
     * Menampilkan Daftar Pengeluaran Bon Operasional Armada
     * 
     * Alur Kerja Bernomor:
     * (1) Tentukan periode bulan penggajian aktif.
     * (2) Tangkap filter pencarian nomor nota, kategori pengeluaran, status, dan unit truk.
     * (3) Ambil daftar pengeluaran biaya terpaginasi dari database.
     * (4) Buka tampilan antarmuka 'admin.operational.expenses.index'.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $kodePeriode = $request->query('periode');
        $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode(is_string($kodePeriode) ? $kodePeriode : null)
            ?? $layananDataPayroll->ambilPeriodeTerbaru();

        $perHalaman = $this->ambilJumlahBarisPerHalaman($request);
        $filter = [
            'periode'  => $periode?->kode ?? 'all',
            'cari'     => $this->ambilKataKunciPencarian($request),
            'kategori' => $this->ambilFilterTeks($request, 'kategori'),
            'status'   => $this->ambilFilterTeks($request, 'status'),
            'unit'     => $this->ambilFilterTeks($request, 'unit'),
        ];

        return view('admin.operational.expenses.index', [
            'rows'            => $layananDataPayroll->barisBiayaOperasionalTerpaginasi($periode, $filter, $perHalaman),
            'summary'         => $layananDataPayroll->ringkasanBiayaOperasionalPeriode($periode),
            'period'          => $layananDataPayroll->formatPeriode($periode),
            'periodOptions'   => $layananDataPayroll->daftarOpsiPeriode(),
            'trucks'          => $layananDataPayroll->pilihanTrukBiaya(),
            'unitOptions'     => $layananDataPayroll->pilihanUnitBiaya($periode),
            'kategoriOptions' => BiayaOperasional::daftarKategori(),
            'statusOptions'   => BiayaOperasional::daftarStatus(),
            'periodeAktifId'  => $periode?->id,
            'filter'          => $filter,
            'perPage'         => $perHalaman,
        ]);
    }

    public function store(
        StoreBiayaOperasionalRequest $permintaan,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|JsonResponse {
        $data = $permintaan->validated();
        if ($permintaan->hasFile('foto_nota')) {
            $data['foto_nota'] = $layananMediaGambar->validasiDanSimpanWebp(
                $permintaan->file('foto_nota'),
                'nota-biaya',
                'foto_nota'
            );
        }
        $biaya = BiayaOperasional::create($data);
        $tujuan = match ($permintaan->input('sumber')) {
            'loans' => 'admin.operational.loans.index',
            'trips' => 'admin.operational.trips.index',
            default => 'admin.operational.expenses.index',
        };
        $nominalFormatted = 'Rp '.number_format((int) $biaya->nominal, 0, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: $tujuan,
            pesan: "Biaya operasional {$biaya->kategori} sebesar {$nominalFormatted} berhasil disimpan.",
            judul: 'Biaya Operasional Dicatat',
            aksi: 'CREATE_BIAYA_OPERASIONAL',
            dataTambahan: [
                'id' => $biaya->id,
                'kategori' => $biaya->kategori,
                'nominal' => $biaya->nominal,
            ],
        );
    }

    public function update(
        UpdateBiayaOperasionalRequest $permintaan,
        BiayaOperasional $expense,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|JsonResponse {
        $data = $permintaan->validated();
        if ($permintaan->hasFile('foto_nota')) {
            $data['foto_nota'] = $layananMediaGambar->validasiDanSimpanWebp(
                $permintaan->file('foto_nota'),
                'nota-biaya',
                'foto_nota'
            );
        }
        $expense->update($data);
        $nominalFormatted = 'Rp '.number_format((int) $expense->nominal, 0, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.expenses.index',
            pesan: "Data biaya operasional {$expense->kategori} ({$nominalFormatted}) berhasil diperbarui.",
            judul: 'Biaya Operasional Diperbarui',
            aksi: 'UPDATE_BIAYA_OPERASIONAL',
            dataTambahan: [
                'id' => $expense->id,
                'kategori' => $expense->kategori,
                'nominal' => $expense->nominal,
            ],
        );
    }

    /**
     * Biaya yang sudah disetujui tidak boleh dihapus.
     */
    public function destroy(Request $permintaan, BiayaOperasional $expense): RedirectResponse|JsonResponse
    {
        if ($expense->status === 'disetujui') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.operational.expenses.index',
                pesan: 'Biaya yang sudah disetujui tidak dapat dihapus agar rekap pengajuan tetap utuh.',
                judul: 'Penghapusan Biaya Ditolak',
                aksi: 'DELETE_BIAYA_LOCKED',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $kategoriDihapus = $expense->kategori;
        $nominalDihapus = 'Rp '.number_format((int) $expense->nominal, 0, ',', '.');
        $expense->delete();

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.expenses.index',
            pesan: "Data biaya operasional {$kategoriDihapus} ({$nominalDihapus}) berhasil dihapus.",
            judul: 'Biaya Operasional Dihapus',
            aksi: 'DELETE_BIAYA_OPERASIONAL',
        );
    }

    /**
     * Mengubah status banyak biaya operasional sekaligus dari checkbox tabel.
     */
    public function statusMassal(Request $permintaan): RedirectResponse|JsonResponse
    {
        $data = $permintaan->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:biaya_operasional,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(BiayaOperasional::daftarStatus()))],
        ]);

        $jumlahBaris = BiayaOperasional::query()->whereIn('id', $data['ids'])->update(['status' => $data['status']]);
        $labelStatus = BiayaOperasional::daftarStatus()[$data['status']] ?? $data['status'];

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.expenses.index',
            pesan: "Status {$jumlahBaris} pengeluaran berhasil diubah menjadi {$labelStatus}.",
            judul: 'Status Pengeluaran Massal Diperbarui',
            aksi: 'BULK_STATUS_BIAYA_OPERASIONAL',
            dataTambahan: ['jumlah' => $jumlahBaris, 'status' => $data['status']],
        );
    }
}
