<!-- ======================================================
     KOMPONEN UI: UBIN KARTU KECIL BER-PROGRESS BAR (STAT TILE)
     
     Analogi Sederhana:
     Komponen ini ibarat "Panel Pengukur Bar Persentase Kerja".
     Dipakai pada halaman master untuk memperlihatkan rasio,
     misalnya: Truk yang sedang jalan dibanding truk menganggur di pool.
====================================================== -->
@props([
    'label',
    'nilai',
    'satuan' => null,
    'ikon' => 'analytics',
    'tone' => 'primary',
    'catatan' => null,
    'progres' => null,
    'progresLabel' => null,
])

@php
    $gaya = [
        'primary' => [
            'ikonBg' => 'bg-primary/10',
            'ikonWarna' => 'text-primary',
            'nilaiWarna' => 'text-slate-text-primary',
            'bar' => 'bg-primary',
            'chip' => 'bg-primary/10 text-primary',
        ],
        'emerald' => [
            'ikonBg' => 'bg-net-pay-surface',
            'ikonWarna' => 'text-net-pay-emerald',
            'nilaiWarna' => 'text-net-pay-emerald',
            'bar' => 'bg-net-pay-emerald',
            'chip' => 'bg-net-pay-surface text-net-pay-emerald',
        ],
        'amber' => [
            'ikonBg' => 'bg-warning-surface',
            'ikonWarna' => 'text-warning-amber',
            'nilaiWarna' => 'text-warning-amber',
            'bar' => 'bg-warning-amber',
            'chip' => 'bg-warning-surface text-warning-amber',
        ],
        'slate' => [
            'ikonBg' => 'bg-surface-container',
            'ikonWarna' => 'text-slate-text-secondary',
            'nilaiWarna' => 'text-slate-text-primary',
            'bar' => 'bg-slate-text-muted',
            'chip' => 'bg-surface-container text-slate-text-secondary',
        ],
    ][$tone] ?? [
        'ikonBg' => 'bg-primary/10',
        'ikonWarna' => 'text-primary',
        'nilaiWarna' => 'text-slate-text-primary',
        'bar' => 'bg-primary',
        'chip' => 'bg-primary/10 text-primary',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-xl bg-surface-container-lowest p-space-lg shadow-card transition-shadow hover:shadow-lg']) }}>
    <div class="mb-space-sm flex items-start justify-between gap-2">
        <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">{{ $label }}</span>
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $gaya['ikonBg'] }} {{ $gaya['ikonWarna'] }}">
            <span class="material-symbols-outlined text-[1.125rem]">{{ $ikon }}</span>
        </span>
    </div>

    <div class="flex items-baseline gap-1.5">
        <span class="font-display text-display-hero-mobile font-bold leading-none tabular-nums {{ $gaya['nilaiWarna'] }}">{{ $nilai }}</span>
        @if ($satuan)
            <span class="font-sans text-body-sm font-medium text-slate-text-muted">{{ $satuan }}</span>
        @endif
    </div>

    @if ($progres !== null)
        <div class="mt-space-sm flex flex-col gap-1">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-container-high">
                <div class="h-full rounded-full transition-all duration-500 {{ $gaya['bar'] }}" style="width: {{ min(100, max(0, $progres)) }}%"></div>
            </div>
            @if ($progresLabel)
                <span class="font-sans text-label-sm text-slate-text-muted">{{ $progresLabel }}</span>
            @endif
        </div>
    @endif

    @if ($catatan)
        <p class="mt-space-sm font-sans text-body-sm text-slate-text-secondary">{{ $catatan }}</p>
    @endif
</div>
