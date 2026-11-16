<?php

namespace App\Models\Concerns;

/**
 * Menyeragamkan seluruh kolom teks (varchar/text) menjadi HURUF BESAR
 * sebelum disimpan ke database.
 *
 * Dipakai lewat properti $hurufBesar pada model:
 *   protected array $hurufBesar = ['nama_lengkap', 'jabatan'];
 */
trait NormalisasiHurufBesar
{
    /**
     * Daftar kolom yang dinormalisasi. Bila kosong, seluruh kolom string
     * yang termasuk $fillable akan dinormalisasi otomatis.
     *
     * @return array<int, string>
     */
    protected function kolomHurufBesar(): array
    {
        if (property_exists($this, 'hurufBesar') && is_array($this->hurufBesar)) {
            return $this->hurufBesar;
        }

        return [];
    }

    protected static function bootNormalisasiHurufBesar(): void
    {
        static::saving(function ($model): void {
            $daftarKolom = $model->kolomHurufBesar();

            // Bila tidak didefinisikan eksplisit, ambil kolom string dari casts/fillable.
            if ($daftarKolom === []) {
                $daftarKolom = array_values(array_filter(
                    $model->getFillable(),
                    fn (string $kolom) => ! in_array($kolom, $model->kolomDikecualikan(), true),
                ));
            }

            foreach ($daftarKolom as $kolom) {
                if (! array_key_exists($kolom, $model->getAttributes())) {
                    continue;
                }

                $nilai = $model->getAttribute($kolom);

                if (! is_string($nilai)) {
                    continue;
                }

                $model->setAttribute($kolom, mb_strtoupper(trim($nilai), 'UTF-8'));
            }
        });
    }

    /**
     * Kolom yang tidak boleh diubah kapitalisasinya (mis. email, password, enum).
     *
     * @return array<int, string>
     */
    protected function kolomDikecualikan(): array
    {
        return property_exists($this, 'kolomHurufBesarDikecualikan')
            ? $this->kolomHurufBesarDikecualikan
            : [];
    }
}
