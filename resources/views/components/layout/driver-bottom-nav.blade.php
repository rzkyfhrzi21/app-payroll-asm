@php
    $items = [
        ['label' => 'Gaji', 'icon' => 'payments', 'route' => 'driver.dashboard', 'active' => request()->routeIs('driver.dashboard')],
        ['label' => 'Pengantaran & DO', 'icon' => 'receipt_long', 'route' => 'driver.trips.index', 'active' => request()->routeIs('driver.trips.*')],
        ['label' => 'Kasbon', 'icon' => 'account_balance_wallet', 'route' => 'driver.loans.index', 'active' => request()->routeIs('driver.loans.*')],
        ['label' => 'Profil', 'icon' => 'badge', 'route' => 'driver.profile.edit', 'active' => request()->routeIs('driver.profile.*')],
    ];
@endphp

<nav class="fixed bottom-0 left-0 right-0 z-40 bg-surface/95 pb-[env(safe-area-inset-bottom,0px)] shadow-[0_-1px_8px_rgba(0,0,0,0.06)] backdrop-blur-xl lg:hidden">
    <div class="flex h-16 items-center justify-around px-1">
        @foreach ($items as $item)
            <a
                href="{{ route($item['route']) }}"
                @class([
                    'flex h-12 min-w-[4.25rem] flex-col items-center justify-center gap-0.5 transition-colors',
                    'font-semibold text-primary-container' => $item['active'],
                    'text-slate-text-secondary hover:text-slate-text-primary' => ! $item['active'],
                ])
            >
                <span class="material-symbols-outlined text-[1.375rem]">{{ $item['icon'] }}</span>
                <span class="font-sans text-label-sm">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
