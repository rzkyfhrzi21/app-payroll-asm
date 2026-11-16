<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulasiGaji extends Model
{
    use HasFactory, NormalisasiHurufBesar, SoftDeletes;

    protected $table = 'simulasi_gaji';

    /**
     * @var array<int, string>
     */
    protected array $hurufBesar = ['nama_simulasi', 'catatan'];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id_karyawan',
        'id_periode_gaji',
        'nama_simulasi',
        'data_surat_jalan',
        'total_rit',
        'total_tonase_kg',
        'total_angkutan_kotor',
        'total_uang_jalan',
        'biaya_sparepart',
        'gaji_komisi',
        'total_tunjangan',
        'potongan_bpjs',
        'potongan_kasbon',
        'potongan_lain',
        'gaji_bersih',
        'mode_potongan',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_surat_jalan' => 'array',
            'total_rit' => 'integer',
            'total_tonase_kg' => 'integer',
            'total_angkutan_kotor' => 'integer',
            'total_uang_jalan' => 'integer',
            'biaya_sparepart' => 'integer',
            'gaji_komisi' => 'integer',
            'total_tunjangan' => 'integer',
            'potongan_bpjs' => 'integer',
            'potongan_kasbon' => 'integer',
            'potongan_lain' => 'integer',
            'gaji_bersih' => 'integer',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }
}
