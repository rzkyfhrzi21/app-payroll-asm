<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class IdentitasAuthorTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    public function test_konfigurasi_author_memiliki_nilai_default_saat_env_kosong(): void
    {
        $kunciEnv = [
            'AUTHOR_NAMA',
            'AUTHOR_NPM',
            'AUTHOR_INSTAGRAM',
            'AUTHOR_EMAIL',
            'AUTHOR_WHATSAPP',
            'AUTHOR_JUDUL',
            'AUTHOR_DOSBING',
            'AUTHOR_ALAMAT',
        ];

        $cadanganEnv = [];
        foreach ($kunciEnv as $kunci) {
            $cadanganEnv[$kunci] = $_ENV[$kunci] ?? null;
            $_ENV[$kunci] = '';
            $_SERVER[$kunci] = '';
            putenv($kunci.'=');
        }

        try {
            $konfig = require config_path('app.php');

            $this->assertSame('Nabela Septiara', $konfig['author']['nama']);
            $this->assertSame('2311050052', $konfig['author']['npm']);
            $this->assertSame('nbelaasp_', $konfig['author']['instagram']);
            $this->assertSame('septiaranabila559@gmail.com', $konfig['author']['email']);
            $this->assertSame('085760898417', $konfig['author']['whatsapp']);
            $this->assertSame('Payroll System pada PT ASM Lampung', $konfig['author']['judul']);
            $this->assertSame('Bu Ayu Firdhayanti, S.Kom., M.T.I', $konfig['author']['dosbing']);
            $this->assertSame('Kosan Dakwah', $konfig['author']['alamat']);

            // Uji saat env diisi nilai kustom
            $_ENV['AUTHOR_NAMA'] = 'Pengembang Uji';
            $_ENV['AUTHOR_INSTAGRAM'] = '@pengembang_uji';
            $_SERVER['AUTHOR_NAMA'] = 'Pengembang Uji';
            $_SERVER['AUTHOR_INSTAGRAM'] = '@pengembang_uji';
            putenv('AUTHOR_NAMA=Pengembang Uji');
            putenv('AUTHOR_INSTAGRAM=@pengembang_uji');

            $konfigKustom = require config_path('app.php');
            $this->assertSame('Pengembang Uji', $konfigKustom['author']['nama']);
            $this->assertSame('pengembang_uji', $konfigKustom['author']['instagram']);
        } finally {
            foreach ($kunciEnv as $kunci) {
                if ($cadanganEnv[$kunci] === null) {
                    unset($_ENV[$kunci], $_SERVER[$kunci]);
                    putenv($kunci);
                } else {
                    $_ENV[$kunci] = $cadanganEnv[$kunci];
                    $_SERVER[$kunci] = $cadanganEnv[$kunci];
                    putenv($kunci.'='.$cadanganEnv[$kunci]);
                }
            }
        }
    }

    public function test_halaman_login_dan_portal_menampilkan_identitas_author(): void
    {
        $responLogin = $this->get('/login');
        $responLogin->assertOk();
        $responLogin->assertSee('Nabela Septiara', false);
        $responLogin->assertSee('Seluruh hak cipta dilindungi.', false);
        $responLogin->assertSee('buka-modal-author', false);
        $responLogin->assertDontSee('Pengembang:', false);
        $responLogin->assertDontSee('Pembimbing:', false);

        $admin = $this->penggunaAdmin();
        $responAdmin = $this->actingAs($admin)->get('/admin/dashboard');
        $responAdmin->assertOk();
        $responAdmin->assertSee('Tentang Pengembang', false);
        $responAdmin->assertSee('Nabela Septiara', false);
        $responAdmin->assertDontSee('Pembimbing:', false);

        $pimpinan = $this->penggunaPimpinan();
        $responPimpinan = $this->actingAs($pimpinan)->get('/executive/dashboard');
        $responPimpinan->assertOk();
        $responPimpinan->assertSee('Tentang Pengembang', false);
        $responPimpinan->assertSee('2311050052', false);
        $responPimpinan->assertDontSee('Pembimbing:', false);
    }

    public function test_endpoint_health_mengembalikan_status_ok_tanpa_auth(): void
    {
        $respon = $this->getJson('/health');

        $respon->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }
}
