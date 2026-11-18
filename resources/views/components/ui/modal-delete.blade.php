<!-- ======================================================
     KOMPONEN UI: KONFIRMASI HAPUS DATA & INTEGRITAS RELASI (MODAL DELETE)
     
     Analogi Sederhana:
     Komponen ini ibarat "Pintu Palang Pengaman Penghapusan Dokumen Kantor".
     Sebelum data master (seperti data supir atau nomor truk) dihapus,
     komponen ini mengecek apakah supir/truk tersebut masih memiliki transaksi berjalan:
     - Jika data masih terikat ke surat jalan (ritase) atau kasbon, tombol Hapus otomatis
       TERKUNCI dan muncul penjelasan alasan hukum auditnya.
     - Disediakan alternatif tombol "Nonaktifkan" agar transaksi lama tetap aman.
     - Jika data benar-benar bebas relasi, pengguna wajib mencentang persetujuan
       sebelum tombol Hapus Permanen bisa diklik.
====================================================== -->
@props([
    'name',
    'title' => 'Hapus Data?',
    'entity' => '',
    'actionBinding',
    'terkunciBinding' => 'false',
    'alasanBinding' => "''",
    'ringkasanBinding' => '[]',
    'centang',
    'statusBinding' => null,
    'statusActionBinding' => null,
    'labelStatus' => 'Nonaktifkan',
    'zIndex' => 50,
])

@php
    $terkunci = $terkunciBinding;
    $centangNama = $centang;
    $punyaStatus = $statusBinding !== null && $statusActionBinding !== null;
@endphp

<x-ui.modal-shell
    :name="$name"
    title="{{ $title }}"
    subtitle="Penghapusan data master akan memengaruhi integritas jejak penggajian."
    icon="warning"
    accent="danger"
    max-width="xl"
    :z-index="$zIndex"
>
    <div class="flex flex-col gap-space-md px-space-lg pb-space-md">
        <div class="rounded-xl bg-surface-container-low p-space-md">
            <p class="font-sans text-body-md font-medium text-slate-text-primary">
                Data {{ $entity }} akan dihapus permanen dan tidak dapat dikembalikan.
            </p>
        </div>

        {{-- Blok validasi: hanya muncul bila data masih terikat --}}
        <div x-show="{{ $terkunci }}" x-cloak class="flex flex-col gap-space-sm rounded-xl bg-danger-surface/60 p-space-md">
            <div class="flex items-start gap-space-sm">
                <span class="material-symbols-outlined text-[1.5rem] text-danger-red">gavel</span>
                <div class="flex flex-col">
                    <span class="font-sans text-body-sm font-bold text-danger-red">Validasi Keterikatan Data</span>
                    <p class="mt-1 font-sans text-body-md font-semibold text-slate-text-primary" x-text="{{ $alasanBinding }}"></p>
                    <p class="mt-1 font-sans text-body-sm text-slate-text-secondary">
                        Aturan kepatuhan audit mencegah penghapusan data yang sudah punya jejak transaksi.
                        @if ($punyaStatus)
                            Gunakan tombol <strong>{{ $labelStatus }}</strong> agar data tidak bisa dipakai lagi tanpa menghapus riwayat.
                        @endif
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-space-sm pt-1">
                <template x-for="(baris, urutan) in {{ $ringkasanBinding }}" :key="urutan">
                    <div class="rounded-lg bg-card-bg p-2.5 shadow-sm">
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted" x-text="baris.label"></span>
                        <div class="font-financial-table text-body-lg font-bold tabular-nums text-slate-text-primary" x-text="baris.nilai"></div>
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-xl bg-surface-container-low p-space-md" :class="{{ $terkunci }} ? 'opacity-60' : ''">
            <label class="flex items-center gap-space-md" :class="{{ $terkunci }} ? 'cursor-not-allowed' : 'cursor-pointer'">
                <input
                    type="checkbox"
                    x-model="{{ $centangNama }}"
                    :disabled="{{ $terkunci }}"
                    class="h-4 w-4 rounded border-card-border text-danger-red focus:ring-danger-red disabled:cursor-not-allowed"
                >
                <span class="font-sans text-body-sm text-slate-text-secondary">
                    Saya memahami data akan dihapus permanen.
                    <span x-show="{{ $terkunci }}" x-cloak class="font-sans text-label-sm italic text-slate-text-muted">(hanya aktif bila belum ada transaksi)</span>
                </span>
            </label>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-space-sm bg-surface-container-low px-space-lg py-space-md">
        <button
            type="button"
            class="rounded-lg bg-card-bg px-space-lg py-2 font-sans text-label-md font-semibold text-slate-text-primary shadow-sm transition-colors hover:bg-surface-container"
            @click="{{ $name }} = false"
        >
            Kembali
        </button>

        <div class="flex flex-wrap items-center gap-space-sm">
            {{-- Alternatif: nonaktifkan tanpa menghapus riwayat --}}
            @if ($punyaStatus)
                <form method="POST" x-show="{{ $terkunci }}" x-cloak :action="{{ $statusActionBinding }}">
                    @csrf
                    @method('PATCH')
                    <button
                        type="submit"
                        class="flex items-center gap-1.5 rounded-lg bg-surface-container px-space-md py-2 font-sans text-label-md font-semibold text-primary transition-all hover:bg-primary-container hover:text-on-primary"
                    >
                        <span class="material-symbols-outlined text-[1.125rem]">pause_circle</span>
                        <span x-text="{{ $statusBinding }} === 'aktif' ? '{{ $labelStatus }}' : 'Aktifkan Kembali'">{{ $labelStatus }}</span>
                    </button>
                </form>
            @endif

            <form method="POST" :action="{{ $actionBinding }}">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    :disabled="{{ $terkunci }} || ! {{ $centangNama }}"
                    class="flex items-center gap-1.5 rounded-lg px-space-lg py-2 font-sans text-label-md font-semibold transition-all"
                    :class="({{ $terkunci }} || ! {{ $centangNama }})
                        ? 'cursor-not-allowed bg-danger-surface text-danger-red/60'
                        : 'bg-danger-red text-white shadow-md hover:brightness-110 active:scale-95'"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">delete_forever</span>
                    <span x-text="{{ $terkunci }} ? 'Hapus Data (Terkunci)' : 'Hapus Permanen'">Hapus Permanen</span>
                </button>
            </form>
        </div>
    </div>
</x-ui.modal-shell>
