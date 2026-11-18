<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN MODAL MIGRASI EXCEL PENGGAJIAN):
     - Tujuan Komponen: Jendela sembul (Modal Popup) untuk mengunggah (Import) dan mengunduh
       (Export) berkas spreadsheet Excel/CSV yang berisi rincian ritase dan gaji supir batubara.
     - Analogi Dunia Nyata: Seperti loket serah terima map berkas fisik: admin membawa satu map
       berisi lembar rekap bulanan supir, lalu loket ini membaca isinya dan memasukkannya ke
       buku besar database secara serentak (atau sebaliknya, mencetak buku besar ke lembar kerja).
     - Data dari Controller / Props:
       * $opsiPeriode: Daftar pilihan bulan/tahun periode gaji yang tersedia.
       * $periodeDefault: Periode yang otomatis terpilih saat modal dibuka.
     - Form & Pemetaan Input ke Database / Service:
       * Input berkas: `name="berkas_excel"` (File .xlsx/.csv) -> diproses ExcelPayrollMigrationService.
       * Input target: `name="target_periode"` -> menentukan id_periode_gaji tujuan migrasi.
       * Opsi status: `name="status_pencairan"` -> menentukan apakah langsung 'paid' atau 'belum_cair'.
     - Interaksi & Event:
       * Terbuka lewat event JavaScript window `@buka-modal-migrasi-excel`.
       * Memiliki 2 tab: Tab 1 untuk Import Berkas dan Tab 2 untuk Export Rekapitulasi.
     ============================================================================== -->
@props([
    'opsiPeriode' => [],
    'periodeDefault' => 'auto',
    'periodeEksporDefault' => 'all',
])

@php
    $adaGalatMigrasi = $errors->has('berkas_excel')
        || $errors->has('target_periode')
        || $errors->has('kode_periode_baru')
        || $errors->has('status_pencairan')
        || $errors->has('konfirmasi_pencairan');

    $konfigurasiModal = [
        'bukaOtomatis' => $adaGalatMigrasi,
        'tabAwal' => 'import',
        'periodeDefault' => old('target_periode', $periodeDefault),
        'statusPencairanAwal' => old('status_pencairan', 'belum_cair'),
        'tanggalHariIni' => old('tanggal_pencairan', now()->toDateString()),
        'periodeEksporDefault' => $periodeEksporDefault,
    ];
@endphp

<div
    x-data="modalMigrasiExcel(@js($konfigurasiModal))"
    @buka-modal-migrasi-excel.window="bukaModal($event.detail || {})"
    @keydown.escape.window="tutupModal()"
