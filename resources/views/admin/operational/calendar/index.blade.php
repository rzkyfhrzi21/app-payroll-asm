<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN KALENDER OPERASIONAL):
     - Tujuan Halaman: Memvisualisasikan kalender operasional hauling batubara, cut-off
       surat jalan timbangan, serta jadwal verifikasi dan pencairan gaji bulanan.
     - Analogi Dunia Nyata: Seperti kalender agenda dinding di pos komando logistik:
       setiap kotak tanggal diberi tanda lingkaran warna (biru untuk jadwal jalan,
       hijau untuk tanggal gajian, kuning untuk pengingat cut-off surat jalan).
     - Data dari Controller:
       * $days: Koleksi hari dalam bulan aktif beserta event/agenda per tanggal.
     ============================================================================== -->
@php
    $eventStyles = [
        'hauling' => ['chip' => 'bg-primary/10 text-primary', 'dot' => 'bg-primary'],
        'warning' => ['chip' => 'bg-warning-surface text-warning-amber', 'dot' => 'bg-warning-amber'],
        'audit' => ['chip' => 'bg-surface-container-high text-banner-indigo', 'dot' => 'bg-banner-indigo'],
        'pay' => ['chip' => 'bg-net-pay-surface text-net-pay-emerald', 'dot' => 'bg-net-pay-emerald'],
        'maintenance' => ['chip' => 'bg-surface-container text-slate-text-secondary', 'dot' => 'bg-slate-text-secondary'],
        'danger' => ['chip' => 'bg-danger-surface text-danger-red', 'dot' => 'bg-danger-red'],
    ];
@endphp

