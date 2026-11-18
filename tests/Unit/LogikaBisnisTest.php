<?php

namespace Tests\Unit;

use App\Models\Kasbon;
use App\Models\PeriodeGaji;
use App\Models\Truk;
use App\Services\PayrollCalculationService;
use App\Services\PayrollDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * BRIEF_UNIT_TEST.md Bab 1B & Bab 8: unit test logika bisnis, presisi angka,
 * normalisasi teks, dan audit query pada layanan.
 */
class LogikaBisnisTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    /**
     * Rumus slip gaji: angkutan bersih = kotor - potongan operasional,
     * hasil bersih armada = bersih - uang jalan - sparepart,
     * komisi = basis poin (1000 = 10%).
     */
    public function test_hitung_komponen_gaji_memakai_rumus_slip_dan_presisi_bulat(): void
    {
        $layanan = new PayrollCalculationService;

        $hasil = $layanan->hitungKomponenGaji([
            'total_rit' => 10,
            'total_tonase_kg' => 284_800,
            'total_angkutan_kotor' => 41_766_581,
            'potongan_operasional' => 1_000_000,
            'total_uang_jalan' => 2_000_000,
            'biaya_sparepart' => 500_000,
            'persentase_komisi_bp' => 1000,
            'total_tunjangan' => 250_000,
            'potongan_bpjs' => 129_000,
            'potongan_kasbon' => 333_000,
        ]);

        $this->assertSame(40_766_581, $hasil['angkutan_bersih']);
        $this->assertSame(38_266_581, $hasil['hasil_bersih_armada']);
        $this->assertSame(3_826_658, $hasil['gaji_komisi']);
        $this->assertSame(3_614_658, $hasil['gaji_bersih']);

        foreach ($hasil as $kunci => $nilai) {
            $this->assertIsInt($nilai, "Komponen {$kunci} harus bilangan bulat Rupiah.");
        }
    }

    public function test_hitung_komponen_gaji_tidak_pernah_negatif(): void
    {
        $hasil = (new PayrollCalculationService)->hitungKomponenGaji([
            'total_angkutan_kotor' => 100_000,
            'total_uang_jalan' => 500_000,
            'biaya_sparepart' => 500_000,
            'potongan_bpjs' => 129_000,
            'potongan_kasbon' => 999_999,
        ]);

        $this->assertSame(0, $hasil['hasil_bersih_armada']);
        $this->assertSame(0, $hasil['gaji_bersih']);
        $this->assertGreaterThanOrEqual(0, $hasil['gaji_komisi']);
    }

    /**
     * Kasbon: Rp 1.000.000 / 3 -> 333.000 | 333.000 | 334.000.
     */
    public function test_kasbon_dibagi_rata_dan_sisa_masuk_cicilan_terakhir(): void
    {
        $data = $this->siapkanDataMaster();

        $kasbon = app(PayrollDataService::class)->buatKasbon([
            'id_karyawan' => $data['karyawan']->id,
            'total_pinjaman' => 1_000_000,
            'jumlah_tenor' => 3,
            'tanggal_pengambilan' => '2026-01-05',
            'id_periode_gaji_mulai' => $data['periode']->id,
        ]);

        $nominal = $kasbon->angsuran->pluck('nominal')->all();

        $this->assertSame([333_000, 333_000, 334_000], $nominal);
        $this->assertSame(1_000_000, array_sum($nominal));
        $this->assertSame(1_000_000, (int) $kasbon->sisa_pinjaman);
        $this->assertSame(3, $kasbon->angsuran()->count());
    }

    public function test_angsuran_terpotong_mengurangi_sisa_dan_melunasi_kasbon(): void
    {
        $data = $this->siapkanDataMaster();
        $layanan = app(PayrollDataService::class);

        $kasbon = $layanan->buatKasbon([
            'id_karyawan' => $data['karyawan']->id,
            'total_pinjaman' => 900_000,
            'jumlah_tenor' => 3,
            'tanggal_pengambilan' => '2026-01-05',
        ]);

        $layanan->potongAngsuranKasbon($kasbon, 1);
        $this->assertSame(600_000, (int) $kasbon->fresh()->sisa_pinjaman);
        $this->assertSame('approved', $kasbon->fresh()->status);

        $layanan->potongAngsuranKasbon($kasbon, 2);
        $layanan->potongAngsuranKasbon($kasbon, 3);

        $this->assertSame(0, (int) $kasbon->fresh()->sisa_pinjaman);
        $this->assertSame('lunas', $kasbon->fresh()->status);
    }

    public function test_batas_kasbon_dan_tenor_maksimal_sesuai_konstanta_model(): void
    {
        $this->assertSame(1_000_000, Kasbon::BATAS_MAKSIMAL);
        $this->assertSame(3, Kasbon::TENOR_MAKSIMAL);
    }

    /**
     * BRIEF 3.3: seluruh kolom teks entitas disimpan HURUF BESAR,
     * sedangkan email/password/enum tidak boleh diubah.
     */
    public function test_trait_normalisasi_huruf_besar_hanya_menyentuh_kolom_entitas(): void
    {
        $pengguna = $this->penggunaAdmin([
            'name' => '  admin hrd  ',
            'email' => 'Admin.HRD@Payroll.Test',
        ]);

        $truk = Truk::query()->create([
            'nomor_polisi' => ' be 1234 xyz ',
            'merek_model' => ' hino 500 ',
            'status' => 'aktif',
            'id_jenis_truk' => $this->siapkanDataMaster()['jenisTruk']->id,
        ]);

        $this->assertSame('ADMIN HRD', $pengguna->fresh()->name);
        $this->assertSame('Admin.HRD@Payroll.Test', $pengguna->fresh()->email);
        $this->assertSame('BE 1234 XYZ', $truk->fresh()->nomor_polisi);
        $this->assertSame('HINO 500', $truk->fresh()->merek_model);
        $this->assertSame('aktif', $truk->fresh()->status);
    }

    /**
     * BRIEF Bab 8: daftar master tidak boleh memicu N+1.
     */
    public function test_query_daftar_master_tidak_mengalami_n_plus_one(): void
    {
        $data = $this->siapkanDataMaster();

        foreach (range(1, 5) as $urutan) {
            $this->buatRitase($data, ['tanggal_ritase' => '2026-01-0'.$urutan]);
        }

        app(PayrollCalculationService::class)->rekonsiliasiPeriode($data['periode']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $layanan = app(PayrollDataService::class);
        $baris = $layanan->barisPayrollTerpaginasi($data['periode']->fresh(), [], 25);
        $baris->items();

        $jumlahQuery = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(1, $baris->items());
        $this->assertLessThanOrEqual(8, $jumlahQuery, "Terdeteksi N+1: {$jumlahQuery} query untuk 1 baris payroll.");
    }

    public function test_ringkasan_payroll_menghitung_total_dari_baris_periode(): void
    {
        $data = $this->siapkanDataMaster();

        $this->buatRitase($data, ['jumlah_rit' => 2, 'tonase_kg' => 28_480]);
        $this->buatRitase($data, ['jumlah_rit' => 3, 'tonase_kg' => 30_000, 'tanggal_ritase' => '2026-01-06']);

        $layanan = app(PayrollDataService::class);
        app(PayrollCalculationService::class)->rekonsiliasiPeriode($data['periode']);

        $ringkasan = $layanan->ringkasanPayroll($data['periode']->fresh());

        $this->assertSame(1, $ringkasan['drivers']);
        $this->assertSame(5, $ringkasan['rit']);
        $this->assertGreaterThan(0, $ringkasan['cash_required']);
    }

    public function test_format_periode_mengikuti_label_bahasa_indonesia(): void
    {
        $periode = PeriodeGaji::query()->create([
            'kode' => 'JAN-26',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-01-31',
            'tanggal_bayar' => '2026-02-01',
            'status' => 'draft',
        ]);

        $format = app(PayrollDataService::class)->formatPeriode($periode);

        $this->assertSame('JAN-26', $format['code']);
        $this->assertSame('Draf Perhitungan', $format['status']);
        $this->assertStringContainsString('Feb 2026', $format['cut_off']);
    }

    public function test_siklus_periode_dapat_mengonversi_kode_periode_ke_tanggal_awal_bulan(): void
    {
        $nov = \App\Support\SiklusPeriodeGaji::tanggalDariKode('Nov-26');
        $this->assertNotNull($nov);
        $this->assertSame('2026-11-01', $nov->toDateString());

        $des = \App\Support\SiklusPeriodeGaji::tanggalDariKode('Des-26');
        $this->assertNotNull($des);
        $this->assertSame('2026-12-01', $des->toDateString());

        $jan = \App\Support\SiklusPeriodeGaji::tanggalDariKode('Jan-26');
        $this->assertNotNull($jan);
        $this->assertSame('2026-01-01', $jan->toDateString());

        $this->assertNull(\App\Support\SiklusPeriodeGaji::tanggalDariKode(''));
        $this->assertNull(\App\Support\SiklusPeriodeGaji::tanggalDariKode('TIDAK_VALID'));
    }
}