>
    <div
        x-show="terbuka"
        x-cloak
        style="z-index: 70;"
        class="fixed inset-0 flex items-center justify-center p-4 sm:p-6 overflow-y-auto bg-slate-950/70 backdrop-blur-xs transition-all duration-200"
        @click.self="tutupModal()"
    >
        <div class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col my-auto">
            {{-- MODAL HEADER --}}
            <div class="px-6 pt-6 pb-4 bg-white flex items-start justify-between border-b border-slate-100">
                <div class="flex items-start gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shadow-2xs shrink-0">
                        <span class="material-symbols-outlined text-[26px]">table_chart</span>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center flex-wrap gap-2">
                            <h2 class="text-lg font-bold text-slate-900 font-headline">Import &amp; Export Migrasi Excel Penggajian</h2>
                            <span class="text-[10px] bg-blue-50 text-blue-700 border border-blue-200 font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Multi-Slip &amp; Pengantaran</span>
                        </div>
                        <p class="text-xs text-slate-600 mt-0.5">
                            Sinkronisasi berkas migrasi Excel/CSV (<strong class="text-slate-800">Rincian Gaji Supir</strong>) sekaligus ke <strong class="text-blue-700">Operasional Pengantaran</strong> &amp; <strong class="text-blue-700">Laporan Slip Gaji</strong>
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="tutupModal()"
                    title="Tutup Modal"
                    class="w-9 h-9 rounded-lg bg-slate-100 text-slate-500 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors"
                >
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            {{-- NAVIGATION TABS --}}
            <div class="px-6 bg-slate-50 border-b border-slate-200 flex items-center gap-6">
                <button
                    type="button"
                    @click="tabAktif = 'import'"
                    :class="tabAktif === 'import' ? 'text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="relative py-3.5 flex items-center gap-2 text-sm transition-colors"
                >
                    <span class="material-symbols-outlined text-[19px]">file_upload</span>
                    <span>Import Migrasi Excel / CSV ke Database</span>
                    <span class="bg-blue-600 text-white text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full">Utama</span>
                    <span
                        x-show="tabAktif === 'import'"
                        class="absolute bottom-0 left-0 right-0 h-[3px] bg-blue-600 rounded-t-full"
                    ></span>
                </button>

                <button
                    type="button"
                    @click="tabAktif = 'export'"
                    :class="tabAktif === 'export' ? 'text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                    class="relative py-3.5 flex items-center gap-2 text-sm transition-colors"
                >
                    <span class="material-symbols-outlined text-[19px]">file_download</span>
                    <span>Export Rekap &amp; Format Migrasi</span>
                    <span
                        x-show="tabAktif === 'export'"
                        class="absolute bottom-0 left-0 right-0 h-[3px] bg-blue-600 rounded-t-full"
                    ></span>
                </button>
            </div>

            {{-- MODAL BODY --}}
            <div class="p-6 max-h-[calc(88vh-155px)] overflow-y-auto">
                {{-- TAB 1: IMPORT --}}
                <form
                    x-show="tabAktif === 'import'"
                    method="POST"
                    action="{{ route('admin.reports.migrasi.preview') }}"
                    target="_blank"
                    data-no-ajax
                    enctype="multipart/form-data"
                    @submit="validasiSebelumSubmit($event)"
                    class="space-y-5"
                >
                    @csrf

                    @if($adaGalatMigrasi)
                        <div class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-700 flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-[18px] text-rose-600 shrink-0">error</span>
                            <div class="space-y-1">
                                <div class="font-bold text-rose-900">Gagal Memproses Impor Migrasi:</div>
                                <ul class="list-disc list-inside space-y-0.5">
                                    @foreach($errors->all() as $pesanError)
                                        <li>{{ $pesanError }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div
                        x-show="pesanGalat !== ''"
                        x-cloak
                        class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-700 flex items-center gap-2.5 font-semibold"
                    >
                        <span class="material-symbols-outlined text-[18px] text-rose-600 shrink-0">error</span>
                        <span x-text="pesanGalat"></span>
                    </div>

                    {{-- 1. Pilih Periode Penggajian Target --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-800 flex items-center justify-between" for="selectPeriodeMigrasi">
                            <span>1. Pilih Periode Penggajian Target</span>
                            <span class="text-[11px] text-slate-500 font-normal">Sinkronisasi otomatis memperbarui pengantaran &amp; slip supir yang sama</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-blue-600 text-[20px]">calendar_month</span>
                            <select
                                id="selectPeriodeMigrasi"
                                name="target_periode"
                                x-model="targetPeriode"
                                class="w-full h-10 pl-11 pr-10 rounded-lg bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all appearance-none cursor-pointer"
                            >
                                <option value="auto">Otomatis Sesuai Kolom PERIODE di Dalam Berkas (Direkomendasikan — Mendukung Multi-Periode)</option>
                                @foreach($opsiPeriode as $opsi)
                                    <option value="{{ $opsi['kode'] }}">Periode {{ $opsi['label'] }}</option>
                                @endforeach
                                <option value="baru">+ Buat Siklus Periode Baru...</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 text-slate-400 pointer-events-none text-[20px]">expand_more</span>
                        </div>

                        <div x-show="targetPeriode === 'baru'" x-cloak class="pt-2">
                            <label class="block text-[11px] font-bold text-slate-600 mb-1" for="inputKodePeriodeBaru">Kode Periode Baru (Contoh: Des-25 atau Feb-26)</label>
                            <input
                                id="inputKodePeriodeBaru"
                                type="text"
                                name="kode_periode_baru"
                                x-model="kodePeriodeBaru"
                                placeholder="Mis. Des-25"
                                class="w-full sm:w-64 bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600"
                            />
                        </div>
                    </div>

                    {{-- 2. Konfirmasi Status Pencairan Gaji (Belum Dicairkan vs Sudah Dicairkan) --}}
                    <div class="space-y-2.5 bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px] text-blue-600">payments</span>
                                <span>2. Konfirmasi Status Pencairan ke Uang Gajian</span>
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-amber-100 text-amber-800">Wajib Dikonfirmasi</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {{-- Opsi A: Belum Dicairkan --}}
                            <label
                                class="relative flex p-3.5 rounded-xl border cursor-pointer transition-all"
                                :class="statusPencairan === 'belum_cair' ? 'bg-amber-50/70 border-amber-400 ring-2 ring-amber-400/30 shadow-2xs' : 'bg-white border-slate-200 hover:bg-slate-50'"
                            >
                                <input
                                    type="radio"
                                    name="status_pencairan"
                                    value="belum_cair"
                                    x-model="statusPencairan"
                                    class="mt-0.5 text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer"
                                />
                                <div class="ml-3 flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900">Belum Dicairkan (Draf / Menunggu ACC)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                                        Surat jalan dicatat sebagai <strong>riwayat terverifikasi</strong>, sedangkan baris gaji masuk ke <strong>Laporan Gaji</strong> sebagai draf aktif. Masih dapat direvisi sebelum uang gajian ditransfer.
                                    </p>
                                </div>
                            </label>

                            {{-- Opsi B: Sudah Dicairkan --}}
                            <label
                                class="relative flex p-3.5 rounded-xl border cursor-pointer transition-all"
                                :class="statusPencairan === 'sudah_cair' ? 'bg-emerald-50/70 border-emerald-400 ring-2 ring-emerald-400/30 shadow-2xs' : 'bg-white border-slate-200 hover:bg-slate-50'"
                            >
                                <input
                                    type="radio"
                                    name="status_pencairan"
                                    value="sudah_cair"
                                    x-model="statusPencairan"
                                    class="mt-0.5 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                                />
                                <div class="ml-3 flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900">Sudah Dicairkan ke Uang Gajian (Lunas)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                                        Khusus migrasi histori Excel yang dananya <strong>sudah dibayarkan</strong> ke supir. Status periode menjadi <strong>Dibayar (Lunas)</strong> &amp; surat jalan <strong>Valid</strong>.
                                    </p>
                                </div>
                            </label>
                        </div>

                        {{-- Tanggal Pencairan bila memilih Sudah Dicairkan --}}
                        <div x-show="statusPencairan === 'sudah_cair'" x-cloak class="pt-1 flex flex-col sm:flex-row sm:items-center gap-2">
                            <label for="inputTanggalPencairan" class="text-xs font-semibold text-emerald-800">Tanggal Pencairan / Transfer Gaji:</label>
                            <input
                                id="inputTanggalPencairan"
                                type="date"
                                name="tanggal_pencairan"
                                x-model="tanggalPencairan"
                                class="bg-white border border-emerald-300 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                            />
                        </div>

                        {{-- Checkbox Konfirmasi --}}
                        <label class="mt-2 pt-2.5 border-t border-slate-200/80 flex items-start gap-2.5 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="konfirmasi_pencairan"
                                value="1"
                                x-model="konfirmasiPencairan"
                                class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-600 w-4 h-4 cursor-pointer"
                            />
                            <span class="text-xs text-slate-700 leading-snug">
                                Saya mengonfirmasi bahwa berkas yang diimpor ini benar-benar berstatus
                                <strong
                                    :class="statusPencairan === 'sudah_cair' ? 'text-emerald-700' : 'text-amber-700'"
                                    x-text="statusPencairan === 'sudah_cair' ? 'SUDAH DICAIRKAN KE UANG GAJIAN (LUNAS)' : 'BELUM DICAIRKAN KE UANG GAJIAN (DRAF)'"
                                ></strong>
                                dan siap disinkronkan ke basis data.
                            </span>
                        </label>
                    </div>

                    {{-- 3. Drag & Drop Zone --}}
                    <div class="space-y-2">
                        <div class="relative group cursor-pointer rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/40 hover:bg-blue-50/80 transition-all p-6 flex flex-col items-center justify-center text-center">
                            <input
                                x-ref="inputBerkasExcel"
                                type="file"
                                name="berkas_excel"
                                accept=".xlsx,.csv,.txt"
                                @change="pilihBerkas($event)"
                                class="absolute inset-0 opacity-0 cursor-pointer z-10"
                            />
                            <div class="relative mb-2.5">
                                <div class="w-14 h-14 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-600 group-hover:scale-105 transition-transform shadow-2xs">
                                    <span class="material-symbols-outlined text-[32px]">cloud_upload</span>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-2xs">
                                    <span class="material-symbols-outlined text-[15px]">description</span>
                                </div>
                            </div>
                            <span class="text-sm text-slate-900 font-bold">
                                Tarik &amp; lepas berkas Excel / CSV di sini, atau <span class="text-blue-600 underline">Telusuri Berkas</span>
                            </span>
                            <p class="text-xs text-slate-500 max-w-2xl mt-1 leading-relaxed">
                                Mendukung berkas <strong class="text-slate-700">.xlsx</strong>, <strong class="text-slate-700">.csv</strong>, dan <strong class="text-slate-700">.txt</strong> format <strong>Rincian Gaji Supir</strong> (1 supir maupun banyak blok/sheet supir sekaligus). Otomatis memetakan Nama Supir, Plat Truk, Pengantaran Harian, Uang Jalan, Sparepart, Komisi Supir, BPJS, dan Kasbon.
                            </p>

                            {{-- Selected File Pill --}}
                            <div
                                x-show="namaBerkas !== ''"
                                x-cloak
                                class="mt-4 relative z-20 inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-white border border-emerald-200 shadow-2xs text-slate-800"
                            >
                                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[14px] font-bold">check</span>
                                </span>
                                <span class="text-xs font-bold text-slate-900" x-text="namaBerkas"></span>
                                <span class="text-xs text-slate-400" x-text="'(' + ukuranBerkas + ')'"></span>
                                <span class="text-[10px] text-emerald-700 font-bold uppercase tracking-wider bg-emerald-50 px-2 py-0.5 rounded">
                                    Siap Diproses
                                </span>
                                <button
                                    type="button"
                                    @click.prevent="hapusBerkas($refs.inputBerkasExcel)"
                                    class="ml-1 text-slate-400 hover:text-rose-600 transition-colors"
                                    title="Ganti Berkas"
                                >
                                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Guidance & Quick Template Links --}}
                    <div class="rounded-xl bg-amber-50/80 border border-amber-200 p-3.5 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-[19px]">warning</span>
                        </div>
                        <div class="text-xs text-slate-700 leading-relaxed">
                            <span class="font-bold text-amber-900 block">Standar Struktur Rincian Gaji Supir:</span>
                            Baris atas memuat <strong>NAMA SUPIR</strong>, <strong>MERK</strong>, <strong>PERIODE</strong>, dan <strong>PLAT</strong>; bagian tengah memuat daftar <strong>Tanggal, No. Pol, Material, Rit, Ton, Tarif/Ton, Angkutan, Uang Jalan, Keterangan</strong>; dan bagian bawah memuat blok <strong>REKAPITULASI</strong>.
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <a
                                href="{{ route('admin.reports.migrasi.dummy') }}"
                                class="text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1 font-semibold"
                            >
                                <span class="material-symbols-outlined text-[17px]">download_for_offline</span>
                                <span>Unduh Contoh Format Migrasi (.xlsx)</span>
                            </a>
                            <span class="text-slate-300">|</span>
                            <a
                                href="{{ route('admin.reports.migrasi.template') }}"
                                class="text-slate-600 hover:text-slate-900 hover:underline inline-flex items-center gap-1 font-medium"
                            >
                                <span class="material-symbols-outlined text-[17px]">description</span>
                                <span>Template Kosong (.xlsx)</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                            <button
                                type="button"
                                @click="tutupModal()"
                                class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 text-xs font-semibold transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="sedangMemproses"
                                class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white shadow-sm text-xs font-bold inline-flex items-center justify-center gap-2 transition-all"
                            >
                                <span class="material-symbols-outlined text-[18px]" :class="sedangMemproses ? 'animate-spin' : ''" x-text="sedangMemproses ? 'sync' : 'open_in_new'"></span>
                                <span x-text="sedangMemproses ? 'Menyiapkan Pratinjau...' : 'Buka Pratinjau &amp; Validasi Data (Tab Baru)'"></span>
                            </button>
                        </div>
                    </div>
                </form>

                {{-- TAB 2: EXPORT --}}
                <form
                    x-show="tabAktif === 'export'"
                    x-cloak
                    method="GET"
                    action="{{ route('admin.reports.migrasi.export') }}"
                    class="space-y-5"
                >
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-800 block" for="selectPeriodeEkspor">Pilih Cakupan Periode Ekspor</label>
                        <select
                            id="selectPeriodeEkspor"
                            name="periode"
                            x-model="periodeEkspor"
                            class="w-full h-10 px-3.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
                        >
                            <option value="all">Seluruh Periode Arsip (Multi-Periode)</option>
                            @foreach($opsiPeriode as $opsi)
                                <option value="{{ $opsi['kode'] }}">Periode {{ $opsi['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Format Ekspor Berkas Excel / CSV</span>
                            <span class="text-[11px] text-slate-500">Kompatibel MS Excel &amp; Impor Ulang</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label
                                class="relative flex p-4 rounded-xl border cursor-pointer transition-all"
                                :class="formatEkspor === 'multi_sheet' ? 'bg-white border-blue-500 ring-2 ring-blue-500/20 shadow-2xs' : 'bg-white border-slate-200 hover:bg-slate-50'"
                            >
                                <input
                                    type="radio"
                                    name="format"
                                    value="multi_sheet"
                                    x-model="formatEkspor"
                                    class="mt-1 text-blue-600 focus:ring-blue-600 w-4 h-4 cursor-pointer"
                                />
                                <div class="ml-3 flex flex-col">
                                    <span class="text-sm font-bold text-slate-900">Format Rincian Gaji Supir Lengkap</span>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        Mengekspor seluruh supir sesuai format asli lembar kerja fisik (Header Supir + Tabel Pengantaran Harian + Blok Rekapitulasi Gaji). Dapat diimpor kembali.
                                    </p>
                                    <div class="mt-2.5 flex items-center gap-1.5 text-emerald-600 text-[11px] font-semibold">
                                        <span class="material-symbols-outlined text-[15px]">check_circle</span>
                                        <span>Identik dengan struktur file migrasi Excel</span>
                                    </div>
                                </div>
                            </label>

                            <label
                                class="relative flex p-4 rounded-xl border cursor-pointer transition-all"
                                :class="formatEkspor === 'single_summary' ? 'bg-white border-blue-500 ring-2 ring-blue-500/20 shadow-2xs' : 'bg-white border-slate-200 hover:bg-slate-50'"
                            >
                                <input
                                    type="radio"
                                    name="format"
                                    value="single_summary"
                                    x-model="formatEkspor"
                                    class="mt-1 text-blue-600 focus:ring-blue-600 w-4 h-4 cursor-pointer"
                                />
                                <div class="ml-3 flex flex-col">
                                    <span class="text-sm font-bold text-slate-900">Rekap Ringkas 1 Tabel (Summary)</span>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        Mengekspor 1 tabel rekapitulasi ringkas untuk keperluan audit keuangan pimpinan dan daftar transfer perbankan.
                                    </p>
                                    <div class="mt-2.5 flex items-center gap-1.5 text-slate-500 text-[11px] font-medium">
                                        <span class="material-symbols-outlined text-[15px]">table_rows</span>
                                        <span>Format datar ringkas siap olah</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="tutupModal()"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 text-xs font-semibold transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm text-xs font-bold inline-flex items-center gap-2 transition-all"
                        >
                            <span class="material-symbols-outlined text-[18px]">download</span>
                            <span>Download Berkas Excel (.csv)</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- STATUS BAR FOOTER --}}
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 text-slate-500 text-[11px]">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Sinkronisasi Database: <strong class="text-slate-700">Operasional Pengantaran &amp; Slip Gaji</strong></span>
                </div>
                <div class="flex items-center gap-3">
                    <span>Mendukung Multi-Supir &amp; Multi-Periode</span>
                </div>
            </div>
        </div>
    </div>
</div>
