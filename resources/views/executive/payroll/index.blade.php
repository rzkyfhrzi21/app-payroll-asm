<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OTORISASI PAYROLL & ACC PIMPINAN):
     - Tujuan Halaman: Meja otorisasi sakral Direktur / Pimpinan: memeriksa satu per satu
       hasil hitungan gaji supir yang diajukan Admin HRD, memberikan persetujuan (ACC),
       menolak/mengembalikan revisi dengan catatan, atau mengeksekusi Pencairan Dana (Disbursement).
     - Analogi Dunia Nyata: Seperti map disposisi persetujuan transfer uang di meja Direktur Utama:
       Direktur meneliti apakah hitungan tonase dan komisi supir sudah wajar. Jika setuju,
       Direktur mencap ACC (atau ACC Massal) lalu mengklik "Cairkan Dana" agar uang ditransfer.
     - Standar Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2 & 3.8):
       * HANYA peran `pimpinan` yang boleh menyetujui (`approved`) atau mencairkan (`paid`).
       * Mendukung ACC Bertahap: satuan per supir, massal via centang checkbox, atau ACC Semua.
       * Penolakan/revisi wajib menyertakan alasan tertulis (`catatan_penolakan`).
       * Pencairan dana (`paid`) otomatis memotong angsuran kasbon (`terpotong`) dan melunasi sisa kasbon!
     - Data dari Controller (Executive/PayrollReviewController@index):
       * $periodeTerpilih: Periode yang sedang dibuka dan diperiksa.
       * $summary: Ringkasan total cash requirement (uang kas yang harus disiapkan bank).
       * $drivers: Baris supir dengan status 'draft', 'pending', 'approved', atau 'paid'.
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
@endphp

