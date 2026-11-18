<x-app-layout title="Pratinjau &amp; Validasi Migrasi Excel">
    @php
        $rp = fn ($nilai) => 'Rp ' . number_format((int) $nilai, 0, ',', '.');
        $ringkasan = $staging['ringkasan'] ?? [];
        $anomali = $staging['anomali'] ?? [];
        $daftarSupir = $staging['daftar_supir'] ?? [];
        $daftarUnit = $staging['daftar_unit'] ?? [];
        $daftarRute = $staging['daftar_rute'] ?? [];
        $daftarPeriode = $staging['daftar_periode'] ?? [];
        $daftarBlok = $staging['daftar_blok'] ?? [];
        $daftarSupirDatabase = $daftarSupirDatabase ?? [];
        $daftarTrukDatabase = $daftarTrukDatabase ?? [];
        $isTerkunci = (bool) ($ringkasan['periode_terkunci'] ?? false);
    @endphp

    <div
        class="flex flex-col gap-6"
        x-data="pratinjauMigrasi({
            token: @js($token),
            subtabAwal: @js(count($anomali) > 0 ? 'anomali' : 'supir'),
            daftarSupir: @js($daftarSupir),
            daftarUnit: @js($daftarUnit),
            daftarRute: @js($daftarRute),
        })"
    >
        {{-- Breadcrumb + Header Halaman --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 font-sans text-label-sm text-slate-text-muted">
                    <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-1 hover:text-primary">
                        <span class="material-symbols-outlined text-[1rem]">arrow_back</span>
                        <span>Kembali ke Laporan</span>
                    </a>
                    <span class="material-symbols-outlined text-[0.875rem]">chevron_right</span>
                    <span class="font-semibold text-slate-text-primary">Pratinjau &amp; Validasi Migrasi Excel</span>
                </div>
                <h1 class="font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">
                    Pratinjau &amp; Validasi Migrasi Excel
                </h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Berkas: <strong class="text-slate-text-primary">{{ $staging['nama_berkas'] }}</strong>
                    &bull; Target Periode: <strong class="text-primary">{{ $staging['target_periode'] }}</strong>
                    &bull; Status Pencairan: <span class="rounded bg-surface-container px-2 py-0.5 font-bold uppercase text-slate-text-primary">{{ $staging['status_pencairan'] === 'sudah_cair' ? 'Sudah Dicairkan (Lunas)' : 'Belum Dicairkan (Draf)' }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="window.close()"
                    class="rounded-lg border border-card-border bg-card-bg px-4 py-2 font-sans text-body-sm font-semibold text-slate-text-secondary shadow-sm transition-colors hover:bg-surface-container-low"
                >
                    Tutup Tab
                </button>
            </div>
        </div>

        {{-- Indikator Kunci Periode (Jika Terkunci) --}}
        @if ($isTerkunci)
            <div class="flex items-start gap-3 rounded-2xl border border-danger-red/40 bg-danger-surface p-4 text-danger-red shadow-sm">
                <span class="material-symbols-outlined mt-0.5 text-[1.5rem]">lock</span>
                <div class="space-y-1">
                    <h3 class="font-display text-body-md font-bold">Periode Target Terkunci!</h3>
                    <p class="font-sans text-body-sm text-slate-text-primary">
                        Periode penggajian yang dipilih sudah disahkan (Approved) atau dicairkan (Paid). Sistem menolak impor ke periode yang sudah terkunci untuk melindungi keabsahan data pembukuan keuangan.
                    </p>
                </div>
            </div>
        @endif

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-tile
                label="Total Lembar Slip (Supir)"
                :nilai="(string) ($ringkasan['total_slip'] ?? 0)"
                satuan="slip supir"
                ikon="badge"
                tone="primary"
                catatan="{{ $ringkasan['supir_belum_terdaftar'] ?? 0 }} supir belum ada akun"
            />
            <x-ui.stat-tile
                label="Total Pengantaran (Ritase)"
                :nilai="number_format((int) ($ringkasan['total_ritase'] ?? 0), 0, ',', '.')"
                satuan="rit"
                ikon="route"
                tone="emerald"
                catatan="Baris perjalanan pengangkutan"
            />
            <x-ui.stat-tile
                label="Total Tonase Muatan"
                :nilai="$ringkasan['total_tonase_label'] ?? '0 Ton'"
                ikon="local_shipping"
                tone="amber"
                catatan="{{ $ringkasan['unit_belum_terdaftar'] ?? 0 }} unit belum terdaftar"
            />
            <x-ui.stat-tile
                label="Estimasi Total Gaji Bersih"
                :nilai="$rp($ringkasan['total_gaji_bersih'] ?? 0)"
                ikon="payments"
                tone="slate"
                catatan="{{ $ringkasan['jumlah_anomali'] ?? 0 }} anomali terdeteksi"
            />
        </div>

        {{-- Subtab Navigasi: Anomali | Supir | Unit Armada | Rute | Pratinjau Rekap --}}
        <div class="sticky top-0 z-30 -mx-1 px-1">
            <div class="flex flex-wrap items-center gap-1 rounded-xl border border-card-border bg-canvas-bg p-1.5 shadow-card" role="tablist" aria-label="Subtab Pratinjau Migrasi">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="subtabAktif === 'anomali'"
                    @click="subtabAktif = 'anomali'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-sans text-body-sm font-semibold transition-colors"
                    :class="subtabAktif === 'anomali' ? 'bg-card-bg text-warning-amber shadow-sm' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">warning</span>
                    <span>Anomali</span>
                    <span class="rounded-full px-1.5 py-0.5 font-sans text-[0.625rem] font-bold" :class="subtabAktif === 'anomali' ? 'bg-warning-amber/20 text-warning-amber' : 'bg-surface-container text-slate-text-secondary'">{{ count($anomali) }}</span>
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="subtabAktif === 'supir'"
                    @click="subtabAktif = 'supir'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-sans text-body-sm font-semibold transition-colors"
                    :class="subtabAktif === 'supir' ? 'bg-card-bg text-primary shadow-sm' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">badge</span>
                    <span>Supir &amp; Akun</span>
                    <span class="rounded-full px-1.5 py-0.5 font-sans text-[0.625rem] font-bold" :class="subtabAktif === 'supir' ? 'bg-primary/15 text-primary' : 'bg-surface-container text-slate-text-secondary'">{{ count($daftarSupir) }}</span>
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="subtabAktif === 'unit'"
                    @click="subtabAktif = 'unit'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-sans text-body-sm font-semibold transition-colors"
                    :class="subtabAktif === 'unit' ? 'bg-card-bg text-net-pay-emerald shadow-sm' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">local_shipping</span>
                    <span>Unit Armada</span>
                    <span class="rounded-full px-1.5 py-0.5 font-sans text-[0.625rem] font-bold" :class="subtabAktif === 'unit' ? 'bg-net-pay-surface text-net-pay-emerald' : 'bg-surface-container text-slate-text-secondary'">{{ count($daftarUnit) }}</span>
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="subtabAktif === 'rute'"
                    @click="subtabAktif = 'rute'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-sans text-body-sm font-semibold transition-colors"
                    :class="subtabAktif === 'rute' ? 'bg-card-bg text-banner-indigo shadow-sm' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">alt_route</span>
                    <span>Rute</span>
                    <span class="rounded-full px-1.5 py-0.5 font-sans text-[0.625rem] font-bold" :class="subtabAktif === 'rute' ? 'bg-banner-indigo/15 text-banner-indigo' : 'bg-surface-container text-slate-text-secondary'">{{ count($daftarRute) }}</span>
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="subtabAktif === 'pratinjau'"
                    @click="subtabAktif = 'pratinjau'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-sans text-body-sm font-semibold transition-colors"
                    :class="subtabAktif === 'pratinjau' ? 'bg-card-bg text-primary shadow-sm' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">table_view</span>
                    <span>Pratinjau Rekap</span>
                </button>
            </div>
        </div>

        {{-- Panel Deteksi Anomali Operasional Lapangan --}}
        @if (! empty($anomali))
            <div x-show="subtabAktif === 'anomali'" class="rounded-2xl border border-warning-amber/40 bg-warning-surface/60 p-5 shadow-card">
                <div class="mb-3 flex items-center justify-between border-b border-warning-amber/30 pb-3">
                    <div class="flex items-center gap-2 text-warning-amber">
                        <span class="material-symbols-outlined text-[1.5rem]">warning</span>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">
                            Peringatan Anomali Integritas Lapangan ({{ count($anomali) }} Terdeteksi)
                        </h2>
                    </div>
                    <span class="rounded-full bg-warning-amber/20 px-2.5 py-0.5 font-sans text-xs font-bold text-warning-amber">
                        Perlu Perhatian Admin
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2">
                    @foreach ($anomali as $item)
                        <div class="flex items-start gap-2.5 rounded-xl border border-card-border bg-card-bg p-3 shadow-xs">
                            <span class="material-symbols-outlined mt-0.5 text-[1.25rem] {{ $item['tingkat'] === 'bahaya' ? 'text-danger-red' : ($item['tingkat'] === 'peringatan' ? 'text-warning-amber' : 'text-primary') }}">
                                {{ $item['tingkat'] === 'bahaya' ? 'error' : ($item['tingkat'] === 'peringatan' ? 'report_problem' : 'info') }}
                            </span>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <span class="font-sans text-body-sm font-bold text-slate-text-primary">{{ $item['judul'] ?? 'Anomali Data' }}</span>
                                <span class="font-sans text-body-sm text-slate-text-secondary leading-snug">{{ $item['keterangan'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div x-show="subtabAktif === 'anomali'" class="flex items-center gap-3 rounded-2xl border border-net-pay-emerald/30 bg-net-pay-surface p-4 text-net-pay-emerald shadow-card">
                <span class="material-symbols-outlined text-[1.5rem]">verified</span>
                <div>
                    <h3 class="font-display text-body-md font-bold">Integritas Berkas Valid</h3>
                    <p class="font-sans text-body-sm text-slate-text-secondary">Tidak ditemukan anomali rute ganda armada atau selisih nominal pada berkas migrasi ini.</p>
                </div>
            </div>
        @endif

        {{-- Form Utama Eksekusi Impor --}}
        <form
            method="POST"
            action="{{ route('admin.reports.migrasi.eksekusi') }}"
            class="flex flex-col gap-6"
            @submit="validasiEksekusi($event)"
        >
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            {{-- 1. Matriks Resolusi Supir (Karyawan / Users) --}}
            <div x-show="subtabAktif === 'supir'" class="rounded-2xl border border-card-border bg-card-bg shadow-card overflow-hidden">
                <div class="flex flex-col justify-between gap-3 border-b border-card-border p-5 sm:flex-row sm:items-center bg-surface-container-low/40">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <span class="material-symbols-outlined text-[1.25rem]">badge</span>
                        </span>
                        <div>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">
                                1. Pengecekan Supir &amp; Akun Login
                            </h2>
                            <p class="font-sans text-body-sm text-slate-text-secondary">
                                Jika data bentrok atau belum terdaftar: pilih <strong>Gabungkan ke Supir Terdaftar</strong>, <strong>Buat Baru Otomatis</strong>, atau <strong>Lewati</strong>.
                            </p>
                        </div>
                    </div>

                    {{-- Tombol Tindakan Massal Supir --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="pilihSemuaSupir('buat_akun')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-primary/30 bg-primary/10 px-3 py-1.5 font-sans text-xs font-bold text-primary transition-colors hover:bg-primary/20"
                        >
                            <span class="material-symbols-outlined text-[1rem]">person_add</span>
                            <span>Pilih Buat Semua Akun Baru</span>
                        </button>
                        <button
                            type="button"
                            @click="pilihSemuaSupir('lewati')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-card-border bg-card-bg px-3 py-1.5 font-sans text-xs font-semibold text-slate-text-secondary transition-colors hover:bg-surface-container-low"
                        >
                            <span class="material-symbols-outlined text-[1rem]">do_not_disturb_on</span>
                            <span>Pilih Lewati Semua Supir Baru</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left font-sans text-sm">
                        <thead>
                            <tr class="border-b border-card-border bg-surface-container-low text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">
                                <th class="w-12 px-4 py-3 text-center">No</th>
                                <th class="px-4 py-3">Nama Supir di Excel</th>
                                <th class="px-4 py-3 text-center">Status Database</th>
                                <th class="px-4 py-3">Keputusan Impor (Pilih Salah Satu)</th>
                                <th class="px-4 py-3">Detail Konfigurasi Akun / Penggabungan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border font-body text-slate-text-primary">
                            @foreach ($daftarSupir as $supir)
                                <tr class="transition-colors hover:bg-surface-container-low/40">
                                    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-text-primary">{{ $supir['nama'] }}</div>
                                        <div class="text-xs text-slate-text-muted">{{ $supir['jumlah_slip'] }} slip transaksi terdeteksi</div>
                                        @if (! empty($supir['kemungkinan_mirip']))
                                            <div class="mt-1 inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 border border-amber-200">
                                                <span class="material-symbols-outlined text-[0.875rem]">info</span>
                                                <span>Mirip: {{ $supir['kemungkinan_mirip']['nama'] }} ({{ $supir['kemungkinan_mirip']['nip'] }})</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($supir['terdaftar'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-2.5 py-0.5 text-xs font-bold text-net-pay-emerald">
                                                <span class="material-symbols-outlined text-[0.875rem]">verified</span>
                                                Terdaftar ({{ $supir['nip'] }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-0.5 text-xs font-bold text-warning-amber">
                                                <span class="material-symbols-outlined text-[0.875rem]">person_off</span>
                                                Belum Terdaftar
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($supir['terdaftar'])
                                            <div class="text-xs font-semibold text-net-pay-emerald flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[1rem]">check</span>
                                                <span>Akan diimpor ke akun supir terdaftar</span>
                                            </div>
                                            <input type="hidden" name="supir[{{ $supir['nama'] }}][aksi]" value="terdaftar">
                                        @else
                                            <div class="flex flex-col gap-1.5">
                                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs">
                                                    <input
                                                        type="radio"
                                                        name="supir[{{ $supir['nama'] }}][aksi]"
                                                        value="buat_akun"
                                                        x-model="keputusanSupir['{{ $supir['nama'] }}'].aksi"
                                                        class="text-primary focus:ring-primary h-3.5 w-3.5"
                                                    >
                                                    <span class="font-bold text-primary">Buat Akun Baru Otomatis</span>
                                                </label>

                                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs">
                                                    <input
                                                        type="radio"
                                                        name="supir[{{ $supir['nama'] }}][aksi]"
                                                        value="gabungkan"
                                                        x-model="keputusanSupir['{{ $supir['nama'] }}'].aksi"
                                                        class="text-banner-indigo focus:ring-banner-indigo h-3.5 w-3.5"
                                                    >
                                                    <span class="font-bold text-banner-indigo">Gabungkan ke Supir Terdaftar</span>
                                                </label>

                                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs">
                                                    <input
                                                        type="radio"
                                                        name="supir[{{ $supir['nama'] }}][aksi]"
                                                        value="lewati"
                                                        x-model="keputusanSupir['{{ $supir['nama'] }}'].aksi"
                                                        class="text-slate-text-secondary focus:ring-primary h-3.5 w-3.5"
                                                    >
                                                    <span class="text-slate-text-secondary">Lewati Impor Supir Ini</span>
                                                </label>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (! $supir['terdaftar'])
                                            {{-- Form Buat Akun Baru --}}
                                            <div
                                                class="flex flex-col gap-1.5 rounded-lg border border-card-border bg-canvas-bg p-2.5 text-xs"
                                                x-show="keputusanSupir['{{ $supir['nama'] }}']?.aksi === 'buat_akun'"
                                            >
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-24 shrink-0 font-semibold text-slate-text-secondary">NIK/NIP:</span>
                                                    <input
                                                        type="text"
                                                        name="supir[{{ $supir['nama'] }}][nip]"
                                                        x-model="keputusanSupir['{{ $supir['nama'] }}'].nip"
                                                        placeholder="DRF-KS-48"
                                                        class="h-7 w-32 rounded border border-card-border bg-card-bg px-2 font-mono text-xs font-bold uppercase text-slate-text-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                                    >
                                                    <span class="text-[11px] text-slate-text-muted">(Format manual berdasar inisial)</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-24 shrink-0 font-semibold text-slate-text-secondary">Email Default:</span>
                                                    <input
                                                        type="email"
                                                        name="supir[{{ $supir['nama'] }}][email]"
                                                        x-model="keputusanSupir['{{ $supir['nama'] }}'].email"
                                                        class="h-7 flex-1 rounded border border-card-border bg-card-bg px-2 font-mono text-xs text-slate-text-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                                    >
                                                </div>
                                                <div class="flex items-center gap-1 text-[11px] text-slate-text-muted">
                                                    <span class="material-symbols-outlined text-[0.875rem]">lock</span>
                                                    <span>Password default: <code class="font-mono font-bold text-slate-text-primary">password</code> (dapat login langsung)</span>
                                                </div>
                                            </div>

                                            {{-- Dropdown Gabungkan ke Supir Terdaftar --}}
                                            <div
                                                class="flex flex-col gap-1 rounded-lg border border-banner-indigo/30 bg-banner-indigo/5 p-2.5 text-xs"
                                                x-show="keputusanSupir['{{ $supir['nama'] }}']?.aksi === 'gabungkan'"
                                            >
                                                <label class="font-bold text-banner-indigo">Pilih Supir Tujuan di Database:</label>
                                                <select
                                                    name="supir[{{ $supir['nama'] }}][id_karyawan_tujuan]"
                                                    x-model="keputusanSupir['{{ $supir['nama'] }}'].id_karyawan_tujuan"
                                                    class="h-8 w-full rounded border border-card-border bg-card-bg px-2 text-xs font-semibold text-slate-text-primary focus:outline-none focus:ring-1 focus:ring-banner-indigo"
                                                >
                                                    <option value="">-- Pilih Supir Terdaftar --</option>
                                                    @foreach ($daftarSupirDatabase as $karyawanDb)
                                                        <option value="{{ $karyawanDb->id }}">
                                                            {{ $karyawanDb->nama_lengkap }} ({{ $karyawanDb->nomor_induk_karyawan }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="text-[11px] text-slate-text-muted">Seluruh transaksi ritase supir ini akan dipetakan ke akun yang dipilih.</span>
                                            </div>

                                            {{-- Info Lewati --}}
                                            <div
                                                class="text-xs text-slate-text-muted italic"
                                                x-show="keputusanSupir['{{ $supir['nama'] }}']?.aksi === 'lewati'"
                                            >
                                                Slip supir ini tidak akan disimpan ke database.
                                            </div>
                                        @else
                                            <div class="text-xs text-slate-text-muted">
                                                Email: <span class="font-mono text-slate-text-primary">{{ $supir['email_terdaftar'] ?? '—' }}</span>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Matriks Resolusi Unit Armada (Truk) --}}
            <div x-show="subtabAktif === 'unit'" class="rounded-2xl border border-card-border bg-card-bg shadow-card overflow-hidden">
                <div class="flex flex-col justify-between gap-3 border-b border-card-border p-5 sm:flex-row sm:items-center bg-surface-container-low/40">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-net-pay-emerald/10 text-net-pay-emerald">
                            <span class="material-symbols-outlined text-[1.25rem]">local_shipping</span>
                        </span>
                        <div>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">
                                2. Pengecekan Unit Armada Truk
                            </h2>
                            <p class="font-sans text-body-sm text-slate-text-secondary">
                                Pengecekan nomor polisi truk batubara yang tercantum di baris ritase.
                            </p>
                        </div>
                    </div>

                    {{-- Tombol Tindakan Massal Truk --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="pilihSemuaUnit('daftar_baru')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-net-pay-emerald/30 bg-net-pay-surface px-3 py-1.5 font-sans text-xs font-bold text-net-pay-emerald transition-colors hover:bg-emerald-100"
                        >
                            <span class="material-symbols-outlined text-[1rem]">add_circle</span>
                            <span>Daftarkan Semua Unit Baru Otomatis</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left font-sans text-sm">
                        <thead>
                            <tr class="border-b border-card-border bg-surface-container-low text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">
                                <th class="w-12 px-4 py-3 text-center">No</th>
                                <th class="px-4 py-3">Nomor Polisi (Plat)</th>
                                <th class="px-4 py-3">Merek / Model</th>
                                <th class="px-4 py-3 text-center">Status Database</th>
                                <th class="px-4 py-3">Keputusan Impor</th>
                                <th class="px-4 py-3 text-center">Aksi / Pilihan Penggabungan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border font-body text-slate-text-primary">
                            @foreach ($daftarUnit as $unit)
                                <tr class="transition-colors hover:bg-surface-container-low/40">
                                    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-mono font-bold text-slate-text-primary">{{ $unit['nomor_polisi'] }}</td>
                                    <td class="px-4 py-3 text-slate-text-secondary">{{ $unit['merek_model'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($unit['terdaftar'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-2.5 py-0.5 text-xs font-bold text-net-pay-emerald">
                                                <span class="material-symbols-outlined text-[0.875rem]">verified</span>
                                                Terdaftar ({{ $unit['jenis_truk'] }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-0.5 text-xs font-bold text-warning-amber">
                                                <span class="material-symbols-outlined text-[0.875rem]">help</span>
                                                Belum Terdaftar
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($unit['terdaftar'])
                                            <span class="text-xs font-semibold text-net-pay-emerald">Unit armada siap ditugaskan</span>
                                            <input type="hidden" name="unit[{{ $unit['nomor_polisi'] }}][aksi]" value="terdaftar">
                                        @else
                                            <div class="flex flex-col gap-1 text-xs">
                                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                                    <input
                                                        type="radio"
                                                        name="unit[{{ $unit['nomor_polisi'] }}][aksi]"
                                                        value="daftar_baru"
                                                        x-model="keputusanUnit['{{ $unit['nomor_polisi'] }}'].aksi"
                                                        class="text-primary focus:ring-primary h-3.5 w-3.5"
                                                    >
                                                    <span class="font-bold text-primary">Daftarkan Unit Baru Otomatis</span>
                                                </label>

                                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                                    <input
                                                        type="radio"
                                                        name="unit[{{ $unit['nomor_polisi'] }}][aksi]"
                                                        value="hubungkan"
                                                        x-model="keputusanUnit['{{ $unit['nomor_polisi'] }}'].aksi"
                                                        class="text-banner-indigo focus:ring-banner-indigo h-3.5 w-3.5"
                                                    >
                                                    <span class="font-bold text-banner-indigo">Hubungkan ke Unit Terdaftar</span>
                                                </label>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (! $unit['terdaftar'])
                                            <div x-show="keputusanUnit['{{ $unit['nomor_polisi'] }}']?.aksi === 'daftar_baru'" class="text-center">
                                                <button
                                                    type="button"
                                                    @click="bukaModalDaftarUnit('{{ $unit['nomor_polisi'] }}', '{{ $unit['merek_model'] }}')"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-primary/30 bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary transition-colors hover:bg-primary/20"
                                                >
                                                    <span class="material-symbols-outlined text-[0.875rem]">edit_square</span>
                                                    <span>Daftar via Modal</span>
                                                </button>
                                            </div>

                                            <div x-show="keputusanUnit['{{ $unit['nomor_polisi'] }}']?.aksi === 'hubungkan'" class="flex flex-col gap-1">
                                                <select
                                                    name="unit[{{ $unit['nomor_polisi'] }}][id_truk_tujuan]"
                                                    x-model="keputusanUnit['{{ $unit['nomor_polisi'] }}'].id_truk_tujuan"
                                                    class="h-7 w-full rounded border border-card-border bg-card-bg px-2 text-xs font-mono font-semibold text-slate-text-primary"
                                                >
                                                    <option value="">-- Pilih Truk Terdaftar --</option>
                                                    @foreach ($daftarTrukDatabase as $trukDb)
                                                        <option value="{{ $trukDb->id }}">
                                                            {{ $trukDb->nomor_polisi }} ({{ $trukDb->merek_model }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <div class="text-center text-xs text-slate-text-muted">—</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 3. Matriks Resolusi Rute Operasional --}}
            <div x-show="subtabAktif === 'rute'" class="rounded-2xl border border-card-border bg-card-bg shadow-card overflow-hidden">
                <div class="flex items-center gap-2.5 border-b border-card-border p-5 bg-surface-container-low/40">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-banner-indigo/10 text-banner-indigo">
                        <span class="material-symbols-outlined text-[1.25rem]">alt_route</span>
                    </span>
                    <div>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">
                            3. Pengecekan Kode Rute Operasional
                        </h2>
                        <p class="font-sans text-body-sm text-slate-text-secondary">
                            Rute yang belum terdaftar akan otomatis dibuatkan jalurnya sesuai tarif di Excel.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left font-sans text-sm">
                        <thead>
                            <tr class="border-b border-card-border bg-surface-container-low text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">
                                <th class="w-12 px-4 py-3 text-center">No</th>
                                <th class="px-4 py-3">Kode Rute</th>
                                <th class="px-4 py-3">Material</th>
                                <th class="px-4 py-3 text-right">Tarif / Ton</th>
                                <th class="px-4 py-3 text-right">Uang Jalan Default</th>
                                <th class="px-4 py-3 text-center">Status Database</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border font-body text-slate-text-primary">
                            @foreach ($daftarRute as $rute)
                                <tr class="transition-colors hover:bg-surface-container-low/40">
                                    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-text-primary">{{ $rute['kode'] }}</td>
                                    <td class="px-4 py-3 text-slate-text-secondary">{{ $rute['material'] }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ $rp($rute['tarif_per_ton']) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-net-pay-emerald font-semibold">{{ $rp($rute['uang_jalan_default']) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($rute['terdaftar'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-2.5 py-0.5 text-xs font-bold text-net-pay-emerald">
                                                <span class="material-symbols-outlined text-[0.875rem]">verified</span>
                                                Terdaftar
                                            </span>
                                            <input type="hidden" name="rute[{{ $rute['kode'] }}][aksi]" value="terdaftar">
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-2.5 py-0.5 text-xs font-bold text-warning-amber">
                                                <span class="material-symbols-outlined text-[0.875rem]">add</span>
                                                Dibuat Otomatis
                                            </span>
                                            <input type="hidden" name="rute[{{ $rute['kode'] }}][aksi]" value="daftar_baru">
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. TABEL PRATINJAU REKAPITULASI & KALKULASI GAJI SUPIR (PREVIEW TABEL UTAMA) --}}
            <div x-show="subtabAktif === 'pratinjau'" class="rounded-2xl border border-card-border bg-card-bg shadow-card overflow-hidden" x-data="{ slipBuka: -1 }">
                <div class="flex flex-col justify-between gap-3 border-b border-card-border p-5 sm:flex-row sm:items-center bg-surface-container-low/40">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-banner-sky/20 text-primary">
                            <span class="material-symbols-outlined text-[1.25rem]">table_chart</span>
                        </span>
                        <div>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">
                                4. Tabel Pratinjau Rekapitulasi &amp; Kalkulasi Gaji Supir ({{ count($daftarBlok) }} Lembar)
                            </h2>
                            <p class="font-sans text-body-sm text-slate-text-secondary">
                                Tinjau angka final sebelum menekan konfirmasi import. Klik baris supir untuk melihat rincian ritase perjalanan harian.
                            </p>
                        </div>
                    </div>
                    <span class="rounded-full bg-primary/10 px-3 py-1 font-sans text-xs font-bold text-primary">
                        Periode {{ $staging['target_periode'] }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left font-financial-table text-xs">
                        <thead>
                            <tr class="border-b border-card-border bg-surface-container-low text-[11px] font-bold uppercase tracking-wider text-slate-text-secondary">
                                <th class="w-10 px-3 py-3 text-center">No</th>
                                <th class="px-3 py-3 font-semibold">Supir di Excel</th>
                                <th class="px-3 py-3 font-semibold">Plat Truk</th>
                                <th class="px-2 py-3 text-right font-semibold">Rit</th>
                                <th class="px-2 py-3 text-right font-semibold">Tonase</th>
                                <th class="px-2 py-3 text-right font-semibold">Uang Jalan</th>
                                <th class="px-2 py-3 text-right font-semibold">Sparepart</th>
                                <th class="px-2 py-3 text-right font-semibold">Komisi Supir</th>
                                <th class="px-2 py-3 text-right font-semibold">BPJS &amp; Kasbon</th>
                                <th class="px-3 py-3 text-right font-semibold">Gaji Bersih</th>
                                <th class="px-3 py-3 text-center font-semibold">Status Aksi</th>
                                <th class="w-12 px-2 py-3 text-center font-semibold">Rincian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border text-slate-text-primary">
                            @foreach ($daftarBlok as $idx => $blok)
                                <tr
                                    class="transition-colors hover:bg-surface-container-low/50 cursor-pointer"
                                    @click="slipBuka = (slipBuka === {{ $idx }} ? -1 : {{ $idx }})"
                                >
                                    <td class="px-3 py-2.5 text-center tabular-nums text-slate-text-muted">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-2.5">
                                        <div class="font-bold text-slate-text-primary">{{ $blok['nama_supir_efektif'] }}</div>
                                        <div class="font-sans text-[11px] text-slate-text-muted">{{ count($blok['ritase']) }} Pengantaran</div>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <span class="font-mono font-bold text-slate-text-primary">{{ $blok['nomor_polisi_efektif'] }}</span>
                                    </td>
                                    <td class="px-2 py-2.5 text-right font-bold">{{ $blok['estimasi_kalkulasi']['total_rit'] ?? count($blok['ritase']) }}</td>
                                    <td class="px-2 py-2.5 text-right font-medium tabular-nums">{{ number_format(($blok['estimasi_kalkulasi']['total_tonase_kg'] ?? 0) / 1000, 2, ',', '.') }} T</td>
                                    <td class="px-2 py-2.5 text-right tabular-nums text-slate-text-secondary">{{ $rp($blok['estimasi_kalkulasi']['total_uang_jalan'] ?? 0) }}</td>
                                    <td class="px-2 py-2.5 text-right tabular-nums text-danger-red">-{{ $rp($blok['estimasi_kalkulasi']['biaya_sparepart'] ?? 0) }}</td>
                                    <td class="px-2 py-2.5 text-right tabular-nums font-bold text-primary">{{ $rp($blok['estimasi_kalkulasi']['gaji_komisi'] ?? 0) }}</td>
                                    <td class="px-2 py-2.5 text-right tabular-nums text-danger-red">-{{ $rp(($blok['estimasi_kalkulasi']['potongan_bpjs'] ?? 0) + ($blok['estimasi_kalkulasi']['potongan_kasbon'] ?? 0)) }}</td>
                                    <td class="px-3 py-2.5 text-right">
                                        <span class="rounded bg-net-pay-surface px-2 py-0.5 font-bold tabular-nums text-net-pay-emerald text-sm">{{ $rp($blok['gaji_bersih_final']) }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-sans text-xs">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded font-bold uppercase text-[10px]"
                                            :class="keputusanSupir['{{ $blok['nama_supir_efektif'] }}']?.aksi === 'lewati' ? 'bg-rose-50 text-rose-700' : (keputusanSupir['{{ $blok['nama_supir_efektif'] }}']?.aksi === 'gabungkan' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700')"
                                            x-text="keputusanSupir['{{ $blok['nama_supir_efektif'] }}']?.aksi === 'lewati' ? 'Dilewati' : (keputusanSupir['{{ $blok['nama_supir_efektif'] }}']?.aksi === 'gabungkan' ? 'Digabung' : 'Diimpor')"
                                        ></span>
                                    </td>
                                    <td class="px-2 py-2.5 text-center">
                                        <span class="material-symbols-outlined text-[1.25rem] text-slate-text-muted transition-transform" :class="slipBuka === {{ $idx }} ? 'rotate-180' : ''">
                                            expand_more
                                        </span>
                                    </td>
                                </tr>

                                {{-- Nested Trip Details --}}
                                <tr x-show="slipBuka === {{ $idx }}" x-cloak class="bg-surface-container-low/40">
                                    <td colspan="12" class="p-4">
                                        <div class="rounded-xl border border-card-border bg-card-bg p-3 shadow-xs">
                                            <div class="mb-2 flex items-center justify-between font-sans text-xs font-bold text-slate-text-secondary">
                                                <span>Daftar Transaksi Pengantaran Ritase — {{ $blok['nama_supir_efektif'] }} ({{ $blok['nomor_polisi_efektif'] }})</span>
                                                <span class="font-financial-table">{{ count($blok['ritase']) }} Ritase</span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full border-collapse text-left text-[11px] font-financial-table">
                                                    <thead>
                                                        <tr class="bg-surface-container-low border-b border-card-border font-bold uppercase text-slate-text-secondary">
                                                            <th class="px-2 py-1.5 text-center">No</th>
                                                            <th class="px-2 py-1.5">Tanggal</th>
                                                            <th class="px-2 py-1.5">Plat Truk</th>
                                                            <th class="px-2 py-1.5">Material</th>
                                                            <th class="px-2 py-1.5 text-center">Rit</th>
                                                            <th class="px-2 py-1.5 text-right">Tonase (Kg)</th>
                                                            <th class="px-2 py-1.5 text-right">Tarif / Ton</th>
                                                            <th class="px-2 py-1.5 text-right">Total Angkutan</th>
                                                            <th class="px-2 py-1.5 text-right">Uang Jalan</th>
                                                            <th class="px-2 py-1.5">Keterangan / Rute</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-card-border text-slate-text-primary">
                                                        @foreach ($blok['ritase'] as $rIdx => $rit)
                                                            <tr>
                                                                <td class="px-2 py-1 text-center text-slate-text-muted">{{ $rIdx + 1 }}</td>
                                                                <td class="px-2 py-1 whitespace-nowrap">{{ $rit['tanggal'] }}</td>
                                                                <td class="px-2 py-1 font-mono font-semibold">{{ $rit['nomor_polisi'] ?? $blok['nomor_polisi_efektif'] }}</td>
                                                                <td class="px-2 py-1">{{ $rit['material'] }}</td>
                                                                <td class="px-2 py-1 text-center font-bold">{{ $rit['jumlah_rit'] }}</td>
                                                                <td class="px-2 py-1 text-right tabular-nums">{{ number_format($rit['tonase_kg'], 0, ',', '.') }}</td>
                                                                <td class="px-2 py-1 text-right tabular-nums">{{ $rp($rit['tarif_per_ton']) }}</td>
                                                                <td class="px-2 py-1 text-right tabular-nums font-bold">{{ $rp($rit['total_angkutan']) }}</td>
                                                                <td class="px-2 py-1 text-right tabular-nums font-bold text-net-pay-emerald">{{ $rp($rit['uang_jalan']) }}</td>
                                                                <td class="px-2 py-1 text-slate-text-secondary">{{ $rit['keterangan'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-surface-container font-financial-table font-bold text-slate-text-primary border-t-2 border-card-border">
                                <td class="px-3 py-3" colspan="3">TOTAL ESTIMASI KESELURUHAN ({{ count($daftarBlok) }} SUPIR)</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ collect($daftarBlok)->sum(fn($b) => $b['estimasi_kalkulasi']['total_rit'] ?? count($b['ritase'])) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ number_format(collect($daftarBlok)->sum(fn($b) => $b['estimasi_kalkulasi']['total_tonase_kg'] ?? 0) / 1000, 2, ',', '.') }} T</td>
                                <td class="px-2 py-3 text-right tabular-nums">{{ $rp(collect($daftarBlok)->sum(fn($b) => $b['estimasi_kalkulasi']['total_uang_jalan'] ?? 0)) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-danger-red">-{{ $rp(collect($daftarBlok)->sum(fn($b) => $b['estimasi_kalkulasi']['biaya_sparepart'] ?? 0)) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-primary">{{ $rp(collect($daftarBlok)->sum(fn($b) => $b['estimasi_kalkulasi']['gaji_komisi'] ?? 0)) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-danger-red">-{{ $rp(collect($daftarBlok)->sum(fn($b) => ($b['estimasi_kalkulasi']['potongan_bpjs'] ?? 0) + ($b['estimasi_kalkulasi']['potongan_kasbon'] ?? 0))) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-net-pay-emerald text-sm">{{ $rp(collect($daftarBlok)->sum('gaji_bersih_final')) }}</td>
                                <td class="px-3 py-3 text-center font-sans text-[11px] text-slate-text-muted" colspan="2">Transaksi DB Aman</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Bottom Sticky Bar --}}
            <div class="sticky bottom-4 z-30 flex flex-col justify-between gap-3 rounded-2xl border border-card-border bg-card-bg/95 p-4 shadow-drawer backdrop-blur-md sm:flex-row sm:items-center">
                <div class="flex items-center gap-2 font-sans text-xs text-slate-text-secondary">
                    <span class="material-symbols-outlined text-[1.125rem] text-primary">security</span>
                    <span>Seluruh eksekusi impor dilindungi <strong>Database Transaction</strong>. Jika satu data gagal, seluruh mutasi dibatalkan otomatis (Rollback).</span>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        onclick="window.close()"
                        class="rounded-lg border border-card-border bg-card-bg px-4 py-2.5 font-sans text-xs font-semibold text-slate-text-secondary transition-colors hover:bg-surface-container-low"
                    >
                        Batalkan
                    </button>

                    <button
                        type="submit"
                        :disabled="sedangSimpan || {{ $isTerkunci ? 'true' : 'false' }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-container px-6 py-2.5 font-display text-headline-sm text-on-primary shadow-md transition-all hover:bg-primary disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span class="material-symbols-outlined text-[1.25rem]" :class="sedangSimpan ? 'animate-spin' : ''" x-text="sedangSimpan ? 'progress_activity' : 'cloud_done'"></span>
                        <span x-text="sedangSimpan ? 'Menyimpan ke Database...' : 'Konfirmasi &amp; Eksekusi Impor ke Database'"></span>
                    </button>
                </div>
            </div>
        </form>

        {{-- Modal Tambah Unit Cepat --}}
        <div
            x-show="modalUnitTerbuka"
            x-cloak
            style="z-index: 80;"
            class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            @click.self="modalUnitTerbuka = false"
        >
            <div class="w-full max-w-md rounded-2xl bg-card-bg p-6 shadow-2xl border border-card-border flex flex-col gap-4">
                <div class="flex items-center justify-between border-b border-card-border pb-3">
                    <div class="flex items-center gap-2 text-primary font-bold">
                        <span class="material-symbols-outlined text-[1.25rem]">local_shipping</span>
                        <span>Daftarkan Unit Truk Baru</span>
                    </div>
                    <button type="button" @click="modalUnitTerbuka = false" class="text-slate-text-muted hover:text-slate-text-primary">
                        <span class="material-symbols-outlined text-[1.25rem]">close</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-text-primary">Nomor Polisi</label>
                        <input
                            type="text"
                            x-model="formUnit.nomor_polisi"
                            class="h-9 w-full rounded-lg border border-card-border bg-canvas-bg px-3 font-mono text-xs font-bold uppercase text-slate-text-primary"
                        >
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-text-primary">Merek / Model</label>
                        <input
                            type="text"
                            x-model="formUnit.merek_model"
                            placeholder="Contoh: HINO 500 TI"
                            class="h-9 w-full rounded-lg border border-card-border bg-canvas-bg px-3 text-xs text-slate-text-primary"
                        >
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-text-primary">Jenis Truk</label>
                        <select
                            x-model="formUnit.id_jenis_truk"
                            class="h-9 w-full rounded-lg border border-card-border bg-canvas-bg px-3 text-xs text-slate-text-primary"
                        >
                            @foreach ($daftarJenisTruk as $jenis)
                                <option value="{{ $jenis->id }}">{{ $jenis->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-card-border">
                    <button
                        type="button"
                        @click="modalUnitTerbuka = false"
                        class="rounded-lg border border-card-border px-3 py-1.5 text-xs font-semibold text-slate-text-secondary"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="simpanUnitCepat()"
                        :disabled="sedangSimpanUnit"
                        class="rounded-lg bg-primary-container px-4 py-1.5 text-xs font-bold text-on-primary shadow-sm hover:bg-primary"
                    >
                        <span x-text="sedangSimpanUnit ? 'Menyimpan...' : 'Simpan Unit'"></span>
                    </button>
                </div>
            </div>
        </div>
        {{-- Kartu Progres Eksekusi Latar Belakang (pojok kanan atas, non-blocking) --}}
        <div
            x-show="modalProgressTerbuka"
            x-cloak
            style="z-index: 94;"
            class="pointer-events-none fixed right-4 top-4 w-full max-w-sm"
            aria-live="polite"
            role="status"
        >
            <div class="pointer-events-auto w-full rounded-2xl bg-card-bg p-5 shadow-2xl border border-card-border flex flex-col gap-3.5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm"
                        :class="progressGagal ? 'bg-danger-surface text-danger-red' : (progressSelesai ? 'bg-net-pay-surface text-net-pay-emerald' : 'bg-primary/10 text-primary')"
                    >
                        <span
                            class="material-symbols-outlined text-[1.75rem]"
                            :class="(!progressSelesai && !progressGagal) ? 'animate-spin' : ''"
                            x-text="progressGagal ? 'error' : (progressSelesai ? 'task_alt' : 'sync')"
                        ></span>
                    </span>
                    <div class="flex flex-col min-w-0">
                        <h3 class="font-display text-body-md font-bold text-slate-text-primary" x-text="progressGagal ? 'Impor Gagal Diproses' : (progressSelesai ? 'Impor Selesai 100%' : 'Memproses Impor di Latar Belakang...')"></h3>
                        <p class="font-sans text-xs text-slate-text-secondary truncate" x-text="progressPesan"></p>
                    </div>
                    <button
                        type="button"
                        x-show="progressGagal || progressSelesai"
                        x-cloak
                        @click="modalProgressTerbuka = false; $store.panelProgres.aktif = false"
                        title="Tutup kartu progres"
                        class="ml-auto flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-text-muted transition-colors hover:bg-surface-container hover:text-slate-text-primary"
                    >
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                {{-- Progress Bar --}}
                <div class="flex flex-col gap-1.5 pt-1">
                    <div class="flex items-center justify-between font-financial-table text-xs font-bold">
                        <span class="text-slate-text-secondary">Kemajuan Impor Database</span>
                        <span class="text-primary tabular-nums" x-text="progressPersen + '%'"></span>
                    </div>
                    <div class="h-3 w-full rounded-full bg-surface-container overflow-hidden p-0.5">
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="progressGagal ? 'bg-danger-red' : (progressSelesai ? 'bg-net-pay-emerald' : 'bg-primary')"
                            :style="'width: ' + progressPersen + '%'"
                        ></div>
                    </div>
                </div>

                {{-- Error Message Box --}}
                <div x-show="progressGagal" class="rounded-xl border border-danger-red/30 bg-danger-surface p-3 text-xs text-danger-red font-sans">
                    <span class="font-bold block mb-0.5">Detail Kesalahan:</span>
                    <span x-text="progressGalat"></span>
                </div>

                <div x-show="progressSelesai" class="rounded-xl border border-net-pay-emerald/30 bg-net-pay-surface p-3 text-xs text-net-pay-emerald font-sans">
                    <span>Transaksi database berhasil dikomit. Anda akan dialihkan ke halaman laporan...</span>
                </div>

                <div x-show="progressGagal" class="flex justify-end pt-2 border-t border-card-border">
                    <button
                        type="button"
                        @click="modalProgressTerbuka = false; $store.panelProgres.aktif = false"
                        class="rounded-lg bg-slate-200 px-4 py-2 font-sans text-xs font-bold text-slate-800 hover:bg-slate-300"
                    >
                        Tutup &amp; Perbaiki Pilihan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function pratinjauMigrasi(config) {
                const supirInit = {};
                (config.daftarSupir || []).forEach(s => {
                    supirInit[s.nama] = {
                        aksi: s.aksi_default || 'buat_akun',
                        email: s.email_default || '',
                        nip: s.nip_default || '',
                        id_karyawan_tujuan: s.id_karyawan_tujuan || '',
                    };
                });

                const unitInit = {};
                (config.daftarUnit || []).forEach(u => {
                    unitInit[u.nomor_polisi] = {
                        aksi: u.aksi_default || 'daftar_baru',
                        merek_model: u.merek_model || 'HINO PUTIH',
                        id_jenis_truk: '1',
                        id_truk_tujuan: '',
                    };
                });

                return {
                    token: config.token,
                    subtabAktif: config.subtabAwal || 'supir',
                    keputusanSupir: supirInit,
                    keputusanUnit: unitInit,
                    sedangSimpan: false,
                    modalUnitTerbuka: false,
                    sedangSimpanUnit: false,
                    modalProgressTerbuka: false,
                    progressPersen: 0,
                    progressPesan: 'Menyiapkan transaksi database...',
                    progressSelesai: false,
                    progressGagal: false,
                    progressGalat: '',
                    formUnit: {
                        nomor_polisi: '',
                        merek_model: 'HINO PUTIH',
                        id_jenis_truk: '1',
                    },
                    pilihSemuaSupir(aksi) {
                        for (const nama in this.keputusanSupir) {
                            if (this.keputusanSupir[nama].aksi !== 'terdaftar') {
                                this.keputusanSupir[nama].aksi = aksi;
                            }
                        }
                    },

                    pilihSemuaUnit(aksi) {
                        for (const nopol in this.keputusanUnit) {
                            if (this.keputusanUnit[nopol].aksi !== 'terdaftar') {
                                this.keputusanUnit[nopol].aksi = aksi;
                            }
                        }
                    },

                    bukaModalDaftarUnit(nopol, merek) {
                        this.formUnit.nomor_polisi = nopol;
                        this.formUnit.merek_model = merek || 'HINO PUTIH';
                        this.modalUnitTerbuka = true;
                    },

                    async simpanUnitCepat() {
                        this.sedangSimpanUnit = true;
                        try {
                            const res = await fetch('{{ route("admin.reports.migrasi.tambah-unit-ajax") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(this.formUnit),
                            });
                            const data = await res.json();
                            if (data.success) {
                                window.tampilkanToast?.({
                                    type: 'success',
                                    title: 'Unit Didaftarkan',
                                    message: data.message,
                                });
                                this.modalUnitTerbuka = false;
                                if (this.keputusanUnit[this.formUnit.nomor_polisi]) {
                                    this.keputusanUnit[this.formUnit.nomor_polisi].aksi = 'terdaftar';
                                }
                            } else {
                                alert(data.message || 'Gagal menyimpan unit');
                            }
                        } catch (e) {
                            alert('Terjadi kesalahan jaringan.');
                        } finally {
                            this.sedangSimpanUnit = false;
                        }
                    },

                    async validasiEksekusi(event) {
                        event.preventDefault();
                        if (this.sedangSimpan) return false;

                        this.sedangSimpan = true;
                        this.modalProgressTerbuka = true;
                        this.$store.panelProgres.aktif = true;
                        this.progressPersen = 5;
                        this.progressPesan = 'Memulai proses impor data di latar belakang...';
                        this.progressSelesai = false;
                        this.progressGagal = false;
                        this.progressGalat = '';

                        let timerProgress = null;
                        timerProgress = setInterval(async () => {
                            try {
                                const res = await fetch('{{ route("admin.reports.migrasi.status-progress", ["token" => $token]) }}');
                                const data = await res.json();
                                if (data && typeof data.persen === 'number') {
                                    this.progressPersen = Math.max(this.progressPersen, data.persen);
                                    if (data.pesan) {
                                        this.progressPesan = data.pesan;
                                    }
                                }
                            } catch (e) {}
                        }, 350);

                        try {
                            const res = await fetch('{{ route("admin.reports.migrasi.eksekusi-async") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    token: this.token,
                                    supir: this.keputusanSupir,
                                    unit: this.keputusanUnit,
                                    rute: this.keputusanRute,
                                }),
                            });

                            clearInterval(timerProgress);
                            const data = await res.json();

                            if (res.ok && data.success) {
                                this.progressPersen = 100;
                                this.progressPesan = data.message || 'Seluruh data berhasil disimpan ke database!';
                                this.progressSelesai = true;

                                // Toast sukses ditampilkan langsung di halaman ini agar pengguna
                                // melihat konfirmasi sebelum dialihkan ke halaman laporan.
                                window.tampilkanToast?.({
                                    success: true,
                                    type: 'success',
                                    title: 'Migrasi Data Excel Berhasil',
                                    action: 'IMPORT_MIGRASI_EXCEL',
                                    status: 'COMPLETED',
                                    message: this.progressPesan,
                                    data: data.data,
                                    timestamp: new Date().toISOString(),
                                });

                                setTimeout(() => {
                                    window.location.href = data.redirect || '{{ route("admin.reports.index") }}';
                                }, 2400);
                            } else {
                                this.progressGagal = true;
                                this.progressGalat = data.message || 'Terjadi kesalahan saat memproses data.';
                                this.sedangSimpan = false;
                                this.$store.panelProgres.aktif = false;

                                window.tampilkanToast?.({
                                    success: false,
                                    type: 'error',
                                    title: 'Impor Migrasi Gagal',
                                    action: 'IMPORT_MIGRASI_EXCEL',
                                    status: 'FAILED',
                                    message: this.progressGalat,
                                    details: data.errors ? Object.values(data.errors).flat() : [],
                                    timestamp: new Date().toISOString(),
                                });
                            }
                        } catch (e) {
                            clearInterval(timerProgress);
                            this.progressGagal = true;
                            this.progressGalat = 'Koneksi ke server terputus. Silakan periksa jaringan dan coba lagi.';
                            this.sedangSimpan = false;
                            this.$store.panelProgres.aktif = false;

                            window.tampilkanToast?.({
                                success: false,
                                type: 'error',
                                title: 'Impor Migrasi Gagal',
                                action: 'IMPORT_MIGRASI_EXCEL',
                                status: 'FAILED',
                                message: this.progressGalat,
                                timestamp: new Date().toISOString(),
                            });
                        }

                        return false;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
