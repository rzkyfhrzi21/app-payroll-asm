<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN SIDEBAR PIMPINAN / EKSEKUTIF):
     - Tujuan Komponen: Navigasi samping (Sidebar) khusus Pimpinan / Direksi Logistik Batubara.
       Fokus pada pengawasan tingkat tinggi (helicopter view), otorisasi, dan audit laba-rugi.
     - Analogi Dunia Nyata: Seperti meja kerja Direktur Utama: ada tombol ACC persetujuan
       gaji (hanya direktur yang boleh menyetujui pencairan uang perusahaan), laporan
       laba kotor dan bersih, radar pantauan armada di jalan, serta cap stempel perusahaan.
     - Struktur Menu Pimpinan:
       * Menu Utama: Executive Dashboard (KPI ringkas omzet dan armada).
       * Otorisasi & Payroll: Data Penggajian & ACC (eksklusif pimpinan), Laporan Finansial.
       * Monitoring Operasional: Monitoring Pengantaran batubara & Kalender agenda.
       * Pengaturan Perusahaan: Profil Perusahaan (kop surat, logo, rekening transfer).
     ============================================================================== -->
@php
    $menuGroups = [
        [
            'label' => 'Menu Utama',
            'items' => [
                [
                    'label' => 'Executive Dashboard',
                    'icon' => 'monitoring',
                    'route' => 'executive.dashboard',
                    'active' => request()->routeIs('executive.dashboard'),
                ],
            ],
        ],
        [
            'label' => 'Otorisasi & Payroll',
            'items' => [
                [
                    'label' => 'Data Penggajian & ACC',
                    'icon' => 'fact_check',
                    'route' => 'executive.payroll.index',
                    'active' => request()->routeIs('executive.payroll.*'),
                ],
                [
                    'label' => 'Laporan Finansial',
                    'icon' => 'assessment',
                    'route' => 'executive.reports.index',
                    'active' => request()->routeIs('executive.reports.*'),
                ],
            ],
        ],
        [
            'label' => 'Monitoring Operasional',
            'items' => [
                [
                    'label' => 'Monitoring Pengantaran',
                    'icon' => 'route',
                    'route' => 'executive.trips',
                    'active' => request()->routeIs('executive.trips'),
                ],
                [
                    'label' => 'Kalender',
                    'icon' => 'calendar_month',
                    'action' => 'calendar',
                ],
            ],
        ],
        [
            'label' => 'Pengaturan Perusahaan',
            'items' => [
                [
                    'label' => 'Profil Perusahaan',
                    'icon' => 'apartment',
                    'route' => 'executive.company.edit',
                    'active' => request()->routeIs('executive.company.*'),
                    'preserve_periode' => false,
                ],
            ],
        ],
    ];

    // Periode aktif dibawa ke setiap tautan agar Pimpinan tidak kehilangan konteks.
    $kodePeriodeAktif = $periodeAktif['code'] ?? null;
    $parameterPeriode = ($kodePeriodeAktif !== null && $kodePeriodeAktif !== '—')
        ? ['periode' => $kodePeriodeAktif]
        : [];

    $otorisasi = $ringkasanOtorisasi ?? [
        'jumlah_periode' => 0,
        'jumlah_supir' => 0,
        'nilai_kas' => 0,
        'kode_periode' => $kodePeriodeAktif ?? '—',
    ];
@endphp

<div
    x-cloak
    x-show="sidebarOpen"
    x-transition.opacity
    class="fixed inset-0 z-40 bg-sidebar-dark/60 backdrop-blur-sm lg:hidden"
    @click="sidebarOpen = false"
></div>

<aside
    class="executive-sidebar-panel fixed left-0 top-0 z-50 flex h-screen w-[16.25rem] flex-col justify-between overflow-y-auto overflow-x-hidden bg-sidebar-dark shadow-card transition-all duration-300 lg:translate-x-0"
    :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'lg:w-[4.5rem]' : 'lg:w-[16.25rem]'
    ]"
