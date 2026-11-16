<?php

namespace Tests\Feature;

use App\Models\Penggajian;
use App\Models\Ritase;
use App\Models\Truk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AlurSuratJalanDanAccTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    public function test_admin_membuat_surat_jalan_dengan_status_default_draft_dan_opsi_terbit(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/operational/trips', [
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-05',
            'jumlah_rit' => 1,
            'tonase_ton' => '25,00',
        ])->assertOk()->assertJsonPath('action', 'CREATE_RITASE');

        $ritaseDraft = Ritase::query()->firstOrFail();
        $this->assertSame('draft', $ritaseDraft->status);

        $htmlAdmin = $this->get('/admin/operational/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Draf (Belum Diterbitkan)', $htmlAdmin);
        $this->assertStringContainsString('Terbitkan ke Supir', $htmlAdmin);

        // Admin dapat membuat SJ langsung dengan status terbit.
        $this->kirimAjax('POST', '/admin/operational/trips', [
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-06',
            'jumlah_rit' => 1,
            'tonase_ton' => '26,00',
            'status' => 'terbit',
        ])->assertOk()->assertJsonPath('action', 'CREATE_RITASE');

        $ritaseTerbit = Ritase::query()->where('id', '!=', $ritaseDraft->id)->firstOrFail();
        $this->assertSame('terbit', $ritaseTerbit->status);

        // Di Portal Supir: SJ draft tidak tampil & ditolak 403; SJ terbit tampil.
        $this->actingAs($data['karyawan']->pengguna);
        $htmlSupir = $this->get('/my/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString($ritaseDraft->nomor_surat_jalan, $htmlSupir);
        $this->assertStringContainsString($ritaseTerbit->nomor_surat_jalan, $htmlSupir);

        $this->kirimAjax('PATCH', '/my/trips/'.$ritaseDraft->id.'/mulai-kirim')
            ->assertStatus(403);
        $this->patchJson('/my/trips/'.$ritaseDraft->id.'/konfirmasi', [
            'tonase_ton' => '25,00',
            'nama_penerima' => 'BUDI',
        ])->assertStatus(403);
    }

    public function test_supir_wajib_mengisi_nama_penerima_dan_foto_saat_konfirmasi_sj(): void
    {
        Storage::fake('public');

        $data = $this->siapkanDataMaster();
        $ritase = $this->buatRitase($data, [
            'status' => 'terbit',
            'foto_surat_jalan' => null,
            'nama_penerima' => null,
        ]);

        $penggunaSupir = $data['karyawan']->pengguna;
        $this->actingAs($penggunaSupir);

        $htmlSupir = $this->get('/my/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('name="nama_penerima"', $htmlSupir);
        $this->assertStringContainsString('name="foto_surat_jalan[]"', $htmlSupir);
        $this->assertStringContainsString('Terbit (Siap Dikirim)', $htmlSupir);
        $this->assertStringContainsString('Mulai Kirim', $htmlSupir);
        $this->assertStringContainsString('Konfirmasi Sampai', $htmlSupir);

        // Tanpa nama_penerima dan tanpa foto -> wajib ditolak 422.
        $this->patchJson('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '28,50',
        ])->assertStatus(422)->assertJsonValidationErrors(['nama_penerima', 'foto_surat_jalan']);

        // Dengan nama_penerima dan foto tunggal (backward-compatible) -> berhasil menjadi menunggu_verifikasi.
        $fotoBukti = UploadedFile::fake()->image('surat-jalan.png', 200, 200);
        $this->patch('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '28,50',
            'nama_penerima' => 'budi jetty 2',
            'catatan_pengantaran' => 'Bongkar aman',
            'foto_surat_jalan' => $fotoBukti,
        ], [
            'Accept' => 'application/json',
            'X-Interaksi-Ajax' => '1',
        ])->assertOk()->assertJsonPath('action', 'CONFIRM_DELIVERY_TRIP');

        $ritase->refresh();
        $this->assertSame('menunggu_verifikasi', $ritase->status);
        $this->assertSame('BUDI JETTY 2', $ritase->nama_penerima);
        $this->assertIsArray($ritase->foto_surat_jalan);
        $this->assertCount(1, $ritase->foto_surat_jalan);
        $this->assertStringEndsWith('.webp', $ritase->foto_surat_jalan[0]);
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $ritase->foto_surat_jalan[0]), '/'));

        $htmlSetelahKonfirmasi = $this->get('/my/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Menunggu Verifikasi Admin', $htmlSetelahKonfirmasi);
        $this->assertStringContainsString('BUDI JETTY 2', $htmlSetelahKonfirmasi);
    }

    public function test_admin_diblokir_ajukan_payroll_sebelum_sj_terverifikasi_lalu_berhasil(): void
    {
        Storage::fake('public');

        $data = $this->siapkanDataMaster();
        $ritase = $this->buatRitase($data, [
            'status' => 'terbit',
            'foto_surat_jalan' => null,
        ]);

        // Ajukan saat SJ masih berstatus terbit -> wajib ditolak 422.
        $this->actingAs($this->penggunaAdmin());
        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame('draft', $data['periode']->fresh()->status);

        // Supir konfirmasi SJ -> menunggu_verifikasi.
        $this->actingAs($data['karyawan']->pengguna);
        $this->patch('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '28,48',
            'nama_penerima' => 'PETUGAS TIMBANGAN',
            'foto_surat_jalan' => UploadedFile::fake()->image('tiket.png', 200, 200),
        ], [
            'Accept' => 'application/json',
            'X-Interaksi-Ajax' => '1',
        ])->assertOk();

        // Admin cek halaman payroll -> ada indikator SJ belum verifikasi, tidak ada persentase 10%.
        $this->actingAs($this->penggunaAdmin());
        $htmlPayrollAdmin = $this->get('/admin/payroll?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('surat jalan', strtolower($htmlPayrollAdmin));
        $this->assertStringContainsString('belum', strtolower($htmlPayrollAdmin));
        $this->assertStringNotContainsString('10%', $htmlPayrollAdmin);

        // Ajukan sebelum verifikasi SJ -> ditolak 422.
        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame('draft', $data['periode']->fresh()->status);

        // Admin verifikasi SJ menjadi terverifikasi.
        $this->kirimAjax('PATCH', '/admin/operational/trips/status-massal', [
            'ids' => [$ritase->id],
            'status' => 'terverifikasi',
        ])->assertOk()->assertJsonPath('action', 'BULK_STATUS_RITASE');
        $this->assertSame('terverifikasi', $ritase->fresh()->status);

        // Hitung Gaji -> Ajukan ke Pimpinan -> berhasil menjadi pending.
        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/calculate')->assertOk();
        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('pending', $data['periode']->fresh()->status);
    }

    public function test_pimpinan_acc_satuan_acc_checkbox_revisi_dan_pencairan_paid(): void
    {
        $data = $this->siapkanDataMaster();

        $supirKedua = $this->buatSupir([
            'nomor_induk_karyawan' => 'NIP-002',
            'nama_lengkap' => 'AGUS SAPUTRA',
        ]);
        $trukKedua = Truk::query()->create([
            'nomor_polisi' => 'BE 9002 CCU',
            'merek_model' => 'HINO 500',
            'status' => 'aktif',
            'id_karyawan' => $supirKedua->id,
            'id_jenis_truk' => $data['jenisTruk']->id,
        ]);

        $supirKetiga = $this->buatSupir([
            'nomor_induk_karyawan' => 'NIP-003',
            'nama_lengkap' => 'DEDI KURNIAWAN',
        ]);
        $trukKetiga = Truk::query()->create([
            'nomor_polisi' => 'BE 9003 DDU',
            'merek_model' => 'HINO 500',
            'status' => 'aktif',
            'id_karyawan' => $supirKetiga->id,
            'id_jenis_truk' => $data['jenisTruk']->id,
        ]);

        $this->buatRitase($data, ['status' => 'terverifikasi']);
        $this->buatRitase([
            'periode' => $data['periode'],
            'karyawan' => $supirKedua,
            'truk' => $trukKedua,
            'rute' => $data['rute'],
        ], ['status' => 'terverifikasi']);
        $this->buatRitase([
            'periode' => $data['periode'],
            'karyawan' => $supirKetiga,
            'truk' => $trukKetiga,
            'rute' => $data['rute'],
        ], ['status' => 'terverifikasi']);

        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);
        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/calculate')->assertOk();
        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')->assertOk();

        $daftarGaji = Penggajian::query()
            ->where('id_periode_gaji', $data['periode']->id)
            ->orderBy('id')
            ->get();
        $this->assertCount(3, $daftarGaji);

        // Pimpinan menolak dulu agar catatan_penolakan tampil di Admin & Pimpinan.
        $pimpinan = $this->penggunaPimpinan();
        $this->actingAs($pimpinan);
        $catatanRevisi = 'Cek ulang nominal kasbon supir kedua.';
        $this->kirimAjax('PATCH', '/executive/payroll/'.$data['periode']->kode.'/reject', [
            'alasan_penolakan' => $catatanRevisi,
        ])->assertOk();

        $htmlPimpinanRevisi = $this->get('/executive/payroll?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($catatanRevisi, $htmlPimpinanRevisi);
        $this->assertStringNotContainsString('10%', $htmlPimpinanRevisi);

        $this->actingAs($admin);
        $htmlAdminRevisi = $this->get('/admin/payroll?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($catatanRevisi, $htmlAdminRevisi);

        // Admin mengajukan ulang.
        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')->assertOk();

        // Pimpinan ACC satuan supir 1.
        $this->actingAs($pimpinan);
        $gajiPertama = $daftarGaji[0];
        $gajiKedua = $daftarGaji[1];
        $gajiKetiga = $daftarGaji[2];

        $this->kirimAjax('PATCH', '/executive/payroll/'.$data['periode']->kode.'/approve', [
            'id_penggajian' => $gajiPertama->id,
        ])->assertOk()->assertJsonPath('action', 'EXECUTIVE_APPROVE_DRIVER_PAYROLL');

        $this->assertSame('approved', $gajiPertama->fresh()->status);
        $this->assertSame('draft', $gajiKedua->fresh()->status);
        $this->assertSame('draft', $gajiKetiga->fresh()->status);
        $this->assertSame('pending', $data['periode']->fresh()->status);

        // Pimpinan ACC batch checkbox untuk supir 2 & 3 -> seluruh supir approved -> periode approved.
        $this->kirimAjax('POST', '/executive/payroll/'.$data['periode']->kode.'/approve-batch', [
            'ids' => [$gajiKedua->id, $gajiKetiga->id],
        ])->assertOk()->assertJsonPath('action', 'EXECUTIVE_BATCH_APPROVE_PAYROLL');

        $this->assertSame('approved', $gajiKedua->fresh()->status);
        $this->assertSame('approved', $gajiKetiga->fresh()->status);
        $this->assertSame('approved', $data['periode']->fresh()->status);

        $htmlSiapCair = $this->get('/executive/payroll?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Disetujui (Siap Cair)', $htmlSiapCair);
        $this->assertStringContainsString('Cairkan', $htmlSiapCair);

        // Cairkan satuan supir 1 -> hanya supir 1 paid.
        $this->kirimAjax('PATCH', '/executive/payroll/'.$data['periode']->kode.'/disburse', [
            'ids' => [$gajiPertama->id],
        ])->assertOk()->assertJsonPath('action', 'EXECUTIVE_DISBURSE_PAYROLL');

        $this->assertSame('paid', $gajiPertama->fresh()->status);
        $this->assertSame('approved', $gajiKedua->fresh()->status);
        $this->assertSame('approved', $data['periode']->fresh()->status);

        // Cairkan sisanya -> semua paid & periode paid.
        $this->kirimAjax('PATCH', '/executive/payroll/'.$data['periode']->kode.'/disburse')
            ->assertOk()
            ->assertJsonPath('action', 'EXECUTIVE_DISBURSE_PAYROLL');

        $this->assertSame('paid', $gajiKedua->fresh()->status);
        $this->assertSame('paid', $gajiKetiga->fresh()->status);
        $this->assertSame('paid', $data['periode']->fresh()->status);

        $htmlSudahCair = $this->get('/executive/payroll?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Direksi Only', $htmlSudahCair);
        $this->assertStringContainsString('Otorisasi Penggajian', $htmlSudahCair);
        $this->assertStringContainsString('Sudah Dicairkan', $htmlSudahCair);
        $this->assertStringNotContainsString('(Sudah Dicairkan)', $htmlSudahCair);
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*readonly[^>]*>[\s\S]*?Sudah Dicairkan/i', $htmlSudahCair);

        // Periode yang sudah paid tidak dapat dicairkan ulang atau ditolak lagi.
        $this->kirimAjax('PATCH', '/executive/payroll/'.$data['periode']->kode.'/disburse')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->kirimAjax('PATCH', '/executive/payroll/'.$data['periode']->kode.'/reject', [
            'alasan_penolakan' => 'Coba tolak setelah paid',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_supir_dapat_mengunggah_banyak_foto_surat_jalan_dan_terbaca_di_supir_dan_admin(): void
    {
        Storage::fake('public');

        $data = $this->siapkanDataMaster();
        $ritase = $this->buatRitase($data, [
            'status' => 'terbit',
            'foto_surat_jalan' => null,
            'nama_penerima' => null,
        ]);

        $this->actingAs($data['karyawan']->pengguna);

        $fotoSatu = UploadedFile::fake()->image('surat-jalan-ttd.png', 240, 240);
        $fotoDua = UploadedFile::fake()->image('barang-diterima.jpg', 240, 240);

        $this->patch('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '29,10',
            'nama_penerima' => 'HENDRA STOCKPILE',
            'catatan_pengantaran' => 'SJ dan muatan lengkap',
            'foto_surat_jalan' => [$fotoSatu, $fotoDua],
        ], [
            'Accept' => 'application/json',
            'X-Interaksi-Ajax' => '1',
        ])->assertOk()->assertJsonPath('action', 'CONFIRM_DELIVERY_TRIP');

        $ritase->refresh();
        $nilaiMentah = json_decode((string) $ritase->getRawOriginal('foto_surat_jalan'), true);
        $this->assertIsArray($nilaiMentah);
        $this->assertCount(2, $nilaiMentah);
        $this->assertIsArray($ritase->foto_surat_jalan);
        $this->assertCount(2, $ritase->foto_surat_jalan);

        foreach ($ritase->foto_surat_jalan as $pathFoto) {
            $this->assertStringEndsWith('.webp', $pathFoto);
            Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $pathFoto), '/'));
        }

        $htmlSupir = $this->get('/my/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('2 Foto Bukti', $htmlSupir);
        $this->assertStringContainsString('daftar_foto_surat_jalan', $htmlSupir);
        $this->assertStringContainsString(basename($ritase->foto_surat_jalan[0]), $htmlSupir);
        $this->assertStringContainsString(basename($ritase->foto_surat_jalan[1]), $htmlSupir);

        $this->actingAs($this->penggunaAdmin());
        $htmlAdmin = $this->get('/admin/operational/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('2 Foto Bukti', $htmlAdmin);
        $this->assertStringContainsString('daftar_foto_surat_jalan', $htmlAdmin);
        $this->assertStringContainsString(basename($ritase->foto_surat_jalan[0]), $htmlAdmin);
        $this->assertStringContainsString(basename($ritase->foto_surat_jalan[1]), $htmlAdmin);
    }

    public function test_validasi_multi_foto_menolak_ukuran_lebih_dari_2_mb_dan_hapus_foto_selektif(): void
    {
        Storage::fake('public');

        $data = $this->siapkanDataMaster();
        $ritase = $this->buatRitase($data, [
            'status' => 'terbit',
            'foto_surat_jalan' => null,
            'nama_penerima' => null,
        ]);

        $this->actingAs($data['karyawan']->pengguna);

        // Salah satu berkas melebihi 2 MB (2048 KB) -> wajib ditolak 422.
        $fotoValid = UploadedFile::fake()->image('sj-kecil.png', 150, 150)->size(300);
        $fotoTerlaluBesar = UploadedFile::fake()->image('sj-besar.png', 150, 150)->size(2500);

        $this->patch('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '27,50',
            'nama_penerima' => 'PETUGAS JETTY',
            'foto_surat_jalan' => [$fotoValid, $fotoTerlaluBesar],
        ], [
            'Accept' => 'application/json',
        ])->assertStatus(422)->assertJsonValidationErrors(['foto_surat_jalan.1']);

        // Unggah awal 2 foto valid.
        $fotoAwal1 = UploadedFile::fake()->image('awal-1.png', 160, 160);
        $fotoAwal2 = UploadedFile::fake()->image('awal-2.png', 160, 160);
        $this->patch('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '27,50',
            'nama_penerima' => 'PETUGAS JETTY',
            'foto_surat_jalan' => [$fotoAwal1, $fotoAwal2],
        ], [
            'Accept' => 'application/json',
            'X-Interaksi-Ajax' => '1',
        ])->assertOk();

        $ritase->refresh();
        $this->assertCount(2, $ritase->foto_surat_jalan);
        $fotoPertama = $ritase->foto_surat_jalan[0];
        $fotoKedua = $ritase->foto_surat_jalan[1];

        // Menghapus semua foto lama tanpa mengunggah foto baru -> ditolak 422.
        $this->patch('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '27,50',
            'nama_penerima' => 'PETUGAS JETTY',
            'hapus_foto' => [$fotoPertama, $fotoKedua],
        ], [
            'Accept' => 'application/json',
        ])->assertStatus(422)->assertJsonValidationErrors(['foto_surat_jalan']);

        // Hapus 1 foto lama + tambah 1 foto baru -> tersisa fotoKedua + fotoBaru.
        $fotoBaru = UploadedFile::fake()->image('pengganti.png', 160, 160);
        $this->patch('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '27,50',
            'nama_penerima' => 'PETUGAS JETTY',
            'hapus_foto' => [$fotoPertama],
            'foto_surat_jalan' => [$fotoBaru],
        ], [
            'Accept' => 'application/json',
            'X-Interaksi-Ajax' => '1',
        ])->assertOk();

        $ritase->refresh();
        $this->assertCount(2, $ritase->foto_surat_jalan);
        $this->assertNotContains($fotoPertama, $ritase->foto_surat_jalan);
        $this->assertContains($fotoKedua, $ritase->foto_surat_jalan);

        // Admin menghapus 1 foto lagi secara selektif saat edit.
        $this->actingAs($this->penggunaAdmin());
        $this->patch('/admin/operational/trips/'.$ritase->id, [
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-05',
            'jumlah_rit' => 1,
            'tonase_ton' => '27,50',
            'status' => 'terverifikasi',
            'hapus_foto' => [$fotoKedua],
        ], [
            'Accept' => 'application/json',
            'X-Interaksi-Ajax' => '1',
        ])->assertOk();

        $ritase->refresh();
        $this->assertCount(1, $ritase->foto_surat_jalan);
        $this->assertNotContains($fotoKedua, $ritase->foto_surat_jalan);
    }

    public function test_alur_kurir_mulai_kirim_sedang_dikirim_otorisasi_dan_blokir_payroll(): void
    {
        Storage::fake('public');

        $data = $this->siapkanDataMaster();
        $admin = $this->penggunaAdmin();
        $this->actingAs($admin);

        // 1. Admin membuat SJ baru -> default status draft (tidak tampil di Portal Supir & ditolak 403).
        $this->kirimAjax('POST', '/admin/operational/trips', [
            'id_periode_gaji' => $data['periode']->id,
            'id_karyawan' => $data['karyawan']->id,
            'id_truk' => $data['truk']->id,
            'id_rute' => $data['rute']->id,
            'tanggal_ritase' => '2026-01-06',
            'jumlah_rit' => 1,
            'tonase_ton' => '26,40',
        ])->assertOk()->assertJsonPath('action', 'CREATE_RITASE');

        $ritase = Ritase::query()->firstOrFail();
        $this->assertSame('draft', $ritase->status);
        $this->flushSession();
        $this->actingAs($data['karyawan']->pengguna);
        $htmlSupirDraft = $this->get('/my/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString($ritase->nomor_surat_jalan, $htmlSupirDraft);
        $this->kirimAjax('PATCH', '/my/trips/'.$ritase->id.'/mulai-kirim')->assertStatus(403);
        $this->patchJson('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '26,40',
            'nama_penerima' => 'ANDI',
        ])->assertStatus(403);

        // Admin menerbitkan SJ menjadi status terbit.
        $this->actingAs($admin);
        $this->kirimAjax('PATCH', '/admin/operational/trips/status-massal', [
            'ids' => [$ritase->id],
            'status' => 'terbit',
        ])->assertOk()->assertJsonPath('action', 'BULK_STATUS_RITASE');
        $this->assertSame('terbit', $ritase->fresh()->status);

        // 2. Supir lain dilarang memulai pengiriman SJ milik orang lain (403).
        $supirLain = $this->buatSupir([
            'nomor_induk_karyawan' => 'NIP-999',
            'nama_lengkap' => 'SUPIR LAIN',
        ]);
        $this->actingAs($supirLain->pengguna);
        $this->kirimAjax('PATCH', '/my/trips/'.$ritase->id.'/mulai-kirim')
            ->assertStatus(403);

        // 3. Supir pemilik melihat tombol Mulai Kirim di /my/trips, lalu menekan Mulai Kirim.
        $this->actingAs($data['karyawan']->pengguna);
        $htmlSupirAwal = $this->get('/my/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($ritase->nomor_surat_jalan, $htmlSupirAwal);
        $this->assertStringContainsString('Terbit (Siap Dikirim)', $htmlSupirAwal);
        $this->assertStringContainsString('Mulai Kirim', $htmlSupirAwal);
        $this->assertStringContainsString('mulaiPengiriman', $htmlSupirAwal);

        $this->kirimAjax('PATCH', '/my/trips/'.$ritase->id.'/mulai-kirim')
            ->assertOk()
            ->assertJsonPath('action', 'START_DELIVERY_TRIP')
            ->assertJsonPath('data.status', 'sedang_dikirim');

        $this->assertSame('sedang_dikirim', $ritase->fresh()->status);

        // 4. Status Sedang Dikirim tampil di halaman Supir & Admin.
        $htmlSupirSedangDikirim = $this->get('/my/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Sedang Dikirim', $htmlSupirSedangDikirim);

        $this->actingAs($admin);
        $htmlAdminSedangDikirim = $this->get('/admin/operational/trips?periode='.$data['periode']->kode)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Sedang Dikirim', $htmlAdminSedangDikirim);

        // 5. Mengulangi mulai-kirim saat status bukan terbit ditolak 422.
        $this->actingAs($data['karyawan']->pengguna);
        $this->kirimAjax('PATCH', '/my/trips/'.$ritase->id.'/mulai-kirim')
            ->assertStatus(422);

        // 6. Periode terkunci (approved) menolak mulai-kirim 422.
        $ritaseTerkunci = $this->buatRitase($data, [
            'status' => 'terbit',
            'tanggal_ritase' => '2026-01-07',
        ]);
        $data['periode']->update(['status' => 'approved']);
        $this->kirimAjax('PATCH', '/my/trips/'.$ritaseTerkunci->id.'/mulai-kirim')
            ->assertStatus(422);
        $data['periode']->update(['status' => 'draft']);
        $ritaseTerkunci->delete();

        // 7. Admin diblokir 422 saat mengajukan payroll karena masih ada SJ sedang_dikirim.
        $this->actingAs($admin);
        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/calculate')->assertOk();
        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame('draft', $data['periode']->fresh()->status);

        // 8. Supir konfirmasi sampai dari status sedang_dikirim -> menunggu_verifikasi -> Admin verifikasi -> terverifikasi.
        $this->actingAs($data['karyawan']->pengguna);
        $this->patch('/my/trips/'.$ritase->id.'/konfirmasi', [
            'tonase_ton' => '26,40',
            'nama_penerima' => 'ANDI STOCKPILE 3',
            'catatan_pengantaran' => 'Pengantaran tuntas',
            'foto_surat_jalan' => [UploadedFile::fake()->image('bukti-sampai.png', 200, 200)],
        ], [
            'Accept' => 'application/json',
            'X-Interaksi-Ajax' => '1',
        ])->assertOk()->assertJsonPath('action', 'CONFIRM_DELIVERY_TRIP');

        $this->assertSame('menunggu_verifikasi', $ritase->fresh()->status);

        $this->actingAs($admin);
        $this->kirimAjax('PATCH', '/admin/operational/trips/status-massal', [
            'ids' => [$ritase->id],
            'status' => 'terverifikasi',
        ])->assertOk()->assertJsonPath('action', 'BULK_STATUS_RITASE');

        $this->assertSame('terverifikasi', $ritase->fresh()->status);

        $this->kirimAjax('POST', '/admin/payroll/'.$data['periode']->kode.'/calculate')->assertOk();
        $this->kirimAjax('PATCH', '/admin/payroll/'.$data['periode']->kode.'/submit')
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSame('pending', $data['periode']->fresh()->status);
    }
}
