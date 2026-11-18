<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OPERASIONAL PENGANTARAN / SURAT JALAN):
     - Tujuan Halaman: Pusat penerbitan, pemantauan, dan verifikasi Surat Jalan (DO) batubara.
     - Analogi Dunia Nyata: Seperti pos satpam & loket timbangan tambang batubara:
       Admin membuat tiket jalan untuk supir dan truk, supir berangkat bawa batubara ke pelabuhan,
       lalu setelah dibongkar dan ditimbang, lembar surat jalan difoto dan diverifikasi oleh Admin.
     - Standar Alur Status Surat Jalan 5 Tahap (Sesuai AGENTS.md Bab 3.8):
       1. Draft (`draft`): Surat jalan baru diketik Admin, belum bisa dilihat supir.
       2. Terbit (`terbit`): Surat jalan diterbitkan, supir bisa melihat tugas di HP.
       3. Sedang Dikirim (`sedang_dikirim`): Supir jalan membawa batubara ke tujuan.
       4. Menunggu Verifikasi (`menunggu_verifikasi`): Supir tiba, upload foto bukti dan nama penerima.
       5. Terverifikasi (`terverifikasi`): Admin mengecek foto dan stempel; data SAH untuk dihitung gaji!
     - Data dari Controller (Admin/Operational/DeliveryTripController@index):
       * $period: Info periode aktif pengantaran.
       * $summary: Total ritase, total tonase (Ton), uang jalan terpakai, dan antrean verifikasi.
       * $trips: Paginator daftar surat jalan dari tabel `ritase` join supir, truk, dan rute.
       * $drivers, $routes, $trucks: Data master untuk dropdown pilihan tambah/edit surat jalan.
     - Tabel Database Terkait:
       * `ritase` (no_surat_jalan, tanggal_ritase, tonase_kg, tarif_per_ton, uang_jalan, foto_surat_jalan, nama_penerima, status).
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');

    $adaFilterAktif = $filter['cari'] !== ''
        || $filter['supir'] !== 'all'
        || $filter['rute'] !== 'all'
        || $filter['jenis'] !== 'all'
        || $filter['status'] !== 'all'
        || ($filter['tanggal_dari'] ?? null) !== null
        || ($filter['tanggal_sampai'] ?? null) !== null;

    $barisDetailRitase = "[
        { label: 'No. Surat Jalan / DO', nilai: ritaseDipilih.sj },
        { label: 'Tanggal Pengantaran', nilai: ritaseDipilih.date },
        { label: 'Nama Supir', nilai: ritaseDipilih.driver + ' (' + (ritaseDipilih.nip || '-') + ')' },
        { label: 'Unit Armada', nilai: (ritaseDipilih.plate || '-') + ' — ' + (ritaseDipilih.truck || '-') },
        { label: 'Koridor Rute', nilai: ritaseDipilih.route },
        { label: 'Material Muatan', nilai: ritaseDipilih.material },
        { label: 'Jumlah Pengantaran (Trip)', nilai: (ritaseDipilih.rit || 1) + ' Trip' },
        { label: 'Tonase Timbangan', nilai: (ritaseDipilih.tonnage || '0,00') + ' Ton' },
        { label: 'Tarif per Ton', nilai: 'Rp ' + Number(ritaseDipilih.rate || 0).toLocaleString('id-ID') },
        { label: 'Total Angkutan Kotor', nilai: 'Rp ' + Number(ritaseDipilih.total || 0).toLocaleString('id-ID') },
        { label: 'Uang Jalan Cair', nilai: 'Rp ' + Number(ritaseDipilih.trip_money || 0).toLocaleString('id-ID') },
        { label: 'Status Tiket', nilai: ritaseDipilih.status_label || ritaseDipilih.status },
        { label: 'Nama Pihak Penerima', nilai: ritaseDipilih.nama_penerima || '—' },
        { label: 'Catatan Pengantaran Supir', nilai: ritaseDipilih.catatan_pengantaran || '—' },
    ]";
@endphp

