<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Concerns\NormalisasiHurufBesar;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * ============================================================================
 * MODEL: PENGGUNA / AKUN LOGIN (TABEL: users)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kartu Tanda Pengenal & Kunci Masuk Pintu Gerbang Sistem".
 * Siapa pun yang ingin membuka aplikasi payroll ini (baik Admin HRD, Pimpinan/Direktur,
 * maupun Supir Lapangan) wajib memiliki satu baris akun di sini.
 * 
 * Hak Akses (Role):
 * - admin    : Mengurus operasional harian, rute, truk, ritase, dan draf gaji.
 * - pimpinan : Memiliki hak veto tertinggi untuk memberikan ACC gaji atau menolak.
 * - supir    : Mengonfirmasi surat jalan di lapangan dan melihat slip gaji mandiri.
 * ============================================================================
 */
#[Fillable([
    'name',          // Nama lengkap pemilik akun (otomatis HURUF BESAR)
    'email',         // Alamat email untuk login (dilarang kapital, harus huruf kecil)
    'foto_profil',   // Jalur berkas foto wajah pengguna di folder storage
    'password',      // Kata sandi login (dienkripsi aman / hashed, bukan teks polos)
    'role',          // Peran pengguna: admin, pimpinan, atau supir
    'nomor_telepon', // Nomor telepon / WhatsApp pengguna
    'alamat',        // Alamat tempat tinggal domisili pengguna
])]
#[Hidden([
    'password',       // Rahasia kata sandi, tidak boleh tampil sembarangan saat diekspor
    'remember_token', // Kode pengingat sesi login aman
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, NormalisasiHurufBesar, Notifiable;

    /**
     * Kolom teks yang wajib disimpan dalam format HURUF BESAR otomatis.
     * PERHATIAN: Email, password, dan peran (role) TIDAK BOLEH diubah hurufnya!
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'name',          // Nama pengguna
        'nomor_telepon', // Nomor kontak
        'alamat',        // Alamat tempat tinggal
    ];

    /**
     * Konversi Otomatis Tipe Data (Casting)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',        // Waktu verifikasi email
            'password'          => 'hashed',          // Otomatis mengenkripsi kata sandi
            'role'              => UserRole::class,   // Menggunakan enum resmi hak akses
        ];
    }

    /**
     * Mendapatkan URL Lengkap Foto Profil Pengguna
     * Berfungsi merapikan alamat link foto agar aman ditampilkan pada avatar navigasi.
     */
    public function getUrlFotoProfilAttribute(): ?string
    {
        // (1) Jika tidak punya foto profil, kembalikan kosong (null)
        if (empty($this->foto_profil)) {
            return null;
        }

        // (2) Jika link eksternal web (http/https), pakai langsung
        if (str_starts_with($this->foto_profil, 'http://') || str_starts_with($this->foto_profil, 'https://')) {
            return $this->foto_profil;
        }

        // (3) Arahkan ke penyimpanan folder storage lokal
        $bersih = ltrim($this->foto_profil, '/');
        return str_starts_with($bersih, 'storage/') ? asset($bersih) : asset('storage/'.$bersih);
    }

    /**
     * Relasi ke Profil Supir (Jika Akun Bertipe Supir)
     * Menghubungkan akun login ini ke data fisik biodata supir di tabel 'karyawan'.
     */
    public function supir(): HasOne
    {
        return $this->hasOne(Karyawan::class, 'id_pengguna');
    }

    /**
     * Pengecekan Peran: Apakah Pengguna Ini Adalah Admin?
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Pengecekan Peran: Apakah Pengguna Ini Adalah Pimpinan / Direktur?
     */
    public function isPimpinan(): bool
    {
        return $this->role === UserRole::Pimpinan;
    }

    /**
     * Pengecekan Peran: Apakah Pengguna Ini Adalah Supir Lapangan?
     */
    public function isSupir(): bool
    {
        return $this->role === UserRole::Supir;
    }
}
