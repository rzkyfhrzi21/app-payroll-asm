<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nama', 'merek_model', 'status'])]
class JenisTruk extends Model
{
    use NormalisasiHurufBesar, SoftDeletes;

    protected $table = 'jenis_truk';

    /**
     * @var array<int, string>
     */
    protected array $hurufBesar = ['nama', 'merek_model'];

    public static function daftarStatus(): array
    {
        return ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
    }

    public function truk(): HasMany
    {
        return $this->hasMany(Truk::class, 'id_jenis_truk');
    }
}
