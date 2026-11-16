@props([
    'colspan' => 8,
    'label' => 'data',
    'tampilkanReset' => true,
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-12 text-center">
        <div class="mx-auto flex max-w-md flex-col items-center justify-center gap-2">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-surface-container text-slate-text-muted">
                <span class="material-symbols-outlined text-[1.75rem]">manage_search</span>
            </div>
            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Data Tidak Ditemukan</h3>
            <p class="font-sans text-body-sm text-slate-text-secondary">
                Tidak ada {{ $label }} yang cocok dengan kata kunci pencarian atau kombinasi filter saat ini.
            </p>
            @if ($tampilkanReset)
                <a
                    href="{{ url()->current() }}"
                    class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3.5 py-2 font-sans text-label-md font-semibold text-primary transition-colors hover:bg-primary hover:text-on-primary"
                >
                    <span class="material-symbols-outlined text-[1rem]">filter_alt_off</span>
                    Reset Pencarian &amp; Filter
                </a>
            @endif
        </div>
    </td>
</tr>
