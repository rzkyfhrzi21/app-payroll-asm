@props([
    'label',
    'name' => null,
    'required' => false,
    'hint' => null,
    'icon' => null,
    'error' => null,
])

@php
    $idField = $name ?? \Illuminate\Support\Str::slug($label);
    $adaError = $error !== null && $error !== '';
@endphp

<div class="flex flex-col gap-1.5">
    <div class="flex items-center justify-between gap-2">
        <label for="{{ $idField }}" class="flex items-center gap-1 font-sans text-body-md font-semibold text-slate-text-primary">
            <span>{{ $label }}</span>
            @if ($required)
                <span class="font-bold text-danger-red" aria-hidden="true">*</span>
            @endif
        </label>
        @isset($meta)
            <span class="font-sans text-label-sm text-slate-text-muted">{{ $meta }}</span>
        @endisset
    </div>

    <div class="relative flex items-center">
        @if ($icon)
            <span class="pointer-events-none absolute left-3 material-symbols-outlined text-[1.125rem] text-slate-text-muted">{{ $icon }}</span>
        @endif

        {{ $slot }}
    </div>

    @if ($hint)
        <p class="flex items-center gap-1 font-sans text-body-sm text-slate-text-secondary">
            <span class="material-symbols-outlined text-[0.875rem] text-slate-text-muted">info</span>
            <span>{{ $hint }}</span>
        </p>
    @endif

    @if ($adaError)
        <p class="font-sans text-body-sm font-medium text-danger-red" role="alert">{{ $error }}</p>
    @endif
</div>
