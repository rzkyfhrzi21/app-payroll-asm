<?php

namespace App\Http\Controllers\Admin\Operational;

use App\Http\Controllers\Concerns\MengaturPaginasiTabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKasbonRequest;
use App\Http\Requests\UpdateKasbonRequest;
use App\Models\BiayaOperasional;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\Penggajian;
use App\Services\PayrollCalculationService;
use App\Services\PayrollDataService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class EmployeeFinanceController extends Controller
{
    use MengaturPaginasiTabel, ResponInteraksiStandar;

    /**
     * Halaman Kasbon & Potongan.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $periodeAktif = $layananDataPayroll->ambilPeriodeTerbaru();
        $perHalaman = $this->ambilJumlahBarisPerHalaman($request);
        $filter = [
            'cari' => $this->ambilKataKunciPencarian($request),
            'supir' => $this->ambilFilterTeks($request, 'supir'),
            'status' => $this->ambilFilterTeks($request, 'status'),
            'tanggal_dari' => $this->ambilFilterTanggal($request, 'tanggal_dari'),
            'tanggal_sampai' => $this->ambilFilterTanggal($request, 'tanggal_sampai'),
        ];

        return view('admin.operational.loans.index', [
            'rows' => $layananDataPayroll->daftarKasbonTerpaginasi($filter, $perHalaman),
            'summary' => $layananDataPayroll->ringkasanKasbon($layananDataPayroll->daftarKasbon()),
            'period' => $layananDataPayroll->formatPeriode($periodeAktif),
            'drivers' => $layananDataPayroll->daftarSupirAktif(),
            'daftarKomponenSupir' => $layananDataPayroll->dataKomisiDanPotongan(),
            'periodeOptions' => $layananDataPayroll->daftarOpsiPeriode(),
            'periodeAktifId' => $periodeAktif?->id,
            'trucks' => $layananDataPayroll->pilihanTrukBiaya(),
            'kategoriOptions' => BiayaOperasional::daftarKategori(),
            'statusOptionsBiaya' => BiayaOperasional::daftarStatus(),
            'pilihanSupirKasbon' => $layananDataPayroll->pilihanSupirKasbon(),
            'filter' => $filter,
            'perPage' => $perHalaman,
        ]);
    }

    /**
     * Menyimpan pengajuan kasbon baru beserta jadwal cicilannya.
     */
    public function storeLoan(StoreKasbonRequest $permintaan, PayrollDataService $layananDataPayroll): RedirectResponse|JsonResponse
    {
        $data = $permintaan->validated();

        $kasbon = DB::transaction(
            fn () => $layananDataPayroll->buatKasbon($data)
        );
        $namaSupir = $kasbon->karyawan?->nama_lengkap ?? 'Supir';
        $nominalFormatted = 'Rp '.number_format((int) $kasbon->total_pinjaman, 0, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.loans.index',
            pesan: "Kasbon {$namaSupir} sebesar {$nominalFormatted} berhasil dibuat dengan {$kasbon->jumlah_tenor} kali cicilan.",
            judul: 'Kasbon Baru Disimpan',
            aksi: 'CREATE_KASBON',
            dataTambahan: [
                'id' => $kasbon->id,
                'nama_supir' => $namaSupir,
                'total_pinjaman' => $kasbon->total_pinjaman,
                'jumlah_tenor' => $kasbon->jumlah_tenor,
            ],
        );
    }

    public function updateLoan(UpdateKasbonRequest $permintaan, Kasbon $loan, PayrollDataService $layananDataPayroll): RedirectResponse|JsonResponse
    {
        $layananDataPayroll->perbaruiKasbon($loan, $permintaan->validated());
        $namaSupir = $loan->karyawan?->nama_lengkap ?? 'Supir';

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.loans.index',
            pesan: "Data kasbon supir '{$namaSupir}' berhasil diperbarui.",
            judul: 'Data Kasbon Diperbarui',
            aksi: 'UPDATE_KASBON',
            dataTambahan: ['id' => $loan->id],
        );
    }

    /**
     * Mengubah status banyak kasbon sekaligus dari checkbox tabel.
     */
    public function statusMassalLoan(Request $permintaan): RedirectResponse|JsonResponse
    {
        $data = $permintaan->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:kasbon,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(Kasbon::daftarStatus()))],
        ]);

        $jumlahBaris = Kasbon::query()->whereIn('id', $data['ids'])->update(['status' => $data['status']]);
        $labelStatus = Kasbon::daftarStatus()[$data['status']] ?? $data['status'];

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.loans.index',
            pesan: "Status {$jumlahBaris} kasbon berhasil diubah menjadi {$labelStatus}.",
            judul: 'Status Kasbon Massal Diperbarui',
            aksi: 'BULK_STATUS_KASBON',
            dataTambahan: ['jumlah' => $jumlahBaris, 'status' => $data['status']],
        );
    }

    public function destroyLoan(Request $permintaan, Kasbon $loan): RedirectResponse|JsonResponse
    {
        if ($loan->angsuran()->where('status', 'terpotong')->exists()) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.operational.loans.index',
                pesan: 'Kasbon ini sudah memiliki cicilan terpotong. Data tidak dapat dihapus.',
                judul: 'Penghapusan Kasbon Ditolak',
                aksi: 'DELETE_KASBON_LOCKED',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $namaSupir = $loan->karyawan?->nama_lengkap ?? 'Supir';
        $loan->angsuran()->delete();
        $loan->delete();

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.loans.index',
            pesan: "Data kasbon supir '{$namaSupir}' berhasil dihapus dari database.",
            judul: 'Data Kasbon Dihapus',
            aksi: 'DELETE_KASBON',
        );
    }

    /**
     * Menandai satu cicilan sudah dipotong dari slip gaji.
     */
    public function potongAngsuran(Request $permintaan, Kasbon $loan, int $angsuran, PayrollDataService $layananDataPayroll): RedirectResponse|JsonResponse
    {
        $layananDataPayroll->potongAngsuranKasbon($loan, $angsuran);
        $namaSupir = $loan->karyawan?->nama_lengkap ?? 'Supir';

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.loans.index',
            pesan: "Cicilan ke-{$angsuran} kasbon '{$namaSupir}' berhasil ditandai sudah terpotong dari slip gaji.",
            judul: 'Cicilan Kasbon Terpotong',
            aksi: 'POTONG_ANGSURAN_KASBON',
            dataTambahan: [
                'id_kasbon' => $loan->id,
                'urutan_angsuran' => $angsuran,
            ],
        );
    }

    public function storeBpjs(Request $permintaan, PayrollDataService $layananDataPayroll, PayrollCalculationService $layananKalkulasi): RedirectResponse|JsonResponse
    {
        $idKaryawan = (int) $permintaan->input('id_karyawan');
        $nominalBpjs = (int) preg_replace('/\D/', '', (string) $permintaan->input('potongan_bpjs_bulanan', 0));

        $karyawan = Karyawan::query()->find($idKaryawan);
        if ($karyawan === null) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.operational.loans.index',
                pesan: 'Data supir tidak ditemukan.',
                judul: 'Gagal Menyimpan BPJS',
                aksi: 'UPDATE_BPJS_NOT_FOUND',
            );
        }

        $karyawan->update(['potongan_bpjs_bulanan' => $nominalBpjs]);

        $periodeAktif = $layananDataPayroll->ambilPeriodeTerbaru();
        if ($periodeAktif !== null) {
            $penggajian = Penggajian::query()
                ->where('id_periode_gaji', $periodeAktif->id)
                ->where('id_karyawan', $karyawan->id)
                ->first();
            if ($penggajian !== null) {
                $layananKalkulasi->perbaruiKomponenSupir($penggajian, [
                    'potongan_bpjs' => $nominalBpjs,
                ]);
            }
        }

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.loans.index',
            pesan: "Potongan BPJS bulanan {$karyawan->nama_lengkap} berhasil diperbarui menjadi Rp ".number_format($nominalBpjs, 0, ',', '.').'.',
            judul: 'Potongan BPJS Diperbarui',
            aksi: 'UPDATE_BPJS_SUPIR',
        );
    }

    /**
     * Menyimpan potongan BPJS untuk banyak supir sekaligus (checkbox massal).
     */
    public function storeBpjsMassal(
        Request $permintaan,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasi,
    ): RedirectResponse|JsonResponse {
        $data = $permintaan->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:karyawan,id'],
            'potongan_bpjs_bulanan' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $periodeAktif = $layananDataPayroll->ambilPeriodeTerbaru();
        $nominalBpjs = (int) $data['potongan_bpjs_bulanan'];

        DB::transaction(function () use ($data, $nominalBpjs, $periodeAktif, $layananKalkulasi): void {
            $daftarKaryawan = Karyawan::query()->whereIn('id', $data['ids'])->get();

            foreach ($daftarKaryawan as $karyawan) {
                $karyawan->update(['potongan_bpjs_bulanan' => $nominalBpjs]);

                if ($periodeAktif === null) {
                    continue;
                }

                $penggajian = Penggajian::query()
                    ->where('id_periode_gaji', $periodeAktif->id)
                    ->where('id_karyawan', $karyawan->id)
                    ->first();

                if ($penggajian !== null) {
                    $layananKalkulasi->perbaruiKomponenSupir($penggajian, [
                        'potongan_bpjs' => $nominalBpjs,
                    ]);
                }
            }
        });

        $jumlahTerpilih = count($data['ids']);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.loans.index',
            pesan: "Potongan BPJS {$jumlahTerpilih} supir berhasil diubah menjadi Rp ".number_format($nominalBpjs, 0, ',', '.').'.',
            judul: 'Potongan BPJS Massal Disimpan',
            aksi: 'BULK_BPJS_SUPIR',
            dataTambahan: ['jumlah' => $jumlahTerpilih, 'potongan_bpjs_bulanan' => $nominalBpjs],
        );
    }

    public function storeAllowance(
        Request $permintaan,
        PayrollDataService $layananDataPayroll,
        PayrollCalculationService $layananKalkulasi,
    ): RedirectResponse|JsonResponse {
        $nominalBersih = (int) preg_replace('/\D/', '', (string) $permintaan->input('nominal_tunjangan', 0));
        $modeTunjangan = (string) $permintaan->input('mode_tunjangan', 'set');

        $validator = Validator::make([
            'id_karyawan' => $permintaan->input('id_karyawan'),
            'id_periode_gaji' => $permintaan->input('id_periode_gaji'),
            'nominal_tunjangan' => $nominalBersih,
            'jenis_tunjangan' => $permintaan->input('jenis_tunjangan'),
            'mode_tunjangan' => $modeTunjangan,
        ], [
            'id_karyawan' => ['required', 'integer', 'exists:karyawan,id'],
            'id_periode_gaji' => ['nullable', 'integer', 'exists:periode_gaji,id'],
            'nominal_tunjangan' => ['required', 'integer', 'min:0', 'max:100000000'],
            'jenis_tunjangan' => ['nullable', 'string', 'max:100'],
            'mode_tunjangan' => ['nullable', 'in:tambah,set'],
        ], [
            'id_karyawan.required' => 'Supir penerima tunjangan wajib dipilih.',
            'nominal_tunjangan.required' => 'Nominal tunjangan/bonus wajib diisi.',
        ]);

        if ($validator->fails()) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.operational.loans.index',
                pesan: $validator->errors()->first(),
                judul: 'Validasi Tunjangan Gagal',
                aksi: 'STORE_ALLOWANCE_INVALID',
                daftarGalat: $validator->errors()->toArray(),
            );
        }

        $karyawan = Karyawan::query()->findOrFail((int) $permintaan->input('id_karyawan'));
        $periode = $permintaan->filled('id_periode_gaji')
            ? \App\Models\PeriodeGaji::query()->find((int) $permintaan->input('id_periode_gaji'))
            : $layananDataPayroll->ambilPeriodeTerbaru();

        if ($periode === null) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.operational.loans.index',
                pesan: 'Periode penggajian aktif tidak ditemukan.',
                judul: 'Gagal Menyimpan Tunjangan',
                aksi: 'STORE_ALLOWANCE_NO_PERIOD',
            );
        }

        $penggajian = Penggajian::query()
            ->where('id_periode_gaji', $periode->id)
            ->where('id_karyawan', $karyawan->id)
            ->first();

        if ($penggajian === null) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.operational.loans.index',
                pesan: "Rekap penggajian untuk supir {$karyawan->nama_lengkap} pada periode {$periode->kode} belum dibuat.",
                judul: 'Baris Penggajian Belum Ada',
                aksi: 'STORE_ALLOWANCE_NO_PAYROLL',
            );
        }

        $totalBaru = $modeTunjangan === 'tambah'
            ? ((int) $penggajian->total_tunjangan + $nominalBersih)
            : $nominalBersih;

        $penggajianTerbaru = $layananKalkulasi->perbaruiKomponenSupir($penggajian, [
            'total_tunjangan' => $totalBaru,
        ]);

        $nominalFormatted = 'Rp '.number_format((int) $penggajianTerbaru->total_tunjangan, 0, ',', '.');
        $gajiBersihFormatted = 'Rp '.number_format((int) $penggajianTerbaru->gaji_bersih, 0, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.operational.loans.index',
            pesan: "Tunjangan/bonus {$karyawan->nama_lengkap} pada periode {$periode->kode} berhasil disimpan menjadi {$nominalFormatted} (Gaji Bersih: {$gajiBersihFormatted}).",
            judul: 'Tunjangan Supir Disimpan',
            aksi: 'STORE_ALLOWANCE_SUPIR',
            dataTambahan: [
                'id_karyawan' => $karyawan->id,
                'total_tunjangan' => (int) $penggajianTerbaru->total_tunjangan,
                'gaji_bersih' => (int) $penggajianTerbaru->gaji_bersih,
            ],
        );
    }

    public function storeDebitMemo(Request $request): RedirectResponse
    {
        return back()->with('status', 'Simulasi simpan memo debet. Belum ada data yang ditulis ke database.');
    }
}
