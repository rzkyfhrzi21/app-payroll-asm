@php
    $rp = fn ($nilai) => 'Rp '.number_format((int) round((float) $nilai), 0, ',', '.');
    $perusahaanAktif = $dataPerusahaan ?? \App\Models\DataPerusahaan::profilAktif();
@endphp

<x-app-layout title="Laporan & Slip Gaji">
    <div
        x-data="reportsArchivePage(@js($barisPeriode), @js($filterAwal))"
        @keydown.escape.window="tutupRingkasanPeriode()"
        class="space-y-6"
    >
        {{-- 1. Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight font-headline">Laporan &amp; Slip Gaji</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">CoalTrans Payroll</span>
                </div>
                <p class="text-sm text-slate-600 max-w-3xl leading-relaxed">
                    Arsip resmi rekapitulasi penggajian supir armada, riwayat persetujuan pimpinan, dan penerbitan slip gaji bulanan {{ $perusahaanAktif->nama_perusahaan }}.
                </p>
            </div>
            <div class="flex items-center flex-wrap gap-2.5 shrink-0">
                <button
                    type="button"
                    @click="$dispatch('buka-modal-migrasi-excel', { tab: 'import', periode: 'auto' })"
                    class="border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 px-4 py-2 rounded-lg font-semibold shadow-2xs flex items-center gap-2 text-sm transition-all"
                >
                    <span class="material-symbols-outlined text-[18px] text-emerald-600">upload_file</span>
                    <span>Import / Export Migrasi Excel</span>
                </button>
                @php
                    $kodePeriodeUtama = $barisPeriode[0]['kode'] ?? 'Jan-26';
                @endphp
                <a
                    href="{{ route('admin.reports.periode.print', $kodePeriodeUtama) }}"
                    target="_blank"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow-sm flex items-center gap-2 text-sm transition-all shadow-blue-600/20 hover:shadow-md"
                >
                    <span class="material-symbols-outlined text-[18px]">print</span>
                    <span>Cetak Rekap PDF</span>
                </a>
            </div>
        </div>

        {{-- 2. Financial KPI Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Total Periode Terdaftar --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Total Periode Terdaftar</span>
                        <div class="text-2xl font-extrabold text-slate-900 tabular-nums">{{ number_format($ringkasan['total_periode'], 0, ',', '.') }} Periode</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">calendar_month</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span>{{ $ringkasan['rentang_tahun_arsip'] }}</span>
                </div>
            </div>

            {{-- Card 2: Total Slip Supir Diterbitkan --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Total Slip Supir Diterbitkan</span>
                        <div class="text-2xl font-extrabold text-slate-900 tabular-nums">{{ number_format($ringkasan['total_slip'], 0, ',', '.') }} Slip Gaji</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">receipt_long</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                    <span class="material-symbols-outlined text-[15px] text-emerald-600">verified</span>
                    <span>100% Arsip Digital Sinkron Basis Data</span>
                </div>
            </div>

            {{-- Card 3: Total Net Pay --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Total Net Pay Tercatat</span>
                        <div class="text-2xl font-extrabold text-slate-900 tabular-nums">{{ $rp($ringkasan['total_net_pay']) }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">payments</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-1.5 text-xs text-slate-600">
                    <span class="material-symbols-outlined text-[15px] text-blue-600">trending_up</span>
                    <span>Rata-rata <strong class="text-slate-800">{{ $rp($ringkasan['rata_net_pay_supir']) }}</strong>/supir</span>
                </div>
            </div>

            {{-- Card 4: Menunggu ACC Pimpinan --}}
            <div class="bg-white border border-amber-200 rounded-xl p-5 shadow-2xs relative overflow-hidden flex flex-col justify-between bg-gradient-to-br from-white via-white to-amber-50/40">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Menunggu ACC Pimpinan</span>
                            @if($ringkasan['jumlah_menunggu_acc'] > 0)
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            @endif
                        </div>
                        <div class="text-2xl font-extrabold text-amber-600 tabular-nums">{{ $ringkasan['jumlah_menunggu_acc'] }} Periode</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">pending_actions</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-amber-100 flex items-center justify-between gap-2 text-xs text-amber-800">
                    <span class="truncate font-semibold">{{ $ringkasan['label_periode_menunggu'] }}</span>
                    @if($ringkasan['jumlah_menunggu_acc'] > 0)
                        <span class="px-2 py-0.5 rounded bg-amber-200/70 text-amber-800 text-[10px] font-bold uppercase shrink-0">Perlu ACC</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 3. Filter Toolbar Card --}}
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                {{-- Filter Bulan --}}
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1" for="filterBulan">Bulan</label>
                    <div class="relative">
                        <select
                            id="filterBulan"
                            x-model="filterBulan"
                            class="w-full appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all cursor-pointer font-medium"
                        >
                            <option value="all">Semua Bulan</option>
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">expand_more</span>
                    </div>
                </div>

                {{-- Filter Tahun --}}
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1" for="filterTahun">Tahun</label>
                    <div class="relative">
                        <select
                            id="filterTahun"
                            x-model="filterTahun"
                            class="w-full appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all cursor-pointer font-medium"
                        >
                            <option value="all">Semua Tahun</option>
                            @foreach($opsiTahun as $tahunItem)
                                <option value="{{ $tahunItem }}">{{ $tahunItem }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">expand_more</span>
                    </div>
                </div>

                {{-- Filter Status ACC --}}
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1" for="filterStatus">Status Persetujuan</label>
                    <div class="relative">
                        <select
                            id="filterStatus"
                            x-model="filterStatus"
                            class="w-full appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all cursor-pointer font-medium"
                        >
                            <option value="all">Semua Status ACC</option>
                            <option value="menunggu">Menunggu ACC / Draf</option>
                            <option value="disetujui">Disetujui (ACC)</option>
                            <option value="dibayar">Dibayar (Lunas)</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">expand_more</span>
                    </div>
                </div>

                {{-- Search Input --}}
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1" for="searchQuery">Pencarian Arsip</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input
                            id="searchQuery"
                            type="text"
                            x-model.debounce.300ms="kataKunci"
                            placeholder="Cari kode periode, rentang tanggal..."
                            class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-lg pl-9 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all placeholder:text-slate-400 font-medium"
                        />
                    </div>
                </div>

                {{-- Reset Filter Button --}}
                <div class="lg:col-span-1 flex items-end pt-4 lg:pt-5">
                    <button
                        type="button"
                        @click="resetFilter()"
                        title="Reset Filter"
                        class="w-full h-[38px] flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]">restart_alt</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- 4. Operational Hint Banner --}}
        <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-lg p-3.5 text-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600 text-[20px] shrink-0">info</span>
                <span class="font-medium text-slate-700">
                    <strong class="text-blue-900">Petunjuk Operasional:</strong> Klik baris periode mana saja untuk membuka lembar kerja <span class="font-semibold text-blue-800">Rincian Penggajian Supir &amp; Audit Pengantaran</span> periode bersangkutan.
                </span>
            </div>
            <div class="flex items-center gap-3 shrink-0 font-medium">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Disetujui</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Menunggu ACC</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Dibayar</span>
            </div>
        </div>

        {{-- 5. Full-Width Data Table Card --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden">
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse min-w-[62rem]" id="tablePeriode">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 text-center w-12 whitespace-nowrap">No</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">Kode Periode</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">Rentang Tanggal (Siklus Cut-Off)</th>
                            <th class="py-3.5 px-4 text-center">Jumlah Supir</th>
                            <th class="py-3.5 px-4 text-right">Total Rit / Ton</th>
                            <th class="py-3.5 px-4 text-right">Total Net Pay (IDR)</th>
                            <th class="py-3.5 px-4 text-center">Status ACC</th>
                            <th class="py-3.5 px-4">Tanggal ACC</th>
                            <th class="py-3.5 px-4 text-center w-48">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 text-slate-700">
                        <template x-for="(baris, indeks) in barisHalamanIni" :key="baris.id">
                            <tr
                                @click="bukaHalamanPeriode(baris.url_detail)"
                                class="hover:bg-blue-50/50 cursor-pointer transition-colors group"
                            >
                                <td
                                    class="py-3.5 px-4 text-center text-xs font-semibold text-slate-400 group-hover:text-blue-600 tabular-nums"
                                    x-text="String(indeksAwal + indeks).padStart(2, '0')"
                                ></td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold border"
                                            :class="indeks === 0 && halamanAktif === 1 ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-slate-100 text-slate-800 border-slate-200'"
                                            x-text="baris.kode"
                                        ></span>
                                        <template x-if="indeks === 0 && halamanAktif === 1">
                                            <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">Terbaru</span>
                                        </template>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-semibold text-slate-900 leading-tight" x-text="baris.rentang_tanggal"></div>
                                    <div class="text-[11px] text-slate-500 font-medium leading-tight mt-0.5" x-text="'Cut-off: ' + baris.tanggal_cut_off"></div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="inline-block px-2.5 py-1 rounded-full bg-slate-100 font-bold text-slate-800 text-xs tabular-nums"
                                        x-text="baris.jumlah_supir + ' Supir'"
                                    ></span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="font-bold text-slate-900 tabular-nums" x-text="formatAngka(baris.total_rit) + ' Rit'"></div>
                                    <div class="text-xs text-slate-500 tabular-nums" x-text="baris.total_tonase_label + ' Ton'"></div>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="font-bold text-slate-900 tabular-nums text-sm" x-text="formatRupiah(baris.total_net_pay)"></div>
                                    <div
                                        class="text-[11px] font-medium tabular-nums"
                                        :class="baris.total_kasbon > 0 ? 'text-rose-600' : 'text-slate-400'"
                                        x-text="'Kasbon: ' + formatRupiah(baris.total_kasbon)"
                                    ></div>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <template x-if="baris.status_filter === 'dibayar'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            <span class="material-symbols-outlined text-[13px]">done_all</span>
                                            <span>Dibayar (Lunas)</span>
                                        </span>
                                    </template>
                                    <template x-if="baris.status_filter === 'disetujui'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="material-symbols-outlined text-[13px]">check_circle</span>
                                            <span>Disetujui (ACC)</span>
                                        </span>
                                    </template>
                                    <template x-if="baris.status_filter === 'menunggu'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span x-text="baris.status_label"></span>
                                        </span>
                                    </template>
                                    <template x-if="baris.status_filter === 'ditolak'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="material-symbols-outlined text-[13px]">cancel</span>
                                            <span>Ditolak</span>
                                        </span>
                                    </template>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <template x-if="baris.sudah_acc">
                                        <div>
                                            <div class="text-xs font-semibold text-slate-800" x-text="baris.tanggal_acc"></div>
                                            <div class="text-[10px] text-slate-500" x-text="'Oleh: ' + baris.pejabat_acc"></div>
                                        </div>
                                    </template>
                                    <template x-if="!baris.sudah_acc">
                                        <span class="text-xs text-slate-400 italic" x-text="baris.tanggal_acc"></span>
                                    </template>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap" @click.stop>
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            @click="bukaRingkasanPeriode(baris)"
                                            title="Ringkasan Cepat Periode"
                                            class="btn-aksi-tabel hover:text-blue-600 hover:bg-blue-50"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        </button>
                                        <a
                                            :href="baris.url_detail"
                                            title="Buka Rincian Daftar Supir"
                                            class="btn-aksi-tabel hover:text-blue-600 hover:bg-blue-50"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                                        </a>
                                        <a
                                            :href="baris.url_cetak"
                                            target="_blank"
                                            title="Cetak Rekap PDF"
                                            class="btn-aksi-tabel hover:text-rose-600 hover:bg-rose-50"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                                        </a>
                                        <a
                                            :href="baris.url_ekspor"
                                            title="Unduh Excel Periode"
                                            class="btn-aksi-tabel hover:text-emerald-600 hover:bg-emerald-50"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">table_chart</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="barisTerfilter.length === 0" x-cloak>
                            <td colspan="9" class="py-12 px-4 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-slate-300">folder_off</span>
                                    <p class="font-semibold text-slate-700">Data Tidak Ditemukan</p>
                                    <p class="text-xs text-slate-500">Tidak ada arsip periode yang sesuai dengan kata kunci pencarian atau filter.</p>
                                    <button
                                        type="button"
                                        @click="resetFilter()"
                                        class="mt-1 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold hover:bg-blue-100 transition-colors"
                                    >
                                        Reset Filter &amp; Pencarian
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Table Footer Pagination --}}
            <div class="p-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-600">
                <div class="flex flex-wrap items-center gap-3">
                    <span>
                        Menampilkan <strong class="text-slate-900" x-text="indeksAwal + '–' + indeksAkhir"></strong> dari
                        <strong class="text-slate-900" x-text="barisTerfilter.length"></strong> periode arsip
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

        {{-- Modal Ringkasan Cepat Periode --}}
        <div
            x-show="modalRingkasanTerbuka && periodeDipilih"
            x-cloak
            style="z-index: 60;"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
            @click.self="tutupRingkasanPeriode()"
        >
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[24px]">folder_managed</span>
                        </div>
                        <div>
                            <h3 class="font-headline text-base font-bold text-slate-900" x-text="'Ringkasan Periode ' + (periodeDipilih?.kode || '')"></h3>
                            <p class="text-xs text-slate-500" x-text="'Siklus: ' + (periodeDipilih?.rentang_tanggal || '')"></p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="tutupRingkasanPeriode()"
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-xl text-xs">
                    <div>
                        <span class="text-slate-500 block">Status Pengajuan</span>
                        <span class="font-bold text-amber-700" x-text="periodeDipilih?.status_label || '-'"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Total Supir Terhitung</span>
                        <span class="font-bold text-slate-900 tabular-nums" x-text="(periodeDipilih?.jumlah_supir || 0) + ' Pengemudi'"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Total Pengantaran &amp; Tonase</span>
                        <span class="font-bold text-slate-900 tabular-nums" x-text="formatAngka(periodeDipilih?.total_rit) + ' Trip (' + (periodeDipilih?.total_tonase_label || '0,00') + ' Ton)'"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Total Angkutan Kotor</span>
                        <span class="font-bold text-slate-900 tabular-nums" x-text="formatRupiah(periodeDipilih?.total_angkutan_kotor)"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Potongan Kasbon</span>
                        <span class="font-bold text-rose-600 tabular-nums" x-text="formatRupiah(periodeDipilih?.total_kasbon)"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Total Bersih (Net Pay)</span>
                        <span class="font-extrabold text-blue-700 tabular-nums" x-text="formatRupiah(periodeDipilih?.total_net_pay)"></span>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl p-3 text-xs space-y-2">
                    <span class="font-bold text-slate-900 block">Informasi Cut-Off &amp; Pembayaran</span>
                    <div class="flex items-center justify-between text-slate-600 py-1 border-b border-slate-100">
                        <span>Batas Cut-Off Pengantaran</span>
                        <span class="font-medium text-slate-800" x-text="periodeDipilih?.tanggal_cut_off || '-'"></span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 py-1">
                        <span>Jadwal / Tanggal Pembayaran</span>
                        <span class="font-medium text-emerald-700" x-text="periodeDipilih?.tanggal_bayar || '-'"></span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="tutupRingkasanPeriode()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50"
                    >
                        Tutup
                    </button>
                    <a
                        :href="periodeDipilih?.url_detail || '#'"
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold flex items-center gap-1.5 shadow-2xs"
                    >
                        <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                        <span x-text="'Buka Rincian ' + (periodeDipilih?.jumlah_supir || 0) + ' Supir'"></span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Toast Feedback --}}
        <div
            x-show="tampilToast"
            x-transition
            x-cloak
            class="fixed bottom-6 right-6 z-50 bg-slate-900 text-white text-xs font-medium px-4 py-3 rounded-lg shadow-xl flex items-center gap-2"
        >
            <span class="material-symbols-outlined text-emerald-400 text-[18px]">check_circle</span>
            <span x-text="pesanToast"></span>
        </div>

        <x-payroll.excel-sync-modal
            :opsi-periode="$opsiPeriode"
            periode-default="auto"
            periode-ekspor-default="all"
        />
    </div>
</x-app-layout>