<x-app-layout title="Kalender Operasional">
    <div class="flex flex-col gap-6" x-data="calendarPage(@js($days))">
        <x-ui.page-header
            title="Kalender Operasional Pengantaran & Siklus Payroll"
            breadcrumb="Kalender Operasional"
            subtitle="Jadwal shift pengantaran 24 jam tambang, monitoring cut-off surat jalan timbangan, dan timeline verifikasi approval gaji pengemudi batu bara."
        >
            <div class="flex items-center rounded-lg bg-card-bg p-1 shadow-sm">
                <button type="button" class="rounded-lg p-1.5 text-slate-text-secondary transition-colors hover:bg-surface-container hover:text-slate-text-primary" onclick="window.alert('Navigasi bulan hanya tersedia untuk periode aktif.')">
                    <span class="material-symbols-outlined text-[1.125rem]">chevron_left</span>
                </button>
                <div class="flex items-center gap-space-xs px-space-md py-1 font-display text-headline-sm text-slate-text-primary">
                    <span class="material-symbols-outlined text-[1.125rem] text-primary">calendar_month</span>
                    <span class="mark-dummy">Sep 2026</span>
                </div>
                <button type="button" class="rounded-lg p-1.5 text-slate-text-secondary transition-colors hover:bg-surface-container hover:text-slate-text-primary" onclick="window.alert('Navigasi bulan hanya tersedia untuk periode aktif.')">
                    <span class="material-symbols-outlined text-[1.125rem]">chevron_right</span>
                </button>
            </div>

            <button type="button" class="rounded-lg bg-card-bg px-space-md py-2 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container">
                Hari Ini
            </button>

            <div class="relative inline-flex items-center">
                <select class="appearance-none rounded-lg bg-card-bg py-2 pl-3 pr-8 font-sans text-body-md font-medium text-slate-text-primary shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
                    <option>Tampilan: Bulan</option>
                    <option>Tampilan: Minggu</option>
                    <option>Tampilan: Timeline</option>
                </select>
                <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-muted">expand_more</span>
            </div>

            <button
                type="button"
                class="flex items-center gap-space-xs rounded-lg bg-primary-container px-space-md py-2 font-sans text-body-md font-semibold text-on-primary shadow-md transition-colors hover:bg-primary"
                onclick="window.alert('Form tambah jadwal operasional belum memiliki desain. Gunakan panel milestone dan shift sebagai referensi komponen.')"
            >
                <span class="material-symbols-outlined text-[1.125rem]">add_circle</span>
                + Tambah Jadwal Operasional
            </button>
        </x-ui.page-header>

        {{-- Ribbon milestone --}}
        <div class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep p-space-lg text-on-primary shadow-lg">
            <div class="pointer-events-none absolute -bottom-8 -right-8 h-64 w-64 rounded-full bg-white/5 blur-2xl"></div>

            <div class="relative z-10 flex flex-col gap-space-md">
                <div class="flex flex-col justify-between gap-space-sm sm:flex-row sm:items-center">
                    <div class="flex items-center gap-space-sm">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-white/20">
                            <span class="material-symbols-outlined text-[1.125rem]">alt_route</span>
                        </div>
                        <div>
                            <span class="mark-label-invert font-sans text-label-sm uppercase tracking-wider">Siklus Periode Pengantaran &amp; Disbursement (Sep-26)</span>
                            <div class="font-display text-headline-sm font-bold text-white">Milestone Payroll</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 self-start rounded-full bg-white/10 px-3 py-1.5 backdrop-blur-md">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-warning-amber opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-warning-amber"></span>
                        </span>
                        <span class="mark-dummy-invert font-sans text-label-sm font-semibold uppercase tracking-wide">Status: Live In-Progress</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-space-md pt-space-xs md:grid-cols-2 xl:grid-cols-4">
                    <div class="flex flex-col justify-between gap-space-xs rounded-lg bg-black/20 p-space-md backdrop-blur-sm transition-colors hover:bg-black/30">
                        <div class="flex items-center justify-between">
                            <span class="mark-label-invert font-sans text-label-sm">Milestone 01</span>
                            <span class="material-symbols-outlined text-[1.125rem] text-net-pay-emerald" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        </div>
                        <div>
                            <div class="mark-dummy-invert font-display text-headline-sm font-bold">21 Agu - 18 Sep 2026</div>
                            <p class="font-sans text-body-sm text-white/80">Periode Pengantaran Batubara</p>
                        </div>
                        <div class="mt-space-xs inline-flex w-fit items-center gap-1.5 rounded bg-net-pay-emerald/20 px-2 py-0.5 font-sans text-label-sm text-white">
                            <span class="h-1.5 w-1.5 rounded-full bg-net-pay-emerald"></span> Selesai (210 Rit)
                        </div>
                    </div>

                    <div class="relative flex flex-col justify-between gap-space-xs rounded-lg bg-white/15 p-space-md shadow-md ring-2 ring-warning-amber/80 backdrop-blur-md">
                        <div class="flex items-center justify-between">
                            <span class="mark-label-invert font-sans text-label-sm font-bold uppercase tracking-wider">Milestone 02 &bull; Sedang Berjalan</span>
                            <span class="material-symbols-outlined animate-pulse text-[1.125rem] text-warning-amber">timer</span>
                        </div>
                        <div>
                            <div class="mark-dummy-invert font-display text-headline-sm font-bold">19 Sep 2026 (23:59 WIB)</div>
                            <p class="font-sans text-body-sm text-white/90">Cut-Off Input Surat Jalan &amp; Bengkel</p>
                        </div>
                        <div class="mt-space-xs inline-flex w-fit items-center gap-1.5 rounded bg-warning-amber px-2 py-0.5 font-sans text-label-sm font-bold text-slate-text-primary">
                            <span class="h-1.5 w-1.5 animate-ping rounded-full bg-danger-red"></span> Berjalan Hari Ini (H-6 Jam)
                        </div>
                    </div>

                    <div class="flex flex-col justify-between gap-space-xs rounded-lg bg-black/20 p-space-md backdrop-blur-sm transition-colors hover:bg-black/30">
                        <div class="flex items-center justify-between">
                            <span class="mark-label-invert font-sans text-label-sm">Milestone 03</span>
                            <span class="material-symbols-outlined text-[1.125rem] text-white/50">pending</span>
                        </div>
                        <div>
                            <div class="mark-dummy-invert font-display text-headline-sm font-bold">20 - 22 Sep 2026</div>
                            <p class="font-sans text-body-sm text-white/80">Audit HRD &amp; Review Otorisasi Direksi</p>
                        </div>
                        <div class="mt-space-xs inline-flex w-fit items-center gap-1.5 rounded bg-white/10 px-2 py-0.5 font-sans text-label-sm text-white/80">
                            <span class="h-1.5 w-1.5 rounded-full bg-white/40"></span> Menunggu Antrean
                        </div>
                    </div>

                    <div class="flex flex-col justify-between gap-space-xs rounded-lg bg-black/20 p-space-md backdrop-blur-sm transition-colors hover:bg-black/30">
                        <div class="flex items-center justify-between">
                            <span class="mark-label-invert font-sans text-label-sm">Milestone 04</span>
                            <span class="material-symbols-outlined text-[1.125rem] text-white/50">account_balance</span>
                        </div>
                        <div>
                            <div class="mark-dummy-invert font-display text-headline-sm font-bold">25 Sep 2026</div>
                            <p class="font-sans text-body-sm text-white/80">Eksekusi Payroll / Transfer Bank</p>
                        </div>
                        <div class="mt-space-xs inline-flex w-fit items-center gap-1.5 rounded bg-white/10 px-2 py-0.5 font-sans text-label-sm text-white/80">
                            <span class="h-1.5 w-1.5 rounded-full bg-white/40"></span> Dijadwalkan
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-space-xl xl:grid-cols-12">
            {{-- Kalender --}}
            <div class="flex flex-col gap-space-lg xl:col-span-8">
                <div class="flex flex-wrap items-center justify-between gap-space-sm rounded-xl bg-card-bg p-space-md shadow-card">
                    <div class="flex flex-wrap items-center gap-space-md">
                        <span class="mark-label font-sans text-label-sm font-semibold uppercase">Legenda:</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-primary"></span> Pengantaran</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-warning-amber"></span> Cut-Off</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-banner-indigo"></span> Audit</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span> Pay Day</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-slate-text-secondary"></span> Maintenance</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-danger-red"></span> Danger</span>
                    </div>
                    <span class="font-sans text-label-sm text-slate-text-muted">Klik tanggal untuk melihat detail</span>
                </div>

                <div class="overflow-hidden rounded-xl bg-card-bg shadow-card">
                    <div class="grid grid-cols-7 bg-surface-container-low py-2.5 text-center">
                        <span class="mark-label font-sans text-label-sm uppercase tracking-wider">Senin</span>
                        <span class="mark-label font-sans text-label-sm uppercase tracking-wider">Selasa</span>
                        <span class="mark-label font-sans text-label-sm uppercase tracking-wider">Rabu</span>
                        <span class="mark-label font-sans text-label-sm uppercase tracking-wider">Kamis</span>
                        <span class="mark-label font-sans text-label-sm uppercase tracking-wider">Jumat</span>
                        <span class="mark-label font-sans text-label-sm uppercase tracking-wider">Sabtu</span>
                        <span class="mark-label font-sans text-label-sm uppercase tracking-wider text-danger-red/80">Minggu</span>
                    </div>

                    <div class="grid grid-cols-7 gap-1 bg-surface-container p-1">
                        @foreach ($days as $index => $day)
                            <button
                                type="button"
                                class="flex min-h-[6.875rem] flex-col justify-between rounded bg-card-bg p-2 text-left transition-colors hover:bg-surface-container-low"
                                :class="selectedIndex === {{ $index }} ? 'ring-2 ring-primary ring-offset-1' : ''"
                                @click="selectDay({{ $index }})"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="mark-dummy font-sans text-label-md font-bold {{ $day['muted'] ? 'text-slate-text-muted' : 'text-slate-text-primary' }}">{{ $day['day'] }}</span>
                                    @if (! empty($day['events']))
                                        <span class="mark-dummy font-sans text-[0.625rem] text-slate-text-muted">{{ count($day['events']) }} agenda</span>
                                    @endif
                                </div>

                                <div class="flex flex-col gap-1">
                                    @forelse ($day['events'] as $event)
                                        <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN KALENDER OPERASIONAL):
     - Tujuan Halaman: Memvisualisasikan kalender operasional hauling batubara, cut-off
       surat jalan timbangan, serta jadwal verifikasi dan pencairan gaji bulanan.
     - Analogi Dunia Nyata: Seperti kalender agenda dinding di pos komando logistik:
       setiap kotak tanggal diberi tanda lingkaran warna (biru untuk jadwal jalan,
       hijau untuk tanggal gajian, kuning untuk pengingat cut-off surat jalan).
     - Data dari Controller:
       * $days: Koleksi hari dalam bulan aktif beserta event/agenda per tanggal.
     ============================================================================== -->
