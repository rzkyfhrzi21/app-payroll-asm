@props([
    'id' => 'berkas_foto',
    'name' => 'foto',
    'previewId' => null,
    'accept' => 'image/jpeg,image/png,image/webp',
    'ikon' => false,
    'tinggi' => 'h-48',
    'multiple' => false,
])

@php
    $idPratinjau = $previewId ?? $id.'-pratinjau';
    $kelasInput = 'w-full rounded-lg bg-surface-container-low py-2 pr-3 font-sans text-body-sm text-slate-text-primary file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1 file:font-semibold file:text-primary hover:file:bg-primary/20 '
        .($ikon ? 'pl-9' : 'pl-3');
@endphp

<div class="flex w-full flex-col gap-2">
    <input
        type="file"
        id="{{ $id }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        @if ($multiple) multiple @endif
        onchange="window.prosesInputFoto(event, @js($idPratinjau))"
        {{ $attributes->merge(['class' => $kelasInput]) }}
    >

    <div
        id="{{ $idPratinjau }}"
        class="relative hidden w-full overflow-hidden rounded-lg border border-dashed border-card-border bg-surface-container-low {{ $tinggi }}"
        data-pratinjau-foto
    >
        <img data-gambar-pratinjau alt="Pratinjau foto yang dipilih" class="h-full w-full object-contain">
        <span
            data-keterangan-berkas
            class="absolute inset-x-0 bottom-0 truncate bg-slate-text-primary/75 px-3 py-1.5 font-sans text-label-sm font-medium text-white"
        ></span>
    </div>

    @if ($multiple)
        <div
            id="{{ $idPratinjau }}-grid"
            data-pratinjau-grid
            class="hidden grid grid-cols-2 gap-2.5 rounded-lg border border-dashed border-card-border bg-surface-container-low p-2.5 sm:grid-cols-3"
        ></div>
    @endif
</div>
