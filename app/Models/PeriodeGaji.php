<?php

namespace App\Models;

use App\Support\SiklusPeriodeGaji;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * ============================================================================
 * MODEL: PERIODE GAJI (TABEL: periode_gaji)
 * ============================================================================
 *
 * Analogi Sederhana:
 * File ini ibarat "Buku Kalender Tutup Buku Gaji".
 * Periode dibuka dan ditutup SECARA MANUAL mengikuti jadwal gajian nyata
 * (misal 21 Okt 2026 s/d 18 Nov 2026), bukan dipaksa tanggal 1 s/d akhir bulan.
 *
 * Buku periode ini menjadi wadah tempat mengumpulkan seluruh surat jalan ritase
 * dan nota bon operasional pada rentang tersebut sebelum diajukan ke pimpinan
 * untuk disetujui dan dicairkan.
 * ============================================================================
 */
#[Fillable([
    'kode',              // Kode unik periode (misal: "Nov-26")
    'tanggal_mulai',     // Hari pertama periode (H+1 dari tutup buku sebelumnya)
    'tanggal_selesai',   // Hari tutup buku (NULL = periode masih berjalan)
    'tanggal_bayar',     // Tanggal rencana uang gaji ditransfer ke supir
    'status',            // Status periode: draft, pending, approved, paid
    'id_penyetuju',      // ID akun pimpinan yang memberi persetujuan ACC
    'disetujui_pada',    // Catatan waktu saat periode resmi di-ACC
    'dicairkan_pada',    // Catatan waktu saat seluruh gaji pada periode ini dibayarkan
    'catatan_penolakan', // Alasan pimpinan jika pengajuan draf dikembalikan/ditolak
])]
class PeriodeGaji extends Model
{
    // Nama tabel database MySQL
    protected $table = 'periode_gaji';

    /**
     * Validasi Rentang Periode (Buka/Tutup Buku Manual)
     *
     * Alur Kerja Bernomor:
     * (1) Dengarkan event 'saving' saat periode baru dibuat atau diedit.
     * (2) Jika tanggal mulai kosong, abaikan (biarkan database menolak).
     * (3) tanggal_selesai boleh NULL: artinya periode masih berjalan.
     * (4) Bila tanggal_selesai terisi, pastikan tidak mendahului tanggal_mulai.
     *
     * Catatan penting: model TIDAK lagi memaksa tanggal 1 s/d akhir bulan.
     * Siklus mengikuti tutup buku nyata (mis. 21 Okt 2026 s/d 18 Nov 2026).
     */
    protected static function booted(): void
    {
        static::saving(function (PeriodeGaji $periode): void {
            // (2) Tanggal mulai wajib ada; validasi detail dilakukan FormRequest.
            if ($periode->tanggal_mulai === null) {
                return;
            }

            // (3) & (4) Periode berjalan (NULL) atau tutup buku yang urut.
            if ($periode->tanggal_selesai !== null
                && $periode->tanggal_selesai->lt($periode->tanggal_mulai)) {
                throw new \InvalidArgumentException(
                    'Tanggal tutup buku tidak boleh lebih awal dari tanggal mulai periode.'
                );
            }
        });
    }

    /**
     * Konversi Otomatis Tipe Data (Casting)
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai'   => 'date',     // Format kalender tanggal mulai (YYYY-MM-DD)
            'tanggal_selesai' => 'date',     // Format kalender tanggal selesai (YYYY-MM-DD)
            'tanggal_bayar'   => 'date',     // Format kalender tanggal bayar (YYYY-MM-DD)
            'disetujui_pada'  => 'datetime', // Format waktu tanggal dan jam ACC
            'dicairkan_pada'  => 'datetime', // Format waktu tanggal dan jam cair
        ];
    }

    /**
     * Relasi ke Pimpinan Penyetuju
     * Mengetahui akun pimpinan yang bertanggung jawab mengesahkan periode ini.
     */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_penyetuju');
    }

    /**
     * Relasi ke Seluruh Surat Jalan (Ritase)
     * Mengumpulkan semua perjalanan angkutan batubara yang terjadi pada periode ini.
     */
    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'id_periode_gaji');
    }

    /**
     * Relasi ke Baris Slip Gaji Karyawan
     * Lembaran-lembaran slip gaji masing-masing supir yang bernaung di periode ini.
     */
    public function penggajian(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'id_periode_gaji');
    }
}
