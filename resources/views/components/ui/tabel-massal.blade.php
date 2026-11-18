<!-- ======================================================
     KOMPONEN UI: BILAH AKSI MASSAL TABEL VIA CHECKBOX (BULK ACTION BAR)
     
     Analogi Sederhana:
     Komponen ini ibarat "Stempel Massal Banyak Dokumen Sekaligus".
     Saat admin atau pimpinan ingin mengubah status banyak surat jalan atau
     meng-ACC gaji banyak supir sekaligus, mereka tinggal mencentang kotak-kotak
     checkbox di tabel, memilih status tujuan, lalu menekan tombol terapkan.
====================================================== -->
@props([
    'url',
    'opsiStatus' => [],
    'label' => 'data',
    'keterangan' => 'Pilih status baru untuk baris yang dicentang.',
])

{{--
    Pembungkus tabel dengan checkbox massal.
    Letakkan <table> di dalam slot; checkbox baris memakai `x-model="terpilih"`
    dan atribut `data-centang-massal`.
--}}
<div x-data="pemilihMassalTabel({ url: @js($url) })" class="flex flex-col">
    <div class="flex flex-col gap-2 border-b border-card-border bg-surface-container-low/70 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 font-sans text-body-sm text-slate-text-secondary">
            <span class="material-symbols-outlined text-[1.125rem] text-primary">checklist</span>
            <span>
                <strong class="tabular-nums text-slate-text-primary" x-text="terpilih.length"></strong>
                {{ $label }} dipilih
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <select
                    x-model="statusTujuan"
                    class="h-9 min-w-[11.5rem] cursor-pointer appearance-none rounded-lg border border-card-border bg-card-bg pl-3 pr-9 font-sans text-body-sm text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                    aria-label="Status baru untuk baris terpilih"
                >
                    <option value="">{{ $keterangan }}</option>
                    @foreach ($opsiStatus as $nilaiStatus => $teksStatus)
                        <option value="{{ $nilaiStatus }}">{{ $teksStatus }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-slate-text-muted">
                    <span class="material-symbols-outlined text-[1.125rem]">expand_more</span>
                </span>
            </div>

            <button
                type="button"
                @click="terapkanStatus()"
                :disabled="terpilih.length === 0 || statusTujuan === '' || sedangSimpan"
                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary-container px-3.5 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-all hover:bg-primary disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span class="material-symbols-outlined text-[1.125rem]" x-text="sedangSimpan ? 'progress_activity' : 'done_all'"></span>
                <span x-text="sedangSimpan ? 'Menyimpan…' : 'Terapkan ke Terpilih'"></span>
            </button>
        </div>
    </div>

    {{ $slot }}
</div>
