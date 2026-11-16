<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'id_periode_gaji',
    'id_truk',
    'id_karyawan',
    'tanggal_biaya',
    'kategori',
    'keterangan',
    'nominal',
    'nomor_nota',
    'foto_nota',
    'status',
])]
class BiayaOperasional extends Model
{
    use NormalisasiHurufBesar, SoftDeletes;

    protected $table = 'biaya_operasional';

    /**
     * @var array<int, string>
     */
    protected array $hurufBesar = ['keterangan', 'nomor_nota'];

    protected function casts(): array
    {
        return [
            'tanggal_biaya' => 'date',
            'nominal' => 'integer',
        ];
    }

    /**
     * Daftar kategori pengeluaran armada.
     *
     * @return array<string, string>
     */
    public static function daftarKategori(): array
    {
        return [
            'sparepart' => 'Sparepart',
            'bbm' => 'BBM / Solar',
            'tol' => 'Tol & Jalan',
            'retribusi' => 'Retribusi',
            'servis' => 'Servis & Perawatan',
            'lain_lain' => 'Lain-lain',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function daftarStatus(): array
    {
        return [
            'draft' => 'Draf',
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
    }

    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }

    public function truk(): BelongsTo
    {
        return $this->belongsTo(Truk::class, 'id_truk');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}
