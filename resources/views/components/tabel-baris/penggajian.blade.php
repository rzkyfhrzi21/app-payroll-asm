{{-- FRAGMEN BARIS TABEL: PAYROLL ADMIN (DAFTAR GAJI SUPIR)
     Dipakai SSR (admin/payroll) DAN endpoint AJAX tabel.
     Variabel: $baris (array), $nomor (int)
     Catatan: seluruh baris membuka drawer rincian saat diklik (perilaku asli). --}}
@php
    $rpBaris = fn ($nilai) => 'Rp '.number_format((int) $nilai, 0, ',', '.');
@endphp

<tr
    class="group cursor-pointer transition-colors hover:bg-surface-container-low/60"
    @click="openDrawer(@js($baris))"
>
    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $nomor }}</td>
    <td class="px-4 py-3">
        <div class="flex items-center gap-3">
            <img
                src="{{ $baris['foto_profil'] }}"
                alt="{{ $baris['initial'] }}"
                loading="lazy"
                onerror="window.tanganiGambarRusak(this, @js($baris['initial']))"
                @click.stop="window.bukaLightbox({ url: @js($baris['foto_profil']), judul: @js($baris['name']), caption: @js($baris['plate'].' • '.$baris['truck']) })"
                title="Klik untuk memperbesar foto supir"
                class="h-9 w-9 shrink-0 cursor-zoom-in rounded-full object-cover ring-1 ring-card-border transition-transform hover:scale-105"
            >
            <div class="flex min-w-0 flex-col">
                <div class="flex items-center gap-1.5">
                    <span class="truncate font-display text-headline-sm font-bold transition-colors group-hover:text-primary">{{ $baris['name'] }}</span>
                    @if ($baris['warning'])
                        <span class="h-2 w-2 rounded-full bg-warning-amber" title="{{ $baris['warning_note'] }}"></span>
                    @endif
                </div>
                <span class="truncate font-sans text-body-sm">{{ $baris['plate'] }} &bull; {{ $baris['truck'] }}</span>
            </div>
        </div>
    </td>

    <td class="px-4 py-3 text-right">
        <div class="font-display text-headline-sm font-bold tabular-nums">{{ $baris['rit'] }} Rit</div>
        <div class="font-sans text-body-sm tabular-nums">{{ $baris['tonnage'] }} Ton</div>
    </td>

    <td class="px-4 py-3 text-right font-sans font-semibold tabular-nums">{{ $rpBaris($baris['net_result']) }}</td>

    <td class="px-4 py-3 text-right">
        <div class="inline-flex items-center justify-end gap-1.5">
            <span class="font-display text-headline-sm font-bold tabular-nums">{{ $rpBaris($baris['gross_pay']) }}</span>
            @if ($baris['warning'])
                <span class="rounded bg-warning-surface px-1.5 py-0.5 font-sans text-label-sm font-bold text-warning-amber" title="{{ $baris['warning_note'] }}">&#9888;</span>
            @endif
        </div>
        <div class="font-sans text-label-sm text-slate-text-muted">Gaji komisi sebelum potongan</div>
    </td>

    <td class="px-4 py-3 text-right">
        <div class="font-display text-headline-sm font-bold tabular-nums">-{{ $rpBaris($baris['deduction']) }}</div>
        <div class="font-sans text-label-sm">{{ $baris['deduction_note'] }}</div>
    </td>

    <td class="px-4 py-3 text-right">
        <div class="font-sans text-body-md font-semibold tabular-nums">{{ $rpBaris($baris['trip_money']) }}</div>
        <div class="font-sans text-label-sm font-medium text-net-pay-emerald">Lunas SPK</div>
    </td>

    <td class="px-4 py-3 text-right">
        <span class="inline-block rounded-md bg-net-pay-surface px-2.5 py-1 font-display text-headline-sm font-bold tabular-nums">{{ $rpBaris($baris['net_pay']) }}</span>
    </td>

    <td class="px-4 py-3 text-center" @click.stop>
        <x-ui.tabel-aksi label="Aksi Payroll Supir">
            <x-ui.tabel-aksi-item ikon="visibility" @click="openDrawer({!! \Illuminate\Support\Js::from($baris) !!})">
                Lihat Slip &amp; Rincian Gaji
            </x-ui.tabel-aksi-item>
            <x-ui.tabel-aksi-item ikon="edit" tone="utama" @click="openDrawer({!! \Illuminate\Support\Js::from($baris) !!})">
                Edit Komponen Gaji
            </x-ui.tabel-aksi-item>
            <div class="my-1 border-t border-card-border"></div>
            <x-ui.tabel-aksi-item ikon="delete" tone="hapus" @click="bukaHapusPayroll({!! \Illuminate\Support\Js::from($baris) !!})">
                Keluarkan dari Periode
            </x-ui.tabel-aksi-item>
        </x-ui.tabel-aksi>
    </td>
</tr>
