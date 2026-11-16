<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * BRIEF_UNIT_TEST.md Bab 3 (skema JSON notifikasi login), Bab 4.3 (akses),
 * dan Bab 9.2 (noindex pada halaman login).
 */
class LoginAdminTest extends TestCase
{
    use MembuatDataUji, RefreshDatabase;

    public function test_halaman_login_dapat_dirender_dengan_judul_dan_noindex(): void
    {
        $respon = $this->get('/login');

        $respon->assertOk();
        $respon->assertSee('<title>', false);
        $respon->assertSee('noindex, nofollow, noarchive', false);
        $respon->assertSee('_token', false);
    }

    public function test_login_ajax_mengembalikan_skema_json_standar(): void
    {
        $pengguna = $this->penggunaAdmin(['email' => 'admin.login@payroll.test']);

        $respon = $this->kirimAjax('POST', '/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        $respon->assertOk()
            ->assertJsonStructure([
                'success', 'type', 'title', 'action', 'status', 'message',
                'data' => ['id', 'nama', 'peran'],
                'redirect', 'timestamp',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('action', 'AUTH_LOGIN')
            ->assertJsonPath('status', 'COMPLETED')
            ->assertJsonPath('data.peran', UserRole::Admin->value);

        $this->assertAuthenticatedAs($pengguna);
    }

    public function test_login_gagal_menampilkan_toast_error_tanpa_membocorkan_kredensial(): void
    {
        $pengguna = $this->penggunaAdmin(['email' => 'admin.gagal@payroll.test']);

        $respon = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'salah-total',
        ]);

        $respon->assertSessionHasErrors('email');
        $respon->assertSessionHas('toast');

        $toast = session('toast');
        $this->assertFalse($toast['success']);
        $this->assertSame('error', $toast['type']);
        $this->assertSame('AUTH_LOGIN_FAILED', $toast['action']);
        $this->assertStringNotContainsString('salah-total', $toast['message']);
        $this->assertStringNotContainsString('password', strtolower($toast['message']));

        $this->assertGuest();
    }

    public function test_login_gagal_ajax_mengembalikan_422_dengan_objek_errors(): void
    {
        $pengguna = $this->penggunaAdmin(['email' => 'admin.gagal2@payroll.test']);

        $this->kirimAjax('POST', '/login', [
            'email' => $pengguna->email,
            'password' => 'salah-total',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 'VALIDATION_FAILED')
            ->assertJsonStructure(['errors' => ['email']]);

        $this->assertGuest();
    }

    public function test_validasi_login_mewajibkan_email_dan_kata_sandi(): void
    {
        $this->kirimAjax('POST', '/login', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_setiap_peran_diarahkan_ke_dashboard_masing_masing(): void
    {
        $harapan = [
            'admin' => 'admin.dashboard',
            'pimpinan' => 'executive.dashboard',
            'supir' => 'driver.dashboard',
        ];

        foreach ($harapan as $peran => $namaRute) {
            $pengguna = match ($peran) {
                'admin' => $this->penggunaAdmin(['email' => "admin.{$peran}@payroll.test"]),
                'pimpinan' => $this->penggunaPimpinan(['email' => "admin.{$peran}@payroll.test"]),
                default => $this->penggunaSupir(['email' => "admin.{$peran}@payroll.test"]),
            };

            $this->actingAs($pengguna)
                ->get('/dashboard')
                ->assertRedirect(route($namaRute));
        }
    }

    public function test_logout_menghapus_sesi(): void
    {
        $pengguna = $this->penggunaAdmin();

        $this->actingAs($pengguna)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_halaman_login_tidak_dapat_diakses_setelah_masuk(): void
    {
        $pengguna = $this->penggunaAdmin();

        $this->actingAs($pengguna)
            ->get('/login')
            ->assertRedirect(route('dashboard', absolute: false));
    }
}
