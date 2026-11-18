<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN DASHBOARD EKSEKUTIF / DIREKSI):
     - Tujuan Halaman: Pemandangan mata elang (helicopter view) untuk Pimpinan/Owner:
       memantau omzet angkutan, total kasbon supir, efisiensi uang jalan, antrean otorisasi ACC gaji,
       serta laba bersih logistik batubara per periode berjalan.
     - Analogi Dunia Nyata: Seperti panel indikator pesawat di kokpit Direktur:
       Pimpinan melihat apakah kapal bisnis logistik melaju sehat (omzet naik, biaya terkendali)
       dan apakah ada permohonan persetujuan gaji supir yang butuh tanda tangan ACC direksi.
     - Data dari Controller (Executive/DashboardController@index):
       * $summary: Total omzet kotor, uang jalan, biaya sparepart armada, komisi, dan net pay.
       * $auditUnit: Unit armada dengan beban sparepart pada periode berjalan.
       * $period: Info periode aktif yang sedang dievaluasi.
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
@endphp

<x-executive-layout title="Executive Dashboard">
    <div
        class="flex flex-col gap-6"
        x-data="executiveDashboard(@js($summary), @js($period))"
    >
        {{-- Breadcrumb + judul + aksi --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 font-sans text-label-md text-slate-text-secondary">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-[1rem]">domain</span>
                        Dashboard
                    </span>
                    <span class="material-symbols-outlined text-[0.875rem] text-slate-text-muted">chevron_right</span>
                    <span class="font-semibold text-slate-text-primary">Rekap Pimpinan</span>
                </div>
                <h1 class="font-display text-headline-lg tracking-tight text-slate-text-primary">Dashboard &amp; Laporan Finansial Logistik</h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Monitoring performa pengantaran batubara, efisiensi kas uang jalan, beban sparepart unit, serta status pencairan gaji.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">

                <a
                    href="{{ route('executive.reports.index', ['periode' => $period['code']]) }}"
                    class="flex items-center gap-2 rounded-lg bg-card-bg px-3.5 py-2 font-sans text-body-sm font-semibold text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container-low"
                >
                    <span class="material-symbols-outlined text-[1.125rem] text-slate-text-secondary">file_download</span>
                    Ekspor Laporan (PDF/Excel)
                </a>

                <a
                    href="{{ route('executive.payroll.index', ['periode' => $period['code']]) }}"
                    class="flex items-center gap-2.5 rounded-lg bg-gradient-to-r from-primary-container via-banner-indigo to-banner-deep px-4 py-2 font-sans text-body-sm font-semibold text-on-primary shadow-md transition-all hover:brightness-110"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">verified_user</span>
                    <span>Keputusan Otorisasi</span>
                </a>
            </div>
        </div>

        {{-- Konteks operasional --}}
        <div class="overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
            <div class="flex flex-col justify-between gap-space-lg md:flex-row md:items-center">
                <div class="flex items-center gap-space-md">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-surface-container-low text-primary">
                        <span class="material-symbols-outlined text-[1.5rem]">local_shipping</span>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 animate-ping rounded-full bg-net-pay-emerald"></span>
                            <span class="font-display text-headline-sm font-bold text-slate-text-primary">Operasi Pengantaran Aktif</span>
                        </div>
                        <span class="font-sans text-body-sm text-slate-text-secondary">
                            Siklus Buku: <strong class="text-slate-text-primary">{{ $period['range'] }}</strong> &bull;
                            Batas Data Timbangan: <span class="font-financial-table font-semibold text-primary">{{ $period['cut_off'] }} 23:59 WIB</span>
                        </span>
                    </div>
                </div>
                <div class="flex flex-col items-start gap-0.5 md:items-end">
                    <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary">Periode Berjalan</span>
                    <span class="font-display text-headline-md font-bold text-slate-text-primary">{{ $period['code'] }}</span>
                    <span class="font-sans text-body-sm text-slate-text-secondary">{{ $summary['drivers'] }} Supir &bull; {{ $summary['rit'] }} Rit</span>
                </div>
            </div>
        </div>

        {{-- Ringkasan angka kunci --}}
        <div class="grid grid-cols-1 gap-space-lg md:grid-cols-2 xl:grid-cols-4">
            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Total Omzet Angkutan</span>
                        <span class="mt-1 font-financial-total font-bold tabular-nums text-slate-text-primary">{{ $rp($summary['omzet']) }}</span>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container-low text-primary">
                        <span class="material-symbols-outlined text-[1.375rem]">payments</span>
                    </div>
                </div>
                <div class="mt-space-md flex items-center justify-between rounded-lg bg-surface-container-low/60 p-2 pt-space-sm font-sans text-body-sm">
                    <span class="font-medium tabular-nums text-slate-text-secondary">{{ $summary['tonnage'] }} Ton &bull; {{ $summary['rit'] }} Rit</span>
                    <span class="font-semibold text-slate-text-primary">{{ $summary['drivers'] }} Supir</span>
                </div>
            </div>

            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Pengeluaran Kas Operasional</span>
                        <span class="mt-1 font-financial-total font-bold tabular-nums text-danger-red">{{ $rp($summary['cash_out']) }}</span>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-danger-surface text-danger-red">
                        <span class="material-symbols-outlined text-[1.375rem]">account_balance_wallet</span>
                    </div>
                </div>
                <div class="mt-space-md flex items-center justify-between rounded-lg bg-surface-container-low/60 p-2 pt-space-sm font-sans text-body-sm">
                    <span class="font-sans text-slate-text-secondary">U. Jalan: <strong class="text-slate-text-primary">{{ $rp($summary['trip_money']) }}</strong></span>
                    <span class="font-sans text-slate-text-secondary">Sparepart: <strong class="text-warning-amber">{{ $rp($summary['sparepart']) }}</strong></span>
                </div>
            </div>

            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Estimasi Margin Operasional</span>
                        <span class="mt-1 font-financial-total font-bold tabular-nums text-net-pay-emerald">{{ $rp($summary['margin']) }}</span>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.375rem]">query_stats</span>
                    </div>
                </div>
                <div class="mt-space-md flex items-center justify-between rounded-lg bg-net-pay-surface/70 p-2 pt-space-sm font-sans text-body-sm">
                    <span class="font-bold text-net-pay-emerald">Margin Kotor: {{ $summary['margin_pct'] }}</span>
                    <span class="font-sans font-medium text-slate-text-secondary">Terhadap omzet</span>
                </div>
            </div>

            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Kebutuhan Kas Gaji</span>
                        <span class="mt-1 font-financial-total font-bold tabular-nums text-primary">{{ $rp($kebutuhanKasGaji['belum_cair']) }}</span>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container-high text-primary">
                        <span class="material-symbols-outlined text-[1.375rem]">account_balance</span>
                    </div>
                </div>
                <div class="mt-space-md flex items-center justify-between rounded-lg bg-surface-container-low p-2 pt-space-sm font-sans text-body-sm">
                    <span class="font-medium text-slate-text-secondary">Belum Dicairkan &bull; {{ $kebutuhanKasGaji['jumlah_periode'] }} Periode</span>
                    <span class="font-semibold tabular-nums text-slate-text-primary">Sudah Cair: {{ $rp($kebutuhanKasGaji['sudah_cair']) }}</span>
                </div>
            </div>
        </div>
        {{-- Audit Kepatuhan & Likuiditas Finansial --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-sidebar-dark via-sidebar-surface to-sidebar-dark p-space-xl text-on-primary shadow-lg">
            <div class="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-banner-indigo/20 blur-3xl"></div>
            <div class="relative z-10 flex flex-col justify-between gap-space-xl lg:flex-row lg:items-center">
                <div class="flex max-w-2xl flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[1.25rem] text-net-pay-emerald">verified</span>
                        <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-banner-sky">Audit Kepatuhan &amp; Likuiditas Finansial</span>
                    </div>
                    <h3 class="font-display text-headline-md font-bold text-surface">Persetujuan Pengeluaran Kas Payroll Periode {{ $period['code'] }}</h3>
                    <p class="font-sans text-body-md leading-relaxed text-slate-text-muted">
                        Sisa kasbon supir aktif tercatat pada angka <strong class="text-surface">{{ $rp($ringkasanKasbon['outstanding']) }}</strong> dengan
                        {{ $ringkasanKasbon['active_drivers'] }} supir masih memiliki kasbon berjalan. Kebutuhan kas payroll periode ini
                        sebesar <strong class="text-surface">{{ $rp($summary['net_pay']) }}</strong> untuk {{ $summary['drivers'] }} supir.
                    </p>
                </div>

                <div class="flex shrink-0 flex-col items-center gap-space-md rounded-xl bg-white/5 p-space-md backdrop-blur-sm sm:flex-row">
                    <div class="flex flex-col text-left sm:text-right">
                        <span class="font-sans text-label-sm uppercase text-banner-sky">Otorisator Berwenang:</span>
                        <span class="font-display text-headline-sm font-bold text-surface">{{ auth()->user()->name ?? 'Pimpinan' }}</span>
                        <span class="font-sans text-[0.6875rem] text-slate-text-muted">Pimpinan / Direksi</span>
                    </div>
                    <div class="hidden h-10 w-px bg-white/10 sm:block"></div>
                    @if (($periodeModel?->status ?? '') === 'paid')
                        <div class="inline-flex items-center gap-2 rounded-lg bg-net-pay-surface px-4 py-2.5 font-sans text-body-sm font-bold text-net-pay-emerald">
                            <span class="material-symbols-outlined text-[1.25rem]">paid</span>
                            Dana Payroll Sudah Dicairkan
                        </div>
                    @elseif (($periodeModel?->status ?? '') === 'approved')
                        <button
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-sky-600 px-5 py-3 font-display text-headline-sm font-bold text-white shadow-md transition-all hover:bg-sky-700 sm:w-auto"
                            @click="confirmDisburse()"
                        >
                            <span class="material-symbols-outlined text-[1.25rem]">payments</span>
                            Cairkan Dana Payroll
                        </button>
                    @else
                        <a
                            href="{{ route('executive.payroll.index', ['periode' => $period['code']]) }}"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-banner-sky to-primary-container px-5 py-3 font-display text-headline-sm font-bold text-on-primary shadow-md transition-all hover:brightness-110 sm:w-auto"
                        >
                            <span class="material-symbols-outlined text-[1.25rem]">verified_user</span>
                            Tinjau &amp; ACC di Otorisasi
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tren bulanan & tahunan (line chart) --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-ui.line-chart
                judul="Tren Omzet Bulanan"
                keterangan="Perbandingan 12 bulan terakhir hingga periode terpilih."
                :data="$trenBulanan"
                label-nilai="Omzet"
                warna="#4F46E5"
            />

            <x-ui.line-chart
                judul="Tren Omzet Tahunan"
                keterangan="Rekap 5 tahun terakhir berdasarkan periode penggajian."
                :data="$trenTahunan"
                label-nilai="Omzet"
                warna="#2563EB"
            />
        </div>

        {{-- Grafik tren + komposisi biaya --}}
        <div class="grid grid-cols-1 gap-space-xl lg:grid-cols-12">
            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-xl shadow-card lg:col-span-8">
                <div class="mb-space-lg flex flex-col justify-between gap-space-md sm:flex-row sm:items-center">
                    <div>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Tren Volume Muatan &amp; Omzet Rute Angkutan (6 Periode Terakhir)</h2>
                        <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">Komparasi fluktuasi tonase batubara dan nilai tagihan pengantaran per siklus penggajian.</p>
                    </div>
                    <div class="flex items-center gap-2 rounded-lg bg-surface-container-low px-2.5 py-1.5 font-sans text-body-sm font-medium">
                        <span class="flex items-center gap-1.5 text-slate-text-primary">
                            <span class="h-3 w-3 rounded-sm bg-banner-indigo"></span>
                            Omzet (Juta Rp)
                        </span>
                        <span class="ml-2 flex items-center gap-1.5 text-slate-text-secondary">
                            <span class="h-3 w-3 rounded-sm bg-banner-sky"></span>
                            Tonase (Ton)
                        </span>
                    </div>
                </div>

                <div class="relative flex h-56 w-full flex-col justify-end pb-2 pt-4">
                    <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN DASHBOARD EKSEKUTIF / DIREKSI):
     - Tujuan Halaman: Pemandangan mata elang (helicopter view) untuk Pimpinan/Owner:
       memantau omzet angkutan, total kasbon supir, efisiensi uang jalan, antrean otorisasi ACC gaji,
       serta laba bersih logistik batubara per periode berjalan.
     - Analogi Dunia Nyata: Seperti panel indikator pesawat di kokpit Direktur:
       Pimpinan melihat apakah kapal bisnis logistik melaju sehat (omzet naik, biaya terkendali)
       dan apakah ada permohonan persetujuan gaji supir yang butuh tanda tangan ACC direksi.
     - Data dari Controller (Executive/DashboardController@index):
       * $summary: Total omzet kotor, uang jalan, biaya sparepart armada, komisi, dan net pay.
       * $auditUnit: Unit armada dengan beban sparepart pada periode berjalan.
       * $period: Info periode aktif yang sedang dievaluasi.
     ============================================================================== -->
@php
                        // Skala grafik dihitung dari data nyata periode terpilih.
                        $nilaiOmzetTren = collect($trend)->pluck('omzet')->all();
                        $omzetMaksTren = max(1, $nilaiOmzetTren === [] ? 0 : max($nilaiOmzetTren));
                        $jumlahTitikTren = max(1, count($trend));
                        $lebarLangkah = $jumlahTitikTren > 1 ? 600 / ($jumlahTitikTren - 1) : 0;
                        $titikTren = collect($trend)->values()->map(function (array $item, int $indeks) use ($omzetMaksTren, $lebarLangkah) {
                            return [
                                'x' => (int) round(40 + ($indeks * $lebarLangkah)),
                                'y' => (int) round(155 - (($item['omzet'] / $omzetMaksTren) * 130)),
                            ];
                        });
                        $garisTren = $titikTren->map(fn (array $titik) => $titik['x'].','.$titik['y'])->implode(' ');
                    @endphp

                    @if ($titikTren->isNotEmpty())
                        <svg class="h-44 w-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 700 160">
                            <defs>
                                <linearGradient id="areaGradient" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#4F46E5" stop-opacity="0.25"></stop>
                                    <stop offset="100%" stop-color="#4F46E5" stop-opacity="0"></stop>
                                </linearGradient>
                            </defs>
                            <line stroke="#E2E8F0" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="700" y1="20" y2="20"></line>
                            <line stroke="#E2E8F0" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="700" y1="65" y2="65"></line>
                            <line stroke="#E2E8F0" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="700" y1="110" y2="110"></line>
                            <line stroke="#CBD5E1" stroke-width="1.5" x1="0" x2="700" y1="155" y2="155"></line>
                            <polygon fill="url(#areaGradient)" points="{{ $garisTren }} {{ $titikTren->last()['x'] }},155 {{ $titikTren->first()['x'] }},155"></polygon>
                            <polyline fill="none" points="{{ $garisTren }}" stroke="#4F46E5" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"></polyline>
                            @foreach ($titikTren as $titik)
                                <circle
                                    cx="{{ $titik['x'] }}"
                                    cy="{{ $titik['y'] }}"
                                    fill="#FFFFFF"
                                    r="{{ $loop->last ? 6 : 4.5 }}"
                                    stroke="{{ $loop->last ? '#38BDF8' : '#4F46E5' }}"
                                    stroke-width="{{ $loop->last ? 3 : 2.5 }}"
                                ></circle>
                            @endforeach
                        </svg>

                        <div class="mt-2 grid text-center font-financial-table" style="grid-template-columns: repeat({{ $jumlahTitikTren }}, minmax(0, 1fr));">
                            @foreach ($trend as $item)
                                <div class="flex flex-col items-center {{ $loop->last ? 'rounded-lg bg-primary-fixed/30 py-0.5' : '' }}">
                                    <span class="{{ $loop->last ? 'text-primary font-bold' : 'text-slate-text-muted' }} text-[0.6875rem]">{{ $item['period'] }}{{ $loop->last ? ' (Kini)' : '' }}</span>
                                    <span class="mt-0.5 font-semibold tabular-nums {{ $loop->last ? 'text-primary font-bold' : 'text-slate-text-primary' }}">{{ $item['label_omzet'] }}</span>
                                    <span class="text-[0.625rem] {{ $loop->last ? 'font-semibold text-primary' : 'text-slate-text-secondary' }}">{{ $item['label_tonnage'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="py-10 text-center font-sans text-body-sm text-slate-text-muted">Belum ada data tren periode.</p>
                    @endif
                </div>

                <div class="mt-space-lg grid grid-cols-1 gap-space-md rounded-xl bg-surface-container-low/40 p-space-md pt-space-md md:grid-cols-3">
                    @foreach ($routeBreakdown as $route)
                        <div class="flex items-center gap-3">
                            <div @class([
                                'flex h-8 w-8 items-center justify-center rounded-lg font-sans text-body-sm font-bold',
                                'bg-banner-indigo/10 text-banner-indigo' => $route['tone'] === 'indigo',
                                'bg-banner-sky/15 text-primary' => $route['tone'] === 'sky',
                                'bg-secondary-container text-slate-text-primary' => $route['tone'] === 'slate',
                            ])>{{ $route['pct'] }}</div>
                            <div class="flex flex-col">
                                <span class="font-sans text-label-sm uppercase">{{ $route['route'] }}</span>
                                <span class="font-display text-headline-sm font-bold tabular-nums">{{ $rp($route['omzet']) }}</span>
                                <span class="font-sans text-[0.6875rem] text-slate-text-secondary">{{ $route['detail'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-xl shadow-card lg:col-span-4">
                <div>
                    <div class="mb-space-md flex items-center justify-between">
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Komposisi Biaya Armada</h2>
                        <span class="rounded bg-surface-container-low px-2 py-0.5 font-sans text-label-sm text-slate-text-secondary">{{ $rp($cost['total']) }}</span>
                    </div>
                    <p class="mb-space-lg font-sans text-body-sm text-slate-text-secondary">Distribusi realisasi modal kerja dump truck batubara berjalan.</p>

                    <div class="my-space-md flex items-center justify-center">
                        <div class="relative flex h-44 w-44 items-center justify-center">
                            <svg class="h-full w-full -rotate-90" viewBox="0 0 36 36">
                                <path class="text-surface-container-high" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="4.5"></path>
                                <path class="text-primary-container" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="{{ $cost['trip_money_pct'] }}, 100" stroke-linecap="round" stroke-width="4.5"></path>
                                <path class="text-warning-amber" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="{{ $cost['sparepart_pct'] }}, 100" stroke-dashoffset="-{{ $cost['trip_money_pct'] }}" stroke-linecap="round" stroke-width="4.5"></path>
                            </svg>
                            <div class="absolute flex flex-col items-center justify-center text-center">
                                <span class="font-display text-headline-sm font-bold">{{ $cost['trip_money_pct'] }}%</span>
                                <span class="font-sans text-[0.625rem] uppercase">BBM &amp; U.Jalan</span>
                            </div>
                        </div>
                    </div>

                    <div class="my-space-md flex flex-col gap-2.5">
                        <div class="flex items-center justify-between font-sans text-body-sm">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-primary-container"></span>
                                <span class="font-medium text-slate-text-primary">Uang Jalan Solar &amp; Tol</span>
                            </div>
                            <span class="font-financial-table font-semibold tabular-nums">{{ $rp($cost['trip_money']) }}</span>
                        </div>
                        <div class="flex items-center justify-between font-sans text-body-sm">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-warning-amber"></span>
                                <span class="font-medium text-slate-text-primary">Suku Cadang &amp; Bengkel</span>
                            </div>
                            <span class="font-financial-table font-semibold tabular-nums text-warning-amber">{{ $rp($cost['sparepart']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-space-md flex items-start gap-space-sm rounded-xl bg-warning-surface/70 p-space-md">
                    <span class="material-symbols-outlined mt-0.5 shrink-0 text-[1.375rem] text-warning-amber">report_problem</span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-primary">Perhatian Audit Biaya Servis</span>
                        @if ($auditUnit !== [])
                            <p class="mt-1 font-sans text-[0.75rem] leading-snug text-slate-text-secondary">
                                @foreach ($auditUnit as $unit)
                                    Unit <strong class="text-slate-text-primary">{{ $unit['plate'] }}</strong>
                                    menyerap <strong class="text-slate-text-primary">{{ $rp($unit['sparepart']) }}</strong>
                                    ({{ number_format($unit['rasio_persen'], 1, ',', '.') }}% dari omzet)
                                    @if (! $loop->last) &bull; @endif
                                @endforeach
                            </p>
                        @else
                            <p class="mt-1 font-sans text-[0.75rem] leading-snug text-slate-text-secondary">
                                Tidak ada beban sparepart tercatat pada periode {{ $period['code'] }}.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-executive-layout>
