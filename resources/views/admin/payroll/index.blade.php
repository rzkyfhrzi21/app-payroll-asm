@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $avatarTones = [
        'bg-banner-indigo/10 text-banner-indigo',
        'bg-primary/10 text-primary',
        'bg-warning-amber/15 text-warning-amber',
        'bg-surface-container-high text-banner-deep',
    ];
    $adaFilterAktif = $filter['cari'] !== '' || $filter['fleet'] !== 'all';
    $sjBelumVerifikasi = (int) ($summary['sj_belum_verifikasi'] ?? $summary['sj_draft_atau_menunggu'] ?? 0);
    $catatanPenolakan = $period['catatan_penolakan'] ?? $summary['catatan_penolakan'] ?? null;
@endphp

<x-app-layout title="Payroll Bulanan">
    <div
        class="flex flex-col gap-6"
        x-data="payrollPage([], @js($summary), @js($period))"
    >
        <x-ui.page-header
            title="Payroll Bulanan"
            breadcrumb="Payroll & Approval"
            subtitle="Kelola perhitungan komisi supir, kasbon, uang jalan, dan persetujuan gaji armada."
        >
            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-net-pay-emerald px-4 py-2 font-sans text-body-md font-semibold text-on-primary shadow-sm transition-all hover:bg-emerald-600 active:scale-[0.98]"
                @click="$dispatch('buka-modal-migrasi-excel', { tab: 'import', periode: '{{ $period['code'] }}' })"
            >
                <span class="material-symbols-outlined text-[1.125rem]">table_view</span>
                Import / Export Excel
            </button>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 font-sans text-body-md font-semibold text-on-primary shadow-sm transition-all hover:bg-banner-indigo active:scale-[0.98]"
                @click="calculate()"
                :disabled="calculating"
                :class="calculating ? 'pointer-events-none opacity-80' : ''"
            >
                <span class="material-symbols-outlined text-[1.125rem]" :class="calculating ? 'animate-spin' : ''">calculate</span>
                <span x-text="calculating ? 'Menghitung...' : 'Hitung Gaji'">Hitung Gaji</span>
            </button>

            <a
                href="{{ route('admin.operational.trips.index', ['periode' => $period['code']]) }}"
                class="flex items-center gap-2 rounded-lg bg-surface-container-lowest px-4 py-2 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low"
            >
                <span class="material-symbols-outlined text-[1.125rem] text-slate-text-secondary">add</span>
                Tambah Data
            </a>
        </x-ui.page-header>

        @if (!empty($notifikasiPeriodeBaru))
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 rounded-xl border border-banner-indigo/30 bg-gradient-to-r from-banner-sky/10 via-banner-indigo/10 to-transparent p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-banner-indigo text-white shadow-sm">
                        <span class="material-symbols-outlined text-[1.25rem]">calendar_add_on</span>
                    </span>
                    <div>
                        <h4 class="font-display text-body-md font-bold text-slate-text-primary">Periode {{ $notifikasiPeriodeBaru['nama_bulan'] }} ({{ $notifikasiPeriodeBaru['kode'] }}) Belum Dimulai</h4>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Buka periode operasional baru untuk memulai pencatatan surat jalan dan penggajian bulan berjalan.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.payroll.period.create') }}">
                    @csrf
                    <input type="hidden" name="tanggal" value="{{ now()->toDateString() }}">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-banner-indigo px-4 py-2 font-sans text-body-sm font-semibold text-white shadow-sm transition-all hover:bg-banner-deep">
                        <span class="material-symbols-outlined text-[1.125rem]">add_circle</span>
                        <span>Mulai Periode {{ $notifikasiPeriodeBaru['kode'] }}</span>
                    </button>
                </form>
            </div>
        @endif

        {{-- Banner operasional --}}
        <div class="relative flex w-full flex-col items-stretch overflow-hidden rounded-2xl bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep text-on-primary shadow-hero md:flex-row">
            <div class="pointer-events-none absolute inset-0 opacity-15" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 18px 18px;"></div>

            <div class="relative z-10 flex flex-1 flex-col justify-center p-space-lg">
                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/20 px-2.5 py-0.5 font-sans text-label-sm font-bold uppercase tracking-wider text-white backdrop-blur-sm">
                        Periode Aktif &bull; {{ $period['code'] }}
                    </span>
                    <span class="font-sans text-body-sm font-medium text-white/85">
                        {{ $period['range'] }} ({{ $period['work_days'] }} Hari Kerja)
                    </span>
                </div>
                <div class="font-display text-headline-md font-bold text-white">
                    Penggajian Supir &amp; Komisi
                </div>
                <p class="mt-1 max-w-xl font-sans text-body-sm leading-relaxed text-white/85">
                    Komisi dihitung dari hasil bersih armada, dipotong BPJS dan cicilan kasbon.
                </p>
            </div>

            <div class="relative z-10 flex w-full shrink-0 items-center justify-center gap-6 border-t border-white/15 bg-slate-950/25 p-5 backdrop-blur-sm md:w-96 md:border-l md:border-t-0">
                <div class="flex flex-col items-center text-center">
                    <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-white/75">Supir Dihitung</span>
                    <span class="font-display text-display-hero-mobile font-bold tabular-nums text-white">{{ $summary['drivers'] }}</span>
                    <span class="font-sans text-label-sm text-white/80">Supir Aktif</span>
                </div>
                <div class="h-14 w-px bg-white/20"></div>
                <div class="flex flex-col items-center text-center">
                    <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-white/75">Total Pengiriman</span>
                    <span class="font-display text-display-hero-mobile font-bold tabular-nums text-white">{{ $summary['rit'] }}</span>
                    <span class="font-sans text-label-sm text-white/80">{{ number_format($summary['tonnage'], 0, ',', '.') }} Ton</span>
                </div>
            </div>
        </div>

        @if (! empty($catatanPenolakan))
            <div class="flex items-start gap-3 rounded-xl border border-danger-red/30 bg-danger-surface px-4 py-3.5 text-danger-red">
                <span class="material-symbols-outlined mt-0.5 text-[1.25rem]">assignment_return</span>
                <div class="flex flex-col gap-0.5">
                    <span class="font-sans text-body-sm font-bold">Periode Dikembalikan Pimpinan untuk Revisi</span>
                    <p class="font-sans text-body-sm text-slate-text-primary">{{ $catatanPenolakan }}</p>
                </div>
            </div>
        @endif

        @if ($sjBelumVerifikasi > 0)
            <div class="flex flex-col justify-between gap-3 rounded-xl border border-warning-amber/40 bg-warning-surface px-4 py-3.5 sm:flex-row sm:items-center">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-[1.25rem] text-warning-amber">pending_actions</span>
                    <div class="flex flex-col gap-0.5">
                        <span class="font-sans text-body-sm font-bold text-slate-text-primary">
                            Masih ada {{ $sjBelumVerifikasi }} surat jalan berstatus Draf / Menunggu Verifikasi Admin
                        </span>
                        <p class="font-sans text-body-sm text-slate-text-secondary">
                            Selesaikan verifikasi seluruh surat jalan pada periode {{ $period['code'] }} sebelum mengajukan payroll ke Pimpinan.
                        </p>
                    </div>
                </div>
                <a
                    href="{{ route('admin.operational.trips.index', ['periode' => $period['code']]) }}"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-warning-amber px-3.5 py-2 font-sans text-body-sm font-bold text-white shadow-sm transition-colors hover:bg-amber-600"
                >
                    <span class="material-symbols-outlined text-[1rem]">fact_check</span>
                    Verifikasi Surat Jalan
                </a>
            </div>
        @endif

        {{-- Ringkasan atas --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-xl shadow-card lg:col-span-5">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-container text-banner-indigo">
                                <span class="material-symbols-outlined text-[1.25rem]">event_note</span>
                            </div>
                            <div>
                                <span class="block font-sans text-label-sm font-semibold uppercase tracking-wider">Siklus Penggajian</span>
                                <span class="font-display text-headline-sm font-bold text-slate-text-primary">Periode Pengantaran &amp; Cut-Off</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 rounded-full bg-warning-surface px-3 py-1 text-warning-amber">
                            <span class="h-2 w-2 animate-ping rounded-full bg-warning-amber"></span>
                            <span class="font-sans text-label-sm font-bold uppercase tracking-wider">{{ $period['status'] }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-space-md">
                        <div class="flex flex-col">
                            <span class="font-sans text-label-sm font-semibold uppercase">Rentang Tanggal</span>
                            <span class="mt-0.5 font-sans text-body-lg font-bold">{{ $period['range'] }}</span>
                        </div>
                        <span class="rounded-md bg-surface-container-highest px-2.5 py-1 font-sans text-label-sm font-semibold text-primary">{{ $period['work_days'] }} Hari Kerja</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <div class="col-span-2 flex flex-col rounded-lg bg-canvas-bg p-3">
                            <span class="font-sans text-label-sm font-semibold uppercase">Volume Pengiriman</span>
                            <span class="mt-1 font-display text-headline-sm font-bold">
                                {{ $summary['rit'] }} Rit
                                <span class="font-sans text-body-sm font-normal text-slate-text-secondary">/ {{ number_format($summary['tonnage'], 0, ',', '.') }} Ton</span>
                            </span>
                            <span class="mt-1 flex items-center gap-1 font-sans text-label-sm font-medium text-net-pay-emerald">
                                <span class="material-symbols-outlined text-[0.8125rem]">database</span> Tersinkron dari data pengantaran
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col overflow-hidden rounded-xl bg-card-bg shadow-card lg:col-span-7">
                <div class="grid grid-cols-1 gap-5 bg-surface-container-lowest p-space-xl sm:grid-cols-3">
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[1rem] text-slate-text-secondary">local_shipping</span>
                            <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Total Angkutan Bersih</span>
                        </div>
                        <div class="font-display text-headline-md font-bold tabular-nums">{{ $rp($summary['haulage_gross']) }}</div>
                        <span class="mt-1 font-sans text-label-sm text-slate-text-muted">Bruto Pengantaran Sebelum Bagi Hasil</span>
                    </div>
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[1rem] text-banner-sky">payments</span>
                            <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Total Uang Jalan Cair</span>
                        </div>
                        <div class="font-display text-headline-md font-bold tabular-nums">{{ $rp($summary['trip_money']) }}</div>
                        <span class="mt-1 font-sans text-label-sm text-slate-text-muted">Solar, Tol, &amp; Uang Saku Supir</span>
                    </div>
                    <div class="flex flex-col">
                        <div class="mb-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[1rem] text-warning-amber">build_circle</span>
                            <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Total Potongan S.Part</span>
                        </div>
                        <div class="font-display text-headline-md font-bold tabular-nums">-{{ $rp($summary['sparepart']) }}</div>
                        <span class="mt-1 font-sans text-label-sm text-slate-text-muted">Ban, Oli, &amp; Bengkel KAFA</span>
                    </div>
                </div>

                <div class="mt-auto flex flex-col justify-between gap-4 bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep p-space-xl text-on-primary shadow-hero md:flex-row md:items-center">
                    <div class="flex flex-col">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Kebutuhan Kas Cair (Cash Requirement)</span>
                            <span class="rounded-full bg-white/20 px-2 py-0.5 font-sans text-label-sm">{{ $summary['drivers'] }} Supir</span>
                        </div>
                        <div class="mt-0.5 font-display text-display-hero font-extrabold tracking-tight tabular-nums">
                            {{ $rp($summary['cash_required']) }}
                        </div>
                        <div class="mt-1 flex items-center gap-2 font-sans text-body-sm text-white/80">
                            <span class="material-symbols-outlined text-[0.9375rem]">info</span>
                            <span>Total gaji bersih setelah dikurangi Kasbon &amp; BPJS TK</span>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg bg-surface-container-lowest px-5 py-2.5 font-display text-headline-sm font-bold text-primary shadow-lg transition-all hover:bg-surface-container active:scale-[0.98]"
                            @click="openApproval('approve')"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">send</span>
                            Ajukan ke Pimpinan
                        </button>
                        @if ($sjBelumVerifikasi > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-0.5 font-sans text-label-sm font-semibold text-warning-amber">
                                <span class="material-symbols-outlined text-[0.875rem]">warning</span>
                                {{ $sjBelumVerifikasi }} SJ belum terverifikasi
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Kartu konteks operasional (di atas tabel) --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="flex flex-col justify-between rounded-xl border border-card-border bg-card-bg p-space-lg shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container-high text-primary">
                            <span class="material-symbols-outlined text-[1.375rem]">local_shipping</span>
                        </div>
                        <div>
                            <span class="font-sans text-label-sm font-semibold uppercase text-slate-text-secondary">Realisasi Muatan</span>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">{{ number_format($summary['tonnage'], 0, ',', '.') }} Ton</h2>
                        </div>
                    </div>
                    <a href="{{ route('admin.operational.trips.index', ['periode' => $period['code']]) }}" class="rounded-lg bg-surface-container-low px-2.5 py-1 font-sans text-label-sm font-semibold text-primary hover:bg-primary/10" title="Lihat Surat Jalan">
                        Surat Jalan
                    </a>
                </div>
                <div class="mt-3 flex items-center justify-between font-sans text-body-sm text-slate-text-secondary">
                    <span>{{ $summary['rit'] }} pengiriman</span>
                    <span>{{ $summary['rit'] > 0 ? number_format($summary['tonnage'] / $summary['rit'], 1, ',', '.') : '0' }} Ton/SJ</span>
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl border border-card-border bg-card-bg p-space-lg shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                            <span class="material-symbols-outlined text-[1.375rem]">account_balance_wallet</span>
                        </div>
                        <div>
                            <span class="font-sans text-label-sm font-semibold uppercase text-slate-text-secondary">Sisa Kasbon</span>
                            <h2 class="font-display text-headline-sm font-bold tabular-nums text-slate-text-primary">{{ $rp($summary['kasbon_outstanding']) }}</h2>
                        </div>
                    </div>
                    <a href="{{ route('admin.operational.loans.index') }}" class="rounded-lg bg-surface-container-low px-2.5 py-1 font-sans text-label-sm font-semibold text-primary hover:bg-primary/10">
                        Kelola
                    </a>
                </div>
                <div class="mt-3 flex items-center justify-between font-sans text-body-sm text-slate-text-secondary">
                    <span>Potong periode ini</span>
                    <span class="font-semibold text-slate-text-primary">{{ $rp($summary['kasbon_deduction']) }}</span>
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl border border-card-border bg-card-bg p-space-lg shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning-surface text-warning-amber">
                            <span class="material-symbols-outlined text-[1.375rem]">pending_actions</span>
                        </div>
                        <div>
                            <span class="font-sans text-label-sm font-semibold uppercase text-slate-text-secondary">Status Gaji</span>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">{{ $period['status'] }}</h2>
                        </div>
                    </div>
                    <span class="rounded-lg bg-surface-container-low px-2.5 py-1 font-sans text-label-sm font-semibold text-slate-text-secondary">{{ $period['cut_off'] }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between font-sans text-body-sm text-slate-text-secondary">
                    <span>{{ $summary['warning_count'] }} baris perlu dicek</span>
                    <span class="rounded-lg bg-surface-container px-2.5 py-1 font-sans text-label-sm font-semibold text-slate-text-primary">
                        Menunggu Pimpinan
                    </span>
                </div>
            </div>
        </div>

        {{-- Filter & tabel --}}
        <div class="flex flex-col gap-3 rounded-xl bg-card-bg p-space-md shadow-card">
            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <div class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-container text-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">group</span>
                    </span>
                    <div class="flex flex-col leading-tight">
                        <span class="font-display text-headline-sm font-bold text-slate-text-primary">Daftar Gaji Supir</span>
                        <span class="font-sans text-label-sm text-slate-text-secondary">{{ $summary['drivers'] }} supir pada periode {{ $period['code'] }}</span>
                    </div>
                </div>

                <form
                    method="GET"
                    action="{{ route('admin.payroll.index') }}"
                    data-filter-server
                    class="flex flex-wrap items-center gap-2"
                >
                    <input type="hidden" name="periode" value="{{ $period['code'] }}">
                    <div class="relative flex min-w-[15rem] flex-1 items-center">
                        <span class="material-symbols-outlined absolute left-3 text-[1.125rem] text-slate-text-secondary">search</span>
                        <input
                            type="text"
                            name="cari"
                            value="{{ $filter['cari'] }}"
                            data-filter-debounce
                            class="h-9 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-sm text-slate-text-primary placeholder:text-slate-text-muted focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Cari nama supir, plat truk..."
                        >
                    </div>

                    <div class="relative flex items-center">
                        <select name="fleet" data-filter-submit class="h-9 appearance-none rounded-lg bg-canvas-bg px-3 pr-8 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="all">Semua Armada</option>
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>

                    @if ($adaFilterAktif)
                        <a
                            href="{{ route('admin.payroll.index', ['periode' => $period['code']]) }}"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-danger-red/25 bg-danger-surface/60 px-3 font-sans text-label-md font-semibold text-danger-red transition-colors hover:bg-danger-surface"
                        >
                            <span class="material-symbols-outlined text-[1rem]">filter_alt_off</span>
                            Reset Filter
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="flex flex-col overflow-hidden rounded-xl bg-card-bg shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-canvas-bg font-sans text-label-sm uppercase tracking-wider">
                            <th class="w-14 px-4 py-3 text-center font-semibold">No</th>
                            <th class="px-4 py-3 font-semibold">Nama Supir &amp; Plat Truk</th>
                            <th class="px-4 py-3 text-right font-semibold">Total Rit / Ton</th>
                            <th class="px-4 py-3 text-right font-semibold">Hasil Bersih Truk</th>
                            <th class="px-4 py-3 text-right font-semibold">Gaji Komisi</th>
                            <th class="px-4 py-3 text-right font-semibold">Potongan BPJS &amp; Kasbon</th>
                            <th class="px-4 py-3 text-right font-semibold">Uang Jalan Info</th>
                            <th class="px-4 py-3 text-right font-semibold">Net Pay</th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-card-border">
                        @forelse ($rows as $index => $row)
                            <tr
                                class="group cursor-pointer transition-colors hover:bg-surface-container-low/60"
                                @click="openDrawer(@js($row))"
                            >
                                <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $rows->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img
                                            src="{{ $row['foto_profil'] }}"
                                            alt="{{ $row['initial'] }}"
                                            loading="lazy"
                                            onerror="window.tanganiGambarRusak(this, @js($row['initial']))"
                                            @click.stop="window.bukaLightbox({ url: @js($row['foto_profil']), judul: @js($row['name']), caption: @js($row['plate'] . ' • ' . $row['truck']) })"
                                            title="Klik untuk memperbesar foto supir"
                                            class="h-9 w-9 shrink-0 cursor-zoom-in rounded-full object-cover ring-1 ring-card-border transition-transform hover:scale-105"
                                        >
                                        <div class="flex min-w-0 flex-col">
                                            <div class="flex items-center gap-1.5">
                                                <span class="truncate font-display text-headline-sm font-bold transition-colors group-hover:text-primary">{{ $row['name'] }}</span>
                                                @if ($row['warning'])
                                                    <span class="h-2 w-2 rounded-full bg-warning-amber" title="{{ $row['warning_note'] }}"></span>
                                                @endif
                                            </div>
                                            <span class="truncate font-sans text-body-sm">{{ $row['plate'] }} &bull; {{ $row['truck'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="font-display text-headline-sm font-bold tabular-nums">{{ $row['rit'] }} Rit</div>
                                    <div class="font-sans text-body-sm tabular-nums">{{ $row['tonnage'] }} Ton</div>
                                </td>

                                <td class="px-4 py-3 text-right font-sans font-semibold tabular-nums">{{ $rp($row['net_result']) }}</td>

                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <span class="font-display text-headline-sm font-bold tabular-nums">{{ $rp($row['gross_pay']) }}</span>
                                        @if ($row['warning'])
                                            <span class="rounded bg-warning-surface px-1.5 py-0.5 font-sans text-label-sm font-bold text-warning-amber" title="{{ $row['warning_note'] }}">&#9888;</span>
                                        @endif
                                    </div>
                                    <div class="font-sans text-label-sm text-slate-text-muted">Gaji komisi sebelum potongan</div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="font-display text-headline-sm font-bold tabular-nums">-{{ $rp($row['deduction']) }}</div>
                                    <div class="font-sans text-label-sm">{{ $row['deduction_note'] }}</div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="font-sans text-body-md font-semibold tabular-nums">{{ $rp($row['trip_money']) }}</div>
                                    <div class="font-sans text-label-sm font-medium text-net-pay-emerald">Lunas SPK</div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <span class="inline-block rounded-md bg-net-pay-surface px-2.5 py-1 font-display text-headline-sm font-bold tabular-nums">{{ $rp($row['net_pay']) }}</span>
                                </td>

                                <td class="px-4 py-3 text-center" @click.stop>
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" class="btn-aksi-tabel hover:text-slate-text-primary" title="Lihat Slip &amp; Rincian Gaji" @click="openDrawer(@js($row))">
                                            <span class="material-symbols-outlined text-[1.125rem]">visibility</span>
                                        </button>
                                        <button type="button" class="btn-aksi-tabel hover:text-primary" title="Edit Komponen Gaji" @click="openDrawer(@js($row))">
                                            <span class="material-symbols-outlined text-[1.125rem]">edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn-aksi-tabel hover:bg-danger-surface hover:text-danger-red"
                                            title="Keluarkan dari Periode"
                                            @click="bukaHapusPayroll(@js($row))"
                                        >
                                            <span class="material-symbols-outlined text-[1.125rem]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="9" label="penggajian supir" :tampilkan-reset="$adaFilterAktif" />
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="bg-surface-container-low font-display text-headline-sm text-slate-text-primary">
                            <td class="px-4 py-3.5 font-bold" colspan="2">Total {{ $summary['drivers'] }} Supir</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums">
                                {{ $summary['sample_rit'] }} Rit
                                <span class="font-sans text-body-sm font-normal text-slate-text-secondary">/ {{ number_format($summary['sample_tonnage'], 0, ',', '.') }} Ton</span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums">{{ $rp($summary['sample_net_result']) }}</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums">{{ $rp($summary['sample_gross_pay']) }}</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums">-{{ $rp($summary['sample_deduction']) }}</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums">{{ $rp($summary['sample_trip_money']) }}</td>
                            <td class="px-4 py-3.5 text-right font-display text-[1.375rem] font-extrabold tabular-nums">{{ $rp($summary['sample_net_pay']) }}</td>
                            <td class="px-4 py-3.5 text-center font-sans text-label-sm uppercase">Audited</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <x-ui.table-pagination :paginator="$rows" :per-page="$perPage" label="supir" />
        </div>

        {{-- Drawer detail slip --}}
        <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-sidebar-dark/60 backdrop-blur-sm" @click="closeDrawer()"></div>

            <aside
                class="absolute right-0 top-0 flex h-full w-full flex-col bg-card-bg shadow-drawer sm:w-[36.25rem]"
                x-show="drawerOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                <template x-if="selected">
                    <div class="flex h-full flex-col overflow-hidden">
                        <div class="flex items-center justify-between bg-canvas-bg p-space-xl">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-gradient-to-br from-banner-sky to-banner-indigo text-on-primary">
                                    <span class="material-symbols-outlined text-[1.375rem]">receipt_long</span>
                                </div>
                                <div>
                                    <span class="font-sans text-label-sm font-semibold uppercase tracking-wider">Detail Slip Gaji Supir</span>
                                    <div class="font-display text-headline-md font-bold" x-text="selected.name"></div>
                                </div>
                            </div>
                            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-text-secondary transition-colors hover:bg-surface-container hover:text-slate-text-primary" @click="closeDrawer()">
                                <span class="material-symbols-outlined text-[1.25rem]">close</span>
                            </button>
                        </div>

                        <div class="flex flex-1 flex-col gap-4 overflow-y-auto p-space-xl">
                            <div class="flex items-center justify-between rounded-xl bg-surface-container-low p-space-md">
                                <div class="flex flex-col">
                                    <span class="font-sans text-label-sm font-semibold uppercase">Armada</span>
                                    <span class="mt-0.5 font-display text-headline-sm font-bold" x-text="selected.plate + ' • ' + selected.truck"></span>
                                </div>
                                <div class="text-right">
                                    <span class="font-sans text-label-sm font-semibold uppercase">Status Payroll</span>
                                    <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-0.5 font-sans text-label-sm font-bold text-warning-amber">
                                        <span>&#9888;</span> Review Anomali
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <span class="font-sans text-label-sm font-bold uppercase tracking-wider">Ringkasan Perhitungan Gaji</span>
                                <div class="flex flex-col gap-1 rounded-xl bg-canvas-bg p-space-md font-sans text-body-md">
                                    <div class="flex items-center justify-between py-1 text-slate-text-secondary">
                                        <span>Total Angkutan Bruto</span>
                                        <span class="font-semibold tabular-nums" x-text="formatRp(selected.haulage_gross ?? 0)"></span>
                                    </div>
                                    <template x-if="(selected.potongan_operasional ?? 0) > 0">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center justify-between py-1 text-slate-text-secondary">
                                                <span>- Potongan Operasional</span>
                                                <span class="tabular-nums text-danger-red" x-text="'-' + formatRp(selected.potongan_operasional)"></span>
                                            </div>
                                            <div class="flex items-center justify-between rounded bg-surface-container-low px-2 py-1 font-semibold text-slate-text-primary">
                                                <span>= Pendapatan Bersih Angkutan</span>
                                                <span class="tabular-nums" x-text="formatRp(selected.omzet ?? Math.max(0, (selected.haulage_gross ?? 0) - (selected.potongan_operasional ?? 0)))"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <div class="flex items-center justify-between py-1 text-slate-text-secondary">
                                        <span>- Total Uang Jalan</span>
                                        <span class="tabular-nums text-danger-red" x-text="'-' + formatRp(selected.trip_money ?? 0)"></span>
                                    </div>
                                    <div class="flex items-center justify-between py-1 text-slate-text-secondary">
                                        <span>- Potongan Sparepart Armada</span>
                                        <span class="tabular-nums text-danger-red" x-text="'-' + formatRp(previewSparepart)"></span>
                                    </div>
                                    <div class="flex items-center justify-between rounded bg-surface-container-low px-2 py-1.5 font-bold text-slate-text-primary">
                                        <span>= Hasil Bersih Truk (Dasar Komisi)</span>
                                        <span class="tabular-nums" x-text="formatRp(previewHasilBersihTruk)"></span>
                                    </div>
                                    <div class="flex items-center justify-between py-1 font-bold text-primary">
                                        <span>Gaji Komisi Supir</span>
                                        <span class="font-display text-headline-sm tabular-nums" x-text="formatRp(previewKomisiSupir)"></span>
                                    </div>
                                    <template x-if="previewTunjangan > 0">
                                        <div class="flex items-center justify-between py-1 text-net-pay-emerald">
                                            <span>+ Tunjangan / Bonus</span>
                                            <span class="font-semibold tabular-nums" x-text="'+' + formatRp(previewTunjangan)"></span>
                                        </div>
                                    </template>
                                    <div class="my-0.5 border-t border-card-border"></div>
                                    <div class="flex items-center justify-between py-1 text-slate-text-secondary">
                                        <span>- Potongan BPJS</span>
                                        <span class="tabular-nums text-danger-red" x-text="'-' + formatRp(previewBpjs)"></span>
                                    </div>
                                    <div class="flex items-center justify-between py-1 text-slate-text-secondary">
                                        <span>- Potongan Kasbon</span>
                                        <span class="tabular-nums text-danger-red" x-text="'-' + formatRp(selected.kasbon ?? 0)"></span>
                                    </div>
                                    <template x-if="(selected.potongan_historis ?? 0) > 0">
                                        <div class="flex items-center justify-between py-1 text-slate-text-secondary">
                                            <span>- Penyesuaian / Potongan Historis</span>
                                            <span class="tabular-nums text-danger-red" x-text="'-' + formatRp(selected.potongan_historis)"></span>
                                        </div>
                                    </template>
                                    <template x-if="(selected.penambahan_historis ?? 0) > 0">
                                        <div class="flex items-center justify-between py-1 text-net-pay-emerald">
                                            <span>+ Penyesuaian / Penambahan Historis</span>
                                            <span class="font-semibold tabular-nums" x-text="'+' + formatRp(selected.penambahan_historis)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                             <div class="flex items-center justify-between rounded-xl bg-net-pay-surface p-space-lg">
                                <div class="flex flex-col">
                                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider">Gaji Bersih Diterima (Net Pay)</span>
                                    <span class="font-sans text-body-sm" x-text="'Ditransfer ke Rekening ' + selected.bank + ' ' + selected.account"></span>
                                </div>
                                <span class="font-display text-financial-total font-extrabold tabular-nums" x-text="formatRp(previewGajiBersih)"></span>
                            </div>

                            {{-- Form Penyesuaian Komponen Gaji Supir --}}
                            <div class="flex flex-col gap-2.5 rounded-xl border border-card-border bg-canvas-bg p-space-md">
                                <div class="flex items-center justify-between">
                                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-primary">Penyesuaian Komponen Gaji</span>
                                    <span class="rounded bg-primary/10 px-2 py-0.5 font-sans text-[11px] font-semibold text-primary">Tersimpan ke DB</span>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div class="flex flex-col gap-1">
                                        <label class="font-sans text-label-sm font-semibold text-slate-text-secondary">Potongan S.Part (Rp)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1000"
                                            x-model.number="editDriverForm.biaya_sparepart"
                                            class="rounded-lg border border-card-border bg-card-bg px-3 py-1.5 font-sans text-body-sm font-semibold tabular-nums text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="font-sans text-label-sm font-semibold text-slate-text-secondary">Tunjangan / Bonus (Rp)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1000"
                                            x-model.number="editDriverForm.total_tunjangan"
                                            class="rounded-lg border border-card-border bg-card-bg px-3 py-1.5 font-sans text-body-sm font-semibold tabular-nums text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="font-sans text-label-sm font-semibold text-slate-text-secondary">Potongan BPJS (Rp)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1000"
                                            x-model.number="editDriverForm.potongan_bpjs"
                                            class="rounded-lg border border-card-border bg-card-bg px-3 py-1.5 font-sans text-body-sm font-semibold tabular-nums text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider">Riwayat Pengantaran Periode Ini</span>
                                    <span class="font-sans text-body-sm">Total: <span x-text="(selected.trips || []).length"></span> Pengantaran Tercatat</span>
                                </div>
                                <div class="overflow-hidden rounded-lg bg-canvas-bg">
                                    <table class="w-full text-left font-sans text-body-sm">
                                        <thead class="bg-surface-container-high font-sans text-label-sm uppercase">
                                            <tr>
                                                <th class="px-3 py-2">Tgl</th>
                                                <th class="px-3 py-2">Rute &amp; DO</th>
                                                <th class="px-3 py-2 text-right">Tonase</th>
                                                <th class="px-3 py-2 text-right">Trip Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-card-border tabular-nums">
                                            <template x-for="(trip, indexRitase) in (selected.trips || [])" :key="indexRitase">
                                                <tr>
                                                    <td class="px-3 py-2" x-text="trip.date"></td>
                                                    <td class="px-3 py-2"><span class="font-medium" x-text="trip.do"></span> (<span x-text="trip.route"></span>)</td>
                                                    <td class="px-3 py-2 text-right" x-text="trip.tonnage + ' Ton'"></td>
                                                    <td class="px-3 py-2 text-right font-medium" x-text="formatRp(trip.rate)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 bg-canvas-bg p-space-lg">
                            <button type="button" class="flex items-center gap-1.5 rounded-lg bg-surface-container-lowest px-4 py-2 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container-high" @click="printSlip()">
                                <span class="material-symbols-outlined text-[1.125rem]">print</span>
                                Cetak Slip PDF
                            </button>
                            <div class="flex items-center gap-2">
                                <button type="button" class="rounded-lg px-4 py-2 font-sans text-body-md font-medium text-slate-text-secondary transition-colors hover:text-slate-text-primary" @click="closeDrawer()">Tutup</button>
                                <button type="button" class="rounded-lg bg-primary px-5 py-2 font-sans text-body-md font-semibold text-on-primary shadow-sm transition-all hover:bg-banner-indigo" @click="saveDriver()">Simpan Perubahan</button>
                            </div>
                        </div>
                    </div>
                </template>
            </aside>
        </div>

        {{-- Modal ACC / Tolak --}}
        <div x-show="approvalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-sidebar-dark/60 backdrop-blur-sm" @click="approvalOpen = false"></div>

            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-xl bg-card-bg shadow-drawer">
                <div class="flex items-center gap-3 border-b border-card-border px-6 py-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg" :class="approvalMode === 'approve' ? 'bg-surface-container-high text-primary' : 'bg-danger-surface text-danger-red'">
                        <span class="material-symbols-outlined text-[1.375rem]" x-text="approvalMode === 'approve' ? 'send' : 'block'"></span>
                    </div>
                    <div>
                        <h2 class="font-display text-headline-md font-bold text-slate-text-primary" x-text="approvalMode === 'approve' ? 'Ajukan Penggajian ke Pimpinan' : 'Tarik Pengajuan'"></h2>
                        <p class="font-sans text-body-sm">Periode {{ $period['code'] }} &bull; {{ $summary['drivers'] }} Supir</p>
                    </div>
                </div>

                <div class="p-6">
                    <template x-if="approvalMode === 'approve'">
                        <div class="flex flex-col gap-3">
                            <p class="font-sans text-body-md text-slate-text-secondary">Pastikan perhitungan komisi, potongan kasbon, BPJS, dan uang jalan sudah sesuai. Setelah diajukan, keputusan ACC sepenuhnya menjadi kewenangan Pimpinan/Direksi.</p>
                            @if ($sjBelumVerifikasi > 0)
                                <div class="flex items-start gap-2 rounded-lg border border-danger-red/30 bg-danger-surface px-3 py-2.5 text-danger-red">
                                    <span class="material-symbols-outlined text-[1.125rem]">error</span>
                                    <p class="font-sans text-body-sm">
                                        Terdapat <strong>{{ $sjBelumVerifikasi }} surat jalan</strong> pada periode ini yang masih berstatus Draf / Menunggu Verifikasi Admin. Seluruh surat jalan wajib berstatus Terverifikasi sebelum diajukan ke Pimpinan.
                                    </p>
                                </div>
                            @endif
                            <div class="flex items-center justify-between rounded-xl bg-surface-container-low px-4 py-3">
                                <span class="font-sans text-label-sm font-semibold uppercase">Cash Requirement</span>
                                <span class="font-display text-headline-md font-bold tabular-nums">{{ $rp($summary['cash_required']) }}</span>
                            </div>
                            <div class="flex items-start gap-2 rounded-lg bg-warning-surface px-3 py-2">
                                <span class="material-symbols-outlined text-[1rem] text-warning-amber">info</span>
                                <p class="font-sans text-body-sm text-slate-text-primary">Admin/HRD tidak dapat memberikan ACC. Status akan berubah menjadi <strong>Menunggu ACC Pimpinan</strong>.</p>
                            </div>
                        </div>
                    </template>

                    <template x-if="approvalMode === 'reject'">
                        <div class="flex flex-col gap-2">
                            <label class="font-sans text-label-sm font-semibold uppercase">Alasan Penolakan / Revisi</label>
                            <textarea x-model="rejectReason" rows="4" class="w-full rounded-lg border-outline-variant bg-canvas-bg font-sans text-body-md focus:ring-2 focus:ring-primary" placeholder="Contoh: Tonase beberapa pengantaran belum sesuai surat jalan."></textarea>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-2 border-t border-card-border bg-surface-container-low px-6 py-3">
                    <button type="button" class="rounded-lg px-4 py-2 font-sans text-body-md font-medium text-slate-text-secondary hover:text-slate-text-primary" @click="approvalOpen = false">Batal</button>
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 font-sans text-body-md font-semibold text-on-primary"
                        :class="approvalMode === 'approve' ? 'bg-primary hover:bg-primary-container' : 'bg-danger-red hover:bg-red-600'"
                        @click="confirmApproval()"
                        x-text="approvalMode === 'approve' ? 'Ya, Ajukan ke Pimpinan' : 'Kirim Penolakan'"
                    ></button>
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi Hapus Baris Payroll --}}
        <x-ui.modal-shell
            name="modalPayrollHapus"
            title="Keluarkan Supir dari Periode"
            subtitle="Tindakan ini memerlukan konfirmasi sebelum mengeluarkan baris perhitungan gaji."
            icon="delete_forever"
            accent="danger"
            badge="Konfirmasi"
            max-width="lg"
            :zIndex="70"
        >
            <div class="flex flex-col gap-space-md px-space-lg pb-space-md">
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Apakah Anda yakin ingin mengeluarkan supir <strong class="text-slate-text-primary" x-text="payrollHapusTarget?.name || '-'"></strong>
                    (<span class="font-semibold text-primary" x-text="payrollHapusTarget?.plate || '-'"></span>) dari daftar perhitungan gaji periode <strong>{{ $period['code'] }}</strong>?
                </p>
                <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-3 font-sans text-body-sm text-rose-800">
                    Catatan: Data pengantaran supir yang sudah terverifikasi tidak ikut terhapus dan dapat dihitung ulang kapan saja melalui tombol <strong>Hitung Ulang</strong>.
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-card-border bg-surface-container-low px-space-lg py-space-md">
                <button type="button" class="rounded-lg bg-card-bg px-4 py-2 font-sans text-body-sm font-semibold text-slate-text-secondary shadow-sm hover:text-slate-text-primary" @click="modalPayrollHapus = false">Batal</button>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-danger-red px-4 py-2 font-sans text-body-sm font-semibold text-white shadow-sm hover:bg-red-700" @click="konfirmasiHapusPayroll()">
                    <span class="material-symbols-outlined text-[1rem]">delete</span>
                    Ya, Keluarkan
                </button>
            </div>
        </x-ui.modal-shell>

        <x-payroll.excel-sync-modal
            :opsi-periode="$periodOptions ?? []"
            :periode-default="$period['code']"
            :periode-ekspor-default="$period['code']"
        />
    </div>
</x-app-layout>
