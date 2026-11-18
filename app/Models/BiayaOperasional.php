<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ============================================================================
 * MODEL: BIAYA OPERASIONAL (TABEL: biaya_operasional)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Buku Kas Pengeluaran Bon Bengkel & Jalan Truk".
 * Saat truk batubara jalan atau diservis di bengkel (ganti ban, beli solar,
 * bayar tol, ganti oli), supir atau mekanik menyerahkan nota/kuitansi fisik.
 * Admin mencatatnya di lembaran ini lengkap dengan foto notanya sebagai bukti.
 * 
 * Pengaruh ke Gaji:
 * Khusus biaya kategori 'sparepart', biayanya akan mengurangi hasil bersih armada
 * sebelum sistem membagikan bagi hasil komisi supir.
 * ============================================================================
 */
#[Fillable([
    'id_periode_gaji', // Bulan gajian di mana biaya ini diperhitungkan
    'id_truk',         // Truk yang memakai atau menghabiskan biaya ini
    'id_karyawan',     // Supir atau mekanik penanggung jawab nota
    'tanggal_biaya',   // Tanggal saat pengeluaran atau pembelian terjadi
    'kategori',        // Jenis biaya: sparepart, bbm, tol, retribusi, servis, dll
    'keterangan',      // Rincian barang yang dibeli (misal: "TAMBAL BAN KANAN BELAKANG")
    'nominal',         // Jumlah uang rupiah yang dikeluarkan untuk nota tersebut
    'nomor_nota',      // Nomor kuitansi atau nomor faktur dari toko/bengkel
    'foto_nota',       // Jalur berkas foto bukti fisik kuitansi di server (folder storage)
    'status',          // Status verifikasi: draft, diajukan, disetujui, ditolak
])]
class BiayaOperasional extends Model
{
    // Menggunakan trait agar teks otomatis kapital dan data tidak hilang saat dihapus (soft delete)
    use NormalisasiHurufBesar, SoftDeletes;

    // Nama tabel database MySQL yang diwakili
    protected $table = 'biaya_operasional';

    /**
     * Kolom teks yang wajib disimpan dalam format HURUF BESAR otomatis
     * agar pencarian nomor nota atau keterangan barang menjadi seragam.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'keterangan',  // Rincian catatan pengeluaran
        'nomor_nota',  // Nomor kuitansi atau seri struk belanja
    ];

    /**
     * Konversi Otomatis Tipe Data
     */
    protected function casts(): array
    {
        return [
            'tanggal_biaya' => 'date',    // Format kalender (YYYY-MM-DD)
            'nominal'       => 'integer', // Nominal uang dalam rupiah bulat tanpa desimal
        ];
    }

    /**
     * Daftar Kategori Pengeluaran Armada
     * Pilihan jenis belanja operasional kendaraan yang dikenali sistem.
     *
     * @return array<string, string>
     */
    public static function daftarKategori(): array
    {
        return [
            'sparepart'  => 'Sparepart',          // Pembelian onderdil cadangan truk
            'bbm'        => 'BBM / Solar',        // Pembelian bahan bakar minyak
            'tol'        => 'Tol & Jalan',        // Biaya melintas jalan tol
            'retribusi'  => 'Retribusi',          // Iuran pos retribusi resmi daerah
            'servis'     => 'Servis & Perawatan', // Ongkos jasa mekanik dan cuci truk
            'lain_lain'  => 'Lain-lain',          // Keperluan darurat di luar kategori atas
        ];
    }

    /**
     * Daftar Status Otorisasi Pengeluaran
     * Alur persetujuan bon pengeluaran oleh pimpinan/atasan.
     *
     * @return array<string, string>
     */
    public static function daftarStatus(): array
    {
        return [
            'draft'     => 'Draf',      // Baru dicatat oleh staf, belum diverifikasi
            'diajukan'  => 'Diajukan',  // Sudah diserahkan ke atasan untuk disetujui
            'disetujui' => 'Disetujui', // Sah dan siap memotong kalkulasi armada
            'ditolak'   => 'Ditolak',   // Bon tidak valid atau tidak diakui kantor
        ];
    }

    /**
     * Relasi ke Periode Gaji
     * Menghubungkan pengeluaran ini ke buku periode gajian yang bersangkutan.
     */
    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }

    /**
     * Relasi ke Unit Truk
     * Mengetahui unit kendaraan mana yang menggunakan sparepart/biaya ini.
     */
    public function truk(): BelongsTo
    {
        return $this->belongsTo(Truk::class, 'id_truk');
    }

    /**
     * Relasi ke Karyawan / Supir
     * Mengetahui supir atau penanggung jawab yang menyerahkan nota ini.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}
