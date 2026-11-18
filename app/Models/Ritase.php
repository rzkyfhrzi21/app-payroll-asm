<?php

namespace App\Models;

use App\Models\Concerns\NormalisasiHurufBesar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: RITASE / SURAT JALAN (TABEL: ritase)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Lembaran Fisik Surat Jalan (SJ / DO) Pengiriman Batubara".
 * Setiap kali supir ditugaskan mengantar batubara dari tambang ke pelabuhan/dermaga,
 * lembaran surat jalan ini diterbitkan. Di dalamnya tercatat rute, nomor truk,
 * uang jalan yang dibawa supir, berat timbangan batubara saat tiba, nama orang
 * yang menerima di dermaga, serta foto cap stempel basah surat jalannya.
 * 
 * Syarat Mutlak Masuk Gaji:
 * Hanya surat jalan yang berstatus 'terverifikasi' (sudah ada foto bukti fisik
 * dan sudah dicek keasliannya oleh admin) yang boleh dihitung masuk ke gaji supir!
 * ============================================================================
 */
#[Fillable([
    'id_periode_gaji',     // Periode penggajian tempat surat jalan ini bernaung
    'id_karyawan',         // Supir yang mengemudikan truk membawa muatan
    'id_truk',             // Truk batubara yang dipakai mengangkut
    'id_rute',             // Rute perjalanan (titik muat tambang s/d titik bongkar dermaga)
    'tanggal_ritase',      // Tanggal surat jalan diterbitkan atau tanggal perjalanan
    'jumlah_rit',          // Berapa kali trip pengiriman (standar bernilai 1 rit)
    'tonase_kg',           // Berat muatan batubara hasil timbangan jembatan dalam satuan Kilogram (kg)
    'volume_liter',        // Volume muatan jika diukur dalam liter (opsional)
    'tarif_per_ton',       // Ongkos angkut per ton sesuai kesepakatan rute
    'total_angkutan',      // Pendapatan kotor angkutan: (tonase_kg / 1000) x tarif_per_ton
    'uang_jalan',          // Uang saku operasional jalan yang diberikan ke supir di awal
    'foto_surat_jalan',    // Daftar berkas foto bukti fisik surat jalan bertanda tangan (format JSON array)
    'catatan_pengantaran', // Catatan perjalanan dari supir (misal: kendala ban pecah atau macet)
    'nama_penerima',       // Nama petugas dermaga/pabrik yang menandatangani tanda terima (otomatis HURUF BESAR)
    'status',              // Status perjalanan: draft, terbit, sedang_dikirim, menunggu_verifikasi, terverifikasi, dibatalkan
])]
class Ritase extends Model
{
    // Menggunakan trait agar nama penerima dan catatan otomatis tersimpan dalam HURUF BESAR
    use NormalisasiHurufBesar;

    // Nama tabel fisik di database MySQL
    protected $table = 'ritase';

    /**
     * Kolom teks yang wajib disimpan dalam format HURUF BESAR otomatis
     * agar pencarian nama penerima barang di pelabuhan menjadi rapi dan konsisten.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = [
        'catatan_pengantaran', // Catatan pengemudi di jalan
        'nama_penerima',       // Nama orang yang tanda tangan tanda terima di pelabuhan
    ];

    /**
     * Daftar Status Perjalanan Surat Jalan (Alur Kerja Operasional)
     * 1. draft               : Admin baru membuat jadwal, belum dikirim ke supir
     * 2. terbit              : Surat jalan terbit, supir melihatnya di aplikasi
     * 3. sedang_dikirim      : Supir menekan tombol "Mulai Kirim" di jalan
     * 4. menunggu_verifikasi : Supir sampai di dermaga, upload foto bukti dan nama penerima
     * 5. terverifikasi       : Admin memeriksa foto & tanda tangan, lalu mengesahkan
     * 6. dibatalkan          : Batal jalan karena kendala armada/cuaca tambang
     */
    public static function daftarStatus(): array
    {
        return [
            'draft'               => 'Draf (Belum Diterbitkan)',
            'terbit'              => 'Terbit (Siap Dikirim Supir)',
            'sedang_dikirim'      => 'Sedang Dikirim',
            'menunggu_verifikasi' => 'Menunggu Verifikasi Admin',
            'terverifikasi'       => 'Berhasil Terkirim & Terverifikasi',
            'dibatalkan'          => 'Dibatalkan',
        ];
    }

