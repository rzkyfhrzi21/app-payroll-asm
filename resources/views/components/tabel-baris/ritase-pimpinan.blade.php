{{-- FRAGMEN BARIS TABEL: PEMANTAUAN PENGANTARAN (PIMPINAN, READ-ONLY)
     Dipakai SSR (executive/trips) DAN endpoint AJAX tabel.
     Variabel: $baris (array), $nomor (int)
     Catatan: peran pimpinan hanya membaca, jadi tidak ada kolom aksi. --}}
@php
    $rpBaris = fn ($nilai) => 'Rp '.number_format((int) $nilai, 0, ',', '.');
@endphp

<tr class="transition-colors hover:bg-surface-container-low/70">
    <td class="px-4 py-3">
        <div class="flex flex-col">
            <span class="font-semibold">{{ $baris['date'] }}</span>
            <span class="font-sans text-label-sm">{{ $baris['sj'] }}</span>
        </div>
    </td>
    <td class="px-4 py-3">
        <div class="flex flex-col">
            <span class="font-bold">{{ $baris['driver'] }}</span>
            <span class="font-sans text-label-sm">{{ $baris['plate'] }} &bull; {{ $baris['truck'] }}</span>
        </div>
    </td>
    <td class="px-3 py-3 text-center">
        <span class="inline-block rounded bg-surface-container-high px-2 py-0.5 font-sans text-label-sm font-bold text-banner-deep">{{ $baris['material'] }}</span>
    </td>
    <td class="px-3 py-3 text-center font-bold">{{ $baris['rit'] }} Rit</td>
    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $baris['tonnage'] }} Ton</td>
    <td class="px-4 py-3 text-right tabular-nums">{{ $rpBaris($baris['rate']) }}</td>
    <td class="px-4 py-3 text-right font-bold tabular-nums text-primary">{{ $rpBaris($baris['total']) }}</td>
    <td class="px-4 py-3 text-right font-bold tabular-nums text-net-pay-emerald">{{ $rpBaris($baris['trip_money']) }}</td>
    <td class="px-4 py-3 text-center">
        <span class="inline-block rounded bg-primary-fixed px-2 py-0.5 font-sans text-label-sm font-semibold text-on-primary-fixed">{{ $baris['route'] }}</span>
    </td>
</tr>
