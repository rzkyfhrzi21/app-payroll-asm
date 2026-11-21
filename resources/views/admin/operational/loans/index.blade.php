<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN KASBON & POTONGAN SUPIR):
     - Tujuan Halaman: Mengelola pinjaman uang muka supir (Kasbon), jadwal angsuran cicilan,
       serta pencatatan potongan BPJS supir.
     - Analogi Dunia Nyata: Seperti buku pinjaman kasbon supir di laci kasir tambang:
       supir butuh uang darurat sebelum gajian (misal untuk keluarga atau uang saku darurat),
       perusahaan meminjamkan maksimal Rp 1.000.000, lalu dicicil potong gaji 1 sampai 3 bulan.
     - Standar Aturan Kasbon (Sesuai AGENTS.md Bab 3.6):
       * Batas maksimal per pengajuan: Rp 1.000.000 (Kasbon::BATAS_MAKSIMAL).
       * Tenor maksimal: 3 kali potong gajian (Kasbon::TENOR_MAKSIMAL).
       * DILARANG menggunakan istilah "plafon" (gunakan "batas kasbon" / "total pinjaman").
       * Pembagian cicilan: rata, sisa pembulatan rupiah masuk ke angsuran terakhir.
       * Jadwal disimpan di tabel `angsuran_kasbon`; sisa berkurang saat gaji dicairkan (`terpotong`).
     - Data dari Controller:
       * $summary: Total kasbon aktif, sisa pinjaman berjalan, dan estimasi potong gaji bulan ini.
       * $loans: Paginator daftar kasbon dari tabel `kasbon` join supir dan jadwal angsuran.
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');

    $barisDetailKasbon = "[
        { label: 'Supir', nilai: kasbonDipilih.nama },
        { label: 'NIP', nilai: kasbonDipilih.nip },
        { label: 'Tanggal Pengambilan', nilai: kasbonDipilih.tanggal },
        { label: 'Total Pinjaman', nilai: 'Rp ' + Number(kasbonDipilih.total || 0).toLocaleString('id-ID') },
        { label: 'Cicilan per Bulan', nilai: 'Rp ' + Number(kasbonDipilih.angsuran || 0).toLocaleString('id-ID') },
        { label: 'Jumlah Cicilan', nilai: (kasbonDipilih.tenor || 0) + ' kali' },
        { label: 'Sudah Dibayar', nilai: 'Rp ' + Number(kasbonDipilih.terbayar || 0).toLocaleString('id-ID') },
        { label: 'Sisa Pinjaman', nilai: 'Rp ' + Number(kasbonDipilih.sisa || 0).toLocaleString('id-ID') },
        { label: 'Keterangan', nilai: kasbonDipilih.keterangan },
        { label: 'Status', nilai: kasbonDipilih.status_label },
    ]";

    $adaFilterAktif = $filter['cari'] !== ''
        || $filter['supir'] !== 'all'
        || $filter['status'] !== 'all'
        || $filter['tanggal_dari'] !== null
        || $filter['tanggal_sampai'] !== null;
@endphp

