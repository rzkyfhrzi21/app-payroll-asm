<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN DASHBOARD UTAMA ADMIN HRD):
     - Tujuan Halaman: Pusat kendali operasional harian Admin HRD: menampilkan status kesiapan
       armada, supir yang belum dapat truk, antrean verifikasi surat jalan, dan ringkasan kasbon.
     - Analogi Dunia Nyata: Seperti papan pantau di dinding ruang Admin Logistik: melihat berapa
       truk yang siap jalan, supir mana yang sedang nganggur, surat jalan mana yang menunggu dicek,
       dan pengingat tutup buku gaji yang akan datang.
     - Data dari Controller (Admin/DashboardController@index):
       * $ringkasanArmada: Total truk, truk aktif, supir aktif, dan unit tanpa pasangan.
       * $antreanTindakan: Daftar hal mendesak yang butuh tindakan Admin (verifikasi SJ, kasbon baru).
       * $trenBulanan: Data grafik tren ritase dan pengeluaran beberapa bulan terakhir.
     ============================================================================== -->
@php
    $rp = fn ($nilai) => 'Rp '.number_format((int) $nilai, 0, ',', '.');
    $totalSupir = max(1, (int) $ringkasanArmada['supir_total']);
    $persenSupirAktif = (int) round(($ringkasanArmada['supir_aktif'] / $totalSupir) * 100);
    $totalArmada = max(1, (int) $ringkasanArmada['total']);
    $persenArmadaAktif = (int) round(($ringkasanArmada['aktif'] / $totalArmada) * 100);

    // Tiga pasangan batang: (aktif vs nonaktif) untuk supir, untuk unit,
    // lalu pasangan masalah penugasan (supir tanpa unit vs unit tanpa supir).
    $pasanganKesiapan = [
        [
            'judul' => 'Supir',
            'keterangan' => $ringkasanArmada['supir_total'].' personel terdaftar',
            'kiri' => [
                'label' => 'Aktif',
                'nilai' => (int) $ringkasanArmada['supir_aktif'],
                'kelas' => 'bg-net-pay-emerald',
                'teks' => 'text-net-pay-emerald',
            ],
            'kanan' => [
                'label' => 'Nonaktif',
                'nilai' => (int) $ringkasanArmada['supir_nonaktif'],
                'kelas' => 'bg-slate-text-muted',
                'teks' => 'text-slate-text-muted',
            ],
        ],
        [
            'judul' => 'Unit Armada',
            'keterangan' => $ringkasanArmada['total'].' unit terdaftar',
            'kiri' => [
                'label' => 'Aktif',
                'nilai' => (int) $ringkasanArmada['aktif'],
                'kelas' => 'bg-primary',
                'teks' => 'text-primary',
            ],
            'kanan' => [
                'label' => 'Nonaktif',
                'nilai' => (int) $ringkasanArmada['nonaktif'],
                'kelas' => 'bg-slate-text-muted',
                'teks' => 'text-slate-text-muted',
            ],
        ],
        [
            'judul' => 'Penugasan',
            'keterangan' => 'Yang belum berpasangan',
            'kiri' => [
                'label' => 'Supir tanpa unit',
                'nilai' => (int) $ringkasanArmada['supir_tanpa_unit'],
                'kelas' => 'bg-warning-amber',
                'teks' => 'text-warning-amber',
            ],
            'kanan' => [
                'label' => 'Unit tanpa supir',
                'nilai' => (int) $ringkasanArmada['tanpa_supir'],
                'kelas' => 'bg-danger-red',
                'teks' => 'text-danger-red',
            ],
        ],
    ];

    // Skala tinggi batang disamakan agar perbandingan antar pasangan fair.
    $nilaiPuncakArmada = max(
        1,
        collect($pasanganKesiapan)
            ->flatMap(fn (array $pasangan) => [$pasangan['kiri']['nilai'], $pasangan['kanan']['nilai']])
            ->max()
    );

    foreach ($pasanganKesiapan as $indeks => $pasangan) {
        foreach (['kiri', 'kanan'] as $sisi) {
            $nilai = (int) $pasangan[$sisi]['nilai'];
            $pasanganKesiapan[$indeks][$sisi]['tinggi'] = $nilai > 0
                ? max(6, (int) round(($nilai / $nilaiPuncakArmada) * 100))
                : 0;
        }
    }
@endphp

