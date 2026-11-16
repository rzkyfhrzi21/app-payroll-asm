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

<x-executive-layout title="Kalender Operasional">
    <div class="flex flex-col gap-5" x-data="executiveCalendar(@js($days))">
        <div class="flex flex-col justify-between gap-space-md lg:flex-row lg:items-center">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 font-sans text-label-sm text-slate-text-secondary">
                    <span>Dashboard</span>
                    <span class="material-symbols-outlined text-[0.8125rem] text-slate-text-muted">chevron_right</span>
                    <span class="font-semibold text-slate-text-primary">Kalender Operasional</span>
                </div>
                <h1 class="font-display text-headline-lg tracking-tight text-slate-text-primary">Kalender Siklus Payroll &amp; Pengantaran</h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Tampilan monitoring pimpinan. Perubahan data operasional hanya dapat dilakukan oleh Admin/HRD.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center rounded-lg bg-card-bg p-1 shadow-sm">
                    <button type="button" class="rounded-lg p-1.5 text-slate-text-secondary transition-colors hover:bg-surface-container" onclick="window.alert('Navigasi bulan hanya tersedia untuk periode aktif.')">
                        <span class="material-symbols-outlined text-[1.125rem]">chevron_left</span>
                    </button>
                    <div class="flex items-center gap-1.5 px-3 py-1 font-display text-headline-sm text-slate-text-primary">
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">calendar_month</span>
                        <span class="text-slate-text-primary">Sep 2026</span>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-slate-text-secondary transition-colors hover:bg-surface-container" onclick="window.alert('Navigasi bulan hanya tersedia untuk periode aktif.')">
                        <span class="material-symbols-outlined text-[1.125rem]">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Milestone --}}
        <div class="grid grid-cols-1 gap-space-md md:grid-cols-2 xl:grid-cols-4">
            <div class="flex flex-col justify-between gap-space-xs rounded-xl bg-card-bg p-space-md shadow-card">
                <div class="flex items-center justify-between">
                    <span class="mark-label font-sans text-label-sm uppercase">Milestone 01</span>
                    <span class="material-symbols-outlined text-[1.125rem] text-net-pay-emerald">check_circle</span>
                </div>
                <div class="mark-dummy font-display text-headline-sm font-bold text-slate-text-primary">21 Agu - 18 Sep 2026</div>
                <p class="font-sans text-body-sm text-slate-text-secondary">Periode Pengantaran Batubara</p>
                <span class="mark-dummy w-fit rounded bg-net-pay-surface px-2 py-0.5 font-sans text-label-sm font-semibold text-net-pay-emerald">Selesai (210 Rit)</span>
            </div>

            <div class="flex flex-col justify-between gap-space-xs rounded-xl bg-card-bg p-space-md shadow-card ring-2 ring-warning-amber/60">
                <div class="flex items-center justify-between">
                    <span class="mark-label font-sans text-label-sm font-bold uppercase tracking-wider">Milestone 02 &bull; Berjalan</span>
                    <span class="material-symbols-outlined animate-pulse text-[1.125rem] text-warning-amber">timer</span>
                </div>
                <div class="mark-dummy font-display text-headline-sm font-bold text-slate-text-primary">19 Sep 2026 (23:59 WIB)</div>
                <p class="font-sans text-body-sm text-slate-text-secondary">Cut-Off Input Surat Jalan &amp; Bengkel</p>
                <span class="mark-dummy w-fit rounded bg-warning-surface px-2 py-0.5 font-sans text-label-sm font-bold text-warning-amber">Berjalan Hari Ini (H-6 Jam)</span>
            </div>

            <div class="flex flex-col justify-between gap-space-xs rounded-xl bg-card-bg p-space-md shadow-card">
                <div class="flex items-center justify-between">
                    <span class="mark-label font-sans text-label-sm uppercase">Milestone 03</span>
                    <span class="material-symbols-outlined text-[1.125rem] text-slate-text-muted">pending</span>
                </div>
                <div class="mark-dummy font-display text-headline-sm font-bold text-slate-text-primary">20 - 22 Sep 2026</div>
                <p class="font-sans text-body-sm text-slate-text-secondary">Audit HRD &amp; Review Otorisasi Direksi</p>
                <span class="mark-dummy w-fit rounded bg-surface-container px-2 py-0.5 font-sans text-label-sm text-slate-text-secondary">Menunggu Antrean</span>
            </div>

            <div class="flex flex-col justify-between gap-space-xs rounded-xl bg-card-bg p-space-md shadow-card">
                <div class="flex items-center justify-between">
                    <span class="mark-label font-sans text-label-sm uppercase">Milestone 04</span>
                    <span class="material-symbols-outlined text-[1.125rem] text-slate-text-muted">account_balance</span>
                </div>
                <div class="mark-dummy font-display text-headline-sm font-bold text-slate-text-primary">25 Sep 2026</div>
                <p class="font-sans text-body-sm text-slate-text-secondary">Eksekusi Payroll / Transfer Bank</p>
                <span class="mark-dummy w-fit rounded bg-surface-container px-2 py-0.5 font-sans text-label-sm text-slate-text-secondary">Dijadwalkan</span>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-space-xl xl:grid-cols-12">
            <div class="flex flex-col gap-space-lg xl:col-span-8">
                <div class="flex flex-wrap items-center justify-between gap-space-sm rounded-xl bg-card-bg p-space-md shadow-card">
                    <div class="flex flex-wrap items-center gap-space-md">
                        <span class="mark-label font-sans text-label-sm font-semibold uppercase">Legenda:</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-primary"></span> Pengantaran</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-warning-amber"></span> Cut-Off</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-banner-indigo"></span> Audit</span>
                        <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary"><span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span> Pay Day</span>
                    </div>
                    <span class="font-sans text-label-sm text-slate-text-muted">Klik tanggal untuk melihat agenda</span>
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
                                @click="selectedIndex = {{ $index }}"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="mark-dummy font-sans text-label-md font-bold {{ $day['muted'] ? 'text-slate-text-muted' : 'text-slate-text-primary' }}">{{ $day['day'] }}</span>
                                    @if (! empty($day['events']))
                                        <span class="mark-dummy font-sans text-[0.625rem] text-slate-text-muted">{{ count($day['events']) }} agenda</span>
                                    @endif
                                </div>

                                <div class="flex flex-col gap-1">
                                    @forelse ($day['events'] as $event)
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
            </div>

            <div class="flex flex-col gap-space-lg xl:col-span-4">
                <div class="flex flex-col gap-space-md rounded-xl bg-card-bg p-space-lg shadow-card">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[1.375rem] text-warning-amber">alarm</span>
                            <h2 class="font-display text-headline-sm text-slate-text-primary">
                                Agenda (<span class="mark-dummy" x-text="activeCell.day"></span> Sep)
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

                    <div class="flex flex-col gap-2">
                        <span class="mark-label font-sans text-label-sm font-bold uppercase tracking-wider">Agenda Tanggal Terpilih</span>
                        <template x-if="activeCell.events.length === 0">
                            <div class="rounded-lg bg-surface-container-low p-3 font-sans text-body-sm text-slate-text-secondary">Tidak ada agenda operasional pada tanggal ini.</div>
                        </template>
                        <template x-for="(event, index) in activeCell.events" :key="index">
                            <div class="flex items-center gap-2 rounded-lg bg-surface-container-low p-2.5">
                                <span class="material-symbols-outlined text-[1.125rem] text-primary">event</span>
                                <span class="mark-dummy flex-1 font-sans text-body-sm font-medium" x-text="event.label"></span>
                                <span class="mark-dummy rounded bg-card-bg px-2 py-0.5 font-sans text-label-sm uppercase text-slate-text-secondary" x-text="event.type"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex flex-col gap-space-md rounded-xl bg-card-bg p-space-lg shadow-card">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
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
                            <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">Masa berlaku uji kelayakan dishub habis. Wajib booking slot pengujian DISHUB  sebelum tanggal cut-off trip.</p>
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
                </div>
            </div>
        </div>
    </div>
</x-executive-layout>
