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
 * MODEL: UNIT TRUK ARMADA (TABEL: truk)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Buku Registrasi STNK & Riwayat Fisik Truk Tambang".
 * Seluruh armada pengangkut batubara milik perusahaan (tanpa pihak ketiga/vendor)
 * didaftarkan di sini. Setiap unit memiliki pelat nomor polisi yang unik,
 * jenis kategori truknya, supir penanggung jawab yang memegang kunci,
 * dan foto fisik kendaraannya.
 * 
 * Keterikatan Operasional:
 * Truk ini menjadi kendaraan yang terhubung ke surat jalan (ritase)
 * serta bon nota pengeluaran bengkel (biaya operasional/sparepart).
 * ============================================================================
 */
#[Fillable([
    'nomor_polisi',  // Pelat nomor polisi kendaraan (otomatis HURUF BESAR, misal: "BG 8123 AB")
    'merek_model',   // Merek spesifik dan tipe truk (misal: "HINO DUTRO 130 HD")
    'foto_unit',     // Jalur berkas foto fisik unit truk di folder storage
    'status',        // Status operasional unit: aktif atau nonaktif
    'id_karyawan',   // Supir utama yang memegang dan bertanggung jawab atas unit ini
    'id_jenis_truk', // Kategori jenis truk (tabel 'jenis_truk')
])]
class Truk extends Model
{
    // Menggunakan normalisasi huruf kapital otomatis dan soft deletes (agar rekap ritase masa lalu aman)
    use NormalisasiHurufBesar, SoftDeletes;

    // Nama tabel database MySQL
    protected $table = 'truk';

    /**
     * Kolom teks yang wajib disimpan dalam format HURUF BESAR otomatis
     * agar pencarian nomor polisi truk selalu akurat dan tidak dobel.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'nomor_polisi', // Pelat nomor polisi
        'merek_model',  // Varian dan tipe pabrikan
    ];

    /**
     * Daftar Status Operasional Truk
     */
    public static function daftarStatus(): array
    {
        return [
            'aktif'    => 'Aktif',    // Truk sehat dan siap ditugaskan menarik ritase
            'nonaktif' => 'Nonaktif', // Truk rusak parah, masuk bengkel besar, atau diistirahatkan
        ];
    }

    /**
     * Relasi ke Kategori Jenis Truk
     * Mengetahui apakah unit ini termasuk Tronton, Fuso, Dump, dll.
     */
    public function jenisTruk(): BelongsTo
    {
        return $this->belongsTo(JenisTruk::class, 'id_jenis_truk');
    }

    /**
     * Relasi ke Supir Penanggung Jawab
     * Mengetahui karyawan/supir tetap yang dipercaya mengemudikan truk ini.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    /**
     * Relasi ke Surat Jalan (Ritase)
     * Mengumpulkan semua riwayat perjalanan angkutan batubara yang pernah dilakukan truk ini.
     */
    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'id_truk');
    }

    /**
     * Relasi ke Lembaran Penggajian
     * Mengetahui lembar slip gaji mana saja yang menghitung hasil kerja truk ini.
     */
    public function penggajian(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'id_truk');
    }
}
