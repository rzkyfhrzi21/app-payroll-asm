@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
@endphp

<x-driver-layout title="Slip Gaji Supir">
    <div class="flex flex-col gap-5" x-data="driverPayroll(@js($payroll), @js($trips), @js($profile))">
        {{-- Banner --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep text-on-primary shadow-hero">
            <div class="pointer-events-none absolute -right-12 -top-16 h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/3 h-72 w-72 rounded-full bg-banner-sky/20 blur-2xl"></div>

            <div class="relative flex flex-col justify-between gap-space-xl p-space-lg xl:flex-row xl:items-center">
                <div class="flex flex-col gap-space-sm">
                    <div class="flex flex-wrap items-center gap-space-sm">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 font-sans text-label-md uppercase tracking-wider backdrop-blur-md">
                            <span class="material-symbols-outlined text-[1rem] text-banner-sky">verified</span>
                            Status: Terverifikasi ACC Pimpinan
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-3 py-1 font-sans text-label-md text-slate-text-primary">
                            <span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span>
                            <span>Periode Gaji: {{ $period['code'] }} ({{ $period['range'] }})</span>
                        </span>
                    </div>

                    <div class="flex flex-col">
                        <h1 class="font-display text-headline-lg tracking-tight text-white">
                            Halo, <span>{{ $profile['name'] }}</span>
                            <span class="font-sans text-[1.25rem] font-normal text-white/85">(<span>NIP: {{ $profile['nip'] }}</span>)</span>
                        </h1>
                        <p class="mt-1 flex flex-wrap items-center gap-2 font-sans text-body-md text-white/90">
                            <span class="material-symbols-outlined text-[1.125rem] text-banner-sky">account_balance</span>
                            Disbursed ke rekening <span class="font-semibold tracking-wide">{{ $profile['bank'] }} {{ $profile['account'] }}</span>
                            a.n. {{ $profile['name'] }} &bull; Transfer terjadwal {{ $payroll['pay_date'] }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-space-md">
                    <button
                        type="button"
                        class="inline-flex items-center gap-space-sm rounded-lg bg-white px-4 py-3 font-display text-headline-sm text-slate-text-primary shadow-md transition-all hover:bg-surface-bright active:scale-[0.98]"
                        @click="downloadPdf()"
                    >
                        <span class="material-symbols-outlined text-[1.25rem] text-primary-container">file_download</span>
                        <span x-text="pdfState === 'idle' ? 'Unduh Slip Gaji (PDF)' : (pdfState === 'loading' ? 'Menyiapkan PDF...' : 'Slip Terunduh')"></span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-space-sm rounded-lg bg-white/15 px-4 py-3 font-display text-headline-sm text-white backdrop-blur-md transition-all hover:bg-white/25 active:scale-[0.98]"
                        @click="tableOpen = ! tableOpen"
                    >
                        <span class="material-symbols-outlined text-[1.25rem]">receipt_long</span>
                        <span x-text="tableOpen ? 'Sembunyikan Rincian' : 'Rincian Pengantaran'"></span>
                    </button>
                    <a
                        href="{{ route('driver.simulation.index') }}"
                        class="inline-flex items-center gap-space-sm rounded-lg bg-white/15 px-4 py-3 font-display text-headline-sm text-white backdrop-blur-md transition-all hover:bg-white/25 active:scale-[0.98]"
                    >
                        <span class="material-symbols-outlined text-[1.25rem] text-banner-sky">calculate</span>
                        <span>Simulasi Gaji</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI --}}
        <div class="grid grid-cols-1 gap-space-lg md:grid-cols-2 xl:grid-cols-4">
            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase">Take Home Pay (Gaji Bersih)</span>
                        <div class="mt-1 font-financial-total font-bold tabular-nums text-slate-text-primary">{{ $rp($payroll['net_pay']) }}</div>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.375rem]">payments</span>
                    </div>
                </div>
                <div class="mt-space-md flex items-center justify-between pt-space-sm">
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-sans text-label-sm font-semibold',
                        'bg-net-pay-surface text-slate-text-primary' => $payroll['is_approved'],
                        'bg-warning-surface text-warning-amber' => ! $payroll['is_approved'],
                    ])>
                        <span @class([
                            'h-2 w-2 rounded-full',
                            'animate-pulse bg-net-pay-emerald' => $payroll['is_approved'],
                            'bg-warning-amber' => ! $payroll['is_approved'],
                        ])></span>
                        {{ $payroll['payment_label'] }}
                    </span>
                    <span class="font-sans text-body-sm text-slate-text-muted">{{ $profile['bank'] }} Payroll</span>
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase">Total Muatan Batubara</span>
                        <div class="mt-1 font-financial-total font-bold tabular-nums text-slate-text-primary">
                            {{ $payroll['rit'] }} Pengantaran <span class="font-sans text-headline-sm font-normal text-slate-text-secondary">/ {{ $payroll['tonnage'] }} Ton</span>
                        </div>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container-high text-primary">
                        <span class="material-symbols-outlined text-[1.375rem]">local_shipping</span>
                    </div>
                </div>
                <div class="mt-space-md flex items-center justify-between pt-space-sm font-sans text-body-sm text-slate-text-secondary">
                    <span class="flex items-center gap-1 font-medium text-slate-text-primary">
                        <span class="material-symbols-outlined text-[1rem] text-banner-indigo">alt_route</span>
                        Rute {{ $payroll['route_list'] }}
                    </span>
                    <span class="rounded bg-net-pay-surface px-1.5 py-0.5 font-sans text-label-sm text-net-pay-emerald">100% On-spec</span>
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase">Total Uang Jalan (UJ Diterima)</span>
                        <div class="mt-1 font-financial-total font-bold tabular-nums text-slate-text-primary">{{ $rp($payroll['trip_money']) }}</div>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container text-banner-indigo">
                        <span class="material-symbols-outlined text-[1.375rem]">account_balance_wallet</span>
                    </div>
                </div>
                <div class="mt-space-md flex items-center justify-between pt-space-sm font-sans text-body-sm text-slate-text-secondary">
                    <span class="text-slate-text-muted">Non-rekon (Hak Jalur)</span>
                    <span class="font-financial-table font-semibold text-slate-text-primary">{{ $payroll['rit'] }} Voucher</span>
                </div>
            </div>

            <div class="flex flex-col justify-between rounded-xl bg-card-bg p-space-lg shadow-card">
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase">Total Potongan Resmi</span>
                        <div class="mt-1 font-financial-total font-bold tabular-nums text-danger-red">-{{ $rp($payroll['bpjs'] + $payroll['kasbon']) }}</div>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-danger-surface text-danger-red">
                        <span class="material-symbols-outlined text-[1.375rem]">receipt</span>
                    </div>
                </div>
                <div class="mt-space-md flex items-center justify-between pt-space-sm font-sans text-body-sm text-slate-text-secondary">
                    <span>BPJS ({{ $rp($payroll['bpjs']) }}) + Kasbon ({{ $rp($payroll['kasbon']) }})</span>
                    <span class="font-sans text-label-sm font-medium text-on-surface-variant">{{ $payroll['kasbon_label'] }}</span>
                </div>
            </div>
        </div>

        {{-- Grid utama --}}
        <div class="grid grid-cols-1 items-start gap-space-xl xl:grid-cols-12">
            <div class="flex flex-col gap-space-md rounded-xl bg-card-bg p-space-lg shadow-card xl:col-span-8">
                <div class="flex flex-col justify-between gap-space-md md:flex-row md:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-space-sm">
                            <h2 class="font-display text-headline-md text-slate-text-primary">Lembar Manifest Pengantaran Harian</h2>
                            <span class="rounded-full bg-surface-container px-2 py-0.5 font-sans text-label-sm font-semibold text-primary">{{ $payroll['rit'] }} Trip Tervalidasi</span>
                        </div>
                        <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">
                            <span>Armada Tronton {{ $profile['plate'] }}</span> &bull; <span>{{ $profile['route'] }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-space-sm">
                        <span class="flex items-center gap-1 font-sans text-body-sm text-slate-text-muted">
                            <span class="material-symbols-outlined text-[1.125rem]">tune</span>
                            Filter Periode Aktif
                        </span>
                        <div class="rounded-lg bg-surface-container-low px-3 py-1 font-financial-table text-slate-text-primary">{{ $period['code'] }}</div>
                    </div>
                </div>

                <div x-show="tableOpen">
                    <div class="overflow-x-auto rounded-lg">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-canvas-bg font-sans text-label-sm uppercase tracking-wider">
                                    <th class="px-3 py-3">Tanggal Pengantaran</th>
                                    <th class="px-3 py-3">No. Surat Jalan</th>
                                    <th class="px-3 py-3">Rute</th>
                                    <th class="px-3 py-3 text-right">Tonase (MT)</th>
                                    <th class="px-3 py-3 text-right">Uang Jalan</th>
                                    <th class="px-3 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-low font-financial-table">
                                @foreach ($trips as $trip)
                                    <tr class="transition-colors hover:bg-canvas-bg/60">
                                        <td class="px-3 py-3 font-medium">{{ $trip['date'] }}</td>
                                        <td class="px-3 py-3 font-semibold text-primary">{{ $trip['sj'] }}</td>
                                        <td class="px-3 py-3">
                                            <span class="inline-block rounded bg-surface-container px-2 py-0.5 font-sans text-label-sm font-semibold text-banner-indigo">{{ $trip['route'] }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-right font-semibold tabular-nums">{{ $trip['tonnage'] }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums text-net-pay-emerald">{{ $rp($trip['trip_money']) }}</td>
                                        <td class="px-3 py-3 text-center">
                                            <span @class([
                                                ' inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-semibold',
                                                'bg-net-pay-surface text-net-pay-emerald' => $trip['tone'] === 'emerald',
                                                'bg-warning-surface text-warning-amber' => $trip['tone'] === 'amber',
                                            ])>
                                                <span class="h-1.5 w-1.5 rounded-full {{ $trip['tone'] === 'emerald' ? 'bg-net-pay-emerald' : 'bg-warning-amber' }}"></span>
                                                {{ $trip['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 flex flex-col items-center justify-between gap-2 rounded-lg bg-canvas-bg px-3 py-2 sm:flex-row">
                        <span class="font-sans text-body-sm text-slate-text-secondary">
                            Menampilkan <span class="font-semibold">1-{{ count($trips) }}</span> dari <span class="font-semibold">{{ $payroll['rit'] }}</span> surat jalan
                        </span>
                        <a href="{{ route('driver.trips.index') }}" class="flex items-center gap-1 font-sans text-body-sm font-semibold text-primary hover:underline">
                            Lihat riwayat lengkap
                            <span class="material-symbols-outlined text-[1rem]">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <div x-show="! tableOpen" x-cloak class="flex items-center justify-between rounded-lg bg-canvas-bg p-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[1.25rem] text-banner-indigo">visibility_off</span>
                        <span class="font-sans text-body-sm text-slate-text-secondary">Tabel pengantaran disembunyikan. Tekan "Rincian Pengantaran" untuk menampilkan.</span>
                    </div>
                    <button type="button" class="rounded bg-card-bg px-3 py-1 font-sans text-label-sm font-semibold text-primary shadow-sm" @click="tableOpen = true">Tampilkan</button>
                </div>
            </div>

            {{-- Buku komisi --}}
            <div class="flex flex-col gap-space-lg xl:col-span-4">
                <div class="flex flex-col gap-space-md rounded-xl bg-card-bg p-space-lg shadow-card">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="font-sans text-label-sm uppercase text-slate-text-secondary">Standar Formula Perhitungan</span>
                            <h3 class="font-display text-headline-md text-slate-text-primary">Buku Komisi Supir</h3>
                        </div>
                        <span class="rounded-lg bg-surface-container p-1.5 text-banner-indigo">
                            <span class="material-symbols-outlined text-[1.375rem]">calculate</span>
                        </span>
                    </div>
                    <p class="font-sans text-body-sm text-slate-text-muted">
                        Sesuai Dokumen Perjanjian Kerja {{ $dataPerusahaan->nama_perusahaan ?? config('app.name') }}. Komisi supir dihitung dari Hasil Bersih Unit Armada
                        <span class="font-semibold">{{ $profile['plate'] }}</span>.
                    </p>

                    <div class="mt-space-xs flex flex-col gap-space-xs font-financial-table">
                        <div class="flex items-center justify-between py-1.5 text-slate-text-primary">
                            <span class="font-sans text-body-sm">Omzet Kotor Truk ({{ $payroll['rit'] }} Rit)</span>
                            <span class="font-semibold tabular-nums">{{ $rp($payroll['gross_omzet']) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 text-danger-red">
                            <span class="font-sans text-body-sm">Retribusi &amp; Jembatan Timbang</span>
                            <span class="font-medium tabular-nums">-{{ $rp($payroll['retribution']) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded bg-surface-container-low px-2 py-2 font-semibold text-slate-text-primary">
                            <span class="font-sans text-body-sm">Pendapatan Bersih Angkutan</span>
                            <span class="tabular-nums">{{ $rp($payroll['net_revenue']) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 text-danger-red">
                            <span class="font-sans text-body-sm">Pengurang: Uang Jalan Lapangan</span>
                            <span class="font-medium tabular-nums">-{{ $rp($payroll['trip_money']) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 text-danger-red">
                            <span class="font-sans text-body-sm">Pengurang: Servis &amp; Sparepart Truk</span>
                            <span class="font-medium tabular-nums">-{{ $rp($payroll['sparepart']) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded bg-surface-container px-2 py-2 font-bold text-slate-text-primary">
                            <span class="font-sans text-body-sm">Hasil Bersih Unit Armada</span>
                            <span class="text-headline-sm tabular-nums">{{ $rp($payroll['unit_net']) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2.5 text-primary">
                            <div class="flex flex-col">
                                <span class="font-sans text-[0.875rem] font-bold">Hak Komisi Supir</span>
                                <span class="font-sans text-[0.6875rem] text-slate-text-muted">Dihitung dari hasil bersih unit armada</span>
                            </div>
                            <span class="font-sans text-headline-sm font-bold tabular-nums">{{ $rp($payroll['commission']) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 text-danger-red">
                            <span class="font-sans text-body-sm text-slate-text-secondary">Potongan BPJS Ketenagakerjaan</span>
                            <span class="font-medium tabular-nums">-{{ $rp($payroll['bpjs']) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 text-danger-red">
                            <div class="flex flex-col">
                                <span class="font-sans text-body-sm text-slate-text-secondary">Potongan Angsuran Kasbon</span>
                                <span class="font-sans text-[0.6875rem] text-slate-text-muted">Sisa saldo kasbon: {{ $rp($payroll['kasbon_remaining']) }}</span>
                            </div>
                            <span class="font-medium tabular-nums">-{{ $rp($payroll['kasbon']) }}</span>
                        </div>
                    </div>

                    <div class="mt-space-md flex flex-col gap-space-xs rounded-xl bg-net-pay-surface p-space-md text-slate-text-primary">
                        <div class="flex items-center justify-between">
                            <span class="font-sans text-label-md uppercase tracking-wider text-slate-text-secondary">Total Gaji Diterima (Net Pay)</span>
                            <span class="rounded bg-card-bg px-1.5 py-0.5 font-sans text-[0.6875rem] font-bold text-on-secondary-fixed-variant shadow-sm">TRANSFER BANK</span>
                        </div>
                        <div class="font-display text-headline-lg font-bold tabular-nums text-slate-text-primary">{{ $rp($payroll['net_pay']) }}</div>
                        <div class="flex items-center justify-between pt-space-xs font-sans text-body-sm text-slate-text-secondary">
                            <span>Status PPh 21 Final</span>
                            <span class="font-medium text-slate-text-primary">Ditanggung Perusahaan (Nett)</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-space-md rounded-xl bg-card-bg p-space-lg shadow-card">
                    <div class="flex items-center gap-space-sm font-sans text-label-md uppercase tracking-wide text-slate-text-secondary">
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">verified</span>
                        Otorisasi Dokumen Resmi
                    </div>
                    <div class="flex flex-col gap-space-sm rounded-lg bg-surface-container-low p-space-md">
                        <div class="flex items-start justify-between">
                            <div class="flex flex-col">
                                <div class="font-display text-headline-sm text-slate-text-primary">{{ $payroll['approver'] }}</div>
                                <div class="font-sans text-body-sm text-slate-text-secondary">{{ $payroll['approver_role'] }}</div>
                            </div>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-net-pay-surface text-net-pay-emerald">
                                <span class="material-symbols-outlined text-[1.125rem]">done_all</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 pt-space-xs font-sans text-[0.75rem] text-slate-text-secondary">
                            <div class="flex items-center justify-between">
                                <span>Waktu Disahkan:</span>
                                <span class="font-medium text-slate-text-primary">{{ $payroll['approved_at'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Digital Hash SHA-256:</span>
                                <span class="font-financial-table font-semibold text-banner-indigo">{{ $payroll['hash'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="font-sans text-[0.6875rem] leading-relaxed text-slate-text-muted">
                        @if ($payroll['is_approved'])
                            Dokumen ini diakui sebagai bukti penggajian elektronik yang sah dan tidak memerlukan tanda tangan basah fisik.
                        @else
                            Slip ini masih berupa draf perhitungan dan belum disahkan Pimpinan. Nilai dapat berubah setelah proses verifikasi.
                        @endif
                        Hubungi bagian Keuangan Lapangan untuk klarifikasi.
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer bantuan --}}
        <div class="flex flex-col items-center justify-between gap-space-md rounded-xl bg-surface-container-low p-space-md font-sans text-body-sm text-slate-text-secondary md:flex-row">
            <div class="flex items-center gap-space-md">
                <span class="material-symbols-outlined text-[1.375rem] text-banner-indigo">contact_support</span>
                <span>Mengalami kendala hitungan uang jalan atau lembar timbangan tidak match? Hubungi Checker Posko  (0812-7729-1090).</span>
            </div>
            <div class="flex shrink-0 items-center gap-space-sm">
                <span class="font-sans text-label-sm uppercase text-slate-text-muted">Audit Log Id:</span>
                <span class="font-financial-table font-bold text-slate-text-primary">{{ $payroll['audit_id'] }}</span>
            </div>
        </div>
    </div>
</x-driver-layout>