<x-app-layout title="Dashboard Admin">
    <div class="flex flex-col gap-6">
        {{-- Kepala halaman --}}
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div class="flex flex-col gap-1">
                <p class="font-sans text-label-md font-semibold uppercase tracking-wider text-primary">Ringkasan Hari Ini</p>
                <h1 class="font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">
                    Selamat datang  , {{ auth()->user()->name ?? 'Admin HRD' }}
                </h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Periode aktif <strong class="text-slate-text-primary">{{ $period['code'] ?? '—' }}</strong>
                    &bull; {{ $period['range'] ?? 'belum ada periode' }}
                </p>
            </div>

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

            <form method="GET" action="{{ route('admin.dashboard') }}" data-filter-server class="flex items-end gap-2">
                <div class="flex flex-col">
                    <label for="periode" class="mb-1 font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">Periode</label>
                    <div class="relative">
                        <select
                            id="periode"
                            name="periode"
                            data-filter-submit
                            onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()"
                            class="h-10 min-w-[13rem] cursor-pointer appearance-none rounded-lg border border-card-border bg-card-bg pl-3 pr-10 font-sans text-body-md text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            @foreach ($periodOptions as $opsi)
                                <option value="{{ $opsi['kode'] }}" @selected($opsi['kode'] === ($period['code'] ?? ''))>{{ $opsi['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-text-muted">
                            <span class="material-symbols-outlined text-[1.25rem]">expand_more</span>
                        </span>
                    </div>
                </div>
            </form>
        </div>

        {{-- Statistik utama: baris penuh --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-tile
                label="Gaji Bersih Periode Saat Ini"
                :nilai="$rp($ringkasanPayroll['cash_required'])"
                ikon="payments"
                tone="primary"
                :catatan="$ringkasanPayroll['drivers'].' supir dalam perhitungan periode '.$period['code']"
            />
            <x-ui.stat-tile
                label="Total Pengantaran Periode Ini"
                :nilai="number_format((int) $ringkasanPayroll['rit'], 0, ',', '.')"
                satuan="rit"
                ikon="local_shipping"
                tone="emerald"
                :catatan="number_format((int) $ringkasanPayroll['tonnage'], 0, ',', '.').' ton terangkut pada periode '.$period['code']"
            />
            <x-ui.stat-tile
                label="Sisa Kasbon Saat Ini"
                :nilai="$rp($ringkasanKasbon['outstanding'])"
                ikon="account_balance_wallet"
                tone="amber"
                :catatan="$ringkasanKasbon['active_drivers'].' supir punya kasbon berjalan'"
            />
            <x-ui.stat-tile
                label="Pengeluaran Periode Saat Ini"
                :nilai="$rp($ringkasanPengeluaran['total'])"
                ikon="receipt_long"
                tone="slate"
                :catatan="$ringkasanPengeluaran['jumlah'].' item tercatat, '.$ringkasanPengeluaran['draft'].' masih draf'"
            />
        </div>

        {{-- Kesiapan operasional: baris tersendiri di bawah statistik (SVG/CSS native, tanpa library chart) --}}
        <div class="rounded-xl bg-surface-container-lowest p-5 shadow-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Kesiapan Operasional</h2>
                        <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">Supir dan unit yang siap bertugas.</p>
                    </div>
                    <span class="material-symbols-outlined text-[1.5rem] text-slate-text-muted">bar_chart</span>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3" role="img" aria-label="Diagram batang kesiapan operasional">
                    @foreach ($pasanganKesiapan as $pasangan)
                        <div class="flex min-w-0 flex-col gap-2 rounded-lg bg-surface-container-low/40 p-3">
                            {{-- Angka tiap batang di atas --}}
                            <div class="flex items-end justify-center gap-3">
                                @foreach (['kiri', 'kanan'] as $sisi)
                                    <span class="font-sans text-body-md font-bold tabular-nums {{ $pasangan[$sisi]['teks'] }}">
                                        {{ $pasangan[$sisi]['nilai'] }}
                                    </span>
                                @endforeach
                            </div>

                            {{-- Dua batang berdampingan --}}
                            <div class="flex h-32 items-end justify-center gap-2">
                                @foreach (['kiri', 'kanan'] as $sisi)
                                    <div
                                        class="flex h-full w-full max-w-[3.25rem] items-end overflow-hidden rounded-md bg-surface-container-low"
                                        title="{{ $pasangan[$sisi]['label'] }}: {{ $pasangan[$sisi]['nilai'] }}"
                                    >
                                        <div
                                            class="w-full rounded-t-md {{ $pasangan[$sisi]['kelas'] }}"
                                            style="height: {{ $pasangan[$sisi]['tinggi'] }}%"
                                        ></div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Legenda pasangan --}}
                            <div class="flex flex-col items-center gap-0.5 text-center">
                                <span class="font-sans text-label-sm font-bold leading-tight text-slate-text-primary">{{ $pasangan['judul'] }}</span>
                                <span class="font-sans text-[10px] leading-tight text-slate-text-muted">{{ $pasangan['keterangan'] }}</span>
                            </div>

                            <div class="mt-0.5 flex flex-col gap-0.5 border-t border-card-border pt-1.5">
                                @foreach (['kiri', 'kanan'] as $sisi)
                                    <span class="flex items-center gap-1 font-sans text-[10px] leading-tight text-slate-text-secondary">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $pasangan[$sisi]['kelas'] }}"></span>
                                        {{ $pasangan[$sisi]['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 border-t border-card-border pt-3">
                    <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary">
                        <span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span> Supir aktif {{ $persenSupirAktif }}%
                    </span>
                    <span class="flex items-center gap-1.5 font-sans text-label-sm text-slate-text-secondary">
                        <span class="h-2 w-2 rounded-full bg-primary"></span> Unit aktif {{ $persenArmadaAktif }}%
                    </span>
                </div>
            </div>

        {{-- Akses cepat --}}
        <div class="rounded-xl bg-surface-container-lowest p-5 shadow-card">
            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Akses Cepat</h2>
            <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">Pintasan menu yang sering dipakai.</p>

            <div class="mt-3 grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['ikon' => 'calculate', 'label' => 'Hitung Gaji Periode', 'rute' => route('admin.payroll.index', ['periode' => $period['code']])],
                    ['ikon' => 'add_circle', 'label' => 'Buat Surat Jalan', 'rute' => route('admin.operational.trips.index')],
                    ['ikon' => 'badge', 'label' => 'Data Supir', 'rute' => route('admin.master.employees.index')],
                    ['ikon' => 'receipt_long', 'label' => 'Laporan & Slip Gaji', 'rute' => route('admin.reports.index')],
                ] as $akses)
                    <a
                        href="{{ $akses['rute'] }}"
                        class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 font-sans text-body-md font-medium text-slate-text-primary transition-colors hover:bg-surface-container-low"
                    >
                        <span class="material-symbols-outlined text-[1.125rem] text-primary">{{ $akses['ikon'] }}</span>
                        <span>{{ $akses['label'] }}</span>
                        <span class="material-symbols-outlined ml-auto text-[1rem] text-slate-text-muted">chevron_right</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Aktivitas pengantaran terakhir --}}
        <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-card">
            <div class="flex items-center justify-between border-b border-card-border px-5 py-4">
                <div>
                    <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Pengantaran Terakhir</h2>
                    <p class="font-sans text-body-sm text-slate-text-secondary">Lima surat jalan terbaru di seluruh periode.</p>
                </div>
                <a href="{{ route('admin.operational.trips.index') }}" class="font-sans text-label-md font-semibold text-primary hover:underline">Lihat semua</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary">
                            <th class="px-5 py-2.5 font-semibold">Tanggal</th>
                            <th class="px-5 py-2.5 font-semibold">Supir</th>
                            <th class="px-5 py-2.5 font-semibold">Unit &amp; Rute</th>
                            <th class="px-5 py-2.5 text-right font-semibold">Tonase</th>
                            <th class="px-5 py-2.5 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-card-border font-sans text-financial-table">
                        @forelse ($aktivitasTerbaru as $aktivitas)
                            <tr class="transition-colors hover:bg-surface-container-low/60">
                                <td class="whitespace-nowrap px-5 py-3 text-slate-text-secondary">{{ $aktivitas['tanggal'] }}</td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-text-primary">{{ $aktivitas['supir'] }}</p>
                                    <p class="font-sans text-label-sm text-slate-text-muted">{{ $aktivitas['nip'] }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold tabular-nums text-slate-text-primary">{{ $aktivitas['plat'] }}</p>
                                    <p class="font-sans text-label-sm text-slate-text-muted">{{ $aktivitas['rute'] }}</p>
                                </td>
                                <td class="px-5 py-3 text-right font-bold tabular-nums text-slate-text-primary">{{ $aktivitas['tonase'] }} t</td>
                                <td class="px-5 py-3 text-center">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 font-sans text-label-sm font-bold',
                                        'bg-net-pay-surface text-net-pay-emerald' => $aktivitas['status'] === 'terverifikasi',
                                        'bg-warning-surface text-warning-amber' => $aktivitas['status'] === 'draft',
                                        'bg-danger-surface text-danger-red' => $aktivitas['status'] === 'dibatalkan',
                                    ])>{{ ucfirst($aktivitas['status']) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center font-sans text-body-sm text-slate-text-muted">
                                    Belum ada pengantaran tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