<x-app-layout title="Kasbon & Potongan">
    <div class="flex flex-col gap-6" x-data="loansPage()">
        <x-ui.page-header
            title="Kasbon & Potongan Supir"
            breadcrumb="Operasional / Kasbon"
            subtitle="Kasbon adalah pengambilan sebagian gaji di awal, dipotong kembali dari total gaji saat tanggal gajian."
        >
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2.5 font-sans text-body-sm font-semibold text-on-primary shadow-md transition-all hover:bg-primary" @click="bukaTambah()">
                    <span class="material-symbols-outlined text-[1.25rem]">add_circle</span>
                    Ajukan Kasbon Baru
                </button>
            </div>
        </x-ui.page-header>

        {{-- Banner visual + metrik ringkas (sama seperti halaman operasional hauling lain) --}}
        <div class="relative w-full overflow-hidden rounded-2xl bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep p-space-xl text-on-primary shadow-hero">
            <div class="pointer-events-none absolute -right-16 -top-20 h-80 w-80 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>

            <div class="relative z-10 flex flex-col justify-between gap-space-lg lg:flex-row lg:items-center">
                <div class="flex max-w-2xl flex-col gap-1">
                    <div class="mb-1 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 font-sans text-label-sm font-semibold uppercase tracking-wider backdrop-blur-md">
                            <span class="material-symbols-outlined text-[0.875rem]">account_balance_wallet</span>
                            Kasbon &amp; Potongan
                        </span>
                        <span class="font-sans text-label-sm text-white/70">&bull;</span>
                        <span class="font-sans text-label-sm tracking-wide">{{ $summary['active_drivers'] }} supir memiliki kasbon berjalan</span>
                    </div>
                    <h1 class="font-display text-headline-lg font-bold tracking-tight text-white">Kasbon &amp; BPJS Supir</h1>
                    <p class="font-sans text-body-md leading-relaxed text-white/85">
                        Kelola pinjaman awal supir dan potongan BPJS Ketenagakerjaan.
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="font-sans text-[0.625rem] font-bold uppercase tracking-wider text-white/60">Periode Aktif &mdash; {{ $period['code'] ?? '—' }}</span>
                    <div class="flex flex-wrap items-center gap-space-sm">
                        <div class="flex min-w-[8.5rem] flex-col rounded-xl bg-white/10 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Sisa Kasbon</span>
                            <span class="font-display text-headline-md font-bold tabular-nums">{{ $rp($summary['outstanding']) }}</span>
                        </div>
                        <div class="flex min-w-[8.5rem] flex-col rounded-xl bg-white/10 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Kasbon Berjalan</span>
                            <span class="font-display text-headline-md font-bold">{{ $summary['jumlah_kasbon'] }} <span class="font-sans text-label-md font-normal text-white/80">data</span></span>
                        </div>
                        <div class="flex min-w-[9.5rem] flex-col rounded-xl bg-white/15 p-3 shadow-sm backdrop-blur-md">
                            <span class="font-sans text-label-sm uppercase text-white/75">Cicilan Bulan Ini</span>
                            <span class="font-display text-headline-md font-bold tabular-nums">{{ $rp($summary['cicilan_bulan_ini']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 gap-space-md lg:grid-cols-4">
            <x-ui.stat-tile label="Sisa Kasbon Berjalan" :nilai="$rp($summary['outstanding'])" ikon="account_balance_wallet" tone="primary"
                :catatan="$summary['active_drivers'].' supir memiliki kasbon'" />
            <x-ui.stat-tile label="Total Pinjaman" :nilai="$rp($summary['total_pinjaman'])" ikon="payments" tone="slate"
                :catatan="$summary['jumlah_kasbon'].' kasbon tercatat'" />
            <x-ui.stat-tile label="Cicilan Bulan Ini" :nilai="$rp($summary['cicilan_bulan_ini'])" ikon="calendar_month" tone="amber"
                catatan="Jatuh tempo periode aktif" />
            <x-ui.stat-tile label="Batas Maksimal" :nilai="$rp($summary['batas_maksimal'])" ikon="speed" tone="emerald"
                :catatan="'Maksimal '.$summary['tenor_maksimal'].' kali cicilan'" />
        </div>

        {{-- Tabel kasbon & BPJS --}}
        <div class="flex flex-col overflow-hidden rounded-xl bg-surface-container-lowest shadow-card">
            {{-- Subtab: Kasbon | Potongan BPJS --}}
            <div class="flex flex-wrap items-center gap-1 border-b border-card-border bg-canvas-bg px-3 pt-3">
                <button
                    type="button"
                    @click="pilihTab('kasbon')"
                    class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 px-4 py-2.5 font-sans text-body-sm font-semibold transition-colors"
                    :class="tabAktif === 'kasbon' ? 'border-primary bg-card-bg text-primary' : 'border-transparent text-slate-text-secondary hover:text-slate-text-primary'"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">account_balance_wallet</span>
                    Kasbon
                </button>
                <button
                    type="button"
                    @click="pilihTab('bpjs')"
                    class="inline-flex items-center gap-1.5 rounded-t-lg border-b-2 px-4 py-2.5 font-sans text-body-sm font-semibold transition-colors"
                    :class="tabAktif === 'bpjs' ? 'border-primary bg-card-bg text-primary' : 'border-transparent text-slate-text-secondary hover:text-slate-text-primary'"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">health_and_safety</span>
                    Potongan BPJS
                </button>
            </div>

            <div x-show="tabAktif === 'kasbon'" data-wadah-tabel="kasbon" class="flex flex-col">
            <div class="flex flex-col gap-space-md border-b border-card-border p-space-md">
                <div>
                    <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Daftar Kasbon</h2>
                    <p class="font-sans text-body-sm text-slate-text-secondary">
                        Potongan kasbon dihitung dari cicilan yang terjadwal, bukan angka manual.
                    </p>
                </div>

                <div
                    class="flex flex-col items-center justify-between gap-space-sm md:flex-row"
                >
                    <div class="relative w-full md:w-80">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">search</span>
                        <input
                            type="text"
                            name="cari"
                            value="{{ $filter['cari'] }}"
                            class="w-full rounded-lg bg-surface py-2 pl-9 pr-4 font-sans text-body-sm text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-primary-container"
                            placeholder="Cari nama supir, NIP, keterangan..."
                        >
                    </div>

                    <div class="flex w-full flex-wrap items-center gap-space-sm md:w-auto">
                        <select name="supir" class="rounded-lg bg-surface px-3 py-2 font-sans text-body-sm text-slate-text-secondary focus:outline-none focus:ring-2 focus:ring-primary-container">
                            <option value="all">Supir: Semua</option>
                            @foreach ($pilihanSupirKasbon as $opsiSupir)
                                <option value="{{ $opsiSupir['nip'] }}" @selected($filter['supir'] === $opsiSupir['nip'])>{{ $opsiSupir['nama'] }} ({{ $opsiSupir['nip'] }})</option>
                            @endforeach
                        </select>

                        <select name="status" class="rounded-lg bg-surface px-3 py-2 font-sans text-body-sm text-slate-text-secondary focus:outline-none focus:ring-2 focus:ring-primary-container">
                            <option value="all">Status: Semua</option>
                            <option value="approved" @selected($filter['status'] === 'approved')>Status: Berjalan</option>
                            <option value="lunas" @selected($filter['status'] === 'lunas')>Status: Lunas</option>
                            <option value="pending" @selected($filter['status'] === 'pending')>Status: Menunggu</option>
                            <option value="rejected" @selected($filter['status'] === 'rejected')>Status: Ditolak</option>
                        </select>

                        <div class="flex items-center gap-1.5 rounded-lg bg-surface px-2.5 py-1.5 font-sans text-body-sm text-slate-text-secondary">
                            <span class="material-symbols-outlined text-[1rem] text-slate-text-muted">date_range</span>
                            <input
                                type="date"
                                name="tanggal_dari"
                                value="{{ $filter['tanggal_dari'] }}"
                                title="Dari Tanggal Pengajuan"
                                class="bg-transparent font-sans text-body-sm text-slate-text-primary focus:outline-none"
                            >
                            <span class="text-slate-text-muted">s/d</span>
                            <input
                                type="date"
                                name="tanggal_sampai"
                                value="{{ $filter['tanggal_sampai'] }}"
                                title="Sampai Tanggal Pengajuan"
                                class="bg-transparent font-sans text-body-sm text-slate-text-primary focus:outline-none"
                            >
                        </div>

                        <button
                            type="button"
                            data-tombol-reset-filter
                            onclick="window.resetTabelAjax('kasbon')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100 {{ $adaFilterAktif ? '' : 'hidden' }}"
                        >
                            <span class="material-symbols-outlined text-[1rem]">restart_alt</span>
                            <span>Reset Filter</span>
                        </button>
                    </div>
                </div>
            </div>

            <x-ui.tabel-massal
                url="{{ route('admin.operational.loans.status-massal') }}"
                :opsi-status="\App\Models\Kasbon::daftarStatus()"
                label="kasbon"
                keterangan="Pilih status kasbon…"
            >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                            <th class="w-10 px-2 py-3 text-center font-semibold text-slate-text-secondary">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
                                    :checked="semuaTerpilih"
                                    @change="pilihSemuaBaris($event.target.checked)"
                                    aria-label="Pilih semua kasbon"
                                >
                            </th>
                            <th class="w-14 px-4 py-3 text-center font-semibold text-slate-text-secondary">No</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Supir</th>
                            <th class="px-4 py-3 font-semibold text-slate-text-secondary">Tanggal</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Pinjaman</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Cicilan</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Sudah Dibayar</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Sisa</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Status</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyKasbon" class="divide-y divide-card-border font-sans text-financial-table">
                        @foreach ($rows as $indeksBaris => $kasbon)
                            @include('components.tabel-baris.kasbon', ['baris' => $kasbon, 'nomor' => $rows->firstItem() + $indeksBaris])
                        @endforeach

                        @if ($rows->isEmpty())
                            <x-ui.table-empty :colspan="10" label="kasbon supir" :tampilkan-reset="$adaFilterAktif" />
                        @endif
                    </tbody>
                </table>
            </div>
            </x-ui.tabel-massal>

            <x-ui.tabel-ajax entitas="kasbon" label="kasbon" target-id="tbodyKasbon" :kolom="10" :filter="$filter" :per-halaman-awal="$perPage" />
            </div>{{-- /subtab kasbon --}}

            {{-- Subtab Potongan BPJS --}}
            <div
                x-show="tabAktif === 'bpjs'"
                x-cloak
                class="flex flex-col"
                x-data="panelBpjsMassal({ url: @js(route('admin.operational.loans.bpjs-massal')) })"
            >
                <div class="flex flex-col gap-1 border-b border-card-border p-space-md">
                    <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Potongan BPJS Ketenagakerjaan</h2>
                    <p class="font-sans text-body-sm text-slate-text-secondary">
                        Centang supir yang ingin diatur, isi nominal potongan bulanan, lalu simpan.
                        Nominal 0 berarti supir belum terdaftar BPJS.
                    </p>
                </div>

                <div class="flex flex-col gap-2 border-b border-card-border bg-surface-container-low/70 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-2 font-sans text-body-sm text-slate-text-secondary">
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">checklist</span>
                        <span>
                            <strong class="tabular-nums text-slate-text-primary" x-text="terpilih.length"></strong>
                            supir dipilih
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-sans text-label-md font-bold text-slate-text-muted">Rp</span>
                            <input
                                type="text"
                                inputmode="numeric"
                                x-model="nominal"
                                @input="formatNominal()"
                                placeholder="0"
                                aria-label="Nominal potongan BPJS bulanan"
                                class="h-9 w-40 rounded-lg border border-card-border bg-card-bg pl-9 pr-3 text-right font-financial-table text-body-sm font-bold tabular-nums text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                        </div>
                        <button
                            type="button"
                            @click="simpanBpjs()"
                            :disabled="terpilih.length === 0 || sedangSimpan"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary-container px-3.5 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-all hover:bg-primary disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]" x-text="sedangSimpan ? 'progress_activity' : 'health_and_safety'"></span>
                            <span x-text="sedangSimpan ? 'Menyimpan…' : 'Simpan Potongan BPJS'"></span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                                <th class="w-10 px-2 py-3 text-center font-semibold text-slate-text-secondary">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
                                        :checked="semuaTerpilih"
                                        @change="pilihSemua($event.target.checked)"
                                        aria-label="Pilih semua supir"
                                    >
                                </th>
                                <th class="w-14 px-4 py-3 text-center font-semibold text-slate-text-secondary">No</th>
                                <th class="px-4 py-3 font-semibold text-slate-text-secondary">Supir</th>
                                <th class="px-4 py-3 font-semibold text-slate-text-secondary">NIP</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Status Kerja</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-text-secondary">Potongan BPJS</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-text-secondary">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border font-sans text-financial-table">
                            @forelse (($daftarKomponenSupir ?? []) as $komponenSupir)
                                <tr class="transition-colors hover:bg-surface-container-low/60">
                                    <td class="px-2 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            value="{{ $komponenSupir['id'] }}"
                                            x-model="terpilih"
                                            data-centang-bpjs
                                            class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
                                            aria-label="Pilih supir {{ $komponenSupir['nama'] }}"
                                        >
                                    </td>
                                    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-display text-headline-sm font-semibold text-slate-text-primary">{{ $komponenSupir['nama'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-text-secondary">{{ $komponenSupir['nip'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $komponenSupir['status'] === 'aktif' ? 'bg-net-pay-surface text-net-pay-emerald' : 'bg-surface-container text-slate-text-secondary' }}">
                                            {{ ucfirst($komponenSupir['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold tabular-nums text-slate-text-primary">{{ $rp($komponenSupir['bpjs']) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($komponenSupir['bpjs'] > 0)
                                            <span class="inline-flex items-center rounded-full bg-net-pay-surface px-2 py-0.5 font-sans text-label-sm font-bold text-net-pay-emerald">Terdaftar</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-surface-container px-2 py-0.5 font-sans text-label-sm font-bold text-slate-text-secondary">Belum terdaftar</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center font-sans text-body-sm text-slate-text-muted">
                                        Belum ada data supir.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>{{-- /subtab bpjs --}}
        </div>

        {{-- ============ MODAL TAMBAH KASBON ============ --}}
        <x-ui.modal-shell name="modalTambah" title="Ajukan Kasbon Baru" subtitle="Uang diambil di awal, dipotong dari gaji pada tanggal gajian." icon="account_balance_wallet" badge="Formulir Baru" max-width="2xl">
            <form method="POST" action="{{ route('admin.operational.loans.store') }}" class="flex flex-col overflow-hidden">
                @csrf
                <div class="flex max-h-[65vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                    <x-ui.modal-field label="Supir" name="id_karyawan" :required="true" icon="person" :error="$errors->first('id_karyawan')">
                        <select id="id_karyawan" name="id_karyawan" required class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">— Pilih supir —</option>
                            @foreach ($drivers as $supir)
                                <option value="{{ $supir['id'] }}" @selected(old('id_karyawan') == $supir['id'])>{{ $supir['nama'] }} ({{ $supir['nip'] }})</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                    </x-ui.modal-field>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Nominal Kasbon" name="total_pinjaman" :required="true" icon="payments"
                            :hint="'Maksimal '.$rp($summary['batas_maksimal']).'.'" :error="$errors->first('total_pinjaman')">
                            <span class="pointer-events-none absolute left-9 font-sans text-body-md font-semibold text-slate-text-muted">Rp</span>
                            <input type="text" id="total_pinjaman" name="total_pinjaman" value="{{ old('total_pinjaman') }}"
                                inputmode="numeric" required @input="formatRupiahInput($event)" placeholder="1.000.000"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-16 pr-3 text-right font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Jumlah Cicilan" name="jumlah_tenor" :required="true" icon="calendar_month"
                            :hint="'Maksimal '.$summary['tenor_maksimal'].' kali potong.'" :error="$errors->first('jumlah_tenor')">
                            <select id="jumlah_tenor" name="jumlah_tenor" required class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                                @for ($i = 1; $i <= $summary['tenor_maksimal']; $i++)
                                    <option value="{{ $i }}" @selected(old('jumlah_tenor', 1) == $i)>{{ $i }} kali potong</option>
                                @endfor
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Tanggal Pengambilan" name="tanggal_pengambilan" :required="true" icon="event" :error="$errors->first('tanggal_pengambilan')">
                            <input type="date" id="tanggal_pengambilan" name="tanggal_pengambilan" value="{{ old('tanggal_pengambilan', date('Y-m-d')) }}" required
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Periode Mulai Potong" name="id_periode_gaji_mulai" icon="event_repeat" hint="Cicilan pertama dipotong pada periode ini." :error="$errors->first('id_periode_gaji_mulai')">
                            <select id="id_periode_gaji_mulai" name="id_periode_gaji_mulai" class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">— Periode aktif —</option>
                                @foreach ($periodeOptions as $opsi)
                                    <option value="{{ $opsi['kode'] }}">{{ $opsi['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                        </x-ui.modal-field>
                    </div>

                    <x-ui.modal-field label="Keterangan / Keperluan" name="keterangan" icon="notes" meta="(Opsional)" :error="$errors->first('keterangan')">
                        <textarea id="keterangan" name="keterangan" rows="2" maxlength="150" placeholder="Contoh: biaya berobat keluarga"
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">{{ old('keterangan') }}</textarea>
                    </x-ui.modal-field>

                    <div class="flex items-start gap-2 rounded-lg bg-surface-container-low p-space-sm">
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">info</span>
                        <p class="font-sans text-body-sm text-slate-text-secondary">
                            Kasbon dipotong otomatis dari gaji pada tanggal gajian. Cicilan dibagi rata,
                            sisa pembulatan masuk cicilan terakhir.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalTambah = false">Batal</button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md hover:bg-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">check_circle</span> Simpan Kasbon
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL EDIT KASBON ============ --}}
        <x-ui.modal-shell name="modalEdit" title="Ubah Data Kasbon" subtitle="Nominal dan cicilan dikunci agar slip gaji lama tidak berubah." icon="edit" max-width="xl">
            <form method="POST" :action="'{{ url('admin/operational/loans') }}/' + kasbonDipilih.id" class="flex flex-col">
                @csrf
                @method('PATCH')
                <div class="flex flex-col gap-space-md px-space-lg pb-space-md">
                    <div class="rounded-lg bg-surface-container-low px-space-md py-space-sm">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-muted">Supir</span>
                        <div class="font-display text-headline-sm font-bold text-slate-text-primary" x-text="kasbonDipilih.nama"></div>
                        <div class="font-sans text-label-sm text-slate-text-muted" x-text="kasbonDipilih.nip"></div>
                    </div>

                    <x-ui.modal-field label="Tanggal Pengambilan" name="edit_tanggal" :required="true" icon="event">
                        <input type="date" id="edit_tanggal" name="tanggal_pengambilan" :value="kasbonDipilih.tanggal_iso" required
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Status Kasbon" name="edit_status" :required="true" icon="flag">
                        <select id="edit_status" name="status" x-model="kasbonDipilih.status" required class="w-full appearance-none rounded-lg bg-surface-container-low py-2.5 pl-9 pr-9 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="pending">Menunggu Persetujuan</option>
                            <option value="approved">Berjalan</option>
                            <option value="rejected">Ditolak</option>
                            <option value="lunas">Lunas</option>
                        </select>
                        <span class="pointer-events-none absolute right-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">expand_more</span>
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Keterangan / Keperluan" name="edit_keterangan" icon="notes" meta="(Opsional)">
                        <textarea id="edit_keterangan" name="keterangan" rows="2" maxlength="150" x-model="kasbonDipilih.keterangan"
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </x-ui.modal-field>
                </div>
                <div class="flex items-center justify-between bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalEdit = false">Batal</button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md hover:bg-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">save</span> Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL DETAIL KASBON ============ --}}
        <x-ui.modal-view name="modalDetail" title="Detail Kasbon" subtitle="Termasuk jadwal cicilan per periode." icon="account_balance_wallet" :baris="$barisDetailKasbon">
            <div class="mt-space-sm flex flex-col gap-space-sm">
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wide text-slate-text-muted">Jadwal Cicilan</span>
                <template x-for="(jadwal, urutan) in (kasbonDipilih.jadwal || [])" :key="urutan">
                    <div class="flex items-center justify-between rounded-lg bg-surface-container-low px-3 py-2">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 font-sans text-label-sm font-bold text-primary" x-text="jadwal.urutan"></span>
                            <span class="font-sans text-body-sm text-slate-text-secondary" x-text="jadwal.tanggal"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-financial-table text-body-sm font-bold tabular-nums text-slate-text-primary" x-text="'Rp ' + Number(jadwal.nominal).toLocaleString('id-ID')"></span>
                            <span class="rounded-full px-2 py-0.5 font-sans text-label-sm font-bold"
                                :class="jadwal.status === 'terpotong' ? 'bg-net-pay-surface text-net-pay-emerald' : (jadwal.status === 'dibatalkan' ? 'bg-danger-surface text-danger-red' : 'bg-warning-surface text-warning-amber')"
                                x-text="jadwal.status_label"></span>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.modal-view>

        {{-- ============ MODAL TANDAI CICILAN TERPOTONG ============ --}}
        <x-ui.modal-shell name="modalPotong" title="Tandai Cicilan Terpotong" subtitle="Cicilan ini akan mengurangi sisa pinjaman." icon="price_check" max-width="lg" :z-index="60">
            <form method="POST" :action="'{{ url('admin/operational/loans') }}/' + kasbonDipilih.id + '/angsuran/' + (kasbonDipilih.angsuran_berikut?.urutan || 0)" class="flex flex-col">
                @csrf
                @method('PATCH')
                <div class="flex flex-col gap-space-md px-space-lg pb-space-md">
                    <div class="rounded-xl bg-surface-container-low p-space-md">
                        <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-muted">Supir</span>
                        <div class="font-display text-headline-sm font-bold text-slate-text-primary" x-text="kasbonDipilih.nama"></div>
                        <div class="mt-space-sm grid grid-cols-2 gap-space-sm">
                            <div>
                                <span class="font-sans text-label-sm text-slate-text-muted">Cicilan ke</span>
                                <div class="font-financial-table text-body-lg font-bold text-primary" x-text="kasbonDipilih.angsuran_berikut?.urutan"></div>
                            </div>
                            <div>
                                <span class="font-sans text-label-sm text-slate-text-muted">Nominal</span>
                                <div class="font-financial-table text-body-lg font-bold tabular-nums text-slate-text-primary"
                                    x-text="'Rp ' + Number(kasbonDipilih.angsuran_berikut?.nominal || 0).toLocaleString('id-ID')"></div>
                            </div>
                        </div>
                    </div>
                    <p class="font-sans text-body-sm text-slate-text-secondary">
                        Sisa pinjaman setelah cicilan ini:
                        <strong class="text-slate-text-primary"
                            x-text="'Rp ' + Math.max(0, Number(kasbonDipilih.sisa || 0) - Number(kasbonDipilih.angsuran_berikut?.nominal || 0)).toLocaleString('id-ID')"></strong>
                    </p>
                </div>
                <div class="flex items-center justify-between bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalPotong = false">Batal</button>
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-net-pay-emerald px-space-xl py-2.5 font-sans text-body-md font-semibold text-white shadow-md hover:brightness-110">
                        <span class="material-symbols-outlined text-[1.125rem]">check_circle</span> Tandai Terpotong
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL HAPUS KASBON ============ --}}
        <x-ui.modal-delete
            name="modalHapus"
            title="Hapus Data Kasbon?"
            entity="kasbon"
            action-binding="'{{ url('admin/operational/loans') }}/' + kasbonDipilih.id"
            terkunci-binding="(kasbonDipilih.terbayar || 0) > 0"
            alasan-binding="'Kasbon ini sudah memiliki cicilan terpotong sebesar Rp ' + Number(kasbonDipilih.terbayar || 0).toLocaleString('id-ID') + '. Data tidak dapat dihapus.'"
            ringkasan-binding="[
                { label: 'Total Pinjaman', nilai: 'Rp ' + Number(kasbonDipilih.total || 0).toLocaleString('id-ID') },
                { label: 'Sudah Dibayar', nilai: 'Rp ' + Number(kasbonDipilih.terbayar || 0).toLocaleString('id-ID') },
            ]"
            centang="setujuHapus"
        />

        {{-- ============ MODAL CEPAT CATAT PENGELUARAN ============ --}}
        <x-ui.modal-shell
            name="modalPengeluaranTambah"
            title="Catat Pengeluaran"
            subtitle="Pengeluaran operasional armada yang diakumulasikan per periode."
            icon="receipt_long"
            accent="gradient"
            maxWidth="2xl"
            :zIndex="50"
        >
            <form
                method="POST"
                action="{{ route('admin.operational.expenses.store') }}"
                data-form="loans-pengeluaran-tambah"
                novalidate
                @submit="if (!validasiPengeluaranTambah()) $event.preventDefault()"
            >
                @csrf
                <input type="hidden" name="id_periode_gaji" value="{{ $periodeAktifId }}">
                <input type="hidden" name="sumber" value="loans">

                <div class="flex flex-col gap-space-md p-space-lg">
                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Tanggal Pengeluaran" required icon="calendar_today">
                            <input
                                type="date"
                                name="tanggal_biaya"
                                required
                                value="{{ old('tanggal_biaya', now()->toDateString()) }}"
                                :class="errorsPengeluaran.tanggal_biaya ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            >
                            <p x-show="errorsPengeluaran.tanggal_biaya" x-text="errorsPengeluaran.tanggal_biaya" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Kategori" required icon="category">
                            <select
                                name="kategori"
                                required
                                :class="errorsPengeluaran.kategori ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            >
                                <option value="">— Pilih kategori —</option>
                                @foreach ($kategoriOptions as $nilai => $label)
                                    <option value="{{ $nilai }}" @selected(old('kategori') === $nilai)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                            <p x-show="errorsPengeluaran.kategori" x-text="errorsPengeluaran.kategori" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>
                    </div>

                    <x-ui.modal-field label="Unit Armada" required icon="local_shipping">
                        <select
                            name="id_truk"
                            required
                            :class="errorsPengeluaran.id_truk ? 'ring-2 ring-danger-red' : ''"
                            class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >
                            <option value="">— Pilih unit —</option>
                            @foreach ($trucks as $truk)
                                <option value="{{ $truk['id'] }}" @selected(old('id_truk') == $truk['id'])>
                                    {{ $truk['nomor_polisi'] }} — {{ $truk['merek_model'] }}{{ $truk['driver'] ? ' (Supir: '.$truk['driver'].')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                        <p x-show="errorsPengeluaran.id_truk" x-text="errorsPengeluaran.id_truk" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </x-ui.modal-field>

                    <div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
                        <x-ui.modal-field label="Nominal" required icon="payments" hint="Angka bulat Rp 1 s/d Rp 999.999.999">
                            <span class="pointer-events-none absolute left-3 font-sans text-label-md font-bold text-slate-text-muted">Rp</span>
                            <input
                                type="text"
                                name="nominal"
                                required
                                inputmode="numeric"
                                placeholder="0"
                                value="{{ old('nominal') }}"
                                :class="errorsPengeluaran.nominal ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg bg-canvas-bg pl-10 pr-3 font-sans text-financial-table font-bold focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                                x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                            >
                            <p x-show="errorsPengeluaran.nominal" x-text="errorsPengeluaran.nominal" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>

                        <x-ui.modal-field label="Nomor Nota" icon="confirmation_number" hint="Opsional, maks 50 karakter">
                            <input
                                type="text"
                                name="nomor_nota"
                                maxlength="50"
                                placeholder="NTX-00001"
                                value="{{ old('nomor_nota') }}"
                                :class="errorsPengeluaran.nomor_nota ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg bg-canvas-bg pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            >
                            <p x-show="errorsPengeluaran.nomor_nota" x-text="errorsPengeluaran.nomor_nota" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        </x-ui.modal-field>
                    </div>

                    <x-ui.modal-field label="Keterangan" icon="notes" hint="Opsional, maksimal 200 karakter">
                        <textarea
                            name="keterangan"
                            rows="2"
                            maxlength="200"
                            placeholder="Deskripsi singkat pengeluaran..."
                            :class="errorsPengeluaran.keterangan ? 'ring-2 ring-danger-red' : ''"
                            class="w-full rounded-lg bg-canvas-bg py-2 pl-9 pr-3 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >{{ old('keterangan') }}</textarea>
                        <p x-show="errorsPengeluaran.keterangan" x-text="errorsPengeluaran.keterangan" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Status" required icon="verified">
                        <select
                            name="status"
                            required
                            :class="errorsPengeluaran.status ? 'ring-2 ring-danger-red' : ''"
                            class="h-10 w-full appearance-none rounded-lg bg-canvas-bg pl-9 pr-8 font-sans text-body-md focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >
                            @foreach ($statusOptionsBiaya as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('status', 'draft') === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 material-symbols-outlined text-[1.125rem] text-slate-text-secondary">expand_more</span>
                        <p x-show="errorsPengeluaran.status" x-text="errorsPengeluaran.status" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                    </x-ui.modal-field>
                </div>

                <div class="flex items-center justify-between gap-space-sm border-t border-card-border p-space-lg">
                    <a href="{{ route('admin.operational.expenses.index') }}" class="font-sans text-body-sm font-medium text-primary hover:underline">
                        Kelola Semua Pengeluaran &rarr;
                    </a>
                    <div class="flex items-center gap-space-sm">
                        <button type="button" class="h-10 rounded-lg bg-surface-container-low px-space-lg font-sans text-body-md font-medium text-slate-text-primary transition-colors hover:bg-surface-container" @click="modalPengeluaranTambah = false">Batal</button>
                        <button type="submit" class="flex h-10 items-center gap-2 rounded-lg bg-primary-container px-space-xl font-display text-headline-sm text-on-primary shadow-md transition-transform active:scale-95">
                            <span class="material-symbols-outlined text-[1.25rem]">save</span>
                            Simpan Pengeluaran
                        </button>
                    </div>
                </div>
            </form>
        </x-ui.modal-shell>
    </div>
</x-app-layout>
