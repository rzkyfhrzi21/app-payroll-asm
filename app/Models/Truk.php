<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nomor_polisi', 'merek_model', 'foto_unit', 'status', 'id_karyawan', 'id_jenis_truk'])]
class Truk extends Model
{
    use NormalisasiHurufBesar, SoftDeletes;

    protected $table = 'truk';

    /**
     * @var array<int, string>
     */
    protected array $hurufBesar = ['nomor_polisi', 'merek_model'];

    public static function daftarStatus(): array
    {
        return ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
    }

    public function jenisTruk(): BelongsTo
    {
        return $this->belongsTo(JenisTruk::class, 'id_jenis_truk');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'id_truk');
    }

    public function penggajian(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'id_truk');
    }
}
