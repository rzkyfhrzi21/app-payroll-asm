<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN ITEM MENU AKSI TABEL):
     - Tujuan Komponen: Satu baris pilihan di dalam dropdown `x-ui.tabel-aksi`.
     - Analogi Dunia Nyata: Satu lembar berkas di dalam laci serbaguna tadi.
     - Prop `tone`: netral (default) | utama (biru) | hapus (merah).
     - Prop `ikon`: nama ikon Material Symbols, contoh "visibility", "edit", "delete".
     ============================================================================== -->
@props([
    'ikon' => 'chevron_right',
    'tone' => 'netral',
])

@php
    $kelasNada = match ($tone) {
        'utama' => 'text-primary hover:bg-surface-container-low',
        'hapus' => 'text-danger-red hover:bg-danger-surface/60',
        'cetak' => 'text-banner-indigo hover:bg-surface-container-low',
        default => 'text-slate-text-primary hover:bg-surface-container-low',
    };
@endphp

<button
    type="button"
    role="menuitem"
    {{ $attributes->merge([
        'class' => "flex w-full items-center gap-2.5 rounded-lg px-3 py-2 font-sans text-body-sm font-medium transition-colors {$kelasNada}",
    ]) }}
>
    <span class="material-symbols-outlined text-[1.25rem]">{{ $ikon }}</span>
    <span>{{ $slot }}</span>
</button>
