@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
@endphp

<x-driver-layout title="Buku Kasbon">
    <div class="flex flex-col gap-5" x-data="driverLoans(@js($loan), @js($mutations), @js($schedule), @js($profile))">
        {{-- Header --}}
        <div class="relative overflow-hidden rounded-xl bg-card-bg p-space-xl shadow-card">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gradient-to-br from-banner-sky/15 via-primary-container/10 to-transparent blur-2xl"></div>

            <div class="relative z-10 flex flex-col justify-between gap-space-lg lg:flex-row lg:items-center">
                <div class="flex flex-col gap-space-lg md:flex-row md:items-center">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-surface-container-high font-display text-headline-lg font-bold text-primary shadow-inner">
                        <span>{{ $profile['initial'] }}</span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-space-sm">
                            <span class="font-display text-headline-lg text-slate-text-primary">Buku Kasbon &amp; Potongan Finansial</span>
                            <span @class([
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-sans text-label-sm font-bold',
                                'bg-primary/10 text-primary' => $loan['ada_kasbon'],
                                'bg-surface-container text-slate-text-secondary' => ! $loan['ada_kasbon'],
                            ])>{{ $loan['ada_kasbon'] ? 'KREDIT OPERASIONAL AKTIF' : 'TANPA KASBON AKTIF' }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 font-sans text-body-md text-slate-text-secondary">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[1.125rem] text-slate-text-muted">badge</span>
                                <span class="font-medium text-slate-text-primary">{{ $profile['name'] }}</span>
                                <span class="font-sans text-label-sm tracking-wider text-slate-text-muted">(NIP: {{ $profile['nip'] }})</span>
                            </div>
                            <span class="text-slate-text-muted">&bull;</span>
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[1.125rem] text-slate-text-muted">local_shipping</span>
                                <span>Unit:</span>
                                <span class="rounded bg-surface-container px-2 py-0.5 font-financial-table font-semibold text-primary">{{ $profile['plate'] }}</span>
                                <span class="font-sans text-body-sm text-slate-text-muted">({{ $profile['truck'] }})</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-space-md">
                    <a
                        href="{{ route('driver.loans.print') }}"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2 rounded-lg bg-surface-container-low px-4 py-2.5 font-sans text-label-md text-slate-text-primary shadow-sm transition-all hover:bg-surface-container active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[1.125rem] text-slate-text-secondary">print</span>
                        Cetak / Unduh Buku Kasbon
                    </a>
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2.5 font-sans text-label-md text-on-primary shadow-md transition-all hover:bg-primary active:scale-95"
                        @click="modalOpen = true"
                    >
                        <span class="material-symbols-outlined text-[1.25rem]">add_circle</span>
                        + Ajukan Permohonan Kasbon
                    </button>
                </div>
            </div>
        </div>

        {{-- KPI --}}
        <div class="grid grid-cols-1 gap-space-lg md:grid-cols-3">
            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-xl shadow-card">
                <div class="space-y-space-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary">Sisa Hutang Pokok Berjalan</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-banner-sky/15 text-banner-indigo">
                            <span class="material-symbols-outlined text-[1.25rem]">pending_actions</span>
                        </div>
                    </div>
                    <div>
                        <div class="font-financial-total font-bold tracking-tight text-slate-text-primary">{{ $rp($loan['remaining']) }}</div>
                        <p class="mt-1 font-sans text-body-sm text-slate-text-secondary">
                            Dari total pinjaman <span class="font-semibold text-slate-text-primary">{{ $rp($loan['principal']) }}</span> ({{ $loan['status'] }})
                        </p>
                    </div>
                </div>
                <div class="mt-space-lg space-y-2 pt-space-md">
                    <div class="flex items-center justify-between font-sans text-label-md">
                        <span class="text-slate-text-secondary">Progress Pelunasan</span>
                        <span class="font-bold text-banner-indigo">{{ $loan['progress_pct'] }}% Lunas</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-surface-container-high">
                        <div class="h-full rounded-full bg-gradient-to-r from-banner-sky to-banner-indigo" style="width: {{ $loan['progress_pct'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between pt-1 font-sans text-body-sm text-slate-text-muted">
                        <span class="flex items-center gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-net-pay-emerald"></span>
                            <span>{{ $loan['tenor_paid'] }} Kali Terpotong</span>
                        </span>
                        <span class="font-medium text-slate-text-secondary">Sisa {{ $loan['tenor_left'] }} Bulan Tenor</span>
                    </div>
                </div>
            </div>

            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-card-bg p-space-xl shadow-card">
                <div class="space-y-space-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary">Potongan Rutin Cut-Off {{ $period['code'] }}</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-warning-amber/15 text-warning-amber">
                            <span class="material-symbols-outlined text-[1.25rem]">calendar_month</span>
                        </div>
                    </div>
                    <div>
                        <div class="font-financial-total font-bold tracking-tight text-slate-text-primary">{{ $rp($loan['installment']) }}</div>
                        <p class="mt-1 font-sans text-body-sm text-slate-text-secondary">
                            Auto-debit otomatis pada tanggal tutup buku <span class="font-semibold text-slate-text-primary">{{ $loan['next_cut_off'] }}</span>
                        </p>
                    </div>
                </div>
                <div class="mt-space-lg flex items-center justify-between rounded-lg bg-surface-container-low p-space-md pt-space-md">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[1.125rem] text-net-pay-emerald">{{ $loan['ada_kasbon'] ? 'check_circle' : 'info' }}</span>
                        <span class="font-sans text-body-sm font-medium text-slate-text-primary">
                            {{ $loan['ada_kasbon'] ? 'Slip Gaji '.$period['code'].' Siap Potong' : 'Tidak ada potongan kasbon periode ini' }}
                        </span>
                    </div>
                    <span class="rounded bg-warning-surface px-2 py-0.5 font-sans text-label-sm font-bold uppercase text-warning-amber">{{ $loan['ada_kasbon'] ? 'Jadwal H-4' : 'NIHIL' }}</span>
                </div>
            </div>

            <div class="relative flex flex-col justify-between overflow-hidden rounded-xl bg-gradient-to-br from-banner-sky/90 via-banner-indigo to-banner-deep p-space-xl text-on-primary shadow-hero">
                <div class="relative z-10 space-y-space-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-sans text-label-sm uppercase tracking-wider text-white/80">Sisa Pinjaman Berjalan</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-white backdrop-blur-md">
                            <span class="material-symbols-outlined text-[1.25rem]">account_balance_wallet</span>
                        </div>
                    </div>
                    <div>
                        <div class="font-financial-total font-bold tracking-tight">{{ $rp($loan['remaining']) }}</div>
                        <p class="mt-1 font-sans text-body-sm text-white/80">
                            Sisa pinjaman berjalan &bull; Bunga 0%
                        </p>
                    </div>
                </div>
                <div class="relative z-10 mt-space-lg flex items-center justify-between pt-space-md">
                    <span class="font-sans text-label-sm uppercase text-white/70">Tenor Maksimal</span>
                    <span class="font-sans text-label-md font-bold text-white">{{ $loan['tenor_total'] }} Bulan</span>
                </div>
            </div>
        </div>

        {{-- Konten utama --}}
        <div class="grid grid-cols-1 items-start gap-space-lg lg:grid-cols-12">
            <div class="flex flex-col gap-space-lg lg:col-span-8">
                <div class="rounded-xl bg-card-bg p-space-xl shadow-card">
                    <div class="mb-space-lg flex flex-col justify-between gap-space-md sm:flex-row sm:items-center">
                        <div>
                            <h2 class="font-display text-headline-md text-slate-text-primary">Riwayat Transaksi &amp; Mutasi Kasbon</h2>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Ledger pemotongan gaji berkala &amp; riwayat pinjaman tunai internal.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[1.125rem] text-slate-text-muted">filter_alt</span>
                                <select x-model="filter" class="appearance-none rounded-lg bg-surface-container-low py-1.5 pl-8 pr-7 font-sans text-body-sm text-slate-text-primary focus:outline-none">
                                    <option value="all">Semua Mutasi ({{ count($mutations) }})</option>
                                    <option value="pencairan">Pencairan Kasir</option>
                                    <option value="potongan">Potongan Payroll</option>
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[1rem] text-slate-text-muted">expand_more</span>
                            </div>
                            <button type="button" class="rounded-lg bg-surface-container-low p-1.5 text-slate-text-secondary transition-colors hover:bg-surface-container hover:text-slate-text-primary" title="Muat Ulang Transaksi" onclick="window.location.reload()">
                                <span class="material-symbols-outlined text-[1.125rem]">refresh</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                                    <th class="px-3 py-3">Tanggal</th>
                                    <th class="px-3 py-3">Kode Transaksi</th>
                                    <th class="px-3 py-3">Jenis Mutasi</th>
                                    <th class="px-3 py-3">Keperluan</th>
                                    <th class="px-3 py-3 text-right">Debit / Kredit</th>
                                    <th class="px-3 py-3 text-right">Sisa Saldo</th>
                                    <th class="px-3 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="font-sans text-body-md">
                                @forelse ($mutations as $mutation)
                                    <tr
                                        class="transition-colors hover:bg-surface-container-low/50"
                                        x-show="filter === 'all' || filter === @js($mutation['filter'])"
                                    >
                                        <td class="whitespace-nowrap px-3 py-3.5">
                                            <span class="font-medium text-slate-text-primary">{{ $mutation['date'] }}</span>
                                            <div class="font-sans text-[0.6875rem] text-slate-text-muted">{{ $mutation['type_date'] }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 font-financial-table text-slate-text-secondary">{{ $mutation['code'] }}</td>
                                        <td class="px-3 py-3.5">
                                            <div class="flex items-center gap-1.5 whitespace-nowrap">
                                                <span @class([
                                                    'h-2 w-2 rounded-full',
                                                    'bg-net-pay-emerald' => $mutation['tone'] === 'emerald',
                                                    'bg-primary' => $mutation['tone'] === 'blue',
                                                    'bg-slate-text-muted' => $mutation['tone'] === 'slate',
                                                ])></span>
                                                <span class="font-medium text-slate-text-primary">{{ $mutation['kind'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3.5 text-slate-text-secondary">{{ $mutation['purpose'] }}</td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-right font-financial-table">
                                            @if ($mutation['credit'] > 0)
                                                <span class="font-bold text-primary">+ {{ $rp($mutation['credit']) }}</span>
                                            @else
                                                <span class="font-bold text-net-pay-emerald">- {{ $rp($mutation['debit']) }}</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-right font-financial-table font-semibold text-slate-text-primary">{{ $rp($mutation['balance']) }}</td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                            <span @class([
                                                ' inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-semibold',
                                                'bg-warning-surface text-warning-amber' => $mutation['tone'] === 'amber',
                                                'bg-net-pay-surface text-net-pay-emerald' => $mutation['tone'] === 'emerald',
                                                'bg-surface-container text-primary' => $mutation['tone'] === 'blue',
                                                'bg-surface-container text-slate-text-secondary' => $mutation['tone'] === 'slate',
                                            ])>
                                                <span class="h-1.5 w-1.5 rounded-full {{ $mutation['tone'] === 'amber' ? 'bg-warning-amber' : ($mutation['tone'] === 'emerald' ? 'bg-net-pay-emerald' : 'bg-primary') }}"></span>
                                                {{ $mutation['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-8 text-center font-sans text-body-sm text-slate-text-muted">
                                            Belum ada mutasi kasbon untuk akun ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-space-md flex flex-col items-center justify-between gap-2 rounded-lg bg-canvas-bg px-3 py-2.5 sm:flex-row">
                        <span class="font-sans text-body-sm text-slate-text-secondary">
                            Menampilkan <span class="font-semibold">1-{{ count($mutations) }}</span> dari <span class="font-semibold">{{ count($mutations) }}</span> mutasi
                        </span>
                        <span class="font-sans text-label-sm text-slate-text-muted">Sumber: Kasir {{ $dataPerusahaan->nama_perusahaan ?? config('app.name') }}</span>
                    </div>
                </div>

                {{-- Simulasi jadwal --}}
                <div class="rounded-xl bg-card-bg p-space-xl shadow-card">
                    <div class="flex items-start justify-between gap-space-md">
                        <div class="space-y-0.5">
                            <h3 class="font-display text-headline-sm text-slate-text-primary">Simulasi Jadwal Pelunasan Pinjaman Aktif</h3>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Estimasi pengembalian saldo piutang hingga pelunasan nihil (Rp 0).</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-surface-container px-2.5 py-1 font-sans text-label-sm font-semibold text-slate-text-secondary">
                            {{ $loan['ada_kasbon'] ? 'TENOR: '.$loan['tenor_total'].' BULAN TOTAL' : 'BELUM ADA KASBON' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-space-md pt-space-xs md:grid-cols-4">
                        @forelse ($schedule as $item)
                            <div @class([
                                'flex flex-col gap-1 rounded-lg p-space-md',
                                'bg-net-pay-surface' => $item['tone'] === 'emerald',
                                'bg-warning-surface' => $item['tone'] === 'amber',
                                'bg-surface-container-low' => $item['tone'] === 'slate' || $item['tone'] === 'primary',
                            ])>
                                <span @class([
                                    ' font-sans text-label-sm font-bold',
                                    'text-net-pay-emerald' => $item['tone'] === 'emerald',
                                    'text-warning-amber' => $item['tone'] === 'amber',
                                    'text-slate-text-secondary' => $item['tone'] === 'slate',
                                    'text-primary' => $item['tone'] === 'primary',
                                ])>{{ $item['period'] }} &bull; {{ $item['state'] }}</span>
                                <span class="font-display text-headline-sm text-slate-text-primary">{{ $rp($item['amount']) }}</span>
                                <span class="font-sans text-body-sm text-slate-text-muted">{{ $item['remain'] }}</span>
                            </div>
                        @empty
                            <div class="col-span-2 rounded-lg bg-surface-container-low p-space-md font-sans text-body-sm text-slate-text-secondary md:col-span-4">
                                Tidak ada jadwal pelunasan karena belum ada kasbon aktif.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Panel samping --}}
            <div class="flex flex-col gap-space-lg lg:col-span-4">

                <div class="rounded-xl bg-card-bg p-space-xl shadow-card">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-space-sm">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container text-slate-text-primary">
                                <span class="material-symbols-outlined text-[1.25rem]">security</span>
                            </div>
                            <div>
                                <h3 class="font-display text-headline-sm text-slate-text-primary">Potongan Wajib Non-Kasbon</h3>
                                <p class="font-sans text-body-sm text-slate-text-muted">Proteksi &amp; Status Rekor Mengemudi</p>
                            </div>
                        </div>
                        <span class="rounded bg-surface-container-low px-2 py-0.5 font-sans text-label-sm font-semibold text-slate-text-secondary">BULANAN</span>
                    </div>

                    <div class="mt-space-md flex flex-col gap-space-sm">
                        <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-space-md">
                            <div class="flex items-center gap-space-sm">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-net-pay-surface text-net-pay-emerald">
                                    <span class="material-symbols-outlined text-[1.125rem]">health_and_safety</span>
                                </div>
                                <div>
                                    <div class="font-sans text-body-md font-semibold text-slate-text-primary">BPJS Ketenagakerjaan</div>
                                    <div class="font-sans text-body-sm text-slate-text-secondary">JKK &amp; JKM Supir Logistik</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-financial-table font-bold text-slate-text-primary">Rp {{ number_format($loan['bpjs'], 0, ',', '.') }}</div>
                                <div class="font-sans text-[0.6875rem] font-semibold text-net-pay-emerald">{{ $loan['bpjs'] > 0 ? 'Dipotong Tiap Periode' : 'Belum Terdaftar' }}</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-net-pay-surface/40 p-space-md">
                            <div class="flex items-center gap-space-sm">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-net-pay-surface text-net-pay-emerald">
                                    <span class="material-symbols-outlined text-[1.125rem]">thumb_up</span>
                                </div>
                                <div>
                                    <div class="font-sans text-body-md font-semibold text-slate-text-primary">Denda Muatan / Insiden</div>
                                    <div class="font-sans text-body-sm font-semibold text-net-pay-emerald">Rekor Mengemudi Bersih &bull; Nihil</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-space-sm rounded-xl bg-sidebar-dark p-space-lg text-on-primary shadow-card">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined text-[1.5rem] text-net-pay-emerald">support_agent</span>
                        <div class="flex flex-col">
                            <span class="font-display text-headline-sm text-white">Bantuan Darurat Kasbon</span>
                            <span class="font-sans text-body-sm text-slate-text-muted">Koordinator Dispatch </span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-white/5 px-3 py-2 font-financial-table text-label-md">0812-7729-1090</div>
                    <button
                        type="button"
                        class="flex items-center justify-center gap-1.5 rounded-lg bg-net-pay-emerald py-2 font-sans text-label-md font-bold text-white transition-all hover:brightness-110"
                        onclick="window.open('https://wa.me/{{ preg_replace('/\D/', '', $dataPerusahaan->nomor_telepon ?? '081277291090') }}', '_blank')"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">chat</span>
                        Chat Koordinator
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal ajukan kasbon --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-sidebar-dark/70 backdrop-blur-sm" @click="modalOpen = false"></div>

            <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-xl bg-surface-container-lowest shadow-drawer">
                <div class="flex items-center justify-between bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep p-space-lg text-on-primary">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined text-[1.5rem]">payments</span>
                        <div>
                            <h3 class="font-display text-headline-sm">Pengajuan Kasbon Internal</h3>
                            <p class="font-sans text-body-sm text-white/80">{{ $dataPerusahaan->nama_perusahaan ?? config('app.name') }} &bull; Maks Rp 1.000.000 (Maks 3x Potong)</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-1 text-white/80 transition-colors hover:bg-white/10 hover:text-white" @click="modalOpen = false">
                        <span class="material-symbols-outlined text-[1.25rem]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('driver.loans.store') }}" class="flex flex-col gap-space-lg p-space-xl">
                    @csrf
                    <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-space-md">
                        <div class="flex flex-col">
                            <span class="font-sans text-label-sm uppercase text-slate-text-secondary">Sisa Pinjaman Saat Ini</span>
                            <span class="font-display text-headline-sm text-primary">{{ $rp($loan['remaining']) }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-2.5 py-1 font-sans text-label-sm font-bold text-net-pay-emerald">
                            <span class="h-1.5 w-1.5 rounded-full bg-net-pay-emerald"></span>SISA KUOTA {{ $rp(max(0, 1000000 - (int) $loan['remaining'])) }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="font-sans text-label-md text-slate-text-primary">Nominal Pengajuan (IDR) <span class="text-danger-red">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-sans text-label-md text-slate-text-muted">Rp</span>
                            <input
                                type="text"
                                name="total_pinjaman"
                                required
                                :value="formatNumber(form.nominal)"
                                @input="form.nominal = parseNumber($event.target.value)"
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-10 pr-3.5 font-financial-table text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-primary-container"
                                placeholder="500.000"
                            >
                        </div>
                        <span class="font-sans text-body-sm text-slate-text-secondary">Min. Rp 100.000 &bull; Maks. Rp 1.000.000</span>
                    </div>

                    <div class="grid grid-cols-2 gap-space-md">
                        <div class="flex flex-col gap-1.5">
                            <label class="font-sans text-label-md text-slate-text-primary">Tenor Angsuran <span class="text-danger-red">*</span></label>
                            <select name="jumlah_tenor" x-model.number="form.tenor" class="w-full rounded-lg bg-surface-container-low px-3.5 py-2.5 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary-container">
                                <option :value="1" value="1">1 Periode (1 Bulan)</option>
                                <option :value="2" value="2">2 Periode (2 Bulan)</option>
                                <option :value="3" value="3">3 Periode (3 Bulan)</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="font-sans text-label-md text-slate-text-primary">Kategori Keperluan</label>
                            <select name="kategori_keperluan" x-model="form.category" class="w-full rounded-lg bg-surface-container-low px-3.5 py-2.5 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary-container">
                                <option value="DARURAT">Kebutuhan Mendesak / Darurat</option>
                                <option value="KELUARGA">Pendidikan &amp; Sekolah Anak</option>
                                <option value="KESEHATAN">Kesehatan &amp; Berobat Keluarga</option>
                                <option value="PERBAIKAN">Perbaikan Rumah / Logistik</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="font-sans text-label-md text-slate-text-primary">Alasan Spesifik / Catatan Lapangan <span class="text-danger-red">*</span></label>
                        <textarea name="keterangan" x-model="form.note" required maxlength="150" rows="2" class="w-full rounded-lg bg-surface-container-low p-3 font-sans text-body-md text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-primary-container" placeholder="Tuliskan keterangan detail kebutuhan..."></textarea>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="password_kasbon_supir" class="font-sans text-label-md text-slate-text-primary">Kata Sandi Akun Supir <span class="text-danger-red">*</span></label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">lock</span>
                            <input
                                id="password_kasbon_supir"
                                name="password"
                                type="password"
                                required
                                class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Masukkan kata sandi akun Anda..."
                            >
                        </div>
                        <p class="font-sans text-xs text-slate-text-muted">Verifikasi kata sandi wajib diisi untuk mengesahkan pengajuan kasbon.</p>
                    </div>


                    <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-3">
                        <span class="font-sans text-body-sm text-slate-text-secondary">Estimasi potongan per periode</span>
                        <span class="font-display text-headline-sm font-bold tabular-nums text-primary" x-text="formatRp(monthly)"></span>
                    </div>

                    <div class="flex items-start gap-space-sm rounded-lg bg-warning-surface/60 p-space-sm">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-[1.25rem] text-warning-amber">info</span>
                        <p class="font-sans text-body-sm leading-tight text-slate-text-secondary">
                            Pengajuan kasbon otomatis membuat jadwal cicilan dan langsung tercatat pada buku kasbon serta rekapan penggajian periode berjalan.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-space-md pt-space-xs">
                        <button type="button" class="rounded-lg bg-surface-container-low px-space-lg py-2.5 font-sans text-label-md text-slate-text-primary transition-colors hover:bg-surface-container" @click="modalOpen = false">Batal</button>
                        <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-label-md text-on-primary shadow-md transition-all hover:bg-primary active:scale-95">
                            <span class="material-symbols-outlined text-[1.125rem]">send</span>
                            Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-driver-layout>
