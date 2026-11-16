<?php

namespace Tests\Feature\Admin;

use App\Models\AngsuranKasbon;
use App\Models\BiayaOperasional;
use App\Models\JenisTruk;
use App\Models\Karyawan;
use App\Models\Kasbon;
use App\Models\Ritase;
use App\Models\Rute;
use App\Models\Truk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * BRIEF_UNIT_TEST.md Bab 3: setiap mutasi admin wajib mengembalikan
 * skema JSON baku (success, type, title, action, status, message, data/errors, timestamp).
 */
class MutasiCrudAdminTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    /**
     * Struktur JSON baku untuk respon sukses mutasi.
     *
     * @return array<int|string, mixed>
     */
    private function strukturSukses(): array
    {
        return ['success', 'type', 'title', 'action', 'status', 'message', 'data', 'redirect', 'timestamp'];
    }

    public function test_simpan_supir_baru_menghasilkan_json_standar_dan_data_uppercase(): void
    {
        $this->actingAs($this->penggunaAdmin());

        $respon = $this->kirimAjax('POST', '/admin/master/employees', [
            'nomor_induk_karyawan' => 'NIP-777',
            'nama_lengkap' => 'budi santoso',
            'status' => 'aktif',
            'persentase_komisi' => '10',
            'potongan_bpjs_bulanan' => '129.000',
            'email' => 'budi.santoso@payroll.test',
            'password' => 'Rahasia123!',
        ]);

        $respon->assertOk()
            ->assertJsonStructure($this->strukturSukses())
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', 'success')
            ->assertJsonPath('action', 'CREATE_KARYAWAN')
            ->assertJsonPath('status', 'COMPLETED')
            ->assertJsonPath('data.nip', 'NIP-777');

        $karyawan = Karyawan::query()->where('nomor_induk_karyawan', 'NIP-777')->firstOrFail();

        $this->assertSame('BUDI SANTOSO', $karyawan->nama_lengkap);
        $this->assertSame(1000, (int) $karyawan->persentase_komisi_bp);
        $this->assertSame(129000, (int) $karyawan->potongan_bpjs_bulanan);
        $this->assertDatabaseHas('users', [
            'email' => 'budi.santoso@payroll.test',
            'role' => 'supir',
        ]);
    }

    public function test_validasi_gagal_mengembalikan_422_dengan_objek_errors(): void
    {
        $this->actingAs($this->penggunaAdmin());

        $respon = $this->kirimAjax('POST', '/admin/master/employees', [
            'nomor_induk_karyawan' => '',
            'nama_lengkap' => 'ab',
            'status' => 'aktif',
            'email' => 'bukan-email',
        ]);

        $respon->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('type', 'error')
            ->assertJsonPath('status', 'VALIDATION_FAILED')
            ->assertJsonStructure(['success', 'type', 'title', 'action', 'status', 'message', 'errors', 'timestamp']);

        $this->assertSame(0, Karyawan::query()->count());
    }

    public function test_tambah_dan_ubah_unit_armada(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/master/trucks', [
            'nomor_polisi' => 'BE 1234 XYZ',
            'merek_model' => 'scania p360',
            'status' => 'aktif',
            'id_jenis_truk' => $data['jenisTruk']->id,
        ])->assertOk()->assertJsonPath('action', 'CREATE_TRUK');

        $truk = Truk::query()->where('nomor_polisi', 'BE 1234 XYZ')->firstOrFail();
        $this->assertSame('SCANIA P360', $truk->merek_model);

        $this->kirimAjax('PATCH', '/admin/master/trucks/'.$truk->id, [
            'nomor_polisi' => 'BE 1234 XYZ',
            'merek_model' => 'scania p400',
            'status' => 'aktif',
            'id_jenis_truk' => $data['jenisTruk']->id,
        ])->assertOk()->assertJsonPath('action', 'UPDATE_TRUK');

        $this->assertSame('SCANIA P400', $truk->fresh()->merek_model);
    }

    public function test_nomor_polisi_duplikat_ditolak(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/master/trucks', [
            'nomor_polisi' => 'BE 8639 AAU',
            'merek_model' => 'HINO 500',
            'status' => 'aktif',
            'id_jenis_truk' => JenisTruk::query()->value('id'),
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_crud_rute_menyimpan_tarif_sebagai_integer(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/master/routes', [
            'kode' => 'RTE-99',
            'material' => 'overburden',
            'tarif_per_ton' => 'Rp 90.000',
            'uang_jalan_default' => '150.000',
            'jarak_km' => '32',
            'status' => 'aktif',
        ])->assertOk()->assertJsonPath('action', 'CREATE_RUTE');

        $rute = Rute::query()->where('kode', 'RTE-99')->firstOrFail();

        $this->assertSame(90000, (int) $rute->tarif_per_ton);
        $this->assertSame(150000, (int) $rute->uang_jalan_default);
        $this->assertSame('OVERBURDEN', $rute->material);
    }

    public function test_crud_jenis_truk_lewat_modal_bersarang(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/master/jenis-truk', [
            'nama' => 'trailer',
            'merek_model' => 'volvo fh16',
            'status' => 'aktif',
        ])->assertOk()->assertJsonPath('action', 'CREATE_JENIS_TRUK');

        $this->assertDatabaseHas('jenis_truk', [
            'nama' => 'TRAILER',
            'merek_model' => 'VOLVO FH16',
        ]);
    }

    public function test_simpan_surat_jalan_menghitung_tonase_dan_total_angkutan(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/operational/trips', [
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-05',
            'jumlah_rit' => 2,
            'tonase_ton' => '28,48',
            'status' => 'terverifikasi',
        ])->assertOk()->assertJsonPath('action', 'CREATE_RITASE');

        $ritase = Ritase::query()->firstOrFail();

        $this->assertSame(28480, (int) $ritase->tonase_kg);
        $this->assertSame(150000, (int) $ritase->tarif_per_ton);
        $this->assertSame(4272000, (int) $ritase->total_angkutan);
        $this->assertSame(200000, (int) $ritase->uang_jalan);
    }

    public function test_simpan_biaya_operasional_dan_tolak_hapus_setelah_disetujui(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/operational/expenses', [
            'id_periode_gaji' => $data['periode']->id,
            'id_truk' => $data['truk']->id,
            'tanggal_biaya' => '2026-01-07',
            'kategori' => 'sparepart',
            'nominal' => '250.000',
            'nomor_nota' => 'nt-001',
            'status' => 'draft',
        ])->assertOk()->assertJsonPath('action', 'CREATE_BIAYA_OPERASIONAL');

        $biaya = BiayaOperasional::query()->firstOrFail();
        $this->assertSame(250000, (int) $biaya->nominal);
        $this->assertSame('NT-001', $biaya->nomor_nota);

        $biaya->update(['status' => 'disetujui']);

        $this->kirimAjax('DELETE', '/admin/operational/expenses/'.$biaya->id)
            ->assertStatus(422)
            ->assertJsonPath('action', 'DELETE_BIAYA_LOCKED');

        $this->assertNotNull(BiayaOperasional::query()->find($biaya->id));
    }

    public function test_simpan_kasbon_membuat_jadwal_cicilan_dan_potong_angsuran(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/operational/loans', [
            'id_karyawan' => $data['karyawan']->id,
            'total_pinjaman' => '1.000.000',
            'jumlah_tenor' => 3,
            'tanggal_pengambilan' => '2026-01-05',
        ])->assertOk()->assertJsonPath('action', 'CREATE_KASBON');

        $kasbon = Kasbon::query()->firstOrFail();
        $this->assertSame([333000, 333000, 334000], $kasbon->angsuran->pluck('nominal')->all());

        $this->kirimAjax('PATCH', '/admin/operational/loans/'.$kasbon->id.'/angsuran/1')
            ->assertOk()
            ->assertJsonPath('action', 'POTONG_ANGSURAN_KASBON');

        $this->assertSame(667000, (int) $kasbon->fresh()->sisa_pinjaman);
        $this->assertSame('terpotong', AngsuranKasbon::query()->where('urutan', 1)->value('status'));

        $this->kirimAjax('DELETE', '/admin/operational/loans/'.$kasbon->id)
            ->assertStatus(422)
            ->assertJsonPath('action', 'DELETE_KASBON_LOCKED');
    }

    public function test_kasbon_melebihi_batas_maksimal_ditolak(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/operational/loans', [
            'id_karyawan' => $data['karyawan']->id,
            'total_pinjaman' => '2.000.000',
            'jumlah_tenor' => 4,
            'tanggal_pengambilan' => '2026-01-05',
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(0, Kasbon::query()->count());
    }

    public function test_update_bpjs_supir_mengubah_potongan_bulanan(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/operational/loans/bpjs', [
            'id_karyawan' => $data['karyawan']->id,
            'potongan_bpjs_bulanan' => '150.000',
        ])->assertOk()->assertJsonPath('action', 'UPDATE_BPJS_SUPIR');

        $this->assertSame(150000, (int) $data['karyawan']->fresh()->potongan_bpjs_bulanan);
    }

    /**
     * Payroll: kalkulasi ulang & pengajuan ke Pimpinan.
     *
     * Dulu endpoint ini 500 karena PayrollController memanggil
     * ResponInteraksiStandar::sukses() yang belum ada; kini sudah dilengkapi
     * dan diuji penuh di sini.
     */
    public function test_kalkulasi_dan_pengajuan_payroll(): void
    {
        $data = $this->siapkanDataMaster();
        $this->buatRitase($data);
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/calculate')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('action', 'calculate')
            ->assertJsonStructure(['success', 'type', 'title', 'action', 'status', 'message', 'data', 'redirect', 'timestamp']);

        $this->assertDatabaseCount('penggajian', 1);

        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')->assertOk();
        $this->assertSame('pending', $data['periode']->fresh()->status);
    }

    /**
     * Admin/HRD hanya boleh mengajukan dan menarik pengajuan.
     * ACC (approve) adalah kewenangan Pimpinan sesuai ACTIVITY_DIAGRAM.drawio.
     */
    public function test_admin_mengajukan_dan_menarik_pengajuan_periode_payroll(): void
    {
        $data = $this->siapkanDataMaster();
        $this->buatRitase($data);
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/calculate')->assertOk();

        // 1. Ajukan ke Pimpinan -> status pending.
        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('pending', $data['periode']->fresh()->status);

        // 2. Tarik pengajuan -> kembali draft.
        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/approval', [
            'mode' => 'reject',
            'alasan_penolakan' => 'TONASE PERLU DIPERIKSA',
        ])->assertOk()->assertJsonPath('action', 'reject');

        $this->assertSame('draft', $data['periode']->fresh()->status);
    }

    /**
     * Admin TIDAK BOLEH memberikan ACC (harus 422/403, bukan 200).
     */
    public function test_admin_tidak_boleh_approve_periode_payroll(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/approval', [
            'mode' => 'approve',
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame('draft', $data['periode']->fresh()->status);
    }

    /**
     * Periode kosong: kalkulasi tidak boleh 500, cukup ringkasan nol.
     */
    public function test_kalkulasi_periode_tanpa_ritase_tetap_ok(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/calculate')
            ->assertOk()
            ->assertJsonPath('data.jumlah_supir', 0)
            ->assertJsonPath('data.total_gaji_bersih', 0);
    }

    public function test_akun_staf_baru_tidak_boleh_membuat_peran_pimpinan(): void
    {
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/master/akun', [
            'name' => 'admin baru',
            'email' => 'admin.baru@payroll.test',
            'role' => 'pimpinan',
            'password' => 'rahasia123',
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertNull(User::query()->where('email', 'admin.baru@payroll.test')->first());
    }

    public function test_akun_staf_baru_dengan_peran_admin_diterima(): void
    {
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/master/akun', [
            'name' => 'admin baru',
            'email' => 'admin.baru@payroll.test',
            'role' => 'admin',
            'password' => 'Rahasia123!',
        ])->assertOk()->assertJsonPath('action', 'CREATE_AKUN_STAF');

        $this->assertDatabaseHas('users', [
            'email' => 'admin.baru@payroll.test',
            'role' => 'admin',
            'name' => 'ADMIN BARU',
        ]);
    }
}
