<?php

namespace Tests\Feature\Admin;

use App\Models\AngsuranKasbon;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\PeriodeGaji;
use App\Models\Penggajian;
use App\Services\PayrollDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Optimasi halaman Kasbon & Potongan: ringkasan dihitung di SQL dan tabel
 * komponen supir terpaginasi AJAX. Hasilnya wajib identik dengan versi lama.
 */
class OptimasiHalamanKasbonTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    private function buatKasbon(Karyawan $karyawan, int $total, int $sisa, string $status, array $statusAngsuran = []): Kasbon
    {
        $kasbon = Kasbon::query()->create([
            'id_karyawan' => $karyawan->id,
            'tanggal_pengambilan' => '2026-01-06',
            'total_pinjaman' => $total,
            'nominal_angsuran' => intdiv($total, 3),
            'jumlah_tenor' => 3,
            'sisa_pinjaman' => $sisa,
            'keterangan' => 'UJI',
            'status' => $status,
        ]);

        foreach ($statusAngsuran as $indeks => $statusAngsuranIni) {
            AngsuranKasbon::query()->create([
                'id_kasbon' => $kasbon->id,
                'urutan' => $indeks + 1,
                'nominal' => 100000 + ($indeks * 1000),
                'tanggal_rencana' => '2026-0'.($indeks + 2).'-01',
                'status' => $statusAngsuranIni,
            ]);
        }

        return $kasbon;
    }

    public function test_ringkasan_sql_identik_dengan_ringkasan_lama_pada_data_campuran(): void
    {
        $data = $this->siapkanDataMaster();
        $budi = $data['karyawan'];
        $siti = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-002', 'nama_lengkap' => 'SITI AMINAH']);
        $joko = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-003', 'nama_lengkap' => 'JOKO']);

        // Budi punya dua kasbon berjalan (supir ganda harus dihitung satu kali).
        $this->buatKasbon($budi, 900000, 600000, 'approved', ['terpotong', 'terjadwal', 'terjadwal']);
        $this->buatKasbon($budi, 300000, 300000, 'pending', ['terjadwal']);
        // Siti: berjalan tanpa angsuran terjadwal (semua terpotong/dibatalkan).
        $this->buatKasbon($siti, 600000, 100000, 'approved', ['terpotong', 'dibatalkan']);
        // Joko: lunas dan ditolak tidak masuk hitungan aktif, tapi masuk total/jumlah.
        $this->buatKasbon($joko, 450000, 0, 'lunas', ['terpotong', 'terpotong', 'terpotong']);
        $this->buatKasbon($joko, 200000, 200000, 'rejected', ['terjadwal']);
        // Kasbon yang sudah dihapus lunak tidak boleh ikut dihitung.
        $this->buatKasbon($siti, 999000, 999000, 'approved', ['terjadwal'])->delete();

        $layanan = app(PayrollDataService::class);

        $lama = $layanan->ringkasanKasbon($layanan->daftarKasbon());
        $baru = $layanan->ringkasanKasbonDariSql();

        $this->assertSame($lama, $baru);
        // Pastikan datanya memang bermakna, bukan sama-sama nol.
        $this->assertSame(5, $baru['jumlah_kasbon']);
        $this->assertSame(2, $baru['active_drivers']);
        $this->assertGreaterThan(0, $baru['cicilan_bulan_ini']);
    }

    public function test_ringkasan_sql_identik_saat_tabel_kosong(): void
    {
        $layanan = app(PayrollDataService::class);

        $this->assertSame(
            $layanan->ringkasanKasbon($layanan->daftarKasbon()),
            $layanan->ringkasanKasbonDariSql(),
        );
    }

    public function test_halaman_kasbon_tidak_menjalankan_query_per_baris(): void
    {
        $data = $this->siapkanDataMaster();

        for ($i = 2; $i <= 12; $i++) {
            $supir = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-1'.$i, 'nama_lengkap' => 'SUPIR '.$i]);
            \App\Models\Truk::query()->create([
                'nomor_polisi' => 'BE '.(1000 + $i).' XX',
                'merek_model' => 'HINO',
                'status' => 'aktif',
                'id_karyawan' => $supir->id,
                'id_jenis_truk' => $data['jenisTruk']->id,
            ]);
            $this->buatKasbon($supir, 300000, 300000, 'approved', ['terjadwal']);
        }

        $this->actingAs($this->penggunaAdmin());

        $jumlahQuery = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('/admin/operational/loans')->assertOk();

            return count(DB::getQueryLog());
        };

        $jumlahQuery(); // pemanasan: request pertama membuat data_perusahaan bawaan
        $sedikit = $jumlahQuery();

        for ($i = 13; $i <= 40; $i++) {
            $supir = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-1'.$i, 'nama_lengkap' => 'SUPIR '.$i]);
            \App\Models\Truk::query()->create([
                'nomor_polisi' => 'BE '.(1000 + $i).' XX',
                'merek_model' => 'HINO',
                'status' => 'aktif',
                'id_karyawan' => $supir->id,
                'id_jenis_truk' => $data['jenisTruk']->id,
            ]);
            $this->buatKasbon($supir, 300000, 300000, 'approved', ['terjadwal']);
        }

        $banyak = $jumlahQuery();

        $this->assertSame($sedikit, $banyak, 'Jumlah query halaman kasbon tidak boleh bertambah mengikuti jumlah data.');
    }

    public function test_komponen_supir_terpaginasi_membawa_kolom_sama_dengan_versi_lama(): void
    {
        $data = $this->siapkanDataMaster();
        $siti = $this->buatSupir(['nomor_induk_karyawan' => 'NIP-002', 'nama_lengkap' => 'SITI AMINAH', 'status' => 'nonaktif']);

        $this->buatKasbon($data['karyawan'], 900000, 600000, 'approved', ['terpotong', 'terjadwal', 'terjadwal']);
        $this->buatKasbon($data['karyawan'], 300000, 0, 'lunas', ['terpotong']);
        $this->buatKasbon($siti, 300000, 300000, 'pending', ['terjadwal']);

        Penggajian::query()->create([
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'total_rit' => 1,
            'total_tonase_kg' => 1000,
            'total_angkutan_kotor' => 1000,
            'potongan_operasional' => 0,
            'angkutan_bersih' => 1000,
            'total_uang_jalan' => 0,
            'biaya_sparepart' => 0,
            'hasil_bersih_armada' => 1000,
            'persentase_komisi_bp' => 1000,
            'gaji_komisi' => 100,
            'potongan_bpjs' => 0,
            'potongan_kasbon' => 50,
            'gaji_bersih' => 50,
            'status' => 'draft',
        ]);

        $layanan = app(PayrollDataService::class);

        $lama = collect($layanan->dataKomisiDanPotongan())->keyBy('id');
        $halaman = $layanan->komponenSupirTerpaginasi(['cari' => '', 'status' => 'all'], 25);

        $this->assertSame($lama->count(), $halaman->total());

        foreach ($halaman->items() as $baris) {
            $this->assertSame($lama[$baris['id']], $baris);
        }

        $this->assertSame(
            [$siti->id],
            collect($layanan->komponenSupirTerpaginasi(['cari' => 'SITI', 'status' => 'nonaktif'], 25)->items())->pluck('id')->all(),
        );
    }

    public function test_komponen_supir_dibatasi_per_halaman_dan_dilayani_endpoint_ajax(): void
    {
        $this->siapkanDataMaster();

        for ($i = 2; $i <= 30; $i++) {
            $this->buatSupir(['nomor_induk_karyawan' => 'NIP-2'.$i, 'nama_lengkap' => 'SUPIR '.$i]);
        }

        $this->actingAs($this->penggunaAdmin());

        $respon = $this->kirimAjax('POST', '/admin/interaksi/tabel-ajax', [
            'entitas' => 'komponen_supir',
            'per_halaman' => 10,
            'halaman' => 2,
            'kolom' => 7,
            'filter' => ['status' => 'all'],
        ]);

        $respon->assertOk()
            ->assertJsonPath('data.meta.per_page', 10)
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.total', 30)
            ->assertJsonPath('data.meta.from', 11);

        $this->assertSame(10, substr_count($respon->json('data.rows_html'), 'data-centang-bpjs'));

        $this->kirimAjax('POST', '/admin/interaksi/tabel-ajax', ['entitas' => 'komponen_supir', 'cari' => 'SUPIR 30'])
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_komponen_supir_tidak_boleh_dibaca_peran_supir(): void
    {
        $this->actingAs($this->penggunaSupir());

        $this->kirimAjax('POST', '/my/interaksi/tabel', ['entitas' => 'komponen_supir'])
            ->assertForbidden();
    }

    public function test_halaman_loans_hanya_merender_satu_halaman_komponen_supir(): void
    {
        $this->siapkanDataMaster();

        for ($i = 2; $i <= 40; $i++) {
            $this->buatSupir(['nomor_induk_karyawan' => 'NIP-3'.$i, 'nama_lengkap' => 'SUPIR '.$i]);
        }

        $this->actingAs($this->penggunaAdmin());

        $html = $this->get('/admin/operational/loans')->assertOk()->getContent();

        $this->assertLessThanOrEqual(25, substr_count($html, 'data-centang-bpjs'));
    }
}



