<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN RIWAYAT PENGANTARAN & SURAT JALAN SUPIR):
     - Tujuan Halaman: Catatan riwayat seluruh surat jalan (DO) yang pernah dijalankan oleh supir,
       status perjalanan (Terbit, Sedang Dikirim, Menunggu Verifikasi, Terverifikasi), dan uang jalan.
     - Analogi Dunia Nyata: Seperti buku saku jalan supir di dalam kabin truk: supir mencatat
       setiap keberangkatan, menekan tombol "Mulai Kirim" saat berangkat, dan mengunggah foto bukti
       surat jalan bertanda tangan saat tiba di pelabuhan/pabrik tujuan.
     - Alur Status di Halaman Ini:
       * `terbit`: Tombol "Mulai Kirim" aktif.
       * `sedang_dikirim`: Tombol "Konfirmasi Sampai" aktif (wajib upload foto & nama penerima).
       * `menunggu_verifikasi`: Menunggu pemeriksaan oleh Admin HRD di kantor.
       * `terverifikasi`: Selesai dan otomatis masuk hitungan gaji!
     - Data dari Controller (Driver/TripHistoryController@index):
       * $trips: Daftar surat jalan supir dari tabel `ritase`.
       * $payroll: Ringkasan total rit, tonase timbangan, dan uang jalan periode ini.
     ============================================================================== -->
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $subtotalTonnage = collect($trips)->sum(fn ($trip) => (float) str_replace(',', '.', $trip['tonnage']));
@endphp

