<?php

namespace Tests\Feature\Security;

use App\Models\Karyawan;
use App\Models\Rute;
use App\Models\Truk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * BRIEF_UNIT_TEST.md Bab 4 (OWASP A01/A03/A04/A10), Bab 5 (rate limiter),
 * Bab 6 (validasi media) untuk seluruh area admin & login.
 */
class KeamananAdminTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * BRIEF Bab 4.1: payload SQL Injection tidak boleh menghasilkan HTTP 500
     * dan harus tersimpan sebagai string literal (bukti prepared statement).
     */
    public function test_payload_sql_injection_dinetralkan_pada_pencarian_dan_sorting(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $payloadBerbahaya = [
            "' OR '1'='1",
            "'; DROP TABLE karyawan; --",
            '1 UNION SELECT null, email, password FROM users --',
            "' OR 1=1 /*",
        ];

        foreach ($payloadBerbahaya as $urutan => $payload) {
            $this->kirimAjax('POST', '/admin/interaksi/tabel', [
                'entitas' => 'karyawan',
                'cari' => $payload,
                'sort_by' => $payload,
                'sort_dir' => $payload,
            ])->assertOk();

            $this->kirimAjax('POST', '/admin/master/employees', [
                'nomor_induk_karyawan' => 'SJ'.$urutan,
                'nama_lengkap' => $payload,
                'status' => 'aktif',
                'email' => "suntik{$urutan}@payroll.test",
                'password' => 'Rahasia123!',
            ])->assertOk();
        }

        // Tidak ada tabel yang hilang dan payload tersimpan apa adanya sebagai teks.
        $this->assertDatabaseCount('karyawan', 5);
        $this->assertDatabaseHas('karyawan', [
            'nomor_induk_karyawan' => 'SJ0',
            'nama_lengkap' => strtoupper("' OR '1'='1"),
        ]);
        $this->assertDatabaseHas('karyawan', ['nomor_induk_karyawan' => 'NIP-001']);
    }

    /**
     * BRIEF Bab 4.2: XSS harus ter-escape saat dirender.
     */
    public function test_payload_xss_ter_escape_saat_render_halaman(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $payload = '<script>alert("XSS")</script><img src=x onerror=alert(1)>';

        $this->kirimAjax('POST', '/admin/master/employees', [
            'nomor_induk_karyawan' => 'NIP-XSS',
            'nama_lengkap' => $payload,
            'status' => 'aktif',
            'email' => 'xss@payroll.test',
            'password' => 'Rahasia123!',
        ])->assertOk();

        $tersimpan = Karyawan::query()->where('nomor_induk_karyawan', 'NIP-XSS')->firstOrFail();
        $this->assertStringNotContainsString('<script>', $tersimpan->nama_lengkap);

        $html = $this->get('/admin/master/employees')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $html);
        $this->assertStringContainsString('&lt;SCRIPT&gt;', $html);
    }

    /**
     * BRIEF Bab 4.3: request tanpa sesi wajib 401 (JSON) atau 302 (HTML).
     */
    public function test_admin_tanpa_sesi_ditolak(): void
    {
        $this->kirimAjax('GET', '/admin/dashboard')->assertStatus(401);
        $this->kirimAjax('POST', '/admin/master/employees', ['nama_lengkap' => 'X'])->assertStatus(401);

        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/admin/payroll')->assertRedirect('/login');
    }

    /**
     * BRIEF Bab 4.3: peran selain admin tidak boleh masuk area admin.
     */
    public function test_peran_selain_admin_ditolak_dari_area_admin(): void
    {
        $this->siapkanDataMaster();

        $this->actingAs($this->penggunaPimpinan())
            ->get('/admin/master/employees')
            ->assertRedirect('/login');

        $this->actingAs($this->penggunaSupir())
            ->get('/admin/payroll')
            ->assertRedirect('/login');
    }

    /**
     * BRIEF Bab 5.1: percobaan gagal ke-6 wajib HTTP 429 + header Retry-After.
     */
    public function test_rate_limiter_login_memblokir_percobaan_keenam(): void
    {
        $this->penggunaAdmin(['email' => 'admin.kunci@payroll.test']);

        foreach (range(1, 5) as $percobaan) {
            $this->postJson('/login', [
                'email' => 'admin.kunci@payroll.test',
                'password' => 'kata-sandi-salah',
            ])->assertStatus(422);
        }

        $terkunci = $this->postJson('/login', [
            'email' => 'admin.kunci@payroll.test',
            'password' => 'kata-sandi-salah',
        ]);

        $terkunci->assertStatus(429);
        $terkunci->assertHeader('Retry-After');
        $this->assertGreaterThan(0, (int) $terkunci->headers->get('Retry-After'));
        $this->assertGuest();
    }

    /**
     * BRIEF Bab 5: login benar setelah beberapa kali gagal tetap berhasil.
     */
    public function test_login_berhasil_sebelum_terkunci(): void
    {
        $pengguna = $this->penggunaAdmin(['email' => 'admin.masuk@payroll.test']);

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($pengguna);
    }

    /**
     * BRIEF Bab 6: upload gambar lebih dari 2 MB ditolak.
     */
    public function test_upload_foto_melebihi_dua_megabita_ditolak(): void
    {
        $data = $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $berkasBesar = UploadedFile::fake()->create('foto.jpg', 2600, 'image/jpeg');

        $this->kirimAjax('POST', '/admin/master/employees', [
            'nomor_induk_karyawan' => 'NIP-UPLOAD',
            'nama_lengkap' => 'SUPIR UPLOAD',
            'status' => 'aktif',
            'email' => 'upload@payroll.test',
            'password' => 'Rahasia123!',
            'foto_profil' => $berkasBesar,
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertNull(Karyawan::query()->where('nomor_induk_karyawan', 'NIP-UPLOAD')->first());
        $this->assertSame(1, Karyawan::query()->count());
        $this->assertNotNull($data['karyawan']->id);
    }

    /**
     * BRIEF Bab 6.1: ekstensi berbahaya yang disamarkan ditolak.
     */
    public function test_upload_dengan_ekstensi_skrip_ditolak(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $palsu = UploadedFile::fake()->createWithContent('shell.php.jpg', '<?php echo "hack"; ?>');

        $respon = $this->kirimAjax('POST', '/admin/master/employees', [
            'nomor_induk_karyawan' => 'NIP-SHELL',
            'nama_lengkap' => 'SUPIR SHELL',
            'status' => 'aktif',
            'email' => 'shell@payroll.test',
            'password' => 'Rahasia123!',
            'foto_profil' => $palsu,
        ]);

        $this->assertContains($respon->getStatusCode(), [422, 500]);
        $this->assertNull(Karyawan::query()->where('nomor_induk_karyawan', 'NIP-SHELL')->first());
    }

    /**
     * BRIEF Bab 9.2: header keamanan & noindex wajib ada pada halaman login.
     */
    public function test_header_keamanan_dan_noindex_pada_login(): void
    {
        $respon = $this->get('/login');

        $respon->assertOk();
        $respon->assertHeader('X-Content-Type-Options', 'nosniff');
        $respon->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respon->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respon->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $respon->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false);
    }

    /**
     * Guard mass assignment: kolom sensitif tidak boleh diisi dari request.
     */
    public function test_mass_assignment_kolom_sensitif_diabaikan(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->kirimAjax('POST', '/admin/master/routes', [
            'kode' => 'RTE-77',
            'material' => 'BATUBARA',
            'tarif_per_ton' => 100000,
            'uang_jalan_default' => 50000,
            'status' => 'aktif',
            'id' => 99999,
            'created_at' => '1990-01-01 00:00:00',
        ])->assertOk();

        $rute = Rute::query()->where('kode', 'RTE-77')->firstOrFail();

        $this->assertNotSame(99999, (int) $rute->id);
        $this->assertNotSame('1990-01-01', $rute->created_at->format('Y-m-d'));
    }

    /**
     * Route admin hanya menerima metode HTTP yang didaftarkan.
     */
    public function test_metode_http_tidak_sesuai_ditolak(): void
    {
        $this->siapkanDataMaster();
        $this->actingAs($this->penggunaAdmin());

        $this->get('/admin/master/employees/NIP-001')->assertStatus(405);
        $this->get('/admin/master/trucks')->assertOk();
        $this->put('/admin/master/trucks/'.Truk::query()->value('id'), [])->assertStatus(405);
    }
}
