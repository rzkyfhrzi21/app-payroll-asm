<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN PENGELUARAN OPERASIONAL & BIAYA ARMADA):
     - Tujuan Halaman: Mencatat pengeluaran operasional armada truk (sparepart bengkel,
       servis berkala, oli, ban, retribusi, dan solar tambahan).
     - Analogi Dunia Nyata: Seperti map kuitansi nota bengkel di meja mandor armada:
       setiap truk yang masuk bengkel atau ganti ban dicatat notanya agar saat tutup buku,
       biaya sparepart ini dapat dikurangkan dari hasil angkutan armada sebelum komisi supir dihitung.
     - Hubungan ke Rumus Penggajian 4 Tahap (AGENTS.md Bab 3.8):
       * Kategori 'sparepart' dan 'servis' akan memotong nilai angkutan armada:
         `hasil_bersih_armada = max(0, angkutan_bersih - total_uang_jalan - biaya_sparepart)`.
     - Data dari Controller:
       * $period: Info periode aktif pengeluaran.
       * $summary: Total biaya, jumlah transaksi kuitansi, dan jumlah truk terlibat.
       * $expenses: Paginator daftar biaya operasional dari tabel `biaya_operasional`.
       * $trucks: Daftar master truk untuk dropdown pilihan unit.
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');

    $barisDetailBiaya = "[
        { label: 'Tanggal', nilai: biayaDipilih.tanggal },
        { label: 'Unit Armada', nilai: biayaDipilih.plate + ' — ' + biayaDipilih.truck },
        { label: 'Kategori', nilai: biayaDipilih.kategori_label },
        { label: 'Nomor Nota', nilai: biayaDipilih.nomor_nota || '—' },
        { label: 'Nominal', nilai: 'Rp ' + Number(biayaDipilih.nominal || 0).toLocaleString('id-ID') },
        { label: 'Keterangan', nilai: biayaDipilih.keterangan || '—' },
        { label: 'Status', nilai: biayaDipilih.status_label },
    ]";

    $adaFilterAktif = $filter['cari'] !== ''
        || $filter['kategori'] !== 'all'
        || $filter['unit'] !== 'all'
        || $filter['status'] !== 'all';
@endphp

