<?php

namespace Tests\Feature\Admin;

use App\Models\PeriodeGaji;
use App\Models\Ritase;
use App\Services\PayrollDataService;
use App\Services\PeriodeGajiLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Siklus periode gaji mengikuti tutup buku manual (bukan paksa tanggal 1 s/d akhir bulan).
 *
 * Skenario acuan: periode dibuka 21 Okt 2026 lalu ditutup buku 18 Nov 2026,
 * sehingga kode periode final menjadi Nov-26 dan periode berikutnya mulai 19 Nov 2026.
 */
class SiklusPeriodeBukuTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function layanan(): PeriodeGajiLifecycleService
    {
        return app(PeriodeGajiLifecycleService::class);
    }

    public function test_periode_baru_dibuka_dengan_tanggal_selesai_kosong(): void
    {
        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));

        $this->assertSame('2026-10-21', $periode->tanggal_mulai->toDateString());
        $this->assertNull($periode->tanggal_selesai, 'Periode baru wajib berstatus berjalan (tanggal_selesai NULL).');
        $this->assertNull($periode->tanggal_bayar);
        $this->assertSame('draft', $periode->status);
    }

    public function test_hanya_boleh_satu_periode_berjalan(): void
    {
        $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));

        $this->expectException(ValidationException::class);
        $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-22'));
    }

    public function test_tutup_buku_mengisi_tanggal_selesai_dan_membuat_periode_berikutnya(): void
    {
        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));

        $hasil = $this->layanan()->tutupBuku($periode, Carbon::parse('2026-11-18'));

        // Kode final mengikuti bulan tanggal tutup buku.
        $this->assertSame('Nov-26', $hasil->kode);
        $this->assertSame('2026-10-21', $hasil->tanggal_mulai->toDateString());
        $this->assertSame('2026-11-18', $hasil->tanggal_selesai->toDateString());
        $this->assertSame('2026-11-18', $hasil->tanggal_bayar->toDateString());

        // Periode berikutnya otomatis dibuka mulai H+1 agar tidak ada tanggal yang hilang.
        $berikutnya = $this->layanan()->ambilPeriodeBerjalan();
        $this->assertNotNull($berikutnya, 'Tutup buku wajib otomatis membuka periode berikutnya.');
        $this->assertSame('2026-11-19', $berikutnya->tanggal_mulai->toDateString());
        $this->assertNull($berikutnya->tanggal_selesai);
        $this->assertSame(2, PeriodeGaji::query()->count());
    }

    public function test_tutup_buku_ditolak_bila_tanggal_mendahului_tanggal_mulai(): void
    {
        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-11-19'));

        $this->expectException(ValidationException::class);
        $this->layanan()->tutupBuku($periode, Carbon::parse('2026-11-18'));
    }

    public function test_tutup_buku_dua_kali_ditolak(): void
    {
        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));
        $this->layanan()->tutupBuku($periode, Carbon::parse('2026-11-18'));

        $this->expectException(ValidationException::class);
        $this->layanan()->tutupBuku($periode->fresh(), Carbon::parse('2026-11-20'));
    }

    public function test_buka_kembali_periode_membatalkan_periode_kosong_sesudahnya(): void
    {
        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));
        $ditutup = $this->layanan()->tutupBuku($periode, Carbon::parse('2026-11-18'));

        $berikutnya = $this->layanan()->ambilPeriodeBerjalan();
        $this->assertNotNull($berikutnya);

        // Periode sesudahnya masih kosong -> boleh dibuka kembali.
        $dibuka = $this->layanan()->bukaKembaliPeriode($ditutup);

        $this->assertNull($dibuka->tanggal_selesai, 'Periode harus kembali berjalan.');
        $this->assertNull(
            PeriodeGaji::query()->find($berikutnya->id),
            'Periode kosong sesudahnya harus dibatalkan agar tidak ada dua periode berjalan.',
        );
    }

    public function test_buka_kembali_ditolak_bila_periode_sesudahnya_sudah_berisi_ritase(): void
    {
        $data = $this->siapkanDataMaster();

        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));
        $ditutup = $this->layanan()->tutupBuku($periode, Carbon::parse('2026-11-18'));

        $berikutnya = $this->layanan()->ambilPeriodeBerjalan();
        $this->assertNotNull($berikutnya);

        // Isi periode sesudahnya dengan satu surat jalan.
        Ritase::query()->create([
            'id_periode_gaji' => $berikutnya->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-11-20',
            'jumlah_rit' => 1,
            'tonase_kg' => 25000,
            'tarif_per_ton' => 150000,
            'total_angkutan' => 3750000,
            'uang_jalan' => 200000,
            'status' => 'terverifikasi',
        ]);

        $this->expectException(ValidationException::class);
        $this->layanan()->bukaKembaliPeriode($ditutup);
    }

    public function test_kode_periode_bentrok_diberi_sufiks(): void
    {
        // Kode "Nov-26" sudah dipakai periode lain.
        PeriodeGaji::query()->create([
            'kode' => 'Nov-26',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'draft',
        ]);

        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));
        $hasil = $this->layanan()->tutupBuku($periode, Carbon::parse('2026-11-18'));

        $this->assertSame('Nov-26-2', $hasil->kode, 'Kode bentrok wajib diberi sufiks agar tetap unik.');
    }

    public function test_label_periode_berjalan_memakai_kata_sekarang(): void
    {
        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));

        $label = app(PayrollDataService::class)->formatPeriode($periode);

        $this->assertTrue($label['terbuka']);
        $this->assertSame('21 Okt 2026 — Sekarang', $label['range']);
        $this->assertSame('Belum ditutup', $label['cut_off']);
    }

    public function test_label_periode_ditutup_memakai_rentang_lengkap(): void
    {
        $periode = $this->layanan()->bukaPeriodeBaru(Carbon::parse('2026-10-21'));
        $ditutup = $this->layanan()->tutupBuku($periode, Carbon::parse('2026-11-18'));

        $label = app(PayrollDataService::class)->formatPeriode($ditutup->fresh());

        $this->assertFalse($label['terbuka']);
        $this->assertSame('21 Okt 2026 — 18 Nov 2026', $label['range']);
    }

    public function test_rentang_periode_tidak_lagi_dipaksa_tanggal_satu_sampai_akhir_bulan(): void
    {
        $periode = PeriodeGaji::query()->create([
            'kode' => 'UJI-CUTOFF',
            'tanggal_mulai' => '2026-10-21',
            'tanggal_selesai' => '2026-11-18',
            'status' => 'draft',
        ]);

        $this->assertSame('2026-10-21', $periode->fresh()->tanggal_mulai->toDateString());
        $this->assertSame('2026-11-18', $periode->fresh()->tanggal_selesai->toDateString());
    }
}
