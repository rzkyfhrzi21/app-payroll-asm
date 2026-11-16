<?php

namespace App\Models;

use App\Support\SiklusPeriodeGaji;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'kode',
    'tanggal_mulai',
    'tanggal_selesai',
    'tanggal_bayar',
    'status',
    'id_penyetuju',
    'disetujui_pada',
    'dicairkan_pada',
    'catatan_penolakan',
])]
class PeriodeGaji extends Model
{
    protected $table = 'periode_gaji';

    /**
     * Siklus penggajian selalu satu bulan penuh: tanggal 1 sampai akhir bulan.
     * Normalisasi dijalankan pada event saving agar setiap periode baru
     * maupun yang diubah otomatis memakai rentang yang benar.
     */
    protected static function booted(): void
    {
        static::saving(function (PeriodeGaji $periode): void {
            if ($periode->tanggal_mulai === null && $periode->tanggal_selesai === null) {
                return;
            }

            $acuan = $periode->tanggal_mulai ?? $periode->tanggal_selesai;
            $rentang = SiklusPeriodeGaji::rentangBulan($acuan);

            $periode->tanggal_mulai = Carbon::parse($rentang['tanggal_mulai']);
            $periode->tanggal_selesai = Carbon::parse($rentang['tanggal_selesai']);
        });
    }

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tanggal_bayar' => 'date',
            'disetujui_pada' => 'datetime',
            'dicairkan_pada' => 'datetime',
        ];
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_penyetuju');
    }

    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'id_periode_gaji');
    }

    public function penggajian(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'id_periode_gaji');
    }
}
