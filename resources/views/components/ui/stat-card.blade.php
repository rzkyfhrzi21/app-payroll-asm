<!-- ======================================================
     KOMPONEN UI: KARTU METRIK STATISTIK UTAMA (STAT CARD)
     
     Analogi Sederhana:
     Komponen ini ibarat "Papan Indikator Spedometer Angka Penting".
     Menampilkan ringkasan angka besar seperti Total Gaji Bersih Periode,
     Total Ritase Selesai, Jumlah Kasbon Berjalan, atau Jumlah Armada Siap Jalan.
====================================================== -->
@props([
    'label',
    'value',
    'sub' => null,
    'icon' => 'analytics',
    'tone' => 'blue',
    'badge' => null,
    'badgeTone' => 'slate',
])

@php
    $iconTones = [
        'blue' => 'bg-surface-container text-primary',
        'indigo' => 'bg-surface-container text-banner-indigo',
        'emerald' => 'bg-net-pay-surface text-net-pay-emerald',
        'amber' => 'bg-warning-surface text-warning-amber',
        'red' => 'bg-danger-surface text-danger-red',
        'slate' => 'bg-surface-container-high text-slate-text-secondary',
    ];

    $badgeTones = [
        'slate' => 'bg-surface-container text-slate-text-secondary',
        'blue' => 'bg-surface-container text-primary',
        'emerald' => 'bg-net-pay-surface text-net-pay-emerald',
        'amber' => 'bg-warning-surface text-warning-amber',
        'red' => 'bg-danger-surface text-danger-red',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl bg-surface-container-lowest p-space-lg shadow-card']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 flex-col gap-1">
            <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">{{ $label }}</span>
            <div class="truncate font-display text-financial-total tabular-nums text-slate-text-primary">{{ $value }}</div>
        </div>

        <div class="flex shrink-0 flex-col items-end gap-2">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $iconTones[$tone] ?? $iconTones['blue'] }}">
                <span class="material-symbols-outlined text-[1.375rem]">{{ $icon }}</span>
            </div>
            @if ($badge)
                <span class="rounded-full px-2 py-0.5 font-sans text-label-sm font-semibold {{ $badgeTones[$badgeTone] ?? $badgeTones['slate'] }}">{{ $badge }}</span>
            @endif
        </div>
    </div>

    @if ($sub)
        <p class="mt-3 font-sans text-body-sm text-slate-text-secondary">{{ $sub }}</p>
    @endif

    {{ $slot }}
</div>
