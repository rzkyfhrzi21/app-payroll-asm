<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ============================================================================
 * MODEL: RUTE & JALUR ANGKUTAN (TABEL: rute)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Papan Trayek & Tarif Resmi Angkutan Batubara".
 * Setiap kali truk jalan, armada harus tahu jalur mana yang ditempuh:
 * dari mulut tambang mana (alamat asal) menuju ke pelabuhan/stockpile mana
 * (alamat tujuan), berapa jarak kilometernya, berapa tarif ongkos angkut
 * per ton yang dibayar pemilik batubara, serta berapa standar uang saku jalan
 * (uang jalan default) yang berhak dibawa supir.
 * ============================================================================
 */
#[Fillable([
    'kode',               // Kode singkatan rute (misal: "TAMBANG-A-PLG")
    'material',           // Jenis muatan batubara (misal: "BATUBARA KALORI TINGGI")
    'tarif_per_ton',      // Ongkos angkut per ton dalam rupiah bulat
    'uang_jalan_default', // Standar uang saku bensin & makan supir untuk rute ini
    'status',             // Status keaktifan rute: aktif atau nonaktif
    'jarak_km',           // Jarak tempuh satu kali jalan dalam kilometer (km)
    'alamat_asal',        // Lokasi titik muat muatan (otomatis HURUF BESAR)
    'alamat_tujuan',      // Lokasi titik bongkar muatan (otomatis HURUF BESAR)
])]
class Rute extends Model
{
    // Menggunakan trait kapital otomatis dan soft deletes (agar riwayat tarif masa lalu tidak hilang)
    use NormalisasiHurufBesar, SoftDeletes;

    // Nama tabel fisik di database MySQL
    protected $table = 'rute';

    /**
     * Kolom teks yang wajib disimpan dalam format HURUF BESAR otomatis
     * agar alamat tambang dan dermaga tidak rancu akibat huruf kecil/besar.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'kode',          // Kode singkatan jalur
        'material',      // Jenis muatan
        'alamat_asal',   // Lokasi muat di tambang
        'alamat_tujuan', // Lokasi bongkar di dermaga/stockpile
    ];

    /**
     * Konversi Otomatis Tipe Data (Casting)
     * Menjaga tarif dan jarak tetap berupa angka integer murni tanpa desimal.
     */
    protected function casts(): array
    {
        return [
            'tarif_per_ton'      => 'integer', // Tarif rupiah per ton
            'uang_jalan_default' => 'integer', // Nominal rupiah uang saku supir
            'jarak_km'           => 'integer', // Jarak kilometer bulat
        ];
    }

    /**
     * Daftar Status Keaktifan Jalur Rute
     */
    public static function daftarStatus(): array
    {
        return [
            'aktif'    => 'Aktif',    // Rute aktif dan bisa dipilih saat membuat surat jalan
            'nonaktif' => 'Nonaktif', // Rute ditutup sementara (misal jalan rusak/tambang tutup)
        ];
    }

    /**
     * Relasi ke Surat Jalan (Ritase)
     * Mengaitkan master rute ini dengan semua transaksi surat jalan pengiriman yang pernah lewat jalur ini.
     */
    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'id_rute');
    }
}
