<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN KALKULATOR & SIMULASI GAJI SUPIR):
     - Tujuan Halaman: Kalkulator interaktif bagi supir truk batubara untuk menghitung perkiraan
       gaji bersih (take-home pay) berdasarkan target ritase yang ingin ditempuh (1 s.d. 31 rit).
     - Analogi Dunia Nyata: Seperti supir yang sedang menghitung di warung kopi rest area:
       "Kalau bulan ini saya bisa tembus 15 rit rute Tambang-Dermaga, muatan rata-rata 29 ton,
       setelah dipotong cicilan kasbon dan bon servis ban, kira-kira berapa uang bersih yang saya bawa pulang?".
     - Rumus Penggajian 4 Tahap yang Disimulasikan:
       1. Total Angkutan Kotor = sum(Tonase x Tarif per Ton).
       2. Hasil Bersih Armada = max(0, Total Angkutan Kotor - Total Uang Jalan - Biaya Sparepart).
       3. Komisi Supir = Hasil Bersih Armada x 10%.
       4. Gaji Bersih = max(0, Komisi + Total Tunjangan - Potongan BPJS - Potongan Kasbon).
     - Data dari Controller (Driver/SalarySimulationController@index):
       * $daftarRute: Master rute aktif beserta tarif per ton dan uang jalan default.
       * $ritaseRiil: Data surat jalan supir yang sudah terverifikasi bulan berjalan.
       * $dataRiil: Angka riil potongan kasbon, BPJS, dan biaya sparepart supir.
     ============================================================================== -->
@php
    $profilSupir = $profile ?? [];
@endphp

