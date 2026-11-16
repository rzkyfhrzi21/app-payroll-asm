@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'breadcrumb' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between']) }}>
    <div class="flex flex-col gap-1.5">
        @if ($breadcrumb)
            <div class="flex items-center gap-1 font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">
                <span>Dashboard</span>
                <span class="material-symbols-outlined text-[0.875rem]">chevron_right</span>
                <span class="font-semibold text-slate-text-primary">{{ $breadcrumb }}</span>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <h1 class="font-display text-headline-lg tracking-tight text-slate-text-primary">{{ $title }}</h1>
            @if ($badge)
                <span class="rounded-full bg-surface-container px-2.5 py-0.5 font-sans text-label-sm font-semibold text-primary">{{ $badge }}</span>
            @endif
        </div>

        @if ($subtitle)
            <p class="max-w-3xl font-sans text-body-md text-slate-text-secondary">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-2">
        {{ $slot }}
    </div>
</div>
