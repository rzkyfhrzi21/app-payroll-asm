<?php

namespace App\Models\Concerns;

/**
 * ============================================================================
 * TRAIT: NORMALISASI HURUF BESAR (UPPERCASE OTOMATIS)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Stempel Penertib / Mesin Cap Huruf Kapital Otomatis".
 * Sering kali supir atau admin mengetik nama orang, nomor pelat truk, atau alamat
 * dengan campuran huruf besar dan kecil yang tidak rapi (misal: "budi", "BuDi", "BUDI").
 * 
 * Trait ini bertugas mencegat data tepat sebelum disimpan ke MySQL,
 * lalu mengubah semua teks penting tersebut menjadi HURUF BESAR yang seragam dan rapi.
 * Dengan demikian, data di laporan dan slip gaji terlihat profesional tanpa ada
 * duplikasi data akibat perbedaan huruf kecil/besar.
 * ============================================================================
 */
trait NormalisasiHurufBesar
{
    /**
     * Mengambil Daftar Kolom yang Wajib Dibuat Huruf Besar
     * 
     * @return array<int, string>
     */
    protected function kolomHurufBesar(): array
    {
        // Jika model mendefinisikan properti $hurufBesar secara spesifik, pakai daftar itu
        if (property_exists($this, 'hurufBesar') && is_array($this->hurufBesar)) {
            return $this->hurufBesar;
        }

        return [];
    }

    /**
     * Memasang Pengait (Boot Event) Saat Data Hendak Disimpan
     * 
     * Alur Kerja Bernomor:
     * (1) Tangkap momen 'saving' sebelum data ditulis ke dalam database MySQL.
     * (2) Ambil daftar kolom yang wajib dibuat huruf besar.
     * (3) Jika model tidak menentukan kolom secara manual, ambil seluruh kolom $fillable
     *     kecuali kolom-kolom khusus yang dilarang diubah (seperti email & password).
     * (4) Telusuri setiap kolom: jika berisi teks, ubah menjadi huruf kapital memakai mb_strtoupper.
     */
    protected static function bootNormalisasiHurufBesar(): void
    {
        static::saving(function ($model): void {
            // (1) Ambil daftar target kolom
            $daftarKolom = $model->kolomHurufBesar();

            // (2) Bila tidak didefinisikan eksplisit, saring kolom fillable yang bukan pengecualian
            if ($daftarKolom === []) {
                $daftarKolom = array_values(array_filter(
                    $model->getFillable(),
                    fn (string $kolom) => ! in_array($kolom, $model->kolomDikecualikan(), true),
                ));
            }

            // (3) Proses penyeragaman ke huruf besar satu per satu
            foreach ($daftarKolom as $kolom) {
                if (! array_key_exists($kolom, $model->getAttributes())) {
                    continue;
                }

                $nilai = $model->getAttribute($kolom);

                // Hanya proses jika nilainya adalah teks (string)
                if (! is_string($nilai)) {
                    continue;
                }

                // (4) Ubah menjadi huruf besar UTF-8 dan bersihkan spasi di pinggir
                $model->setAttribute($kolom, mb_strtoupper(trim($nilai), 'UTF-8'));
            }
        });
    }

    /**
     * Daftar Kolom yang DILARANG Diubah Menjadi Huruf Besar
     * Kolom sensitif seperti email login, password, dan token sesi dilarang di-uppercase.
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
