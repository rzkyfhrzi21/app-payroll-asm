<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN TOPBAR ADMIN HRD):
     - Tujuan Komponen: Bilah navigasi atas (Topbar Header) untuk Admin HRD yang melayang
       di bagian atas layar (fixed left-0 right-0 top-0 z-30).
     - Analogi Dunia Nyata: Seperti jam dan papan kendali di atas meja kasir/pos kontrol:
       ada tombol buka/tutup laci menu samping, kotak pencarian cepat (Ctrl+K),
       jam dinding digital real-time, tombol layar penuh, tombol kalender, dan akun user.
     - Fitur Utama:
       * Tombol Toggle Sidebar (Desktop & Mobile).
       * Global Search (Ctrl+K): mencari rute, menu, nama supir, atau nomor plat armada.
       * Widget Jam & Tanggal Real-Time (Alpine jamRealtime).
       * Tombol Layar Penuh komponen bersama x-ui.tombol-layar-penuh.
       * Tombol Buka Panel Kalender Operasional (Alpine calendarPanelOpen).
       * Profil Pengguna & Logout lewat komponen x-layout.user-dropdown.
     ============================================================================== -->
@php
    $periodeAktif = $periodeAktif ?? ['code' => '—', 'range' => 'Belum ada periode'];
@endphp

<header
    class="admin-topbar-header fixed left-0 right-0 top-0 z-30 h-16 bg-card-bg/90 shadow-card backdrop-blur-xl transition-all duration-300"
    :class="sidebarCollapsed ? 'lg:left-[4.5rem]' : 'lg:left-[16.25rem]'"
