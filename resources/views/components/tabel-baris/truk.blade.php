{{-- FRAGMEN BARIS TABEL: UNIT ARMADA (TRUK). Dipakai SSR + AJAX. --}}
@php
    $angka = fn ($nilai) => number_format((int) $nilai, 0, ',', '.');
@endphp

<tr class="transition-colors hover:bg-surface-container-low/60">
    <td class="px-2 py-3 text-center">
        <input
            type="checkbox"
            value="{{ $baris['id'] }}"
            x-model="terpilih"
            data-centang-massal
            class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
            aria-label="Pilih unit {{ $baris['nomor_polisi'] }}"
        >
    </td>
    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $nomor }}</td>
    <td class="px-4 py-3">
        <button type="button" class="font-display text-headline-sm font-semibold tabular-nums text-primary hover:underline" @click="bukaDetailTruk(@js($baris))">{{ $baris['nomor_polisi'] }}</button>
    </td>
    <td class="px-4 py-3">
        @if (($baris['nama_jenis'] ?? '') !== '')
            <span class="rounded bg-surface-container-high px-2 py-0.5 font-sans text-label-sm font-semibold text-primary">{{ $baris['nama_jenis'] }}</span>
        @else
            <span class="font-sans text-label-sm text-slate-text-muted">—</span>
        @endif
    </td>
    <td class="px-4 py-3 text-slate-text-primary">{{ $baris['merek_model'] }}</td>
    <td class="px-4 py-3">
        @if (($baris['nama_supir'] ?? '') !== '')
            <div class="flex flex-col">
                <span class="font-semibold text-slate-text-primary">{{ $baris['nama_supir'] }}</span>
                <span class="font-sans text-label-sm text-slate-text-muted">{{ $baris['nip_supir'] }}</span>
            </div>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-2 py-0.5 font-sans text-label-sm font-semibold text-warning-amber">
                <span class="material-symbols-outlined text-[0.875rem]">person_off</span>
                Belum ada supir
            </span>
        @endif
    </td>
    <td class="px-4 py-3 text-center">
        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $baris['status'] === 'aktif' ? 'bg-net-pay-surface text-net-pay-emerald' : 'bg-surface-container text-slate-text-secondary' }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $baris['status'] === 'aktif' ? 'bg-net-pay-emerald' : 'bg-slate-text-muted' }}"></span>
            {{ ucfirst($baris['status']) }}
        </span>
    </td>
    <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-text-primary">{{ $angka($baris['ritase_count'] ?? 0) }}</td>
    <td class="px-4 py-3 text-right font-semibold tabular-nums text-primary">{{ $baris['tonase'] ?? '0,00' }} <span class="font-sans text-label-sm font-normal text-slate-text-muted">Ton</span></td>
    <td class="px-4 py-3 text-center">
        <x-ui.tabel-aksi label="Aksi Unit Armada">
            <x-ui.tabel-aksi-item ikon="visibility" @click="bukaDetailTruk({!! \Illuminate\Support\Js::from($baris) !!})">
                Detail Unit
            </x-ui.tabel-aksi-item>
            <x-ui.tabel-aksi-item ikon="edit" tone="utama" @click="bukaEditTruk({!! \Illuminate\Support\Js::from($baris) !!})">
                Ubah Unit
            </x-ui.tabel-aksi-item>
            <div class="my-1 border-t border-card-border"></div>
            <x-ui.tabel-aksi-item ikon="delete" tone="hapus" @click="bukaHapusTruk({!! \Illuminate\Support\Js::from($baris) !!})">
                Hapus Unit
            </x-ui.tabel-aksi-item>
        </x-ui.tabel-aksi>
    </td>
</tr>
