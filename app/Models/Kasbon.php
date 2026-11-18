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
 * MODEL: KASBON / PINJAMAN SUPIR (TABEL: kasbon)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Surat Akad Perjanjian Pinjaman Uang Muka Supir".
 * Ketika supir membutuhkan uang sebelum hari gajian, supir mengajukan kasbon.
 * Sistem membatasi pinjaman ini agar tidak memberatkan (maksimal Rp 1.000.000
 * dengan cicilan maksimal 3 kali potong gaji).
 * 
 * Mekanisme Pembayaran:
 * Pinjaman ini akan dipecah otomatis ke dalam beberapa baris kartu cicilan
 * di tabel 'angsuran_kasbon'. Setiap kali periode gajian dicairkan, sisa pinjaman
 * akan berkurang hingga statusnya otomatis berubah menjadi 'lunas'.
 * ============================================================================
 */
#[Fillable([
    'id_karyawan',            // Supir yang meminjam dana kasbon
    'tanggal_pengambilan',    // Tanggal saat uang kasbon diserahkan kepada supir
    'id_periode_gaji_mulai',  // Bulan periode gajian pertama di mana cicilan mulai dipotong
    'total_pinjaman',         // Jumlah pokok uang pinjaman (maksimal Rp 1.000.000)
    'nominal_angsuran',       // Besaran cicilan per bulan
    'jumlah_tenor',           // Berapa kali cicilan akan dipotong (maksimal 3 kali gajian)
    'sisa_pinjaman',          // Sisa saldo utang yang belum terpotong dari slip gaji
    'keterangan',             // Alasan atau keperluan supir meminjam (otomatis HURUF BESAR)
    'status',                 // Keadaan pengajuan kasbon: pending, approved, rejected, lunas
])]
class Kasbon extends Model
{
    // Menggunakan normalisasi huruf kapital dan soft delete
    use NormalisasiHurufBesar, SoftDeletes;

    // Nama tabel fisik di MySQL
    protected $table = 'kasbon';

    /**
     * Kolom teks yang wajib disimpan dalam HURUF BESAR otomatis.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'keterangan', // Alasan pengajuan pinjaman
    ];

    /**
     * Batasan Bisnis Pinjaman Kasbon:
     * - Batas maksimal pinjaman: Rp 1.000.000 per pengajuan kasbon.
     * - Batas tenor maksimal  : 3 kali cicilan (3 bulan gajian).
     */
    public const BATAS_MAKSIMAL = 1_000_000;
    public const TENOR_MAKSIMAL = 3;

    /**
     * Konversi Otomatis Tipe Data (Casting)
     */
    protected function casts(): array
    {
        return [
            'total_pinjaman'      => 'integer', // Pokok pinjaman dalam rupiah bulat
            'nominal_angsuran'    => 'integer', // Cicilan per bulan dalam rupiah bulat
            'jumlah_tenor'        => 'integer', // Jumlah kali potong gaji (1, 2, atau 3)
            'sisa_pinjaman'       => 'integer', // Sisa utang dalam rupiah bulat
            'tanggal_pengambilan' => 'date',    // Tanggal pinjam diambil (YYYY-MM-DD)
        ];
    }

    /**
     * Daftar Status Pinjaman Kasbon
     */
    public static function daftarStatus(): array
    {
        return [
            'pending'  => 'Menunggu Persetujuan', // Diajukan supir, menunggu ACC admin/keuangan
            'approved' => 'Berjalan',             // Sudah disetujui, cicilan aktif memotong gaji
            'rejected' => 'Ditolak',              // Ditolak oleh pihak kantor
            'lunas'    => 'Lunas',                // Seluruh cicilan sudah berhasil dipotong (sisa Rp 0)
        ];
    }

    /**
     * Relasi ke Karyawan / Supir
     * Mengetahui identitas supir yang berutang kasbon.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    /**
     * Relasi ke Periode Gaji Mulai
     * Mengetahui di bulan periode mana cicilan pertama mulai berlaku.
     */
    public function periodeMulai(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji_mulai');
    }

    /**
     * Relasi ke Daftar Angsuran / Cicilan
     * Rincian baris kupon cicilan bulanan yang terikat ke pinjaman ini,
     * diurutkan berdasarkan urutan cicilan ke-1, ke-2, ke-3.
     */
    public function angsuran(): HasMany
    {
        return $this->hasMany(AngsuranKasbon::class, 'id_kasbon')->orderBy('urutan');
    }
}
