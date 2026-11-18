<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ============================================================================
 * MODEL: KARYAWAN / SUPIR (TABEL: karyawan)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Buku Induk Biodata Supir & Karyawan Tambang Batubara".
 * Setiap kali ada supir baru yang bergabung dengan armada, biodata resminya
 * (nomor induk pegawai / NIK, nama sesuai KTP, rekening bank untuk transfer gaji,
 * potongan BPJS bulanan, dan persentase komisi) dicatat di formulir model ini.
 * 
 * Peran Penting dalam Penggajian:
 * File ini menjadi jangkar utama penggajian. Setiap ritase surat jalan, kasbon,
 * dan penerbitan slip gaji selalu terikat langsung ke data supir ini.
 * ============================================================================
 */
#[Fillable([
    'id_pengguna',            // ID akun login supir di sistem (tabel 'users')
    'nomor_induk_karyawan',   // Nomor Induk Karyawan resmi (misal: "NIK-2026-001")
    'nama_lengkap',           // Nama lengkap supir sesuai KTP (otomatis HURUF BESAR)
    'foto_profil',            // Jalur simpan berkas pas foto supir di folder storage
    'status',                 // Keaktifan bekerja: aktif atau nonaktif
    'persentase_komisi_bp',   // Persentase bagi hasil komisi armada dalam basis poin (1000 = 10,00%)
    'nama_bank',              // Nama bank rekening supir (misal: "BCA", "MANDIRI", "BRI")
    'nomor_rekening',         // Nomor rekening tabungan supir untuk transfer gaji
    'potongan_bpjs_bulanan',  // Nilai rupiah iuran BPJS Ketenagakerjaan/Kesehatan per bulan
])]
class Karyawan extends Model
{
    // Menggunakan trait kapital otomatis dan soft deletes (agar riwayat gaji tidak hilang jika supir keluar)
    use NormalisasiHurufBesar, SoftDeletes;

    // Nama tabel fisik di database MySQL
    protected $table = 'karyawan';

    /**
     * Kolom teks yang wajib disimpan dalam format HURUF BESAR
     * agar data perbankan dan identitas konsisten tanpa masalah huruf kecil/besar.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'nomor_induk_karyawan', // NIK supir
        'nama_lengkap',         // Nama supir
        'nama_bank',            // Nama bank
        'nomor_rekening',       // Nomor rekening
    ];

    /**
     * Konversi Otomatis Tipe Data (Casting)
     */
    protected function casts(): array
    {
        return [
            'persentase_komisi_bp'  => 'integer', // Basis poin komisi (integer bulat, misal 1000)
            'potongan_bpjs_bulanan' => 'integer', // Nilai potongan BPJS dalam rupiah bulat
        ];
    }

    /**
     * Daftar Status Kepegawaian
     */
    public static function daftarStatus(): array
    {
        return [
            'aktif'    => 'Aktif',    // Supir aktif bekerja dan boleh menerima surat jalan
            'nonaktif' => 'Nonaktif', // Supir istirahat/keluar, tidak diikutkan dalam ritase baru
        ];
    }

    /**
     * Relasi ke Akun Login Pengguna
     * Menghubungkan biodata supir dengan akun pengguna untuk login ke aplikasi.
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    /**
     * Relasi ke Riwayat Ritase / Surat Jalan
     * Mengetahui semua daftar perjalanan angkutan batubara yang pernah diselesaikan supir.
     */
    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'id_karyawan');
    }

    /**
     * Relasi ke Catatan Kasbon / Pinjaman
     * Mengetahui riwayat pinjaman uang yang pernah diajukan oleh supir ini.
     */
    public function kasbon(): HasMany
    {
        return $this->hasMany(Kasbon::class, 'id_karyawan');
    }

    /**
     * Relasi ke Rekap Penggajian Bulanan
     * Mengetahui arsip slip gaji bulanan yang pernah diterima oleh supir.
     */
    public function penggajian(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'id_karyawan');
    }

    /**
     * Relasi ke Truk yang Dipegang
     * Mengetahui unit kendaraan armada yang dipercayakan kepada supir ini.
     */
    public function truk(): HasMany
    {
        return $this->hasMany(Truk::class, 'id_karyawan');
    }

    /**
     * Relasi ke Draf Simulasi Gaji Supir
     * Daftar perkiraan hitungan gaji mandiri yang disimpan oleh supir lewat portal supir.
     */
    public function simulasiGaji(): HasMany
    {
        return $this->hasMany(SimulasiGaji::class, 'id_karyawan');
    }
}