<x-app-layout title="Pengeluaran Operasional">
    <div class="flex flex-col gap-6" x-data="expensesPage([], @js($period))" data-wadah-tabel="biaya">
        {{-- Banner --}}
        <div class="relative w-full overflow-hidden rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-400 p-space-xl text-white shadow-hero">
            <div class="pointer-events-none absolute -right-16 -top-20 h-80 w-80 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>

            <div class="relative z-10 flex flex-col justify-between gap-space-lg lg:flex-row lg:items-center">
                <div class="flex max-w-2xl flex-col gap-1">
                    <div class="mb-1 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 font-sans text-label-sm font-semibold uppercase tracking-wider backdrop-blur-md">
                            <span class="material-symbols-outlined text-[0.875rem]">receipt</span>
                            Pengeluaran Armada
                        </span>
                        <span class="font-sans text-label-sm text-white/70">&bull;</span>
                        <span class="font-sans text-label-sm tracking-wide">Periode &mdash; {{ $period['range'] }}</span>
                    </div>
                    <h1 class="font-display text-headline-lg font-bold tracking-tight">Pengeluaran Operasional</h1>
                    <p class="font-sans text-body-md leading-relaxed text-white/85">
                        Catat sparepart, BBM, tol, retribusi, dan servis armada per periode gaji.
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="font-sans text-[0.625rem] font-bold uppercase tracking-wider text-white/60">Periode &mdash; {{ $period['code'] }}</span>
                    <div class="flex flex-wrap items-center gap-space-sm">
                        <div class="flex min-w-[7.5rem] flex-col rounded-xl bg-white/10 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Total Biaya</span>
                            <span class="font-display text-headline-md font-bold tabular-nums">{{ $rp($summary['total']) }}</span>
                        </div>
                        <div class="flex min-w-[6rem] flex-col rounded-xl bg-white/10 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Item</span>
                            <span class="font-display text-headline-md font-bold">{{ $summary['baris'] }}</span>
                        </div>
                        <div class="flex min-w-[6rem] flex-col rounded-xl bg-white/15 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Unit</span>
                            <span class="font-display text-headline-md font-bold">{{ $summary['unit'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Akumulasi Keseluruhan Pengeluaran Operasional --}}
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
                            {{ $period['code'] }}
                        </span>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 font-sans text-label-sm text-slate-text-muted">
                    <span class="material-symbols-outlined text-[0.95rem]">inventory_2</span>
                    Seluruh pengeluaran pada periode ini
                </span>
            </div>

            <div class="grid grid-cols-1 gap-space-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="group relative flex items-center gap-3 overflow-hidden rounded-xl border border-card-border/60 bg-gradient-to-br from-surface-container-low to-card-bg px-3.5 py-3">
                    <span class="absolute inset-y-0 left-0 w-1 bg-banner-indigo"></span>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-banner-indigo/10 text-banner-indigo">
                        <span class="material-symbols-outlined text-[1.25rem]">payments</span>
                    </span>
                    <div class="flex min-w-0 flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Total Biaya</span>
                        <span class="truncate font-display text-headline-sm font-bold tabular-nums text-banner-indigo">{{ $rp($summary['total']) }}</span>
                    </div>
                </div>

                <div class="group relative flex items-center gap-3 overflow-hidden rounded-xl border border-card-border/60 bg-gradient-to-br from-surface-container-low to-card-bg px-3.5 py-3">
                    <span class="absolute inset-y-0 left-0 w-1 bg-amber-500"></span>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                        <span class="material-symbols-outlined text-[1.25rem]">receipt_long</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Jumlah Item</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-slate-text-primary">
                            {{ number_format((int) $summary['baris'], 0, ',', '.') }}
                            <span class="font-sans text-label-sm font-normal text-slate-text-muted">item</span>
                        </span>
                    </div>
                </div>

                <div class="group relative flex items-center gap-3 overflow-hidden rounded-xl border border-card-border/60 bg-gradient-to-br from-surface-container-low to-card-bg px-3.5 py-3">
                    <span class="absolute inset-y-0 left-0 w-1 bg-net-pay-emerald"></span>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.25rem]">local_shipping</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Unit Terdampak</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-slate-text-primary">
                            {{ number_format((int) $summary['unit'], 0, ',', '.') }}
                            <span class="font-sans text-label-sm font-normal text-slate-text-muted">unit</span>
                        </span>
                    </div>
                </div>

                <div class="group relative flex items-center gap-3 overflow-hidden rounded-xl border border-card-border/60 bg-gradient-to-br from-surface-container-low to-card-bg px-3.5 py-3">
                    <span class="absolute inset-y-0 left-0 w-1 bg-slate-text-muted"></span>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-surface-container text-slate-text-secondary">
                        <span class="material-symbols-outlined text-[1.25rem]">pending_actions</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">Status</span>
                        <span class="flex items-center gap-1.5 font-display text-headline-sm font-bold text-slate-text-primary">
                            <span class="text-warning-amber">{{ $summary['draft'] }}</span>
                            <span class="font-sans text-label-sm font-normal text-slate-text-muted">draf</span>
                            <span class="text-slate-text-muted">/</span>
                            <span class="text-net-pay-emerald">{{ $summary['disetujui'] }}</span>
                            <span class="font-sans text-label-sm font-normal text-slate-text-muted">ACC</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter + Tombol Tambah --}}
        <div
            class="flex flex-col items-stretch justify-between gap-space-md rounded-xl bg-card-bg p-space-md shadow-card lg:flex-row lg:items-end"
        >
            <div class="flex flex-1 flex-col gap-space-md sm:flex-row sm:flex-wrap">

                {{-- Filter periode --}}
                <div class="flex min-w-[14.375rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Periode</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-banner-indigo">date_range</span>
                        <select
                            name="periode"
                            data-no-tabel-ajax
                            onchange="if (this.value) window.location.assign('{{ route('admin.operational.expenses.index') }}?periode=' + encodeURIComponent(this.value))"
                            class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >
                            @foreach ($periodOptions as $opsi)
                                <option
                                    value="{{ $opsi['kode'] }}"
                                    @selected($opsi['kode'] === $period['code'])
                                >{{ $opsi['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                {{-- Filter kategori --}}
                <div class="flex min-w-[12rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Kategori</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">category</span>
                        <select name="kategori" class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            <option value="all">Semua Kategori</option>
                            @foreach ($kategoriOptions as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($filter['kategori'] === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                {{-- Filter unit --}}
                <div class="flex min-w-[13rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Unit Armada</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">local_shipping</span>
                        <select name="unit" class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                            <option value="all">Semua Unit</option>
                            @foreach ($unitOptions as $pelat)
                                <option value="{{ $pelat }}" @selected($filter['unit'] === $pelat)>{{ $pelat }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                {{-- Filter Status --}}
                <div class="flex min-w-[9.5rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Status</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">fact_check</span>
                        <select
                            name="status"
                           
                            class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >
                            <option value="all">Semua Status</option>
                            <option value="draft" @selected($filter['status'] === 'draft')>Draf</option>
                            <option value="diajukan" @selected($filter['status'] === 'diajukan')>Diajukan</option>
                            <option value="disetujui" @selected($filter['status'] === 'disetujui')>Disetujui</option>
                            <option value="ditolak" @selected($filter['status'] === 'ditolak')>Ditolak</option>
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-secondary">expand_more</span>
                    </div>
                </div>

                {{-- Cari --}}
                <div class="flex min-w-[13.75rem] flex-1 flex-col">
                    <label class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Cari</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">search</span>
                        <input
                            type="text"
                            name="cari"
                            value="{{ $filter['cari'] }}"
                            class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-4 font-sans text-body-md text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            placeholder="No. nota, keterangan, unit..."
                        >
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-space-sm self-end lg:self-auto">
                <button
                    type="button"
                    data-tombol-reset-filter
                    onclick="window.resetTabelAjax('biaya')"
                    class="flex h-10 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3.5 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100 {{ $adaFilterAktif ? '' : 'hidden' }}"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">restart_alt</span>
                    <span>Reset Filter</span>
                </button>
                <button
                    type="button"
                    class="flex h-10 items-center gap-2 rounded-lg bg-primary-container px-space-lg font-display text-headline-sm text-on-primary shadow-md transition-transform active:scale-95"
                    @click="bukaTambah()"
                >
                    <span class="material-symbols-outlined text-[1.25rem]">add</span>
                    Catat Pengeluaran
                </button>
            </div>
        </div>

        {{-- Rekap per kategori --}}
        @if (count($summary['per_kategori']) > 0)
        <div class="grid grid-cols-2 gap-space-sm lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($summary['per_kategori'] as $kat)
            <div class="flex flex-col rounded-xl bg-card-bg p-3 shadow-card">
                <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-secondary">{{ $kat['label'] }}</span>
                <span class="font-display text-headline-sm font-bold tabular-nums text-slate-text-primary">{{ $rp($kat['total']) }}</span>
                <span class="font-sans text-label-sm text-slate-text-muted">{{ $kat['jumlah'] }} item</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Tabel biaya --}}
        <div class="flex flex-col overflow-hidden rounded-xl bg-card-bg shadow-card">
            <x-ui.tabel-massal
                url="{{ route('admin.operational.expenses.status-massal') }}"
                :opsi-status="\App\Models\BiayaOperasional::daftarStatus()"
                label="pengeluaran"
                keterangan="Pilih status pengeluaran…"
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
                                    aria-label="Pilih semua pengeluaran"
                                >
                            </th>
                            <th class="w-14 px-4 py-3.5 text-center font-semibold">No</th>
                            <th class="px-4 py-3.5 font-semibold">Tanggal</th>
                            <th class="px-4 py-3.5 font-semibold">Unit Armada</th>
                            <th class="px-4 py-3.5 font-semibold">Kategori</th>
                            <th class="px-4 py-3.5 font-semibold">No. Nota</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Nominal</th>
                            <th class="px-4 py-3.5 font-semibold">Keterangan</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Status</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="tbodyBiaya" class="divide-y divide-surface-container-low font-sans text-financial-table">
                        @foreach ($rows as $indeksBaris => $baris)
                            @include('components.tabel-baris.biaya', ['baris' => $baris, 'nomor' => $rows->firstItem() + $indeksBaris])
                        @endforeach

                        @if ($rows->isEmpty())
                            <x-ui.table-empty :colspan="10" label="pengeluaran operasional" :tampilkan-reset="$adaFilterAktif" />
                        @endif
                    </tbody>

                    <tfoot>
                        <tr class="bg-surface-container font-display text-headline-sm text-slate-text-primary">
                            <td class="px-4 py-3.5 font-bold" colspan="6">Total {{ $summary['baris'] }} item periode ini</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-primary">{{ $rp($summary['total']) }}</td>
                            <td class="px-4 py-3.5 text-center font-sans text-label-sm text-slate-text-secondary" colspan="3">
                                <span class="text-warning-amber">{{ $summary['draft'] }} Draf</span>
                                &bull;
                                <span class="text-net-pay-emerald">{{ $summary['disetujui'] }} Disetujui</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </x-ui.tabel-massal>

            <x-ui.tabel-ajax entitas="biaya" label="pengeluaran" target-id="tbodyBiaya" :kolom="10" :filter="$filter" :per-halaman-awal="$perPage" />
        </div>

        {{-- =================== MODAL TAMBAH =================== --}}
        <x-ui.modal-shell
            name="modalBiayaTambah"
            title="Catat Pengeluaran"
            subtitle="Pengeluaran sparepart, BBM, tol, retribusi, servis, atau lain-lain."
            icon="add_circle"
            accent="gradient"
            maxWidth="2xl"
            :zIndex="50"
        >
            <form method="POST" action="{{ route('admin.operational.expenses.store') }}" enctype="multipart/form-data" data-form="biaya-tambah" novalidate @submit="if (!validasiExpensesTambah()) $event.preventDefault()">
                @csrf
                <input type="hidden" name="id_periode_gaji" value="{{ $periodeAktifId }}">

                <div class="flex flex-col gap-space-md p-space-lg">

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        {{-- Tanggal --}}
                        <x-ui.modal-field label="Tanggal Pengeluaran" required icon="calendar_today">
                            <input
                                type="date"
                                name="tanggal_biaya"
                                required
                                :class="errorsTambah.tanggal_biaya ? 'ring-2 ring-danger-red' : ''"
                                class="{{ $errors->has('tanggal_biaya') ? 'ring-2 ring-danger-red' : '' }} h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                                value="{{ old('tanggal_biaya') }}"
                            >
                            <p x-show="errorsTambah.tanggal_biaya" x-text="errorsTambah.tanggal_biaya" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                            @error('tanggal_biaya') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                        </x-ui.modal-field>

                        {{-- Kategori --}}
                        <x-ui.modal-field label="Kategori" required icon="category">
                            <select
                                name="kategori"
                                required
                                :class="errorsTambah.kategori ? 'ring-2 ring-danger-red' : ''"
                                class="{{ $errors->has('kategori') ? 'ring-2 ring-danger-red' : '' }} h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            >
                                <option value="">— Pilih kategori —</option>
                                @foreach ($kategoriOptions as $nilai => $label)
                                    <option value="{{ $nilai }}" @selected(old('kategori') === $nilai)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                            <p x-show="errorsTambah.kategori" x-text="errorsTambah.kategori" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                            @error('kategori') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                        </x-ui.modal-field>
                    </div>

                    {{-- Unit Armada --}}
                    <x-ui.modal-field label="Unit Armada" required icon="local_shipping">
                        <select
                            name="id_truk"
                            required
                            :class="errorsTambah.id_truk ? 'ring-2 ring-danger-red' : ''"
                            class="{{ $errors->has('id_truk') ? 'ring-2 ring-danger-red' : '' }} h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >
                            <option value="">— Pilih unit —</option>
                            @foreach ($trucks as $truk)
                                <option value="{{ $truk['id'] }}" @selected(old('id_truk') == $truk['id'])>
                                    {{ $truk['nomor_polisi'] }} — {{ $truk['merek_model'] }}{{ $truk['driver'] ? ' (Supir: '.$truk['driver'].')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                        <p x-show="errorsTambah.id_truk" x-text="errorsTambah.id_truk" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        @error('id_truk') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                    </x-ui.modal-field>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        {{-- Nominal --}}
                        <x-ui.modal-field label="Nominal" required icon="payments" hint="Angka bulat Rp 1 s/d Rp 999.999.999">
                            <span class="pointer-events-none absolute left-3 font-sans text-label-md font-bold text-slate-text-muted">Rp</span>
                            <input
                                type="text"
                                name="nominal"
                                required
                                inputmode="numeric"
                                placeholder="0"
                                :class="errorsTambah.nominal ? 'ring-2 ring-danger-red' : ''"
                                class="{{ $errors->has('nominal') ? 'ring-2 ring-danger-red' : '' }} h-10 w-full rounded-lg bg-canvas-bg pl-10 pr-3 font-sans text-financial-table font-bold focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                                value="{{ old('nominal') }}"
                                x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                            >
                            <p x-show="errorsTambah.nominal" x-text="errorsTambah.nominal" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                            @error('nominal') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                        </x-ui.modal-field>

                        {{-- Nomor Nota --}}
                        <x-ui.modal-field label="Nomor Nota" icon="confirmation_number" hint="Opsional">
                            <input
                                type="text"
                                name="nomor_nota"
                                maxlength="50"
                                placeholder="NTX-00001"
                                :class="errorsTambah.nomor_nota ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                                value="{{ old('nomor_nota') }}"
                            >
                            <p x-show="errorsTambah.nomor_nota" x-text="errorsTambah.nomor_nota" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>
                    </div>

                    {{-- Keterangan --}}
                    <x-ui.modal-field label="Keterangan" icon="notes" hint="Opsional, maksimal 200 karakter">
                        <textarea
                            name="keterangan"
                            rows="2"
                            maxlength="200"
                            placeholder="Deskripsi singkat pengeluaran..."
                            :class="errorsTambah.keterangan ? 'ring-2 ring-danger-red' : ''"
                            class="w-full rounded-lg bg-canvas-bg py-2 pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >{{ old('keterangan') }}</textarea>
                        <p x-show="errorsTambah.keterangan" x-text="errorsTambah.keterangan" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </x-ui.modal-field>

                    {{-- Status --}}
                    <x-ui.modal-field label="Status" required icon="verified">
                        <select
                            name="status"
                            required
                            :class="errorsTambah.status ? 'ring-2 ring-danger-red' : ''"
                            class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >
                            @foreach ($statusOptions as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('status', 'draft') === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                        <p x-show="errorsTambah.status" x-text="errorsTambah.status" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </x-ui.modal-field>

                    {{-- Foto Bukti Nota --}}
                    <x-ui.modal-field label="Foto Bukti Nota" icon="photo_camera" meta="(Opsional)" hint="Maksimal 2MB">
                        <x-ui.upload-foto id="foto_nota_tambah" name="foto_nota" :ikon="true" />
                    </x-ui.modal-field>
                </div>

                <div class="flex items-center justify-end gap-space-sm border-t border-card-border p-space-lg">
                    <button type="button" class="h-10 rounded-lg bg-surface-container-low px-space-lg font-sans text-body-md font-medium text-slate-text-primary transition-colors hover:bg-surface-container" @click="modalBiayaTambah = false">Batal</button>
                    <button type="submit" class="flex h-10 items-center gap-2 rounded-lg bg-primary-container px-space-xl font-display text-headline-sm text-on-primary shadow-md transition-transform active:scale-95">
                        <span class="material-symbols-outlined text-[1.25rem]">save</span>
                        Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- =================== MODAL EDIT =================== --}}
        <x-ui.modal-shell
            name="modalBiayaEdit"
            title="Edit Pengeluaran"
            subtitle="Perbarui data pengeluaran operasional armada."
            icon="edit_note"
            accent="gradient"
            maxWidth="2xl"
            :zIndex="50"
        >
            <form method="POST" :action="'{{ url('admin/operational/expenses') }}/' + (biayaEdit.id || 0)" enctype="multipart/form-data" data-modal="biaya-edit" data-form="biaya-edit" novalidate @submit="if (!validasiExpensesEdit()) $event.preventDefault()">
                @csrf
                @method('PATCH')

                <div class="flex flex-col gap-space-md p-space-lg">

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        {{-- Tanggal --}}
                        <x-ui.modal-field label="Tanggal Pengeluaran" required icon="calendar_today">
                            <input
                                type="date"
                                name="tanggal_biaya"
                                required
                                :value="biayaEdit.tanggal_iso"
                                :class="errorsEdit.tanggal_biaya ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            >
                            <p x-show="errorsEdit.tanggal_biaya" x-text="errorsEdit.tanggal_biaya" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>

                        {{-- Kategori --}}
                        <x-ui.modal-field label="Kategori" required icon="category">
                            <select
                                name="kategori"
                                required
                                :class="errorsEdit.kategori ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                                x-effect="$el.value = biayaEdit.kategori"
                            >
                                <option value="">— Pilih kategori —</option>
                                @foreach ($kategoriOptions as $nilai => $label)
                                    <option value="{{ $nilai }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                            <p x-show="errorsEdit.kategori" x-text="errorsEdit.kategori" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>
                    </div>

                    {{-- Unit Armada --}}
                    <x-ui.modal-field label="Unit Armada" required icon="local_shipping">
                        <select
                            name="id_truk"
                            required
                            :class="errorsEdit.id_truk ? 'ring-2 ring-danger-red' : ''"
                            class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            x-effect="$el.value = biayaEdit.id_truk"
                        >
                            <option value="">— Pilih unit —</option>
                            @foreach ($trucks as $truk)
                                <option value="{{ $truk['id'] }}">
                                    {{ $truk['nomor_polisi'] }} — {{ $truk['merek_model'] }}{{ $truk['driver'] ? ' (Supir: '.$truk['driver'].')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                        <p x-show="errorsEdit.id_truk" x-text="errorsEdit.id_truk" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </x-ui.modal-field>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        {{-- Nominal --}}
                        <x-ui.modal-field label="Nominal" required icon="payments">
                            <span class="pointer-events-none absolute left-3 font-sans text-label-md font-bold text-slate-text-muted">Rp</span>
                            <input
                                type="text"
                                name="nominal"
                                required
                                inputmode="numeric"
                                :class="errorsEdit.nominal ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg bg-canvas-bg pl-10 pr-3 font-sans text-financial-table font-bold focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                                x-bind:value="Number(biayaEdit.nominal || 0).toLocaleString('id-ID')"
                                x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                            >
                            <p x-show="errorsEdit.nominal" x-text="errorsEdit.nominal" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>

                        {{-- Nomor Nota --}}
                        <x-ui.modal-field label="Nomor Nota" icon="confirmation_number" hint="Opsional">
                            <input
                                type="text"
                                name="nomor_nota"
                                maxlength="50"
                                :class="errorsEdit.nomor_nota ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                                x-bind:value="biayaEdit.nomor_nota"
                            >
                            <p x-show="errorsEdit.nomor_nota" x-text="errorsEdit.nomor_nota" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>
                    </div>

                    {{-- Keterangan --}}
                    <x-ui.modal-field label="Keterangan" icon="notes" hint="Opsional, maksimal 200 karakter">
                        <textarea
                            name="keterangan"
                            rows="2"
                            maxlength="200"
                            :class="errorsEdit.keterangan ? 'ring-2 ring-danger-red' : ''"
                            class="w-full rounded-lg bg-canvas-bg py-2 pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            x-text="biayaEdit.keterangan"
                        ></textarea>
                        <p x-show="errorsEdit.keterangan" x-text="errorsEdit.keterangan" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </x-ui.modal-field>

                    {{-- Status --}}
                    <x-ui.modal-field label="Status" required icon="verified">
                        <select
                            name="status"
                            required
                            :class="errorsEdit.status ? 'ring-2 ring-danger-red' : ''"
                            class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            x-effect="$el.value = biayaEdit.status"
                        >
                            @foreach ($statusOptions as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                        <p x-show="errorsEdit.status" x-text="errorsEdit.status" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </x-ui.modal-field>

                    {{-- Ganti Foto Bukti Nota --}}
                    <x-ui.modal-field label="Ganti Foto Bukti Nota" icon="photo_camera" meta="(Opsional)" hint="Maksimal 2MB">
                        <x-ui.upload-foto id="foto_nota_edit" name="foto_nota" :ikon="true" />
                    </x-ui.modal-field>
                </div>

                <div class="flex items-center justify-end gap-space-sm border-t border-card-border p-space-lg">
                    <button type="button" class="h-10 rounded-lg bg-surface-container-low px-space-lg font-sans text-body-md font-medium text-slate-text-primary transition-colors hover:bg-surface-container" @click="modalBiayaEdit = false">Batal</button>
                    <button type="submit" class="flex h-10 items-center gap-2 rounded-lg bg-primary-container px-space-xl font-display text-headline-sm text-on-primary shadow-md transition-transform active:scale-95">
                        <span class="material-symbols-outlined text-[1.25rem]">save</span>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- =================== MODAL DETAIL =================== --}}
        <x-ui.modal-view
            name="modalBiayaDetail"
            :baris="$barisDetailBiaya"
            title="Detail Pengeluaran"
            icon="receipt"
            :zIndex="50"
        />

        {{-- =================== MODAL HAPUS =================== --}}
        <x-ui.modal-shell
            name="modalBiayaHapus"
            title="Hapus Pengeluaran"
            subtitle="Tindakan ini tidak dapat dibatalkan."
            icon="delete"
            accent="danger"
            maxWidth="lg"
            :zIndex="50"
        >
            <div class="flex flex-col gap-space-md p-space-lg">
                <div class="flex items-start gap-3 rounded-xl bg-danger-surface p-4">
                    <span class="material-symbols-outlined mt-0.5 shrink-0 text-[1.5rem] text-danger-red">warning</span>
                    <div class="flex flex-col gap-1">
                        <span class="font-sans text-body-md font-semibold text-danger-red">Konfirmasi Penghapusan</span>
                        <span class="font-sans text-body-sm text-slate-text-primary">
                            Anda akan menghapus pengeluaran
                            <strong x-text="biayaHapus.kategori_label + ' — ' + (biayaHapus.nomor_nota || 'tanpa nota')"></strong>
                            senilai <strong x-text="'Rp ' + Number(biayaHapus.nominal || 0).toLocaleString('id-ID')"></strong>.
                        </span>
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-card-border p-3 hover:bg-surface-container-low">
                    <input type="checkbox" x-model="konfirmasiHapus" class="h-4 w-4 rounded border-slate-300 text-danger-red accent-danger-red">
                    <span class="font-sans text-body-sm text-slate-text-primary">Ya, saya yakin ingin menghapus data ini.</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-space-sm border-t border-card-border p-space-lg">
                <button type="button" class="h-10 rounded-lg bg-surface-container-low px-space-lg font-sans text-body-md font-medium text-slate-text-primary transition-colors hover:bg-surface-container" @click="modalBiayaHapus = false">Batal</button>
                <form method="POST" :action="'{{ url('admin/operational/expenses') }}/' + (biayaHapus.id || 0)" x-ref="formHapus">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        :disabled="!konfirmasiHapus"
                        class="flex h-10 items-center gap-2 rounded-lg bg-danger-red px-space-xl font-display text-headline-sm text-white shadow-md transition-transform active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span class="material-symbols-outlined text-[1.25rem]">delete</span>
                        Hapus Sekarang
                    </button>
                </form>
            </div>
        </x-ui.modal-shell>

    </div>

</x-app-layout>