<x-executive-layout title="Data Penggajian & Otorisasi">
    @if (! $periodeTerpilih)
        {{-- Tahap 1: Pimpinan memilih periode pengajuan lebih dulu --}}
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <span class="font-sans text-label-sm text-slate-text-muted">Dashboard</span>
                    <span class="material-symbols-outlined text-[0.875rem] text-slate-text-muted">chevron_right</span>
                    <span class="font-sans text-label-sm font-semibold uppercase tracking-wide text-banner-indigo">Data Penggajian Pimpinan</span>
                </div>
                <h1 class="font-display text-headline-lg tracking-tight text-slate-text-primary">Pilih Periode Penggajian</h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Klik periode yang ingin ditinjau. Rincian supir serta tombol ACC/revisi muncul setelah periode dipilih.
                </p>
            </div>

            @if ($daftarPeriode === [])
                <div class="rounded-xl bg-card-bg p-10 text-center shadow-card">
                    <span class="material-symbols-outlined text-[2rem] text-slate-text-muted">event_busy</span>
                    <p class="mt-2 font-sans text-body-md text-slate-text-secondary">Belum ada periode penggajian.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($daftarPeriode as $periodeOpsi)
                        <a
                            href="{{ route('executive.payroll.index', ['periode' => $periodeOpsi['kode']]) }}"
                            class="group flex flex-col gap-3 rounded-xl bg-card-bg p-5 shadow-card transition-all hover:-translate-y-0.5 hover:shadow-drawer focus:outline-none focus:ring-2 focus:ring-banner-indigo"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex flex-col">
                                    <span class="font-display text-headline-md font-bold text-slate-text-primary">{{ $periodeOpsi['kode'] }}</span>
                                    <span class="font-sans text-body-sm text-slate-text-secondary">{{ $periodeOpsi['rentang'] }}</span>
                                </div>
                                <span @class([
                                    'shrink-0 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold',
                                    'bg-net-pay-surface text-net-pay-emerald' => in_array($periodeOpsi['status_kode'], ['approved', 'paid'], true),
                                    'bg-warning-surface text-warning-amber' => $periodeOpsi['status_kode'] === 'pending',
                                    'bg-surface-container text-slate-text-secondary' => ! in_array($periodeOpsi['status_kode'], ['approved', 'paid', 'pending'], true),
                                ])>{{ $periodeOpsi['status_label'] }}</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 rounded-lg bg-surface-container-low/60 p-3">
                                <div class="flex flex-col">
                                    <span class="font-sans text-[0.625rem] font-bold uppercase tracking-wide text-slate-text-muted">Total Kas Payroll</span>
                                    <span class="font-financial-table font-bold tabular-nums text-net-pay-emerald">{{ $rp($periodeOpsi['gaji_bersih']) }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-sans text-[0.625rem] font-bold uppercase tracking-wide text-slate-text-muted">Omzet</span>
                                    <span class="font-financial-table font-bold tabular-nums text-slate-text-primary">{{ $rp($periodeOpsi['omzet']) }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-sans text-[0.625rem] font-bold uppercase tracking-wide text-slate-text-muted">Supir / Rit</span>
                                    <span class="font-financial-table font-semibold tabular-nums text-slate-text-primary">{{ $periodeOpsi['jumlah_supir'] }} / {{ $periodeOpsi['rit'] }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-sans text-[0.625rem] font-bold uppercase tracking-wide text-slate-text-muted">Sparepart</span>
                                    <span class="font-financial-table font-semibold tabular-nums text-warning-amber">{{ $rp($periodeOpsi['sparepart']) }}</span>
                                </div>
                            </div>

                            <span class="mt-auto flex items-center gap-1 font-sans text-label-md font-semibold text-banner-indigo group-hover:underline">
                                Tinjau periode ini
                                <span class="material-symbols-outlined text-[1rem]">arrow_forward</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @else
    <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OTORISASI PAYROLL & ACC PIMPINAN):
     - Tujuan Halaman: Meja otorisasi sakral Direktur / Pimpinan: memeriksa satu per satu
       hasil hitungan gaji supir yang diajukan Admin HRD, memberikan persetujuan (ACC),
       menolak/mengembalikan revisi dengan catatan, atau mengeksekusi Pencairan Dana (Disbursement).
     - Analogi Dunia Nyata: Seperti map disposisi persetujuan transfer uang di meja Direktur Utama:
       Direktur meneliti apakah hitungan tonase dan komisi supir sudah wajar. Jika setuju,
       Direktur mencap ACC (atau ACC Massal) lalu mengklik "Cairkan Dana" agar uang ditransfer.
     - Standar Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2 & 3.8):
       * HANYA peran `pimpinan` yang boleh menyetujui (`approved`) atau mencairkan (`paid`).
       * Mendukung ACC Bertahap: satuan per supir, massal via centang checkbox, atau ACC Semua.
       * Penolakan/revisi wajib menyertakan alasan tertulis (`catatan_penolakan`).
       * Pencairan dana (`paid`) otomatis memotong angsuran kasbon (`terpotong`) dan melunasi sisa kasbon!
     - Data dari Controller (Executive/PayrollReviewController@index):
       * $periodeTerpilih: Periode yang sedang dibuka dan diperiksa.
       * $summary: Ringkasan total cash requirement (uang kas yang harus disiapkan bank).
       * $drivers: Baris supir dengan status 'draft', 'pending', 'approved', atau 'paid'.
     ============================================================================== -->
@php
        $catatanPenolakan = $permintaanAcc['catatan_penolakan'] ?? $period['catatan_penolakan'] ?? null;
        $jumlahSudahAcc = (int) ($permintaanAcc['jumlah_sudah_acc'] ?? collect($rows)->whereIn('status_kode', ['approved', 'paid'])->count());
        $jumlahSudahCair = (int) ($permintaanAcc['jumlah_sudah_cair'] ?? collect($rows)->where('status_kode', 'paid')->count());
        $periodeDisetujui = in_array($periodeModel?->status ?? '', ['approved', 'paid'], true) || ($permintaanAcc['sudah_acc'] ?? false);
        $periodePaid = ($periodeModel?->status ?? '') === 'paid';
        $periodeDiajukan = (bool) ($permintaanAcc['ada_pengajuan'] ?? false) || in_array($periodeModel?->status ?? '', ['pending', 'approved'], true);
        $adaBelumAcc = $periodeDiajukan && ! $periodeDisetujui && (count($rows) === 0 || collect($rows)->contains(fn ($baris) => ($baris['status_kode'] ?? 'draft') === 'draft'));
        $adaSiapCair = $periodeDisetujui && ! $periodePaid && (($permintaanAcc['siap_cair'] ?? false) || collect($rows)->contains(fn ($baris) => ($baris['status_kode'] ?? 'draft') === 'approved'));
        $semuaSudahCair = $periodePaid || (count($rows) > 0 && $jumlahSudahCair >= count($rows));
    @endphp
    <div class="flex flex-col gap-5" x-data="executivePayroll(@js($rows), @js($summary), @js($period))">
        {{-- Breadcrumb + status --}}
        <div class="flex flex-col justify-between gap-space-sm md:flex-row md:items-center">
            <div class="flex items-center gap-2">
                <a href="{{ route('executive.payroll.index') }}" class="flex items-center gap-1 font-sans text-label-sm text-slate-text-muted hover:text-banner-indigo">
                    <span class="material-symbols-outlined text-[0.875rem]">arrow_back</span>
                    Pilih Periode
                </a>
                <span class="material-symbols-outlined text-[0.875rem] text-slate-text-muted">chevron_right</span>
                <span class="font-sans text-label-sm font-semibold uppercase tracking-wide text-banner-indigo">Data Penggajian Pimpinan</span>
            </div>
            <div class="flex items-center gap-space-sm self-start md:self-auto">
                @if ($permintaanAcc['sudah_acc'])
                    <span class="flex items-center gap-1.5 rounded-full bg-net-pay-surface px-3 py-1 font-sans text-label-sm font-semibold tracking-wide text-net-pay-emerald">
                        <span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span>
                        Periode {{ $period['code'] }} &bull; {{ $permintaanAcc['status_label'] }}
                    </span>
                @elseif ($permintaanAcc['ada_pengajuan'])
                    <span class="flex items-center gap-1.5 rounded-full bg-warning-surface px-3 py-1 font-sans text-label-sm font-semibold tracking-wide text-warning-amber">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-warning-amber"></span>
                        Periode {{ $period['code'] }} &bull; {{ $permintaanAcc['status_label'] }}
                    </span>
                @else
                    <span class="flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1 font-sans text-label-sm font-semibold tracking-wide text-slate-text-secondary">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                        Periode {{ $period['code'] }} &bull; {{ $permintaanAcc['status_label'] }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Banner + filter --}}
        <div class="relative overflow-hidden rounded-xl bg-card-bg p-space-xl shadow-card">
            <div class="pointer-events-none absolute -right-16 -top-16 h-80 w-80 rounded-full bg-gradient-to-br from-banner-sky/15 via-banner-indigo/10 to-transparent blur-3xl"></div>

            <div class="relative z-10 flex flex-col justify-between gap-space-lg xl:flex-row xl:items-center">
                <div class="flex max-w-3xl flex-col">
                    <div class="mb-1 flex items-center gap-2">
                        <span class="rounded bg-surface-container-high px-2 py-0.5 font-sans text-[0.6875rem] font-bold uppercase tracking-wider text-primary">Direksi Only</span>
                        <span class="font-sans text-body-sm text-slate-text-muted">&bull; Arsip Keuangan Payroll &bull; Otorisasi Penggajian</span>
                    </div>
                    <h1 class="font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">Laporan Rekapitulasi Penggajian &amp; Finansial Pengantaran</h1>
                    <p class="mt-1 font-sans text-body-md leading-relaxed text-slate-text-secondary">
                        Rekapitulasi komprehensif realisasi biaya operasional, bagi hasil komisi supir, beban armada, serta arus kas per periode cut-off.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-space-sm self-start xl:self-auto">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg bg-surface-container-low px-3.5 py-2.5 font-sans text-body-sm font-semibold text-net-pay-emerald shadow-sm transition-all hover:bg-net-pay-surface"
                        onclick="window.print()"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">table_chart</span>
                        Cetak / Ekspor Rekap
                    </button>
                    @if ($adaBelumAcc && ! $semuaSudahCair)
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2.5 font-sans text-body-sm font-semibold text-on-primary shadow-md transition-colors hover:bg-primary"
                            @click="batchOpen = true"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">done_all</span>
                            ACC Semua yang Valid
                        </button>
                    @elseif ($adaSiapCair)
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 font-sans text-body-sm font-bold text-white shadow-md transition-colors hover:bg-sky-700 disabled:opacity-60"
                            :disabled="sedangMemproses"
                            @click="cairkanSemua()"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">payments</span>
                            Cairkan Semua
                        </button>
                    @else
                        <button
                            type="button"
                            disabled
                            readonly
                            class="flex cursor-not-allowed items-center gap-2 rounded-lg bg-net-pay-surface px-4 py-2.5 font-sans text-body-sm font-bold text-net-pay-emerald"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">paid</span>
                            Sudah Dicairkan
                        </button>
                    @endif
                </div>
            </div>

        </div>
        {{-- Ringkasan angka kunci periode terpilih --}}
        <div class="grid grid-cols-1 gap-space-lg sm:grid-cols-2 xl:grid-cols-4">
            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Total Kas Diajukan</span>
                        <span class="mt-1 font-financial-total font-bold tabular-nums text-net-pay-emerald">{{ $rp($permintaanAcc['total_kas']) }}</span>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.375rem]">payments</span>
                    </span>
                </div>
                <div class="mt-space-md rounded-lg bg-surface-container-low/60 p-2 font-sans text-body-sm text-slate-text-secondary">
                    {{ $permintaanAcc['jumlah_supir'] }} supir pada periode {{ $period['code'] }}
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Omzet Angkutan Bersih</span>
                        <span class="mt-1 font-financial-total font-bold tabular-nums text-slate-text-primary">{{ $rp($summary['omzet']) }}</span>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container-high text-banner-indigo">
                        <span class="material-symbols-outlined text-[1.375rem]">trending_up</span>
                    </span>
                </div>
                <div class="mt-space-md rounded-lg bg-surface-container-low/60 p-2 font-sans text-body-sm text-slate-text-secondary">
                    {{ $summary['rit'] }} rit &bull; {{ $summary['tonnage'] }} ton
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Uang Jalan &amp; Sparepart</span>
                        <span class="mt-1 font-financial-total font-bold tabular-nums text-danger-red">{{ $rp($summary['cash_out']) }}</span>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-danger-surface text-danger-red">
                        <span class="material-symbols-outlined text-[1.375rem]">account_balance_wallet</span>
                    </span>
                </div>
                <div class="mt-space-md flex items-center justify-between rounded-lg bg-surface-container-low/60 p-2 font-sans text-body-sm text-slate-text-secondary">
                    <span>U. Jalan: <strong class="text-slate-text-primary">{{ $rp($summary['trip_money']) }}</strong></span>
                    <span>Servis: <strong class="text-warning-amber">{{ $rp($summary['sparepart']) }}</strong></span>
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Total Potongan</span>
                        <span class="mt-1 font-financial-total font-bold tabular-nums text-warning-amber">{{ $rp($summary['deduction']) }}</span>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning-surface text-warning-amber">
                        <span class="material-symbols-outlined text-[1.375rem]">receipt_long</span>
                    </span>
                </div>
                <div class="mt-space-md flex items-center justify-between rounded-lg bg-surface-container-low/60 p-2 font-sans text-body-sm text-slate-text-secondary">
                    <span>BPJS: <strong class="text-slate-text-primary">{{ $rp($summary['bpjs']) }}</strong></span>
                    <span>Kasbon: <strong class="text-slate-text-primary">{{ $rp($summary['kasbon']) }}</strong></span>
                </div>
            </div>
        </div>

        @if (! empty($catatanPenolakan))
            <div class="flex items-start gap-3 rounded-xl border border-danger-red/30 bg-danger-surface px-4 py-3.5 text-danger-red">
                <span class="material-symbols-outlined mt-0.5 text-[1.25rem]">assignment_return</span>
                <div class="flex flex-col gap-0.5">
                    <span class="font-sans text-body-sm font-bold">Catatan Revisi / Penolakan Sebelumnya</span>
                    <p class="font-sans text-body-sm text-slate-text-primary">{{ $catatanPenolakan }}</p>
                </div>
            </div>
        @endif

        {{-- Permintaan ACC dari Admin HRD (sesuai Activity Diagram) --}}
        <div class="overflow-hidden rounded-xl bg-card-bg shadow-card">
            <div class="flex flex-col justify-between gap-3 border-b border-card-border p-space-lg md:flex-row md:items-center">
                <div class="flex flex-col">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Permintaan ACC Penggajian dari Admin HRD</h2>
                        @if ($permintaanAcc['sudah_acc'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-2.5 py-0.5 font-sans text-label-sm font-bold text-net-pay-emerald">
                                <span class="material-symbols-outlined text-[0.875rem]">verified</span>
                                Sudah Di-ACC
                            </span>
                        @elseif ($permintaanAcc['ada_pengajuan'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-0.5 font-sans text-label-sm font-bold text-warning-amber">
                                <span class="material-symbols-outlined text-[0.875rem]">pending_actions</span>
                                Menunggu Keputusan Pimpinan
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-0.5 font-sans text-label-sm font-bold text-slate-text-secondary">
                                <span class="material-symbols-outlined text-[0.875rem]">edit_note</span>
                                Belum Diajukan Admin
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">
                        Periode {{ $permintaanAcc['kode_periode'] }} &bull; {{ $permintaanAcc['rentang'] }}
                        @if ($permintaanAcc['diajukan_pada'])
                            &bull; diperbarui {{ $permintaanAcc['diajukan_pada'] }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($semuaSudahCair)
                        <span class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg bg-net-pay-surface px-3.5 py-2 font-sans text-body-sm font-bold text-net-pay-emerald">
                            <span class="material-symbols-outlined text-[1rem]">paid</span>
                            Sudah Dicairkan ({{ $jumlahSudahCair }}/{{ count($rows) }})
                        </span>
                        <a
                            href="{{ route('executive.reports.index', ['periode' => $period['code']]) }}"
                            class="flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-colors hover:bg-primary"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">description</span>
                            Lihat Laporan Gaji
                        </a>
                    @elseif ($periodeDisetujui)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 border border-sky-200 px-3.5 py-2 font-sans text-body-sm font-bold text-sky-700">
                            <span class="material-symbols-outlined text-[1rem]">verified</span>
                            Sudah Di-ACC Pimpinan
                        </span>
                        @if ($adaSiapCair)
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 font-sans text-body-sm font-bold text-white shadow-sm transition-colors hover:bg-sky-700 disabled:opacity-60"
                                :disabled="sedangMemproses"
                                @click="cairkanSemua()"
                            >
                                <span class="material-symbols-outlined text-[1.125rem]">payments</span>
                                Cairkan Semua
                            </button>
                        @endif
                        <a
                            href="{{ route('executive.reports.index', ['periode' => $period['code']]) }}"
                            class="flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-colors hover:bg-primary"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">description</span>
                            Lihat Laporan Gaji
                        </a>
                    @elseif ($permintaanAcc['ada_pengajuan'])
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg bg-net-pay-emerald px-4 py-2 font-sans text-body-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-emerald-600"
                            @click="batchOpen = true"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">check_circle</span>
                            Setujui (ACC)
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg bg-danger-surface px-4 py-2 font-sans text-body-sm font-bold text-danger-red shadow-sm transition-colors hover:bg-danger-red hover:text-white"
                            @click="openTolak()"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">assignment_return</span>
                            Tolak / Minta Revisi
                        </button>
                    @else
                        {{-- Admin HRD belum mengajukan periode ini: Pimpinan tidak diberi tombol ACC. --}}
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface-container px-3.5 py-2 font-sans text-body-sm font-semibold text-slate-text-secondary">
                            <span class="material-symbols-outlined text-[1rem]">hourglass_top</span>
                            Menunggu Pengajuan Admin HRD
                        </span>
                        <a
                            href="{{ route('executive.payroll.index', ['periode' => $permintaanAcc['kode_periode']]) }}"
                            class="flex items-center gap-2 rounded-lg bg-surface-container-low px-4 py-2 font-sans text-body-sm font-semibold text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">refresh</span>
                            Muat Ulang Status
                        </a>
                    @endif
                </div>
            </div>

            <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OTORISASI PAYROLL & ACC PIMPINAN):
     - Tujuan Halaman: Meja otorisasi sakral Direktur / Pimpinan: memeriksa satu per satu
       hasil hitungan gaji supir yang diajukan Admin HRD, memberikan persetujuan (ACC),
       menolak/mengembalikan revisi dengan catatan, atau mengeksekusi Pencairan Dana (Disbursement).
     - Analogi Dunia Nyata: Seperti map disposisi persetujuan transfer uang di meja Direktur Utama:
       Direktur meneliti apakah hitungan tonase dan komisi supir sudah wajar. Jika setuju,
       Direktur mencap ACC (atau ACC Massal) lalu mengklik "Cairkan Dana" agar uang ditransfer.
     - Standar Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2 & 3.8):
       * HANYA peran `pimpinan` yang boleh menyetujui (`approved`) atau mencairkan (`paid`).
       * Mendukung ACC Bertahap: satuan per supir, massal via centang checkbox, atau ACC Semua.
       * Penolakan/revisi wajib menyertakan alasan tertulis (`catatan_penolakan`).
       * Pencairan dana (`paid`) otomatis memotong angsuran kasbon (`terpotong`) dan melunasi sisa kasbon!
     - Data dari Controller (Executive/PayrollReviewController@index):
       * $periodeTerpilih: Periode yang sedang dibuka dan diperiksa.
       * $summary: Ringkasan total cash requirement (uang kas yang harus disiapkan bank).
       * $drivers: Baris supir dengan status 'draft', 'pending', 'approved', atau 'paid'.
     ============================================================================== -->
@php
                $adaBarisPengajuan = ($permintaanAcc['jumlah_supir'] ?? 0) > 0;
                $totalKomisiPengajuan = (int) ($permintaanAcc['komisi'] ?? 0);
                $totalTunjanganPengajuan = (int) ($permintaanAcc['tunjangan'] ?? 0);
                $totalBpjsPengajuan = (int) ($permintaanAcc['bpjs'] ?? 0);
                $totalKasbonPengajuan = (int) ($permintaanAcc['kasbon'] ?? 0);
                $totalPotonganHistorisPengajuan = (int) ($permintaanAcc['potongan_historis'] ?? 0);
                $totalPenambahanHistorisPengajuan = (int) ($permintaanAcc['penambahan_historis'] ?? 0);
                $totalPotonganWajibPengajuan = $adaBarisPengajuan
                    ? ($totalBpjsPengajuan + $totalKasbonPengajuan)
                    : (int) ($permintaanAcc['total_potongan'] ?? 0);
            @endphp
            <div class="grid grid-cols-1 gap-0 divide-y divide-card-border lg:grid-cols-3 lg:divide-x lg:divide-y-0">
                <div class="flex flex-col gap-2 p-space-lg">
                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">Ringkasan Pengajuan</span>
                    <div class="flex items-center justify-between font-sans text-body-sm">
                        <span class="text-slate-text-secondary">Supir</span>
                        <span class="font-bold tabular-nums text-slate-text-primary">{{ $permintaanAcc['jumlah_supir'] }}</span>
                    </div>
                    <div class="flex items-center justify-between font-sans text-body-sm">
                        <span class="text-slate-text-secondary">Ritase / Tonase</span>
                        <span class="font-bold tabular-nums text-slate-text-primary">{{ $permintaanAcc['jumlah_rit'] }} rit &bull; {{ $permintaanAcc['tonase'] }} T</span>
                    </div>
                    <div class="flex items-center justify-between font-sans text-body-sm">
                        <span class="text-slate-text-secondary">Total Komisi Supir</span>
                        <span class="font-bold tabular-nums text-slate-text-primary">{{ $rp($totalKomisiPengajuan) }}</span>
                    </div>
                    @if ($totalTunjanganPengajuan > 0)
                        <div class="flex items-center justify-between font-sans text-body-sm">
                            <span class="text-slate-text-secondary">Total Tunjangan</span>
                            <span class="font-bold tabular-nums text-net-pay-emerald">+{{ $rp($totalTunjanganPengajuan) }}</span>
                        </div>
                    @endif
                    @if ($totalPenambahanHistorisPengajuan > 0)
                        <div class="flex items-center justify-between font-sans text-body-sm">
                            <span class="text-slate-text-secondary">Penambahan Historis</span>
                            <span class="font-bold tabular-nums text-net-pay-emerald">+{{ $rp($totalPenambahanHistorisPengajuan) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between font-sans text-body-sm">
                        <span class="text-slate-text-secondary">Total Potongan (BPJS &amp; Kasbon)</span>
                        <span class="font-bold tabular-nums text-danger-red">-{{ $rp($totalPotonganWajibPengajuan) }}</span>
                    </div>
                    @if ($totalPotonganHistorisPengajuan > 0)
                        <div class="flex items-center justify-between font-sans text-body-sm">
                            <span class="text-slate-text-secondary">Penyesuaian Historis</span>
                            <span class="font-bold tabular-nums text-danger-red">-{{ $rp($totalPotonganHistorisPengajuan) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-card-border pt-2 font-sans text-body-sm">
                        <span class="font-semibold text-slate-text-primary">Total Kas Diajukan</span>
                        <span class="font-bold tabular-nums text-net-pay-emerald">{{ $rp($permintaanAcc['total_kas']) }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5 p-space-lg lg:col-span-2">
                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">Yang Wajib Diperiksa Sebelum ACC</span>
                    <ul class="flex flex-col gap-1.5">
                        @foreach ($permintaanAcc['pemeriksaan'] as $poin)
                            <li class="flex items-start gap-2 rounded-lg {{ $poin['lolos'] ? 'bg-surface-container-low/60' : 'bg-warning-surface/60' }} px-2.5 py-1.5">
                                <span class="material-symbols-outlined mt-0.5 shrink-0 text-[1rem] {{ $poin['lolos'] ? 'text-net-pay-emerald' : 'text-warning-amber' }}">
                                    {{ $poin['lolos'] ? 'check_circle' : 'error' }}
                                </span>
                                <span class="flex min-w-0 flex-1 flex-col">
                                    <span class="font-sans text-body-sm font-semibold text-slate-text-primary">{{ $poin['label'] }}</span>
                                    <span class="font-sans text-[0.6875rem] text-slate-text-secondary">{{ $poin['keterangan'] }}</span>
                                </span>
                                <span class="shrink-0 font-financial-table font-bold tabular-nums text-slate-text-primary">{{ $rp($poin['nilai']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

        </div>


        {{-- Card Filter Supir Tersendiri Di Atas Tabel --}}
        <div class="rounded-xl bg-card-bg p-space-md shadow-card">
            <div class="flex flex-wrap items-center gap-space-md">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[1.25rem] text-slate-text-secondary">filter_alt</span>
                    <span class="font-sans text-label-md font-bold uppercase tracking-wider text-slate-text-primary">Filter Supir:</span>
                </div>

                <div class="min-w-[15rem] flex-1">
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">search</span>
                        <input
                            type="text"
                            x-model="search"
                            class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-3 font-sans text-body-sm text-slate-text-primary placeholder:text-slate-text-muted shadow-sm focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Cari Supir / NIP / Plat (mis. AGUS, BE 8639 AAU)"
                        >
                    </div>
                </div>

                <div class="min-w-[14rem] flex-1">
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">local_shipping</span>
                        <select x-model="category" class="h-10 w-full appearance-none rounded-lg bg-surface-container-low pl-9 pr-8 font-sans text-body-sm font-medium text-slate-text-primary shadow-sm focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="all">Semua Supir ({{ count($rows) }})</option>
                            <option value="ready">Siap Otorisasi ({{ $summary['audit_ready'] }})</option>
                        </select>
                        <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-[1.125rem] text-slate-text-muted">arrow_drop_down</span>
                    </div>
                </div>

                <button
                    type="button"
                    x-show="search.trim() !== '' || category !== 'all'"
                    x-cloak
                    class="flex h-10 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3.5 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100"
                    @click="resetFilter()"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">restart_alt</span>
                    Reset
                </button>
            </div>
        </div>

        {{-- Tabel review --}}
        <div class="flex flex-col overflow-hidden rounded-xl bg-card-bg shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-card-border p-3.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 font-sans text-label-sm font-semibold uppercase text-slate-text-secondary">Kategori Unit:</span>
                    <button type="button" class="rounded-lg px-3 py-1 font-sans text-label-md font-semibold" :class="category === 'all' ? 'bg-surface-container text-slate-text-primary' : 'text-slate-text-secondary hover:bg-surface-container'" @click="category = 'all'">Semua Supir ({{ count($rows) }})</button>
                    <button type="button" class="rounded-lg px-3 py-1 font-sans text-label-md font-medium" :class="category === 'ready' ? 'bg-surface-container text-slate-text-primary font-semibold' : 'text-slate-text-secondary hover:bg-surface-container'" @click="category = 'ready'">Siap Otorisasi ({{ $summary['audit_ready'] }})</button>
                </div>

                <div class="flex flex-wrap items-center gap-2" x-show="selectedIds.length > 0" x-cloak>
                    <div class="flex items-center gap-1.5 font-sans text-body-sm text-slate-text-secondary">
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">checklist</span>
                        <span><strong class="tabular-nums text-slate-text-primary" x-text="selectedIds.length"></strong> supir dipilih</span>
                    </div>

                    <div class="relative">
                        <select
                            x-model="aksiMassalTerpilih"
                            class="h-9 min-w-[12rem] cursor-pointer appearance-none rounded-lg border border-card-border bg-card-bg pl-3 pr-9 font-sans text-body-sm font-medium text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                            aria-label="Pilih status untuk baris terpilih"
                        >
                            <option value="">Pilih status terpilih…</option>
                            <option value="acc" :disabled="selectedBelumAccIds.length === 0">Setujui (ACC) Terpilih</option>
                            <option value="cairkan" :disabled="selectedSiapCairIds.length === 0">Cairkan Dana Terpilih</option>
                        </select>
                        <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-slate-text-muted">
                            <span class="material-symbols-outlined text-[1.125rem]">expand_more</span>
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="eksekusiAksiMassal()"
                        :disabled="aksiMassalTerpilih === '' || sedangMemproses"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary-container px-3.5 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-all hover:bg-primary disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]" x-text="sedangMemproses ? 'progress_activity' : 'done_all'"></span>
                        <span x-text="sedangMemproses ? 'Memproses…' : 'Terapkan'"></span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left font-financial-table">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                            <th class="w-10 px-4 py-3 text-center">
                                <input type="checkbox" class="h-4 w-4 cursor-pointer rounded text-sidebar-active focus:ring-0" :checked="semuaTerpilih" @change="toggleAll($event.target.checked)">
                            </th>
                            <th class="w-14 px-4 py-3 text-center">No</th>
                            <th class="px-4 py-3">Supir &amp; Plat Truk</th>
                            <th class="px-4 py-3 text-right">Rit / Tonase</th>
                            <th class="px-4 py-3 text-right">Omzet Angkutan</th>
                            <th class="px-4 py-3 text-right">Uang Jalan &amp; Servis</th>
                            <th class="px-4 py-3 text-right">Komisi Supir</th>
                            <th class="px-4 py-3 text-right">Kasbon / BPJS</th>
                            <th class="px-4 py-3 text-right">Gaji Bersih (Net Pay)</th>
                            <th class="px-4 py-3 text-center">Status Review</th>
                            <th class="px-4 py-3 text-center">Aksi Direksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-surface-container-low">
                        @foreach ($rows as $index => $row)
                            <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OTORISASI PAYROLL & ACC PIMPINAN):
     - Tujuan Halaman: Meja otorisasi sakral Direktur / Pimpinan: memeriksa satu per satu
       hasil hitungan gaji supir yang diajukan Admin HRD, memberikan persetujuan (ACC),
       menolak/mengembalikan revisi dengan catatan, atau mengeksekusi Pencairan Dana (Disbursement).
     - Analogi Dunia Nyata: Seperti map disposisi persetujuan transfer uang di meja Direktur Utama:
       Direktur meneliti apakah hitungan tonase dan komisi supir sudah wajar. Jika setuju,
       Direktur mencap ACC (atau ACC Massal) lalu mengklik "Cairkan Dana" agar uang ditransfer.
     - Standar Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2 & 3.8):
       * HANYA peran `pimpinan` yang boleh menyetujui (`approved`) atau mencairkan (`paid`).
       * Mendukung ACC Bertahap: satuan per supir, massal via centang checkbox, atau ACC Semua.
       * Penolakan/revisi wajib menyertakan alasan tertulis (`catatan_penolakan`).
       * Pencairan dana (`paid`) otomatis memotong angsuran kasbon (`terpotong`) dan melunasi sisa kasbon!
     - Data dari Controller (Executive/PayrollReviewController@index):
       * $periodeTerpilih: Periode yang sedang dibuka dan diperiksa.
       * $summary: Ringkasan total cash requirement (uang kas yang harus disiapkan bank).
       * $drivers: Baris supir dengan status 'draft', 'pending', 'approved', atau 'paid'.
     ============================================================================== -->
@php
                                $idPenggajianBaris = $row['id'] ?? null;
                                $statusKodeBaris = $row['status_kode'] ?? 'draft';
                            @endphp
                            <tr
                                class="transition-colors {{ $row['anomaly'] ? 'bg-warning-surface/30 hover:bg-warning-surface/50' : 'hover:bg-surface-container-low/50' }}"
                                x-show="rowVisible(@js($row['name']), @js($row['nip']), @js($row['plate']), '', {{ $row['anomaly'] ? 'true' : 'false' }})"
                            >
                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded text-sidebar-active focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40"
                                        x-model="selected"
                                        value="{{ $idPenggajianBaris ?? '' }}"
                                        @disabled($statusKodeBaris === 'paid' || empty($idPenggajianBaris))
                                    >
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div @class([
                                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-sans text-xs font-bold',
                                            'bg-warning-surface text-warning-amber' => $row['anomaly'],
                                            'bg-surface-container-high text-primary' => ! $row['anomaly'],
                                        ])>
                                            <span class="font-sans text-xs font-bold text-primary">{{ $row['initial'] }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="flex items-center gap-1.5 font-bold text-slate-text-primary">
                                                {{ $row['name'] }}
                                                @if ($row['anomaly'])
                                                    <span class="material-symbols-outlined text-[0.9375rem] text-warning-amber">warning</span>
                                                @endif
                                            </span>
                                            <span class="font-sans text-[0.6875rem] text-slate-text-secondary">{{ $row['plate'] }} &bull; {{ $row['truck'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    <div class="font-bold">{{ $row['rit'] }} Rit</div>
                                    <div class="font-sans text-[0.6875rem] font-normal text-slate-text-secondary">{{ $row['tonnage'] }} Ton</div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">
                                    {{ $rp($row['omzet']) }}
                                    @if (($row['potongan_operasional'] ?? 0) > 0)
                                        <div class="font-sans text-[0.6875rem] font-normal text-slate-text-muted">Bruto {{ $rp($row['omzet_kotor'] ?? $row['omzet']) }} (-Op {{ $rp($row['potongan_operasional']) }})</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    <div class="text-slate-text-secondary">{{ $rp($row['trip_money']) }}</div>
                                    <div class="font-sans text-[0.6875rem] {{ $row['anomaly'] ? 'font-semibold text-danger-red' : 'text-slate-text-muted' }}">Sparepart {{ $rp($row['sparepart']) }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">
                                    {{ $rp($row['commission']) }}
                                    @if (($row['tunjangan'] ?? 0) > 0)
                                        <div class="font-sans text-[0.6875rem] font-medium text-net-pay-emerald">+ Tunjangan {{ $rp($row['tunjangan']) }}</div>
                                    @endif
                                </td>
                                <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OTORISASI PAYROLL & ACC PIMPINAN):
     - Tujuan Halaman: Meja otorisasi sakral Direktur / Pimpinan: memeriksa satu per satu
       hasil hitungan gaji supir yang diajukan Admin HRD, memberikan persetujuan (ACC),
       menolak/mengembalikan revisi dengan catatan, atau mengeksekusi Pencairan Dana (Disbursement).
     - Analogi Dunia Nyata: Seperti map disposisi persetujuan transfer uang di meja Direktur Utama:
       Direktur meneliti apakah hitungan tonase dan komisi supir sudah wajar. Jika setuju,
       Direktur mencap ACC (atau ACC Massal) lalu mengklik "Cairkan Dana" agar uang ditransfer.
     - Standar Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2 & 3.8):
       * HANYA peran `pimpinan` yang boleh menyetujui (`approved`) atau mencairkan (`paid`).
       * Mendukung ACC Bertahap: satuan per supir, massal via centang checkbox, atau ACC Semua.
       * Penolakan/revisi wajib menyertakan alasan tertulis (`catatan_penolakan`).
       * Pencairan dana (`paid`) otomatis memotong angsuran kasbon (`terpotong`) dan melunasi sisa kasbon!
     - Data dari Controller (Executive/PayrollReviewController@index):
       * $periodeTerpilih: Periode yang sedang dibuka dan diperiksa.
       * $summary: Ringkasan total cash requirement (uang kas yang harus disiapkan bank).
       * $drivers: Baris supir dengan status 'draft', 'pending', 'approved', atau 'paid'.
     ============================================================================== -->
@php
                                    $potonganHistorisBaris = (int) ($row['potongan_historis'] ?? 0);
                                    $penambahanHistorisBaris = (int) ($row['penambahan_historis'] ?? 0);
                                    $totalPotonganBaris = (int) ($row['bpjs'] ?? 0) + (int) ($row['kasbon'] ?? 0) + $potonganHistorisBaris;
                                @endphp
                                <td class="px-4 py-3 text-right tabular-nums text-danger-red">
                                    -{{ $rp($totalPotonganBaris) }}
                                    <div class="font-sans text-[0.6875rem] font-normal text-slate-text-muted">
                                        BPJS {{ $rp($row['bpjs'] ?? 0) }} + Kasbon {{ $rp($row['kasbon'] ?? 0) }}@if ($potonganHistorisBaris > 0) + Hist {{ $rp($potonganHistorisBaris) }}@endif
                                    </div>
                                    @if ($penambahanHistorisBaris > 0)
                                        <div class="font-sans text-[0.6875rem] font-semibold text-net-pay-emerald">+ Penyesuaian {{ $rp($penambahanHistorisBaris) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    <span class="inline-block rounded bg-net-pay-surface px-2 py-0.5 font-bold text-net-pay-emerald">{{ $rp($row['net_pay']) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OTORISASI PAYROLL & ACC PIMPINAN):
     - Tujuan Halaman: Meja otorisasi sakral Direktur / Pimpinan: memeriksa satu per satu
       hasil hitungan gaji supir yang diajukan Admin HRD, memberikan persetujuan (ACC),
       menolak/mengembalikan revisi dengan catatan, atau mengeksekusi Pencairan Dana (Disbursement).
     - Analogi Dunia Nyata: Seperti map disposisi persetujuan transfer uang di meja Direktur Utama:
       Direktur meneliti apakah hitungan tonase dan komisi supir sudah wajar. Jika setuju,
       Direktur mencap ACC (atau ACC Massal) lalu mengklik "Cairkan Dana" agar uang ditransfer.
     - Standar Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2 & 3.8):
       * HANYA peran `pimpinan` yang boleh menyetujui (`approved`) atau mencairkan (`paid`).
       * Mendukung ACC Bertahap: satuan per supir, massal via centang checkbox, atau ACC Semua.
       * Penolakan/revisi wajib menyertakan alasan tertulis (`catatan_penolakan`).
       * Pencairan dana (`paid`) otomatis memotong angsuran kasbon (`terpotong`) dan melunasi sisa kasbon!
     - Data dari Controller (Executive/PayrollReviewController@index):
       * $periodeTerpilih: Periode yang sedang dibuka dan diperiksa.
       * $summary: Ringkasan total cash requirement (uang kas yang harus disiapkan bank).
       * $drivers: Baris supir dengan status 'draft', 'pending', 'approved', atau 'paid'.
     ============================================================================== -->
@php
                                        $labelStatusBaris = match ($statusKodeBaris) {
                                            'paid' => 'Sudah Dicairkan',
                                            'approved' => 'Disetujui (Siap Cair)',
                                            default => $row['status'] ?? 'Menunggu ACC',
                                        };
                                    @endphp
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-[0.6875rem] font-bold',
                                        'bg-warning-surface text-warning-amber' => $statusKodeBaris === 'draft',
                                        'bg-sky-50 text-sky-700' => $statusKodeBaris === 'approved',
                                        'bg-net-pay-surface text-net-pay-emerald' => $statusKodeBaris === 'paid',
                                    ])>
                                        <span @class([
                                            'h-1.5 w-1.5 rounded-full',
                                            'bg-warning-amber' => $statusKodeBaris === 'draft',
                                            'bg-sky-500' => $statusKodeBaris === 'approved',
                                            'bg-net-pay-emerald' => $statusKodeBaris === 'paid',
                                        ])></span>
                                        {{ $labelStatusBaris }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            class="rounded bg-surface-container-low px-2.5 py-1 font-sans text-[0.6875rem] font-semibold text-slate-text-primary transition-colors hover:bg-surface-container-high"
                                            @click="openDetail(@js($row))"
                                        >
                                            Review
                                        </button>
                                        @if ($statusKodeBaris === 'draft' && $periodeDiajukan)
                                            <button
                                                type="button"
                                                class="rounded bg-net-pay-emerald px-2.5 py-1 font-sans text-[0.6875rem] font-bold text-on-primary transition-colors hover:bg-emerald-600 disabled:opacity-60"
                                                :disabled="sedangMemproses"
                                                @click="confirmAcc(@js($idPenggajianBaris), @js($row['name']))"
                                            >
                                                ACC
                                            </button>
                                        @elseif ($statusKodeBaris === 'draft')
                                            <span class="rounded bg-surface-container px-2 py-1 font-sans text-[0.6875rem] font-semibold text-slate-text-secondary" title="Admin HRD belum mengajukan periode ini">Belum Diajukan</span>
                                        @elseif ($statusKodeBaris === 'approved')
                                            <button
                                                type="button"
                                                class="rounded bg-sky-600 px-2.5 py-1 font-sans text-[0.6875rem] font-bold text-white transition-colors hover:bg-sky-700 disabled:opacity-60"
                                                :disabled="sedangMemproses"
                                                @click="cairkanSatuan(@js($idPenggajianBaris))"
                                            >
                                                Cairkan
                                            </button>
                                        @else
                                            <button type="button" disabled readonly class="cursor-not-allowed rounded bg-net-pay-surface px-2 py-1 font-sans text-[0.6875rem] font-semibold text-net-pay-emerald">
                                                Sudah Dicairkan
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OTORISASI PAYROLL & ACC PIMPINAN):
     - Tujuan Halaman: Meja otorisasi sakral Direktur / Pimpinan: memeriksa satu per satu
       hasil hitungan gaji supir yang diajukan Admin HRD, memberikan persetujuan (ACC),
       menolak/mengembalikan revisi dengan catatan, atau mengeksekusi Pencairan Dana (Disbursement).
     - Analogi Dunia Nyata: Seperti map disposisi persetujuan transfer uang di meja Direktur Utama:
       Direktur meneliti apakah hitungan tonase dan komisi supir sudah wajar. Jika setuju,
       Direktur mencap ACC (atau ACC Massal) lalu mengklik "Cairkan Dana" agar uang ditransfer.
     - Standar Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2 & 3.8):
       * HANYA peran `pimpinan` yang boleh menyetujui (`approved`) atau mencairkan (`paid`).
       * Mendukung ACC Bertahap: satuan per supir, massal via centang checkbox, atau ACC Semua.
       * Penolakan/revisi wajib menyertakan alasan tertulis (`catatan_penolakan`).
       * Pencairan dana (`paid`) otomatis memotong angsuran kasbon (`terpotong`) dan melunasi sisa kasbon!
     - Data dari Controller (Executive/PayrollReviewController@index):
       * $periodeTerpilih: Periode yang sedang dibuka dan diperiksa.
       * $summary: Ringkasan total cash requirement (uang kas yang harus disiapkan bank).
       * $drivers: Baris supir dengan status 'draft', 'pending', 'approved', atau 'paid'.
     ============================================================================== -->
@php
                            $totalOmzetKotorTabel = (int) collect($rows)->sum(fn ($baris) => (int) ($baris['omzet_kotor'] ?? $baris['omzet'] ?? 0));
                            $totalPotonganOperasionalTabel = (int) collect($rows)->sum('potongan_operasional');
                            $totalSparepartTabel = (int) collect($rows)->sum('sparepart');
                            $totalTunjanganTabel = (int) collect($rows)->sum('tunjangan');
                            $totalBpjsTabel = (int) collect($rows)->sum('bpjs');
                            $totalKasbonTabel = (int) collect($rows)->sum('kasbon');
                            $totalPotonganHistorisTabel = (int) collect($rows)->sum('potongan_historis');
                            $totalPenambahanHistorisTabel = (int) collect($rows)->sum('penambahan_historis');
                            $totalPotonganTabel = $totalBpjsTabel + $totalKasbonTabel + $totalPotonganHistorisTabel;
                        @endphp
                        <tr class="bg-surface-container font-financial-table font-bold text-slate-text-primary">
                            <td class="px-4 py-3" colspan="2">TOTAL {{ count($rows) }} SUPIR</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ collect($rows)->sum('rit') }} Rit</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $rp(collect($rows)->sum('omzet')) }}
                                @if ($totalPotonganOperasionalTabel > 0)
                                    <div class="font-sans text-[0.6875rem] font-normal text-slate-text-muted">Bruto {{ $rp($totalOmzetKotorTabel) }} (-Op {{ $rp($totalPotonganOperasionalTabel) }})</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $rp(collect($rows)->sum('trip_money')) }}
                                <div class="font-sans text-[0.6875rem] font-normal text-slate-text-muted">Sparepart {{ $rp($totalSparepartTabel) }}</div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ $rp(collect($rows)->sum('commission')) }}
                                @if ($totalTunjanganTabel > 0)
                                    <div class="font-sans text-[0.6875rem] font-semibold text-net-pay-emerald">+ Tunjangan {{ $rp($totalTunjanganTabel) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-danger-red">
                                -{{ $rp($totalPotonganTabel) }}
                                <div class="font-sans text-[0.6875rem] font-normal text-slate-text-muted">
                                    BPJS {{ $rp($totalBpjsTabel) }} + Kasbon {{ $rp($totalKasbonTabel) }}@if ($totalPotonganHistorisTabel > 0) + Hist {{ $rp($totalPotonganHistorisTabel) }}@endif
                                </div>
                                @if ($totalPenambahanHistorisTabel > 0)
                                    <div class="font-sans text-[0.6875rem] font-semibold text-net-pay-emerald">+ Penyesuaian {{ $rp($totalPenambahanHistorisTabel) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-net-pay-emerald">{{ $rp(collect($rows)->sum('net_pay')) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-sans text-xs text-primary">{{ $permintaanAcc['status_label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($adaBelumAcc && ! $semuaSudahCair)
                                    <button type="button" class="w-full rounded bg-net-pay-emerald px-3 py-1.5 font-sans text-label-md font-bold uppercase tracking-wider text-on-primary shadow-sm transition-all hover:bg-emerald-600" @click="batchOpen = true">ACC Semua</button>
                                @elseif ($adaSiapCair)
                                    <button type="button" class="w-full rounded bg-sky-600 px-3 py-1.5 font-sans text-label-md font-bold uppercase tracking-wider text-white shadow-sm transition-all hover:bg-sky-700 disabled:opacity-60" :disabled="sedangMemproses" @click="cairkanSemua()">Cairkan Semua</button>
                                @else
                                    <button type="button" disabled readonly class="w-full cursor-not-allowed rounded bg-net-pay-surface px-3 py-1.5 font-sans text-[0.6875rem] font-semibold text-net-pay-emerald">Sudah Dicairkan</button>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex flex-col items-center justify-between gap-2 bg-surface-container-low p-3.5 sm:flex-row">
                <div class="flex items-center gap-2 font-sans text-body-sm text-slate-text-secondary">
                    <span>Menampilkan <span class="font-semibold text-slate-text-primary">{{ count($rows) }}</span> Supir pada periode {{ $period['code'] }}</span>
                    <span class="text-slate-text-muted">&bull;</span>
                    <span class="font-sans text-label-sm font-semibold text-sidebar-active"><span x-text="selected.length"></span> Supir Terpilih dalam Mode Audit Detail</span>
                </div>
        </div>

        {{-- Modal review perhitungan --}}
        <div x-show="detailOpen" x-cloak @keydown.escape.window="detailOpen = false" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 sm:p-6" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-sidebar-dark/70 backdrop-blur-md" @click="detailOpen = false"></div>

            <div class="relative z-10 my-auto flex w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-card-bg shadow-drawer">
                {{-- Header --}}
                <div class="relative flex flex-col gap-2 bg-surface-container-low p-space-xl pb-space-md">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-banner-indigo text-on-primary shadow-md">
                                <span class="material-symbols-outlined text-[1.375rem]">calculate</span>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-display text-headline-md font-bold tracking-tight text-slate-text-primary">Review Perhitungan Gaji</h2>
                                    <span class="flex items-center gap-1 rounded bg-warning-surface px-2 py-0.5 font-sans text-[0.6875rem] font-bold uppercase tracking-wider text-warning-amber">
                                        <span class="material-symbols-outlined text-[0.8125rem]">verified_user</span> Otorisasi Pimpinan
                                    </span>
                                </div>
                                <p class="font-sans text-body-sm text-slate-text-secondary">Perhitungan gaji berdasarkan surat jalan pengantaran, uang jalan, dan beban servis armada.</p>
                            </div>
                        </div>
                        <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-container text-slate-text-secondary transition-colors hover:bg-danger-surface hover:text-danger-red" @click="detailOpen = false">
                            <span class="material-symbols-outlined text-[1.25rem]">close</span>
                        </button>
                    </div>

                    <template x-if="detail">
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-card-bg p-3 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container-high font-sans text-sm font-bold text-primary">
                                    <span class="font-sans text-xs font-bold text-primary" x-text="detail.initial"></span>
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-display text-headline-sm font-bold text-slate-text-primary" x-text="detail.name"></span>
                                        <span class="rounded bg-surface-container px-2 py-0.5 font-sans text-label-sm font-semibold text-slate-text-secondary" x-text="'NIP: ' + detail.nip"></span>
                                    </div>
                                    <span class="font-sans text-body-sm text-slate-text-muted" x-text="'Unit Armada: ' + detail.plate + ' (' + detail.truck + ')'"></span>
                                </div>
                            </div>
                            <div class="flex flex-col items-start sm:items-end">
                                <span class="font-sans text-label-sm uppercase">Rekening Payroll Supir</span>
                                <div class="mt-0.5 flex items-center gap-1.5 font-financial-table text-body-sm font-bold text-slate-text-primary">
                                    <span class="material-symbols-outlined text-[1rem] text-net-pay-emerald">account_balance</span>
                                    <span class="font-sans text-[0.6875rem] text-slate-text-secondary" x-text="detail.bank + ' ' + detail.account"></span>
                                    <span class="rounded bg-net-pay-surface px-1.5 font-sans text-[0.625rem] uppercase text-net-pay-emerald">Terverifikasi</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <button type="button" class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-sans text-label-md font-semibold shadow-sm" :class="detailTab === 'formula' ? 'bg-sidebar-active text-on-primary' : 'bg-card-bg text-slate-text-secondary hover:bg-surface-container'" @click="detailTab = 'formula'">
                            <span class="material-symbols-outlined text-[1rem]">functions</span>
                            Ringkasan Formula Komisi
                        </button>
                        <button type="button" class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-sans text-label-md font-semibold" :class="detailTab === 'trips' ? 'bg-sidebar-active text-on-primary' : 'bg-card-bg text-slate-text-secondary hover:bg-surface-container'" @click="detailTab = 'trips'">
                            <span class="material-symbols-outlined text-[1rem]">history</span>
                            Log Pengantaran Harian
                        </button>
                        <button type="button" class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-sans text-label-md font-semibold" :class="detailTab === 'sparepart' ? 'bg-sidebar-active text-on-primary' : 'bg-card-bg text-danger-red hover:bg-danger-surface'" @click="detailTab = 'sparepart'">
                            <span class="material-symbols-outlined text-[1rem]">build</span>
                            Beban Bengkel
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="flex max-h-[38.375rem] flex-col gap-space-md overflow-y-auto p-space-xl">
                    <template x-if="detail">
                        <div class="flex flex-col gap-space-md">
                            {{-- Tab formula --}}
                            <div x-show="detailTab === 'formula'" class="flex flex-col gap-3 rounded-xl bg-surface-container-low p-space-md">
                                <div class="flex items-center justify-between pb-2">
                                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">Tahapan Kalkulasi Komisi Supir</span>
                                    <span class="font-sans text-label-sm font-medium text-slate-text-muted">Perhitungan sesuai kesepakatan kerja supir</span>
                                </div>

                                <div class="flex flex-col gap-2 font-financial-table text-body-sm">
                                    <div class="flex items-center justify-between rounded-lg bg-card-bg p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-container font-sans text-xs font-bold text-slate-text-secondary">1</span>
                                            <div>
                                                <span class="font-semibold text-slate-text-primary">Omzet Kotor Pengantaran (Bruto)</span>
                                                <span class="block font-sans text-body-sm text-slate-text-muted" x-text="(detail.rit ?? 0) + ' Pengantaran Selesai • Tonase Timbangan Lapangan: ' + (detail.tonnage ?? 0) + ' Ton'"></span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-slate-text-primary" x-text="formatRp(detail.omzet_kotor ?? detail.omzet)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-card-bg p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-container font-sans text-xs font-bold text-slate-text-secondary">2</span>
                                            <div>
                                                <span class="font-semibold text-slate-text-primary">Retribusi Timbangan / Potongan Operasional</span>
                                                <span class="block font-sans text-body-sm text-slate-text-muted">Potongan operasional tercatat pada periode berjalan</span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-danger-red" x-text="'-' + formatRp(detail.potongan_operasional ?? 0)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-surface-container p-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sidebar-active font-sans text-xs font-bold text-on-primary">3</span>
                                            <div>
                                                <span class="font-semibold text-slate-text-primary">Pendapatan Bersih Angkutan Truk (Pend)</span>
                                                <span class="block font-sans text-body-sm text-slate-text-secondary">Omzet Kotor dikurangi potongan operasional</span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-slate-text-primary" x-text="formatRp(detail.omzet ?? 0)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-card-bg p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-container font-sans text-xs font-bold text-slate-text-secondary">4</span>
                                            <div>
                                                <span class="font-semibold text-slate-text-primary">Pengurang: Realisasi Uang Jalan Lapangan (Solar &amp; UJ)</span>
                                                <span class="block font-sans text-body-sm text-slate-text-muted" x-text="(detail.rit ?? 0) + ' Pengantaran @ voucher SPBU &amp; Kasir Lapangan'"></span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-danger-red" x-text="'-' + formatRp(detail.trip_money ?? 0)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-danger-surface/40 p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-danger-red font-sans text-xs font-bold text-white">5</span>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-danger-red">Pengurang: Biaya Sparepart &amp; Servis Bengkel</span>
                                                    <span x-show="detail.anomaly" x-cloak class="rounded bg-danger-red px-2 font-sans text-[0.625rem] font-bold uppercase text-white">Diperiksa Pimpinan</span>
                                                </div>
                                                <span class="block font-sans text-body-sm text-slate-text-secondary" x-text="detail.sparepart_detail || 'Tidak ada catatan potongan sparepart'"></span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-danger-red" x-text="'-' + formatRp(detail.sparepart ?? 0)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-surface-container p-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sidebar-active font-sans text-xs font-bold text-on-primary">6</span>
                                            <div>
                                                <span class="font-semibold text-slate-text-primary">Hasil Bersih Armada Unit</span>
                                                <span class="block font-sans text-body-sm text-slate-text-secondary">Pendapatan bersih - Uang Jalan - Sparepart (Basis Hak Komisi Supir)</span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-slate-text-primary" x-text="formatRp(detail.hasil_bersih_armada ?? 0)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-primary-fixed/30 p-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-banner-indigo font-sans text-xs font-bold text-on-primary">7</span>
                                            <div>
                                                <span class="font-bold text-slate-text-primary">Hak Komisi Supir</span>
                                                <span class="block font-sans text-body-sm text-slate-text-secondary">Bagi hasil komisi supir dari hasil bersih armada</span>
                                            </div>
                                        </div>
                                        <span class="font-display text-headline-sm font-bold tabular-nums text-banner-indigo" x-text="formatRp(detail.commission ?? 0)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-card-bg p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-net-pay-surface font-sans text-xs font-bold text-net-pay-emerald">8</span>
                                            <div>
                                                <span class="font-semibold text-slate-text-primary">Penambah: Tunjangan &amp; Bonus Supir</span>
                                                <span class="block font-sans text-body-sm text-slate-text-muted">Tambahan tunjangan di luar komisi ritase periode berjalan</span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums" :class="Number(detail.tunjangan || 0) > 0 ? 'text-net-pay-emerald' : 'text-slate-text-secondary'" x-text="'+' + formatRp(detail.tunjangan ?? 0)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-card-bg p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-container font-sans text-xs font-bold text-slate-text-secondary">9</span>
                                            <div>
                                                <span class="font-semibold text-slate-text-primary">Potongan: Iuran BPJS Ketenagakerjaan</span>
                                                <span class="block font-sans text-body-sm text-slate-text-muted">Iuran jaminan sosial ketenagakerjaan supir periode berjalan</span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-danger-red" x-text="'-' + formatRp(detail.bpjs ?? 0)"></span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-lg bg-card-bg p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-container font-sans text-xs font-bold text-slate-text-secondary">10</span>
                                            <div>
                                                <span class="font-semibold text-slate-text-primary">Potongan: Angsuran Kasbon</span>
                                                <span class="block font-sans text-body-sm text-slate-text-muted">Cicilan / potongan kasbon supir pada periode berjalan</span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-danger-red" x-text="'-' + formatRp(detail.kasbon ?? 0)"></span>
                                    </div>

                                    <div x-show="Number(detail.potongan_historis || 0) > 0" x-cloak class="flex items-center justify-between rounded-lg bg-danger-surface/30 p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-danger-red font-sans text-xs font-bold text-white">11</span>
                                            <div>
                                                <span class="font-semibold text-danger-red">Penyesuaian: Potongan Historis Lainnya</span>
                                                <span class="block font-sans text-body-sm text-slate-text-secondary">Potongan historis / selisih pembulatan arsip periode berjalan</span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-danger-red" x-text="'-' + formatRp(detail.potongan_historis ?? 0)"></span>
                                    </div>

                                    <div x-show="Number(detail.penambahan_historis || 0) > 0" x-cloak class="flex items-center justify-between rounded-lg bg-net-pay-surface/40 p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-net-pay-emerald font-sans text-xs font-bold text-white">11</span>
                                            <div>
                                                <span class="font-semibold text-net-pay-emerald">Penyesuaian: Penambahan Historis Lainnya</span>
                                                <span class="block font-sans text-body-sm text-slate-text-secondary">Penambahan / koreksi positif arsip periode berjalan</span>
                                            </div>
                                        </div>
                                        <span class="font-bold tabular-nums text-net-pay-emerald" x-text="'+' + formatRp(detail.penambahan_historis ?? 0)"></span>
                                    </div>

                                    <div class="mt-1 flex flex-col gap-1 rounded-lg border border-card-border bg-surface-container p-2.5 font-sans text-body-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-text-secondary">Total Hak Supir (Komisi + Tunjangan<span x-show="Number(detail.penambahan_historis || 0) > 0"> + Penyesuaian</span>)</span>
                                            <span class="font-semibold tabular-nums text-slate-text-primary" x-text="formatRp(Number(detail.commission || 0) + Number(detail.tunjangan || 0) + Number(detail.penambahan_historis || 0))"></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-text-secondary">Total Potongan (BPJS + Kasbon<span x-show="Number(detail.potongan_historis || 0) > 0"> + Historis</span>)</span>
                                            <span class="font-semibold tabular-nums text-danger-red" x-text="'-' + formatRp(Number(detail.bpjs || 0) + Number(detail.kasbon || 0) + Number(detail.potongan_historis || 0))"></span>
                                        </div>
                                        <div class="flex items-center justify-between border-t border-card-border pt-1">
                                            <span class="font-bold text-slate-text-primary">Gaji Bersih Diterima (Net Pay)</span>
                                            <span class="font-bold tabular-nums text-net-pay-emerald" x-text="formatRp(detail.net_pay ?? 0)"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab pengantaran --}}
                            <div x-show="detailTab === 'trips'" x-cloak class="flex flex-col gap-2 rounded-xl bg-surface-container-low p-space-md">
                                <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary" x-text="'Log Pengantaran Periode (' + (Array.isArray(detail.trips) ? detail.trips.length : (tripLog ? tripLog.length : 0)) + ' dari ' + (detail.rit ?? 0) + ' Trip)'"></span>
                                <div x-show="Array.isArray(detail.trips) && detail.trips.length === 0" x-cloak class="rounded-lg bg-card-bg p-4 text-center font-sans text-body-sm text-slate-text-secondary">
                                    Belum ada catatan ritase pada periode ini.
                                </div>
                                <template x-for="(trip, index) in (Array.isArray(detail.trips) ? detail.trips : tripLog)" :key="index">
                                    <div class="flex items-center justify-between rounded-lg bg-card-bg p-3 shadow-sm">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-slate-text-primary" x-text="trip.sj"></span>
                                            <span class="font-sans text-body-sm text-slate-text-secondary" x-text="trip.date + ' • ' + trip.route"></span>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold tabular-nums text-slate-text-primary" x-text="trip.tonnage + ' Ton'"></div>
                                            <div x-show="trip.total_angkutan" class="font-sans text-label-sm font-medium tabular-nums text-slate-text-secondary" x-text="formatRp(trip.total_angkutan)"></div>
                                            <div class="font-sans text-label-sm text-net-pay-emerald" x-text="trip.status"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Tab sparepart --}}
                            <div x-show="detailTab === 'sparepart'" x-cloak class="flex flex-col gap-space-md">
                                <div class="flex flex-col gap-2 rounded-xl bg-danger-surface/40 p-space-md">
                                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-danger-red">Rincian Beban Bengkel</span>
                                    <p class="font-sans text-body-sm text-slate-text-secondary" x-text="detail.sparepart_detail"></p>
                                    <div class="flex items-center justify-between rounded bg-card-bg p-3">
                                        <span class="font-sans text-body-sm text-slate-text-secondary">Total Beban Sparepart</span>
                                        <span class="font-display text-headline-sm font-bold tabular-nums text-danger-red" x-text="formatRp(detail.sparepart)"></span>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2.5 rounded-xl bg-surface-container-low p-3">
                                    <span class="material-symbols-outlined mt-0.5 text-[1.25rem] text-warning-amber">info</span>
                                    <div class="font-sans text-body-sm">
                                        <strong class="font-semibold text-slate-text-primary">Catatan Pemeriksaan Internal Direksi:</strong>
                                        <p class="mt-0.5 text-slate-text-secondary" x-text="detail.note"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Grand total --}}
                            <div class="flex flex-col justify-between gap-space-md rounded-xl bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep p-space-lg text-on-primary shadow-lg md:flex-row md:items-center">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 text-on-primary backdrop-blur-md">
                                        <span class="material-symbols-outlined text-[1.75rem]">payments</span>
                                    </div>
                                    <div>
                                        <span class="font-sans text-label-sm uppercase tracking-wider text-primary-fixed">Kalkulasi Final Disetujui</span>
                                        <h3 class="font-display text-headline-lg font-bold text-on-primary">NET PAY AKHIR (Gaji Bersih Transfer)</h3>
                                        <span class="font-sans text-xs text-white/80" x-text="'Otomatis dialokasikan ke Bank ' + detail.bank + ' setelah otorisasi ditandatangani'"></span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-start md:items-end">
                                    <span class="font-display text-[2rem] font-extrabold leading-tight tracking-tight tabular-nums text-white" x-text="formatRp(detail.net_pay ?? 0)"></span>
                                    <span class="mt-1 rounded-full bg-net-pay-surface px-2 py-0.5 font-sans text-[0.6875rem] font-bold uppercase text-net-pay-emerald" x-text="'Status: ' + (detail.status || 'Siap Review')"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="flex flex-wrap items-center justify-between gap-space-sm bg-surface-container px-space-xl py-space-md">
                    <button
                        type="button"
                        x-show="!detail || detail.status_kode !== 'paid'"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-2 font-sans text-body-sm font-semibold text-danger-red transition-colors hover:bg-danger-surface"
                        @click="requestRevision()"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">assignment_return</span>
                        Tolak / Minta Revisi HRD
                    </button>
                    <div class="ml-auto flex items-center gap-space-sm">
                        <button type="button" class="rounded-lg bg-card-bg px-4 py-2 font-sans text-body-sm font-semibold text-slate-text-secondary shadow-sm" @click="detailOpen = false">Tutup Modal</button>
                        <button
                            type="button"
                            x-show="!detail || (detail.status_kode || 'draft') === 'draft'"
                            class="flex items-center gap-2 rounded-lg bg-net-pay-emerald px-5 py-2.5 font-sans text-body-sm font-bold text-on-primary shadow-md transition-all hover:bg-emerald-600 disabled:opacity-60"
                            :disabled="sedangMemproses"
                            @click="confirmAcc(detail ? detail.id : null, detail ? detail.name : '')"
                        >
                            <span class="material-symbols-outlined text-[1.25rem]">verified</span>
                            ACC &amp; Sahkan Penggajian Supir Ini
                        </button>
                        <button
                            type="button"
                            x-show="detail && detail.status_kode === 'approved'"
                            class="flex items-center gap-2 rounded-lg bg-sky-600 px-5 py-2.5 font-sans text-body-sm font-bold text-white shadow-md transition-all hover:bg-sky-700 disabled:opacity-60"
                            :disabled="sedangMemproses"
                            @click="cairkanSatuan(detail ? detail.id : null)"
                        >
                            <span class="material-symbols-outlined text-[1.25rem]">payments</span>
                            Cairkan Dana
                        </button>
                        <button
                            type="button"
                            disabled
                            readonly
                            x-show="detail && detail.status_kode === 'paid'"
                            class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg bg-net-pay-surface px-4 py-2 font-sans text-body-sm font-bold text-net-pay-emerald"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">paid</span>
                            Sudah Dicairkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal batch ACC --}}
        <div x-show="batchOpen" x-cloak @keydown.escape.window="batchOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-sidebar-dark/70 backdrop-blur-sm" @click="batchOpen = false"></div>

            <div class="relative z-10 flex w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-card-bg shadow-drawer">
                <div class="flex items-center gap-2.5 bg-surface-container-low p-space-lg">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-net-pay-emerald text-on-primary">
                        <span class="material-symbols-outlined text-[1.375rem]">done_all</span>
                    </div>
                    <div>
                        <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">ACC Batch Payroll</h3>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Periode {{ $period['code'] }} &bull; {{ $summary['audit_ready'] }} supir valid</p>
                    </div>
                </div>

                <div class="flex flex-col gap-space-md p-space-lg">
                    <p class="font-sans text-body-md text-slate-text-secondary">
                        Seluruh baris penggajian periode <strong class="text-slate-text-primary">{{ $period['code'] }}</strong>
                        akan disahkan sekaligus oleh Pimpinan beserta tanggal pembayaran hari ini.
                    </p>
                    <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN OTORISASI PAYROLL & ACC PIMPINAN):
     - Tujuan Halaman: Meja otorisasi sakral Direktur / Pimpinan: memeriksa satu per satu
       hasil hitungan gaji supir yang diajukan Admin HRD, memberikan persetujuan (ACC),
       menolak/mengembalikan revisi dengan catatan, atau mengeksekusi Pencairan Dana (Disbursement).
     - Analogi Dunia Nyata: Seperti map disposisi persetujuan transfer uang di meja Direktur Utama:
       Direktur meneliti apakah hitungan tonase dan komisi supir sudah wajar. Jika setuju,
       Direktur mencap ACC (atau ACC Massal) lalu mengklik "Cairkan Dana" agar uang ditransfer.
     - Standar Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2 & 3.8):
       * HANYA peran `pimpinan` yang boleh menyetujui (`approved`) atau mencairkan (`paid`).
       * Mendukung ACC Bertahap: satuan per supir, massal via centang checkbox, atau ACC Semua.
       * Penolakan/revisi wajib menyertakan alasan tertulis (`catatan_penolakan`).
       * Pencairan dana (`paid`) otomatis memotong angsuran kasbon (`terpotong`) dan melunasi sisa kasbon!
     - Data dari Controller (Executive/PayrollReviewController@index):
       * $periodeTerpilih: Periode yang sedang dibuka dan diperiksa.
       * $summary: Ringkasan total cash requirement (uang kas yang harus disiapkan bank).
       * $drivers: Baris supir dengan status 'draft', 'pending', 'approved', atau 'paid'.
     ============================================================================== -->
