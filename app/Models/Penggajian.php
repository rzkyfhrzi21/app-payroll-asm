<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: PENGGAJIAN / SLIP GAJI SUPIR (TABEL: penggajian)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Lembaran Fisik Rekapitulasi Slip Gaji Bulanan Supir".
 * Setelah sebulan supir mengantar batubara, semua surat jalan yang sah
 * dikumpulkan dan dihitung oleh sistem. Lembaran ini merangkum total uang
 * hasil angkutan kotor, dipotong uang jalan dan sparepart, dihitung persentase
 * komisi supir, lalu dikurangi iuran BPJS dan angsuran kasbon hingga ketemu
 * angka akhir "Gaji Bersih" yang akan ditransfer ke rekening supir.
 * 
 * Alur Otorisasi Gaji (Wajib Diketahui):
 * 1. Admin/HRD merekonsiliasi ritase dan mengajukan draf gaji ('draft').
 * 2. Hanya Pimpinan/Direktur yang berhak memberikan persetujuan ACC ('approved').
 * 3. Setelah disetujui, kasir/pimpinan mencairkan dana transferan ('paid').
 * ============================================================================
 */
#[Fillable([
    'id_periode_gaji',      // ID bulan periode penggajian (tabel 'periode_gaji')
    'id_karyawan',          // ID supir yang menerima gaji (tabel 'karyawan')
    'id_truk',              // ID unit truk batubara utama yang dioperasikan supir
    'total_rit',            // Total berapa kali supir melakukan pengantaran dalam sebulan
    'total_tonase_kg',      // Total berat muatan batubara dalam kilogram (kg)
    'total_angkutan_kotor', // Pendapatan kotor angkutan (tonase x tarif rute)
    'potongan_operasional', // Potongan awal operasional (jika ada ketentuan khusus rute)
    'angkutan_bersih',      // Hasil angkutan bersih tahap 1: kotor dikurangi operasional
    'total_uang_jalan',     // Total uang saku jalan yang sudah diambil supir saat berangkat
    'biaya_sparepart',      // Total nota bon suku cadang truk yang dibebankan ke armada
    'hasil_bersih_armada',  // Hasil bersih armada tahap 2: angkutan bersih - uang jalan - sparepart
    'persentase_komisi_bp', // Basis poin komisi supir (misal: 1000 = 10,00%)
    'gaji_komisi',          // Nilai bagi hasil komisi supir (hasil bersih armada x persentase)
    'total_tunjangan',      // Tunjangan tambahan (tunjangan hadir, prestasi, dll)
    'potongan_bpjs',        // Potongan wajib iuran jaminan BPJS bulanan
    'potongan_kasbon',      // Potongan cicilan pinjaman kasbon periode berjalan
    'gaji_bersih',          // Angka final yang diterima supir (Rupiah bulat tanpa desimal)
    'status',               // Status slip gaji: draft, approved, paid
    'id_penyetuju',         // ID user pimpinan yang memberikan otorisasi ACC
    'disetujui_pada',       // Waktu tanggal dan jam saat pimpinan menekan tombol ACC
    'dicairkan_pada',       // Waktu tanggal dan jam saat uang gaji resmi dicairkan/ditransfer
])]
class Penggajian extends Model
{
    // Nama tabel database MySQL
    protected $table = 'penggajian';

    /**
     * Daftar Pilihan Status Penggajian
     * Menjelaskan tahapan hidup dari selembar slip gaji.
     */
    public static function daftarStatus(): array
    {
        return [
            'draft'    => 'Menunggu ACC',           // Selesai dihitung admin, menunggu tinjauan pimpinan
            'approved' => 'Disetujui (Siap Cair)',  // Sudah di-ACC pimpinan, siap dieksekusi transfer
            'paid'     => 'Sudah Dicairkan',        // Uang sudah keluar, kasbon terpotong resmi
        ];
    }

    /**
     * Konversi Otomatis Tipe Data (Casting)
     * Seluruh nominal uang dan berat diubah ke integer agar tidak ada koma desimal (KISS & presisi).
     */
    protected function casts(): array
    {
        return [
            'total_rit'             => 'integer',  // Jumlah trip/ritase pengiriman
            'total_tonase_kg'       => 'integer',  // Berat timbangan dalam kilogram bulat
            'total_angkutan_kotor'  => 'integer',  // Nominal rupiah pendapatan kotor
            'potongan_operasional'  => 'integer',  // Nominal rupiah potongan rute
            'angkutan_bersih'       => 'integer',  // Nominal rupiah angkutan bersih
            'total_uang_jalan'      => 'integer',  // Nominal rupiah uang jalan supir
            'biaya_sparepart'       => 'integer',  // Nominal rupiah nota onderdil
            'hasil_bersih_armada'   => 'integer',  // Nominal rupiah sisa armada
            'persentase_komisi_bp'  => 'integer',  // Basis poin persen (1000 = 10%)
            'gaji_komisi'           => 'integer',  // Nominal rupiah komisi supir
            'total_tunjangan'       => 'integer',  // Nominal rupiah tunjangan
            'potongan_bpjs'         => 'integer',  // Nominal rupiah iuran BPJS
            'potongan_kasbon'       => 'integer',  // Nominal rupiah potong kasbon
            'gaji_bersih'           => 'integer',  // Nominal rupiah gaji bersih final
            'disetujui_pada'        => 'datetime', // Format tanggal dan waktu ACC
            'dicairkan_pada'        => 'datetime', // Format tanggal dan waktu pencairan dana
        ];
    }

    /**
     * Relasi ke Periode Gaji
     * Mengetahui buku periode gajian mana lembar slip ini terdaftar.
     */
    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }

    /**
     * Relasi ke Karyawan / Supir
     * Mengetahui identitas supir pemilik slip gaji ini.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    /**
     * Relasi ke Truk Armada
     * Mengetahui armada truk yang digunakan supir selama periode tersebut.
     */
    public function truk(): BelongsTo
    {
        return $this->belongsTo(Truk::class, 'id_truk');
    }

    /**
     * Relasi ke User Penyetuju (Pimpinan)
     * Mengetahui siapa pimpinan atau direktur yang menandatangani/meng-ACC slip gaji ini.
     */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_penyetuju');
    }
}
