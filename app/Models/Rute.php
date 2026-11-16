<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kode', 'material', 'tarif_per_ton', 'uang_jalan_default', 'status', 'jarak_km', 'alamat_asal', 'alamat_tujuan'])]
class Rute extends Model
{
    use NormalisasiHurufBesar, SoftDeletes;

    protected $table = 'rute';

    /**
     * @var array<int, string>
     */
    protected array $hurufBesar = ['kode', 'material', 'alamat_asal', 'alamat_tujuan'];

    protected function casts(): array
    {
        return [
            'tarif_per_ton' => 'integer',
            'uang_jalan_default' => 'integer',
            'jarak_km' => 'integer',
        ];
    }

    public static function daftarStatus(): array
    {
        return ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
    }

    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'id_rute');
    }
}