>
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            {{-- Hamburger Mobile Drawer --}}
            <button
                type="button"
                class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-text-secondary hover:bg-surface-container-low hover:text-slate-text-primary lg:hidden"
                @click="sidebarOpen = true"
                aria-label="Buka Menu Navigasi"
            >
                <span class="material-symbols-outlined text-[1.375rem]">menu</span>
            </button>

            {{-- Hamburger Toggle Collapsible Desktop Mode (Icon-Only Rail ~64px — BRIEF Bab 6.1) --}}
            <button
                type="button"
                class="hidden h-10 w-10 items-center justify-center rounded-lg text-slate-text-secondary transition-colors hover:bg-surface-container-low hover:text-slate-text-primary lg:flex"
                @click="toggleSidebarDesktop()"
                :title="sidebarCollapsed ? 'Perluas Sidebar Menu' : 'Ciutkan Sidebar (Mode Ikon)'"
                aria-label="Toggle Sidebar"
            >
                <span class="material-symbols-outlined text-[1.35rem]" x-text="sidebarCollapsed ? 'menu' : 'menu_open'">menu_open</span>
            </button>

            {{-- PENCARIAN GLOBAL: berbasis daftar route sesuai peran --}}
            <div
                class="relative hidden w-full max-w-md md:block"
                x-data="globalSearch(@js($hasilPencarianAwal ?? []))"
                @click.outside="tutup()"
                @keydown.escape="tutup()"
            >
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-secondary">search</span>
                <input
                    type="text"
                    x-model="kataKunci"
                    @input.debounce.200ms="cari()"
                    @focus="buka()"
                    @keydown.arrow-down.prevent="pilihBawah()"
                    @keydown.arrow-up.prevent="pilihAtas()"
                    @keydown.enter.prevent="bukaTerpilih()"
                    class="h-9 w-full rounded-lg bg-canvas-bg pl-9 pr-16 font-sans text-body-sm font-semibold text-slate-text-primary shadow-sm placeholder:font-normal placeholder:text-slate-text-muted focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                    placeholder="Cari halaman, supir, plat nomor..."
                    autocomplete="off"
                >
                <kbd class="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 rounded border border-card-border bg-card-bg px-1.5 py-0.5 font-sans text-[0.625rem] font-semibold text-slate-text-muted sm:block">Ctrl K</kbd>

                {{-- Panel hasil --}}
                <div
                    x-show="terbuka"
                    x-cloak
                    x-transition.opacity.duration.120ms
                    class="absolute left-0 right-0 top-11 z-40 max-h-[70vh] overflow-y-auto rounded-xl bg-card-bg p-1.5 shadow-drawer"
                >
                    {{-- Memuat --}}
                    <template x-if="memuat">
                        <div class="flex items-center gap-2 px-3 py-2 font-sans text-body-sm font-semibold text-slate-text-muted">
                            <span class="material-symbols-outlined animate-spin text-[1rem]">progress_activity</span>
                            Mencari...
                        </div>
                    </template>

                    {{-- Kosong --}}
                    <template x-if="! memuat && kataKunci.trim() !== '' && hasil.length === 0">
                        <div class="flex flex-col items-center gap-1 px-3 py-6 text-center">
                            <span class="material-symbols-outlined text-[1.5rem] text-slate-text-muted">search_off</span>
                            <span class="font-sans text-body-sm font-bold text-slate-text-secondary">Tidak ada hasil untuk "<span x-text="kataKunci"></span>"</span>
                            <span class="font-sans text-label-sm font-semibold text-slate-text-muted">Coba kata lain atau periksa hak akses Anda.</span>
                        </div>
                    </template>

                    {{-- Panduan awal --}}
                    <template x-if="! memuat && kataKunci.trim() === ''">
                        <div class="flex flex-col gap-1.5 px-2 py-2">
                            <span class="px-1 font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-muted">Halaman yang bisa Anda akses</span>
                            <template x-for="(item, urutan) in panduan" :key="urutan">
                                <a :href="item.url" class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-surface-container-low">
                                    <span class="material-symbols-outlined text-[1.125rem] text-primary" x-text="item.ikon"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-sans text-body-sm font-bold text-slate-text-primary" x-text="item.label"></span>
                                    </span>
                                    <span class="material-symbols-outlined text-[0.875rem] text-slate-text-muted">arrow_outward</span>
                                </a>
                            </template>
                        </div>
                    </template>

                    {{-- Hasil --}}
                    <template x-if="! memuat && hasil.length > 0">
                        <div class="flex flex-col">
                            <template x-for="(item, urutan) in hasil" :key="urutan">
                                <a
                                    :href="item.url"
                                    @mouseenter="terpilih = urutan"
                                    :class="terpilih === urutan ? 'bg-surface-container-low' : ''"
                                    class="flex items-start gap-2.5 rounded-lg px-2 py-1.5 transition-colors"
                                >
                                    <span class="material-symbols-outlined mt-0.5 text-[1.125rem] text-primary" x-text="item.ikon || 'description'"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-sans text-body-sm font-bold text-slate-text-primary" x-text="item.label"></span>
                                        <span class="block truncate font-sans text-label-sm font-semibold text-slate-text-muted" x-text="item.keterangan"></span>
                                    </span>
                                    <span class="mt-0.5 shrink-0 rounded bg-surface-container px-1.5 py-0.5 font-sans text-[0.625rem] font-bold uppercase tracking-wide text-slate-text-secondary" x-text="item.grup"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <div class="mt-1 flex items-center justify-between border-t border-card-border px-2 pt-1.5 font-sans text-[0.625rem] font-semibold text-slate-text-muted">
                        <span>&uarr;&darr; navigasi &bull; Enter buka &bull; Esc tutup</span>
                        <span x-text="jumlahHalaman + ' halaman'"></span>
                    </div>
                </div>
            </div>

            <div class="hidden items-center gap-2 xl:flex">
                <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">Lokasi:</span>
                <span class="rounded-md bg-surface-container-low px-2.5 py-1 font-sans text-body-sm font-bold text-slate-text-primary">Rute Operasional Aktif</span>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            {{-- JAM & TANGGAL REAL-TIME --}}
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

            {{-- Tombol Layar Penuh (semua peran) --}}
            <x-ui.tombol-layar-penuh />

            <button
                type="button"
                class="hidden items-center gap-1.5 rounded-lg bg-surface-container-low px-3 py-1.5 font-sans text-body-sm font-bold text-slate-text-primary transition-colors hover:bg-surface-container-high sm:flex"
                @click="calendarPanelOpen = true"
                title="Buka Kalender Operasional"
            >
                <span class="material-symbols-outlined text-[1.125rem] text-banner-indigo">calendar_month</span>
                <span>Periode {{ $periodeAktif['code'] }}</span>
            </button>

            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-text-secondary transition-colors hover:bg-surface-container-low hover:text-slate-text-primary sm:hidden"
                @click="calendarPanelOpen = true"
                title="Buka Kalender Operasional"
            >
                <span class="material-symbols-outlined text-[1.25rem] text-banner-indigo">calendar_month</span>
            </button>

            <x-layout.user-dropdown
                :nama="auth()->user()->name ?? 'Admin HRD'"
                :peran="$labelPeran ?? 'Admin HRD'"
                ruteDashboard="admin.payroll.index"
                ruteProfil="profile.edit"
                warnaAvatar="bg-primary text-on-primary"
            />
        </div>
    </div>
</header>
