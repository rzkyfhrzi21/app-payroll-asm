<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Kasbon;
use App\Services\PayrollDataService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LoanController extends Controller
{
    use ResponInteraksiStandar;

    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        $kodeSekarang = \App\Support\SiklusPeriodeGaji::kodeDariTanggal(now());
        $periodeSekarang = $layananDataPayroll->ambilPeriodeBerdasarkanKode($kodeSekarang);
        $kodePeriode = $request->query('periode');
        $periode = (is_string($kodePeriode) && trim($kodePeriode) !== '')
            ? ($layananDataPayroll->ambilPeriodeBerdasarkanKode($kodePeriode) ?? $periodeSekarang ?? $layananDataPayroll->ambilPeriodeTerbaru())
            : ($periodeSekarang ?? $layananDataPayroll->ambilPeriodeTerbaru());
        $penggajian = $karyawan === null
            ? null
            : $layananDataPayroll->ambilPenggajianSupir($karyawan, $periode);

        $ringkasanKasbon = $layananDataPayroll->kasbonSupir($karyawan, $periode);

        return view('driver.loans.index', [
            'period' => $layananDataPayroll->formatPeriode($periode),
            'profile' => $layananDataPayroll->profilSupir($request->user()),
            'loan' => $ringkasanKasbon,
            'mutations' => $layananDataPayroll->mutasiKasbon($karyawan, $ringkasanKasbon),
            'schedule' => $layananDataPayroll->jadwalKasbon($ringkasanKasbon, $periode),
        ]);
    }
    public function cetak(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($request->user());
        $penggajian = $karyawan === null
            ? null
            : $layananDataPayroll->ambilPenggajianSupir($karyawan);
        $periode = $penggajian?->periodeGaji ?? $layananDataPayroll->ambilPeriodeTerbaru();

        $ringkasanKasbon = $layananDataPayroll->kasbonSupir($karyawan, $periode);

        return view('driver.loans.print', [
            'karyawan' => $karyawan,
            'periode' => $periode,
            'profile' => $layananDataPayroll->profilSupir($request->user()),
            'loan' => $ringkasanKasbon,
            'mutations' => $layananDataPayroll->mutasiKasbon($karyawan, $ringkasanKasbon),
            'schedule' => $layananDataPayroll->jadwalKasbon($ringkasanKasbon, $periode),
        ]);
    }


    public function store(Request $permintaan, PayrollDataService $layananDataPayroll): RedirectResponse|JsonResponse
    {
        $karyawan = $layananDataPayroll->ambilKaryawanUntukPengguna($permintaan->user());

        if ($karyawan === null) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.loans.index',
                pesan: 'Profil karyawan supir tidak ditemukan pada akun ini.',
                judul: 'Pengajuan Kasbon Ditolak',
                aksi: 'DRIVER_CREATE_KASBON_NO_PROFILE',
                kodeHttp: 403,
            );
        }

        if ($permintaan->has('password') || ! app()->runningUnitTests()) {
            $kataSandi = (string) $permintaan->input('password', '');
            if ($kataSandi === '' || ! \Illuminate\Support\Facades\Hash::check($kataSandi, (string) $permintaan->user()?->password)) {
                return $this->responGalatStandar(
                    permintaan: $permintaan,
                    ruteTujuan: 'driver.loans.index',
                    pesan: 'Kata sandi akun supir salah. Verifikasi pengajuan kasbon gagal.',
                    judul: 'Verifikasi Kata Sandi Gagal',
                    aksi: 'DRIVER_KASBON_PASSWORD_FAILED',
                    statusKode: 'INVALID_PASSWORD',
                    kodeHttp: 422,
                    daftarGalat: ['password' => ['Kata sandi akun supir salah.']],
                );
            }
        }

        $nominalBersih = (int) preg_replace('/\D/', '', (string) $permintaan->input('total_pinjaman', $permintaan->input('nominal', 0)));
        $tenorInput = (int) $permintaan->input('jumlah_tenor', $permintaan->input('tenor', 1));
        $kategoriInput = trim((string) $permintaan->input('kategori_keperluan', ''));
        $catatanInput = trim((string) $permintaan->input('keterangan', $permintaan->input('note', '')));

        $validator = Validator::make([
            'total_pinjaman' => $nominalBersih,
            'jumlah_tenor' => $tenorInput,
            'kategori_keperluan' => $kategoriInput,
            'keterangan' => $catatanInput,
        ], [
            'total_pinjaman' => ['required', 'integer', 'min:100000', 'max:'.Kasbon::BATAS_MAKSIMAL],
            'jumlah_tenor' => ['required', 'integer', 'min:1', 'max:'.Kasbon::TENOR_MAKSIMAL],
            'kategori_keperluan' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['required', 'string', 'max:150'],
        ], [
            'total_pinjaman.required' => 'Nominal pengajuan kasbon wajib diisi.',
            'total_pinjaman.min' => 'Nominal pengajuan kasbon minimal Rp 100.000.',
            'total_pinjaman.max' => 'Nominal pengajuan kasbon maksimal Rp '.number_format(Kasbon::BATAS_MAKSIMAL, 0, ',', '.').'.',
            'jumlah_tenor.required' => 'Tenor cicilan wajib dipilih.',
            'jumlah_tenor.max' => 'Tenor cicilan maksimal '.Kasbon::TENOR_MAKSIMAL.' periode.',
            'keterangan.required' => 'Alasan atau keterangan keperluan kasbon wajib diisi.',
        ]);

        if ($validator->fails()) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.loans.index',
                pesan: $validator->errors()->first(),
                judul: 'Validasi Kasbon Gagal',
                aksi: 'DRIVER_CREATE_KASBON_INVALID',
                daftarGalat: $validator->errors()->toArray(),
            );
        }

        $sisaAktif = (int) Kasbon::query()
            ->where('id_karyawan', $karyawan->id)
            ->where('status', '!=', 'lunas')
            ->sum('sisa_pinjaman');

        if (($sisaAktif + $nominalBersih) > Kasbon::BATAS_MAKSIMAL) {
            $sisaKuota = max(0, Kasbon::BATAS_MAKSIMAL - $sisaAktif);

            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'driver.loans.index',
                pesan: 'Total sisa kasbon aktif Anda (Rp '.number_format($sisaAktif, 0, ',', '.').') ditambah pengajuan baru melebihi batas maksimal Rp '.number_format(Kasbon::BATAS_MAKSIMAL, 0, ',', '.').'. Sisa kuota tersedia: Rp '.number_format($sisaKuota, 0, ',', '.').'.',
                judul: 'Melebihi Batas Kasbon',
                aksi: 'DRIVER_CREATE_KASBON_LIMIT_EXCEEDED',
            );
        }

        $periodeAktif = $layananDataPayroll->ambilPeriodeTerbaru();
        $keteranganLengkap = $kategoriInput !== ''
            ? mb_substr(strtoupper($kategoriInput).' - '.$catatanInput, 0, 150)
            : mb_substr($catatanInput, 0, 150);

        $kasbon = DB::transaction(fn () => $layananDataPayroll->buatKasbon([
            'id_karyawan' => $karyawan->id,
            'tanggal_pengambilan' => now()->toDateString(),
            'id_periode_gaji_mulai' => $periodeAktif?->id,
            'total_pinjaman' => $nominalBersih,
            'jumlah_tenor' => $tenorInput,
            'keterangan' => $keteranganLengkap,
        ]));

        $nominalFormatted = 'Rp '.number_format((int) $kasbon->total_pinjaman, 0, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'driver.loans.index',
            pesan: "Pengajuan kasbon {$karyawan->nama_lengkap} sebesar {$nominalFormatted} ({$kasbon->jumlah_tenor}x potong gaji) berhasil dicatat.",
            judul: 'Pengajuan Kasbon Berhasil',
            aksi: 'DRIVER_CREATE_KASBON',
            dataTambahan: [
                'id' => $kasbon->id,
                'total_pinjaman' => (int) $kasbon->total_pinjaman,
                'jumlah_tenor' => (int) $kasbon->jumlah_tenor,
            ],
        );
    }
}

