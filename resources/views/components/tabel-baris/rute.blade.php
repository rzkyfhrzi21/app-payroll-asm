{{-- FRAGMEN BARIS TABEL: TARIF RUTE. Dipakai SSR + AJAX. --}}
@php
    $rpBaris = fn ($nilai) => 'Rp '.number_format((int) $nilai, 0, ',', '.');
    $angkaBaris = fn ($nilai) => number_format((int) $nilai, 0, ',', '.');
@endphp

<tr class="transition-colors hover:bg-surface-container-low/60">
    <td class="px-2 py-3 text-center">
        <input
            type="checkbox"
            value="{{ $baris['id'] }}"
            x-model="terpilih"
            data-centang-massal
            class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
            aria-label="Pilih rute {{ $baris['code'] }}"
        >
    </td>
    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $nomor }}</td>
    <td class="px-4 py-3">
        <button type="button" class="rounded bg-surface-container-high px-2 py-0.5 font-sans text-label-sm font-bold text-primary hover:bg-primary hover:text-on-primary" @click="bukaDetailRute(@js($baris))">{{ $baris['code'] }}</button>
    </td>
    <td class="px-4 py-3 font-semibold text-slate-text-primary">{{ $baris['name'] }}</td>
    <td class="px-4 py-3">
        @if (($baris['alamat_asal'] ?? '') !== '' || ($baris['alamat_tujuan'] ?? '') !== '')
            <div class="flex flex-col gap-0.5 font-sans text-body-sm">
                <span class="text-slate-text-primary">{{ ($baris['alamat_asal'] ?? '') !== '' ? $baris['alamat_asal'] : '—' }}</span>
                <span class="flex items-center gap-1 text-slate-text-muted">
                    <span class="material-symbols-outlined text-[0.875rem]">south</span>
                    {{ ($baris['alamat_tujuan'] ?? '') !== '' ? $baris['alamat_tujuan'] : '—' }}
                </span>
            </div>
        @else
            <span class="font-sans text-body-sm text-slate-text-muted">Belum diisi</span>
        @endif
    </td>
    <td class="px-4 py-3 text-right tabular-nums text-slate-text-primary">
        {{ $baris['jarak_km'] !== null ? $angkaBaris($baris['jarak_km']).' KM' : '—' }}
    </td>
    <td class="px-4 py-3 text-right font-bold tabular-nums text-slate-text-primary">{{ $rpBaris($baris['rate']) }}</td>
    <td class="px-4 py-3 text-right tabular-nums text-slate-text-primary">{{ $rpBaris($baris['trip_money']) }}</td>
    <td class="px-4 py-3 text-center">
        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $baris['status'] === 'aktif' ? 'bg-net-pay-surface text-net-pay-emerald' : 'bg-surface-container text-slate-text-secondary' }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $baris['status'] === 'aktif' ? 'bg-net-pay-emerald' : 'bg-slate-text-muted' }}"></span>
            {{ ucfirst($baris['status']) }}
        </span>
    </td>
    <td class="px-4 py-3 text-right tabular-nums text-slate-text-primary">{{ $angkaBaris($baris['ritase_count'] ?? 0) }}</td>
    <td class="px-4 py-3 text-right tabular-nums text-slate-text-primary">{{ $baris['tonase'] ?? '0,00' }} Ton</td>
    <td class="px-4 py-3 text-center">
        <x-ui.tabel-aksi label="Aksi Rute">
            <x-ui.tabel-aksi-item ikon="visibility" @click="bukaDetailRute(@js($baris))">
                Detail Rute
            </x-ui.tabel-aksi-item>
            <x-ui.tabel-aksi-item ikon="edit" tone="utama" @click="bukaEditRute(@js($baris))">
                Ubah Rute
            </x-ui.tabel-aksi-item>
            <div class="my-1 border-t border-card-border"></div>
            <x-ui.tabel-aksi-item ikon="delete" tone="hapus" @click="bukaHapusRute(@js($baris))">
                Hapus Rute
            </x-ui.tabel-aksi-item>
        </x-ui.tabel-aksi>
    </td>
</tr>