@php
                        $adaBarisModal = ($permintaanAcc['jumlah_supir'] ?? 0) > 0;
                        $totalKomisiModal = (int) ($permintaanAcc['komisi'] ?? 0);
                        $totalTunjanganModal = (int) ($permintaanAcc['tunjangan'] ?? 0);
                        $totalBpjsModal = (int) ($permintaanAcc['bpjs'] ?? 0);
                        $totalKasbonModal = (int) ($permintaanAcc['kasbon'] ?? 0);
                        $totalPotonganHistorisModal = (int) ($permintaanAcc['potongan_historis'] ?? 0);
                        $totalPenambahanHistorisModal = (int) ($permintaanAcc['penambahan_historis'] ?? 0);
                        $totalPotonganWajibModal = $adaBarisModal
                            ? ($totalBpjsModal + $totalKasbonModal)
                            : (int) ($permintaanAcc['total_potongan'] ?? 0);
                    @endphp
                    <div class="rounded-lg bg-surface-container-low p-3">
                        <div class="flex items-center justify-between font-sans text-body-sm">
                            <span class="text-slate-text-secondary">Jumlah supir</span>
                            <span class="font-bold text-slate-text-primary">{{ $permintaanAcc['jumlah_supir'] }} Supir</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between font-sans text-body-sm">
                            <span class="text-slate-text-secondary">Total komisi supir</span>
                            <span class="font-bold tabular-nums text-slate-text-primary">{{ $rp($totalKomisiModal) }}</span>
                        </div>
                        @if ($totalTunjanganModal > 0)
                            <div class="mt-1 flex items-center justify-between font-sans text-body-sm">
                                <span class="text-slate-text-secondary">Total tunjangan</span>
                                <span class="font-bold tabular-nums text-net-pay-emerald">+{{ $rp($totalTunjanganModal) }}</span>
                            </div>
                        @endif
                        @if ($totalPenambahanHistorisModal > 0)
                            <div class="mt-1 flex items-center justify-between font-sans text-body-sm">
                                <span class="text-slate-text-secondary">Penambahan historis</span>
                                <span class="font-bold tabular-nums text-net-pay-emerald">+{{ $rp($totalPenambahanHistorisModal) }}</span>
                            </div>
                        @endif
                        <div class="mt-1 flex items-center justify-between font-sans text-body-sm">
                            <span class="text-slate-text-secondary">Total potongan (BPJS &amp; Kasbon)</span>
                            <span class="font-bold tabular-nums text-danger-red">-{{ $rp($totalPotonganWajibModal) }}</span>
                        </div>
                        @if ($totalPotonganHistorisModal > 0)
                            <div class="mt-1 flex items-center justify-between font-sans text-body-sm">
                                <span class="text-slate-text-secondary">Penyesuaian historis</span>
                                <span class="font-bold tabular-nums text-danger-red">-{{ $rp($totalPotonganHistorisModal) }}</span>
                            </div>
                        @endif
                        <div class="mt-2 flex items-center justify-between border-t border-card-border pt-2 font-sans text-body-sm">
                            <span class="font-semibold text-slate-text-primary">Total kebutuhan kas</span>
                            <span class="font-bold tabular-nums text-net-pay-emerald">{{ $rp($permintaanAcc['total_kas']) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-warning-surface/70 p-3">
                        <p class="font-sans text-body-sm text-slate-text-secondary">
                            Pastikan lima poin pemeriksaan (kas, komisi, tunjangan, sparepart, potongan kasbon/BPJS)
                            sudah Anda tinjau sebelum menyetujui.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-space-sm bg-surface-container-low p-space-lg">
                    <button type="button" class="rounded-lg bg-card-bg px-4 py-2 font-sans text-body-sm font-semibold text-slate-text-secondary shadow-sm" @click="batchOpen = false">Batal</button>
                    <button type="button" class="rounded-lg bg-net-pay-emerald px-4 py-2 font-sans text-body-sm font-bold text-on-primary shadow-md disabled:opacity-60" :disabled="sedangMemproses" @click="confirmBatch()">Ya, ACC Payroll</button>
                </div>
            </div>
        </div>

        {{-- Modal tolak / minta revisi --}}
        <div x-show="reviseOpen" x-cloak @keydown.escape.window="reviseOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-sidebar-dark/70 backdrop-blur-sm" @click="reviseOpen = false"></div>

            <div class="relative z-10 flex w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-card-bg shadow-drawer">
                <div class="flex items-center gap-2.5 bg-surface-container-low p-space-lg">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-danger-surface text-danger-red">
                        <span class="material-symbols-outlined text-[1.375rem]">assignment_return</span>
                    </div>
                    <div>
                        <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Tolak &amp; Minta Revisi</h3>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Periode {{ $period['code'] }} dikembalikan ke Admin HRD</p>
                    </div>
                </div>

                <div class="flex flex-col gap-space-md p-space-lg">
                    <p class="font-sans text-body-md text-slate-text-secondary">
                        Periode akan kembali berstatus <strong class="text-slate-text-primary">Draf Perhitungan</strong>
                        sehingga Admin HRD dapat memperbaiki komponen gaji.
                    </p>
                    <div class="flex flex-col gap-1">
                        <label for="catatan_revisi" class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">Catatan Revisi untuk Admin</label>
                        <textarea
                            id="catatan_revisi"
                            x-model="reviseNote"
                            rows="3"
                            maxlength="500"
                            class="w-full rounded-lg bg-canvas-bg p-3 font-sans text-body-md text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                            placeholder="Contoh: Mohon lampirkan nota bengkel unit BE 8639 AAU sebelum payroll dicairkan."
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-space-sm bg-surface-container-low p-space-lg">
                    <button type="button" class="rounded-lg bg-card-bg px-4 py-2 font-sans text-body-sm font-semibold text-slate-text-secondary shadow-sm" @click="reviseOpen = false">Batal</button>
                    <button type="button" class="rounded-lg bg-danger-red px-4 py-2 font-sans text-body-sm font-bold text-white shadow-md disabled:opacity-60" :disabled="sedangMemproses" @click="kirimRevisi()">Kirim Permintaan Revisi</button>
                </div>
            </div>
        </div>
        {{-- Modal Konfirmasi Pencairan Gaji dengan Password (Verifikasi 2 Langkah) --}}
        <div
            x-show="modalKonfirmasiCair"
            x-cloak
            @keydown.escape.window="modalKonfirmasiCair = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            aria-modal="true"
            role="dialog"
        >
            <div class="absolute inset-0 bg-sidebar-dark/70 backdrop-blur-sm" @click="modalKonfirmasiCair = false"></div>

            <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-card-bg shadow-drawer border border-card-border">
                <div class="flex items-start justify-between bg-surface-container-low p-space-lg border-b border-card-border">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-500 text-white shadow-sm">
                            <span class="material-symbols-outlined text-[1.5rem]">payments</span>
                        </div>
                        <div>
                            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Otorisasi Pencairan Dana Gaji</h3>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Verifikasi 2 Langkah Direksi / Pimpinan</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-slate-text-muted hover:bg-surface-container hover:text-slate-text-primary" @click="modalKonfirmasiCair = false">
                        <span class="material-symbols-outlined text-[1.25rem]">close</span>
                    </button>
                </div>

                <div class="p-space-lg flex flex-col gap-4">
                    <div class="rounded-xl bg-sky-50 border border-sky-200 p-3.5 text-xs text-sky-900 flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-[1.25rem] text-sky-600 shrink-0 mt-0.5">lock_person</span>
                        <div>
                            Anda akan mengeksekusi pencairan gaji untuk <strong x-text="labelTargetCair"></strong> periode <strong>{{ $period['code'] }}</strong>.
                            Ketik kata sandi akun Pimpinan Anda untuk menyetujui mutasi rekening payroll dan pemotongan kasbon otomatis.
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password_pencairan_pimpinan" class="font-sans text-body-sm font-bold text-slate-text-primary">
                            Kata Sandi Akun Pimpinan <span class="text-danger-red">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">key</span>
                            <input
                                :type="lihatKataSandiCair ? 'text' : 'password'"
                                id="password_pencairan_pimpinan"
                                x-model="kataSandiCair"
                                @keydown.enter.prevent="eksekusiPencairanDenganPassword()"
                                class="h-11 w-full rounded-xl bg-surface-container-low pl-9 pr-11 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Ketik kata sandi Anda..."
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                @click="lihatKataSandiCair = !lihatKataSandiCair"
                                class="absolute right-3 text-slate-text-muted hover:text-slate-text-primary"
                                aria-label="Lihat kata sandi"
                            >
                                <span class="material-symbols-outlined text-[1.25rem]" x-text="lihatKataSandiCair ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 bg-surface-container-low px-space-lg py-space-md border-t border-card-border">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2.5 font-sans text-body-sm font-semibold text-slate-text-secondary hover:bg-surface-container"
                        @click="modalKonfirmasiCair = false"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="!kataSandiCair.trim() || sedangMemproses"
                        @click="eksekusiPencairanDenganPassword()"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 font-sans text-body-sm font-bold text-white shadow-md transition-all hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]" x-text="sedangMemproses ? 'progress_activity' : 'verified'">verified</span>
                        <span x-text="sedangMemproses ? 'Memverifikasi...' : 'Konfirmasi &amp; Cairkan'">Konfirmasi &amp; Cairkan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-executive-layout>