@php $style = $eventStyles[$event['type']] ?? $eventStyles['maintenance']; @endphp
                                        <div class="flex items-center gap-1 rounded px-1.5 py-1 font-sans text-[0.6875rem] font-semibold leading-tight {{ $style['chip'] }}">
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $style['dot'] }}"></span>
                                            <span class="mark-dummy truncate">{{ $event['label'] }}</span>
                                        </div>
                                    @empty
                                        <span class="font-sans text-[0.625rem] text-slate-text-muted">{{ $day['muted'] ? 'Luar periode' : 'Tidak ada agenda' }}</span>
                                    @endforelse
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-space-sm rounded-xl bg-card-bg p-space-md shadow-card">
                    <span class="font-sans text-body-sm text-slate-text-secondary">
                        Menampilkan <span class="mark-dummy font-semibold">1 - 30 Sep 2026</span> &bull; <span class="mark-dummy font-semibold">35</span> sel kalender
                    </span>
                    <div class="flex items-center gap-2">
                        <span class="flex items-center gap-1.5 rounded bg-surface-container-low px-2.5 py-1 font-sans text-label-sm text-slate-text-secondary">
                            <span class="material-symbols-outlined text-[0.9375rem] text-banner-indigo">sync</span>
                            Sinkron Kalender Operasional
                        </span>
                    </div>
                </div>
            </div>

            {{-- Panel samping --}}
            <div class="flex flex-col gap-space-lg xl:col-span-4">
                <div class="relative flex flex-col gap-space-md overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                    <div class="pointer-events-none absolute -right-4 -top-4 h-28 w-28 rounded-full bg-warning-amber/10 blur-xl"></div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-space-xs">
                            <span class="material-symbols-outlined text-[1.375rem] text-warning-amber">alarm</span>
                            <h2 class="font-display text-headline-sm text-slate-text-primary">
                                Jadwal Hari Ini (<span class="mark-dummy" x-text="activeCell.day"></span> Sep)
                            </h2>
                        </div>
                        <span class="rounded-full bg-warning-surface px-2 py-0.5 font-sans text-label-sm font-bold uppercase text-warning-amber">Kritis</span>
                    </div>

                    <div class="flex flex-col gap-2 rounded-xl bg-gradient-to-br from-warning-surface to-warning-amber/20 p-space-md">
                        <div class="flex items-center justify-between">
                            <span class="mark-label font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-primary">Countdown Cut-Off Periode</span>
                            <span class="flex items-center gap-1 font-sans text-label-sm font-extrabold text-danger-red">
                                <span class="h-2 w-2 animate-ping rounded-full bg-danger-red"></span> Live
                            </span>
                        </div>
                        <div class="mark-dummy font-display text-display-hero-mobile font-extrabold tracking-tight">06 : 12 : 45</div>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Batas akhir perhitungan data trip &amp; potongan kasbon supir pukul 23:59 WIB.</p>
                    </div>

                    {{-- Agenda hari terpilih --}}
                    <div class="flex flex-col gap-2">
                        <span class="mark-label font-sans text-label-sm font-bold uppercase tracking-wider">Agenda Tanggal Terpilih</span>
                        <template x-if="activeCell.events.length === 0">
                            <div class="rounded-lg bg-surface-container-low p-3 font-sans text-body-sm text-slate-text-secondary">Tidak ada agenda operasional pada tanggal ini.</div>
                        </template>
                        <template x-for="(event, index) in activeCell.events" :key="index">
                            <div class="flex items-center gap-2 rounded-lg bg-surface-container-low p-2.5">
                                <span class="material-symbols-outlined text-[1.125rem] text-primary">event</span>
                                <span class="mark-dummy flex-1 font-sans text-body-sm font-medium" x-text="event.label"></span>
                                <span class="rounded bg-card-bg px-2 py-0.5 font-sans text-label-sm uppercase text-slate-text-secondary" x-text="event.type"></span>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-col gap-space-sm pt-space-xs">
                        <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-space-md">
                            <div class="flex items-center gap-space-sm">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                                    <span class="material-symbols-outlined text-[1.25rem] text-primary">scale</span>
                                </div>
                                <div>
                                    <div class="mark-dummy font-sans text-body-md font-semibold text-slate-text-primary">18 Pengantaran Masuk</div>
                                    <div class="font-sans text-body-sm text-slate-text-secondary">Timbangan Jembatan MIP</div>
                                </div>
                            </div>
                            <div class="mark-dummy font-display text-headline-sm font-bold text-primary">512 Ton</div>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-danger-surface/40 p-space-md">
                            <div class="flex items-center gap-space-sm">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-danger-red/15">
                                    <span class="material-symbols-outlined text-[1.25rem] text-danger-red">receipt_long</span>
                                </div>
                                <div>
                                    <div class="mark-dummy font-sans text-body-md font-semibold text-slate-text-primary">3 Surat Jalan Fisik</div>
                                    <div class="font-sans text-body-sm font-medium text-danger-red">Belum divalidasi barcode</div>
                                </div>
                            </div>
                            <button type="button" class="rounded bg-danger-red px-2.5 py-1 font-sans text-label-sm font-semibold text-on-primary shadow-sm transition-colors hover:bg-red-700" onclick="window.alert('Simulasi validasi 3 surat jalan fisik. Belum ada data yang diubah.')">
                                Validasi
                            </button>
                        </div>
                    </div>

                    <button type="button" class="flex w-full items-center justify-center gap-space-xs rounded-lg bg-card-bg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container" onclick="window.alert('Simulasi kunci data pengantaran sementara. Belum ada data yang diubah.')">
                        <span class="material-symbols-outlined text-[1.125rem]">verified_user</span>
                        Kunci Data Pengantaran Sementara
                    </button>
                </div>

                <div class="flex flex-col gap-space-md rounded-xl bg-card-bg p-space-lg shadow-card">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-space-xs">
                            <span class="material-symbols-outlined text-[1.375rem] text-primary">badge</span>
                            <h2 class="font-display text-headline-sm text-slate-text-primary">Shift Logistik &amp; Timbangan</h2>
                        </div>
                        <span class="font-sans text-label-sm text-slate-text-muted">Operasional 24 Jam</span>
                    </div>

                    <div class="relative flex flex-col gap-space-xs rounded-xl bg-surface-container-low p-space-md">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span>
                                <span class="font-sans text-label-md font-bold text-slate-text-primary">Shift 1 (Siang)</span>
                            </div>
                            <span class="font-mono font-sans text-label-sm text-slate-text-secondary">07:00 - 19:00 WIB</span>
                        </div>
                        <div class="mt-1 flex items-center gap-space-md">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/20 font-display text-headline-sm font-bold text-primary">JT</div>
                            <div class="flex flex-col">
                                <span class="mark-dummy font-sans text-body-md font-bold text-slate-text-primary">Bpk. Joko Tarub</span>
                                <span class="font-sans text-body-sm text-slate-text-secondary">Koordinator Lapangan</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-space-xs font-sans text-[0.6875rem] text-slate-text-muted">
                            <span>Pos Timbangan Utama MIP</span>
                            <span class="font-semibold text-net-pay-emerald">Aktif Bertugas</span>
                        </div>
                    </div>

                    <div class="relative flex flex-col gap-space-xs rounded-xl bg-sidebar-dark p-space-md text-surface-bright">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-warning-amber"></span>
                                <span class="font-sans text-label-md font-bold text-white">Shift 2 (Malam)</span>
                            </div>
                            <span class="font-mono font-sans text-label-sm text-slate-text-muted">19:00 - 07:00 WIB</span>
                        </div>
                        <div class="mt-1 flex items-center gap-space-md">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-banner-indigo/40 font-display text-headline-sm font-bold text-banner-sky">RD</div>
                            <div class="flex flex-col">
                                <span class="mark-dummy-invert font-sans text-body-md font-bold text-white">Bpk. Ridwan</span>
                                <span class="font-sans text-body-sm text-slate-text-muted">Spv Bongkar Muat &bull; Stockpile Jetty Port</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-space-xs font-sans text-[0.6875rem] text-slate-text-muted">
                            <span>Gate 3 Jembatan Jetty</span>
                            <span class="font-semibold text-banner-sky">Siaga Pergantian (19:00)</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-space-md rounded-xl bg-card-bg p-space-lg shadow-card">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-space-xs">
                            <span class="material-symbols-outlined text-[1.375rem] text-danger-red">notifications_active</span>
                            <h2 class="font-display text-headline-sm text-slate-text-primary">Peringatan Operasional</h2>
                        </div>
                        <span class="h-2 w-2 rounded-full bg-danger-red"></span>
                    </div>

                    <div class="flex items-start gap-space-sm rounded-xl bg-danger-surface/30 p-space-md">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-danger-red/15 text-danger-red">
                            <span class="material-symbols-outlined text-[1.125rem]">commute</span>
                        </div>
                        <div class="flex flex-1 flex-col">
                            <div class="flex items-center justify-between">
                                <span class="mark-dummy font-sans text-label-md font-bold text-danger-red">23 Sep 2026</span>
                                <span class="rounded bg-danger-red px-1.5 py-0.5 font-sans text-[0.625rem] font-bold uppercase text-white">H-4 Hari</span>
                            </div>
                            <span class="mark-dummy mt-0.5 font-sans text-body-md font-semibold text-slate-text-primary">Servis Berkala Truk BE 8639 AAU (Hino Putih)</span>
                            <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">Jadwal perawatan armada mendekati batas cut-off trip. Koordinasikan dengan pool workshop sebelum keberangkatan.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-space-sm rounded-xl bg-warning-surface/30 p-space-md">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-warning-amber/20 text-warning-amber">
                            <span class="material-symbols-outlined text-[1.125rem]">local_gas_station</span>
                        </div>
                        <div class="flex flex-1 flex-col">
                            <div class="flex items-center justify-between">
                                <span class="mark-dummy font-sans text-label-md font-bold text-slate-text-primary">30 Sep 2026</span>
                                <span class="rounded bg-warning-surface px-1.5 py-0.5 font-sans text-[0.625rem] font-bold uppercase text-warning-amber">Akhir Bulan</span>
                            </div>
                            <span class="mark-dummy mt-0.5 font-sans text-body-md font-semibold text-slate-text-primary">Pencocokan BBM Solar Tambang</span>
                            <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">Pencocokan kuota tangki SPBU Tambang dengan lembar voucher BBM pengemudi periode berjalan.</p>
                        </div>
                    </div>

                    <button type="button" class="flex w-full items-center justify-center gap-1 rounded-lg bg-surface-container-low py-2 font-sans text-body-sm font-semibold text-primary transition-colors hover:bg-surface-container" onclick="window.alert('Log reminder compliance belum memiliki desain terpisah.')">
                        <span>Lihat Seluruh Log Reminder Compliance</span>
                        <span class="material-symbols-outlined text-[0.875rem]">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
