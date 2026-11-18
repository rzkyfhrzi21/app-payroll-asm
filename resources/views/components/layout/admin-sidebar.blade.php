<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN SIDEBAR ADMIN HRD):
     - Tujuan Komponen: Navigasi bilah samping (Sidebar) untuk pengguna bertipe Admin/HRD.
       Mengatur pintu masuk ke seluruh modul: Keuangan & Audit, Operasional Pengantaran,
       dan Manajemen Master Armada Truk.
     - Analogi Dunia Nyata: Seperti lemari arsip kerja berlabel di samping meja Admin:
       rak atas untuk berkas Gaji & Laporan, rak tengah untuk Surat Jalan & Kasbon,
       dan rak bawah untuk data pokok supir, truk, serta rute tambang.
     - Struktur Grup Menu:
       * Keuangan & Audit: Dashboard, Payroll, Laporan & Slip Gaji.
       * Operasional Pengantaran: Operasional Pengantaran (Surat Jalan), Pengeluaran, Kasbon, Kalender.
       * Master & Armada: Data Supir, Unit Armada Truk, Tarif Rute & Jalur.
     - Fitur Anti-FOUC & Responsif:
       * Menyimpan status ciut (collapsed) di `localStorage.admin_sidebar_collapsed`
         sehingga saat halaman dibuka ulang, posisi sidebar tidak berkedip.
       * Di bagian bawah terdapat kartu ringkasan periode aktif ($ringkasanKasbonAdmin).
     ============================================================================== -->
