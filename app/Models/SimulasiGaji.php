<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ============================================================================
 * MODEL: SIMULASI GAJI MANDIRI SUPIR (TABEL: simulasi_gaji)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Buku Coret-Coretan / Kalkulator Perkiraan Gaji Supir".
 * Supir di lapangan sering kali ingin memperkirakan sendiri:
 * "Jika bulan ini saya narik 20 kali ritase dengan muatan sekian ton,
 * kira-kira berapa ya uang bersih yang akan saya bawa pulang nanti?".
 * 
 * Sifat Simulasi:
 * Data di sini hanyalah coret-coretan mandiri milik supir yang dibuat lewat
 * Portal Supir. Data ini sama sekali TIDAK MEMPENGARUHI laporan keuangan
 * atau slip gaji resmi perusahaan sampai pimpinan mengesahkan slip yang asli.
 * ============================================================================
 */
class SimulasiGaji extends Model
{
    // Menggunakan trait factory, kapital otomatis, dan soft delete
    use HasFactory, NormalisasiHurufBesar, SoftDeletes;

    // Nama tabel fisik di database MySQL
    protected $table = 'simulasi_gaji';

    /**
     * Kolom teks yang wajib disimpan dalam format HURUF BESAR otomatis.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'nama_simulasi', // Nama judul simulasi buatan supir (misal: "TARGET LEBARAN 25 RIT")
        'catatan',       // Catatan pengingat pribadi supir
    ];

    /**
     * Daftar Kolom Isian yang Boleh Disimpan (Mass Assignment)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_karyawan',          // Supir pemilik hitungan simulasi ini
        'id_periode_gaji',      // Periode bulan gajian yang dijadikan acuan target
        'nama_simulasi',        // Judul skenario simulasi
        'data_surat_jalan',     // Daftar perkiraan surat jalan (disimpan dalam format JSON)
        'total_rit',            // Perkiraan total trip/ritase
        'total_tonase_kg',      // Perkiraan total muatan batubara dalam kilogram (kg)
        'total_angkutan_kotor', // Perkiraan pendapatan kotor
        'total_uang_jalan',     // Perkiraan uang jalan yang diambil
        'biaya_sparepart',      // Perkiraan pengeluaran suku cadang truk
        'gaji_komisi',          // Perkiraan nominal komisi yang didapat
        'potongan_bpjs',        // Perkiraan potongan BPJS bulanan
        'potongan_kasbon',      // Perkiraan cicilan kasbon yang jatuh tempo
        'potongan_lain',        // Perkiraan potongan lain-lain
        'gaji_bersih',          // Perkiraan uang bersih akhir yang diterima
        'mode_potongan',        // Pilihan mode hitungan potongan
        'catatan',              // Catatan bebas supir
    ];

    /**
     * Konversi Otomatis Tipe Data (Casting)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_surat_jalan'     => 'array',   // Dikonversi otomatis menjadi array PHP dari teks JSON
            'total_rit'            => 'integer', // Jumlah ritase bulat
            'total_tonase_kg'      => 'integer', // Berat kg bulat
            'total_angkutan_kotor' => 'integer', // Rupiah bulat
            'total_uang_jalan'     => 'integer', // Rupiah bulat
            'biaya_sparepart'      => 'integer', // Rupiah bulat
            'gaji_komisi'          => 'integer', // Rupiah bulat
            'potongan_bpjs'        => 'integer', // Rupiah bulat
            'potongan_kasbon'      => 'integer', // Rupiah bulat
            'potongan_lain'        => 'integer', // Rupiah bulat
            'gaji_bersih'          => 'integer', // Rupiah bulat
        ];
    }

    /**
     * Relasi ke Supir Pemilik Simulasi
     * Mengetahui supir mana yang membuat draf hitungan simulasi ini.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    /**
     * Relasi ke Periode Gaji
     * Mengetahui bulan periode mana yang dijadikan patokan target simulasi.
     */
    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }
}
