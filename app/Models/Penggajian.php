<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_periode_gaji',
    'id_karyawan',
    'id_truk',
    'total_rit',
    'total_tonase_kg',
    'total_angkutan_kotor',
    'potongan_operasional',
    'angkutan_bersih',
    'total_uang_jalan',
    'biaya_sparepart',
    'hasil_bersih_armada',
    'persentase_komisi_bp',
    'gaji_komisi',
    'total_tunjangan',
    'potongan_bpjs',
    'potongan_kasbon',
    'gaji_bersih',
    'status',
    'id_penyetuju',
    'disetujui_pada',
    'dicairkan_pada',
])]
class Penggajian extends Model
{
    protected $table = 'penggajian';

    public static function daftarStatus(): array
    {
        return [
            'draft' => 'Menunggu ACC',
            'approved' => 'Disetujui (Siap Cair)',
            'paid' => 'Sudah Dicairkan',
        ];
    }

    protected function casts(): array
    {
        return [
            'total_rit' => 'integer',
            'total_tonase_kg' => 'integer',
            'total_angkutan_kotor' => 'integer',
            'potongan_operasional' => 'integer',
            'angkutan_bersih' => 'integer',
            'total_uang_jalan' => 'integer',
            'biaya_sparepart' => 'integer',
            'hasil_bersih_armada' => 'integer',
            'persentase_komisi_bp' => 'integer',
            'gaji_komisi' => 'integer',
            'total_tunjangan' => 'integer',
            'potongan_bpjs' => 'integer',
            'potongan_kasbon' => 'integer',
            'gaji_bersih' => 'integer',
            'disetujui_pada' => 'datetime',
            'dicairkan_pada' => 'datetime',
        ];
    }

    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function truk(): BelongsTo
    {
        return $this->belongsTo(Truk::class, 'id_truk');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_penyetuju');
    }
}
