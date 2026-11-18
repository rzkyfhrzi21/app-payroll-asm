<!-- ======================================================
     KOMPONEN UI: KERANGKA JENDELA POPUP (MODAL SHELL)
     
     Analogi Sederhana:
     Komponen ini ibarat "Bingkai Jendela Popup (Dialog Box)".
     Saat admin mengklik tombol Tambah Data, Edit, atau Detail,
     bingkai ini melayang di tengah layar dengan latar belakang gelap buram.
     
     Fitur Utama:
     - Lapisan zIndex bertingkat: modal di atas modal (misal: modal jenis truk
       di atas modal truk induk) tanpa tabrakan visual.
     - Menutup otomatis saat tombol Escape ditekan atau area luar diklik.
     - Header fleksibel dengan ikon simbol, judul, subjudul, dan tombol silang (x).
====================================================== -->
@props([
    'name',
    'title' => '',
    'subtitle' => null,
    'icon' => 'edit',
    'badge' => null,
    'accent' => 'none',
    'maxWidth' => '2xl',
    'zIndex' => 50,
])

@php
    $lebarMaks = [
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
    ][$maxWidth] ?? 'max-w-2xl';

    $aksen = match ($accent) {
        'gradient' => 'h-1.5 w-full bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep',
        'danger' => 'h-1.5 w-full bg-danger-red',
        default => '',
    };
@endphp

<div
    x-show="{{ $name }}"
    x-cloak
    x-effect="if ({{ $name }}) { window.siapkanModalSaatBuka && window.siapkanModalSaatBuka($el, '{{ $name }}'); }"
    class="fixed inset-0 flex items-center justify-center overflow-y-auto p-4"
    style="z-index: {{ $zIndex }};"
    role="dialog"
    aria-modal="true"
    aria-labelledby="judul-{{ $name }}"
    @keydown.escape.window="{{ $name }} = false"
    @keydown.tab="window.trapFokusModal && window.trapFokusModal($event, $el)"
>
    <div class="fixed inset-0 bg-sidebar-dark/60 backdrop-blur-sm" @click="{{ $name }} = false"></div>

    <div class="relative my-auto flex w-full {{ $lebarMaks }} flex-col overflow-hidden rounded-xl bg-card-bg shadow-drawer">
        @if ($aksen !== '')
            <div class="{{ $aksen }}"></div>
        @endif

        <div class="flex items-start justify-between gap-space-md p-space-lg">
            <div class="flex items-start gap-space-md">
                <div @class([
                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl shadow-sm',
                    'bg-surface-container text-primary' => $accent !== 'danger',
                    'bg-danger-surface text-danger-red' => $accent === 'danger',
                ])>
                    <span class="material-symbols-outlined text-[1.5rem]">{{ $icon }}</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="judul-{{ $name }}" class="font-display text-headline-md font-bold tracking-tight text-slate-text-primary">{{ $title }}</h2>
                        @if ($badge)
                            <span class="rounded bg-surface-container px-2 py-0.5 font-sans text-label-sm font-semibold uppercase text-primary">{{ $badge }}</span>
                        @endif
                    </div>
                    @if ($subtitle)
                        <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <button
                type="button"
                class="flex h-10 w-10 min-h-[44px] min-w-[44px] shrink-0 items-center justify-center rounded-lg bg-surface-container-low text-slate-text-muted transition-colors hover:bg-surface-container hover:text-slate-text-primary"
                @click="{{ $name }} = false"
                aria-label="Tutup dialog"
            >
                <span class="material-symbols-outlined text-[1.25rem]">close</span>
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
