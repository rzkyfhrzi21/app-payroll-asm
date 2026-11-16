<?php

namespace App\Http\Controllers\Admin\Master;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\MengaturPaginasiTabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKaryawanRequest;
use App\Http\Requests\UpdateKaryawanRequest;
use App\Models\Karyawan;
use App\Models\User;
use App\Services\PayrollDataService;
use App\Support\AturanKataSandi;
use App\Support\FormatNomorTelepon;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use MengaturPaginasiTabel, ResponInteraksiStandar;

    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $perHalaman = $this->ambilJumlahBarisPerHalaman($request);
        $filter = [
            'cari' => $this->ambilKataKunciPencarian($request),
            'status' => $this->ambilFilterTeks($request, 'status'),
            'unit' => $this->ambilFilterTeks($request, 'unit'),
        ];

        return view('admin.master.employees.index', [
            'employees' => $layananDataPayroll->dataKaryawanMasterTerpaginasi($filter, $perHalaman),
            'summary' => $layananDataPayroll->ringkasanMaster(),
            'routes' => $layananDataPayroll->daftarRute(),
            'truckComposition' => $layananDataPayroll->komposisiTruk(),
            'daftarAkun' => $this->daftarAkunStaf(),
            'peranTersedia' => $this->peranYangBolehDikelola(),
            'filter' => $filter,
            'perPage' => $perHalaman,
        ]);
    }

    /**
     * Data rincian untuk sub tab modal detail supir (dipanggil via AJAX).
     */
    public function detailSupir(Karyawan $employee, PayrollDataService $layananDataPayroll): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'ringkasan' => $layananDataPayroll->ringkasanDetailSupir($employee->id),
                'pengeluaran' => $layananDataPayroll->pengeluaranPerSupir($employee->id),
                'kasbon' => $layananDataPayroll->kasbonPerSupir($employee->id),
            ],
        ]);
    }

    /**
     * Akun staf (di luar supir) yang boleh dikelola Admin HRD.
     * Supir dikelola di tabel utama halaman ini, bukan di modal akun.
     *
     * @return array<int, array<string, mixed>>
     */
    private function daftarAkunStaf(): array
    {
        return User::query()
            ->whereIn('role', [UserRole::Admin->value, UserRole::Pimpinan->value])
            ->orderBy('name')
            ->get()
            ->map(fn (User $pengguna) => [
                'id' => $pengguna->id,
                'nama' => $pengguna->name,
                'email' => $pengguna->email,
                'peran' => $pengguna->role?->value ?? UserRole::Admin->value,
                'peran_label' => $pengguna->role?->label() ?? 'Admin HRD',
                'nomor_telepon' => $pengguna->nomor_telepon ?? '',
                'dibuat_pada' => $pengguna->created_at?->format('d/m/Y') ?? '—',
            ])
            ->all();
    }

    /**
     * Peran yang boleh dibuat oleh pengguna yang sedang login.
     *
     * @return array<int, array{nilai: string, label: string}>
     */
    private function peranYangBolehDikelola(): array
    {
        $peranSaya = $this->peranPenggunaSaatIni();

        return array_values(array_map(
            fn (UserRole $peran) => ['nilai' => $peran->value, 'label' => $peran->label()],
            $peranSaya->peranYangBolehDibuat()
        ));
    }

    private function peranPenggunaSaatIni(): UserRole
    {
        $peran = request()->user()?->role;

        return $peran instanceof UserRole
            ? $peran
            : UserRole::tryFrom((string) $peran) ?? UserRole::Supir;
    }

    /**
     * Membuat akun staf baru (Admin HRD / Pimpinan).
     * Supir tidak lewat sini karena wajib punya data karyawan terpisah.
     */
    public function simpanAkun(Request $permintaan): RedirectResponse|JsonResponse
    {
        $peranSaya = $this->peranPenggunaSaatIni();
        $peranDiizinkan = array_map(
            fn (UserRole $peran) => $peran->value,
            $peranSaya->peranYangBolehDibuat()
        );

        $permintaan->merge([
            'nomor_telepon' => FormatNomorTelepon::keStandar($permintaan->input('nomor_telepon')),
        ]);

        $data = $permintaan->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in($peranDiizinkan)],
            'password' => AturanKataSandi::aturan(),
            'nomor_telepon' => ['nullable', 'string', 'regex:'.FormatNomorTelepon::POLA_VALIDASI],
        ], array_merge([
            'role.in' => 'Anda tidak berwenang membuat akun dengan peran tersebut.',
            'email.unique' => 'Email sudah terdaftar. Gunakan email lain.',
            'nomor_telepon.regex' => 'Nomor telepon harus diawali angka 8 setelah kode +62 dan hanya berisi angka (contoh: 812-8930-1123).',
        ], AturanKataSandi::pesan()));

        $akun = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => UserRole::from($data['role']),
            'password' => Hash::make($data['password']),
            'nomor_telepon' => $data['nomor_telepon'] ?? null,
        ]);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.employees.index',
            pesan: "Akun {$akun->role->label()} atas nama '{$akun->name}' ({$akun->email}) berhasil dibuat.",
            judul: 'Akun Pengguna Dibuat',
            aksi: 'CREATE_AKUN_STAF',
            dataTambahan: [
                'id' => $akun->id,
                'email' => $akun->email,
                'peran' => $akun->role?->value,
            ],
        );
    }

    /**
     * Memperbarui data akun staf.
     */
    public function perbaruiAkun(Request $permintaan, User $akun): RedirectResponse|JsonResponse
    {
        $peranSaya = $this->peranPenggunaSaatIni();
        $peranTarget = $akun->role instanceof UserRole ? $akun->role : UserRole::tryFrom((string) $akun->role);

        if ($peranTarget === null || ! $peranSaya->bolehMembuat($peranTarget)) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.master.employees.index',
                pesan: 'Anda tidak berwenang mengubah akun dengan peran tersebut.',
                judul: 'Perubahan Akun Ditolak',
                aksi: 'UPDATE_AKUN_FORBIDDEN',
                statusKode: 'FORBIDDEN',
                kodeHttp: 403,
            );
        }

        $peranDiizinkan = array_map(
            fn (UserRole $peran) => $peran->value,
            $peranSaya->peranYangBolehDibuat()
        );

        $permintaan->merge([
            'nomor_telepon' => FormatNomorTelepon::keStandar($permintaan->input('nomor_telepon')),
        ]);

        $data = $permintaan->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($akun->id)],
            'role' => ['required', Rule::in($peranDiizinkan)],
            'password' => AturanKataSandi::aturan(false),
            'nomor_telepon' => ['nullable', 'string', 'regex:'.FormatNomorTelepon::POLA_VALIDASI],
        ], array_merge(
            ['nomor_telepon.regex' => 'Nomor telepon harus diawali angka 8 setelah kode +62 dan hanya berisi angka (contoh: 812-8930-1123).'],
            AturanKataSandi::pesan()
        ));

        $atribut = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => UserRole::from($data['role']),
            'nomor_telepon' => $data['nomor_telepon'] ?? null,
        ];

        if (! empty($data['password'])) {
            $atribut['password'] = Hash::make($data['password']);
        }

        $akun->update($atribut);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.employees.index',
            pesan: "Akun '{$akun->name}' ({$akun->email}) berhasil diperbarui.",
            judul: 'Akun Pengguna Diperbarui',
            aksi: 'UPDATE_AKUN_STAF',
            dataTambahan: ['id' => $akun->id, 'peran' => $akun->role?->value],
        );
    }

    /**
     * Menghapus akun staf. Akun sendiri tidak boleh dihapus.
     */
    public function hapusAkun(Request $permintaan, User $akun): RedirectResponse|JsonResponse
    {
        $peranSaya = $this->peranPenggunaSaatIni();
        $peranTarget = $akun->role instanceof UserRole ? $akun->role : UserRole::tryFrom((string) $akun->role);

        if ((int) $akun->id === (int) $permintaan->user()->id) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.master.employees.index',
                pesan: 'Anda tidak dapat menghapus akun yang sedang Anda pakai.',
                judul: 'Penghapusan Akun Ditolak',
                aksi: 'DELETE_AKUN_SENDIRI',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        if ($peranTarget === null || ! $peranSaya->bolehMembuat($peranTarget)) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.master.employees.index',
                pesan: 'Anda tidak berwenang menghapus akun dengan peran tersebut.',
                judul: 'Penghapusan Akun Ditolak',
                aksi: 'DELETE_AKUN_FORBIDDEN',
                statusKode: 'FORBIDDEN',
                kodeHttp: 403,
            );
        }

        if ($akun->supir()->exists()) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.master.employees.index',
                pesan: "Akun '{$akun->name}' tertaut ke data supir. Hapus data supirnya lebih dulu.",
                judul: 'Penghapusan Akun Ditolak',
                aksi: 'DELETE_AKUN_TERIKAT',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $nama = $akun->name;
        $akun->delete();

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.employees.index',
            pesan: "Akun '{$nama}' berhasil dihapus.",
            judul: 'Akun Pengguna Dihapus',
            aksi: 'DELETE_AKUN_STAF',
        );
    }

    /**
     * Menyimpan supir baru sekaligus akun login Portal Supir.
     * Dua tabel ditulis dalam satu transaksi agar tidak ada akun tanpa karyawan.
     */
    public function store(
        StoreKaryawanRequest $permintaan,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|JsonResponse {
        $data = $permintaan->validated();
        $jalurFoto = $permintaan->hasFile('foto_profil')
            ? $layananMediaGambar->validasiDanSimpanWebp($permintaan->file('foto_profil'), 'foto-supir', 'foto_profil')
            : null;
        $karyawanBaru = null;

        DB::transaction(function () use ($data, $jalurFoto, &$karyawanBaru): void {
            $pengguna = User::create([
                'name' => $data['nama_lengkap'],
                'email' => $data['email'],
                'foto_profil' => $jalurFoto,
                'password' => (string) $data['password'],
                'role' => UserRole::Supir,
                'nomor_telepon' => $data['nomor_telepon'] ?? null,
            ]);

            $karyawanBaru = Karyawan::create([
                'id_pengguna' => $pengguna->id,
                'nomor_induk_karyawan' => $data['nomor_induk_karyawan'],
                'nama_lengkap' => $data['nama_lengkap'],
                'foto_profil' => $jalurFoto,
                'status' => $data['status'] ?? 'aktif',
                'persentase_komisi_bp' => (int) round(($data['persentase_komisi'] ?? 10) * 100),
                'nama_bank' => $data['nama_bank'] ?? null,
                'nomor_rekening' => $data['nomor_rekening'] ?? null,
                'potongan_bpjs_bulanan' => (int) round($data['potongan_bpjs_bulanan'] ?? 0),
            ]);
        });

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.employees.index',
            pesan: "Data supir baru atas nama '{$karyawanBaru?->nama_lengkap}' (NIP: {$karyawanBaru?->nomor_induk_karyawan}) berhasil ditambahkan ke dalam database.",
            judul: 'Berhasil Menyimpan Data Supir',
            aksi: 'CREATE_KARYAWAN',
            dataTambahan: [
                'id' => $karyawanBaru?->id,
                'nip' => $karyawanBaru?->nomor_induk_karyawan,
                'nama' => $karyawanBaru?->nama_lengkap,
            ],
        );
    }

    public function update(
        UpdateKaryawanRequest $permintaan,
        Karyawan $employee,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|JsonResponse {
        $data = $permintaan->validated();
        $atributPerbarui = [
            'nama_lengkap' => $data['nama_lengkap'],
            'status' => $data['status'] ?? 'aktif',
            'persentase_komisi_bp' => isset($data['persentase_komisi'])
                ? (int) round(((float) $data['persentase_komisi']) * 100)
                : (int) $employee->persentase_komisi_bp,
            'nama_bank' => $data['nama_bank'] ?? null,
            'nomor_rekening' => $data['nomor_rekening'] ?? null,
            'potongan_bpjs_bulanan' => isset($data['potongan_bpjs_bulanan'])
                ? (int) round((float) $data['potongan_bpjs_bulanan'])
                : (int) $employee->potongan_bpjs_bulanan,
        ];

        if ($permintaan->hasFile('foto_profil')) {
            $atributPerbarui['foto_profil'] = $layananMediaGambar->validasiDanSimpanWebp(
                $permintaan->file('foto_profil'),
                'foto-supir',
                'foto_profil'
            );
        }

        $employee->update($atributPerbarui);

        $atributPengguna = [
            'name' => $data['nama_lengkap'],
        ];

        if (array_key_exists('nomor_telepon', $data)) {
            $atributPengguna['nomor_telepon'] = $data['nomor_telepon'];
        }

        if (isset($atributPerbarui['foto_profil'])) {
            $atributPengguna['foto_profil'] = $atributPerbarui['foto_profil'];
        }

        $employee->pengguna?->update($atributPengguna);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.employees.index',
            pesan: "Data profil & rekening supir '{$employee->nama_lengkap}' (NIP: {$employee->nomor_induk_karyawan}) berhasil diperbarui.",
            judul: 'Berhasil Memperbarui Supir',
            aksi: 'UPDATE_KARYAWAN',
            dataTambahan: [
                'id' => $employee->id,
                'nip' => $employee->nomor_induk_karyawan,
                'nama' => $employee->nama_lengkap,
            ],
        );
    }

    /**
     * Mengubah status aktif/nonaktif tanpa menghapus data.
     */
    public function toggleStatus(Request $permintaan, Karyawan $employee): RedirectResponse|JsonResponse
    {
        $statusBaru = $employee->status === 'aktif' ? 'nonaktif' : 'aktif';
        $employee->update(['status' => $statusBaru]);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.employees.index',
            pesan: "Status supir '{$employee->nama_lengkap}' (NIP: {$employee->nomor_induk_karyawan}) diubah menjadi ".ucfirst($statusBaru).'.',
            judul: 'Status Supir Diperbarui',
            aksi: 'STATUS_KARYAWAN',
            dataTambahan: [
                'id' => $employee->id,
                'status' => $statusBaru,
            ],
        );
    }

    /**
     * Menghapus supir hanya bila belum punya jejak ritase/penggajian/kasbon.
     */
    /**
     * Mengubah status banyak supir sekaligus dari checkbox tabel.
     */
    public function statusMassal(Request $permintaan): RedirectResponse|JsonResponse
    {
        $data = $permintaan->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:karyawan,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(Karyawan::daftarStatus()))],
        ]);

        $jumlahBaris = Karyawan::query()->whereIn('id', $data['ids'])->update(['status' => $data['status']]);
        $labelStatus = Karyawan::daftarStatus()[$data['status']] ?? $data['status'];

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.employees.index',
            pesan: "Status {$jumlahBaris} supir berhasil diubah menjadi {$labelStatus}.",
            judul: 'Status Supir Massal Diperbarui',
            aksi: 'BULK_STATUS_KARYAWAN',
            dataTambahan: ['jumlah' => $jumlahBaris, 'status' => $data['status']],
        );
    }

    public function destroy(Request $permintaan, Karyawan $employee, PayrollDataService $layananDataPayroll): RedirectResponse|JsonResponse
    {
        $ketergantungan = $layananDataPayroll->periksaKetergantungan('karyawan', $employee->id);

        $rincian = collect([
            $ketergantungan['ritase'] > 0 ? $ketergantungan['ritase'].' ritase' : null,
            $ketergantungan['penggajian'] > 0 ? $ketergantungan['penggajian'].' slip gaji' : null,
            $ketergantungan['kasbon'] > 0 ? $ketergantungan['kasbon'].' kasbon' : null,
        ])->filter()->implode(', ');

        if ($rincian !== '') {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.master.employees.index',
                pesan: "Supir '{$employee->nama_lengkap}' memiliki {$rincian}. Data tidak dapat dihapus. Nonaktifkan supir bila sudah tidak bertugas.",
                judul: 'Penghapusan Supir Ditolak',
                aksi: 'DELETE_KARYAWAN_LOCKED',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $namaDihapus = $employee->nama_lengkap;
        $nipDihapus = $employee->nomor_induk_karyawan;

        DB::transaction(function () use ($employee): void {
            $pengguna = $employee->pengguna;
            $employee->delete();
            $pengguna?->delete();
        });

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.employees.index',
            pesan: "Data supir '{$namaDihapus}' (NIP: {$nipDihapus}) berhasil dihapus dari database.",
            judul: 'Data Supir Dihapus',
            aksi: 'DELETE_KARYAWAN',
            dataTambahan: [
                'nip' => $nipDihapus,
                'nama' => $namaDihapus,
            ],
        );
    }
}