<x-driver-layout title="Riwayat Pengantaran & DO">
    <div class="flex flex-col gap-5" x-data="driverTrips(@js($trips), @js($profile), @js($payroll))">
        <div class="flex flex-col justify-between gap-space-md lg:flex-row lg:items-center">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 font-sans text-label-sm text-slate-text-secondary">
                    <span>Portal Supir</span>
                    <span class="material-symbols-outlined text-[0.8125rem] text-slate-text-muted">chevron_right</span>
                    <span class="font-semibold text-slate-text-primary">Riwayat Pengantaran &amp; DO</span>
                </div>
                <h1 class="font-display text-headline-lg tracking-tight text-slate-text-primary">Riwayat Pengantaran &amp; Surat Jalan</h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Catatan pengantaran batubara, tonase timbangan, dan uang jalan yang Anda terima per periode cut-off.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 rounded-lg bg-card-bg px-3 py-2 shadow-sm">
                    <span class="material-symbols-outlined text-[1.125rem] text-banner-indigo">calendar_month</span>
                    <select class="bg-transparent font-sans text-body-sm font-medium text-slate-text-primary focus:outline-none">
                        @foreach ($period_options as $opsiPeriode)
                            <option @selected($opsiPeriode['kode'] === $period['code'])>{{ $opsiPeriode['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <a
                    href="{{ route('driver.payroll.slip') }}"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-2 rounded-lg bg-primary-container px-3.5 py-2 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-all hover:bg-primary"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">file_download</span>
                    Unduh Rekap &amp; Slip
                </a>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep p-space-lg text-on-primary shadow-hero">
                <span class="font-sans text-label-sm uppercase tracking-wider">Total Pengantaran</span>
                <div class="font-display text-financial-total font-bold">{{ $payroll['rit'] }} <span class="font-sans text-label-md font-normal text-white/80">Trip</span></div>
                <span class="font-sans text-body-sm text-white/80">{{ count($trips) }} DO tercatat pada periode ini</span>
            </div>
            <div class="rounded-xl bg-card-bg p-space-lg shadow-card">
                <span class="font-sans text-label-sm uppercase tracking-wider">Total Tonase</span>
                <div class="font-display text-financial-total font-bold text-slate-text-primary">{{ $payroll['tonnage'] }} <span class="font-sans text-label-md font-normal text-slate-text-secondary">Ton</span></div>
                <span class="font-sans text-body-sm text-slate-text-secondary">Sesuai bukti surat jalan</span>
            </div>
            <div class="rounded-xl bg-card-bg p-space-lg shadow-card">
                <span class="font-sans text-label-sm uppercase tracking-wider">Uang Jalan Diterima</span>
                <div class="font-display text-financial-total font-bold tabular-nums text-net-pay-emerald">{{ $rp($payroll['trip_money']) }}</div>
                <span class="font-sans text-body-sm text-slate-text-secondary">Voucher SPBU &amp; kasir lapangan</span>
            </div>
            <div class="rounded-xl bg-card-bg p-space-lg shadow-card">
                <span class="font-sans text-label-sm uppercase tracking-wider">Unit Armada</span>
                <div class="font-display text-headline-md font-bold text-slate-text-primary">{{ $profile['plate'] }}</div>
                <span class="font-sans text-body-sm text-slate-text-secondary">{{ $profile['truck'] }}</span>
            </div>
        </div>

        {{-- Filter --}}
        <div class="flex flex-col items-stretch justify-between gap-space-md rounded-xl bg-card-bg p-space-md shadow-card lg:flex-row lg:items-end">
            <div class="flex flex-1 flex-col gap-space-md sm:flex-row sm:flex-wrap">
                <div class="flex min-w-[12.5rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm uppercase tracking-wider">Filter Rute</label>
                    <select x-model="route" class="h-10 w-full rounded-lg bg-surface-container-low px-3 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                        <option value="all">Semua Rute</option>
                        @foreach (collect($trips)->pluck('route')->unique()->sort() as $kodeRute)
                            <option value="{{ $kodeRute }}">{{ $kodeRute }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex min-w-[12.5rem] flex-col">
                    <label class="mb-1 font-sans text-label-sm uppercase tracking-wider">Status Validasi</label>
                    <select x-model="status" class="h-10 w-full rounded-lg bg-surface-container-low px-3 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar-active">
                        <option value="all">Semua Status</option>
                        <option value="terbit">Terbit (Siap Dikirim)</option>
                        <option value="sedang_dikirim">Sedang Dikirim</option>
                        <option value="menunggu_verifikasi">Menunggu Verifikasi Admin</option>
                        <option value="valid">Terverifikasi</option>
                        <option value="pending">Perlu Klarifikasi</option>
                    </select>
                </div>
                <div class="flex min-w-[13.75rem] flex-1 flex-col">
                    <label class="mb-1 font-sans text-label-sm uppercase tracking-wider">Cari Surat Jalan</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-secondary">search</span>
                        <input type="text" x-model="search" class="h-10 w-full rounded-lg bg-surface-container-low pl-9 pr-4 font-sans text-body-sm text-slate-text-primary placeholder:text-slate-text-muted focus:outline-none focus:ring-2 focus:ring-sidebar-active" placeholder="SJ-MIP-10012">
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="flex flex-col overflow-hidden rounded-xl bg-card-bg shadow-card">
            <div class="w-full overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-sans text-label-sm uppercase tracking-wider">
                            <th class="w-12 px-3 py-3.5 text-center font-semibold">No</th>
                            <th class="px-4 py-3.5 font-semibold">Tanggal</th>
                            <th class="px-4 py-3.5 font-semibold">No. Surat Jalan</th>
                            <th class="px-3 py-3.5 text-center font-semibold">Rute</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Tonase</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Tarif / Ton</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Nilai Angkutan</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Uang Jalan</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Status</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Aksi &amp; Konfirmasi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-surface-container-low font-financial-table">
                        @foreach ($trips as $index => $trip)
                            <tr
                                class="transition-colors hover:bg-canvas-bg/70"
                                x-show="tripVisible(@js($trip['route']), @js($trip['sj']), @js($trip['status']), @js($trip['status_kode'] ?? ''))"
                            >
                                <td class="px-3 py-3 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-medium">{{ $trip['date'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-primary">{{ $trip['sj'] }}</span>
                                        @if (($trip['jumlah_foto'] ?? 0) > 0)
                                            <span class="mt-0.5 inline-flex w-fit items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 font-sans text-[0.6875rem] font-semibold text-primary">
                                                <span class="material-symbols-outlined text-[0.8rem]">photo_library</span>
                                                {{ $trip['jumlah_foto'] }} Foto Bukti
                                            </span>
                                        @endif
                                        @if (! empty($trip['nama_penerima']))
                                            <span class="mt-0.5 truncate font-sans text-label-sm text-slate-text-secondary" title="Penerima: {{ $trip['nama_penerima'] }}">
                                                Penerima: {{ $trip['nama_penerima'] }}
                                            </span>
                                        @endif
                                        @if (! empty($trip['catatan_pengantaran']))
                                            <span class="mt-0.5 truncate font-sans text-label-sm text-slate-text-secondary" title="{{ $trip['catatan_pengantaran'] }}">
                                                Catatan: {{ $trip['catatan_pengantaran'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="{{ $trip['route'] === 'MIP-36' ? 'bg-secondary-fixed text-on-secondary-fixed' : 'bg-primary-fixed text-on-primary-fixed' }} inline-block rounded px-2 py-0.5 font-sans text-label-sm font-semibold">{{ $trip['route'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $trip['tonnage'] }} Ton</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-text-secondary">{{ $rp($trip['rate']) }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums text-slate-text-primary">
                                    {{ $rp(round((float) str_replace(',', '.', $trip['tonnage']) * $trip['rate'])) }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums text-net-pay-emerald">{{ $rp($trip['trip_money']) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN RIWAYAT PENGANTARAN & SURAT JALAN SUPIR):
     - Tujuan Halaman: Catatan riwayat seluruh surat jalan (DO) yang pernah dijalankan oleh supir,
       status perjalanan (Terbit, Sedang Dikirim, Menunggu Verifikasi, Terverifikasi), dan uang jalan.
     - Analogi Dunia Nyata: Seperti buku saku jalan supir di dalam kabin truk: supir mencatat
       setiap keberangkatan, menekan tombol "Mulai Kirim" saat berangkat, dan mengunggah foto bukti
       surat jalan bertanda tangan saat tiba di pelabuhan/pabrik tujuan.
     - Alur Status di Halaman Ini:
       * `terbit`: Tombol "Mulai Kirim" aktif.
       * `sedang_dikirim`: Tombol "Konfirmasi Sampai" aktif (wajib upload foto & nama penerima).
       * `menunggu_verifikasi`: Menunggu pemeriksaan oleh Admin HRD di kantor.
       * `terverifikasi`: Selesai dan otomatis masuk hitungan gaji!
     - Data dari Controller (Driver/TripHistoryController@index):
       * $trips: Daftar surat jalan supir dari tabel `ritase`.
       * $payroll: Ringkasan total rit, tonase timbangan, dan uang jalan periode ini.
     ============================================================================== -->
@php
                                        $kodeStatusTrip = $trip['status_kode'] ?? '';
                                        $labelStatusTrip = match ($kodeStatusTrip) {
                                            'terbit' => 'Terbit (Siap Dikirim)',
                                            'sedang_dikirim' => 'Sedang Dikirim',
                                            'menunggu_verifikasi' => 'Menunggu Verifikasi Admin',
                                            default => $trip['status'],
                                        };
                                        $apakahSedangDikirim = $kodeStatusTrip === 'sedang_dikirim' || $trip['tone'] === 'indigo';
                                        $apakahMenungguVerifikasi = ($kodeStatusTrip === 'menunggu_verifikasi' || $trip['tone'] === 'sky') && ! $apakahSedangDikirim;
                                    @endphp
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-semibold',
                                        'bg-net-pay-surface text-net-pay-emerald' => $trip['tone'] === 'emerald',
                                        'bg-indigo-50 text-indigo-700' => $apakahSedangDikirim,
                                        'bg-sky-50 text-sky-700' => $apakahMenungguVerifikasi,
                                        'bg-warning-surface text-warning-amber' => $trip['tone'] === 'amber' && ! $apakahSedangDikirim && ! $apakahMenungguVerifikasi,
                                        'bg-danger-surface text-danger-red' => $trip['tone'] === 'red',
                                    ])>
                                        <span @class([
                                            'h-1.5 w-1.5 rounded-full',
                                            'bg-net-pay-emerald' => $trip['tone'] === 'emerald',
                                            'bg-indigo-500' => $apakahSedangDikirim,
                                            'bg-sky-500' => $apakahMenungguVerifikasi,
                                            'bg-warning-amber' => $trip['tone'] === 'amber' && ! $apakahSedangDikirim && ! $apakahMenungguVerifikasi,
                                            'bg-danger-red' => $trip['tone'] === 'red',
                                        ])></span>
                                        {{ $labelStatusTrip }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN RIWAYAT PENGANTARAN & SURAT JALAN SUPIR):
     - Tujuan Halaman: Catatan riwayat seluruh surat jalan (DO) yang pernah dijalankan oleh supir,
       status perjalanan (Terbit, Sedang Dikirim, Menunggu Verifikasi, Terverifikasi), dan uang jalan.
     - Analogi Dunia Nyata: Seperti buku saku jalan supir di dalam kabin truk: supir mencatat
       setiap keberangkatan, menekan tombol "Mulai Kirim" saat berangkat, dan mengunggah foto bukti
       surat jalan bertanda tangan saat tiba di pelabuhan/pabrik tujuan.
     - Alur Status di Halaman Ini:
       * `terbit`: Tombol "Mulai Kirim" aktif.
       * `sedang_dikirim`: Tombol "Konfirmasi Sampai" aktif (wajib upload foto & nama penerima).
       * `menunggu_verifikasi`: Menunggu pemeriksaan oleh Admin HRD di kantor.
       * `terverifikasi`: Selesai dan otomatis masuk hitungan gaji!
     - Data dari Controller (Driver/TripHistoryController@index):
       * $trips: Daftar surat jalan supir dari tabel `ritase`.
       * $payroll: Ringkasan total rit, tonase timbangan, dan uang jalan periode ini.
     ============================================================================== -->
@php
                                        $terkunciTrip = (bool) ($trip['terkunci'] ?? false);
                                        $bisaMulaiKirim = ! $terkunciTrip && $kodeStatusTrip === 'terbit';
                                        $bisaKonfirmasiSampai = ! $terkunciTrip && in_array($kodeStatusTrip, ['terbit', 'sedang_dikirim', 'menunggu_verifikasi'], true);
                                    @endphp
                                    <div class="flex flex-wrap items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-lg bg-surface-container-low px-2.5 py-1.5 font-sans text-label-sm font-semibold text-slate-text-primary transition-colors hover:bg-surface-container-high"
                                            title="Lihat Dokumen & Bukti Surat Jalan"
                                            @click="bukaSuratJalanSupir(@js($trip))"
                                        >
                                            <span class="material-symbols-outlined text-[1rem] text-primary">receipt_long</span>
                                            <span>SJ</span>
                                        </button>

                                        @if ($bisaMulaiKirim || $bisaKonfirmasiSampai)
                                            @if ($bisaMulaiKirim)
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-2.5 py-1.5 font-sans text-label-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95 disabled:opacity-60"
                                                    title="Mulai pengantaran surat jalan ini"
                                                    :disabled="sedangMemulaiId === {{ $trip['id'] }}"
                                                    @click="mulaiPengiriman(@js($trip))"
                                                >
                                                    <span class="material-symbols-outlined text-[1rem]">local_shipping</span>
                                                    <span x-text="sedangMemulaiId === {{ $trip['id'] }} ? 'Memulai...' : 'Mulai Kirim'">Mulai Kirim</span>
                                                </button>
                                            @endif

                                            @if ($bisaKonfirmasiSampai)
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-primary-container px-2.5 py-1.5 font-sans text-label-sm font-semibold text-on-primary shadow-sm transition-all hover:bg-primary active:scale-95"
                                                    title="Konfirmasi Barang Berhasil Diantarkan berdasarkan Surat Jalan"
                                                    @click="bukaKonfirmasiPengantaran(@js($trip))"
                                                >
                                                    <span class="material-symbols-outlined text-[1rem]">task_alt</span>
                                                    <span>Konfirmasi Sampai</span>
                                                </button>
                                            @endif
                                        @elseif ($terkunciTrip)
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-surface-container px-2.5 py-1.5 font-sans text-label-sm font-medium text-slate-text-muted" title="Periode gaji sudah difinalisasi">
                                                <span class="material-symbols-outlined text-[0.95rem]">lock</span>
                                                <span>Terkunci</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-surface-container px-2.5 py-1.5 font-sans text-label-sm font-medium text-slate-text-muted" title="Status surat jalan sudah final">
                                                <span class="material-symbols-outlined text-[0.95rem]">{{ $kodeStatusTrip === 'terverifikasi' ? 'verified' : 'block' }}</span>
                                                <span>{{ $kodeStatusTrip === 'terverifikasi' ? 'Selesai' : 'Dibatalkan' }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="bg-surface-container font-display text-headline-sm text-slate-text-primary">
                            <td class="px-4 py-3.5 font-bold" colspan="4">Subtotal {{ count($trips) }} Surat Jalan</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-primary">{{ number_format($subtotalTonnage, 2, ',', '.') }} Ton</td>
                            <td class="px-4 py-3.5 text-right font-sans text-label-sm font-normal text-slate-text-secondary">-</td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-primary">
                                {{ $rp(collect($trips)->sum(fn ($trip) => round((float) str_replace(',', '.', $trip['tonnage']) * $trip['rate']))) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold tabular-nums text-net-pay-emerald">{{ $rp(collect($trips)->sum('trip_money')) }}</td>
                            <td class="px-4 py-3.5 text-center font-sans text-label-sm font-normal text-slate-text-secondary" colspan="2">Akumulasi Periode</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex flex-col items-center justify-between gap-2 bg-surface-container-low px-space-md py-3 sm:flex-row">
                <span class="font-sans text-body-sm text-slate-text-secondary">
                    Menampilkan <span class="font-semibold">1-{{ count($trips) }}</span> dari <span class="font-semibold">{{ count($trips) }}</span> surat jalan periode ini
                </span>
                <span class="font-sans text-label-sm text-slate-text-muted">Seluruh pengantaran periode ini ditampilkan</span>
            </div>
        </div>

        {{-- Catatan bantuan --}}
        <div class="flex flex-col items-start gap-3 rounded-xl bg-surface-container-low p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[1.375rem] text-banner-indigo">info</span>
                <p class="font-sans text-body-sm text-slate-text-secondary">
                    Surat jalan berstatus <strong class="text-warning-amber">Terbit (Siap Dikirim)</strong>, <strong class="text-indigo-700">Sedang Dikirim</strong>, dan <strong class="text-sky-700">Menunggu Verifikasi Admin</strong> belum masuk perhitungan komisi sebelum diverifikasi Admin.
                </p>
            </div>
            <button
                type="button"
                class="shrink-0 rounded-lg bg-card-bg px-3.5 py-2 font-sans text-body-sm font-semibold text-primary shadow-sm transition-colors hover:bg-surface-bright"
                onclick="window.open('https://wa.me/{{ preg_replace('/\D/', '', $dataPerusahaan->nomor_telepon ?? '081277291090') }}', '_blank')"
            >
                Hubungi Checker
            </button>
        </div>

        {{-- ============ MODAL KONFIRMASI PENGANTARAN SUPIR ============ --}}
        <x-ui.modal-shell
            name="modalKonfirmasiPengantaran"
            title="Konfirmasi Pengantaran Surat Jalan"
            subtitle="Laporkan bahwa muatan batubara telah berhasil diantarkan sesuai tiket timbangan & surat jalan."
            icon="task_alt"
            badge="Konfirmasi Supir"
            max-width="xl"
            :z-index="60"
        >
            <form method="POST" :action="'{{ url('my/trips') }}/' + formKonfirmasi.id + '/konfirmasi'" enctype="multipart/form-data" class="flex flex-col overflow-hidden">
                @csrf
                @method('PATCH')
                <div class="flex max-h-[68vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                    <div class="grid grid-cols-2 gap-3 rounded-xl bg-surface-container-low p-space-md sm:grid-cols-4">
                        <div class="flex flex-col">
                            <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">No. Surat Jalan</span>
                            <span class="font-financial-table text-body-md font-bold text-primary" x-text="formKonfirmasi.sj || '-'"></span>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">Tanggal</span>
                            <span class="font-sans text-body-md font-semibold text-slate-text-primary" x-text="formKonfirmasi.date || '-'"></span>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">Rute Koridor</span>
                            <span class="font-sans text-body-md font-semibold text-slate-text-primary" x-text="formKonfirmasi.route || '-'"></span>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">Uang Jalan</span>
                            <span class="font-financial-table text-body-md font-bold text-net-pay-emerald" x-text="formatRp(formKonfirmasi.trip_money || 0)"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                        <x-ui.modal-field label="Tonase Timbangan Bersih (Ton)" name="tonase_ton" :required="true" icon="scale"
                            hint="Sesuaikan dengan angka tonase pada bukti surat jalan / timbangan.">
                            <input type="text" id="tonase_ton" name="tonase_ton" x-model="formKonfirmasi.tonase_ton"
                                inputmode="decimal" placeholder="28,48" required
                                class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-14 font-financial-table text-body-md font-bold tabular-nums text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                            <span class="pointer-events-none absolute right-3 font-sans text-label-sm font-bold text-slate-text-secondary">TON</span>
                        </x-ui.modal-field>

                        <div class="flex flex-col justify-center rounded-xl border border-card-border bg-surface-container-lowest px-space-md py-2.5">
                            <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">Estimasi Nilai Angkutan</span>
                            <span class="font-display text-headline-sm font-bold tabular-nums text-primary" x-text="formatRp(estimasiAngkutan)"></span>
                            <span class="font-sans text-label-sm text-slate-text-secondary" x-text="'Tarif Rute: ' + formatRp(formKonfirmasi.rate || 0) + ' / Ton'"></span>
                        </div>
                    </div>

                    <x-ui.modal-field label="Nama Pihak Penerima / Petugas Tujuan" name="nama_penerima" :required="true" icon="badge"
                        hint="Isi nama jelas penerima barang atau petugas di lokasi tujuan.">
                        <input type="text" id="nama_penerima" name="nama_penerima" x-model="formKonfirmasi.nama_penerima"
                            maxlength="120" required placeholder="Contoh: BUDI SANTOSO"
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md uppercase text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Unggah Bukti Surat Jalan / Tiket Timbangan" name="foto_surat_jalan" :required="true" icon="photo_camera"
                        hint="Boleh unggah lebih dari 1 foto (mis. foto Surat Jalan TTD & foto barang diterima). Maksimal 2 MB per foto (JPG/PNG/WEBP), otomatis dikonversi ke .webp.">
                        <x-slot:meta>
                            <span x-text="punyaFotoSetelahHapus ? (formKonfirmasi.daftar_foto.length - formKonfirmasi.foto_dihapus.length) + ' foto tersimpan (opsional tambah/hapus)' : 'Wajib diunggah minimal 1 foto'"></span>
                        </x-slot:meta>
                        <x-ui.upload-foto
                            id="foto_surat_jalan"
                            name="foto_surat_jalan[]"
                            :multiple="true"
                            :ikon="true"
                            x-bind:required="!formKonfirmasi.punya_foto_asli || formKonfirmasi.daftar_foto.filter(u => !formKonfirmasi.foto_dihapus.includes(u)).length === 0"
                        />

                        <div x-show="formKonfirmasi.daftar_foto.length > 0" x-cloak class="mt-2 flex flex-col gap-2 rounded-lg border border-card-border bg-surface-container-low p-2.5">
                            <span class="font-sans text-label-sm font-semibold text-slate-text-secondary">Foto Bukti Tersimpan Saat Ini:</span>
                            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                <template x-for="(urlFoto, idxFoto) in formKonfirmasi.daftar_foto" :key="urlFoto">
                                    <div
                                        :class="formKonfirmasi.foto_dihapus.includes(urlFoto) ? 'opacity-45 ring-1 ring-danger-red' : 'ring-1 ring-card-border'"
                                        class="flex flex-col overflow-hidden rounded-lg bg-card-bg p-1.5 shadow-sm transition-all"
                                    >
                                        <div class="relative h-20 w-full overflow-hidden rounded bg-slate-900/5">
                                            <img :src="urlFoto" :alt="'Bukti ' + (idxFoto + 1)" class="h-full w-full object-cover">
                                            <span class="absolute left-1 top-1 rounded bg-slate-950/75 px-1.5 py-0.5 font-sans text-[0.625rem] font-semibold text-white" x-text="'#' + (idxFoto + 1)"></span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="toggleHapusFotoKonfirmasi(urlFoto)"
                                            :class="formKonfirmasi.foto_dihapus.includes(urlFoto) ? 'bg-surface-container-high text-slate-text-primary' : 'bg-danger-surface text-danger-red hover:bg-rose-100'"
                                            class="mt-1.5 inline-flex items-center justify-center gap-1 rounded px-2 py-1 font-sans text-[0.6875rem] font-semibold transition-colors"
                                        >
                                            <span class="material-symbols-outlined text-[0.875rem]" x-text="formKonfirmasi.foto_dihapus.includes(urlFoto) ? 'undo' : 'delete'"></span>
                                            <span x-text="formKonfirmasi.foto_dihapus.includes(urlFoto) ? 'Batal Hapus' : 'Hapus Foto'"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <template x-for="urlHapus in formKonfirmasi.foto_dihapus" :key="urlHapus">
                            <input type="hidden" name="hapus_foto[]" :value="urlHapus">
                        </template>
                    </x-ui.modal-field>

                    <x-ui.modal-field label="Catatan Pengantaran" name="catatan_pengantaran" icon="notes" meta="(Opsional)"
                        hint="Catat nama petugas penerima, kondisi bongkar muat, atau keterangan penerimaan.">
                        <input type="text" id="catatan_pengantaran" name="catatan_pengantaran" x-model="formKonfirmasi.catatan_pengantaran"
                            maxlength="255" placeholder="Contoh: MUATAN DITERIMA LENGKAP DI LOKASI TUJUAN"
                            class="w-full rounded-lg bg-surface-container-low py-2.5 pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary">
                    </x-ui.modal-field>
                </div>

                <div class="flex items-center justify-between gap-space-sm bg-canvas-bg px-space-lg py-space-md shadow-inner">
                    <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm hover:bg-surface-container-low" @click="modalKonfirmasiPengantaran = false">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-container px-space-xl py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-md transition-all hover:bg-primary active:scale-95">
                        <span class="material-symbols-outlined text-[1.125rem]">check_circle</span>
                        <span>Simpan &amp; Konfirmasi Sampai</span>
                    </button>
                </div>
            </form>
        </x-ui.modal-shell>

        {{-- ============ MODAL PRATINJAU SURAT JALAN SUPIR ============ --}}
        <x-ui.modal-shell
            name="modalSuratJalanSupir"
            title="Dokumen & Bukti Surat Jalan"
            subtitle="Pratinjau surat jalan pengantaran batubara beserta bukti foto tiket timbangan."
            icon="receipt_long"
            max-width="xl"
            :z-index="60"
        >
            <div class="flex max-h-[68vh] flex-col gap-space-md overflow-y-auto px-space-lg pb-space-md">
                <div class="flex flex-col items-center gap-3 rounded-xl border border-card-border bg-surface-container-low p-space-md">
                    <div class="flex w-full items-center justify-between gap-2">
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-sans text-label-sm font-semibold"
                            :class="daftarFotoSuratJalanDipilih.length > 0 ? 'bg-primary/10 text-primary' : 'bg-surface-container-high text-slate-text-secondary'"
                        >
                            <span class="material-symbols-outlined text-[0.95rem]">photo_library</span>
                            <span x-text="daftarFotoSuratJalanDipilih.length > 0 ? daftarFotoSuratJalanDipilih.length + ' Foto Bukti' : 'Belum Ada Foto Bukti'"></span>
                        </span>
                        <span
                            x-show="daftarFotoSuratJalanDipilih.length > 1"
                            x-cloak
                            class="font-sans text-label-sm font-semibold text-slate-text-secondary"
                            x-text="'Foto ' + (indeksFotoAktif + 1) + ' / ' + daftarFotoSuratJalanDipilih.length"
                        ></span>
                    </div>

                    <img
                        :src="fotoAktifSuratJalan"
                        :alt="suratJalanDipilih.sj || 'Surat Jalan'"
                        @click="window.bukaLightbox({ url: fotoAktifSuratJalan, galeri: daftarFotoGaleriLightbox, indeks: indeksFotoAktif, judul: 'Surat Jalan ' + (suratJalanDipilih.sj || ''), caption: (suratJalanDipilih.route || '') + ' • ' + (suratJalanDipilih.tonnage || '0') + ' Ton' })"
                        class="max-h-56 w-auto cursor-zoom-in rounded-lg object-contain shadow-sm ring-1 ring-card-border transition-transform hover:scale-[1.02]"
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
                                :class="idxThumb === indeksFotoAktif ? 'ring-2 ring-primary opacity-100' : 'opacity-65 hover:opacity-100'"
                                class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-card-border bg-card-bg transition-all"
                            >
                                <img :src="urlThumb" :alt="'Thumbnail ' + (idxThumb + 1)" class="h-full w-full object-cover">
                            </button>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 w-full">
                        <span class="font-sans text-label-sm text-slate-text-secondary">Klik gambar untuk memperbesar layar penuh</span>
                        <button
                            type="button"
                            @click="window.bukaLightbox({ url: fotoAktifSuratJalan, galeri: daftarFotoGaleriLightbox, indeks: indeksFotoAktif, judul: 'Surat Jalan ' + (suratJalanDipilih.sj || ''), caption: (suratJalanDipilih.route || '') + ' • ' + (suratJalanDipilih.tonnage || '0') + ' Ton' })"
                            class="inline-flex items-center gap-1 rounded-lg border border-primary/25 bg-card-bg px-2.5 py-1 font-sans text-label-sm font-semibold text-primary hover:bg-primary/10"
                        >
                            <span class="material-symbols-outlined text-[0.95rem]">zoom_in</span>
                            <span>Perbesar</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-xl bg-surface-container-lowest p-space-md ring-1 ring-card-border">
                    <div>
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Nomor Surat Jalan</span>
                        <div class="font-financial-table text-body-md font-bold text-primary" x-text="suratJalanDipilih.sj || '-'"></div>
                    </div>
                    <div>
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Tanggal Pengantaran</span>
                        <div class="font-sans text-body-md font-semibold text-slate-text-primary" x-text="suratJalanDipilih.date || '-'"></div>
                    </div>
                    <div>
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Rute &amp; Material</span>
                        <div class="font-sans text-body-md font-semibold text-slate-text-primary" x-text="(suratJalanDipilih.route || '-') + ' • ' + (suratJalanDipilih.material || 'BATU BARA')"></div>
                    </div>
                    <div>
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Tonase Timbangan</span>
                        <div class="font-financial-table text-body-md font-bold text-slate-text-primary" x-text="(suratJalanDipilih.tonnage || '0,00') + ' Ton'"></div>
                    </div>
                    <div class="col-span-2">
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Nama Pihak Penerima</span>
                        <div class="font-sans text-body-md font-semibold text-slate-text-primary" x-text="suratJalanDipilih.nama_penerima || 'Belum diisi'"></div>
                    </div>
                    <div class="col-span-2">
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Catatan Pengantaran Supir</span>
                        <div class="font-sans text-body-md font-medium text-slate-text-primary" x-text="suratJalanDipilih.catatan_pengantaran || 'Belum ada catatan pengantaran'"></div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-space-sm bg-canvas-bg px-space-lg py-space-md shadow-inner">
                <button type="button" class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm" @click="modalSuratJalanSupir = false">
                    Tutup
                </button>
                <button
                    type="button"
                    x-show="!suratJalanDipilih.terkunci && ['terbit', 'sedang_dikirim', 'menunggu_verifikasi'].includes(suratJalanDipilih.status_kode || '')"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-container px-space-lg py-2.5 font-sans text-body-md font-semibold text-on-primary shadow-sm hover:bg-primary"
                    @click="modalSuratJalanSupir = false; bukaKonfirmasiPengantaran(suratJalanDipilih)"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">task_alt</span>
                    <span>Perbarui Konfirmasi / Bukti SJ</span>
                </button>
            </div>
        </x-ui.modal-shell>
    </div>
</x-driver-layout>
