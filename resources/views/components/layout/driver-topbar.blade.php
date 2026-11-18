<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN TOPBAR SUPIR):
     - Tujuan Komponen: Bilah atas (Topbar) khusus portal supir truk logistik.
     - Analogi Dunia Nyata: Seperti kaca spion atas: menampilkan NIP supir yang sedang login,
       jam operasional saat ini, tombol kalender jadwal jalan, dan menu akun supir.
     - Komponen Penting:
       * Tampilan NIP supir aktif.
       * Jam real-time & tanggal Indonesia.
       * Tombol kalender dan profil akun.
     ============================================================================== -->
@php
    $profilSupir = $profilSupir ?? ['nip' => '—'];
@endphp

<header
    class="driver-topbar-header fixed left-0 right-0 top-0 z-30 h-16 bg-card-bg/90 shadow-card backdrop-blur-xl transition-all duration-300"
    :class="sidebarCollapsed ? 'lg:left-[4.5rem]' : 'lg:left-[16.25rem]'"
>
    <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-2.5">
            {{-- Hamburger Mobile Drawer --}}
            <button
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-text-secondary hover:bg-surface-container-low hover:text-slate-text-primary lg:hidden"
                @click="sidebarOpen = true"
                aria-label="Buka Menu Navigasi"
            >
                <span class="material-symbols-outlined text-[1.375rem]">menu</span>
            </button>

            {{-- Toggle Collapsible Desktop --}}
            <button
                type="button"
                class="hidden h-10 w-10 items-center justify-center rounded-lg text-slate-text-secondary transition-colors hover:bg-surface-container-low hover:text-slate-text-primary lg:flex"
                @click="toggleSidebarDesktop()"
                :title="sidebarCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
                aria-label="Toggle Sidebar"
            >
                <span class="material-symbols-outlined text-[1.35rem]" x-text="sidebarCollapsed ? 'menu' : 'menu_open'">menu_open</span>
            </button>

            <div class="flex min-w-0 flex-col">
                <span class="truncate font-display text-headline-sm font-semibold tracking-tight text-slate-text-primary">Portal Supir</span>
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-net-pay-emerald"></span>
                    <span class="truncate font-sans text-label-sm font-semibold uppercase tracking-wider">{{ $profilSupir['nip'] }}</span>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            {{-- Jam & Tanggal Realtime --}}
            <div
                class="hidden items-center gap-2.5 border-r border-card-border pr-3 sm:flex"
                x-data="jamRealtime()"
                x-init="mulai()"
            >
                <span class="material-symbols-outlined text-[1.125rem] text-banner-indigo">schedule</span>
                <div class="flex flex-col leading-tight">
                    <span class="font-financial-table text-body-sm font-bold tabular-nums text-slate-text-primary" x-text="jam">--:--:--</span>
                    <span class="font-sans text-[0.625rem] font-bold uppercase tracking-wide text-slate-text-muted" x-text="tanggal">—</span>
                </div>
            </div>

            {{-- Tombol Buka Panel Kalender --}}
            <button
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-text-secondary transition-colors hover:bg-surface-container-low hover:text-slate-text-primary"
                @click="calendarPanelOpen = true"
                title="Buka Kalender Operasional"
                aria-label="Kalender"
            >
                <span class="material-symbols-outlined text-[1.25rem] text-banner-indigo">calendar_month</span>
            </button>

            {{-- Tombol Layar Penuh --}}
            <x-ui.tombol-layar-penuh />

            <x-layout.user-dropdown
                :nama="auth()->user()->name ?? ($profilSupir['name'] ?? 'Supir')"
                peran="Supir Armada"
                ruteDashboard="driver.dashboard"
                ruteProfil="driver.profile.edit"
                warnaAvatar="bg-primary text-on-primary"
            />
        </div>
    </div>
</header>
