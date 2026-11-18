<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN DETAIL RINCIAN PERIODE LAPORAN):
     - Tujuan Halaman: Menampilkan tabel rincian gaji seluruh supir untuk satu periode tertentu,
       lengkap dengan perolehan tonase, uang jalan, potongan kasbon, dan tombol cetak slip per supir.
     - Analogi Dunia Nyata: Seperti membuka map satu bulan gajian: melihat satu per satu nama supir,
       berapa ton batubara yang dia bawa, berapa komisi 10% yang diperoleh, cicilan kasbon yang dipotong,
       dan tombol cetak slip gaji perorangan untuk dibagikan ke supir.
     - Data dari Controller (Admin/ReportController@show):
       * $periode: Info kode periode ('Jan-26'), rentang tanggal, status persetujuan, dan tanggal bayar.
       * $daftarSupir: Daftar gaji supir dari tabel `penggajian` join `karyawan`.
       * $ringkasanFinansial: Total uang jalan, total komisi, potongan, dan kas bersih yang dikeluarkan.
     ============================================================================== -->
@php
    $rp = fn ($nilai) => 'Rp '.number_format((int) round((float) $nilai), 0, ',', '.');
@endphp

<x-app-layout :title="'Rincian Periode ' . $periode['code']">
    <div
        x-data="reportsPeriodDetailPage(@js($daftarSupir), @js($periode), @js($cariAwal))"
        @keydown.escape.window="tutupModalRitaseSupir()"
        class="space-y-6"
    >
        {{-- 1. Breadcrumb & Period Header Section --}}
        <div class="space-y-4">
            {{-- Breadcrumb --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <nav class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                    <a
                        href="{{ route('admin.reports.index') }}"
                        class="inline-flex items-center gap-1.5 text-slate-600 hover:text-blue-600 font-semibold transition-colors"
                    >
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        <span>Laporan &amp; Slip Gaji</span>
                    </a>
                    <span class="material-symbols-outlined text-[16px] text-slate-400">chevron_right</span>
                    <span class="font-bold text-slate-900">Rincian Periode {{ $periode['code'] }}</span>
                </nav>

                {{-- Pemilih Cepat Periode --}}
                <div class="flex items-center gap-2">
                    <label for="pilihPeriodeCepat" class="text-xs font-semibold text-slate-500">Ganti Periode:</label>
                    <select
                        id="pilihPeriodeCepat"
                        onchange="if (this.value) window.location.href = this.value"
                        class="bg-white border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-600"
                    >
                        @foreach($opsiPeriode as $opsi)
                            <option
                                value="{{ route('admin.reports.show', $opsi['kode']) }}"
                                @selected($opsi['kode'] === $periode['code'])
                            >
                                {{ $opsi['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Period Header Card --}}
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-2xs flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div class="flex items-start md:items-center gap-4 flex-col sm:flex-row">
                    <div class="flex items-center justify-center bg-blue-100 text-blue-700 border border-blue-200 px-4 py-2.5 rounded-xl font-headline text-xl font-bold tracking-tight shadow-2xs shrink-0">
                        {{ $periode['code'] }}
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center flex-wrap gap-3">
                            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight font-headline">
                                Rincian Penggajian Supir — Periode {{ $periode['code'] }}
                            </h1>
                            @if($periode['status_filter'] === 'dibayar')
                                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold">
                                    <span class="material-symbols-outlined text-[14px]">done_all</span>
                                    <span>{{ $periode['status'] }}</span>
                                </span>
                            @elseif($periode['status_filter'] === 'disetujui')
                                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                    <span>{{ $periode['status'] }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>{{ $periode['status'] }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600">
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-slate-400">date_range</span>
                                <span>Siklus Operasional: <strong class="text-slate-800">{{ $periode['range'] }}</strong></span>
                            </span>
                            <span class="text-slate-300">•</span>
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-slate-400">schedule</span>
                                <span>Cut-Off Pengantaran: <strong class="text-slate-800">{{ $periode['cut_off_lengkap'] }}</strong></span>
                            </span>
                            <span class="text-slate-300">•</span>
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px] text-slate-400">event_available</span>
                                <span>Hari Aktif Pengantaran: <strong class="text-slate-800">{{ $periode['work_days'] }} Hari</strong></span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                    <button
                        type="button"
                        @click="$dispatch('buka-modal-migrasi-excel', { tab: 'import', periode: '{{ $periode['code'] }}' })"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition-all shadow-2xs"
                    >
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                        <span>Import / Export Migrasi Excel</span>
                    </button>
                    <a
                        href="{{ route('admin.reports.periode.print', $periode['code']) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold transition-all shadow-2xs"
                    >
                        <span class="material-symbols-outlined text-[18px] text-blue-600">picture_as_pdf</span>
                        <span>Cetak Rekap Periode (PDF)</span>
                    </a>
                    <a
                        href="{{ route('admin.reports.periode.export', $periode['code']) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-semibold transition-all shadow-2xs"
                    >
                        <span class="material-symbols-outlined text-[18px] text-emerald-600">download</span>
                        <span>Ekspor Data Supir (CSV)</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. Ringkasan 4 KPI Periode Terpilih --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- KPI 1: Total Pengantaran Periode --}}
            <div class="bg-white border border-slate-200 p-5 rounded-xl shadow-2xs flex flex-col justify-between relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Pengantaran Periode</span>
                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-slate-900 tabular-nums tracking-tight">{{ number_format($ringkasan['total_rit'], 0, ',', '.') }} Rit</div>
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-slate-600">
                        <span class="material-symbols-outlined text-emerald-500 text-[16px]">check_circle</span>
                        <span>{{ $ringkasan['jumlah_supir'] }} Supir Armada Terhitung</span>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-600/20"></div>
            </div>

            {{-- KPI 2: Total Tonase Muatan --}}
            <div class="bg-white border border-slate-200 p-5 rounded-xl shadow-2xs flex flex-col justify-between relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Tonase Muatan</span>
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">scale</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-slate-900 tabular-nums tracking-tight">{{ $ringkasan['total_tonase_label'] }} Ton</div>
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-slate-600">
                        <span class="material-symbols-outlined text-indigo-600 text-[16px]">trending_up</span>
                        <span>Rata-rata {{ $ringkasan['rata_ton_per_rit'] }} Ton/Rit</span>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-indigo-600/20"></div>
            </div>

            {{-- KPI 3: Total Angkutan Kotor --}}
            <div class="bg-white border border-slate-200 p-5 rounded-xl shadow-2xs flex flex-col justify-between relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Angkutan Kotor</span>
                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-slate-900 tabular-nums tracking-tight">{{ $rp($ringkasan['total_angkutan_kotor']) }}</div>
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-slate-600">
                        <span class="material-symbols-outlined text-slate-400 text-[16px]">payments</span>
                        <span>Uang Jalan: {{ $rp($ringkasan['total_uang_jalan']) }}</span>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-600/20"></div>
            </div>

            {{-- KPI 4: Total Gaji Bersih (Net Pay) --}}
            <div class="bg-white border border-emerald-200 p-5 rounded-xl shadow-2xs flex flex-col justify-between relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Total Gaji Bersih (Net Pay)</span>
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-extrabold text-emerald-600 tabular-nums tracking-tight">{{ $rp($ringkasan['total_gaji_bersih']) }}</div>
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-emerald-700 font-medium">
                        <span class="material-symbols-outlined text-[16px]">verified</span>
                        <span>Sparepart, BPJS &amp; Kasbon Terpotong</span>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500"></div>
            </div>
        </div>

        {{-- 3. Toolbar & Filters Card --}}
        <div class="bg-white border border-slate-200 p-4 rounded-xl shadow-2xs space-y-3">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                {{-- Search Input --}}
                <div class="relative w-full lg:w-96 flex items-center">
                    <span class="material-symbols-outlined text-slate-400 text-[20px] absolute left-3 pointer-events-none">search</span>
                    <input
                        id="driverSearchInput"
                        type="text"
                        x-model.debounce.300ms="kataKunci"
                        @input.debounce.300ms="halamanAktif = 1"
                        placeholder="Cari nama supir, NIP, atau plat truk..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-slate-800 placeholder:text-slate-400 outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-colors text-sm font-medium"
                    />
                </div>

                {{-- Filter Badges/Tabs --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1 lg:pb-0 text-xs">
                    <button
                        type="button"
                        @click="pilihTab('semua')"
                        :class="tabFilter === 'semua' ? 'bg-blue-600 text-white font-semibold shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                        class="px-3 py-2 rounded-lg shrink-0 transition-all"
                    >
                        Semua Supir ({{ $ringkasan['jumlah_supir'] }})
                    </button>
                    <button
                        type="button"
                        @click="pilihTab('tanpa_kasbon')"
                        :class="tabFilter === 'tanpa_kasbon' ? 'bg-emerald-600 text-white font-semibold shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium'"
                        class="px-3 py-2 rounded-lg shrink-0 transition-all"
                    >
                        Lunas Tanpa Kasbon ({{ $ringkasan['jumlah_tanpa_kasbon'] }})
                    </button>
                    <button
                        type="button"
                        x-show="kataKunci.trim() !== '' || tabFilter !== 'semua'"
                        x-cloak
                        @click="kataKunci = ''; pilihTab('semua')"
                        class="inline-flex items-center gap-1 px-3 py-2 rounded-lg shrink-0 border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 font-semibold transition-all"
                    >
                        <span class="material-symbols-outlined text-[15px]">restart_alt</span>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            {{-- Operational Hint Banner --}}
            <div class="bg-blue-50/70 border border-blue-100 px-3.5 py-2.5 rounded-lg flex items-center gap-2.5 text-slate-600 text-xs">
                <span class="material-symbols-outlined text-blue-600 text-[20px] shrink-0">info</span>
                <span>
                    <strong class="font-semibold text-slate-900">Petunjuk Audit:</strong>
                    Klik baris supir mana saja atau tombol <strong class="text-blue-700">"Catatan Pengantaran"</strong> untuk membuka modal besar rincian surat jalan (DO), tiket timbangan, dan formula perhitungan slip gaji.
                </span>
            </div>
        </div>

        {{-- 4. Main Driver Payroll Table --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden flex flex-col">
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left text-sm border-collapse" id="payrollTable">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-[11px] font-bold uppercase tracking-wider select-none">
                            <th class="py-3.5 px-3 text-center w-12">No</th>
                            <th class="py-3.5 px-3 min-w-[200px]">Supir &amp; NIP</th>
                            <th class="py-3.5 px-3 min-w-[180px]">Unit Truk Pegangan</th>
                            <th class="py-3.5 px-3 text-right min-w-[85px]">Total Rit</th>
                            <th class="py-3.5 px-3 text-right min-w-[120px]">Total Tonase</th>
                            <th class="py-3.5 px-3 text-right min-w-[135px]">Angkutan Kotor</th>
                            <th class="py-3.5 px-3 text-right min-w-[125px]">Uang Jalan</th>
                            <th class="py-3.5 px-3 text-right min-w-[120px]">Komisi Supir</th>
                            <th class="py-3.5 px-3 text-right min-w-[170px]">Total Potongan</th>
                            <th class="py-3.5 px-3 text-right min-w-[135px] bg-emerald-50/70 text-emerald-900">Gaji Bersih</th>
                            <th class="py-3.5 px-3 text-center min-w-[165px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 text-slate-800">
                        <template x-for="(supir, indeks) in supirHalamanIni" :key="supir.id_penggajian">
                            <tr
                                @click="bukaModalRitaseSupir(supir)"
                                class="hover:bg-blue-50/40 transition-colors group cursor-pointer"
                            >
                                <td
                                    class="py-3 px-3 text-center text-xs font-semibold text-slate-400 group-hover:text-blue-600 tabular-nums"
                                    x-text="String(indeksAwal + indeks).padStart(2, '0')"
                                ></td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0"
                                            x-text="supir.inisial"
                                        ></div>
                                        <div class="flex flex-col">
                                            <span
                                                class="font-semibold text-sm text-slate-900 group-hover:text-blue-600 transition-colors"
                                                x-text="supir.nama"
                                            ></span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[11px] font-mono text-slate-500" x-text="supir.nik"></span>
                                                <template x-if="supir.anomali">
                                                    <span
                                                        class="text-[10px] px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 font-semibold"
                                                        x-text="supir.catatan_anomali"
                                                    ></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-900" x-text="supir.nopol"></span>
                                        <span class="text-[11px] text-slate-500">
                                            <span x-text="supir.model_truk"></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-right font-medium tabular-nums" x-text="supir.total_rit + ' Rit'"></td>
                                <td class="py-3 px-3 text-right font-medium tabular-nums" x-text="supir.total_tonase_label + ' Ton'"></td>
                                <td class="py-3 px-3 text-right tabular-nums text-slate-600" x-text="formatRupiah(supir.angkutan_kotor)"></td>
                                <td class="py-3 px-3 text-right tabular-nums text-slate-600" x-text="formatRupiah(supir.uang_jalan)"></td>
                                <td class="py-3 px-3 text-right tabular-nums font-semibold text-slate-900" x-text="formatRupiah(supir.gaji_komisi)"></td>
                                <td class="py-3 px-3 text-right tabular-nums">
                                    <div class="flex flex-col items-end">
                                        <span
                                            class="font-semibold text-xs"
                                            :class="supir.total_potongan > 0 ? 'text-rose-600' : 'text-slate-400'"
                                            x-text="'- ' + formatRupiah(supir.total_potongan)"
                                        ></span>
                                        <span class="text-[11px] text-slate-400" x-text="supir.catatan_potongan"></span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-right tabular-nums bg-emerald-50/40 group-hover:bg-emerald-50/80">
                                    <span class="font-bold text-emerald-700 text-sm" x-text="formatRupiah(supir.gaji_bersih)"></span>
                                </td>
                                <td class="py-3 px-3 text-center" @click.stop>
                                    <x-ui.tabel-aksi label="Aksi Supir">
                                        <x-ui.tabel-aksi-item ikon="visibility" @click="bukaModalRitaseSupir(supir)">
                                            Lihat Pengantaran &amp; Slip
                                        </x-ui.tabel-aksi-item>
                                        <a
                                            :href="supir.url_cetak_slip"
                                            target="_blank"
                                            role="menuitem"
                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 font-sans text-body-sm font-medium text-banner-indigo transition-colors hover:bg-surface-container-low"
                                        >
                                            <span class="material-symbols-outlined text-[1.25rem]">print</span>
                                            <span>Cetak Slip Gaji</span>
                                        </a>
                                    </x-ui.tabel-aksi>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="supirTerfilter.length === 0" x-cloak>
                            <td colspan="11" class="py-12 px-4 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-slate-300">person_off</span>
                                    <p class="font-semibold text-slate-700">Data Tidak Ditemukan</p>
                                    <p class="text-xs text-slate-500">Tidak ada data supir yang sesuai dengan kata kunci pencarian atau filter.</p>
                                    <button
                                        type="button"
                                        @click="kataKunci = ''; pilihTab('semua')"
                                        class="mt-1 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold hover:bg-blue-100"
                                    >
                                        Reset Filter &amp; Pencarian
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    {{-- Table Aggregate Footer --}}
                    <tfoot x-show="supirTerfilter.length > 0">
                        <tr class="bg-slate-100/90 border-t-2 border-slate-200 text-slate-900 text-xs font-bold">
                            <td class="py-3.5 px-3 text-center">TOTAL</td>
                            <td class="py-3.5 px-3" colspan="2" x-text="'Akumulasi Terfilter (' + akumulasiTerfilter.jumlahSupir + ' Supir)'"></td>
                            <td class="py-3.5 px-3 text-right tabular-nums" x-text="formatAngka(akumulasiTerfilter.totalRit) + ' Rit'"></td>
                            <td class="py-3.5 px-3 text-right tabular-nums" x-text="akumulasiTerfilter.totalTonaseLabel + ' Ton'"></td>
                            <td class="py-3.5 px-3 text-right tabular-nums" x-text="formatRupiah(akumulasiTerfilter.totalAngkutanKotor)"></td>
                            <td class="py-3.5 px-3 text-right tabular-nums" x-text="formatRupiah(akumulasiTerfilter.totalUangJalan)"></td>
                            <td class="py-3.5 px-3 text-right tabular-nums" x-text="formatRupiah(akumulasiTerfilter.totalKomisi)"></td>
                            <td class="py-3.5 px-3 text-right tabular-nums text-rose-600" x-text="'- ' + formatRupiah(akumulasiTerfilter.totalPotongan)"></td>
                            <td class="py-3.5 px-3 text-right tabular-nums text-emerald-800 bg-emerald-100/70" x-text="formatRupiah(akumulasiTerfilter.totalGajiBersih)"></td>
                            <td class="py-3.5 px-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Pagination & Meta --}}
            <div class="px-5 py-3.5 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-slate-600 text-xs">
                <div class="flex flex-wrap items-center gap-3">
                    <span>
                        Menampilkan <strong class="text-slate-900" x-text="indeksAwal + '–' + indeksAkhir"></strong>
                        dari <strong class="text-slate-900" x-text="supirTerfilter.length"></strong> supir
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400">Baris:</span>
                        <select x-model.number="perHalaman" @change="ubahPerHalaman()" data-no-select2 class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 font-semibold">
                    <button
                        type="button"
                        @click="pindahHalaman(halamanAktif - 1)"
                        :disabled="halamanAktif <= 1"
                        class="inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg border border-slate-200 px-2.5 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                    </button>
                    <template x-for="hal in totalHalaman" :key="hal">
                        <button
                            type="button"
                            @click="pindahHalaman(hal)"
                            :class="hal === halamanAktif ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                            class="inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg border px-2.5 text-xs font-bold transition-colors"
                            x-text="hal"
                        ></button>
                    </template>
                    <button
                        type="button"
                        @click="pindahHalaman(halamanAktif + 1)"
                        :disabled="halamanAktif >= totalHalaman"
                        class="inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg border border-slate-200 px-2.5 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ===================================================================== --}}
        {{-- LAYAR 3: MODAL LARGE CATATAN PENGANTARAN (RITASE) SUPIR              --}}
        {{-- ===================================================================== --}}
        <div
            x-show="modalRitaseTerbuka && supirDipilih"
            x-cloak
            style="z-index: 60;"
            class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            @click.self="tutupModalRitaseSupir()"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="modalRitaseHeading"
                class="w-full max-w-5xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
            >
                {{-- 1. Modal Header --}}
                <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-blue-600 text-white font-bold w-11 h-11 rounded-xl flex items-center justify-center text-base tracking-tight shadow-2xs shrink-0"
                            x-text="supirDipilih?.inisial || 'SP'"
                        ></div>
                        <div class="flex flex-col">
                            <div class="flex items-center flex-wrap gap-2.5">
                                <h2 class="text-lg font-bold text-slate-900 leading-snug" id="modalRitaseHeading">
                                    Catatan Pengantaran &amp; Surat Jalan Supir — Periode {{ $periode['code'] }}
                                </h2>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/80 tabular-nums">
                                    {{ $periode['range'] }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 mt-0.5 flex flex-wrap items-center gap-1.5">
                                <span class="font-semibold text-slate-900" x-text="supirDipilih?.nama"></span>
                                <span class="text-slate-400">•</span>
                                <span class="font-mono text-slate-700" x-text="'NIP: ' + (supirDipilih?.nik || '-')"></span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-600">
                                    Unit Armada: <strong class="text-slate-800" x-text="supirDipilih?.nopol"></strong>
                                    (<span x-text="supirDipilih?.model_truk || '-'"></span>)
                                </span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="tutupModalRitaseSupir()"
                        aria-label="Tutup Dialog"
                        class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-200/60 transition-colors flex items-center justify-center"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                {{-- 2. 5 KPI Summary Cards inside Modal --}}
                <div class="p-5 bg-slate-50/50 border-b border-slate-100 grid grid-cols-2 md:grid-cols-5 gap-3 shrink-0">
                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500">
                            <span class="text-[11px] font-semibold tracking-wider uppercase">TOTAL PENGANTARAN</span>
                            <span class="material-symbols-outlined text-slate-400 text-[16px]">local_shipping</span>
                        </div>
                        <div class="my-1.5">
                            <div class="text-xl font-extrabold text-slate-900 tabular-nums tracking-tight" x-text="(supirDipilih?.total_rit || 0) + ' Rit'"></div>
                        </div>
                        <div class="flex items-center gap-1 text-[11px] text-emerald-600 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span x-text="(supirDipilih?.daftar_ritase?.length || 0) + ' Tiket Tercatat'"></span>
                        </div>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500">
                            <span class="text-[11px] font-semibold tracking-wider uppercase">TOTAL TONASE</span>
                            <span class="material-symbols-outlined text-slate-400 text-[16px]">scale</span>
                        </div>
                        <div class="my-1.5">
                            <div class="text-xl font-extrabold text-slate-900 tabular-nums tracking-tight">
                                <span x-text="supirDipilih?.total_tonase_label || '0,00'"></span>
                                <span class="text-sm font-semibold text-slate-600">Ton</span>
                            </div>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Rata-rata <span class="font-semibold text-slate-700" x-text="(supirDipilih?.rata_ton_per_rit || '0,00') + ' T/Rit'"></span>
                        </div>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500">
                            <span class="text-[11px] font-semibold tracking-wider uppercase">ANGKUTAN KOTOR</span>
                            <span class="material-symbols-outlined text-blue-500 text-[16px]">receipt_long</span>
                        </div>
                        <div class="my-1.5">
                            <div class="text-lg font-extrabold text-blue-600 tabular-nums tracking-tight" x-text="formatRupiah(supirDipilih?.angkutan_kotor)"></div>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Tarif <span class="font-semibold text-slate-700" x-text="formatRupiah(supirDipilih?.tarif_rata_per_ton) + '/T'"></span>
                        </div>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500">
                            <span class="text-[11px] font-semibold tracking-wider uppercase">TOTAL UANG JALAN</span>
                            <span class="material-symbols-outlined text-amber-500 text-[16px]">account_balance_wallet</span>
                        </div>
                        <div class="my-1.5">
                            <div class="text-lg font-extrabold text-amber-600 tabular-nums tracking-tight" x-text="formatRupiah(supirDipilih?.uang_jalan)"></div>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Sparepart: <span class="font-semibold text-slate-700" x-text="formatRupiah(supirDipilih?.biaya_sparepart)"></span>
                        </div>
                    </div>

                    <div class="bg-emerald-50/60 p-3.5 rounded-xl border border-emerald-200/80 shadow-2xs flex flex-col justify-between col-span-2 md:col-span-1">
                        <div class="flex items-center justify-between text-emerald-800">
                            <span class="text-[11px] font-bold tracking-wider uppercase" x-text="'KOMISI ' + (supirDipilih?.persen_komisi_label || '-')"></span>
                            <span class="material-symbols-outlined text-emerald-600 text-[16px]">savings</span>
                        </div>
                        <div class="my-1.5">
                            <div class="text-lg font-extrabold text-emerald-600 tabular-nums tracking-tight" x-text="formatRupiah(supirDipilih?.gaji_komisi)"></div>
                        </div>
                        <div class="text-[11px] text-emerald-700 font-semibold">
                            Net Pay: <span class="tabular-nums" x-text="formatRupiah(supirDipilih?.gaji_bersih)"></span>
                        </div>
                    </div>
                </div>

                {{-- 3. Modal Body (Scrollable Table Area) --}}
                <div class="px-6 py-4 overflow-y-auto space-y-4 grow">
                    {{-- Table Toolbar Filter Bar --}}
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex items-center flex-wrap gap-2">
                            <span class="text-xs font-bold text-slate-900 tracking-tight">Daftar Surat Jalan &amp; Tiket Timbangan Lapangan</span>
                            <span class="text-xs text-slate-400">|</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-md text-[11px] font-medium">
                                <span class="w-2 h-2 rounded-full bg-slate-800"></span>
                                <span>Rekening: <strong x-text="(supirDipilih?.bank || '-') + ' • ' + (supirDipilih?.rekening || '-')"></strong></span>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <span class="material-symbols-outlined text-slate-400 text-[15px] absolute left-2.5 top-1/2 -translate-y-1/2">search</span>
                                <input
                                    type="text"
                                    x-model="cariTiketRitase"
                                    placeholder="Cari No. SJ, tanggal, rute..."
                                    class="bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-600"
                                />
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <span class="material-symbols-outlined text-[13px] text-emerald-600">verified</span>
                                <span x-text="tiketRitaseTerfilter.length + ' Tiket Ditampilkan'"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Detailed Trips Table --}}
                    <div class="border border-slate-200 rounded-lg overflow-hidden shadow-2xs bg-white">
                        <div class="overflow-x-auto max-h-[340px]">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-slate-100 text-[11px] font-semibold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="py-2.5 px-3 w-10 text-center" scope="col">No</th>
                                        <th class="py-2.5 px-3" scope="col">Tanggal</th>
                                        <th class="py-2.5 px-3" scope="col">No. Surat Jalan / DO</th>
                                        <th class="py-2.5 px-3" scope="col">Rute</th>
                                        <th class="py-2.5 px-3" scope="col">Material</th>
                                        <th class="py-2.5 px-3 text-center" scope="col">Rit</th>
                                        <th class="py-2.5 px-3 text-right" scope="col">Tonase (Ton)</th>
                                        <th class="py-2.5 px-3 text-right" scope="col">Tarif / Ton</th>
                                        <th class="py-2.5 px-3 text-right" scope="col">Total Angkutan</th>
                                        <th class="py-2.5 px-3 text-right" scope="col">Uang Jalan</th>
                                        <th class="py-2.5 px-3 text-center" scope="col">Status Tiket</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                                    <template x-for="(tiket, idxTiket) in tiketRitaseTerfilter" :key="tiket.id">
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="py-2.5 px-3 text-center font-mono text-slate-500 text-[11px]" x-text="String(idxTiket + 1).padStart(2, '0')"></td>
                                            <td class="py-2.5 px-3 font-medium text-slate-900 tabular-nums whitespace-nowrap" x-text="tiket.tanggal"></td>
                                            <td class="py-2.5 px-3 font-mono font-semibold text-blue-600 whitespace-nowrap" x-text="tiket.no_do"></td>
                                            <td class="py-2.5 px-3 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 tracking-wider"
                                                    :title="tiket.nama_rute"
                                                    x-text="tiket.kode_rute"
                                                ></span>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap" x-text="tiket.material"></td>
                                            <td class="py-2.5 px-3 text-center font-bold text-slate-900 tabular-nums" x-text="tiket.rit"></td>
                                            <td class="py-2.5 px-3 text-right font-medium tabular-nums text-slate-900" x-text="tiket.tonase_label"></td>
                                            <td class="py-2.5 px-3 text-right tabular-nums text-slate-500 whitespace-nowrap" x-text="formatRupiah(tiket.tarif_per_ton)"></td>
                                            <td class="py-2.5 px-3 text-right font-semibold text-slate-900 tabular-nums whitespace-nowrap" x-text="formatRupiah(tiket.total_angkutan)"></td>
                                            <td class="py-2.5 px-3 text-right text-amber-700 font-medium tabular-nums whitespace-nowrap" x-text="formatRupiah(tiket.uang_jalan)"></td>
                                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                                    :class="tiket.status === 'Valid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                                >
                                                    <span
                                                        class="w-1.5 h-1.5 rounded-full"
                                                        :class="tiket.status === 'Valid' ? 'bg-emerald-500' : 'bg-amber-500'"
                                                    ></span>
                                                    <span x-text="tiket.status"></span>
                                                </span>
                                            </td>
                                        </tr>
                                    </template>

                                    <tr x-show="tiketRitaseTerfilter.length === 0">
                                        <td colspan="11" class="py-8 text-center text-slate-500">
                                            Tidak ada tiket surat jalan yang ditemukan untuk kata kunci tersebut.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Calculation Note Callout --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5 text-xs text-slate-600 flex flex-col md:flex-row md:items-center md:justify-between gap-3 shadow-2xs">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-blue-600 text-[18px] shrink-0 mt-0.5">calculate</span>
                            <div>
                                <span class="font-bold text-slate-800">Formula Payroll Logistik:</span>
                                <p class="text-slate-600 leading-relaxed mt-0.5">
                                    Perhitungan Komisi: <strong x-text="supirDipilih?.persen_komisi_label || '-'"></strong> &times;
                                    (Hasil Bersih Truk <span class="font-semibold tabular-nums text-slate-800" x-text="formatRupiah(supirDipilih?.hasil_bersih_armada)"></span>) =
                                    <span class="font-bold text-emerald-700 tabular-nums" x-text="formatRupiah(supirDipilih?.gaji_komisi)"></span>.
                                    Dikurangi BPJS (<span class="tabular-nums font-medium text-rose-600" x-text="formatRupiah(supirDipilih?.potongan_bpjs)"></span>) &amp;
                                    Kasbon (<span class="tabular-nums font-medium text-rose-600" x-text="formatRupiah(supirDipilih?.potongan_kasbon)"></span>) =
                                    <strong class="text-slate-900">Take Home Pay <span class="text-emerald-600 tabular-nums font-extrabold" x-text="formatRupiah(supirDipilih?.gaji_bersih)"></span></strong>.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 self-end md:self-center shrink-0 bg-white px-3 py-1.5 rounded-md border border-slate-200">
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">qr_code_2</span>
                            <div class="flex flex-col text-[11px] leading-tight font-mono">
                                <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">VERIFIED PAYROLL</span>
                                <span class="font-semibold text-slate-800" x-text="'ID: ' + (supirDipilih?.kode_slip || '-')"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Sticky Modal Footer --}}
                <div class="bg-slate-50 border-t border-slate-200 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                    <div class="text-xs text-slate-800 flex flex-wrap items-center gap-1.5">
                        <span class="text-slate-500 font-medium">Akumulasi Periode:</span>
                        <span class="tabular-nums font-semibold text-slate-900" x-text="(supirDipilih?.total_rit || 0) + ' Rit'"></span>
                        <span class="text-slate-300">•</span>
                        <span class="tabular-nums font-semibold text-slate-900" x-text="(supirDipilih?.total_tonase_label || '0,00') + ' Ton'"></span>
                        <span class="text-slate-300">•</span>
                        <span class="text-slate-600">Angkutan:</span>
                        <span class="tabular-nums font-bold text-blue-700" x-text="formatRupiah(supirDipilih?.angkutan_kotor)"></span>
                        <span class="text-slate-300">•</span>
                        <span class="text-slate-600">Uang Jalan:</span>
                        <span class="tabular-nums font-bold text-amber-700" x-text="formatRupiah(supirDipilih?.uang_jalan)"></span>
                    </div>
                    <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                        <button
                            type="button"
                            @click="tutupModalRitaseSupir()"
                            class="w-full sm:w-auto bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 px-4 py-2 rounded-lg font-medium text-sm transition-colors shadow-2xs"
                        >
                            Tutup
                        </button>
                        <a
                            :href="supirDipilih?.url_cetak_slip || '#'"
                            target="_blank"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold text-sm shadow-2xs flex items-center justify-center gap-2 transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]">print</span>
                            <span>Cetak Slip Supir (PDF)</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <x-payroll.excel-sync-modal
            :opsi-periode="$opsiPeriode"
            :periode-default="$periode['code']"
            :periode-ekspor-default="$periode['code']"
        />
    </div>
</x-app-layout>