>
    <div class="flex flex-col">
        <div
            class="flex h-16 items-center gap-2.5 bg-sidebar-dark px-4 transition-all"
            :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : 'px-6'"
        >
            @if (!empty($dataPerusahaan?->url_logo))
                <img
                    src="{{ $dataPerusahaan->url_logo }}"
                    alt="Logo {{ $dataPerusahaan->nama_perusahaan }}"
                    class="h-9 w-9 shrink-0 rounded-lg bg-white object-contain p-0.5 shadow-sm"
                >
            @else
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep text-on-primary shadow-hero">
                    <span class="material-symbols-outlined text-[1.3125rem]">domain</span>
                </div>
            @endif
            <div class="executive-sidebar-hide flex flex-col min-w-0" x-show="!sidebarCollapsed">
                <span class="font-display text-headline-sm font-bold leading-none tracking-tight text-white truncate max-w-[10.5rem]">{{ $dataPerusahaan->kode_perusahaan ?: ($dataPerusahaan->nama_perusahaan ?? config('app.name')) }}</span>
                <span class="mt-1 font-sans text-label-sm uppercase tracking-wider text-slate-text-muted truncate">{{ $dataPerusahaan->bidang_usaha ?: 'Panel Pimpinan' }}</span>
            </div>
            <button type="button" class="ml-auto rounded-lg p-1.5 text-slate-text-muted hover:bg-sidebar-surface hover:text-white lg:hidden" @click="sidebarOpen = false">
                <span class="material-symbols-outlined text-[1.25rem]">close</span>
            </button>
        </div>

        <nav class="flex flex-col gap-1 px-2.5 pb-4">
            @foreach ($menuGroups as $group)
                <div class="executive-sidebar-hide px-3 pb-1 pt-4" x-show="!sidebarCollapsed">
                    <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-muted">
                        {{ $group['label'] }}
                    </span>
                </div>
                <div x-show="sidebarCollapsed" class="my-1.5 hidden border-t border-white/10 lg:block"></div>

                @foreach ($group['items'] as $item)
                    @if (($item['action'] ?? null) === 'calendar')
                        <button
                            type="button"
                            title="{{ $item['label'] }}"
                            class="executive-sidebar-item flex min-h-[2.75rem] w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors"
                            :class="[
                                calendarPanelOpen ? 'bg-sidebar-active font-semibold text-white shadow-sm' : 'text-slate-text-muted hover:bg-sidebar-surface hover:text-white',
                                sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''
                            ]"
                            @click="calendarPanelOpen = true; sidebarOpen = false"
                        >
                            <span class="material-symbols-outlined shrink-0 text-[1.25rem]">{{ $item['icon'] }}</span>
                            <span class="executive-sidebar-hide truncate font-sans text-body-md font-medium" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                        </button>
                    @else
                        <a
                            href="{{ route($item['route'], ($item['preserve_periode'] ?? true) ? $parameterPeriode : []) }}"
                            title="{{ $item['label'] }}"
                            @class([
                                'executive-sidebar-item flex min-h-[2.75rem] items-center gap-3 rounded-lg px-3 py-2.5 transition-colors',
                                'bg-sidebar-active font-semibold text-white shadow-sm' => $item['active'],
                                'text-slate-text-muted hover:bg-sidebar-surface hover:text-white' => ! $item['active'],
                            ])
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                        >
                            <span class="material-symbols-outlined shrink-0 text-[1.25rem]">{{ $item['icon'] }}</span>
                            <span class="executive-sidebar-hide truncate font-sans text-body-md" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            @endforeach
        </nav>
    </div>

    <div class="flex flex-col gap-4 px-3 pb-5">
        <a
            href="{{ route('executive.payroll.index', $parameterPeriode) }}"
            class="executive-sidebar-hide group block rounded-xl border border-white/10 bg-sidebar-surface p-4 shadow-sm transition-colors hover:border-sidebar-active/50 hover:bg-sidebar-surface/90"
            x-show="!sidebarCollapsed"
            title="Buka antrean otorisasi penggajian Pimpinan"
        >
            <div class="mb-1 flex items-center justify-between">
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wide text-slate-text-muted">Perlu Otorisasi</span>
                <span class="material-symbols-outlined text-[1.125rem] text-warning-amber">pending_actions</span>
            </div>
            <div class="font-sans text-financial-total tabular-nums text-white group-hover:text-banner-sky transition-colors">
                {{ $otorisasi['jumlah_supir'] }} Supir
            </div>
            <div class="mt-1 flex items-center justify-between gap-2">
                <span class="font-sans text-body-sm text-slate-text-muted">Nilai Net Pay {{ $otorisasi['kode_periode'] }}</span>
                <span class="font-sans text-label-sm font-semibold text-net-pay-emerald">
                    Rp {{ number_format((int) $otorisasi['nilai_kas'], 0, ',', '.') }}
                </span>
            </div>
            @if (($otorisasi['jumlah_periode'] ?? 0) > 1)
                <div class="mt-1.5 font-sans text-label-sm text-slate-text-muted">
                    {{ $otorisasi['jumlah_periode'] }} periode menunggu keputusan
                </div>
            @endif
        </a>

        <div
            class="flex items-center justify-between px-1"
            :class="sidebarCollapsed ? 'lg:flex-col lg:gap-2 lg:px-0' : ''"
        >
            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-banner-indigo text-on-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">shield_person</span>
                    </div>
                    <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-net-pay-emerald ring-2 ring-sidebar-dark"></span>
                </div>
                <div class="executive-sidebar-hide flex flex-col" x-show="!sidebarCollapsed">
                    <span class="font-sans text-body-sm font-semibold leading-tight text-white">{{ auth()->user()->name ?? 'Pimpinan' }}</span>
                    <span class="font-sans text-label-sm text-slate-text-muted">Direksi / Owner</span>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-text-muted transition-colors hover:bg-sidebar-surface hover:text-danger-red" title="Logout">
                    <span class="material-symbols-outlined text-[1.25rem]">logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>