<x-app-layout title="Operasional Pengantaran">
    <div class="flex flex-col gap-6" x-data="tripsPage([], @js($summary), @js($period))">
        {{-- Banner visual + metrik ringkas --}}
        <div class="relative w-full overflow-hidden rounded-2xl bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep p-space-xl text-on-primary shadow-hero">
            <div class="pointer-events-none absolute -right-16 -top-20 h-80 w-80 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>

            <div class="relative z-10 flex flex-col justify-between gap-space-lg lg:flex-row lg:items-center">
                <div class="flex max-w-2xl flex-col gap-1">
                    <div class="mb-1 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 font-sans text-label-sm font-semibold uppercase tracking-wider backdrop-blur-md">
                            <span class="material-symbols-outlined text-[0.875rem]">local_shipping</span>
                            Logistik Tambang Batubara
                        </span>
                        <span class="font-sans text-label-sm text-white/70">&bull;</span>
                        <span class="font-sans text-label-sm tracking-wide">
                            @if ($summary['rute_utama'])
                                Rute utama: {{ $summary['rute_utama'] }}
                            @else
                                Belum ada rute tercatat
                            @endif
                        </span>
                    </div>
                    <h1 class="font-display text-headline-lg font-bold tracking-tight text-white">Operasional Pengantaran Batubara</h1>
                    <p class="font-sans text-body-md leading-relaxed text-white/85">
                        Buat surat jalan pengantaran harian: tonase timbangan, tarif rute, dan uang jalan supir.
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="font-sans text-[0.625rem] font-bold uppercase tracking-wider text-white/60">Periode Aktif &mdash; {{ $period['code'] }}</span>
                    <div class="flex flex-wrap items-center gap-space-sm">
                        <div class="flex min-w-[7.5rem] flex-col rounded-xl bg-white/10 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Pengantaran</span>
                            <span class="font-display text-headline-md font-bold">{{ $summary['rit'] }} <span class="font-sans text-label-md font-normal text-white/80">Trip</span></span>
                        </div>
                        <div class="flex min-w-[8.5rem] flex-col rounded-xl bg-white/10 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Tonase</span>
                            <span class="font-display text-headline-md font-bold">{{ $summary['tonnage'] }} <span class="font-sans text-label-md font-normal text-white/80">Ton</span></span>
                        </div>
                        <div class="flex min-w-[9.5rem] flex-col rounded-xl bg-white/15 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Uang Jalan</span>
                            <span class="font-display text-headline-md font-bold tabular-nums">{{ $rp($summary['trip_money']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kartu akumulasi seluruh periode --}}
        <div class="flex flex-col gap-3 rounded-2xl border border-card-border bg-card-bg p-space-md shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-card-border/70 pb-2.5">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-banner-indigo/10 text-banner-indigo">
                        <span class="material-symbols-outlined text-[1.125rem]">database</span>
                    </span>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-display text-label-md font-bold uppercase tracking-wide text-slate-text-primary">Akumulasi Keseluruhan</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-0.5 font-sans text-label-sm font-semibold text-slate-text-secondary">
                            <span class="material-symbols-outlined text-[0.875rem] text-banner-indigo">calendar_month</span>
                            {{ $summaryKeseluruhan['periode'] }} Periode
                        </span>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 font-sans text-label-sm text-slate-text-muted">
                    <span class="material-symbols-outlined text-[0.95rem]">inventory_2</span>
                    Seluruh data pengantaran di database
                </span>
            </div>

            <div class="grid grid-cols-1 gap-space-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-3 rounded-xl border border-card-border/60 bg-surface-container-low px-3.5 py-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-banner-indigo/10 text-banner-indigo">
                        <span class="material-symbols-outlined text-[1.25rem]">local_shipping</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Total Pengantaran</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-slate-text-primary">{{ number_format($summaryKeseluruhan['rit'], 0, ',', '.') }} <span class="font-sans text-label-sm font-normal text-slate-text-muted">Trip</span></span>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-card-border/60 bg-surface-container-low px-3.5 py-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                        <span class="material-symbols-outlined text-[1.25rem]">scale</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Total Tonase</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-slate-text-primary">{{ $summaryKeseluruhan['tonnage'] }} <span class="font-sans text-label-sm font-normal text-slate-text-muted">Ton</span></span>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-card-border/60 bg-surface-container-low px-3.5 py-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <span class="material-symbols-outlined text-[1.25rem]">payments</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Total Angkutan</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-primary">{{ $rp($summaryKeseluruhan['haulage']) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-card-border/60 bg-surface-container-low px-3.5 py-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.25rem]">account_balance_wallet</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Uang Jalan Cair</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-net-pay-emerald">{{ $rp($summaryKeseluruhan['trip_money']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kartu ringkasan terpilih (mengikuti filter aktif) --}}
        <div class="flex flex-col gap-3 rounded-2xl border border-card-border bg-card-bg p-space-md shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-card-border/70 pb-2.5">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.125rem]">filter_alt</span>
                    </span>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-display text-label-md font-bold uppercase tracking-wide text-slate-text-primary">Ringkasan Terpilih</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-0.5 font-sans text-label-sm font-semibold text-slate-text-secondary">
                            <span class="material-symbols-outlined text-[0.875rem] text-net-pay-emerald">tune</span>
                            Sesuai filter aktif
                        </span>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 font-sans text-label-sm text-slate-text-muted">
                    <span class="material-symbols-outlined text-[0.95rem]">tune</span>
                    Hanya pengantaran yang lolos filter
                </span>
            </div>

            <div class="grid grid-cols-1 gap-space-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-3 rounded-xl border border-card-border/60 bg-surface-container-low px-3.5 py-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-banner-indigo/10 text-banner-indigo">
                        <span class="material-symbols-outlined text-[1.25rem]">local_shipping</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Total Pengantaran</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-slate-text-primary">{{ number_format($summary['rit'], 0, ',', '.') }} <span class="font-sans text-label-sm font-normal text-slate-text-muted">Trip</span></span>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-card-border/60 bg-surface-container-low px-3.5 py-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                        <span class="material-symbols-outlined text-[1.25rem]">scale</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Total Tonase</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-slate-text-primary">{{ $summary['tonnage'] }} <span class="font-sans text-label-sm font-normal text-slate-text-muted">Ton</span></span>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-card-border/60 bg-surface-container-low px-3.5 py-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <span class="material-symbols-outlined text-[1.25rem]">payments</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Total Angkutan</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-primary">{{ $rp($summary['haulage']) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-card-border/60 bg-surface-container-low px-3.5 py-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.25rem]">account_balance_wallet</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Uang Jalan Cair</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-net-pay-emerald">{{ $rp($summary['trip_money']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter & aksi (AJAX: URL tidak berubah, tanpa query string) --}}
        <div
            class="flex flex-col items-stretch justify-between gap-space-md rounded-xl bg-card-bg p-space-md shadow-card lg:flex-row lg:items-end"
            data-wadah-tabel="ritase"
        >
            <div class="flex flex-1 flex-col gap-space-md sm:flex-row sm:flex-wrap">
                <div class="flex min-w-[13.75rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Supir</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">badge</span>
                        <select name="supir" class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            <option value="all">Semua Supir ({{ $summary['supplier'] }})</option>
                            @foreach (collect($drivers)->sortBy('nama') as $supir)
                                <option value="{{ $supir['nip'] }}" @selected($filter['supir'] === $supir['nip'])>{{ $supir['nama'] }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                <div class="flex min-w-[12rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Rute</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">alt_route</span>
                        <select name="rute" class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            <option value="all">Semua Rute</option>
                            @foreach (collect($routes)->pluck('code')->filter()->sort() as $kodeRute)
                                <option value="{{ $kodeRute }}" @selected($filter['rute'] === $kodeRute)>{{ $kodeRute }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                <div class="flex min-w-[12rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Jenis Truk</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">category</span>
                        <select name="jenis" class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            <option value="all">Semua Jenis</option>
                            @foreach ($jenisTrukOptions as $namaJenis)
                                <option value="{{ $namaJenis }}" @selected($filter['jenis'] === $namaJenis)>{{ $namaJenis }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                <div class="flex min-w-[14.375rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Periode Operasional</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-banner-indigo">date_range</span>
                        <select name="periode"
                            data-no-tabel-ajax
                            onchange="if (this.value) window.location.assign('{{ route('admin.operational.trips.index') }}?periode=' + encodeURIComponent(this.value))"
                            class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            @foreach ($periodOptions as $opsi)
                                <option value="{{ $opsi['kode'] }}"
                                    @selected($opsi['kode'] === $period['code'])>{{ $opsi['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                <div class="flex min-w-[12.5rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Status</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">fact_check</span>
                        <select name="status" class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            <option value="all">Semua Status</option>
                            @foreach ($statusOptions as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filter['status'] === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                <div class="flex min-w-[13.75rem] flex-1 flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Cari</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">search</span>
                        <input
                            type="text"
                            name="cari"
                            value="{{ $filter['cari'] }}"
                            class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-4 font-sans text-body-md text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            placeholder="Tanggal, no. surat jalan, supir..."
                        >
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-space-sm self-end lg:self-auto">
                <button
                    type="button"
                    data-tombol-reset-filter
                    onclick="window.resetTabelAjax('ritase')"
                    class="flex h-10 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3.5 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100 {{ $adaFilterAktif ? '' : 'hidden' }}"
                    title="Reset Semua Filter"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">restart_alt</span>
                    <span>Reset Filter</span>
                </button>
                <button
                    type="button"
                    class="flex h-10 items-center gap-1.5 rounded-lg bg-surface-container-low px-space-md font-sans text-body-md font-medium text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container-high"
                    @click="$dispatch('buka-modal-migrasi-excel', { tab: 'import', periode: '{{ $period['code'] }}' })"
                >
                    <span class="material-symbols-outlined text-[1.125rem] text-net-pay-emerald">table_chart</span>
                    Import / Export Excel
                </button>
                <button
                    type="button"
                    class="flex h-10 items-center gap-2 rounded-lg bg-primary-container px-space-lg font-display text-headline-sm text-on-primary shadow-md transition-transform active:scale-95"
                    @click="openCreate()"
                >
                    <span class="material-symbols-outlined text-[1.25rem]">add</span>
                    Buat Surat Jalan
                </button>
            </div>
        </div>

        {{-- Tabel ritase --}}
        <div class="flex flex-col overflow-hidden rounded-xl bg-card-bg shadow-card">
            <x-ui.tabel-massal
                url="{{ route('admin.operational.trips.status-massal') }}"
                :opsi-status="\App\Models\Ritase::daftarStatus()"
                label="surat jalan"
                keterangan="Pilih status surat jalan…"
            >
            <div class="w-full overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                            <th class="w-10 px-2 py-3.5 text-center font-semibold">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
                                    :checked="semuaTerpilih"
                                    @change="pilihSemuaBaris($event.target.checked)"
                                    aria-label="Pilih semua surat jalan"
                                >
                            </th>
                            <th class="w-14 px-4 py-3.5 text-center font-semibold">No</th>
                            <th class="px-4 py-3.5 font-semibold">Tanggal</th>
                            <th class="px-4 py-3.5 font-semibold">No. Pol &amp; Supir</th>
                            <th class="px-3 py-3.5 text-center font-semibold">Material</th>
                            <th class="px-3 py-3.5 text-center font-semibold">Rit</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Tonase</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Tarif / Ton</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Total Angkutan</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Uang Jalan Cair</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Rute</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Status</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="tbodyRitase" class="divide-y divide-surface-container-low font-sans text-financial-table">
                        @foreach ($rows as $indeksBaris => $row)
                            @include('components.tabel-baris.ritase', ['baris' => $row, 'nomor' => $rows->firstItem() + $indeksBaris, 'statusOptions' => $statusOptions])
                        @endforeach

                        @if ($rows->isEmpty())
                            <x-ui.table-empty
                                :colspan="13"
                                label="surat jalan pengantaran"
                                :tampilkan-reset="$adaFilterAktif"
                            />
                        @endif
                    </tbody>

                    <tfoot>
                        <tr class="bg-surface-container font-display text-headline-sm text-slate-text-primary">
                            <td class="px-4 py-3.5 font-bold" colspan="5">Total {{ number_format($summary['baris'], 0, ',', '.') }} baris periode ini</td>
                            <td class="px-3 py-3.5 text-center font-bold">{{ $summary['rit'] }} Rit</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-primary">{{ $summary['tonnage'] }} Ton</td>
                            <td class="px-4 py-3.5 text-right font-sans text-label-sm font-normal text-slate-text-secondary">-</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-primary">{{ $rp($summary['haulage']) }}</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-net-pay-emerald">{{ $rp($summary['trip_money']) }}</td>
                            <td class="px-4 py-3.5 text-center font-sans text-label-sm text-slate-text-secondary" colspan="3">Akumulasi Valid</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </x-ui.tabel-massal>

            <x-ui.tabel-ajax
                entitas="ritase"
                label="surat jalan"
                target-id="tbodyRitase"
                :kolom="13"
                :filter="$filter"
                :per-halaman-awal="$perPage"
            />
        </div>
        {{-- Modal tengah tambah/edit ritase --}}
        <x-ui.modal-shell
            name="modalRitaseForm"
            title="Buat Surat Jalan Pengantaran"
            subtitle="Tonase timbangan, tarif rute, dan uang jalan supir."
            icon="assignment"
            accent="gradient"
            maxWidth="2xl"
            :zIndex="50"
        >
            <form
                method="POST"
                :action="editing ? '{{ url('admin/operational/trips') }}/' + (form.id || 0) : '{{ route('admin.operational.trips.store') }}'"
                enctype="multipart/form-data"
                class="flex flex-col overflow-hidden"
                novalidate
                @submit="if (!validasiRitase()) $event.preventDefault()"
            >
                @csrf
                <input type="hidden" name="_method" value="PATCH" x-bind:disabled="! editing">
                <input type="hidden" name="id_periode_gaji" value="{{ $periodeAktifId }}">

                <div class="flex max-h-[70vh] flex-col gap-space-md overflow-y-auto p-space-lg">
                    <div class="flex items-center justify-between gap-2 rounded-lg bg-surface-container-low p-3.5">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined mt-0.5 shrink-0 text-[1.25rem] text-primary">calculate</span>
                            <div class="flex flex-col">
                                <span class="font-sans text-body-sm font-semibold text-slate-text-primary">Kalkulasi Otomatis Aktif</span>
                                <span class="font-sans text-label-sm text-slate-text-secondary">Total Nilai Angkutan dihitung instan: Tonase Timbangan &times; Tarif Rute per Ton.</span>
                            </div>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-0.5 font-sans text-label-sm font-bold"
                            :class="editing ? 'bg-warning-surface text-warning-amber' : 'bg-primary/10 text-primary'"
                            x-text="editing ? 'Mode Edit' : 'Surat Jalan Baru'"
                        ></span>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase">Tanggal Pengantaran</label>
                            <div class="relative flex items-center">
                                <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">calendar_today</span>
                                <input type="date" name="tanggal_ritase" x-model="form.date" required :class="errorsRitase.date ? 'ring-2 ring-danger-red' : ''" class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            </div>
                            <p x-show="errorsRitase.date" x-text="errorsRitase.date" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase" x-text="editing ? 'Status Verifikasi' : 'Status Awal Surat Jalan'">Status Awal Surat Jalan</label>
                            <template x-if="! editing">
                                <div class="relative flex items-center">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-3 z-10 text-[1.125rem] text-slate-text-muted">verified</span>
                                    <select name="status" x-model="form.status" :disabled="editing" required class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                                        <option value="draft">Draf — Belum Tampil ke Supir</option>
                                        <option value="terbit">Terbit — Tampil &amp; Siap Dikirim Supir</option>
                                    </select>
                                    <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                                </div>
                            </template>
                            <template x-if="editing">
                                <div class="relative flex items-center">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-3 z-10 text-[1.125rem] text-slate-text-muted">verified</span>
                                    <select name="status" x-model="form.status" :disabled="! editing" required class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                                        @foreach ($statusOptions as $nilai => $label)
                                            <option value="{{ $nilai }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase">Supir Pengantar</label>
                            <div class="relative flex items-center">
                                <span class="material-symbols-outlined pointer-events-none absolute left-3 z-10 text-[1.125rem] text-slate-text-muted">person</span>
                                <select name="id_karyawan" x-model="form.id_karyawan" @change="syncDriver($event)" required class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                                    <option value="">— Pilih supir —</option>
                                    @foreach ($drivers as $supir)
                                        <option value="{{ $supir['id'] }}"
                                            data-truk="{{ $supir['id_truk'] }}"
                                            data-plate="{{ $supir['plate'] }}"
                                            data-truck="{{ $supir['truk'] }}">
                                            {{ $supir['nama'] }} ({{ $supir['nip'] }}){{ $supir['plate'] ? ' — '.$supir['plate'] : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                            </div>
                            <p x-show="errorsRitase.id_karyawan" x-text="errorsRitase.id_karyawan" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase">Unit Armada Truk</label>
                            <div class="relative flex items-center">
                                <span class="material-symbols-outlined pointer-events-none absolute left-3 z-10 text-[1.125rem] text-slate-text-muted">local_shipping</span>
                                <select name="id_truk" x-model="form.id_truk" @change="syncTruck($event)" required class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                                    <option value="">— Pilih unit armada —</option>
                                    @foreach ($trucks ?? [] as $trukUnit)
                                        <option value="{{ $trukUnit['id'] }}"
                                            data-plate="{{ $trukUnit['nomor_polisi'] }}"
                                            data-truck="{{ $trukUnit['merek_model'] }}">
                                            {{ $trukUnit['nomor_polisi'] }} — {{ $trukUnit['merek_model'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                            </div>
                            <span class="font-sans text-label-sm text-slate-text-muted" x-text="form.plate ? 'Terpilih: ' + form.plate + ' (' + form.truck + ')' : 'Terisi otomatis dari supir atau pilih manual.'"></span>
                            <p x-show="errorsRitase.id_truk" x-text="errorsRitase.id_truk" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="font-sans text-label-sm font-semibold uppercase">Rute Pengangkutan</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined pointer-events-none absolute left-3 z-10 text-[1.125rem] text-slate-text-muted">alt_route</span>
                            <select name="id_rute" x-model="form.id_rute" @change="syncRoute($event)" required :class="errorsRitase.id_rute ? 'ring-2 ring-danger-red' : ''" class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                                <option value="">— Pilih rute —</option>
                                @foreach ($routes as $rute)
                                    <option value="{{ $rute['id'] }}"
                                        data-rate="{{ $rute['rate'] }}"
                                        data-trip-money="{{ $rute['trip_money'] }}"
                                        data-material="{{ $rute['name'] }}">
                                        {{ $rute['code'] }} ({{ $rute['name'] }}) — {{ 'Rp '.number_format($rute['rate'], 0, ',', '.') }}/ton
                                    </option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                        </div>
                        <p x-show="errorsRitase.id_rute" x-text="errorsRitase.id_rute" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase">Muatan Material</label>
                            <div class="relative flex items-center">
                                <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">layers</span>
                                <input type="text" :value="form.material || '—'" readonly class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-3 font-sans text-body-md font-semibold text-slate-text-primary">
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase">Tonase Bersih (Ton)</label>
                            <div class="relative flex items-center">
                                <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-primary">scale</span>
                                <input type="text" name="tonase_ton" x-model="form.tonnage" inputmode="decimal" placeholder="00,00" required :class="errorsRitase.tonnage ? 'ring-2 ring-danger-red' : ''" class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-14 font-sans text-body-md font-bold text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                                <span class="absolute right-3 font-sans text-label-sm font-semibold text-slate-text-secondary">TON</span>
                            </div>
                            <p x-show="errorsRitase.tonnage" x-text="errorsRitase.tonnage" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase">Jumlah Pengantaran (Trip)</label>
                            <div class="relative flex items-center">
                                <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">repeat</span>
                                <input type="number" name="jumlah_rit" x-model="form.rit" min="1" max="20" required :class="errorsRitase.rit ? 'ring-2 ring-danger-red' : ''" class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md font-bold text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            </div>
                            <p x-show="errorsRitase.rit" x-text="errorsRitase.rit" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase text-net-pay-emerald">Uang Jalan Cair</label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3 font-sans text-label-md font-bold text-net-pay-emerald">Rp</span>
                                <input type="hidden" name="uang_jalan" :value="form.trip_money">
                                <input type="text" :value="formatNumber(form.trip_money)" @input="form.trip_money = parseNumber($event.target.value)" :class="errorsRitase.trip_money ? 'ring-2 ring-danger-red' : ''" class="h-10 w-full rounded-lg bg-canvas-bg pl-10 pr-3 font-sans text-financial-table font-bold text-slate-text-primary transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-net-pay-emerald">
                            </div>
                            <span class="font-sans text-label-sm text-slate-text-muted">Sesuai approval mandor</span>
                            <p x-show="errorsRitase.trip_money" x-text="errorsRitase.trip_money" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase text-primary">Kalkulasi Angkutan (Auto)</label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3 font-sans text-label-md font-bold text-primary">Rp</span>
                                <input type="text" :value="formatNumber(totalHaulage)" readonly class="h-10 w-full cursor-not-allowed rounded-lg bg-surface-container pl-10 pr-3 font-sans text-financial-table font-bold text-primary">
                            </div>
                            <span class="font-sans text-label-sm text-slate-text-muted">Tarif &times; Tonase</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="font-sans text-label-sm font-semibold uppercase">Tarif per Ton</label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3 font-sans text-label-md font-bold text-slate-text-muted">Rp</span>
                                <input type="hidden" name="tarif_per_ton" :value="form.rate">
                                <input type="text" :value="formatNumber(form.rate)" readonly class="h-10 w-full cursor-not-allowed rounded-lg bg-surface-container pl-10 pr-3 font-sans text-financial-table font-bold text-slate-text-primary">
                            </div>
                            <span class="font-sans text-label-sm text-slate-text-muted">Mengikuti rute terpilih</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="font-sans text-label-sm font-semibold uppercase">Nama Pihak Penerima <span class="text-slate-text-muted">(Opsional)</span></label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">person_check</span>
                            <input type="text" name="nama_penerima" x-model="form.nama_penerima" maxlength="120" placeholder="Diisi supir saat serah terima atau isi manual" class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md text-slate-text-primary uppercase transition-all focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="font-sans text-label-sm font-semibold uppercase">Foto Surat Jalan / Tiket Timbangan <span class="text-slate-text-muted">(Opsional, Boleh &gt;1 Foto)</span></label>
                        <x-ui.upload-foto id="foto_surat_jalan_admin" name="foto_surat_jalan[]" :multiple="true" />
                        <span class="font-sans text-label-sm text-slate-text-muted">Boleh unggah lebih dari 1 foto bukti (maksimal 2 MB per foto, format JPG/PNG/WEBP, otomatis dikonversi ke .webp).</span>

                        <div x-show="editing && form.daftar_foto.length > 0" x-cloak class="mt-2 flex flex-col gap-2 rounded-lg border border-card-border bg-surface-container-low p-2.5">
                            <span class="font-sans text-label-sm font-semibold text-slate-text-secondary">Foto Bukti Tersimpan Saat Ini:</span>
                            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                <template x-for="(urlFoto, idxFoto) in form.daftar_foto" :key="urlFoto">
                                    <div
                                        :class="form.foto_dihapus.includes(urlFoto) ? 'opacity-45 ring-1 ring-danger-red' : 'ring-1 ring-card-border'"
                                        class="flex flex-col overflow-hidden rounded-lg bg-card-bg p-1.5 shadow-sm transition-all"
                                    >
                                        <div class="relative h-20 w-full overflow-hidden rounded bg-slate-900/5">
                                            <img :src="urlFoto" :alt="'Bukti ' + (idxFoto + 1)" class="h-full w-full object-cover">
                                            <span class="absolute left-1 top-1 rounded bg-slate-950/75 px-1.5 py-0.5 font-sans text-[0.625rem] font-semibold text-white" x-text="'#' + (idxFoto + 1)"></span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="toggleHapusFotoAdmin(urlFoto)"
                                            :class="form.foto_dihapus.includes(urlFoto) ? 'bg-surface-container-high text-slate-text-primary' : 'bg-danger-surface text-danger-red hover:bg-rose-100'"
                                            class="mt-1.5 inline-flex items-center justify-center gap-1 rounded px-2 py-1 font-sans text-[0.6875rem] font-semibold transition-colors"
                                        >
                                            <span class="material-symbols-outlined text-[0.875rem]" x-text="form.foto_dihapus.includes(urlFoto) ? 'undo' : 'delete'"></span>
                                            <span x-text="form.foto_dihapus.includes(urlFoto) ? 'Batal Hapus' : 'Hapus Foto'"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <template x-for="urlHapus in form.foto_dihapus" :key="urlHapus">
                            <input type="hidden" name="hapus_foto[]" :value="urlHapus">
                        </template>
                    </div>

                    <div class="mt-2 flex flex-col gap-2 rounded-xl bg-surface-container-low p-4">
                        <span class="font-sans text-label-sm font-bold uppercase tracking-wider">Verifikasi Tonase</span>
                        <div class="flex items-center justify-between text-body-sm">
                            <span class="font-sans text-slate-text-primary">Batas wajar per pengantaran (Maks 30 Ton)</span>
                            <span
                                class="inline-flex items-center gap-1 rounded px-2 py-0.5 font-sans text-label-sm font-bold"
                                :class="tonnageValue > 30 ? 'bg-danger-surface text-danger-red' : 'bg-surface-container-highest text-primary'"
                                x-text="tonnageValue > 30 ? 'MELEBIHI BATAS (' + form.tonnage + ' TON)' : 'AMAN (' + (form.tonnage || '0') + ' TON)'"
                            ></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-space-sm border-t border-card-border bg-surface-container-low p-space-lg">
                    <button type="button" class="h-10 rounded-lg bg-card-bg px-space-lg font-sans text-body-md font-medium text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container" @click="closeDrawer()">Batal</button>
                    <button type="submit" class="flex h-10 items-center gap-2 rounded-lg bg-primary-container px-space-xl font-display text-headline-sm text-on-primary shadow-md transition-transform active:scale-95">
                        <span class="material-symbols-outlined text-[1.25rem]">save</span>
                        <span x-text="editing ? 'Simpan Perubahan' : 'Buat Surat Jalan'">Buat Surat Jalan</span>
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- Modal Detail Ritase Read-Only (AGENTS.md 3.4 & BRIEF Bab 4.2.5) --}}
        <x-ui.modal-view
            name="modalRitaseDetail"
            title="Detail Surat Jalan & Pengantaran"
            subtitle="Rincian surat jalan, tonase timbangan, tarif rute, dan uang jalan supir."
            icon="receipt_long"
            :baris="$barisDetailRitase"
        >
            <div class="mt-2 flex flex-col gap-3 rounded-xl border border-card-border bg-surface-container-low p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <span class="material-symbols-outlined text-[1.25rem]">description</span>
                        </span>
                        <div class="flex flex-col">
                            <span class="font-display text-body-md font-bold text-slate-text-primary">Dokumen &amp; Foto Surat Jalan</span>
                            <span class="font-sans text-label-sm text-slate-text-secondary" x-text="'Tiket DO: ' + (ritaseDipilih.sj || '-') + ' • ' + (ritaseDipilih.date || '-')"></span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="bukaModalSuratJalan(ritaseDipilih)"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary-container px-4 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-all hover:bg-primary"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">visibility</span>
                        <span>Pratinjau Surat Jalan</span>
                    </button>
                </div>
            </div>
        </x-ui.modal-view>

        {{-- Modal Bersarang (z-index 60): Pratinjau Surat Jalan di atas Modal Detail (z-index 50) --}}
        <x-ui.modal-shell
            name="modalSuratJalan"
            title="Pratinjau Dokumen Surat Jalan"
            subtitle="Bukti surat jalan dan tiket timbangan pengantaran batubara."
            icon="description"
            badge="Dokumen SJ / DO"
            accent="gradient"
            max-width="2xl"
            :z-index="60"
        >
            <div class="flex max-h-[72vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                <div class="grid grid-cols-2 gap-3 rounded-xl border border-card-border bg-surface-container-low p-3.5 sm:grid-cols-4">
                    <div class="flex flex-col">
                        <span class="font-sans text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-text-muted">No. Surat Jalan</span>
                        <span class="font-display text-body-md font-bold text-primary" x-text="suratJalanDipilih.sj || '—'"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-sans text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-text-muted">Tanggal</span>
                        <span class="font-sans text-body-sm font-semibold text-slate-text-primary" x-text="suratJalanDipilih.date || '—'"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-sans text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-text-muted">Supir &amp; Unit</span>
                        <span class="font-sans text-body-sm font-semibold text-slate-text-primary" x-text="(suratJalanDipilih.driver || '—') + ' (' + (suratJalanDipilih.plate || '—') + ')'"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-sans text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-text-muted">Tonase &amp; Rute</span>
                        <span class="font-sans text-body-sm font-bold text-net-pay-emerald" x-text="(suratJalanDipilih.tonnage || '0,00') + ' Ton • ' + (suratJalanDipilih.route || '—')"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 rounded-xl border border-card-border bg-surface-container-lowest p-3.5 sm:grid-cols-3">
                    <div class="flex flex-col">
                        <span class="font-sans text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-text-muted">Status Tiket</span>
                        <span class="mt-0.5 font-sans text-body-sm font-bold text-slate-text-primary" x-text="suratJalanDipilih.status_label || suratJalanDipilih.status || '—'"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-sans text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-text-muted">Nama Pihak Penerima</span>
                        <span class="mt-0.5 font-sans text-body-sm font-bold text-slate-text-primary" x-text="suratJalanDipilih.nama_penerima || 'Belum diisi supir'"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-sans text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-text-muted">Catatan Pengantaran</span>
                        <span class="mt-0.5 font-sans text-body-sm font-medium text-slate-text-secondary" x-text="suratJalanDipilih.catatan_pengantaran || '—'"></span>
                    </div>
                </div>

                <div class="relative flex min-h-[260px] flex-col items-center justify-center gap-3 overflow-hidden rounded-xl border border-card-border bg-slate-950/90 p-3">
                    <div class="flex w-full items-center justify-between gap-2 px-1">
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-sans text-label-sm font-semibold"
                            :class="daftarFotoSuratJalanDipilih.length > 0 ? 'bg-primary/20 text-white' : 'bg-white/10 text-slate-300'"
                        >
                            <span class="material-symbols-outlined text-[0.95rem]">photo_library</span>
                            <span x-text="daftarFotoSuratJalanDipilih.length > 0 ? daftarFotoSuratJalanDipilih.length + ' Foto Bukti' : 'Belum Ada Foto Bukti'"></span>
                        </span>
                        <span
                            x-show="daftarFotoSuratJalanDipilih.length > 1"
                            x-cloak
                            class="font-sans text-label-sm font-semibold text-slate-200"
                            x-text="'Foto ' + (indeksFotoAktif + 1) + ' / ' + daftarFotoSuratJalanDipilih.length"
                        ></span>
                    </div>

                    <img
                        :src="fotoAktifSuratJalan"
                        :alt="'Surat Jalan ' + (suratJalanDipilih.sj || '')"
                        onerror="this.onerror=null;this.src='/images/placeholders/surat-jalan-default.svg';"
                        @click="window.bukaLightbox({ url: fotoAktifSuratJalan, galeri: daftarFotoGaleriLightbox, indeks: indeksFotoAktif, judul: 'Surat Jalan ' + (suratJalanDipilih.sj || ''), caption: (suratJalanDipilih.driver || '') + ' • ' + (suratJalanDipilih.plate || '') + ' • ' + (suratJalanDipilih.date || '') })"
                        class="max-h-[46vh] w-auto max-w-full cursor-zoom-in rounded-lg object-contain shadow-lg"
                    >

                    <div
                        x-show="daftarFotoSuratJalanDipilih.length > 1"
                        x-cloak
                        class="flex max-w-full items-center gap-2 overflow-x-auto py-1"
                    >
                        <template x-for="(urlThumb, idxThumb) in daftarFotoSuratJalanDipilih" :key="idxThumb">
                            <button
                                type="button"
                                @click="indeksFotoAktif = idxThumb"
                                :class="idxThumb === indeksFotoAktif ? 'ring-2 ring-primary opacity-100' : 'opacity-60 hover:opacity-100'"
                                class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-white/20 bg-slate-900 transition-all"
                            >
                                <img :src="urlThumb" :alt="'Thumbnail ' + (idxThumb + 1)" class="h-full w-full object-cover">
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-surface-container-low px-3.5 py-2.5">
                    <span class="font-sans text-label-sm text-slate-text-secondary">
                        Klik gambar atau tombol perbesar untuk memeriksa detail angka timbangan pada foto layar penuh.
                    </span>
                    <button
                        type="button"
                        @click="window.bukaLightbox({ url: fotoAktifSuratJalan, galeri: daftarFotoGaleriLightbox, indeks: indeksFotoAktif, judul: 'Surat Jalan ' + (suratJalanDipilih.sj || ''), caption: (suratJalanDipilih.driver || '') + ' • ' + (suratJalanDipilih.plate || '') + ' • ' + (suratJalanDipilih.date || '') })"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-primary/25 bg-card-bg px-3 py-1.5 font-sans text-label-md font-semibold text-primary transition-colors hover:bg-primary/10"
                    >
                        <span class="material-symbols-outlined text-[1rem]">zoom_in</span>
                        <span>Perbesar Layar Penuh</span>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 bg-canvas-bg px-space-lg py-space-md shadow-inner">
                <button
                    type="button"
                    class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low"
                    @click="modalSuratJalan = false"
                >
                    Tutup Pratinjau
                </button>
                <a
                    :href="'/admin/operational/trips/' + (suratJalanDipilih.id || 0) + '/cetak'"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-card-border bg-card-bg px-3.5 py-2.5 font-sans text-body-sm font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">print</span>
                    <span>Cetak Surat Jalan</span>
                </a>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        x-show="suratJalanDipilih.id && suratJalanDipilih.status === 'draft'"
                        x-cloak
                        :disabled="sedangVerifikasiSj"
                        @click="ubahStatusSuratJalan(suratJalanDipilih.id, 'terbit')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-space-md py-2.5 font-sans text-body-sm font-bold text-white shadow-sm transition-all hover:bg-amber-600 disabled:opacity-60"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">send</span>
                        <span x-text="sedangVerifikasiSj ? 'Menerbitkan...' : 'Terbitkan ke Supir'">Terbitkan ke Supir</span>
                    </button>
                    <button
                        type="button"
                        x-show="suratJalanDipilih.id && suratJalanDipilih.status !== 'draft'"
                        x-cloak
                        :disabled="sedangVerifikasiSj"
                        @click="ubahStatusSuratJalan(suratJalanDipilih.id, 'draft')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-warning-amber/30 bg-warning-surface px-space-md py-2.5 font-sans text-body-sm font-bold text-warning-amber transition-all hover:bg-amber-100 disabled:opacity-60"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">undo</span>
                        <span>Kembalikan ke Draf</span>
                    </button>
                    <button
                        type="button"
                        x-show="suratJalanDipilih.id && suratJalanDipilih.status !== 'terverifikasi'"
                        x-cloak
                        :disabled="sedangVerifikasiSj"
                        @click="ubahStatusSuratJalan(suratJalanDipilih.id, 'terverifikasi')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-net-pay-emerald px-space-lg py-2.5 font-sans text-body-sm font-bold text-on-primary shadow-md transition-all hover:bg-emerald-600 disabled:opacity-60"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">verified</span>
                        <span x-text="sedangVerifikasiSj ? 'Menyimpan...' : 'Tandai Terverifikasi (Berhasil Terkirim)'"></span>
                    </button>
                </div>
            </div>
        </x-ui.modal-shell>

        {{-- Modal Konfirmasi Hapus Ritase (BRIEF Bab 1.1) --}}
        <x-ui.modal-delete
            name="modalRitaseHapus"
            title="Hapus Surat Jalan Pengantaran?"
            entity="catatan pengantaran surat jalan terpilih"
            action-binding="'{{ url('admin/operational/trips') }}/' + (ritaseDipilih.id || 0)"
            centang="setujuRitaseHapus"
        />

        <x-payroll.excel-sync-modal
            :opsi-periode="$periodOptions"
            :periode-default="$period['code']"
            :periode-ekspor-default="$period['code']"
        />
    </div>
</x-app-layout>
