<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'nama_perusahaan',
    'kode_perusahaan',
    'tanggal_berdiri',
    'bidang_usaha',
    'logo_perusahaan',
    'alamat',
    'kota',
    'no_telepon',
    'email',
    'nama_pemilik',
    'jabatan_pemilik',
    'ttd_pemilik',
    'nama_bank',
    'no_rekening',
])]
class DataPerusahaan extends Model
{
    use NormalisasiHurufBesar;

    protected $table = 'data_perusahaan';

    /**
     * Kolom teks identitas perusahaan wajib huruf besar; email, logo, ttd, dan
     * no_telepon dibiarkan apa adanya.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'nama_perusahaan',
        'kode_perusahaan',
        'alamat',
        'kota',
        'nama_pemilik',
        'jabatan_pemilik',
        'nama_bank',
        'no_rekening',
        'bidang_usaha',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_berdiri' => 'date',
        ];
    }
    public function getUrlLogoAttribute(): ?string
    {
        if (empty($this->logo_perusahaan)) {
            return null;
        }
        if (str_starts_with($this->logo_perusahaan, 'http://') || str_starts_with($this->logo_perusahaan, 'https://')) {
            return $this->logo_perusahaan;
        }
        $bersih = ltrim($this->logo_perusahaan, '/');
        return str_starts_with($bersih, 'storage/') ? asset($bersih) : asset('storage/'.$bersih);
    }

    public function getUrlTtdAttribute(): ?string
    {
        if (empty($this->ttd_pemilik)) {
            return null;
        }
        if (str_starts_with($this->ttd_pemilik, 'http://') || str_starts_with($this->ttd_pemilik, 'https://')) {
            return $this->ttd_pemilik;
        }
        $bersih = ltrim($this->ttd_pemilik, '/');
        return str_starts_with($bersih, 'storage/') ? asset($bersih) : asset('storage/'.$bersih);
    }


    /**
     * Nilai bawaan singleton profil perusahaan.
     *
     * @return array<string, string>
     */
    public static function nilaiBawaan(): array
    {
        return [
            'nama_perusahaan' => 'PT KAFA COALTRANS MINING LOGISTICS',
            'kode_perusahaan' => 'COALTRANS',
            'kota' => 'PALEMBANG',
            'jabatan_pemilik' => 'DIREKTUR UTAMA',
            'nama_pemilik' => 'PIMPINAN PERUSAHAAN',
            'alamat' => 'JL. LINTAS SUMATERA KM 12, PALEMBANG',
            'no_telepon' => '0711-000000',
            'email' => 'finance@coaltrans.local',
            'tanggal_berdiri' => '2018-05-12',
            'bidang_usaha' => 'LOGISTIK BATUBARA & ANGKUTAN ALAT BERAT',
        ];
    }

    /**
     * Mengambil profil perusahaan singleton yang aman dipanggil dari view mana pun,
     * termasuk saat tabel belum dimigrasi atau masih kosong saat test.
     */
    public static function profilAktif(): self
    {
        try {
            if (! Schema::hasTable('data_perusahaan')) {
                return new self(self::nilaiBawaan());
            }

            $barisPertama = self::query()->orderBy('id')->first();

            if ($barisPertama !== null) {
                return $barisPertama;
            }

            return self::create(self::nilaiBawaan());
        } catch (\Throwable) {
            return new self(self::nilaiBawaan());
        }
    }
}
