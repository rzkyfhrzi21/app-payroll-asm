<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'id_karyawan',
    'tanggal_pengambilan',
    'id_periode_gaji_mulai',
    'total_pinjaman',
    'nominal_angsuran',
    'jumlah_tenor',
    'sisa_pinjaman',
    'keterangan',
    'status',
])]
class Kasbon extends Model
{
    use NormalisasiHurufBesar, SoftDeletes;

    protected $table = 'kasbon';

    /**
     * @var array<int, string>
     */
    protected array $hurufBesar = ['keterangan'];

    public const BATAS_MAKSIMAL = 1_000_000;

    public const TENOR_MAKSIMAL = 3;

    protected function casts(): array
    {
        return [
            'total_pinjaman' => 'integer',
            'nominal_angsuran' => 'integer',
            'jumlah_tenor' => 'integer',
            'sisa_pinjaman' => 'integer',
            'tanggal_pengambilan' => 'date',
        ];
    }

    public static function daftarStatus(): array
    {
        return [
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Berjalan',
            'rejected' => 'Ditolak',
            'lunas' => 'Lunas',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function periodeMulai(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji_mulai');
    }

    public function angsuran(): HasMany
    {
        return $this->hasMany(AngsuranKasbon::class, 'id_kasbon')->orderBy('urutan');
    }
}
