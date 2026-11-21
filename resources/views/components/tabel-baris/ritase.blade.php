{{-- ==============================================================================
     FRAGMEN BARIS TABEL: SURAT JALAN / RITASE
     Dipakai DUA tempat sekaligus agar markup tidak pernah terduplikasi:
       1. Halaman SSR  : admin/operational/trips/index.blade.php (melalui @forelse)
       2. Endpoint AJAX: InteraksiTabelPeranController (render per baris)
     Variabel wajib: $baris (array baris), $nomor (int), waktu dibutuhkan: $statusOptions
     ============================================================================== --}}
@php
    $rp = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
@endphp

<tr class="transition-colors hover:bg-surface-container-low/70">
    <td class="px-2 py-3 text-center">
        <input
            type="checkbox"
            value="{{ $baris['id'] }}"
            x-model="terpilih"
            data-centang-massal
            class="h-4 w-4 cursor-pointer rounded border-card-border text-primary focus:ring-2 focus:ring-primary"
            aria-label="Pilih surat jalan {{ $baris['sj'] }}"
        >
    </td>
    <td class="px-4 py-3 text-center tabular-nums text-slate-text-muted">{{ $nomor }}</td>
    <td class="px-4 py-3">
        <div class="flex flex-col">
            <span class="font-semibold">{{ $baris['date'] }}</span>
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="font-sans text-label-sm">{{ $baris['sj'] }}</span>
                @if (($baris['jumlah_foto'] ?? 0) > 0)
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-primary/10 px-1.5 py-0.5 font-sans text-[0.625rem] font-bold text-primary">
                        {{ $baris['jumlah_foto'] }} Foto Bukti
                    </span>
                @endif
                <button
                    type="button"
                    @click.stop="bukaModalSuratJalan(@js($baris))"
                    title="Pratinjau Foto Surat Jalan"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-primary/20 bg-primary/10 text-primary transition-colors hover:bg-primary hover:text-on-primary"
                >
                    <span class="material-symbols-outlined text-[0.95rem]">description</span>
                </button>
            </div>
        </div>
    </td>
    <td class="px-4 py-3">
        <div class="flex flex-col">
            <span class="font-bold">{{ $baris['driver'] }}</span>
            <span class="font-sans text-label-sm">{{ $baris['plate'] }} &bull; {{ $baris['truck'] }}</span>
            @if (! empty($baris['nama_penerima']))
                <span class="mt-0.5 font-sans text-label-sm font-medium text-net-pay-emerald">Penerima: {{ $baris['nama_penerima'] }}</span>
            @endif
        </div>
    </td>
    <td class="px-3 py-3 text-center">
        <span class="inline-block rounded bg-surface-container-high px-2 py-0.5 font-sans text-label-sm font-bold text-banner-deep">{{ $baris['material'] }}</span>
    </td>
    <td class="px-3 py-3 text-center font-bold">{{ $baris['rit'] }} Rit</td>
    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $baris['tonnage'] }} Ton</td>
    <td class="px-4 py-3 text-right tabular-nums">{{ $rp($baris['rate']) }}</td>
    <td class="px-4 py-3 text-right font-bold tabular-nums text-primary">{{ $rp($baris['total']) }}</td>
    <td class="px-4 py-3 text-right font-bold tabular-nums text-net-pay-emerald">{{ $rp($baris['trip_money']) }}</td>
    <td class="px-4 py-3 text-center">
        <span class="@if (($baris['route'] ?? '') === 'MIP-36') bg-secondary-fixed text-on-secondary-fixed @else bg-primary-fixed text-on-primary-fixed @endif inline-block rounded px-2 py-0.5 font-sans text-label-sm font-semibold">{{ $baris['route'] }}</span>
    </td>
    <td class="px-4 py-3 text-center">
        <span @class([
            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-sans text-label-sm font-bold',
            'bg-net-pay-surface text-net-pay-emerald' => $baris['status'] === 'terverifikasi',
            'bg-indigo-50 text-indigo-700' => $baris['status'] === 'sedang_dikirim',
            'bg-sky-50 text-sky-700' => $baris['status'] === 'menunggu_verifikasi',
            'bg-warning-surface text-warning-amber' => $baris['status'] === 'terbit',
            'bg-slate-100 text-slate-700' => $baris['status'] === 'draft',
            'bg-danger-surface text-danger-red' => $baris['status'] === 'dibatalkan',
        ])>
            <span @class([
                'h-1.5 w-1.5 rounded-full',
                'bg-net-pay-emerald' => $baris['status'] === 'terverifikasi',
                'bg-indigo-500' => $baris['status'] === 'sedang_dikirim',
                'bg-sky-500' => $baris['status'] === 'menunggu_verifikasi',
                'bg-warning-amber' => $baris['status'] === 'terbit',
                'bg-slate-400' => $baris['status'] === 'draft',
                'bg-danger-red' => $baris['status'] === 'dibatalkan',
            ])></span>
            {{ $baris['status_label'] }}
        </span>
    </td>
    <td class="px-4 py-3 text-center">
        <x-ui.tabel-aksi label="Aksi Surat Jalan">
            <x-ui.tabel-aksi-item ikon="visibility" @click="bukaDetailRitase({!! \Illuminate\Support\Js::from($baris) !!})">
                Detail Surat Jalan
            </x-ui.tabel-aksi-item>
            <a
                href="{{ route('admin.operational.trips.print', $baris['id']) }}"
                target="_blank"
                rel="noopener"
                role="menuitem"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 font-sans text-body-sm font-medium text-banner-indigo transition-colors hover:bg-surface-container-low"
            >
                <span class="material-symbols-outlined text-[1.25rem]">print</span>
                <span>Cetak Surat Jalan</span>
            </a>
            <x-ui.tabel-aksi-item ikon="edit" tone="utama" @click="openEdit({!! \Illuminate\Support\Js::from($baris) !!})">
                Edit Surat Jalan
            </x-ui.tabel-aksi-item>
            <div class="my-1 border-t border-card-border"></div>
            <x-ui.tabel-aksi-item ikon="delete" tone="hapus" @click="bukaHapusRitase({!! \Illuminate\Support\Js::from($baris) !!})">
                Hapus Surat Jalan
            </x-ui.tabel-aksi-item>
        </x-ui.tabel-aksi>
    </td>
</tr>
