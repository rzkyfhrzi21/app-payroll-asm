<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MODEL: DATA PERUSAHAAN (TABEL: data_perusahaan)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Stempel, Kop Surat, dan Buku Akta Resmi Perusahaan".
 * Segala informasi resmi mengenai PT Kafa Coaltrans Mining Logistics
 * (mulai dari logo kantor, nama bos/direktur, tanda tangan digital,
 * nomor rekening kantor, sampai alamat lengkap) disimpan di sini.
 * 
 * Sifat Khusus (Singleton):
 * Hanya ada 1 data aktif di tabel ini. Saat sistem mencetak slip gaji atau
 * mengekspor laporan ke file Excel/PDF, informasi kepala surat (kop) otomatis
 * diambil dari file model ini.
 * ============================================================================
 */
#[Fillable([
    'nama_perusahaan',  // Nama resmi badan usaha PT (otomatis HURUF BESAR)
    'kode_perusahaan',  // Singkatan kode perusahaan untuk kop surat
    'tanggal_berdiri',  // Tanggal akta pendirian perusahaan
    'bidang_usaha',     // Lingkup bisnis (misal: LOGISTIK BATUBARA)
    'logo_perusahaan',  // Jalur simpan file gambar logo di folder storage
    'alamat',           // Alamat kantor pusat atau pool truk
    'kota',             // Nama kota kantor berada (misal: PALEMBANG)
    'no_telepon',       // Nomor telepon atau kontak kantor
    'email',            // Alamat email surat-menyurat perusahaan
    'nama_pemilik',     // Nama lengkap direktur / pimpinan pemilik usaha
    'jabatan_pemilik',  // Jabatan pimpinan (misal: DIREKTUR UTAMA)
    'ttd_pemilik',      // Jalur simpan file gambar tanda tangan basah pimpinan
    'nama_bank',        // Nama bank rekening resmi operasional kantor
    'no_rekening',      // Nomor rekening bank perusahaan
])]
class DataPerusahaan extends Model
{
    // Menggunakan trait agar teks identitas otomatis berformat HURUF BESAR
    use NormalisasiHurufBesar;

    // Nama tabel database MySQL
    protected $table = 'data_perusahaan';

    /**
     * Kolom teks identitas perusahaan yang wajib berhuruf kapital.
     * Catatan: email, nomor telepon, logo, dan ttd tidak diubah agar link/formatnya tidak rusak.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'nama_perusahaan', // Nama PT
        'kode_perusahaan', // Kode PT
        'alamat',          // Alamat fisik kantor
        'kota',            // Nama kota
        'nama_pemilik',    // Nama direktur
        'jabatan_pemilik', // Jabatan direktur
        'nama_bank',       // Nama bank
        'no_rekening',     // Nomor rekening
        'bidang_usaha',    // Bidang usaha
    ];

    /**
     * Konversi Otomatis Tipe Data
     */
    protected function casts(): array
    {
        return [
            'tanggal_berdiri' => 'date', // Format tanggal kalender (YYYY-MM-DD)
        ];
    }

    /**
     * Mendapatkan URL Lengkap File Logo Perusahaan
     * Berfungsi merapikan alamat link file logo agar bisa langsung ditampilkan di tag <img> pada Blade.
     */
    public function getUrlLogoAttribute(): ?string
    {
        // (1) Jika tidak ada file logo tersimpan di database, kembalikan kosong (null)
        if (empty($this->logo_perusahaan)) {
            return null;
        }

        // (2) Jika sudah berupa link web lengkap (http/https), pakai link tersebut langsung
        if (str_starts_with($this->logo_perusahaan, 'http://') || str_starts_with($this->logo_perusahaan, 'https://')) {
            return $this->logo_perusahaan;
        }

        // (3) Jika berupa nama file di folder local storage, rangkai menjadi URL publik
        $bersih = ltrim($this->logo_perusahaan, '/');
        return str_starts_with($bersih, 'storage/') ? asset($bersih) : asset('storage/'.$bersih);
    }

    /**
     * Mendapatkan URL Lengkap File Tanda Tangan Digital Pimpinan
     * Menghasilkan tautan file gambar tanda tangan untuk disematkan di pojok bawah slip gaji.
     */
    public function getUrlTtdAttribute(): ?string
    {
        // (1) Jika belum ada file tanda tangan, kembalikan kosong (null)
        if (empty($this->ttd_pemilik)) {
            return null;
        }

        // (2) Jika link web eksternal, gunakan langsung
        if (str_starts_with($this->ttd_pemilik, 'http://') || str_starts_with($this->ttd_pemilik, 'https://')) {
            return $this->ttd_pemilik;
        }

        // (3) Arahkan ke folder storage publik
        $bersih = ltrim($this->ttd_pemilik, '/');
        return str_starts_with($bersih, 'storage/') ? asset($bersih) : asset('storage/'.$bersih);
    }

    /**
     * Nilai Bawaan (Default Profil Perusahaan)
     * Data cadangan standar jika tabel database baru diinstal dan masih kosong.
     *
     * @return array<string, string>
     */
    public static function nilaiBawaan(): array
    {
        return [
            'nama_perusahaan' => 'PT ADIGUNA SEJAHTERA MANDIRI',
            'kode_perusahaan' => 'PT ASM',
            'kota'            => 'BANDAR LAMPUNG',
            'jabatan_pemilik' => 'DIREKTUR UTAMA',
            'nama_pemilik'    => 'PIMPINAN PERUSAHAAN',
            'alamat'          => 'JL. LINTAS SUMATERA KM 12, BANDAR LAMPUNG',
            'no_telepon'      => '0711-000000',
            'email'           => 'official_asm@gmail.com',
            'tanggal_berdiri' => '2018-05-12',
            'bidang_usaha'    => 'PENGANTARAN LOGISTIK BATUBARA',
        ];
    }

    /**
     * Mengambil Profil Perusahaan yang Sedang Aktif (Singleton)
     * 
     * Alur Kerja Bernomor:
     * (1) Cek apakah tabel 'data_perusahaan' sudah dibuat di database.
     * (2) Jika sudah ada, ambil baris pertama data perusahaan yang tersedia.
     * (3) Jika tabel belum punya data, buatkan 1 data awal otomatis memakai nilai bawaan.
     * (4) Jika terjadi galat/error apa pun, kembalikan objek darurat dengan nilai bawaan agar tampilan web tidak macet/blank.
     */
    public static function profilAktif(): self
    {
        try {
            // (1) Pengecekan keberadaan tabel
            if (! Schema::hasTable('data_perusahaan')) {
                return new self(self::nilaiBawaan());
            }

            // (2) Cari baris profil pertama
            $barisPertama = self::query()->orderBy('id')->first();

            if ($barisPertama !== null) {
                return $barisPertama;
            }

            // (3) Buat data bawaan jika tabel masih kosong
            return self::create(self::nilaiBawaan());
        } catch (\Throwable) {
            // (4) Fallback aman jika database belum siap
            return new self(self::nilaiBawaan());
        }
    }
}