@php
    $menuGroups = [
        [
            'label' => 'Keuangan & Audit',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'space_dashboard',
                    'route' => 'admin.dashboard',
                    'active' => request()->routeIs('admin.dashboard'),
                ],
                [
                    'label' => 'Payroll',
                    'icon' => 'payments',
                    'route' => 'admin.payroll.index',
                    'active' => request()->routeIs('admin.payroll.*'),
                ],
                [
                    'label' => 'Laporan & Slip Gaji',
                    'icon' => 'receipt_long',
                    'route' => 'admin.reports.index',
                    'active' => request()->routeIs('admin.reports.*'),
                ],
            ],
        ],
        [
            'label' => 'Operasional Pengantaran',
            'items' => [
                [
                    'label' => 'Operasional Pengantaran',
                    'icon' => 'route',
                    'route' => 'admin.operational.trips.index',
                    'active' => request()->routeIs('admin.operational.trips.*'),
                ],
                [
                    'label' => 'Pengeluaran Operasional',
                    'icon' => 'receipt',
                    'route' => 'admin.operational.expenses.index',
                    'active' => request()->routeIs('admin.operational.expenses.*'),
                ],
                [
                    'label' => 'Kasbon & Potongan',
                    'icon' => 'account_balance_wallet',
                    'route' => 'admin.operational.loans.index',
                    'active' => request()->routeIs('admin.operational.loans.*'),
                ],
                [
                    'label' => 'Kalender',
                    'icon' => 'calendar_month',
                    'action' => 'calendar',
                ],
            ],
        ],
        [
            'label' => 'Master & Armada',
            'items' => [
                [
                    'label' => 'Data Supir',
                    'icon' => 'badge',
                    'route' => 'admin.master.employees.index',
                    'active' => request()->routeIs('admin.master.employees.*'),
                ],
                [
                    'label' => 'Unit Armada Truk',
                    'icon' => 'rv_hookup',
                    'route' => 'admin.master.trucks.index',
                    'active' => request()->routeIs('admin.master.trucks.*'),
                ],
                [
                    'label' => 'Tarif Rute & Jalur',
                    'icon' => 'alt_route',
                    'route' => 'admin.master.routes.index',
                    'active' => request()->routeIs('admin.master.routes.*'),
                ],
            ],
        ],
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
    class="admin-sidebar-panel fixed left-0 top-0 z-50 flex h-screen w-[16.25rem] flex-col justify-between overflow-y-auto overflow-x-hidden bg-sidebar-dark shadow-card transition-all duration-300 lg:translate-x-0"
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
                    <span class="material-symbols-outlined text-[1.3125rem]">local_shipping</span>
                </div>
            @endif
            <div class="admin-sidebar-hide flex flex-col min-w-0" x-show="!sidebarCollapsed">
                <span class="font-display text-headline-sm font-bold leading-none tracking-tight text-white truncate max-w-[10.5rem]">{{ $dataPerusahaan->kode_perusahaan ?: ($dataPerusahaan->nama_perusahaan ?? config('app.name')) }}</span>
                <span class="mt-1 font-sans text-label-sm uppercase tracking-wider text-slate-text-muted truncate">{{ $dataPerusahaan->bidang_usaha ?: 'Logistik &amp; Penggajian' }}</span>
            </div>
            <button type="button" class="ml-auto rounded-lg p-1.5 text-slate-text-muted hover:bg-sidebar-surface hover:text-white lg:hidden" @click="sidebarOpen = false">
                <span class="material-symbols-outlined text-[1.25rem]">close</span>
            </button>
        </div>

        <nav class="flex flex-col gap-1 px-2.5 pb-4">
            @foreach ($menuGroups as $group)
                <div class="admin-sidebar-hide px-3 pb-1 pt-4" x-show="!sidebarCollapsed">
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
                            class="admin-sidebar-item flex min-h-[2.75rem] w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors"
                            :class="[
                                calendarPanelOpen ? 'bg-sidebar-active font-semibold text-white shadow-sm' : 'text-slate-text-muted hover:bg-sidebar-surface hover:text-white',
                                sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''
                            ]"
                            @click="calendarPanelOpen = true; sidebarOpen = false"
                        >
                            <span class="material-symbols-outlined shrink-0 text-[1.25rem]">{{ $item['icon'] }}</span>
                            <span class="admin-sidebar-hide truncate font-sans text-body-md font-medium" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                        </button>
                    @elseif (! empty($item['alert']))
                        <button
                            type="button"
                            title="{{ $item['label'] }}"
                            class="admin-sidebar-item flex min-h-[2.75rem] items-center gap-3 rounded-lg px-3 py-2.5 text-left text-slate-text-muted transition-colors hover:bg-sidebar-surface hover:text-white"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                            onclick="window.alert(@js($item['alert']))"
                        >
                            <span class="material-symbols-outlined shrink-0 text-[1.25rem]">{{ $item['icon'] }}</span>
                            <span class="admin-sidebar-hide truncate font-sans text-body-md font-medium" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                        </button>
                    @else
                        <a
                            href="{{ route($item['route']) }}"
                            title="{{ $item['label'] }}"
                            @class([
                                'admin-sidebar-item flex min-h-[2.75rem] items-center gap-3 rounded-lg px-3 py-2.5 transition-colors',
                                'bg-sidebar-active font-semibold text-white shadow-sm' => $item['active'],
                                'text-slate-text-muted hover:bg-sidebar-surface hover:text-white' => ! $item['active'],
                            ])
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                        >
                            <span class="material-symbols-outlined shrink-0 text-[1.25rem]">{{ $item['icon'] }}</span>
                            <span class="admin-sidebar-hide truncate font-sans text-body-md" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            @endforeach
        </nav>
    </div>

    <div class="flex flex-col gap-4 px-3 pb-5">
        <a
            href="{{ route('admin.payroll.index') }}"
            class="admin-sidebar-hide group block rounded-xl border border-white/10 bg-sidebar-surface p-4 shadow-sm transition-colors hover:border-sidebar-active/50 hover:bg-sidebar-surface/90"
            x-show="!sidebarCollapsed"
            title="Buka Lembar Kerja Payroll Periode Aktif"
        >
            <div class="mb-1 flex items-center justify-between">
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wide text-slate-text-muted">Periode Gaji Aktif</span>
                <span class="inline-flex items-center gap-1 rounded-md bg-sidebar-active/20 px-2 py-0.5 font-sans text-label-sm font-bold text-banner-sky">{{ $ringkasanKasbonAdmin['periode'] }}</span>
            </div>
            <div class="font-sans text-financial-total tabular-nums text-white group-hover:text-banner-sky transition-colors">{{ $ringkasanKasbonAdmin['total_gaji_bersih'] ?? $ringkasanKasbonAdmin['nominal'] }}</div>
            <div class="mt-1.5 flex items-center justify-between gap-2">
                <span class="font-sans text-body-sm text-slate-text-muted">{{ $ringkasanKasbonAdmin['jumlah_supir_payroll'] ?? $ringkasanKasbonAdmin['jumlah_supir'] }} Supir &bull; {{ $ringkasanKasbonAdmin['total_rit'] ?? 0 }} Rit</span>
                <span class="font-sans text-label-sm font-semibold text-net-pay-emerald">{{ $ringkasanKasbonAdmin['status_periode'] ?? 'Aktif' }}</span>
            </div>
        </a>

        <div
            class="flex items-center justify-between px-1"
            :class="sidebarCollapsed ? 'lg:flex-col lg:gap-2 lg:px-0' : ''"
        >
            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-on-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">person</span>
                    </div>
                    <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-net-pay-emerald ring-2 ring-sidebar-dark"></span>
                </div>
                <div class="admin-sidebar-hide flex flex-col" x-show="!sidebarCollapsed">
                    <span class="font-sans text-body-sm font-semibold leading-tight text-white">{{ auth()->user()->name ?? 'Admin HRD' }}</span>
                    <span class="font-sans text-label-sm text-slate-text-muted">Admin HRD</span>
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
