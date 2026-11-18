<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ============================================================================
 * MODEL: JENIS TRUK (TABEL: jenis_truk)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Katalog Tipe & Kategori Kendaraan Angkutan".
 * Sebelum mencatat truk fisik nomor polisi sekian, kantor mendata dulu jenis-jenisnya,
 * misalnya: Tronton Dump Batubara, Fuso Engkel, Trailer 10 Roda.
 * Pengelompokan ini berguna agar staf armada bisa membedakan kapasitas muatan
 * dan merek truk yang beroperasi di tambang.
 * ============================================================================
 */
#[Fillable([
    'nama',        // Nama sebutan kategori truk (misal: "TRONTON DUMP 10 RODA")
    'merek_model', // Merek dan varian pabrikan truk (misal: "HINO 500 TI", "MITSUBISHI FUSO FIGHTER")
    'status',      // Status operasional kategori: aktif atau nonaktif
])]
class JenisTruk extends Model
{
    // Teks nama dan merek otomatis HURUF BESAR dan data aman dari hapus permanen (soft delete)
    use NormalisasiHurufBesar, SoftDeletes;

    // Nama tabel database MySQL
    protected $table = 'jenis_truk';

    /**
     * Kolom teks yang wajib dinormalisasi menjadi HURUF BESAR.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'nama',        // Nama tipe truk
        'merek_model', // Merek pabrikan
    ];

    /**
     * Daftar Pilihan Status
     * Pilihan status keaktifan kategori truk.
     */
    public static function daftarStatus(): array
    {
        return [
            'aktif'    => 'Aktif',    // Kategori masih dipakai untuk pendaftaran truk baru
            'nonaktif' => 'Nonaktif', // Kategori diarsipkan dan tidak lagi dipakai
        ];
    }

    /**
     * Relasi ke Unit Truk
     * Satu kategori jenis truk bisa menaungi banyak unit armada truk fisik di lapangan.
     */
    public function truk(): HasMany
    {
        return $this->hasMany(Truk::class, 'id_jenis_truk');
    }
}
