@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $subtotalTonnage = collect($rows)->sum(fn ($row) => (float) str_replace(',', '.', $row['tonnage']));
@endphp

<x-executive-layout title="Monitoring Pengantaran">
    <div class="flex flex-col gap-5" x-data="executiveTrips(@js($rows), @js($summary), @js($overview))">
        <div class="flex flex-col justify-between gap-space-md lg:flex-row lg:items-center">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 font-sans text-label-sm text-slate-text-secondary">
                    <span>Dashboard</span>
                    <span class="material-symbols-outlined text-[0.8125rem] text-slate-text-muted">chevron_right</span>
                    <span class="font-semibold text-slate-text-primary">Monitoring Pengantaran</span>
                </div>
                <h1 class="font-display text-headline-lg tracking-tight text-slate-text-primary">Monitoring Pengantaran Harian</h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Pemantauan surat jalan, tonase timbangan, dan uang jalan. Input data tetap dilakukan Admin/HRD.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg bg-card-bg px-3.5 py-2 font-sans text-body-sm font-semibold text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container"
                    onclick="window.print()"
                >
                    <span class="material-symbols-outlined text-[1.125rem] text-net-pay-emerald">file_download</span>
                    Cetak Monitoring
                </button>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep p-space-lg text-on-primary shadow-hero">
                <span class="font-sans text-label-sm uppercase tracking-wider">Total Pengantaran</span>
                <div class="font-display text-financial-total font-bold">{{ $overview['rit'] }} <span class="font-sans text-label-md font-normal text-white/80">Trip</span></div>
                <span class="font-sans text-body-sm text-white/80">Periode {{ $period['code'] }}</span>
            </div>
            <div class="rounded-xl bg-card-bg p-space-lg shadow-card">
                <span class="font-sans text-label-sm uppercase tracking-wider">Total Tonase</span>
                <div class="font-display text-financial-total font-bold text-slate-text-primary">{{ $overview['tonnage'] }} <span class="font-sans text-label-md font-normal text-slate-text-secondary">Ton</span></div>
                <span class="font-sans text-body-sm text-slate-text-secondary">{{ $overview['target_pct'] }} dari target kontrak</span>
            </div>
            <div class="rounded-xl bg-card-bg p-space-lg shadow-card">
                <span class="font-sans text-label-sm uppercase tracking-wider">Nilai Angkutan</span>
                <div class="font-display text-financial-total font-bold tabular-nums text-primary">{{ $rp($overview['omzet']) }}</div>
                <span class="font-sans text-body-sm text-slate-text-secondary">Omzet bruto pengantaran</span>
            </div>
            <div class="rounded-xl bg-card-bg p-space-lg shadow-card">
                <span class="font-sans text-label-sm uppercase tracking-wider">Uang Jalan Cair</span>
                <div class="font-display text-financial-total font-bold tabular-nums text-net-pay-emerald">{{ $rp($summary['trip_money']) }}</div>
                <span class="font-sans text-body-sm text-slate-text-secondary">{{ count($rows) }} surat jalan pada periode ini</span>
            </div>
        </div>
        {{-- Bar Chart Harian Pengantaran & Tonase (Native SVG) --}}
        @php
            $daftarGrafik = $rekapHarian ?? [];
            $jumlahHari = count($daftarGrafik);
            $maxTonase = max(1, (float) collect($daftarGrafik)->max('total_tonase'));
            $maxRit = max(1, (int) collect($daftarGrafik)->max('total_rit'));
            $lebarSvg = max(760, $jumlahHari * 32);
            $tinggiSvg = 220;
            $marginBawah = 32;
            $marginAtas = 20;
            $tinggiPlot = $tinggiSvg - $marginBawah - $marginAtas;
            $lebarSlot = $jumlahHari > 0 ? ($lebarSvg - 40) / $jumlahHari : 30;
        @endphp
        <div class="rounded-xl bg-card-bg p-space-lg shadow-card">
            <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center border-b border-card-border pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-banner-indigo/10 text-banner-indigo">
                            <span class="material-symbols-outlined text-[1.25rem]">bar_chart</span>
                        </span>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Tren Harian Pengantaran &amp; Tonase Batubara</h2>
                    </div>
                    <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">
                        Grafik harian {{ $jumlahHari }} tanggal aktif pengantaran pada periode {{ $period['code'] }}.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs font-sans">
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm bg-banner-indigo"></span>
                        <span class="font-semibold text-slate-text-primary">Tonase Muatan (Ton)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm bg-net-pay-emerald"></span>
                        <span class="font-semibold text-slate-text-primary">Total Rit (Pengantaran)</span>
                    </div>
                    <div class="rounded-lg bg-surface-container-low px-2.5 py-1 text-slate-text-secondary font-medium">
                        Puncak: <strong class="text-slate-text-primary">{{ number_format($maxTonase, 1, ',', '.') }} Ton</strong>
                    </div>
                </div>
            </div>

            @if ($jumlahHari === 0)
                <div class="py-12 text-center font-sans text-body-sm text-slate-text-muted">
                    Belum ada data pengantaran pada rentang atau periode ini.
                </div>
            @else
                <div class="overflow-x-auto pb-2">
                    <svg
                        viewBox="0 0 {{ $lebarSvg }} {{ $tinggiSvg }}"
                        class="w-full min-w-[720px] h-56"
                        xmlns="http://www.w3.org/2000/svg"
                        role="img"
                        aria-label="Grafik Harian Pengantaran dan Tonase"
                    >
                        {{-- Garis Panduan Grid Horizontal --}}
                        @for ($i = 0; $i <= 4; $i++)
                            @php
                                $yGrid = $marginAtas + ($tinggiPlot * (1 - $i / 4));
                                $nilaiGridTon = round($maxTonase * ($i / 4));
                            @endphp
                            <line x1="30" y1="{{ $yGrid }}" x2="{{ $lebarSvg - 10 }}" y2="{{ $yGrid }}" stroke="currentColor" stroke-opacity="0.08" stroke-dasharray="3,3" />
                            <text x="25" y="{{ $yGrid + 3 }}" font-size="9" fill="currentColor" opacity="0.45" text-anchor="end" font-family="sans-serif">{{ $nilaiGridTon }}</text>
                        @endfor

                        {{-- Bar Harian --}}
                        @foreach ($daftarGrafik as $idx => $item)
                            @php
                                $xPusat = 40 + ($idx * $lebarSlot) + ($lebarSlot / 2);
                                $tinggiBarTon = max(4, ($item['total_tonase'] / $maxTonase) * $tinggiPlot);
                                $yBarTon = $marginAtas + $tinggiPlot - $tinggiBarTon;
                                $lebarBatang = max(8, min(14, $lebarSlot * 0.35));

                                $tinggiBarRit = max(4, ($item['total_rit'] / $maxRit) * $tinggiPlot);
                                $yBarRit = $marginAtas + $tinggiPlot - $tinggiBarRit;
                            @endphp
                            <g class="cursor-pointer group" tabindex="0">
                                <title>{{ $item['label'] }}: {{ $item['total_tonase'] }} Ton &bull; {{ $item['total_rit'] }} Rit ({{ $item['jumlah_supir'] }} supir)</title>

                                {{-- Batang Tonase (Indigo) --}}
                                <rect
                                    x="{{ $xPusat - $lebarBatang - 1 }}"
                                    y="{{ $yBarTon }}"
                                    width="{{ $lebarBatang }}"
                                    height="{{ $tinggiBarTon }}"
                                    rx="2"
                                    fill="#4F46E5"
                                    class="transition-opacity hover:opacity-80"
                                />

                                {{-- Batang Rit (Emerald) --}}
                                <rect
                                    x="{{ $xPusat + 1 }}"
                                    y="{{ $yBarRit }}"
                                    width="{{ $lebarBatang }}"
                                    height="{{ $tinggiBarRit }}"
                                    rx="2"
                                    fill="#059669"
                                    class="transition-opacity hover:opacity-80"
                                />

                                {{-- Label Tanggal X-Axis --}}
                                <text
                                    x="{{ $xPusat }}"
                                    y="{{ $tinggiSvg - 10 }}"
                                    font-size="10"
                                    font-weight="600"
                                    fill="currentColor"
                                    opacity="0.75"
                                    text-anchor="middle"
                                    font-family="sans-serif"
                                >{{ $item['hari'] }}</text>
                            </g>
                        @endforeach
                    </svg>
                </div>
            @endif
        </div>

        {{-- Filter --}}
        <form method="GET" action="{{ route('executive.trips') }}" data-filter-server class="flex flex-col items-stretch justify-between gap-space-md rounded-xl bg-card-bg p-space-md shadow-card lg:flex-row lg:items-end">
            <input type="hidden" name="periode" value="{{ $period['code'] }}">
            <div class="flex flex-1 flex-col gap-space-md sm:flex-row sm:flex-wrap">
                <div class="flex min-w-[11rem] flex-col">
                    <label for="filter_tanggal_dari" class="mb-1 font-sans text-label-sm uppercase tracking-wider">Tanggal Dari</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">event</span>
                        <input
                            type="date"
                            id="filter_tanggal_dari"
                            name="tanggal_dari"
                            value="{{ $tanggalDari }}"
                            data-filter-submit
                            class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-3 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >
                    </div>
                </div>

                <div class="flex min-w-[11rem] flex-col">
                    <label for="filter_tanggal_sampai" class="mb-1 font-sans text-label-sm uppercase tracking-wider">Tanggal Sampai</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">event_available</span>
                        <input
                            type="date"
                            id="filter_tanggal_sampai"
                            name="tanggal_sampai"
                            value="{{ $tanggalSampai }}"
                            data-filter-submit
                            class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-3 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active"
                        >
                    </div>
                </div>

                <div class="flex min-w-[13.75rem] flex-col">
                    <label for="filter_supir" class="mb-1 font-sans text-label-sm uppercase tracking-wider">Filter Supir</label>
                    <select id="filter_supir" x-model="driver" class="h-10 w-full rounded-lg bg-surface-container-low px-3 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                        <option value="all">Semua Supir ({{ count($rows) }})</option>
                        @foreach (collect($rows)->pluck('driver')->unique()->sort()->values() as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex min-w-[13.75rem] flex-col">
                    <label for="filter_rute" class="mb-1 font-sans text-label-sm uppercase tracking-wider">Filter Rute</label>
                    <select id="filter_rute" x-model="route" class="h-10 w-full rounded-lg bg-surface-container-low px-3 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                        <option value="all">Semua Rute</option>
                        @foreach (collect($rows)->pluck('route')->unique()->sort()->values() as $kodeRute)
                            <option value="{{ $kodeRute }}">{{ $kodeRute }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex min-w-[13.75rem] flex-1 flex-col">
                    <label for="filter_cari" class="mb-1 font-sans text-label-sm uppercase tracking-wider">Cari Surat Jalan</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">search</span>
                        <input type="text" id="filter_cari" x-model="search" class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-4 font-sans text-body-sm text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-sidebar-active" placeholder="SJ-MIP-09821">
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($modeRentangTanggal)
                    <a href="{{ route('executive.trips', ['periode' => $period['code']]) }}" class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 font-sans text-body-sm font-semibold text-rose-700 transition-colors hover:bg-rose-100">
                        <span class="material-symbols-outlined text-[1rem]">restart_alt</span>
                        Reset Tanggal
                    </a>
                @endif
                <button type="submit" class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-primary-container px-4 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-colors hover:bg-primary">
                    <span class="material-symbols-outlined text-[1.125rem]">filter_alt</span>
                    Terapkan
                </button>
            </div>
        </form>

        @if ($modeRentangTanggal)
            <div class="flex items-start gap-2 rounded-xl border border-banner-indigo/20 bg-banner-indigo/5 px-4 py-3">
                <span class="material-symbols-outlined text-[1.125rem] text-banner-indigo">info</span>
                <p class="font-sans text-body-sm text-slate-text-secondary">
                    Menampilkan data berdasarkan rentang tanggal
                    <strong class="text-slate-text-primary">{{ $tanggalDari ?? 'awal' }}</strong> s/d
                    <strong class="text-slate-text-primary">{{ $tanggalSampai ?? 'akhir' }}</strong>,
                    di luar periode {{ $period['code'] }}. Bila tidak ada pengantaran pada tanggal tersebut, tabel akan kosong.
                </p>
            </div>
        @endif

        {{-- Tabel --}}
        <div class="flex flex-col overflow-hidden rounded-xl bg-card-bg shadow-card">
            <div class="w-full overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                            <th class="px-4 py-3.5 font-semibold">Tanggal</th>
                            <th class="px-4 py-3.5 font-semibold">No. Pol &amp; Supir</th>
                            <th class="px-3 py-3.5 text-center font-semibold">Material</th>
                            <th class="px-3 py-3.5 text-center font-semibold">Rit</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Tonase</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Tarif / Ton</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Total Angkutan</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Uang Jalan</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Rute</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-surface-container-low font-financial-table">
                        @forelse ($rows as $row)
                            <tr
                                class="transition-colors hover:bg-surface-container-low/70"
                                x-show="rowVisible(@js($row['driver']), @js($row['sj']), @js($row['route']))"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-semibold">{{ $row['date'] }}</span>
                                        <span class="font-sans text-label-sm">{{ $row['sj'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-bold">{{ $row['driver'] }}</span>
                                        <span class="font-sans text-label-sm">{{ $row['plate'] }} &bull; {{ $row['truck'] }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-block rounded bg-surface-container-high px-2 py-0.5 font-sans text-label-sm font-bold text-banner-deep">{{ $row['material'] }}</span>
                                </td>
                                <td class="px-3 py-3 text-center font-bold">{{ $row['rit'] }} Rit</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $row['tonnage'] }} Ton</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $rp($row['rate']) }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums text-primary">{{ $rp($row['total']) }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums text-net-pay-emerald">{{ $rp($row['trip_money']) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block rounded bg-primary-fixed px-2 py-0.5 font-sans text-label-sm font-semibold text-on-primary-fixed">{{ $row['route'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center font-sans text-body-sm text-slate-text-muted">
                                    Belum ada surat jalan pada periode {{ $period['code'] }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="bg-surface-container font-display text-headline-sm text-slate-text-primary">
                            <td class="px-4 py-3.5 font-bold" colspan="3">Subtotal {{ count($rows) }} Rit Ditampilkan</td>
                            <td class="px-3 py-3.5 text-center font-bold">{{ collect($rows)->sum('rit') }} Rit</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-primary">{{ number_format($subtotalTonnage, 2, ',', '.') }} Ton</td>
                            <td class="px-4 py-3.5 text-right font-sans text-label-sm font-normal text-slate-text-secondary">-</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-primary">{{ $rp(collect($rows)->sum('total')) }}</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-net-pay-emerald">{{ $rp(collect($rows)->sum('trip_money')) }}</td>
                            <td class="px-4 py-3.5 text-center font-sans text-label-sm font-normal text-slate-text-secondary">Akumulasi</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex flex-col items-center justify-between gap-2 bg-surface-container-low px-space-md py-3 sm:flex-row">
                <span class="font-sans text-body-sm text-slate-text-secondary">
                    Menampilkan <strong class="text-slate-text-primary">{{ count($rows) }}</strong> dari <strong class="text-slate-text-primary">{{ $summary['rit'] }}</strong> ritase pada periode {{ $period['code'] }}
                </span>
                <a
                    href="{{ route('executive.reports.index', ['periode' => $period['code']]) }}"
                    class="flex items-center gap-1 font-sans text-body-sm font-semibold text-banner-indigo hover:underline"
                >
                    Lihat rekapitulasi finansial
                    <span class="material-symbols-outlined text-[1.125rem]">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>
</x-executive-layout>
