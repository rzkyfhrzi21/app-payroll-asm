<?php

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\JenisTruk;
use App\Models\Karyawan;
use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\Truk;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * Pembuat data uji bersama untuk seluruh feature test admin.
 *
 * Tidak memakai factory per model karena proyek hanya menyediakan UserFactory;
 * data dibuat langsung lewat Eloquent agar mengikuti aturan fillable & trait
 * normalisasi huruf besar yang dipakai aplikasi sesungguhnya.
 */
trait MembuatDataUji
{
    protected function penggunaAdmin(array $atribut = []): User
    {
        $email = $atribut['email'] ?? 'admin.hrd@payroll.test';

        return User::query()->firstOrCreate(
            ['email' => $email],
            array_merge([
                'name' => 'ADMIN HRD',
                'password' => 'password',
                'role' => UserRole::Admin,
            ], $atribut)
        );
    }

    protected function penggunaPimpinan(array $atribut = []): User
    {
        $email = $atribut['email'] ?? 'pimpinan@payroll.test';

        return User::query()->firstOrCreate(
            ['email' => $email],
            array_merge([
                'name' => 'PIMPINAN',
                'password' => 'password',
                'role' => UserRole::Pimpinan,
            ], $atribut)
        );
    }

    protected function penggunaSupir(array $atribut = []): User
    {
        $email = $atribut['email'] ?? 'supir@payroll.test';

        return User::query()->firstOrCreate(
            ['email' => $email],
            array_merge([
                'name' => 'SUPIR PORTAL',
                'password' => 'password',
                'role' => UserRole::Supir,
            ], $atribut)
        );
    }

    /**
     * Membuat akun login portal supir sekaligus baris tabel karyawan.
     *
     * @param  array<string, mixed>  $atribut
     */
    protected function buatSupir(array $atribut = []): Karyawan
    {
        $nomorInduk = (string) ($atribut['nomor_induk_karyawan'] ?? 'NIP-001');
        $emailAkun = (string) ($atribut['email'] ?? 'supir.'.strtolower(preg_replace('/[^a-z0-9]+/i', '', $nomorInduk)).'@payroll.test');

        $pengguna = $this->penggunaSupir([
            'name' => (string) ($atribut['nama_lengkap'] ?? 'BUDI SANTOSO'),
            'email' => $emailAkun,
        ]);

        unset($atribut['email']);

        return Karyawan::query()->create(array_merge([
            'id_pengguna' => $pengguna->id,
            'nomor_induk_karyawan' => $nomorInduk,
            'nama_lengkap' => 'BUDI SANTOSO',
            'status' => 'aktif',
            'persentase_komisi_bp' => 1000,
            'potongan_bpjs_bulanan' => 129000,
        ], $atribut));
    }

    /**
     * Data induk minimal agar halaman admin & mutasi CRUD bisa berjalan.
     *
     * @return array{periode: PeriodeGaji, jenisTruk: JenisTruk, karyawan: Karyawan, truk: Truk, rute: Rute}
     */
    protected function siapkanDataMaster(): array
    {
        $periode = PeriodeGaji::query()->create([
            'kode' => 'JAN-26',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-01-31',
            'tanggal_bayar' => '2026-02-01',
            'status' => 'draft',
        ]);

        $jenisTruk = JenisTruk::query()->create([
            'nama' => 'TRONTON',
            'merek_model' => 'HINO 500',
            'status' => 'aktif',
        ]);

        $karyawan = $this->buatSupir([
            'nomor_induk_karyawan' => 'NIP-001',
            'nama_lengkap' => 'BUDI SANTOSO',
        ]);

        $truk = Truk::query()->create([
            'nomor_polisi' => 'BE 8639 AAU',
            'merek_model' => 'HINO 500',
            'status' => 'aktif',
            'id_karyawan' => $karyawan->id,
            'id_jenis_truk' => $jenisTruk->id,
        ]);

        $rute = Rute::query()->create([
            'kode' => 'RTE-01',
            'material' => 'BATUBARA',
            'tarif_per_ton' => 150000,
            'uang_jalan_default' => 200000,
            'jarak_km' => 45,
            'alamat_asal' => 'PIT A TAMBANG',
            'alamat_tujuan' => 'STOCKPILE JETTY',
            'status' => 'aktif',
        ]);

        return compact('periode', 'jenisTruk', 'karyawan', 'truk', 'rute');
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    protected function buatRitase(array $data, array $atribut = []): Ritase
    {
        return Ritase::query()->create(array_merge([
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-05',
            'jumlah_rit' => 2,
            'tonase_kg' => 28480,
            'tarif_per_ton' => 150000,
            'total_angkutan' => 4272000,
            'uang_jalan' => 200000,
            'status' => 'terverifikasi',
        ], $atribut));
    }

    /**
     * Permintaan AJAX POST sesuai standar interaksi (X-Interaksi-Ajax: 1).
     *
     * @param  array<string, mixed>  $data
     */
    protected function kirimAjax(string $metode, string $alamat, array $data = []): TestResponse
    {
        return $this->json($metode, $alamat, $data, ['X-Interaksi-Ajax' => '1']);
    }
}
