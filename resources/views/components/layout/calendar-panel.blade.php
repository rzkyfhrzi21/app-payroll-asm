{{-- Panel Kalender Nasional sebelah kanan, dapat dipanggil dari sidebar kiri di seluruh halaman --}}
<div
    x-cloak
    x-show="calendarPanelOpen"
    @keydown.escape.window="calendarPanelOpen = false"
    class="fixed inset-0 z-50"
    aria-modal="true"
    role="dialog"
    aria-label="Panel Kalender"
>
    <div
        x-show="calendarPanelOpen"
        x-transition.opacity
        class="absolute inset-0 bg-sidebar-dark/50 backdrop-blur-sm"
        @click="calendarPanelOpen = false"
    ></div>

    <aside
        x-show="calendarPanelOpen"
        x-data="calendarPanel()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="absolute right-0 top-0 flex h-screen w-full max-w-md flex-col bg-card-bg shadow-drawer"
    >
        {{-- Header panel --}}
        <div class="flex items-center justify-between border-b border-card-border bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep px-5 py-4 text-white">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                    <span class="material-symbols-outlined text-[1.375rem]">calendar_month</span>
                </div>
                <div class="flex flex-col">
                    <h2 class="font-display text-headline-sm font-bold text-white">Kalender Nasional</h2>
                    <span class="font-sans text-label-sm text-white/80">Senin &ndash; Minggu &bull; Hari Libur Indonesia</span>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="rounded-lg bg-white/15 px-2.5 py-1.5 font-sans text-label-sm font-semibold text-white transition-colors hover:bg-white/25"
                    @click="keHariIni()"
                >
                    Hari Ini
                </button>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-white/80 transition-colors hover:bg-white/15 hover:text-white"
                    @click="calendarPanelOpen = false"
                    title="Tutup Kalender"
                >
                    <span class="material-symbols-outlined text-[1.25rem]">close</span>
                </button>
            </div>
        </div>

        {{-- Body scrollable --}}
        <div class="flex flex-1 flex-col gap-4 overflow-y-auto p-5">
            {{-- Navigasi Bulan & Tahun --}}
            <div class="flex items-center justify-between gap-2 rounded-xl border border-card-border bg-surface-container-low p-2">
                <button
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-card-bg text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container"
                    @click="bulanSebelumnya()"
                    title="Bulan Sebelumnya"
                >
                    <span class="material-symbols-outlined text-[1.25rem]">chevron_left</span>
                </button>

                <div class="flex flex-1 items-center gap-2">
                    <select
                        x-model.number="bulanAktif"
                        aria-label="Pilih Bulan"
                        class="h-9 flex-1 rounded-lg border border-card-border bg-card-bg px-2.5 font-display text-body-md font-semibold text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <template x-for="(namaBulan, indeks) in daftarBulan" :key="indeks">
                            <option :value="indeks" x-text="namaBulan"></option>
                        </template>
                    </select>

                    <select
                        x-model.number="tahunAktif"
                        aria-label="Pilih Tahun"
                        class="h-9 w-24 rounded-lg border border-card-border bg-card-bg px-2.5 font-display text-body-md font-semibold text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <template x-for="tahun in daftarTahun" :key="tahun">
                            <option :value="tahun" x-text="tahun"></option>
                        </template>
                    </select>
                </div>

                <button
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-card-bg text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container"
                    @click="bulanBerikutnya()"
                    title="Bulan Berikutnya"
                >
                    <span class="material-symbols-outlined text-[1.25rem]">chevron_right</span>
                </button>
            </div>

            {{-- Grid Kalender Senin - Minggu --}}
            <div class="rounded-xl border border-card-border bg-card-bg p-3 shadow-card">
                <div class="mb-2 grid grid-cols-7 gap-1 text-center">
                    <template x-for="(hari, idx) in daftarHari" :key="hari">
                        <div
                            class="py-1 font-sans text-label-sm font-bold uppercase tracking-wider"
                            :class="idx === 6 ? 'text-danger-red' : 'text-slate-text-secondary'"
                            x-text="hari"
                        ></div>
                    </template>
                </div>

                <div class="grid grid-cols-7 gap-1">
                    <template x-for="sel in daftarSelKalender" :key="sel.iso">
                        <button
                            type="button"
                            @click="pilihTanggal(sel)"
                            class="relative flex h-11 flex-col items-center justify-center rounded-lg font-sans text-body-md transition-all"
                            :class="[
                                sel.iso === tanggalDipilih
                                    ? 'bg-primary text-white font-bold shadow-sm ring-2 ring-primary/30'
                                    : sel.isHariIni
                                        ? 'bg-primary/10 text-primary font-bold ring-1 ring-primary/40'
                                        : sel.luarBulan
                                            ? 'text-slate-text-muted/50 hover:bg-surface-container-low'
                                            : sel.isTanggalMerah
                                                ? 'bg-danger-surface/60 text-danger-red font-semibold hover:bg-danger-surface'
                                                : 'text-slate-text-primary hover:bg-surface-container-low'
                            ]"
                        >
                            <span x-text="sel.hari"></span>
                            <span
                                x-show="sel.liburNasional.length > 0"
                                class="mt-0.5 h-1.5 w-1.5 rounded-full"
                                :class="sel.iso === tanggalDipilih ? 'bg-white' : 'bg-danger-red'"
                            ></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Detail Tanggal Dipilih --}}
            <div
                class="flex flex-col gap-2 rounded-xl border p-4"
                :class="infoTanggalDipilih.isTanggalMerah
                    ? 'border-danger-red/30 bg-danger-surface/50'
                    : 'border-card-border bg-surface-container-low'"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-secondary">Tanggal Dipilih</span>
                        <span class="font-display text-headline-sm font-bold text-slate-text-primary" x-text="infoTanggalDipilih.judul"></span>
                    </div>
                    <span
                        class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-0.5 font-sans text-label-sm font-bold"
                        :class="infoTanggalDipilih.isTanggalMerah
                            ? 'bg-danger-red text-white'
                            : 'bg-net-pay-surface text-net-pay-emerald'"
                        x-text="infoTanggalDipilih.isTanggalMerah ? 'Tanggal Merah' : 'Hari Kerja'"
                    ></span>
                </div>

                <template x-if="infoTanggalDipilih.catatan.length > 0">
                    <div class="flex flex-col gap-1 border-t border-danger-red/15 pt-2">
                        <span class="font-sans text-label-sm font-semibold text-danger-red">Keterangan Hari Libur:</span>
                        <ul class="flex flex-col gap-1">
                            <template x-for="(alasan, i) in infoTanggalDipilih.catatan" :key="i">
                                <li class="flex items-center gap-2 font-sans text-body-sm font-medium text-slate-text-primary">
                                    <span class="material-symbols-outlined text-[1rem] text-danger-red">event_busy</span>
                                    <span x-text="alasan"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                <template x-if="infoTanggalDipilih.catatan.length === 0">
                    <p class="font-sans text-body-sm text-slate-text-secondary">
                        Tidak ada hari libur nasional pada tanggal ini.
                    </p>
                </template>
            </div>

            {{-- Daftar Hari Libur Nasional Bulan Ini --}}
            <div class="flex flex-col gap-2 rounded-xl border border-card-border bg-card-bg p-4 shadow-card">
                <div class="flex items-center justify-between border-b border-card-border pb-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[1.125rem] text-danger-red">celebration</span>
                        <span class="font-display text-label-md font-bold uppercase tracking-wide text-slate-text-primary">
                            Tanggal Merah Bulan Ini
                        </span>
                    </div>
                    <span class="rounded-full bg-surface-container px-2 py-0.5 font-sans text-label-sm font-semibold text-slate-text-secondary"
                        x-text="daftarLiburBulanIni.length + ' Hari'"></span>
                </div>

                <template x-if="daftarLiburBulanIni.length > 0">
                    <ul class="flex flex-col divide-y divide-card-border/70">
                        <template x-for="libur in daftarLiburBulanIni" :key="libur.iso">
                            <li
                                class="flex cursor-pointer items-start gap-3 py-2.5 transition-colors hover:bg-surface-container-low/60"
                                @click="tanggalDipilih = libur.iso"
                            >
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-danger-surface font-display text-body-md font-bold text-danger-red"
                                    x-text="libur.hariAngka"></span>
                                <div class="flex flex-col">
                                    <span class="font-sans text-body-sm font-semibold text-slate-text-primary" x-text="libur.alasan"></span>
                                    <span class="font-sans text-label-sm text-slate-text-muted" x-text="libur.labelTanggal"></span>
                                </div>
                            </li>
                        </template>
                    </ul>
                </template>

                <template x-if="daftarLiburBulanIni.length === 0">
                    <p class="py-2 font-sans text-body-sm text-slate-text-muted">
                        Tidak ada tanggal merah libur nasional pada bulan ini (hanya libur akhir pekan Hari Minggu).
                    </p>
                </template>
            </div>
        </div>
    </aside>
</div>