<x-driver-layout title="Simulasi & Kalkulator Gaji">
    <div
        class="flex flex-col gap-6"
        x-data="simulasiGajiSupir({
            daftarRute: @js($daftarRute),
            ritaseRiil: @js($ritaseRiil),
            dataRiil: @js($dataRiil),
        })"
        x-init="init()"
    >
        {{-- Hero Header Halaman --}}
        <div class="flex flex-col justify-between gap-4 rounded-2xl border border-card-border bg-card-bg p-5 shadow-card lg:flex-row lg:items-center">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 font-sans text-label-sm text-slate-text-secondary">
                    <span>Portal Supir</span>
                    <span class="material-symbols-outlined text-[0.8125rem]">chevron_right</span>
                    <span class="font-semibold text-slate-text-primary">Kalkulator &amp; Simulasi Gaji</span>
                </div>
                <h1 class="font-display text-headline-lg font-bold text-slate-text-primary">Simulasi Perhitungan Gaji Bersih</h1>
                <p class="font-sans text-body-sm text-slate-text-secondary">
                    Perkirakan penghasilan take-home pay Anda bulan ini berdasarkan rencana atau target surat jalan (1 s.d. 31 rit).
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    @click="salinDariRiil()"
                    :disabled="ritaseRiil.length === 0"
                    class="inline-flex items-center gap-2 rounded-xl bg-banner-indigo px-4 py-2 font-sans text-body-sm font-bold text-white shadow-md transition-all hover:bg-banner-deep hover:shadow-lg active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                    title="Salin data ritase yang sudah terverifikasi bulan ini ke dalam tabel simulasi"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">content_paste</span>
                    <span>Salin Data Riil Bulan Ini (<span x-text="ritaseRiil.length"></span> Rit)</span>
                </button>

                <button
                    type="button"
                    @click="resetBaris()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-danger-red/40 bg-danger-red/10 px-3.5 py-2 font-sans text-body-sm font-bold text-danger-red shadow-xs transition-all hover:bg-danger-red hover:text-white active:scale-95"
                    title="Kosongkan seluruh baris surat jalan dan mulai dari awal"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">restart_alt</span>
                    <span>Reset</span>
                </button>
            </div>
        </div>

        {{-- Section 1: Tabel Tunggal Surat Jalan Simulasi (1 s.d. 31 Baris) --}}
        <div class="flex flex-col gap-3 rounded-2xl border border-card-border bg-card-bg p-5 shadow-card">
            <div class="flex flex-col justify-between gap-3 border-b border-card-border pb-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <span class="material-symbols-outlined text-[1.25rem]">conversion_path</span>
                    </div>
                    <div>
                        <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Daftar Surat Jalan Simulasi</h2>
                        <span class="font-sans text-label-sm text-slate-text-secondary">
                            Terisi <strong class="text-primary" x-text="suratJalan.length"></strong> dari maksimal <strong>31</strong> surat jalan per bulan.
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="tambahBaris()"
                        :disabled="suratJalan.length >= 31"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 font-sans text-body-sm font-bold text-on-primary shadow-sm transition-all hover:bg-banner-indigo active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">add</span>
                        <span>Tambah Baris Surat Jalan</span>
                    </button>
                </div>
            </div>

            {{-- Tabel Responsif --}}
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[760px] border-collapse text-left font-sans text-body-sm">
                    <thead>
                        <tr class="border-b border-card-border bg-surface-container-low text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">
                            <th class="w-12 px-3 py-3 text-center">No</th>
                            <th class="min-w-[220px] px-3 py-3">Rute Pengantaran</th>
                            <th class="w-32 px-3 py-3 text-right">Muatan (Ton)</th>
                            <th class="w-36 px-3 py-3 text-right">Tarif / Ton (Rp)</th>
                            <th class="w-36 px-3 py-3 text-right">Uang Jalan (Rp)</th>
                            <th class="w-40 px-3 py-3 text-right">Angkutan Kotor (Rp)</th>
                            <th class="w-24 px-3 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-card-border/60">
                        <template x-for="(baris, index) in suratJalan" :key="baris.id_kunci">
                            <tr class="transition-colors hover:bg-surface-container-low/40">
                                {{-- Nomor Urut --}}
                                <td class="px-3 py-2.5 text-center font-bold text-slate-text-secondary" x-text="index + 1"></td>

                                {{-- Pilihan Rute (Tidak Bisa Diubah Manual, Hanya Pilih Master Rute) --}}
                                <td class="px-3 py-2.5">
                                    <div class="flex flex-col gap-1">
                                        <select
                                            x-model="baris.rute_id"
                                            @change="pilihRute(index, $event.target.value)"
                                            class="h-9 w-full rounded-lg border border-card-border bg-canvas-bg px-2.5 font-sans text-body-sm font-semibold text-slate-text-primary focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        >
                                            <option value="">-- Pilih Rute Pengantaran --</option>
                                            @foreach ($daftarRute as $rute)
                                                <option value="{{ $rute['id'] }}">{{ $rute['nama'] }} ({{ $rute['kota_asal'] }} → {{ $rute['kota_tujuan'] }})</option>
                                            @endforeach
                                        </select>
                                        <div class="flex items-center gap-1.5 text-xs text-slate-text-muted">
                                            <span class="material-symbols-outlined text-[0.875rem] text-banner-indigo">alt_route</span>
                                            <span class="truncate" x-text="baris.nama_rute ? baris.nama_rute : 'Pilih rute terdaftar'"></span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Tonase Muatan (Ton) --}}
                                <td class="px-3 py-2.5 text-right">
                                    <div class="flex flex-col items-end">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max="100"
                                            x-model.number="baris.tonase_ton"
                                            class="h-9 w-28 rounded-lg border border-card-border bg-canvas-bg px-2 text-right font-financial-table text-body-md font-bold text-slate-text-primary focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        >
                                        <span class="mt-0.5 text-[0.6875rem] font-medium text-slate-text-muted">Standar: 29,00 Ton</span>
                                    </div>
                                </td>

                                {{-- Tarif per Ton (Rp) --}}
                                <td class="px-3 py-2.5 text-right">
                                    <div class="flex flex-col items-end">
                                        <input
                                            type="number"
                                            step="1000"
                                            min="0"
                                            x-model.number="baris.tarif_per_ton"
                                            class="h-9 w-32 rounded-lg border border-card-border bg-canvas-bg px-2 text-right font-financial-table text-body-md font-bold text-slate-text-primary focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        >
                                        <span class="mt-0.5 text-[0.6875rem] font-semibold text-banner-indigo" x-text="'Asli: ' + formatRupiah(ambilRute(baris.rute_id)?.tarif_per_ton || baris.tarif_per_ton)"></span>
                                    </div>
                                </td>

                                {{-- Uang Jalan (Rp) --}}
                                <td class="px-3 py-2.5 text-right">
                                    <div class="flex flex-col items-end">
                                        <input
                                            type="number"
                                            step="50000"
                                            min="0"
                                            x-model.number="baris.uang_jalan"
                                            class="h-9 w-32 rounded-lg border border-card-border bg-canvas-bg px-2 text-right font-financial-table text-body-md font-bold text-slate-text-primary focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        >
                                        <span class="mt-0.5 text-[0.6875rem] font-semibold text-net-pay-emerald" x-text="'Asli: ' + formatRupiah(ambilRute(baris.rute_id)?.uang_jalan || baris.uang_jalan)"></span>
                                    </div>
                                </td>

                                {{-- Total Angkutan Kotor (Readonly) --}}
                                <td class="px-3 py-2.5 text-right font-financial-table text-body-md font-bold text-slate-text-primary">
                                    <span x-text="formatRupiah(Math.round((baris.tonase_ton || 0) * (baris.tarif_per_ton || 0)))"></span>
                                </td>

                                {{-- Tombol Aksi (Mencolok: Duplikat Biru/Sky & Hapus Merah) --}}
                                <td class="px-3 py-2.5 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="duplikatBaris(index)"
                                            :disabled="suratJalan.length >= 31"
                                            title="Duplikat baris ini"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-banner-sky bg-banner-sky/20 text-banner-indigo shadow-xs transition-all hover:bg-banner-sky hover:text-white active:scale-95 disabled:cursor-not-allowed disabled:opacity-30"
                                        >
                                            <span class="material-symbols-outlined text-[1.125rem]">content_copy</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="hapusBaris(index)"
                                            :disabled="suratJalan.length <= 1"
                                            title="Hapus baris ini"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-danger-red/40 bg-danger-red/15 text-danger-red shadow-xs transition-all hover:bg-danger-red hover:text-white active:scale-95 disabled:cursor-not-allowed disabled:opacity-30"
                                        >
                                            <span class="material-symbols-outlined text-[1.125rem]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    {{-- Baris Rekapitulasi Total Tabel --}}
                    <tfoot>
                        <tr class="border-t-2 border-card-border bg-surface-container-low font-bold text-slate-text-primary">
                            <td colspan="2" class="px-3 py-3">
                                <span>TOTAL SURAT JALAN: <span class="text-primary" x-text="totalRit"></span> RIT</span>
                            </td>
                            <td class="px-3 py-3 text-right font-financial-table text-body-md text-primary">
                                <span x-text="formatAngka(totalTonaseTon, 2)"></span> Ton
                            </td>
                            <td class="px-3 py-3 text-right text-xs text-slate-text-muted">Rata-rata</td>
                            <td class="px-3 py-3 text-right font-financial-table text-body-md text-danger-red">
                                <span x-text="formatRupiah(totalUangJalan)"></span>
                            </td>
                            <td class="px-3 py-3 text-right font-financial-table text-headline-sm text-net-pay-emerald">
                                <span x-text="formatRupiah(totalAngkutanKotor)"></span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Section 2: Panel Pengaturan Potongan & Rekonsiliasi Hasil (2 Kolom Grid) --}}
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- Kolom Kiri: Pilihan Mode Potongan & Input Nominal (lg:col-span-7) --}}
            <div class="flex flex-col gap-5 rounded-2xl border border-card-border bg-card-bg p-5 shadow-card lg:col-span-7">
                <div class="flex flex-col gap-2 border-b border-card-border pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Pengaturan Potongan &amp; Tunjangan</h3>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Pilih apakah menggunakan data riil sistem atau memasukkan angka perkiraan sendiri.</p>
                    </div>

                    {{-- Switcher Mode Potongan --}}
                    <div class="inline-flex rounded-xl border border-card-border bg-surface-container-low p-1 shadow-sm">
                        <button
                            type="button"
                            @click="gantiMode('riil')"
                            :class="modePotongan === 'riil' ? 'bg-primary text-on-primary font-bold shadow' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                            class="rounded-lg px-3.5 py-1.5 font-sans text-xs transition-all"
                        >
                            Data Riil Bulan Ini
                        </button>
                        <button
                            type="button"
                            @click="gantiMode('manual')"
                            :class="modePotongan === 'manual' ? 'bg-primary text-on-primary font-bold shadow' : 'text-slate-text-secondary hover:text-slate-text-primary'"
                            class="rounded-lg px-3.5 py-1.5 font-sans text-xs transition-all"
                        >
                            Input Manual Sendiri
                        </button>
                    </div>
                </div>

                {{-- Komponen Potongan & Tunjangan --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- 1. Biaya Sparepart Armada --}}
                    <div class="flex flex-col gap-1.5 rounded-xl border border-card-border/70 bg-surface-container-low/50 p-3.5">
                        <div class="flex items-center justify-between">
                            <label class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-primary">Biaya Sparepart Armada</label>
                            <span class="rounded bg-surface-container px-1.5 py-0.5 text-[0.625rem] font-bold uppercase text-slate-text-muted" x-text="modePotongan === 'riil' ? 'Otomatis' : 'Manual'"></span>
                        </div>
                        <p class="font-sans text-xs text-slate-text-muted">
                            Pengurang omzet kotor armada unit <span class="font-semibold text-slate-text-primary" x-text="dataRiil.nopol_truk"></span> sebelum komisi supir.
                        </p>
                        <div class="relative mt-1 flex items-center">
                            <span class="pointer-events-none absolute left-3 font-financial-table text-xs font-bold text-slate-text-muted">Rp</span>
                            <input
                                type="number"
                                min="0"
                                step="10000"
                                :readonly="modePotongan === 'riil'"
                                x-model.number="biayaSparepartInput"
                                :class="modePotongan === 'riil' ? 'bg-surface-container cursor-not-allowed text-slate-text-secondary' : 'bg-canvas-bg text-slate-text-primary focus:border-primary focus:ring-1 focus:ring-primary'"
                                class="h-9 w-full rounded-lg border border-card-border pl-9 pr-3 text-right font-financial-table text-body-md font-bold"
                            >
                        </div>
                    </div>

                    {{-- 2. Potongan Kasbon --}}
                    <div class="flex flex-col gap-1.5 rounded-xl border border-card-border/70 bg-surface-container-low/50 p-3.5">
                        <div class="flex items-center justify-between">
                            <label class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-primary">Potongan Kasbon Supir</label>
                            <span class="rounded bg-surface-container px-1.5 py-0.5 text-[0.625rem] font-bold uppercase text-slate-text-muted" x-text="modePotongan === 'riil' ? 'Otomatis' : 'Manual'"></span>
                        </div>
                        <p class="font-sans text-xs text-slate-text-muted">Jadwal angsuran kasbon berjalan yang memotong komisi supir.</p>
                        <div class="relative mt-1 flex items-center">
                            <span class="pointer-events-none absolute left-3 font-financial-table text-xs font-bold text-slate-text-muted">Rp</span>
                            <input
                                type="number"
                                min="0"
                                step="50000"
                                :readonly="modePotongan === 'riil'"
                                x-model.number="potonganKasbonInput"
                                :class="modePotongan === 'riil' ? 'bg-surface-container cursor-not-allowed text-slate-text-secondary' : 'bg-canvas-bg text-slate-text-primary focus:border-primary focus:ring-1 focus:ring-primary'"
                                class="h-9 w-full rounded-lg border border-card-border pl-9 pr-3 text-right font-financial-table text-body-md font-bold"
                            >
                        </div>
                    </div>

                    {{-- 3. Potongan BPJS --}}
                    <div class="flex flex-col gap-1.5 rounded-xl border border-card-border/70 bg-surface-container-low/50 p-3.5">
                        <div class="flex items-center justify-between">
                            <label class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-primary">Potongan BPJS Ketenagakerjaan</label>
                            <span class="rounded bg-surface-container px-1.5 py-0.5 text-[0.625rem] font-bold uppercase text-slate-text-muted" x-text="modePotongan === 'riil' ? 'Otomatis' : 'Manual'"></span>
                        </div>
                        <p class="font-sans text-xs text-slate-text-muted">Iuran jaminan sosial terdaftar supir per bulan.</p>
                        <div class="relative mt-1 flex items-center">
                            <span class="pointer-events-none absolute left-3 font-financial-table text-xs font-bold text-slate-text-muted">Rp</span>
                            <input
                                type="number"
                                min="0"
                                step="1000"
                                :readonly="modePotongan === 'riil'"
                                x-model.number="potonganBpjsInput"
                                :class="modePotongan === 'riil' ? 'bg-surface-container cursor-not-allowed text-slate-text-secondary' : 'bg-canvas-bg text-slate-text-primary focus:border-primary focus:ring-1 focus:ring-primary'"
                                class="h-9 w-full rounded-lg border border-card-border pl-9 pr-3 text-right font-financial-table text-body-md font-bold"
                            >
                        </div>
                    </div>

                    {{-- 4. Tunjangan & Bonus Tambahan --}}
                    <div class="flex flex-col gap-1.5 rounded-xl border border-card-border/70 bg-surface-container-low/50 p-3.5">
                        <div class="flex items-center justify-between">
                            <label class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-primary">Tunjangan &amp; Bonus</label>
                            <span class="rounded bg-net-pay-surface px-1.5 py-0.5 text-[0.625rem] font-bold uppercase text-net-pay-emerald">Penambah (+)</span>
                        </div>
                        <p class="font-sans text-xs text-slate-text-muted">Tunjangan tetap atau bonus performa ritase supir.</p>
                        <div class="relative mt-1 flex items-center">
                            <span class="pointer-events-none absolute left-3 font-financial-table text-xs font-bold text-slate-text-muted">Rp</span>
                            <input
                                type="number"
                                min="0"
                                step="50000"
                                x-model.number="totalTunjanganInput"
                                class="h-9 w-full rounded-lg border border-card-border bg-canvas-bg pl-9 pr-3 text-right font-financial-table text-body-md font-bold text-slate-text-primary focus:border-primary focus:ring-1 focus:ring-primary"
                            >
                        </div>
                    </div>
                </div>

                {{-- Form Simpan Skenario Simulasi --}}
                <div class="mt-2 flex flex-col gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[1.25rem] text-primary">bookmark_add</span>
                        <h4 class="font-sans text-body-md font-bold text-slate-text-primary">Simpan Hasil Simulasi Ini ke Arsip Akun</h4>
                    </div>
                    <form method="POST" action="{{ route('driver.simulation.store') }}" @submit="siapkanDataSebelumSimpan($el)" class="flex flex-col gap-3">
                        @csrf
                        {{-- Hidden Payload Data Surat Jalan & Komponen --}}
                        <input type="hidden" name="id_periode_gaji" value="{{ $period['id'] ?? '' }}">
                        <input type="hidden" name="mode_potongan" :value="modePotongan">
                        <input type="hidden" name="biaya_sparepart" :value="biayaSparepart">
                        <input type="hidden" name="potongan_bpjs" :value="potonganBpjs">
                        <input type="hidden" name="potongan_kasbon" :value="potonganKasbon">
                        <input type="hidden" name="total_tunjangan" :value="totalTunjanganInput || 0">
                        <input type="hidden" name="potongan_lain" value="0">

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                            <div class="sm:col-span-8">
                                <label for="nama_simulasi" class="sr-only">Nama Simulasi</label>
                                <input
                                    id="nama_simulasi"
                                    name="nama_simulasi"
                                    type="text"
                                    required
                                    maxlength="150"
                                    x-model="namaSimulasi"
                                    placeholder="Contoh: TARGET 25 RIT BULAN JANUARI..."
                                    class="h-10 w-full rounded-lg border border-card-border bg-card-bg px-3.5 font-sans text-body-sm font-semibold text-slate-text-primary focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                >
                            </div>
                            <div class="sm:col-span-4">
                                <button
                                    type="submit"
                                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 font-sans text-body-sm font-bold text-on-primary shadow transition-all hover:bg-banner-indigo active:scale-95"
                                >
                                    <span class="material-symbols-outlined text-[1.125rem]">save</span>
                                    <span>Simpan Skenario</span>
                                </button>
                            </div>
                        </div>

                        {{-- Hidden inputs baris surat jalan dinamis di-inject oleh siapkanDataSebelumSimpan --}}
                        <div x-ref="wadahInputSuratJalan"></div>
                    </form>
                </div>
            </div>

            {{-- Kolom Kanan: Card Ringkasan Estimasi Gaji Bersih (lg:col-span-5) --}}
            <div class="flex flex-col gap-4 rounded-2xl border border-card-border bg-card-bg p-6 shadow-card lg:col-span-5">
                <div class="flex items-center justify-between border-b border-card-border pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-net-pay-surface text-net-pay-emerald">
                            <span class="material-symbols-outlined text-[1.35rem]">payments</span>
                        </div>
                        <div>
                            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Estimasi Gaji Bersih</h3>
                            <span class="font-sans text-label-sm text-slate-text-secondary">Take-Home Pay Bersih Supir</span>
                        </div>
                    </div>
                    <span class="rounded-full bg-net-pay-surface px-2.5 py-1 font-sans text-label-sm font-bold text-net-pay-emerald">
                        Simulasi Aktif
                    </span>
                </div>

                {{-- Breakdown Rincian Gaji Bersih 4 Tahap --}}
                <div class="flex flex-col gap-2.5 font-sans text-body-sm">
                    <div class="flex items-center justify-between text-slate-text-secondary">
                        <span>Total Ritase Simulasi</span>
                        <span class="font-financial-table font-bold text-slate-text-primary" x-text="totalRit + ' Rit (' + formatAngka(totalTonaseTon, 2) + ' Ton)'"></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-text-secondary">
                        <span>Total Angkutan Kotor</span>
                        <span class="font-financial-table font-bold text-slate-text-primary" x-text="formatRupiah(totalAngkutanKotor)"></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-text-secondary">
                        <span>Total Uang Jalan</span>
                        <span class="font-financial-table font-semibold text-danger-red" x-text="'- ' + formatRupiah(totalUangJalan)"></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-text-secondary">
                        <span>Beban Sparepart Armada</span>
                        <span class="font-financial-table font-semibold text-danger-red" x-text="'- ' + formatRupiah(biayaSparepart)"></span>
                    </div>

                    <div class="border-t border-dashed border-card-border pt-2 flex items-center justify-between font-semibold text-slate-text-secondary">
                        <span>Hasil Bersih Armada</span>
                        <span class="font-financial-table font-bold text-slate-text-primary" x-text="formatRupiah(hasilBersihArmada)"></span>
                    </div>

                    <div class="rounded-xl border border-card-border bg-surface-container-low p-3 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="font-sans text-label-sm font-bold text-slate-text-primary">Bagi Hasil Komisi Supir</span>
                            <span class="font-sans text-xs text-slate-text-muted">Hak komisi operasional supir</span>
                        </div>
                        <span class="font-financial-table text-headline-sm font-bold text-primary" x-text="formatRupiah(komisiSupir)"></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-text-secondary">
                        <span>Tunjangan &amp; Bonus (+)</span>
                        <span class="font-financial-table font-semibold text-net-pay-emerald" x-text="'+ ' + formatRupiah(totalTunjanganFinal)"></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-text-secondary">
                        <span>Potongan BPJS (-)</span>
                        <span class="font-financial-table font-semibold text-danger-red" x-text="'- ' + formatRupiah(potonganBpjs)"></span>
                    </div>

                    <div class="flex items-center justify-between text-slate-text-secondary">
                        <span>Potongan Kasbon (-)</span>
                        <span class="font-financial-table font-semibold text-danger-red" x-text="'- ' + formatRupiah(potonganKasbon)"></span>
                    </div>
                </div>

                {{-- Banner Gaji Bersih Utama --}}
                <div class="mt-2 rounded-2xl bg-gradient-to-br from-sidebar-dark via-sidebar-surface to-sidebar-dark p-5 text-white shadow-hero">
                    <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-banner-sky">Gaji Bersih Siap Diterima</span>
                    <div class="mt-1 font-financial-table text-[1.85rem] font-extrabold tracking-tight text-net-pay-emerald sm:text-[2.25rem]" x-text="formatRupiah(gajiBersih)"></div>
                    <p class="mt-1 font-sans text-xs text-slate-text-muted">
                        Estimasi nominal murni setelah seluruh uang jalan, operasional armada, dan potongan kewajiban diselesaikan.
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 3: Riwayat Simulasi yang Pernah Disimpan --}}
        @if ($daftarSimulasi->isNotEmpty())
            <div class="flex flex-col gap-4 rounded-2xl border border-card-border bg-card-bg p-5 shadow-card">
                <div class="flex items-center justify-between border-b border-card-border pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-container text-slate-text-secondary">
                            <span class="material-symbols-outlined text-[1.25rem]">history</span>
                        </div>
                        <div>
                            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Riwayat Simulasi Tersimpan</h3>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Pilih simulasi tersimpan di bawah ini untuk memuat kembali rinciannya ke kalkulator.</p>
                        </div>
                    </div>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[700px] border-collapse text-left font-sans text-body-sm">
                        <thead>
                            <tr class="border-b border-card-border bg-surface-container-low text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">
                                <th class="w-12 px-3 py-2.5 text-center">No</th>
                                <th class="px-3 py-2.5">Nama Simulasi</th>
                                <th class="px-3 py-2.5 text-center">Ritase</th>
                                <th class="px-3 py-2.5 text-right">Total Tonase</th>
                                <th class="px-3 py-2.5 text-right">Estimasi Gaji Bersih</th>
                                <th class="px-3 py-2.5 text-center">Waktu Simpan</th>
                                <th class="w-36 px-3 py-2.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border/60">
                            @foreach ($daftarSimulasi as $idx => $sim)
                                <tr class="transition-colors hover:bg-surface-container-low/40">
                                    <td class="px-3 py-3 text-center font-bold text-slate-text-secondary">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-3 font-semibold text-slate-text-primary">{{ $sim->nama_simulasi }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-primary">{{ $sim->total_rit }} Rit</td>
                                    <td class="px-3 py-3 text-right font-financial-table font-semibold text-slate-text-primary">
                                        {{ number_format($sim->total_tonase_kg / 1000, 2, ',', '.') }} Ton
                                    </td>
                                    <td class="px-3 py-3 text-right font-financial-table font-bold text-net-pay-emerald">
                                        Rp {{ number_format($sim->gaji_bersih, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-center text-xs text-slate-text-muted">
                                        {{ $sim->created_at?->format('d M Y, H:i') }}
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="muatSimulasiDariDatabase(@js($sim->data_surat_jalan), '{{ $sim->mode_potongan }}', {{ $sim->biaya_sparepart }}, {{ $sim->potongan_bpjs }}, {{ $sim->potongan_kasbon }}, {{ $sim->total_tunjangan }}, '{{ addslashes($sim->nama_simulasi) }}')"
                                                class="inline-flex items-center gap-1 rounded-lg bg-surface-container-low px-2.5 py-1 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white"
                                            >
                                                <span class="material-symbols-outlined text-[1rem]">open_in_browser</span>
                                                <span>Muat</span>
                                            </button>

                                            <form method="POST" action="{{ route('driver.simulation.destroy', $sim) }}" onsubmit="return confirm('Hapus simulasi ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-text-muted transition-colors hover:bg-danger-red/10 hover:text-danger-red"
                                                    title="Hapus simulasi tersimpan"
                                                >
                                                    <span class="material-symbols-outlined text-[1rem]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- ==============================================================================
         KOMENTAR ORANG TUA (LOGIKA ALUR SCRIPT KALKULATOR GAJI SUPIR):
         Fungsi Alpine.js ini bekerja dengan alur bertahap:
         (1) Inisialisasi: Mengambil data rute dan potongan riil dari database.
         (2) Manajemen Baris: Supir dapat menambah, menyalin dari riil, menduplikat, atau menghapus baris (maks 31).
         (3) Sinkronisasi Rute: Saat rute dipilih, tarif dan uang jalan langsung terisi otomatis.
         (4) Rumus 4 Tahap: Menghitung Angkutan Kotor -> Bersih Armada -> Komisi 10% -> Gaji Bersih.
         (5) Pengiriman Form: Menyiapkan hidden input ke form POST saat supir menekan tombol simpan.
         ============================================================================== -->
    <script>
    window.simulasiGajiSupir = function(konfigurasi) {
        return {
            // (1) Inisialisasi data awal dari Controller
            daftarRute: konfigurasi.daftarRute || [],
            ritaseRiil: konfigurasi.ritaseRiil || [],
            dataRiil: konfigurasi.dataRiil || {},

            suratJalan: [],
            idGenerator: 1,

            modePotongan: 'riil',
            biayaSparepartInput: 0,
            potonganKasbonInput: 0,
            potonganBpjsInput: 0,
            totalTunjanganInput: 0,
            namaSimulasi: 'SIMULASI TARGET RITASE',

            init() {
                // (1.1) Isi nilai awal potongan dari data riil berjalan
                this.biayaSparepartInput = Number(this.dataRiil.sparepart || 0);
                this.potonganKasbonInput = Number(this.dataRiil.kasbon || 0);
                this.potonganBpjsInput = Number(this.dataRiil.bpjs || 0);
                this.totalTunjanganInput = Number(this.dataRiil.tunjangan || 0);

                // (1.2) Jika ada surat jalan riil bulan ini, salin otomatis; bila kosong, buat 1 baris kosong
                if (this.ritaseRiil && this.ritaseRiil.length > 0) {
                    this.salinDariRiil();
                } else {
                    this.tambahBaris();
                }
            },

            // (1.3) Beralih antara mode potongan riil dan mode kustom
            gantiMode(mode) {
                this.modePotongan = mode;
                if (mode === 'riil') {
                    this.biayaSparepartInput = Number(this.dataRiil.sparepart || 0);
                    this.potonganKasbonInput = Number(this.dataRiil.kasbon || 0);
                    this.potonganBpjsInput = Number(this.dataRiil.bpjs || 0);
                }
            },

            // (2.1) Tambah 1 baris surat jalan baru (maksimal 31 baris sesuai hari dalam sebulan)
            tambahBaris() {
                if (this.suratJalan.length >= 31) return;

                const defaultRute = (this.daftarRute && this.daftarRute.length > 0) ? this.daftarRute[0] : null;
                this.suratJalan.push({
                    id_kunci: 'sj_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7),
                    rute_id: defaultRute ? defaultRute.id : '',
                    nama_rute: defaultRute ? defaultRute.nama : 'RUTE STANDAR',
                    tonase_ton: 29.00,
                    tarif_per_ton: defaultRute ? Number(defaultRute.tarif_per_ton) : 152000,
                    uang_jalan: defaultRute ? Number(defaultRute.uang_jalan) : 2100000,
                    tanggal: new Date().toISOString().split('T')[0],
                });
            },

            // (2.2) Hapus 1 baris surat jalan
            hapusBaris(index) {
                if (this.suratJalan.length <= 1) return;
                this.suratJalan.splice(index, 1);
            },

            // (2.3) Duplikat baris yang ada untuk mempercepat input target harian
            duplikatBaris(index) {
                if (this.suratJalan.length >= 31) return;
                const sumber = this.suratJalan[index];
                this.suratJalan.splice(index + 1, 0, {
                    id_kunci: 'sj_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7),
                    rute_id: sumber.rute_id,
                    nama_rute: sumber.nama_rute,
                    tonase_ton: Number(sumber.tonase_ton) || 29.00,
                    tarif_per_ton: Number(sumber.tarif_per_ton) || 152000,
                    uang_jalan: Number(sumber.uang_jalan) || 2100000,
                    tanggal: sumber.tanggal,
                });
            },

            // (2.4) Bersihkan seluruh baris dan kembalikan ke 1 baris kosong
            resetBaris() {
                this.suratJalan = [];
                this.tambahBaris();
            },

            // (2.5) Salin seluruh riwayat surat jalan riil yang sudah terverifikasi bulan ini
            salinDariRiil() {
                if (!this.ritaseRiil || this.ritaseRiil.length === 0) return;
                this.suratJalan = this.ritaseRiil.slice(0, 31).map((r, i) => ({
                    id_kunci: 'sj_' + Date.now() + '_' + i + '_' + Math.random().toString(36).substring(2, 7),
                    rute_id: r.rute_id || '',
                    nama_rute: r.nama_rute || 'RUTE STANDAR',
                    tonase_ton: Number(r.tonase_ton) || 29.00,
                    tarif_per_ton: Number(r.tarif_per_ton) || 152000,
                    uang_jalan: Number(r.uang_jalan) || 2100000,
                    tanggal: r.tanggal || new Date().toISOString().split('T')[0],
                }));
            },

            // (3) Sinkronisasi Rute: Pasang tarif dan uang jalan standar sesuai rute yang dipilih
            pilihRute(index, ruteId) {
                const rute = this.daftarRute.find(r => String(r.id) === String(ruteId));
                if (rute) {
                    this.suratJalan[index].nama_rute = rute.nama;
                    this.suratJalan[index].tarif_per_ton = Number(rute.tarif_per_ton) || 0;
                    this.suratJalan[index].uang_jalan = Number(rute.uang_jalan) || 0;
                }
            },

            ambilRute(ruteId) {
                if (!ruteId) return null;
                return this.daftarRute.find(r => String(r.id) === String(ruteId)) || null;
            },

            // (3.2) Muat arsip simulasi yang pernah disimpan sebelumnya di database
            muatSimulasiDariDatabase(dataSj, mode, sparepart, bpjs, kasbon, tunjangan, judul) {
                if (Array.isArray(dataSj) && dataSj.length > 0) {
                    this.suratJalan = dataSj.map((s, i) => ({
                        id_kunci: 'sj_db_' + i + '_' + Date.now(),
                        rute_id: s.rute_id || '',
                        nama_rute: s.nama_rute,
                        tonase_ton: Number(s.tonase_ton),
                        tarif_per_ton: Number(s.tarif_per_ton),
                        uang_jalan: Number(s.uang_jalan),
                        tanggal: s.tanggal || new Date().toISOString().split('T')[0],
                    }));
                }
                this.gantiMode(mode || 'riil');
                this.biayaSparepartInput = Number(sparepart) || 0;
                this.potonganBpjsInput = Number(bpjs) || 0;
                this.potonganKasbonInput = Number(kasbon) || 0;
                this.totalTunjanganInput = Number(tunjangan) || 0;
                this.namaSimulasi = judul || 'SIMULASI DIMUAT';

                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            // (4) RUMUS 4 TAHAP KALKULASI GAJI (Sesuai AGENTS.md Bab 3.8)
            get totalRit() {
                return this.suratJalan.length;
            },

            get totalTonaseTon() {
                return this.suratJalan.reduce((acc, b) => acc + (Number(b.tonase_ton) || 0), 0);
            },

            // (4.1) Tahap 1: Total Angkutan Kotor (Tonase x Tarif per Ton)
            get totalAngkutanKotor() {
                return this.suratJalan.reduce((acc, b) => {
                    const ton = Number(b.tonase_ton) || 0;
                    const tarif = Number(b.tarif_per_ton) || 0;
                    return acc + Math.round(ton * tarif);
                }, 0);
            },

            get totalUangJalan() {
                return this.suratJalan.reduce((acc, b) => acc + (Number(b.uang_jalan) || 0), 0);
            },

            get biayaSparepart() {
                return this.modePotongan === 'riil'
                    ? Number(this.dataRiil.sparepart || 0)
                    : Number(this.biayaSparepartInput || 0);
            },

            // (4.2) Tahap 2: Hasil Bersih Armada (Angkutan Kotor - Uang Jalan - Biaya Sparepart)
            get hasilBersihArmada() {
                return Math.max(0, this.totalAngkutanKotor - this.totalUangJalan - this.biayaSparepart);
            },

            // (4.3) Tahap 3: Komisi Supir (10% dari Hasil Bersih Armada)
            get komisiSupir() {
                return Math.round(this.hasilBersihArmada * 0.10);
            },

            get potonganBpjs() {
                return this.modePotongan === 'riil'
                    ? Number(this.dataRiil.bpjs || 0)
                    : Number(this.potonganBpjsInput || 0);
            },

            get potonganKasbon() {
                return this.modePotongan === 'riil'
                    ? Number(this.dataRiil.kasbon || 0)
                    : Number(this.potonganKasbonInput || 0);
            },

            get totalTunjanganFinal() {
                return Number(this.totalTunjanganInput || 0);
            },

            // (4.4) Tahap 4: Gaji Bersih Final (Take Home Pay)
            get gajiBersih() {
                return Math.max(0, this.komisiSupir + this.totalTunjanganFinal - this.potonganBpjs - this.potonganKasbon);
            },

            formatRupiah(nilai) {
                const bulat = Math.round(Number(nilai) || 0);
                return 'Rp ' + bulat.toLocaleString('id-ID');
            },

            formatAngka(nilai, desimal = 2) {
                const angka = Number(nilai) || 0;
                return angka.toLocaleString('id-ID', { minimumFractionDigits: desimal, maximumFractionDigits: desimal });
            },

            // (5) Persiapan data sebelum submit form simpan skenario simulasi
            siapkanDataSebelumSimpan(form) {
                const wadah = this.$refs.wadahInputSuratJalan;
                wadah.innerHTML = '';

                this.suratJalan.forEach((b, i) => {
                    if (b.rute_id) {
                        const inputRuteId = document.createElement('input');
                        inputRuteId.type = 'hidden';
                        inputRuteId.name = `surat_jalan[${i}][rute_id]`;
                        inputRuteId.value = b.rute_id;
                        wadah.appendChild(inputRuteId);
                    }

                    const inputNama = document.createElement('input');
                    inputNama.type = 'hidden';
                    inputNama.name = `surat_jalan[${i}][nama_rute]`;
                    inputNama.value = b.nama_rute;
                    wadah.appendChild(inputNama);

                    const inputTonase = document.createElement('input');
                    inputTonase.type = 'hidden';
                    inputTonase.name = `surat_jalan[${i}][tonase_ton]`;
                    inputTonase.value = b.tonase_ton;
                    wadah.appendChild(inputTonase);

                    const inputTarif = document.createElement('input');
                    inputTarif.type = 'hidden';
                    inputTarif.name = `surat_jalan[${i}][tarif_per_ton]`;
                    inputTarif.value = b.tarif_per_ton;
                    wadah.appendChild(inputTarif);

                    const inputUangJalan = document.createElement('input');
                    inputUangJalan.type = 'hidden';
                    inputUangJalan.name = `surat_jalan[${i}][uang_jalan]`;
                    inputUangJalan.value = b.uang_jalan;
                    wadah.appendChild(inputUangJalan);

                    const inputTgl = document.createElement('input');
                    inputTgl.type = 'hidden';
                    inputTgl.name = `surat_jalan[${i}][tanggal]`;
                    inputTgl.value = b.tanggal;
                    wadah.appendChild(inputTgl);
                });
            }
        };
    };
    </script>
</x-driver-layout>
