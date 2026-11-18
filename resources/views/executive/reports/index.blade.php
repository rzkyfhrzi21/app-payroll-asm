<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN LAPORAN FINANSIAL EKSEKUTIF):
     - Tujuan Halaman: Pusat laporan keuangan dan audit laba-rugi penggajian supir untuk Pimpinan.
     - Analogi Dunia Nyata: Seperti buku laporan keuangan bulanan yang diserahkan ke komisaris:
       menampilkan neraca kas pengeluaran gaji supir, total ritase batubara yang berhasil
       dikirim ke dermaga, efisiensi konsumsi uang jalan, dan perbandingan antar bulan.
     - Data dari Controller (Executive/FinancialReportController@index):
       * $daftarPeriode: Daftar seluruh periode untuk dipilih oleh pimpinan.
       * $ringkasan: Total kas keluar, omzet, dan tonase dari periode terpilih.
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
@endphp

<x-executive-layout title="Laporan Finansial">
    @if (! $periodeTerpilih)
        {{-- Tahap 1: Pimpinan memilih periode lebih dulu --}}
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <span class="font-sans text-label-sm text-slate-text-muted">Dashboard</span>
                    <span class="material-symbols-outlined text-[0.875rem] text-slate-text-muted">chevron_right</span>
                    <span class="font-sans text-label-sm font-semibold uppercase tracking-wide text-banner-indigo">Laporan Penggajian &amp; Audit Keuangan</span>
                </div>
                <h1 class="font-display text-headline-lg tracking-tight text-slate-text-primary">Pilih Periode Laporan</h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Klik salah satu periode di bawah untuk membuka rekapitulasi finansial dan rincian gaji seluruh supir.
                </p>
            </div>

            @if ($daftarPeriode === [])
                <div class="rounded-xl bg-card-bg p-10 text-center shadow-card">
                    <span class="material-symbols-outlined text-[2rem] text-slate-text-muted">event_busy</span>
                    <p class="mt-2 font-sans text-body-md text-slate-text-secondary">Belum ada periode penggajian.</p>
                </div>
            @else
                <div class="rounded-xl border border-card-border bg-card-bg shadow-card overflow-hidden">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse min-w-[62rem]">
                            <thead>
                                <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary border-b border-card-border">
                                    <th class="py-3.5 px-4 text-center w-12 whitespace-nowrap">No</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap">Kode Periode</th>
                                    <th class="py-3.5 px-4 whitespace-nowrap">Rentang Tanggal (Siklus Cut-Off)</th>
                                    <th class="py-3.5 px-4 text-center whitespace-nowrap">Jumlah Supir</th>
                                    <th class="py-3.5 px-4 text-right whitespace-nowrap">Total Rit / Ton</th>
                                    <th class="py-3.5 px-4 text-right whitespace-nowrap">Total Net Pay (IDR)</th>
                                    <th class="py-3.5 px-4 text-center whitespace-nowrap">Status Otorisasi</th>
                                    <th class="py-3.5 px-4 text-center w-36 whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-card-border font-financial-table text-slate-text-primary">
                                @foreach ($daftarPeriode as $indeks => $periodeOpsi)
                                    <tr
                                        class="transition-colors hover:bg-surface-container-low/60 cursor-pointer"
                                        onclick="window.location.href='{{ route('executive.reports.index', ['periode' => $periodeOpsi['kode']]) }}'"
                                    >
                                        <td class="py-3.5 px-4 text-center text-xs font-semibold text-slate-text-muted tabular-nums">{{ $indeks + 1 }}</td>
                                        <td class="py-3.5 px-4 whitespace-nowrap font-sans">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold border border-banner-indigo/20 bg-banner-indigo/10 text-banner-indigo">
                                                {{ $periodeOpsi['kode'] }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 whitespace-nowrap font-sans">
                                            <div class="font-semibold text-slate-text-primary">{{ $periodeOpsi['rentang'] }}</div>
                                            <div class="text-[11px] text-slate-text-muted">Siklus Bulanan Terbuku</div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap font-sans">
                                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-surface-container-low font-bold text-slate-text-primary text-xs tabular-nums">
                                                {{ $periodeOpsi['jumlah_supir'] }} Supir
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                            <div class="font-bold text-slate-text-primary tabular-nums">{{ $periodeOpsi['rit'] }} Rit</div>
                                            <div class="text-xs text-slate-text-muted tabular-nums">{{ $periodeOpsi['tonase'] }} Ton</div>
                                        </td>
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                            <span class="font-bold text-net-pay-emerald tabular-nums text-sm">{{ $rp($periodeOpsi['gaji_bersih']) }}</span>
                                            <div class="text-[11px] text-slate-text-muted">Omzet: {{ $rp($periodeOpsi['omzet']) }}</div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap font-sans">
                                            <span @class([
                                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold',
                                                'bg-net-pay-surface text-net-pay-emerald' => in_array($periodeOpsi['status_kode'], ['approved', 'paid'], true),
                                                'bg-warning-surface text-warning-amber' => $periodeOpsi['status_kode'] === 'pending',
                                                'bg-surface-container text-slate-text-secondary' => ! in_array($periodeOpsi['status_kode'], ['approved', 'paid', 'pending'], true),
                                            ])>
                                                <span class="h-1.5 w-1.5 rounded-full {{ in_array($periodeOpsi['status_kode'], ['approved', 'paid'], true) ? 'bg-net-pay-emerald' : 'bg-warning-amber' }}"></span>
                                                {{ $periodeOpsi['status_label'] }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap font-sans" @click.stop>
                                            <a
                                                href="{{ route('executive.reports.index', ['periode' => $periodeOpsi['kode']]) }}"
                                                class="inline-flex items-center gap-1 rounded-lg bg-primary-container px-3 py-1.5 text-xs font-semibold text-on-primary shadow-xs transition-colors hover:bg-primary"
                                            >
                                                <span>Buka Laporan</span>
                                                <span class="material-symbols-outlined text-[1rem]">arrow_forward</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @else
    <div class="flex flex-col gap-5" x-data="executiveReports(@js($rows), @js($report))">
        {{-- Breadcrumb + status --}}
        <div class="flex flex-col justify-between gap-space-sm md:flex-row md:items-center">
            <div class="flex items-center gap-2">
                <a href="{{ route('executive.reports.index') }}" class="flex items-center gap-1 font-sans text-label-sm text-slate-text-muted hover:text-banner-indigo">
                    <span class="material-symbols-outlined text-[0.875rem]">arrow_back</span>
                    Pilih Periode
                </a>
                <span class="material-symbols-outlined text-[0.875rem] text-slate-text-muted">chevron_right</span>
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wide text-banner-indigo">Laporan Penggajian &amp; Audit Keuangan</span>
            </div>
            <div class="flex items-center gap-space-sm self-start md:self-auto">
                <span class="flex items-center gap-1.5 rounded-full bg-net-pay-surface px-3 py-1 font-sans text-label-sm font-semibold tracking-wide text-net-pay-emerald">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-net-pay-emerald"></span>
                    Periode {{ $period['code'] }} &bull; {{ $period['status'] }}
                </span>
                <span class="font-sans text-label-sm text-slate-text-muted">Doc ID: {{ $report['doc_id'] }}</span>
            </div>
        </div>

        {{-- Banner + filter --}}
        <div class="relative overflow-hidden rounded-xl bg-card-bg p-space-xl shadow-card">
            <div class="pointer-events-none absolute -right-16 -top-16 h-80 w-80 rounded-full bg-gradient-to-br from-banner-sky/15 via-banner-indigo/10 to-transparent blur-3xl"></div>

            <div class="relative z-10 flex flex-col justify-between gap-space-lg xl:flex-row xl:items-center">
                <div class="flex max-w-3xl flex-col">
                    <div class="mb-1 flex items-center gap-2">
                        <span class="rounded bg-surface-container-high px-2 py-0.5 font-sans text-[0.6875rem] font-bold uppercase tracking-wider text-primary">Direksi Only</span>
                        <span class="font-sans text-body-sm text-slate-text-muted">&bull; Arsip Laporan Penggajian Supir</span>
                    </div>
                    <h1 class="font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">Laporan Rekapitulasi Penggajian &amp; Finansial Pengantaran</h1>
                    <p class="mt-1 font-sans text-body-md leading-relaxed text-slate-text-secondary">
                        Rekapitulasi komprehensif realisasi biaya operasional, bagi hasil komisi supir, beban armada, serta arus kas per periode cut-off.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-space-sm self-start xl:self-auto">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg bg-surface-container-low px-3.5 py-2.5 font-sans text-body-sm font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container"
                        onclick="window.print()"
                    >
                        <span class="material-symbols-outlined text-[1.125rem] text-danger-red">picture_as_pdf</span>
                        Cetak PDF Resmi Pimpinan
                    </button>
                    <a
                        href="{{ route('admin.reports.periode.export', $period['code']) }}"
                        class="flex items-center gap-2 rounded-lg bg-net-pay-surface px-3.5 py-2.5 font-sans text-body-sm font-semibold text-net-pay-emerald shadow-sm transition-all hover:bg-emerald-100"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">table_view</span>
                        Ekspor File Rekap
                    </a>
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg bg-banner-indigo px-4 py-2.5 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-all hover:bg-banner-deep"
                        @click="emailOpen = true"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">forward_to_inbox</span>
                        Kirim Laporan ke Email Direksi
                    </button>
                </div>
            </div>

            <div class="mt-space-lg flex flex-wrap items-center gap-space-md rounded-lg bg-canvas-bg/80 p-space-md pt-space-lg">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[1.25rem] text-slate-text-secondary">filter_alt</span>
                    <span class="font-sans text-label-md font-bold uppercase tracking-wider text-slate-text-primary">Filter Laporan:</span>
                </div>

                <div class="min-w-[15rem] flex-1">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">calendar_today</span>
                        <select
                            x-on:change="window.location.href = '{{ route('executive.reports.index') }}?periode=' + encodeURIComponent($event.target.value)"
                            class="h-10 w-full appearance-none rounded-lg bg-card-bg pl-9 pr-8 font-sans text-body-sm text-slate-text-primary shadow-sm focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            aria-label="Pilih periode laporan"
                        >
                            @foreach ($periodOptions as $opsiPeriode)
                                <option value="{{ $opsiPeriode['kode'] }}" @selected($opsiPeriode['kode'] === $period['code'])>{{ $opsiPeriode['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">arrow_drop_down</span>
                    </div>
                </div>

                <div class="min-w-[15rem] flex-1">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">local_shipping</span>
                        <select
                            x-model="modelUnit"
                            class="h-10 w-full appearance-none rounded-lg bg-card-bg pl-9 pr-8 font-sans text-body-sm text-slate-text-primary shadow-sm focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            aria-label="Filter model unit armada"
                        >
                            <option value="">Semua Model Unit</option>
                            @foreach ($modelArmada as $model)
                                <option value="{{ $model['name'] }}">{{ $model['name'] }} ({{ $model['fleet'] }} unit)</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">arrow_drop_down</span>
                    </div>
                </div>

                <div class="min-w-[13.75rem] flex-1">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">alt_route</span>
                        <select
                            x-model="ruteTerpilih"
                            class="h-10 w-full appearance-none rounded-lg bg-card-bg pl-9 pr-8 font-sans text-body-sm text-slate-text-primary shadow-sm focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            aria-label="Filter rute pengantaran"
                        >
                            <option value="">Semua Rute</option>
                            @foreach (collect($rows)->pluck('rute')->flatten()->filter()->unique()->sort() as $kodeRute)
                                <option value="{{ $kodeRute }}">{{ $kodeRute }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">arrow_drop_down</span>
                    </div>
                </div>

                <button
                    type="button"
                    x-show="filterLaporanAktif"
                    x-cloak
                    class="flex h-10 items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100"
                    @click="resetFilterLaporan()"
                >
                    <span class="material-symbols-outlined text-[1rem]">restart_alt</span>
                    Reset Filter
                </button>
            </div>
        </div>

        {{-- KPI --}}
        <div class="grid grid-cols-1 gap-space-lg md:grid-cols-2 xl:grid-cols-4">
            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="pointer-events-none absolute -bottom-6 -right-6 h-24 w-24 rounded-full bg-net-pay-surface/80"></div>
                <div class="relative z-10">
                    <div class="mb-space-sm flex items-center justify-between">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Total Realisasi Kas Payroll</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                            <span class="material-symbols-outlined text-[1.25rem]">payments</span>
                        </span>
                    </div>
                    <div class="font-financial-total font-bold tracking-tight text-slate-text-primary">{{ $rp($report['realisasi']) }}</div>
                </div>
                <div class="relative z-10 mt-space-md flex items-center justify-between pt-space-sm font-sans text-body-sm">
                    <span class="flex items-center gap-1 font-semibold text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1rem]">check_circle</span>
                        100% Siap Transfer
                    </span>
                    <span class="font-medium text-slate-text-muted">{{ count($rows) }} Supir</span>
                </div>
            </div>

            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="pointer-events-none absolute -bottom-6 -right-6 h-24 w-24 rounded-full bg-surface-container-low"></div>
                <div class="relative z-10">
                    <div class="mb-space-sm flex items-center justify-between">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Total Omzet Angkutan Bersih</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container-high text-banner-indigo">
                            <span class="material-symbols-outlined text-[1.25rem]">trending_up</span>
                        </span>
                    </div>
                    <div class="font-financial-total font-bold tracking-tight text-slate-text-primary">{{ $rp($report['omzet_bersih']) }}</div>
                </div>
                <div class="relative z-10 mt-space-md flex items-center justify-between pt-space-sm font-sans text-body-sm">
                    <span class="font-semibold text-net-pay-emerald">{{ $summary['omzet_growth'] }} MoM</span>
                    <span class="font-medium text-slate-text-muted">{{ $summary['tonnage'] }} Ton</span>
                </div>
            </div>

            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="pointer-events-none absolute -bottom-6 -right-6 h-24 w-24 rounded-full bg-surface-container-low"></div>
                <div class="relative z-10">
                    <div class="mb-space-sm flex items-center justify-between">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Total Uang Jalan Terdistribusi</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container text-slate-text-secondary">
                            <span class="material-symbols-outlined text-[1.25rem]">route</span>
                        </span>
                    </div>
                    <div class="font-financial-total font-bold tracking-tight text-slate-text-primary">{{ $rp($report['trip_money']) }}</div>
                </div>
                <div class="relative z-10 mt-space-md flex items-center justify-between pt-space-sm font-sans text-body-sm">
                    <span class="font-semibold text-slate-text-primary">{{ $summary['rit'] }} Voucher Pengantaran</span>
                    <span class="font-medium text-slate-text-muted">Realisasi Penuh</span>
                </div>
            </div>

            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="pointer-events-none absolute -bottom-6 -right-6 h-24 w-24 rounded-full bg-danger-surface/60"></div>
                <div class="relative z-10">
                    <div class="mb-space-sm flex items-center justify-between">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Biaya Sparepart &amp; Perawatan</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-danger-surface text-danger-red">
                            <span class="material-symbols-outlined text-[1.25rem]">build_circle</span>
                        </span>
                    </div>
                    <div class="font-financial-total font-bold tracking-tight text-danger-red">{{ $rp($report['sparepart']) }}</div>
                </div>
                <div class="relative z-10 mt-space-md flex items-center justify-between pt-space-sm font-sans text-body-sm">
                    <span class="font-semibold text-danger-red">{{ $summary['pending_acc'] }} Unit Perlu Audit</span>
                    <span class="font-medium text-slate-text-muted">Nota Terverifikasi</span>
                </div>
            </div>
        </div>

        {{-- Rekapitulasi Likuiditas Kas Payroll & Rekening Bank Pencairan --}}
        <div class="rounded-xl bg-card-bg p-space-xl shadow-card">
            <div class="mb-space-md flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-space-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.375rem]">account_balance_wallet</span>
                    </div>
                    <div>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Rekapitulasi Likuiditas Kas Payroll &amp; Rekening Bank Pencairan</h2>
                        <span class="font-sans text-body-sm text-slate-text-muted">Kontrol arus kas gaji supir, beban operasional, dan rekening sumber dana transfer perusahaan</span>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-net-pay-surface px-3 py-1 font-sans text-label-sm font-bold text-net-pay-emerald">
                    <span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span>
                    Saldo Siap Dicairkan
                </span>
            </div>

            <div class="grid grid-cols-1 gap-space-md lg:grid-cols-3">
                {{-- Kolom 1: Kebutuhan Kas Pengeluaran --}}
                <div class="flex flex-col gap-2 rounded-xl bg-canvas-bg p-space-md">
                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">Kebutuhan Kas Periode {{ $period['code'] }}</span>
                    <div class="flex items-center justify-between border-b border-card-border/60 pb-2">
                        <span class="font-sans text-body-sm text-slate-text-secondary">Gaji Bersih (Net Pay Supir)</span>
                        <span class="font-financial-table text-body-md font-bold text-net-pay-emerald">{{ $rp($summary['net_pay']) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-card-border/60 pb-2">
                        <span class="font-sans text-body-sm text-slate-text-secondary">Total Uang Jalan Lapangan</span>
                        <span class="font-financial-table text-body-md font-semibold text-slate-text-primary">{{ $rp($summary['trip_money']) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-card-border/60 pb-2">
                        <span class="font-sans text-body-sm text-slate-text-secondary">Beban Servis &amp; Sparepart</span>
                        <span class="font-financial-table text-body-md font-semibold text-danger-red">{{ $rp($summary['sparepart']) }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between pt-1">
                        <span class="font-sans text-body-sm font-bold text-slate-text-primary">Total Realisasi Kas</span>
                        <span class="font-financial-table text-headline-sm font-extrabold text-primary">{{ $rp($summary['net_pay'] + $summary['trip_money'] + $summary['sparepart']) }}</span>
                    </div>
                </div>

                {{-- Kolom 2: Realisasi Pemotongan & Kasbon --}}
                <div class="flex flex-col gap-2 rounded-xl bg-canvas-bg p-space-md">
                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">Realisasi Potongan Terbuku</span>
                    <div class="flex items-center justify-between border-b border-card-border/60 pb-2">
                        <span class="font-sans text-body-sm text-slate-text-secondary">Cicilan Kasbon Terpotong</span>
                        <span class="font-financial-table text-body-md font-semibold text-net-pay-emerald">{{ $rp($summary['kasbon']) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-card-border/60 pb-2">
                        <span class="font-sans text-body-sm text-slate-text-secondary">Iuran BPJS Ketenagakerjaan</span>
                        <span class="font-financial-table text-body-md font-semibold text-slate-text-primary">{{ $rp($summary['bpjs']) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-card-border/60 pb-2">
                        <span class="font-sans text-body-sm text-slate-text-secondary">Sisa Piutang Kasbon Berjalan</span>
                        <span class="font-financial-table text-body-md font-semibold text-warning-amber">{{ $rp($report['kasbon_outstanding'] ?? 0) }}</span>
                    </div>
                </div>

                {{-- Kolom 3: Rekening Bank Perusahaan --}}
                <div class="flex flex-col justify-between rounded-xl border border-card-border bg-surface-container-low p-space-md">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-primary">
                            <span class="material-symbols-outlined text-[1.25rem]">account_balance</span>
                            <span class="font-sans text-label-sm font-bold uppercase tracking-wider">Rekening Sumber Pencairan</span>
                        </div>
                        <div class="rounded-lg bg-card-bg p-3 shadow-sm space-y-1">
                            <div class="font-sans text-label-sm font-semibold uppercase text-slate-text-muted">Bank Resmi Perusahaan:</div>
                            <div class="font-display text-headline-sm font-bold text-slate-text-primary">{{ $dataPerusahaan->nama_bank ?? 'BANK MANDIRI' }}</div>
                            <div class="font-mono text-body-md font-extrabold text-banner-indigo">{{ $dataPerusahaan->nomor_rekening ?? '140-00-998822-1' }}</div>
                            <div class="font-sans text-body-sm text-slate-text-secondary">a.n. <strong class="text-slate-text-primary">{{ $dataPerusahaan->nama_pemilik ?? ($dataPerusahaan->nama_perusahaan ?? 'PT ANUGERAH SUMBER MAKMUR') }}</strong></div>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-text-muted">
                        <span>Data Rekening Terpusat di Profil Perusahaan</span>
                        <a href="{{ route('executive.company.edit') }}" class="font-semibold text-banner-indigo hover:underline">Kelola Profil &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel rincian per supir --}}
        <div class="flex flex-col overflow-hidden rounded-xl bg-card-bg shadow-card">
            <div class="flex flex-col justify-between gap-space-md border-b border-card-border p-space-lg md:flex-row md:items-center">
                <div class="flex flex-wrap items-center gap-space-md">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-net-pay-emerald"></span>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">
                            Rincian Rekap Payroll Per Supir &bull; Periode {{ $period['code'] }}
                        </h2>
                    </div>
                    <span class="rounded-full bg-surface-container-high px-2.5 py-0.5 font-sans text-label-sm font-semibold text-banner-indigo">
                        {{ count($rows) }} Supir &bull; {{ $period['range'] }}
                    </span>
                </div>

                <div class="relative w-full md:w-72">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1rem] text-slate-text-muted">search</span>
                    <input
                        type="text"
                        x-model.debounce.300ms="search"
                        class="h-9 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-sm text-slate-text-primary shadow-sm placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        placeholder="Cari NIP, nama supir, nopol..."
                    >
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left font-financial-table">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary">
                            <th class="w-14 px-4 py-3 text-center font-semibold">No</th>
                            <th class="px-3 py-3 font-semibold">NIP &amp; Supir</th>
                            <th class="px-3 py-3 font-semibold">Plat Truk &amp; Model</th>
                            <th class="px-2 py-3 text-right font-semibold">Rit</th>
                            <th class="px-2 py-3 text-right font-semibold">Tonase</th>
                            <th class="px-2 py-3 text-right font-semibold">Omzet Truk</th>
                            <th class="px-2 py-3 text-right font-semibold">UJ Cair</th>
                            <th class="px-2 py-3 text-right font-semibold">Sparepart</th>
                            <th class="px-2 py-3 text-right font-semibold">Gaji Komisi</th>
                            <th class="px-2 py-3 text-right font-semibold">BPJS</th>
                            <th class="px-2 py-3 text-right font-semibold">Kasbon</th>
                            <th class="px-3 py-3 text-right font-semibold">Net Transfer</th>
                            <th class="px-3 py-3 font-semibold">Bank &amp; Rekening</th>
                            <th class="px-3 py-3 text-center font-semibold">Status ACC</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-card-border text-slate-text-primary">
                        @forelse ($rows as $row)
                            <tr
                                class="transition-colors hover:bg-surface-container-low/60"
                                x-show="rowVisible(@js($row['name']), @js($row['nip']), @js($row['plate']), @js($row['model_truk'] ?? ''), @js($row['rute'] ?? []))"
                            >
                                <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-text-primary">{{ $row['name'] }}</span>
                                        <span class="font-sans text-label-sm text-slate-text-muted">{{ $row['nip'] }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-text-primary">{{ $row['plate'] }}</span>
                                        <span class="font-sans text-label-sm text-slate-text-muted">{{ $row['model_truk'] ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-2 py-3 text-right font-medium tabular-nums">{{ $row['rit'] }} Rit</td>
                                <td class="px-2 py-3 text-right font-medium tabular-nums">{{ $row['tonnage'] }} T</td>
                                <td class="px-2 py-3 text-right font-medium tabular-nums text-slate-text-secondary">{{ $rp($row['omzet']) }}</td>
                                <td class="px-2 py-3 text-right font-medium tabular-nums text-slate-text-secondary">{{ $rp($row['trip_money']) }}</td>
                                <td class="px-2 py-3 text-right font-medium tabular-nums text-danger-red">-{{ $rp($row['sparepart']) }}</td>
                                <td class="px-2 py-3 text-right font-semibold tabular-nums">{{ $rp($row['gross']) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-slate-text-muted">-{{ $rp($row['bpjs']) }}</td>
                                <td class="px-2 py-3 text-right font-medium tabular-nums text-warning-amber">-{{ $rp($row['kasbon']) }}</td>
                                <td class="px-3 py-3 text-right">
                                    <span class="rounded bg-net-pay-surface px-2 py-0.5 font-bold tabular-nums text-net-pay-emerald">{{ $rp($row['net']) }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-text-primary">{{ $row['bank'] }} &bull; {{ $row['account'] }}</span>
                                        <span class="font-sans text-label-sm text-slate-text-muted">a.n {{ $row['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($row['acc'] === 'Pending')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-2 py-0.5 font-sans text-[0.625rem] font-bold uppercase tracking-wider text-warning-amber">
                                            <span class="material-symbols-outlined text-[0.75rem]">pending</span> Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-2 py-0.5 font-sans text-[0.625rem] font-bold uppercase tracking-wider text-net-pay-emerald">
                                            <span class="material-symbols-outlined text-[0.75rem]">verified</span> {{ $row['acc'] }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-4 py-10 text-center font-sans text-body-sm text-slate-text-muted">
                                    Belum ada baris penggajian pada periode {{ $period['code'] }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if (count($rows) > 0)
                        <tfoot>
                            <tr class="bg-surface-container font-financial-table font-bold text-slate-text-primary">
                                <td class="px-3 py-3" colspan="2">TOTAL {{ count($rows) }} SUPIR</td>
                                <td class="px-3 py-3 font-sans text-label-sm text-slate-text-secondary">Periode {{ $period['code'] }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ collect($rows)->sum('rit') }} Rit</td>
                                <td class="px-2 py-3 text-right text-slate-text-secondary">—</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ $rp(collect($rows)->sum('omzet')) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ $rp(collect($rows)->sum('trip_money')) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-danger-red">-{{ $rp(collect($rows)->sum('sparepart')) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ $rp(collect($rows)->sum('gross')) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-slate-text-muted">-{{ $rp(collect($rows)->sum('bpjs')) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-warning-amber">-{{ $rp(collect($rows)->sum('kasbon')) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-net-pay-emerald">{{ $rp(collect($rows)->sum('net')) }}</td>
                                <td class="px-3 py-3 text-center font-sans text-label-sm text-slate-text-secondary" colspan="2">Total Transfer Bank</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="flex flex-col items-center justify-between gap-space-sm border-t border-card-border bg-surface-container-low p-space-md font-sans text-body-sm text-slate-text-muted sm:flex-row">
                <span>
                    Menampilkan <strong class="text-slate-text-primary" x-text="jumlahTerlihat"></strong>
                    dari <strong class="text-slate-text-primary">{{ count($rows) }}</strong> supir pada periode
                    {{ $period['code'] }} ({{ $period['range'] }})
                </span>
                <a
                    href="{{ route('executive.payroll.index', ['periode' => $period['code']]) }}"
                    class="flex items-center gap-1 font-semibold text-banner-indigo hover:underline"
                >
                    Buka Otorisasi Payroll
                    <span class="material-symbols-outlined text-[1rem]">arrow_forward</span>
                </a>
            </div>
        </div>


        {{-- Tanda tangan --}}
        <div class="rounded-xl bg-card-bg p-space-xl shadow-card">
            <div class="flex flex-col justify-between gap-space-lg lg:flex-row lg:items-center">
                <div class="flex max-w-xl items-start gap-space-md">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-net-pay-surface text-net-pay-emerald shadow-sm">
                        <span class="material-symbols-outlined text-[1.75rem]">verified_user</span>
                    </div>
                    <div>
                        <span class="mb-1 inline-block rounded-full bg-net-pay-surface px-2.5 py-0.5 font-sans text-label-sm font-bold uppercase tracking-wider text-net-pay-emerald">
                            STATUS DOKUMEN: DISAHKAN &amp; DIAUDIT
                        </span>
                        <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Audit Otentikasi Penggajian &amp; Pimpinan</h3>
                        <p class="mt-1 font-sans text-body-sm text-slate-text-secondary">
                            Laporan ini dibuat secara otomatis melalui sistem informasi penggajian supir terintegrasi. Telah divalidasi oleh tim audit HRD
                            dan disetujui langsung oleh Direksi untuk eksekusi pencairan gaji.
                        </p>
                    </div>
                </div>

                <div class="grid w-full shrink-0 grid-cols-1 gap-space-md sm:grid-cols-2 lg:w-auto">
                    <div class="flex min-w-[13.75rem] flex-col justify-between rounded-lg bg-canvas-bg p-space-md">
                        <div>
                            <span class="mb-1 block font-sans text-label-sm uppercase tracking-wider">Dibuat Oleh:</span>
                            <div class="font-sans text-body-md font-bold text-slate-text-primary">Siti Rahmawati, S.E.</div>
                            <span class="font-sans text-label-sm text-slate-text-secondary">HRD &amp; Payroll Logistik Batubara</span>
                        </div>
                        <div class="mt-space-md flex items-center gap-1.5 pt-space-xs font-sans text-label-sm text-net-pay-emerald">
                            <span class="material-symbols-outlined text-[1rem]">draw</span>
                            Tanda Tangan Digital Terverifikasi
                        </div>
                    </div>

                    <div class="flex min-w-[13.75rem] flex-col justify-between rounded-lg bg-surface-container-low p-space-md">
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <span class="block font-sans text-label-sm font-bold uppercase tracking-wider text-banner-indigo">Mengetahui (Pimpinan):</span>
                                <span class="material-symbols-outlined text-[1rem] text-banner-indigo">lock</span>
                            </div>
                            <div class="font-sans text-body-md font-bold text-slate-text-primary">{{ auth()->user()->name ?? 'Ir. Hendra Wijaya, M.M.' }}</div>
                            <span class="font-sans text-label-sm text-slate-text-secondary">Direktur Operasional &amp; Keuangan</span>
                        </div>
                        <div class="mt-space-md flex items-center gap-1.5 pt-space-xs font-sans text-label-sm font-semibold text-banner-indigo">
                            <span class="material-symbols-outlined text-[1rem]">task_alt</span>
                            ACC Direksi: 19 Jan 2026, 09:15 WIB
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal email --}}
        <div x-show="emailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-sidebar-dark/70 backdrop-blur-sm" @click="emailOpen = false"></div>

            <div class="relative z-10 flex w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-card-bg shadow-drawer">
                <div class="flex items-center gap-2.5 bg-surface-container-low p-space-lg">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-banner-indigo text-on-primary">
                        <span class="material-symbols-outlined text-[1.375rem]">forward_to_inbox</span>
                    </div>
                    <div>
                        <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Kirim Laporan ke Email Direksi</h3>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Periode {{ $period['code'] }} &bull; {{ $report['doc_id'] }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-space-md p-space-lg">
                    <div class="flex flex-col gap-1">
                        <label class="font-sans text-label-sm uppercase tracking-wider">Email Penerima</label>
                        <input type="text" x-model="emailTo" class="h-10 w-full rounded-lg bg-canvas-bg px-3 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active" placeholder="direksi@kafa-logistics.id">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-sans text-label-sm uppercase tracking-wider">Catatan</label>
                        <textarea x-model="emailNote" rows="3" class="w-full rounded-lg bg-canvas-bg p-3 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active" placeholder="Mohon ditinjau sebelum eksekusi transfer bank."></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-space-sm bg-surface-container-low p-space-lg">
                    <button type="button" class="rounded-lg bg-card-bg px-4 py-2 font-sans text-body-sm font-semibold text-slate-text-secondary shadow-sm" @click="emailOpen = false">Batal</button>
                    <button type="button" class="flex items-center gap-1.5 rounded-lg bg-banner-indigo px-4 py-2 font-sans text-body-sm font-bold text-on-primary shadow-md" @click="sendEmail()">
                        <span class="material-symbols-outlined text-[1.125rem]">send</span>
                        Kirim Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-executive-layout>
