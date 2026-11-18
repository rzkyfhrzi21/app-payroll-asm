<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: ANGSURAN KASBON (TABEL: angsuran_kasbon)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kartu Catatan Cicilan / Kupon Potong Gaji".
 * Jika seorang supir meminjam uang (kasbon) dan memilih mencicil 3 kali,
 * maka sistem akan mencetak 3 lembar catatan angsuran ini. Setiap tanggal
 * gajian tiba, staf keuangan akan mengambil satu per satu kupon ini untuk
 * memotong gaji supir sampai total pinjamannya lunas tanpa ada yang terlewat.
 * 
 * Tugas Utama:
 * 1. Menyimpan jadwal rincian cicilan pinjaman supir per bulan.
 * 2. Mengaitkan potongan kasbon dengan periode penggajian bulanan tertentu.
 * 3. Menandai status apakah cicilan masih 'terjadwal' atau sudah 'terpotong'.
 * ============================================================================
 */
#[Fillable([
    'id_kasbon',         // Nomor induk pinjaman kasbon yang sedang dicicil
    'id_periode_gaji',   // Periode gajian di mana cicilan ini dipotong (bisa kosong jika belum masuk periode)
    'urutan',            // Angsuran ke berapa (misal: cicilan ke-1, ke-2, atau ke-3)
    'nominal',           // Jumlah uang rupiah yang dipotong pada cicilan ini
    'tanggal_rencana',   // Perkiraan tanggal kapan cicilan ini seharusnya dipotong dari gaji
    'tanggal_potong',    // Tanggal riil saat uang gaji supir benar-benar dipotong saat pencairan
    'status',            // Status cicilan: terjadwal (menunggu gajian), terpotong (sudah lunas dipotong), dibatalkan
    'catatan',           // Catatan penjelasan tambahan dari staf kasir atau admin
])]
class AngsuranKasbon extends Model
{
    // Nama tabel fisik di database MySQL yang dijaga oleh model ini
    protected $table = 'angsuran_kasbon';

    /**
     * Konversi Otomatis Tipe Data (Casting)
     * 
     * Memastikan data angka murni tetap menjadi angka bulat (integer) tanpa desimal,
     * serta tanggal diformat rapi agar aman saat diolah dan dibandingkan.
     */
    protected function casts(): array
    {
        return [
            'urutan'          => 'integer', // Angka urutan cicilan (1, 2, 3)
            'nominal'         => 'integer', // Nominal uang dalam rupiah bulat tanpa koma desimal
            'tanggal_rencana' => 'date',    // Format tanggal kalender perkiraan potong gaji
            'tanggal_potong'  => 'date',    // Format tanggal kalender realisasi potong gaji
        ];
    }

    /**
     * Daftar Pilihan Status Cicilan
     * Berisi label status dalam bahasa Indonesia yang ramah dan mudah dibaca manusia.
     */
    public static function daftarStatus(): array
    {
        return [
            'terjadwal'  => 'Terjadwal',       // Menunggu tanggal gajian tiba
            'terpotong'  => 'Sudah Terpotong', // Sudah resmi dipotong dari slip gaji
            'dibatalkan' => 'Dibatalkan',      // Cicilan dibatalkan oleh kebijakan kantor
        ];
    }

    /**
     * Relasi ke Pinjaman Induk (Kasbon)
     * Ibarat melihat bundel map pinjaman utama tempat lembaran cicilan ini berasal.
     */
    public function kasbon(): BelongsTo
    {
        return $this->belongsTo(Kasbon::class, 'id_kasbon');
    }

    /**
     * Relasi ke Periode Gaji
     * Menghubungkan cicilan ini ke bulan gajian mana potongan tersebut dieksekusi.
     */
    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }
}
