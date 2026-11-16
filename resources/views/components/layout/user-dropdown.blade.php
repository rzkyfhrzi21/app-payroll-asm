@props([
    'nama' => 'Pengguna',
    'peran' => 'Pengguna',
    'ruteDashboard' => 'admin.payroll.index',
    'ruteProfil' => 'profile.edit',
    'ikonAvatar' => 'person',
    'warnaAvatar' => 'bg-primary text-on-primary',
])

@php
    $kata = preg_split('/\s+/', trim((string) $nama)) ?: [];
    $inisial = '';
    foreach (array_slice($kata, 0, 2) as $bagian) {
        $inisial .= mb_strtoupper(mb_substr($bagian, 0, 1));
    }
    $inisial = $inisial !== '' ? $inisial : 'U';
@endphp

<div
    class="relative"
    x-data="{ menuAkunTerbuka: false }"
    @click.outside="menuAkunTerbuka = false"
    @keydown.escape.window="menuAkunTerbuka = false"
>
    <button
        type="button"
        @click="menuAkunTerbuka = ! menuAkunTerbuka"
        class="flex items-center gap-3 rounded-xl px-2.5 py-2 text-left transition-colors hover:bg-surface-container-low focus:outline-none focus:ring-2 focus:ring-primary/30"
        aria-haspopup="true"
        :aria-expanded="menuAkunTerbuka"
    >
        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-display text-headline-sm font-bold shadow-sm overflow-hidden {{ $warnaAvatar }}">
            @if (!empty(auth()->user()?->url_foto_profil))
                <img src="{{ auth()->user()->url_foto_profil }}" alt="{{ $nama }}" class="h-full w-full object-cover">
            @else
                <span>{{ $inisial }}</span>
            @endif
            <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-card-bg bg-net-pay-emerald"></span>
        </div>

        <div class="hidden flex-col leading-snug sm:flex">
            <span class="max-w-[11.5rem] truncate font-sans text-body-md font-bold text-slate-text-primary">{{ $nama }}</span>
            <span class="font-sans text-label-md font-medium text-slate-text-muted">{{ $peran }}</span>
        </div>

        <span
            class="material-symbols-outlined hidden text-[1.25rem] text-slate-text-secondary transition-transform duration-150 sm:block"
            :class="menuAkunTerbuka ? 'rotate-180' : ''"
        >
            expand_more
        </span>
    </button>

    {{-- Dropdown Menu --}}
    <div
        x-cloak
        x-show="menuAkunTerbuka"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 top-14 z-50 w-64 overflow-hidden rounded-2xl border border-card-border bg-card-bg p-2 shadow-drawer"
    >
        <div class="border-b border-card-border px-3.5 py-3">
            <p class="truncate font-sans text-body-md font-bold text-slate-text-primary">{{ $nama }}</p>
            <p class="font-sans text-body-sm font-medium text-slate-text-muted">{{ $peran }}</p>
        </div>

        <div class="mt-1 flex flex-col gap-0.5">
            <a
                href="{{ route($ruteDashboard) }}"
                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 font-sans text-body-md font-semibold text-slate-text-primary transition-colors hover:bg-surface-container-low"
            >
                <span class="material-symbols-outlined text-[1.25rem] text-primary">dashboard</span>
                <span>Dashboard</span>
            </a>

            <a
                href="{{ route($ruteProfil) }}"
                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 font-sans text-body-md font-semibold text-slate-text-primary transition-colors hover:bg-surface-container-low"
            >
                <span class="material-symbols-outlined text-[1.25rem] text-banner-indigo">account_circle</span>
                <span>Profile</span>
            </a>
        </div>

        <div class="my-1.5 border-t border-card-border"></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-left font-sans text-body-md font-semibold text-danger-red transition-colors hover:bg-danger-surface"
            >
                <span class="material-symbols-outlined text-[1.25rem]">logout</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</div>
