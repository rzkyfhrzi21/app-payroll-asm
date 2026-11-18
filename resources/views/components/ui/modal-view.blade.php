<!-- ======================================================
     KOMPONEN UI: MODAL DETAIL DATA BACA-SAJA (MODAL VIEW READ-ONLY)
     
     Analogi Sederhana:
     Komponen ini ibarat "Map Berkas Detail Data Pegawai / Truk".
     Saat admin atau supir mengklik tombol ikon mata (Detail),
     jendela ini menampilkan seluruh informasi lengkap data baris per baris
     secara rapi dan hanya bisa dibaca (read-only tanpa risiko teredit).
====================================================== -->
@props([
    'name',
    'title',
    'subtitle' => null,
    'icon' => 'visibility',
    'badge' => null,
    'baris' => '[]',
    'zIndex' => 50,
])

<x-ui.modal-shell :name="$name" :title="$title" :subtitle="$subtitle" :icon="$icon" :badge="$badge" max-width="2xl" :z-index="$zIndex">
    <div class="flex max-h-[65vh] flex-col gap-space-sm overflow-y-auto px-space-lg pb-space-md">
        <template x-for="(item, urutan) in {{ $baris }}" :key="urutan">
            <div class="flex flex-col gap-0.5 border-b border-card-border pb-2 last:border-0">
                <span class="font-sans text-label-sm uppercase tracking-wide text-slate-text-muted" x-text="item.label"></span>
                <span class="break-words font-sans text-body-md font-semibold text-slate-text-primary" x-text="(item.nilai === null || item.nilai === '') ? '—' : item.nilai"></span>
            </div>
        </template>

        {{ $slot }}
    </div>

    <div class="flex items-center justify-end bg-canvas-bg px-space-lg py-space-md shadow-inner">
        <button
            type="button"
            class="rounded-lg bg-card-bg px-space-lg py-2.5 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low"
            @click="{{ $name }} = false"
        >
            Tutup
        </button>
    </div>
</x-ui.modal-shell>
