<!-- ==============================================================================
     KOMENTAR ORANG TUA (KOMPONEN TABEL AJAX SERVER-SIDE):
     - Tujuan Komponen: Mengganti navigasi paginasi berbasis <a href> dan filter
       berbasis submit form (yang menulis query string panjang di URL) menjadi
       pemuatan data via AJAX POST.
     - Analogi Dunia Nyata: Seperti memesan arsip lewat telepon ke petugas:
       kita sebutkan kriteria, petugas mengambilkan, tanpa kita harus pindah
       ruangan (tanpa halaman dimuat ulang, tanpa URL berubah).
     - Prinsip penting:
       1. Muat pertama tetap SSR (server-side render) agar tautan langsung,
          pencetakan, dan pengujian tetap berjalan.
       2. Setelah itu, filter/cari/paginasi dikirim via POST ke endpoint peran.
       3. URL TIDAK PERNAH berubah (tanpa query string).
       4. State filter disimpan di sessionStorage agar refresh tidak menghapusnya.
     - Data dari Controller / Props:
       * $entitas   : kunci entitas pada endpoint (mis. "ritase", "karyawan").
       * $label     : sebutan yang dibaca manusia (mis. "surat jalan").
       * $targetId  : id <tbody> yang akan diganti isinya.
       * $kolom     : jumlah kolom tabel (untuk baris kosong).
       * $filter    : array nilai filter awal dari server (SSR).
     ============================================================================== -->
@props([
    'entitas',
    'label' => 'data',
    'targetId',
    'kolom' => 10,
    'filter' => [],
    'perHalamanAwal' => 25,
    'endpoint' => null,
])

@php
    $urlEndpoint = $endpoint ?? route(auth()->user()?->isAdmin() ? 'admin.interaksi.tabel-ajax' : (auth()->user()?->isPimpinan() ? 'executive.interaksi.tabel' : 'driver.interaksi.tabel'));
    // State filter disimpan per peran agar filter milik Admin tidak terbawa ke
    // halaman Pimpinan (dan sebaliknya) saat berpindah peran di peramban yang sama.
    $peranPengguna = auth()->user()?->role?->value ?? 'tamu';
    $kunciSimpan = 'filter_tabel_'.$peranPengguna.'_'.$entitas;
@endphp

<div
    x-data="tabelAjaxServer({
        entitas: @js($entitas),
        endpoint: @js($urlEndpoint),
        targetId: @js($targetId),
        label: @js($label),
        kolom: {{ (int) $kolom }},
        filterAwal: @js($filter),
        perHalamanAwal: {{ (int) $perHalamanAwal }},
        kunciSimpan: @js($kunciSimpan),
    })"
    class="flex flex-col items-center justify-between gap-3 border-t border-card-border bg-surface/60 px-4 py-3 sm:flex-row"
>
    <div class="flex flex-wrap items-center gap-3 font-sans text-body-sm text-slate-text-secondary">
        <span>
            Menampilkan
            <strong class="text-slate-text-primary" x-text="teksRentang"></strong>
            dari
            <strong class="text-slate-text-primary" x-text="formatAngka(meta.total)"></strong>
            {{ $label }}
        </span>

        <div class="flex items-center gap-1.5">
            <span class="text-slate-text-muted">Baris:</span>
            <select
                x-model.number="perHalaman"
                @change="muatHalaman(1)"
                data-no-select2
                aria-label="Jumlah baris per halaman"
                class="rounded-lg border border-card-border bg-card-bg px-2 py-1 font-sans text-label-md font-semibold text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
            >
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>

        <span x-show="sedangMemuat" x-cloak class="flex items-center gap-1.5 font-semibold text-primary">
            <span class="material-symbols-outlined animate-spin text-[1rem]">progress_activity</span>
            <span>Memuat…</span>
        </span>
    </div>

    <nav x-show="meta.last_page > 1" class="flex items-center gap-1.5" aria-label="Navigasi halaman {{ $label }}">
        <button
            type="button"
            @click="muatHalaman(meta.current_page - 1)"
            :disabled="meta.current_page <= 1 || sedangMemuat"
            class="inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg border border-card-border bg-card-bg px-2.5 text-slate-text-primary transition-colors hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-40"
            aria-label="Halaman sebelumnya"
        >
            <span class="material-symbols-outlined text-[1rem]">chevron_left</span>
        </button>

        <template x-for="halaman in daftarNomorHalaman" :key="halaman">
            <button
                type="button"
                @click="muatHalaman(halaman)"
                :disabled="sedangMemuat"
                :class="halaman === meta.current_page
                    ? 'bg-primary text-on-primary shadow-sm border-primary'
                    : 'border-card-border bg-card-bg text-slate-text-primary hover:bg-surface-container-low'"
                class="inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg border px-2.5 font-sans text-xs font-bold transition-colors disabled:cursor-not-allowed"
                :aria-current="halaman === meta.current_page ? 'page' : null"
                x-text="halaman"
            ></button>
        </template>

        <button
            type="button"
            @click="muatHalaman(meta.current_page + 1)"
            :disabled="meta.current_page >= meta.last_page || sedangMemuat"
            class="inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg border border-card-border bg-card-bg px-2.5 text-slate-text-primary transition-colors hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-40"
            aria-label="Halaman berikutnya"
        >
            <span class="material-symbols-outlined text-[1rem]">chevron_right</span>
        </button>
    </nav>

    <div x-show="pesanGalat !== ''" x-cloak class="w-full rounded-lg bg-danger-surface px-3 py-2 font-sans text-body-sm font-medium text-danger-red">
        <span x-text="pesanGalat"></span>
    </div>
</div>
