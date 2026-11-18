<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN GRAFIK GARIS TREN SVG):
     - Tujuan Komponen: Menggambar grafik garis (Line Chart) murni menggunakan tag SVG bawaan browser,
       tanpa mengunduh library luar seperti Chart.js apalagi React (sesuai aturan ketat AGENTS.md).
     - Analogi Dunia Nyata: Seperti papan grafik tempel di dinding kantor logistik: penggaris ditarik
       dari bulan ke bulan untuk melihat apakah pengiriman batubara sedang meroket atau merosot.
     - Data dari Controller / Props:
       * $judul: Nama grafik (misal: "Tren Ritase Bulanan", "Pengeluaran Operasional").
       * $keterangan: Subjudul penjelas di bawah judul.
       * $data: Array daftar titik koordinat [['label' => 'Jan', 'nilai' => 12000000], ...].
       * $warna: Kode warna heksadesimal garis grafik (default warna ungu/indigo).
       * $formatNilai: Fungsi callback untuk memformat angka (rupiah atau jumlah ritase).
     - Alur Logika Matematika:
       1. Menghitung nilai tertinggi ($nilaiMaks) sebagai patokan atap grafik (100%).
       2. Memetakan setiap data ke titik pixel X (jarak mendatar) dan Y (ketinggian terbalik SVG).
       3. Menggambar poligon arsiran gradien di bawah garis dan polyline untuk garis tren.
     ============================================================================== -->
@props([
    'judul' => 'Tren Bulanan',
    'keterangan' => null,
    'data' => [],
    'labelNilai' => 'Nilai',
    'warna' => '#4F46E5',
    'formatNilai' => null,
    'tinggiSvg' => 160,
])

@php
    // Setiap titik: ['label' => 'Jan', 'nilai' => 123456, 'keterangan' => 'opsional']
    $titikData = collect($data)->values();

    $nilaiMaks = max(1, (int) $titikData->max('nilai'));
    $jumlahTitik = max(1, $titikData->count());
    $lebarViewBox = 700;
    $paddingKiri = 30;
    $paddingKanan = 20;
    $lebarGaris = $lebarViewBox - $paddingKiri - $paddingKanan;
    $yAtas = 20;
    $yBawah = $tinggiSvg - 20;

    $langkah = $jumlahTitik > 1 ? $lebarGaris / ($jumlahTitik - 1) : 0;

    $titik = $titikData->map(function (array $item, int $indeks) use ($nilaiMaks, $langkah, $paddingKiri, $yAtas, $yBawah) {
        $rasio = ((int) $item['nilai']) / $nilaiMaks;
        $x = (int) round($paddingKiri + ($indeks * $langkah));
        $y = (int) round($yBawah - ($rasio * ($yBawah - $yAtas)));

        return [
            'x' => $x,
            'y' => $y,
            'label' => (string) ($item['label'] ?? ''),
            'nilai' => (int) ($item['nilai'] ?? 0),
            'keterangan' => (string) ($item['keterangan'] ?? ''),
        ];
    });

    $garis = $titik->map(fn (array $t) => $t['x'].','.$t['y'])->implode(' ');
    $idGradien = 'gradien-'.substr(md5($judul.$garis), 0, 8);

    $format = $formatNilai ?? static fn (int $nilai): string => number_format($nilai, 0, ',', '.');
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col rounded-xl bg-card-bg p-space-lg shadow-card']) }}>
    <div class="flex items-start justify-between gap-2">
        <div class="flex flex-col">
            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">{{ $judul }}</h3>
            @if ($keterangan)
                <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">{{ $keterangan }}</p>
            @endif
        </div>
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-surface-container text-primary">
            <span class="material-symbols-outlined text-[1.125rem]">show_chart</span>
        </span>
    </div>

    @if ($titik->isEmpty())
        <p class="py-10 text-center font-sans text-body-sm text-slate-text-muted">Belum ada data untuk ditampilkan.</p>
    @else
        <div class="mt-4 flex flex-col">
            <svg
                class="w-full overflow-visible"
                viewBox="0 0 {{ $lebarViewBox }} {{ $tinggiSvg }}"
                preserveAspectRatio="none"
                style="height: {{ $tinggiSvg }}px"
                role="img"
                aria-label="Grafik {{ $judul }}"
            >
                <defs>
                    <linearGradient id="{{ $idGradien }}" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="{{ $warna }}" stop-opacity="0.22"></stop>
                        <stop offset="100%" stop-color="{{ $warna }}" stop-opacity="0"></stop>
                    </linearGradient>
                </defs>

                @foreach ([0.25, 0.5, 0.75] as $rasioGaris)
                    @php $yGaris = (int) round($yAtas + ($yBawah - $yAtas) * $rasioGaris); @endphp
                    <line x1="{{ $paddingKiri }}" x2="{{ $lebarViewBox - $paddingKanan }}" y1="{{ $yGaris }}" y2="{{ $yGaris }}"
                        stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4"></line>
                @endforeach
                <line x1="{{ $paddingKiri }}" x2="{{ $lebarViewBox - $paddingKanan }}" y1="{{ $yBawah }}" y2="{{ $yBawah }}"
                    stroke="#CBD5E1" stroke-width="1.5"></line>

                <polygon
                    fill="url(#{{ $idGradien }})"
                    points="{{ $garis }} {{ $titik->last()['x'] }},{{ $yBawah }} {{ $titik->first()['x'] }},{{ $yBawah }}"
                ></polygon>

                <polyline
                    fill="none"
                    points="{{ $garis }}"
                    stroke="{{ $warna }}"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                ></polyline>

                @foreach ($titik as $t)
                    <circle cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="{{ $loop->last ? 5.5 : 4 }}"
                        fill="#FFFFFF" stroke="{{ $warna }}" stroke-width="{{ $loop->last ? 3 : 2.2 }}">
                        <title>{{ $t['label'] }}: {{ $format($t['nilai']) }}</title>
                    </circle>
                @endforeach
            </svg>

            <div class="mt-2 grid gap-1 text-center" style="grid-template-columns: repeat({{ $jumlahTitik }}, minmax(0, 1fr));">
                @foreach ($titik as $t)
                    <div class="flex min-w-0 flex-col items-center {{ $loop->last ? 'rounded-md bg-surface-container-low py-0.5' : '' }}">
                        <span class="font-sans text-[0.6875rem] {{ $loop->last ? 'font-bold text-primary' : 'text-slate-text-muted' }}">{{ $t['label'] }}</span>
                        <span class="truncate font-sans text-[0.625rem] tabular-nums {{ $loop->last ? 'font-bold text-slate-text-primary' : 'text-slate-text-secondary' }}">{{ $format($t['nilai']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
