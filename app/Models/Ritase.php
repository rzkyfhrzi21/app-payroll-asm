<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_periode_gaji',
    'id_karyawan',
    'id_truk',
    'id_rute',
    'tanggal_ritase',
    'jumlah_rit',
    'tonase_kg',
    'volume_liter',
    'tarif_per_ton',
    'total_angkutan',
    'uang_jalan',
    'foto_surat_jalan',
    'catatan_pengantaran',
    'nama_penerima',
    'status',
])]
class Ritase extends Model
{
    use NormalisasiHurufBesar;

    protected $table = 'ritase';

    /** @var array<int, string> */
    protected array $hurufBesar = ['catatan_pengantaran', 'nama_penerima'];

    public static function daftarStatus(): array
    {
        return [
            'draft' => 'Draf (Belum Diterbitkan)',
            'terbit' => 'Terbit (Siap Dikirim Supir)',
            'sedang_dikirim' => 'Sedang Dikirim',
            'menunggu_verifikasi' => 'Menunggu Verifikasi Admin',
            'terverifikasi' => 'Berhasil Terkirim & Terverifikasi',
            'dibatalkan' => 'Dibatalkan',
        ];
    }

    protected function casts(): array
    {
        return [
            'tanggal_ritase' => 'date',
            'jumlah_rit' => 'integer',
            'tonase_kg' => 'integer',
            'volume_liter' => 'integer',
            'tarif_per_ton' => 'integer',
            'total_angkutan' => 'integer',
            'uang_jalan' => 'integer',
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

    public function rute(): BelongsTo
    {
        return $this->belongsTo(Rute::class, 'id_rute');
    }

    public function getNomorSuratJalanAttribute(): string
    {
        $kode = $this->rute?->kode ?? 'RUTE';

        return sprintf('SJ-%s-%05d', $kode, (int) $this->id);
    }

    /**
     * @return Attribute<array<int, string>, string|null>
     */
    protected function fotoSuratJalan(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $nilai): array => self::normalisasiDaftarFoto($nilai),
            set: function (mixed $nilai): ?string {
                $daftar = self::normalisasiDaftarFoto($nilai);

                return $daftar === []
                    ? null
                    : json_encode($daftar, JSON_UNESCAPED_SLASHES);
            },
        );
    }

    /**
     * Menormalisasi nilai kolom foto_surat_jalan (JSON array, string tunggal warisan, atau null)
     * menjadi daftar unik path non-kosong.
     *
     * @return array<int, string>
     */
    public static function normalisasiDaftarFoto(mixed $nilai): array
    {
        if ($nilai === null || $nilai === '') {
            return [];
        }

        $kandidat = [];

        if (is_array($nilai)) {
            $kandidat = $nilai;
        } elseif (is_string($nilai)) {
            $teks = trim($nilai);
            if ($teks === '') {
                return [];
            }

            $hasilDecode = json_decode($teks, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($hasilDecode)) {
                $kandidat = $hasilDecode;
            } else {
                $kandidat = [$teks];
            }
        }

        $terfilter = [];
        foreach ($kandidat as $item) {
            if (! is_string($item)) {
                continue;
            }
            $jalurBersih = trim($item);
            if ($jalurBersih !== '' && ! in_array($jalurBersih, $terfilter, true)) {
                $terfilter[] = $jalurBersih;
            }
        }

        return $terfilter;
    }

    public function fotoUtamaSuratJalan(): ?string
    {
        $daftarFoto = $this->foto_surat_jalan;

        return $daftarFoto[0] ?? null;
    }
}
