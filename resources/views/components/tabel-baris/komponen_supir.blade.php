{{-- FRAGMEN BARIS TABEL: POTONGAN BPJS PER SUPIR. Dipakai SSR + AJAX. --}}
@php
    $rpBaris = fn ($nilai) => 'Rp '.number_format((int) $nilai, 0, ',', '.');
@endphp

<tr class="transition-colors hover:bg-surface-container-low/60">
    <td class="px-2 py-3 text-center">
        <input
            type="checkbox"
            value="{{ $baris['id'] }}"
            x-model="terpilih"
            data-centang-bpjs
            class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
            aria-label="Pilih supir {{ $baris['nama'] }}"
        >
    </td>
    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $nomor }}</td>
    <td class="px-4 py-3">
        <span class="font-display text-headline-sm font-semibold text-slate-text-primary">{{ $baris['nama'] }}</span>
    </td>
    <td class="px-4 py-3 text-slate-text-secondary">{{ $baris['nip'] }}</td>
    <td class="px-4 py-3 text-center">
        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $baris['status'] === 'aktif' ? 'bg-net-pay-surface text-net-pay-emerald' : 'bg-surface-container text-slate-text-secondary' }}">
            {{ ucfirst($baris['status']) }}
        </span>
    </td>
    <td class="px-4 py-3 text-right font-bold tabular-nums text-slate-text-primary">{{ $rpBaris($baris['bpjs']) }}</td>
    <td class="px-4 py-3 text-center">
        @if ($baris['bpjs'] > 0)
            <span class="inline-flex items-center rounded-full bg-net-pay-surface px-2 py-0.5 font-sans text-label-sm font-bold text-net-pay-emerald">Terdaftar</span>
        @else
            <span class="inline-flex items-center rounded-full bg-surface-container px-2 py-0.5 font-sans text-label-sm font-bold text-slate-text-secondary">Belum terdaftar</span>
        @endif
    </td>
</tr>
