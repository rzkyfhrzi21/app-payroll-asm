<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN SIDEBAR PORTAL SUPIR):
     - Tujuan Komponen: Navigasi samping (Sidebar) khusus supir truk logistik batubara.
       Menyediakan akses mandiri (self-service) tanpa harus bertanya manual ke kantor HRD.
     - Analogi Dunia Nyata: Seperti map dasbor di dalam kabin truk supir: berisi slip gaji,
       lembar surat jalan (DO) harian, buku catatan kasbon/potongan, kartu unit truk,
       dan kalkulator perkiraan uang komisi bila menambah ritase pengantaran.
     - Struktur Menu Supir:
       * Slip Gaji (driver.dashboard): Ringkasan gaji bersih bulan berjalan.
       * Riwayat Pengantaran & DO (driver.trips.index): Pantau status kirim dan upload bukti SJ.
       * Buku Kasbon (driver.loans.index): Sisa cicilan kasbon dan jadwal potong gaji.
       * Simulasi Gaji (driver.simulation.index): Hitung perkiraan komisi 10% berbasis ritase.
       * Profil Supir & Armada (driver.profile.edit): Info NIP, kontak, dan plat nomor truk.
     ============================================================================== -->
@php
    $profilSupir = $profilSupir ?? [
        'initial' => '—',
        'name' => 'Pengguna',
        'nip' => '—',
        'plate' => '—',
        'truck' => 'Belum ada armada',
    ];
    $periodeAktif = $periodeAktif ?? ['status' => 'Belum Ada Data'];

    $items = [
        ['label' => 'Slip Gaji', 'icon' => 'payments', 'route' => 'driver.dashboard', 'active' => request()->routeIs('driver.dashboard')],
        ['label' => 'Riwayat Pengantaran & DO', 'icon' => 'conversion_path', 'route' => 'driver.trips.index', 'active' => request()->routeIs('driver.trips.*')],
        ['label' => 'Buku Kasbon', 'icon' => 'account_balance_wallet', 'route' => 'driver.loans.index', 'active' => request()->routeIs('driver.loans.*')],
        ['label' => 'Simulasi Gaji', 'icon' => 'calculate', 'route' => 'driver.simulation.index', 'active' => request()->routeIs('driver.simulation.*')],
        ['label' => 'Profil Supir & Armada', 'icon' => 'badge', 'route' => 'driver.profile.edit', 'active' => request()->routeIs('driver.profile.*')],
        ['label' => 'Kalender Operasional', 'icon' => 'calendar_month', 'action' => 'calendar', 'active' => false],
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
    class="driver-sidebar-panel fixed left-0 top-0 z-50 flex h-screen w-[16.25rem] flex-col justify-between overflow-y-auto overflow-x-hidden bg-sidebar-dark shadow-card transition-all duration-300 lg:translate-x-0"
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
            <div class="driver-sidebar-hide flex flex-col min-w-0" x-show="!sidebarCollapsed">
                <span class="font-display text-headline-sm font-bold leading-none tracking-tight text-white truncate max-w-[10.5rem]">{{ $dataPerusahaan->kode_perusahaan ?: ($dataPerusahaan->nama_perusahaan ?? config('app.name')) }}</span>
                <span class="mt-1 font-sans text-label-sm uppercase tracking-wider text-slate-text-muted truncate">{{ $dataPerusahaan->bidang_usaha ?: 'Portal Supir' }}</span>
            </div>
            <button type="button" class="ml-auto rounded-lg p-1.5 text-slate-text-muted hover:bg-sidebar-surface hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="Tutup Menu">
                <span class="material-symbols-outlined text-[1.25rem]">close</span>
            </button>
        </div>

        <div class="mx-3 mb-2 rounded-xl border border-white/10 bg-sidebar-surface p-3" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary font-display text-headline-sm font-bold text-on-primary">
                    <span>{{ $profilSupir['initial'] }}</span>
                </div>
                <div class="flex min-w-0 flex-col">
                    <span class="truncate font-sans text-body-sm font-semibold text-white">{{ $profilSupir['name'] }}</span>
                    <span class="truncate font-sans text-label-sm text-slate-text-muted">{{ $profilSupir['nip'] }}</span>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-1.5 rounded bg-net-pay-emerald/15 px-2 py-1">
                <span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span>
                <span class="font-sans text-label-sm font-semibold text-net-pay-emerald truncate">Status Gaji: {{ $periodeAktif['status'] }}</span>
            </div>
        </div>

        <nav class="flex flex-col gap-1 px-2.5 pb-4">
            @foreach ($items as $item)
                @if (isset($item['action']) && $item['action'] === 'calendar')
                    <button
                        type="button"
                        title="{{ $item['label'] }}"
                        class="driver-sidebar-item flex min-h-[2.75rem] w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-slate-text-muted transition-colors hover:bg-sidebar-surface hover:text-white"
                        :class="[
                            calendarPanelOpen ? 'bg-sidebar-active font-semibold text-white shadow-sm' : '',
                            sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''
                        ]"
                        @click="calendarPanelOpen = true; sidebarOpen = false"
                    >
                        <span class="material-symbols-outlined shrink-0 text-[1.25rem] text-banner-indigo">{{ $item['icon'] }}</span>
                        <span class="driver-sidebar-hide truncate font-sans text-body-md" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                    </button>
                @else
                    <a
                        href="{{ route($item['route']) }}"
                        title="{{ $item['label'] }}"
                        @class([
                            'driver-sidebar-item flex min-h-[2.75rem] items-center gap-3 rounded-lg px-3 py-2.5 transition-colors',
                            'bg-sidebar-active font-semibold text-white shadow-sm' => $item['active'],
                            'text-slate-text-muted hover:bg-sidebar-surface hover:text-white' => ! $item['active'],
                        ])
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                        @click="sidebarOpen = false"
                    >
                        <span class="material-symbols-outlined shrink-0 text-[1.25rem]">{{ $item['icon'] }}</span>
                        <span class="driver-sidebar-hide truncate font-sans text-body-md" x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
    </div>

    <div class="flex flex-col gap-3 px-3 pb-5">
        <div class="rounded-xl border border-white/10 bg-sidebar-surface p-3.5" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-banner-sky">Unit Armada</span>
            <div class="mt-0.5 font-display text-headline-sm font-bold text-white">{{ $profilSupir['plate'] }}</div>
            <div class="font-sans text-label-sm text-slate-text-muted">{{ $profilSupir['truck'] }}</div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                title="Keluar Akun"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-white/5 py-2 font-sans text-label-md font-semibold text-slate-text-muted transition-colors hover:bg-danger-red/15 hover:text-danger-red"
                :class="sidebarCollapsed ? 'lg:px-1' : ''"
            >
                <span class="material-symbols-outlined text-[1.125rem]">logout</span>
                <span class="driver-sidebar-hide" x-show="!sidebarCollapsed">Keluar Akun</span>
            </button>
        </form>
    </div>
</aside>
