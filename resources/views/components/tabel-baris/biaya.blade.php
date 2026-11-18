{{-- FRAGMEN BARIS TABEL: BIAYA OPERASIONAL. Dipakai SSR + AJAX. --}}
@php
    $rpBaris = fn ($nilai) => 'Rp '.number_format((int) $nilai, 0, ',', '.');
@endphp

<tr class="transition-colors hover:bg-surface-container-low/70">
    <td class="px-2 py-3 text-center">
        <input
            type="checkbox"
            value="{{ $baris['id'] }}"
            x-model="terpilih"
            data-centang-massal
            class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
            aria-label="Pilih pengeluaran {{ $baris['nomor_nota'] ?: $baris['kategori_label'] }}"
        >
    </td>
    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $nomor }}</td>
    <td class="px-4 py-3 font-semibold">{{ $baris['tanggal'] }}</td>
    <td class="px-4 py-3">
        <div class="flex flex-col">
            <span class="font-bold">{{ $baris['plate'] }}</span>
            <span class="font-sans text-label-sm text-slate-text-muted">{{ $baris['truck'] }}</span>
        </div>
    </td>
    <td class="px-4 py-3">
        <span @class([
            'inline-block rounded px-2 py-0.5 font-sans text-label-sm font-bold',
            'bg-blue-50 text-blue-700' => $baris['kategori'] === 'sparepart',
            'bg-amber-50 text-amber-700' => $baris['kategori'] === 'bbm',
            'bg-slate-100 text-slate-700' => $baris['kategori'] === 'tol',
            'bg-purple-50 text-purple-700' => $baris['kategori'] === 'retribusi',
            'bg-green-50 text-green-700' => $baris['kategori'] === 'servis',
            'bg-surface-container text-slate-text-secondary' => $baris['kategori'] === 'lain_lain',
        ])>{{ $baris['kategori_label'] }}</span>
    </td>
    <td class="px-4 py-3 font-sans text-label-sm text-slate-text-secondary">
        <div class="flex items-center gap-1.5">
            <span>{{ $baris['nomor_nota'] ?: '—' }}</span>
            <button
                type="button"
                @click.stop="window.bukaLightbox({ url: @js($baris['foto_nota']), judul: @js('Bukti Nota '.($baris['nomor_nota'] ?: $baris['kategori_label'])), caption: @js($baris['plate'].' • '.$baris['tanggal']) })"
                title="Pratinjau Bukti Nota"
                class="inline-flex h-6 w-6 items-center justify-center rounded bg-surface-container-low text-primary transition-colors hover:bg-primary/10"
            >
                <span class="material-symbols-outlined text-[0.9375rem]">receipt_long</span>
            </button>
        </div>
    </td>
    <td class="px-4 py-3 text-right font-bold tabular-nums text-primary">{{ $rpBaris($baris['nominal']) }}</td>
    <td class="max-w-[12rem] truncate px-4 py-3 font-sans text-body-sm text-slate-text-secondary">{{ $baris['keterangan'] ?: '—' }}</td>
    <td class="px-4 py-3 text-center">
        <span @class([
            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold',
            'bg-surface-container-high text-slate-text-secondary' => $baris['status'] === 'draft',
            'bg-warning-surface text-warning-amber' => $baris['status'] === 'diajukan',
            'bg-net-pay-surface text-net-pay-emerald' => $baris['status'] === 'disetujui',
            'bg-danger-surface text-danger-red' => $baris['status'] === 'ditolak',
        ])>{{ $baris['status_label'] }}</span>
    </td>
    <td class="px-4 py-3 text-center">
        <x-ui.tabel-aksi label="Aksi Pengeluaran">
            <x-ui.tabel-aksi-item ikon="visibility" @click="bukaDetail(@js($baris))">
                Detail Pengeluaran
            </x-ui.tabel-aksi-item>
            <x-ui.tabel-aksi-item ikon="edit" tone="utama" @click="bukaEdit(@js($baris))">
                Edit Pengeluaran
            </x-ui.tabel-aksi-item>
            <div class="my-1 border-t border-card-border"></div>
            @if ($baris['status'] === 'disetujui')
                <button
                    type="button"
                    role="menuitem"
                    disabled
                    title="Sudah disetujui, tidak dapat dihapus"
                    class="flex w-full cursor-not-allowed items-center gap-2.5 rounded-lg px-3 py-2 font-sans text-body-sm font-medium text-slate-text-muted opacity-50"
                >
                    <span class="material-symbols-outlined text-[1.25rem]">delete</span>
                    <span>Hapus (Terkunci)</span>
                </button>
            @else
                <x-ui.tabel-aksi-item ikon="delete" tone="hapus" @click="bukaHapus(@js($baris))">
                    Hapus Pengeluaran
                </x-ui.tabel-aksi-item>
            @endif
        </x-ui.tabel-aksi>
    </td>
</tr>