    /**
     * Konversi Otomatis Tipe Data (Casting)
     * Seluruh angka disimpan dalam integer bulat murni (KISS & bebas desimal).
     */
    protected function casts(): array
    {
        return [
            'tanggal_ritase' => 'date',    // Format kalender tanggal ritase (YYYY-MM-DD)
            'jumlah_rit'     => 'integer', // Jumlah ritase (angka bulat)
            'tonase_kg'      => 'integer', // Berat muatan dalam kilogram (kg)
            'volume_liter'   => 'integer', // Volume dalam liter
            'tarif_per_ton'  => 'integer', // Tarif rupiah per ton
            'total_angkutan' => 'integer', // Total rupiah kotor
            'uang_jalan'     => 'integer', // Uang jalan rupiah
        ];
    }

    /**
     * Relasi ke Periode Gaji
     * Menghubungkan surat jalan ini ke buku periode bulan berjalan.
     */
    public function periodeGaji(): BelongsTo
    {
        return $this->belongsTo(PeriodeGaji::class, 'id_periode_gaji');
    }

    /**
     * Relasi ke Supir
     * Mengetahui siapa supir yang mengemudikan truk pada surat jalan ini.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    /**
     * Relasi ke Unit Truk
     * Mengetahui nomor pelat truk yang membawa batubara.
     */
    public function truk(): BelongsTo
    {
        return $this->belongsTo(Truk::class, 'id_truk');
    }

    /**
     * Relasi ke Data Rute
     * Mengetahui jalur perjalanan (asal tambang, tujuan dermaga, jarak km).
     */
    public function rute(): BelongsTo
    {
        return $this->belongsTo(Rute::class, 'id_rute');
    }

    /**
     * Nomor Surat Jalan Otomatis (Accessor)
     * Menghasilkan format kode resmi untuk dicetak (contoh: "SJ-PLG-00125").
     */
    public function getNomorSuratJalanAttribute(): string
    {
        $kode = $this->rute?->kode ?? 'RUTE';

        return sprintf('SJ-%s-%05d', $kode, (int) $this->id);
    }

    /**
     * Penanganan Khusus Berkas Foto Bukti Surat Jalan (JSON Array)
     * Mengubah data teks JSON di database menjadi array daftar file gambar di PHP.
     *
     * @return Attribute<array<int, string>, string|null>
     */
    protected function fotoSuratJalan(): Attribute
    {
        return Attribute::make(
            // Saat dibaca oleh program: ubah teks JSON database menjadi daftar array PHP
            get: fn (mixed $nilai): array => self::normalisasiDaftarFoto($nilai),
            // Saat disimpan ke database: ubah array daftar foto menjadi teks JSON aman
            set: function (mixed $nilai): ?string {
                $daftar = self::normalisasiDaftarFoto($nilai);

                return $daftar === []
                    ? null
                    : json_encode($daftar, JSON_UNESCAPED_SLASHES);
            },
        );
    }

    /**
     * Pembersih Daftar Foto Surat Jalan
     * 
     * Alur Kerja Bernomor:
     * (1) Periksa apakah data foto kosong/null.
     * (2) Jika berupa teks JSON, decode menjadi susunan array.
     * (3) Saring dan buang nilai kosong atau duplikat.
     * (4) Kembalikan daftar alamat foto yang bersih dan valid.
     *
     * @return array<int, string>
     */
    public static function normalisasiDaftarFoto(mixed $nilai): array
    {
        // (1) Jika kosong sama sekali, kembalikan array kosong
        if ($nilai === null || $nilai === '') {
            return [];
        }

        $kandidat = [];

        // (2) Cek tipe data: apakah sudah array atau masih teks JSON
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

        // (3) Saring nama file yang kosong dan hindari duplikasi
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

        // (4) Kembalikan hasil filter
        return $terfilter;
    }

    /**
     * Mengambil Foto Utama Pertama
     * Berguna untuk menampilkan thumbnail foto depan surat jalan di tabel daftar.
     */
    public function fotoUtamaSuratJalan(): ?string
    {
        $daftarFoto = $this->foto_surat_jalan;

        return $daftarFoto[0] ?? null;
    }
}
