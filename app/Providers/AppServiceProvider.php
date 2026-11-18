<?php

namespace App\Providers;

use App\Models\DataPerusahaan;
use App\Services\PayrollDataService;
use App\Services\PencarianNavigasiService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu instance per request agar cache internal service dipakai bersama
        // oleh controller dan seluruh view composer.
        $this->app->singleton(PayrollDataService::class);
        $this->app->singleton(PencarianNavigasiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Directive format Rupiah & Tonase standar Indonesia (AGENTS.md 3.2).
        Blade::directive('rupiah', function (string $ekspresi): string {
            return "<?php echo 'Rp ' . number_format((int) round((float) ({$ekspresi})), 0, ',', '.'); ?>";
        });

        Blade::directive('tonase', function (string $ekspresi): string {
            return "<?php echo number_format(((float) ({$ekspresi})) / 1000, 2, ',', '.'); ?>";
        });

        // Rate Limiter global endpoint AJAX & mutasi admin: maks 60 request/menit per IP (BRIEF Bab 10.2).
        RateLimiter::for('interaksi-admin', function (Request $permintaan): Limit {
            return Limit::perMinute(60)
                ->by((string) ($permintaan->user()?->id ?: $permintaan->ip()))
                ->response(function (Request $permintaan, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);
                    $pesan = 'Terlalu banyak permintaan dari perangkat Anda. Harap tunggu beberapa saat sebelum mencoba kembali.';

                    if ($permintaan->expectsJson() || $permintaan->header('X-Interaksi-Ajax') === '1') {
                        return response()->json([
                            'success' => false,
                            'type' => 'error',
                            'title' => 'Batas Permintaan Terlampaui',
                            'action' => 'RATE_LIMIT_EXCEEDED',
                            'status' => 'BLOCKED',
                            'message' => $pesan,
                            'retry_after_seconds' => $retryAfter,
                            'timestamp' => now()->toIso8601String(),
                        ], 429, array_merge($headers, [
                            'Retry-After' => (string) $retryAfter,
                            'X-RateLimit-Limit' => '60',
                        ]));
                    }

                    return back()
                        ->with('galat', $pesan)
                        ->with('toast', [
                            'success' => false,
                            'type' => 'error',
                            'title' => 'Batas Permintaan Terlampaui',
                            'action' => 'RATE_LIMIT_EXCEEDED',
                            'status' => 'BLOCKED',
                            'message' => $pesan,
                            'timestamp' => now()->toIso8601String(),
                        ]);
                });
        });

        View::composer(
            ['components.layout.topbar', 'components.layout.executive-topbar'],
            function ($tampilan): void {
                $layananDataPayroll = app(PayrollDataService::class);
                $layananPencarian = app(PencarianNavigasiService::class);
                $peran = auth()->user()?->role;

                $tampilan->with(
                    'periodeAktif',
                    $layananDataPayroll->formatPeriode($layananDataPayroll->ambilPeriodeTerbaru()),
                );

                // Daftar halaman yang boleh diakses peran ini (panduan awal pencarian).
                $tampilan->with(
                    'hasilPencarianAwal',
                    $layananPencarian->katalogUntukPeran($peran)
                        ->map(fn (array $halaman) => [
                            'label' => $halaman['label'],
                            'keterangan' => $halaman['keterangan'],
                            'ikon' => $halaman['ikon'],
                            'grup' => $halaman['grup'],
                            'url' => route($halaman['route']),
                        ])
                        ->all(),
                );

                $tampilan->with('labelPeran', $peran?->label() ?? 'Pengguna');
            },
        );

        View::composer(
            ['components.layout.admin-sidebar'],
            function ($tampilan): void {
                $layananDataPayroll = app(PayrollDataService::class);

                $tampilan->with(
                    'ringkasanKasbonAdmin',
                    $layananDataPayroll->ringkasanKasbonSidebar(),
                );
            },
        );

        View::composer(
            ['components.layout.driver-topbar', 'components.layout.driver-sidebar'],
            function ($tampilan): void {
                $layananDataPayroll = app(PayrollDataService::class);

                $tampilan->with('profilSupir', $layananDataPayroll->profilSupir(auth()->user()));
                $tampilan->with(
                    'periodeAktif',
                    $layananDataPayroll->formatPeriode($layananDataPayroll->ambilPeriodeTerbaru()),
                );
            },
        );

        // Sidebar & topbar Pimpinan: periode aktif mengikuti query string
        // (?periode=KODE) supaya seluruh halaman eksekutif konsisten.
        View::composer(
            ['components.layout.executive-topbar', 'components.layout.executive-sidebar'],
            function ($tampilan): void {
                $layananDataPayroll = app(PayrollDataService::class);
                $kodePeriode = request()->query('periode');
                $periode = $layananDataPayroll->ambilPeriodeBerdasarkanKode(is_string($kodePeriode) ? $kodePeriode : null)
                    ?? $layananDataPayroll->ambilPeriodeTerbaru();

                $tampilan->with('periodeAktif', $layananDataPayroll->formatPeriode($periode));
                $tampilan->with('opsiPeriodeEksekutif', $layananDataPayroll->opsiPeriodeEksekutif($periode?->kode));
                $tampilan->with('ringkasanOtorisasi', $layananDataPayroll->ringkasanOtorisasiSidebar());
            },
        );
        View::composer(
            ['admin.dashboard', 'admin.payroll.index', 'executive.dashboard', 'executive.payroll.index'],
            function ($tampilan): void {
                $sekarang = now();
                $layananDataPayroll = app(PayrollDataService::class);
                $periodeBerjalan = $layananDataPayroll->ambilPeriodeBerjalan();

                // Notifikasi buka periode baru hanya muncul bila TIDAK ada periode berjalan.
                $tampilan->with(
                    'notifikasiPeriodeBaru',
                    $periodeBerjalan === null ? [
                        'kode' => \App\Support\SiklusPeriodeGaji::kodeDariTanggal($sekarang),
                        'nama_bulan' => $sekarang->translatedFormat('F Y'),
                    ] : null
                );
            }
        );


        // Profil perusahaan singleton tersedia di seluruh Blade view & komponen.
        $idPermintaanTerakhir = null;
        $dataPerusahaanTerakhir = null;

        View::composer('*', function ($tampilan) use (&$idPermintaanTerakhir, &$dataPerusahaanTerakhir): void {
            $idPermintaanSaatIni = app()->bound('request') ? spl_object_id(request()) : null;

            if ($dataPerusahaanTerakhir === null || $idPermintaanSaatIni !== $idPermintaanTerakhir) {
                $idPermintaanTerakhir = $idPermintaanSaatIni;
                $dataPerusahaanTerakhir = DataPerusahaan::profilAktif();
            }

            $tampilan->with('dataPerusahaan', $dataPerusahaanTerakhir);
            $tampilan->with('infoAuthor', config('app.author'));
        });
    }
}
