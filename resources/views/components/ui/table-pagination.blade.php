<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN PAGINASI TABEL DATA):
     - Tujuan Komponen: Membagi daftar data yang panjang menjadi lembaran-lembaran halaman
       (halaman 1, 2, 3...) agar tidak membuat layar lambat saat memuat ribuan baris data.
     - Analogi Dunia Nyata: Seperti membuka buku tebal catatan ekspedisi batubara lembar demi lembar;
       ada nomor halaman di bagian bawah dan pilihan mau lihat 25, 50, atau 100 baris per lembar.
     - Data dari Controller / Props:
       * $paginator: Objek Paginator Laravel (LengthAwarePaginator) pembawa data dan info halaman.
       * $label: Nama entitas yang dihitung (misal: "ritase", "karyawan", "transaksi").
       * $perPage: Jumlah baris data yang sedang ditampilkan per halaman (default: 25).
     - Fitur Penting:
       * Menyertakan query parameter filter lama ($paginator->appends(request()->query()))
         sehingga saat ganti nomor halaman, saringan tanggal/supir tidak hilang.
     ============================================================================== -->
@props([
    'paginator',
    'label' => 'data',
    'perPage' => 25,
])

@php
    // Pastikan parameter filter yang aktif ikut terbawa di tautan halaman.
    $paginator->appends(request()->query());

    $total = (int) $paginator->total();
    $dari = (int) ($paginator->firstItem() ?? 0);
    $sampai = (int) ($paginator->lastItem() ?? 0);
    $halamanAktif = (int) $paginator->currentPage();
    $totalHalaman = max(1, (int) $paginator->lastPage());

    $tautanPerHalaman = fn (int $nilai): string => request()->fullUrlWithQuery([
        'per_page' => $nilai,
        'page' => 1,
    ]);

    $nomorHalaman = [];
    for ($halaman = 1; $halaman <= $totalHalaman; $halaman++) {
        if ($totalHalaman <= 7 || $halaman === 1 || $halaman === $totalHalaman || abs($halaman - $halamanAktif) <= 1) {
            $nomorHalaman[] = $halaman;
        }
    }

    $kelasTombolDasar = 'inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg px-2.5 font-sans text-label-md font-bold transition-colors';
    $kelasTombolAktif = 'bg-primary text-on-primary shadow-sm';
    $kelasTombolBiasa = 'border border-card-border bg-card-bg text-slate-text-primary hover:bg-surface-container-low';
    $kelasTombolNonaktif = 'border border-card-border bg-card-bg text-slate-text-primary opacity-40 cursor-not-allowed';
@endphp

<div class="flex flex-col items-center justify-between gap-3 border-t border-card-border bg-surface/60 px-4 py-3 sm:flex-row">
    <div class="flex flex-wrap items-center gap-3 font-sans text-body-sm text-slate-text-secondary">
        <span>
            Menampilkan
            <strong class="text-slate-text-primary">{{ $dari }}–{{ $sampai }}</strong>
            dari
            <strong class="text-slate-text-primary">{{ number_format($total, 0, ',', '.') }}</strong>
            {{ $label }}
        </span>
        <div class="flex items-center gap-1.5">
            <span class="text-slate-text-muted">Baris:</span>
            <select
                onchange="if (this.value) { window.location.href = this.value; }"
                data-no-select2
                aria-label="Jumlah baris per halaman"
                class="rounded-lg border border-card-border bg-card-bg px-2 py-1 font-sans text-label-md font-semibold text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
            >
                @foreach ([25, 50, 100] as $opsiPerHalaman)
                    <option value="{{ $tautanPerHalaman($opsiPerHalaman) }}" @selected($perPage === $opsiPerHalaman)>{{ $opsiPerHalaman }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($totalHalaman > 1)
        <nav class="flex items-center gap-1.5" aria-label="Navigasi halaman {{ $label }}">
            @if ($halamanAktif <= 1)
                <span class="{{ $kelasTombolDasar }} {{ $kelasTombolNonaktif }}" aria-disabled="true">
                    <span class="material-symbols-outlined text-[1rem]">chevron_left</span>
                </span>
            @else
                <a href="{{ $paginator->url($halamanAktif - 1) }}" class="{{ $kelasTombolDasar }} {{ $kelasTombolBiasa }}" rel="prev" aria-label="Halaman sebelumnya">
                    <span class="material-symbols-outlined text-[1rem]">chevron_left</span>
                </a>
            @endif

            @php $sebelumnya = 0; @endphp
            @foreach ($nomorHalaman as $nomor)
                @if ($sebelumnya !== 0 && $nomor - $sebelumnya > 1)
                    <span class="px-1 text-slate-text-muted">…</span>
                @endif

                @if ($nomor === $halamanAktif)
                    <span class="{{ $kelasTombolDasar }} {{ $kelasTombolAktif }}" aria-current="page">{{ $nomor }}</span>
                @else
                    <a href="{{ $paginator->url($nomor) }}" class="{{ $kelasTombolDasar }} {{ $kelasTombolBiasa }}" aria-label="Halaman {{ $nomor }}">{{ $nomor }}</a>
                @endif

                @php $sebelumnya = $nomor; @endphp
            @endforeach

            @if ($halamanAktif >= $totalHalaman)
                <span class="{{ $kelasTombolDasar }} {{ $kelasTombolNonaktif }}" aria-disabled="true">
                    <span class="material-symbols-outlined text-[1rem]">chevron_right</span>
                </span>
            @else
                <a href="{{ $paginator->url($halamanAktif + 1) }}" class="{{ $kelasTombolDasar }} {{ $kelasTombolBiasa }}" rel="next" aria-label="Halaman berikutnya">
                    <span class="material-symbols-outlined text-[1rem]">chevron_right</span>
                </a>
            @endif
        </nav>
    @endif
</div>
