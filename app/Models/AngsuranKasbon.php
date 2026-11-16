<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_kasbon',
    'id_periode_gaji',
    'urutan',
    'nominal',
    'tanggal_rencana',
    'tanggal_potong',
    'status',
    'catatan',
])]
class AngsuranKasbon extends Model
{
    protected $table = 'angsuran_kasbon';

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'nominal' => 'integer',
            'tanggal_rencana' => 'date',
            'tanggal_potong' => 'date',
        ];
    }

    public static function daftarStatus(): array
    {
        return [
            'terjadwal' => 'Terjadwal',
            'terpotong' => 'Sudah Terpotong',
            'dibatalkan' => 'Dibatalkan',
        ];
    }

    public function kasbon(): BelongsTo
    {
        return $this->belongsTo(Kasbon::class, 'id_kasbon');
    }

    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }
}
