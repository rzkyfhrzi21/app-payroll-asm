<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'id_pengguna',
    'nomor_induk_karyawan',
    'nama_lengkap',
    'foto_profil',
    'status',
    'persentase_komisi_bp',
    'nama_bank',
    'nomor_rekening',
    'potongan_bpjs_bulanan',
])]
class Karyawan extends Model
{
    use NormalisasiHurufBesar, SoftDeletes;

    protected $table = 'karyawan';

    /**
     * Kolom teks yang wajib HURUF BESAR saat disimpan.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'nomor_induk_karyawan',
        'nama_lengkap',
        'nama_bank',
        'nomor_rekening',
    ];

    protected function casts(): array
    {
        return [
            'persentase_komisi_bp' => 'integer',
            'potongan_bpjs_bulanan' => 'integer',
        ];
    }

    public static function daftarStatus(): array
    {
        return ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function ritase(): HasMany
    {
        return $this->hasMany(Ritase::class, 'id_karyawan');
    }

    public function kasbon(): HasMany
    {
        return $this->hasMany(Kasbon::class, 'id_karyawan');
    }

    public function penggajian(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'id_karyawan');
    }

    /**
     * Unit armada yang sedang dipegang supir ini.
     */
    public function truk(): HasMany
    {
        return $this->hasMany(Truk::class, 'id_karyawan');
    }

    public function simulasiGaji(): HasMany
    {
        return $this->hasMany(SimulasiGaji::class, 'id_karyawan');
    }
}
