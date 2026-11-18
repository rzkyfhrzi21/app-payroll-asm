{{-- FRAGMEN BARIS TABEL: KASBON SUPIR. Dipakai SSR + AJAX. --}}
@php
    $rpBaris = fn ($nilai) => 'Rp '.number_format((int) $nilai, 0, ',', '.');
@endphp

<tr class="transition-colors hover:bg-surface-container-low/60">
    <td class="px-2 py-3 text-center">
        <input
            type="checkbox"
            value="{{ $baris['id'] }}"
            x-model="terpilih"
            data-centang-massal
            class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
            aria-label="Pilih kasbon {{ $baris['nama'] }}"
        >
    </td>
    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $nomor }}</td>
    <td class="px-4 py-3">
        <div class="flex flex-col">
            <span class="font-display text-headline-sm font-semibold text-slate-text-primary">{{ $baris['nama'] }}</span>
            <span class="font-sans text-label-sm text-slate-text-muted">{{ $baris['nip'] }}</span>
        </div>
    </td>
    <td class="px-4 py-3 text-slate-text-secondary">{{ $baris['tanggal'] }}</td>
    <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-text-primary">{{ $rpBaris($baris['total']) }}</td>
    <td class="px-4 py-3 text-center">
        <div class="flex flex-col items-center gap-0.5">
            <span class="font-sans text-body-sm font-bold tabular-nums text-slate-text-primary">{{ $rpBaris($baris['angsuran']) }}</span>
            <span class="font-sans text-label-sm text-slate-text-muted">{{ $baris['cicilan_selesai'] }}/{{ $baris['tenor'] }} kali</span>
        </div>
    </td>
    <td class="px-4 py-3 text-right tabular-nums text-net-pay-emerald">{{ $rpBaris($baris['terbayar']) }}</td>
    <td class="px-4 py-3 text-right font-bold tabular-nums {{ ($baris['sisa'] ?? 0) > 0 ? 'text-danger-red' : 'text-net-pay-emerald' }}">
        {{ ($baris['sisa'] ?? 0) > 0 ? $rpBaris($baris['sisa']) : 'Lunas' }}
    </td>
    <td class="px-4 py-3 text-center">
        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $baris['status'] === 'lunas' ? 'bg-net-pay-surface text-net-pay-emerald' : ($baris['status'] === 'rejected' ? 'bg-danger-surface text-danger-red' : 'bg-warning-surface text-warning-amber') }}">
            {{ $baris['status_label'] }}
        </span>
    </td>
    <td class="px-4 py-3 text-center">
        <x-ui.tabel-aksi label="Aksi Kasbon">
            <x-ui.tabel-aksi-item ikon="visibility" @click="bukaDetail(@js($baris))">
                Detail Kasbon
            </x-ui.tabel-aksi-item>
            @if (($baris['sisa'] ?? 0) > 0 && ($baris['angsuran_berikut'] ?? null) !== null)
                <x-ui.tabel-aksi-item ikon="price_check" tone="utama" @click="bukaPotong(@js($baris))">
                    Potong Cicilan Ke-{{ $baris['angsuran_berikut']['urutan'] }}
                </x-ui.tabel-aksi-item>
            @endif
            <x-ui.tabel-aksi-item ikon="edit" @click="bukaEdit(@js($baris))">
                Ubah Kasbon
            </x-ui.tabel-aksi-item>
            <div class="my-1 border-t border-card-border"></div>
            <x-ui.tabel-aksi-item ikon="delete" tone="hapus" @click="bukaHapus(@js($baris))">
                Hapus Kasbon
            </x-ui.tabel-aksi-item>
        </x-ui.tabel-aksi>
    </td>
</tr>
