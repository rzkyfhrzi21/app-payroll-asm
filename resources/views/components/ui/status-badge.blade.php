<!-- ======================================================
     KOMPONEN UI: LENCANA LABEL STATUS BERWARNA (STATUS BADGE)
     
     Analogi Sederhana:
     Komponen ini ibarat "Pita Stempel Berwarna Status Dokumen".
     - Hijau (Emerald) : Disetujui / Selesai / Terverifikasi / Lunas
     - Kuning (Amber)  : Menunggu ACC / Diajukan / Terjadwal
     - Merah (Red)     : Ditolak / Gagal / Rusak
     - Abu-abu (Slate) : Draf Belum Diterbitkan / Nonaktif
====================================================== -->
@props([
    'tone' => 'slate',
    'label',
])

@php
    $tones = [
        'slate' => 'bg-surface-container text-slate-text-secondary',
        'blue' => 'bg-surface-container text-primary',
        'emerald' => 'bg-net-pay-surface text-net-pay-emerald',
        'amber' => 'bg-warning-surface text-warning-amber',
        'red' => 'bg-danger-surface text-danger-red',
        'dark' => 'bg-sidebar-dark text-white',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-sans text-label-sm font-semibold '.($tones[$tone] ?? $tones['slate'])]) }}>
    {{ $label }}
</span>
