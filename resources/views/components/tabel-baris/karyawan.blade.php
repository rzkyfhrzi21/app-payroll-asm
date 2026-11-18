{{-- ==============================================================================
     FRAGMEN BARIS TABEL: KARYAWAN / SUPIR
     Dipakai halaman SSR (admin/master/employees) DAN endpoint AJAX tabel.
     Variabel: $baris (array dari PayrollDataService), $nomor (int)
     ============================================================================== --}}
@php
    $rpBaris = fn ($nilai) => 'Rp '.number_format((int) $nilai, 0, ',', '.');
    $toneBadge = [
        'emerald' => 'bg-net-pay-surface text-net-pay-emerald',
        'amber' => 'bg-warning-surface text-warning-amber',
        'blue' => 'bg-primary/10 text-primary',
        'slate' => 'bg-surface-container text-slate-text-secondary',
    ];
@endphp

<tr class="group transition-colors hover:bg-surface-container-low/60">
    <td class="px-2 py-3 text-center">
        <input
            type="checkbox"
            value="{{ $baris['id'] }}"
            x-model="terpilih"
            data-centang-massal
            class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
            aria-label="Pilih supir {{ $baris['name'] }}"
        >
    </td>
    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $nomor }}</td>

    <td class="px-4 py-3">
        <div class="flex items-center gap-3">
            <img
                src="{{ $baris['foto_profil'] }}"
                alt="{{ $baris['initial'] }}"
                loading="lazy"
                onerror="window.tanganiGambarRusak(this, @js($baris['initial']))"
                @click.stop="window.bukaLightbox({ url: @js($baris['foto_profil']), judul: @js($baris['name']), caption: @js('NIP: '.$baris['nip'].' • '.$baris['plate']) })"
                title="Klik untuk memperbesar foto"
                class="h-9 w-9 shrink-0 cursor-zoom-in rounded-full object-cover ring-1 ring-card-border transition-transform hover:scale-105"
            >
            <div class="flex min-w-0 flex-col">
                <span class="truncate font-display text-headline-sm font-semibold text-slate-text-primary">{{ $baris['name'] }}</span>
                <span class="truncate font-sans text-label-sm text-slate-text-secondary">NIP: {{ $baris['nip'] }} &bull; {{ $baris['phone'] }}</span>
            </div>
        </div>
    </td>

    <td class="px-4 py-3">
        <div class="flex flex-col">
            <span class="font-display text-headline-sm font-semibold tabular-nums text-slate-text-primary">{{ $baris['plate'] }}</span>
            <span class="font-sans text-body-sm text-slate-text-secondary">{{ $baris['truck'] }}</span>
        </div>
    </td>

    <td class="px-4 py-3">
        <div class="flex flex-col">
            <div class="flex items-center gap-1 font-semibold text-slate-text-primary">
                <span class="rounded bg-surface-container-high px-1.5 py-0.5 font-sans text-label-sm font-bold text-primary">{{ $baris['bank'] }}</span>
                <span class="tabular-nums">{{ $baris['account'] }}</span>
            </div>
            @if ($baris['verified'])
                <span class="flex items-center gap-0.5 font-sans text-label-sm text-net-pay-emerald">
                    <span class="material-symbols-outlined text-[0.8125rem]">verified</span> Terverifikasi
                </span>
            @else
                <span class="flex items-center gap-0.5 font-sans text-label-sm text-warning-amber">
                    <span class="material-symbols-outlined text-[0.8125rem]">pending</span> Belum diverifikasi
                </span>
            @endif
        </div>
    </td>

    <td class="px-4 py-3 text-center">
        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-sans text-label-sm font-bold {{ $toneBadge[$baris['tone']] ?? $toneBadge['blue'] }}">
            {{ $baris['status'] }}
        </span>
    </td>

    <td class="px-4 py-3 text-right">
        <span class="font-sans text-body-sm font-bold tabular-nums {{ ($baris['sisa_kasbon'] ?? 0) > 0 ? 'text-danger-red' : 'text-slate-text-muted' }}">
            {{ $rpBaris($baris['sisa_kasbon'] ?? 0) }}
        </span>
    </td>

    <td class="px-4 py-3 text-center">
        <x-ui.tabel-aksi label="Aksi Supir">
            <x-ui.tabel-aksi-item ikon="visibility" @click="bukaDetailSupir(@js($baris))">
                Detail Supir
            </x-ui.tabel-aksi-item>
            <x-ui.tabel-aksi-item ikon="edit" tone="utama" @click="bukaEditSupir(@js($baris))">
                Edit Profil Supir
            </x-ui.tabel-aksi-item>
            <div class="my-1 border-t border-card-border"></div>
            <x-ui.tabel-aksi-item ikon="delete" tone="hapus" @click="bukaHapusSupir(@js($baris))">
                Hapus Data Supir
            </x-ui.tabel-aksi-item>
        </x-ui.tabel-aksi>
    </td>
</tr>
