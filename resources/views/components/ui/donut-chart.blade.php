@props([
    'items' => [],
    'total' => 0,
    'label' => 'Unit',
    'judul' => 'Komposisi Armada',
])

@php
    // Palet mengikuti design system CoalTrans.
    $palet = ['#2563EB', '#4F46E5', '#38BDF8', '#10B981', '#F59E0B', '#F43F5E', '#8B5CF6', '#0EA5E9'];

    $radius = 70;
    $tebalGaris = 22;
    $keliling = 2 * M_PI * $radius;
    $offset = 0.0;
    $segmen = [];

    foreach ($items as $urutan => $item) {
        $persen = (float) $item['pct'];
        $panjang = ($persen / 100) * $keliling;

        // Celah kecil antar segmen agar batas warna terbaca jelas.
        $celah = count($items) > 1 ? 2.5 : 0;

        $segmen[] = [
            'model' => $item['model'],
            'unit' => $item['unit'],
            'pct' => $persen,
            'ritase' => $item['ritase'] ?? 0,
            'tonase' => $item['tonase'] ?? '0,00',
            'warna' => $palet[$urutan % count($palet)],
            'dash' => max(0, $panjang - $celah),
            'offset' => -$offset,
        ];

        $offset += $panjang;
    }

    $jumlahModel = count($segmen);
@endphp

@if ($segmen !== [])
    <div
        {{ $attributes->merge(['class' => 'flex flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-lg shadow-card']) }}
        x-data="{ aktif: null }"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[1.25rem] text-primary">donut_large</span>
                <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">{{ $judul }}</h3>
            </div>
            <span class="rounded-full bg-surface-container px-2.5 py-0.5 font-sans text-label-sm font-semibold text-slate-text-secondary">
                {{ $jumlahModel }} Model
            </span>
        </div>

        <div class="flex flex-col items-center gap-space-lg sm:flex-row sm:items-center">
            {{-- Donat SVG --}}
            <div class="relative shrink-0">
                <svg viewBox="0 0 200 200" class="h-40 w-40 -rotate-90" role="img"
                    aria-label="Diagram komposisi model armada, total {{ $total }} unit">
                    {{-- Rel dasar --}}
                    <circle cx="100" cy="100" r="{{ $radius }}"
                        fill="none" stroke="#E2E8F0" stroke-width="{{ $tebalGaris }}" />

                    @foreach ($segmen as $urutan => $bagian)
                        <circle
                            cx="100" cy="100" r="{{ $radius }}"
                            fill="none"
                            stroke="{{ $bagian['warna'] }}"
                            stroke-width="{{ $tebalGaris }}"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $bagian['dash'] }} {{ $keliling - $bagian['dash'] }}"
                            stroke-dashoffset="{{ $bagian['offset'] }}"
                            class="cursor-pointer transition-all duration-200"
                            :class="aktif === {{ $urutan }} ? 'opacity-100' : (aktif !== null ? 'opacity-40' : 'opacity-100')"
                            @mouseenter="aktif = {{ $urutan }}"
                            @mouseleave="aktif = null"
                        >
                            <title>{{ $bagian['model'] }} — {{ $bagian['unit'] }} unit ({{ number_format($bagian['pct'], 1, ',', '.') }}%)</title>
                        </circle>
                    @endforeach
                </svg>

                {{-- Label tengah: berubah saat hover --}}
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <template x-if="aktif === null">
                        <div class="flex flex-col items-center">
                            <span class="font-display text-headline-lg font-bold leading-none text-slate-text-primary">{{ $total }}</span>
                            <span class="mt-0.5 font-sans text-[0.6875rem] uppercase tracking-wider text-slate-text-muted">{{ $label }}</span>
                        </div>
                    </template>

                    @foreach ($segmen as $urutan => $bagian)
                        <template x-if="aktif === {{ $urutan }}">
                            <div class="flex flex-col items-center px-2 text-center">
                                <span class="font-display text-headline-md font-bold leading-none text-slate-text-primary">{{ $bagian['unit'] }}</span>
                                <span class="mt-0.5 line-clamp-2 font-sans text-[0.625rem] uppercase tracking-wide text-slate-text-muted">{{ $bagian['model'] }}</span>
                                <span class="mt-0.5 font-sans text-label-sm font-bold tabular-nums" style="color: {{ $bagian['warna'] }}">{{ number_format($bagian['pct'], 1, ',', '.') }}%</span>
                            </div>
                        </template>
                    @endforeach
                </div>
            </div>

            {{-- Legenda --}}
            <div class="flex w-full min-w-0 flex-col gap-0.5">
                @foreach ($segmen as $urutan => $bagian)
                    <div
                        class="flex cursor-pointer items-center gap-2 rounded-md px-1.5 py-1 transition-colors hover:bg-surface-container-low"
                        :class="aktif === {{ $urutan }} ? 'bg-surface-container-low' : ''"
                        @mouseenter="aktif = {{ $urutan }}"
                        @mouseleave="aktif = null"
                    >
                        <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background: {{ $bagian['warna'] }}"></span>
                        <span class="min-w-0 flex-1 truncate font-sans text-body-sm text-slate-text-secondary">{{ $bagian['model'] }}</span>
                        <span class="shrink-0 font-sans text-body-sm font-bold tabular-nums text-slate-text-primary">{{ $bagian['unit'] }}</span>
                        <span class="w-11 shrink-0 text-right font-sans text-label-sm tabular-nums text-slate-text-muted">{{ number_format($bagian['pct'], 1, ',', '.') }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
