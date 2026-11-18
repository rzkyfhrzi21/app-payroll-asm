<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN CETAK REKAPITULASI PENGGAJIAN PERIODE):
     - Tujuan Halaman: Menampilkan dokumen cetak Rekapitulasi Penggajian Seluruh Supir
       dalam format lembar kerja horizontal (landscape) siap audit atau daftar transfer bank.
     - Analogi Dunia Nyata: Seperti lembar rekap besar akuntansi yang ditandatangani Direktur
       sebelum uang gaji ditransfer serentak lewat payroll bank (daftar nama supir, bank,
       nomor rekening, dan take home pay bersih masing-masing supir).
     - Aturan Otorisasi (Sesuai AGENTS.md Bab 3.2):
       * Dokumen ini hanya sah dicetak jika periode sudah disetujui ('approved' / 'paid').
     - Data dari Controller:
       * $periode: Info kode periode, rentang tanggal kerja, dan tanggal persetujuan.
       * $daftarSupir: Seluruh baris supir dengan total ritase, tonase, dan gaji bersih.
       * $ringkasan: Total kas keluar perusahaan untuk periode bersangkutan.
     ============================================================================== -->
@php
    $perusahaanAktif = $dataPerusahaan ?? \App\Models\DataPerusahaan::profilAktif();
    $periodeDisetujui = in_array($periode['status_code'] ?? null, ['approved', 'paid'], true);
    $tanggalPengesahan = ($periode['pay_date'] ?? '—') !== '—'
        ? $periode['pay_date']
        : now()->translatedFormat('d F Y');

    $buatUrlMedia = static function (?string $jalur): string {
        if (empty($jalur)) {
            return '';
        }
        if (str_starts_with($jalur, 'http://') || str_starts_with($jalur, 'https://')) {
            return $jalur;
        }
        $bersih = ltrim($jalur, '/');

        return str_starts_with($bersih, 'storage/') ? asset($bersih) : asset('storage/'.$bersih);
    };

    $urlLogoPerusahaan = $buatUrlMedia($perusahaanAktif->logo_perusahaan ?? null);
    $urlTtdPemilik = $buatUrlMedia($perusahaanAktif->ttd_pemilik ?? null);
    $author = $infoAuthor ?? config('app.author', []);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="{{ $author['nama'] ?? 'Nabela Septiara' }} (NPM: {{ $author['npm'] ?? '2311050052' }})">
    <meta name="description" content="{{ $author['judul'] ?? 'Payroll System pada PT ASM Lampung' }}">
    <title>Rekapitulasi Penggajian Supir — Periode {{ $periode['code'] }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased p-4 md:p-8">
    <div class="max-w-6xl mx-auto bg-white border border-slate-200 rounded-xl shadow-sm p-6 md:p-8 space-y-6">
        {{-- Top Action Bar (No Print) --}}
        <div class="no-print flex items-center justify-between border-b border-slate-200 pb-4">
            <a
                href="{{ route('admin.reports.show', $periode['code']) }}"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-blue-600"
            >
                <span>&larr; Kembali ke Rincian Periode {{ $periode['code'] }}</span>
            </a>
            <button
                type="button"
                onclick="window.print()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-2xs"
            >
                Cetak / Simpan PDF
            </button>
        </div>

        {{-- Document Header / Kop Perusahaan --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-2 border-slate-900 pb-4">
            <div class="flex items-start gap-3.5">
                @if ($urlLogoPerusahaan !== '')
                    <img
                        src="{{ $urlLogoPerusahaan }}"
                        alt="Logo {{ $perusahaanAktif->nama_perusahaan }}"
                        class="h-14 w-14 shrink-0 rounded-lg border border-slate-200 bg-white object-contain p-1"
                    >
                @endif
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-blue-700">{{ $perusahaanAktif->nama_perusahaan }}</div>
                    <h1 class="text-xl font-extrabold text-slate-900 mt-0.5">REKAPITULASI PENGGAJIAN SUPIR — PERIODE {{ strtoupper($periode['code']) }}</h1>
                    @if (! empty($perusahaanAktif->alamat) || ! empty($perusahaanAktif->kota) || ! empty($perusahaanAktif->no_telepon) || ! empty($perusahaanAktif->email))
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            {{ collect([$perusahaanAktif->alamat, $perusahaanAktif->kota])->filter()->implode(' — ') }}
                            @if (! empty($perusahaanAktif->no_telepon))
                                &bull; Telp: {{ $perusahaanAktif->no_telepon }}
                            @endif
                            @if (! empty($perusahaanAktif->email))
                                &bull; Email: {{ $perusahaanAktif->email }}
                            @endif
                        </p>
                    @endif
                    <p class="text-xs text-slate-600 mt-1">
                        Siklus Operasional: <strong>{{ $periode['range'] }}</strong> &bull;
                        Cut-Off: <strong>{{ $periode['cut_off_lengkap'] }}</strong> &bull;
                        Status: <strong>{{ $periode['status'] }}</strong>
                    </p>
                </div>
            </div>
            <div class="text-left md:text-right text-xs text-slate-600 shrink-0">
                <div>Total Supir: <strong>{{ $ringkasan['jumlah_supir'] }} Supir</strong></div>
                <div>Total Rit / Tonase: <strong>{{ number_format($ringkasan['total_rit'], 0, ',', '.') }} Rit ({{ $ringkasan['total_tonase_label'] }} Ton)</strong></div>
                <div class="text-sm font-extrabold text-emerald-700 mt-0.5">Total Net Pay: @rupiah($ringkasan['total_gaji_bersih'])</div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse border border-slate-300">
                <thead>
                    <tr class="bg-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-700 border-b border-slate-300">
                        <th class="py-2 px-2 text-center border-r border-slate-300 w-10">No</th>
                        <th class="py-2 px-2 border-r border-slate-300">NIP &amp; Nama Supir</th>
                        <th class="py-2 px-2 border-r border-slate-300">Unit Truk</th>
                        <th class="py-2 px-2 text-right border-r border-slate-300">Rit</th>
                        <th class="py-2 px-2 text-right border-r border-slate-300">Tonase (T)</th>
                        <th class="py-2 px-2 text-right border-r border-slate-300">Angkutan Kotor</th>
                        <th class="py-2 px-2 text-right border-r border-slate-300">Uang Jalan</th>
                        <th class="py-2 px-2 text-right border-r border-slate-300">Sparepart</th>
                        <th class="py-2 px-2 text-right border-r border-slate-300">Komisi Supir</th>
                        <th class="py-2 px-2 text-right border-r border-slate-300">BPJS + Kasbon</th>
                        <th class="py-2 px-2 text-right">Gaji Bersih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($daftarSupir as $supir)
                        <tr>
                            <td class="py-2 px-2 text-center border-r border-slate-200 tabular-nums">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-2 px-2 border-r border-slate-200">
                                <div class="font-bold text-slate-900">{{ $supir['nama'] }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $supir['nik'] }}</div>
                            </td>
                            <td class="py-2 px-2 border-r border-slate-200">
                                <div class="font-semibold">{{ $supir['nopol'] }}</div>
                                <div class="text-[10px] text-slate-500">{{ $supir['model_truk'] }}</div>
                            </td>
                            <td class="py-2 px-2 text-right border-r border-slate-200 tabular-nums">{{ $supir['total_rit'] }}</td>
                            <td class="py-2 px-2 text-right border-r border-slate-200 tabular-nums">{{ $supir['total_tonase_label'] }}</td>
                            <td class="py-2 px-2 text-right border-r border-slate-200 tabular-nums">@rupiah($supir['angkutan_kotor'])</td>
                            <td class="py-2 px-2 text-right border-r border-slate-200 tabular-nums">@rupiah($supir['uang_jalan'])</td>
                            <td class="py-2 px-2 text-right border-r border-slate-200 tabular-nums">@rupiah($supir['biaya_sparepart'])</td>
                            <td class="py-2 px-2 text-right border-r border-slate-200 tabular-nums font-semibold">@rupiah($supir['gaji_komisi'])</td>
                            <td class="py-2 px-2 text-right border-r border-slate-200 tabular-nums text-rose-700">- @rupiah($supir['total_potongan'])</td>
                            <td class="py-2 px-2 text-right tabular-nums font-bold text-emerald-800">@rupiah($supir['gaji_bersih'])</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 border-t-2 border-slate-400 font-bold text-slate-900">
                        <td class="py-2.5 px-2 text-center border-r border-slate-300">TOTAL</td>
                        <td class="py-2.5 px-2 border-r border-slate-300" colspan="2">Akumulasi Periode ({{ $ringkasan['jumlah_supir'] }} Supir)</td>
                        <td class="py-2.5 px-2 text-right border-r border-slate-300 tabular-nums">{{ number_format($ringkasan['total_rit'], 0, ',', '.') }}</td>
                        <td class="py-2.5 px-2 text-right border-r border-slate-300 tabular-nums">{{ $ringkasan['total_tonase_label'] }}</td>
                        <td class="py-2.5 px-2 text-right border-r border-slate-300 tabular-nums">@rupiah($ringkasan['total_angkutan_kotor'])</td>
                        <td class="py-2.5 px-2 text-right border-r border-slate-300 tabular-nums">@rupiah($ringkasan['total_uang_jalan'])</td>
                        <td class="py-2.5 px-2 text-right border-r border-slate-300 tabular-nums">@rupiah($ringkasan['total_sparepart'])</td>
                        <td class="py-2.5 px-2 text-right border-r border-slate-300 tabular-nums">@rupiah($ringkasan['total_komisi'])</td>
                        <td class="py-2.5 px-2 text-right border-r border-slate-300 tabular-nums text-rose-700">- @rupiah($ringkasan['total_potongan'])</td>
                        <td class="py-2.5 px-2 text-right tabular-nums text-emerald-800">@rupiah($ringkasan['total_gaji_bersih'])</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Blok Pengesahan & Tanda Tangan Pemilik --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-6 border-t border-slate-200 pt-5 text-xs">
            <div class="space-y-1 text-slate-500 max-w-md">
                @if (! empty($perusahaanAktif->nama_bank) || ! empty($perusahaanAktif->no_rekening))
                    <div>
                        <span class="font-semibold text-slate-700">Rekening Perusahaan:</span>
                        <span class="font-mono text-slate-800">
                            {{ collect([$perusahaanAktif->nama_bank, $perusahaanAktif->no_rekening])->filter()->implode(' — ') }}
                        </span>
                    </div>
                @endif
                <p>Rekapitulasi resmi penggajian {{ $perusahaanAktif->nama_perusahaan }}.</p>
            </div>

            <div class="min-w-[220px] text-center self-end">
                <p class="text-slate-600">{{ $perusahaanAktif->kota ?: 'PALEMBANG' }}, {{ $tanggalPengesahan }}</p>
                <p class="text-slate-500">Mengetahui / Mengesahkan,</p>

                <div class="my-2 flex h-16 items-center justify-center">
                    @if ($urlTtdPemilik !== '' && $periodeDisetujui)
                        <img
                            src="{{ $urlTtdPemilik }}"
                            alt="Tanda Tangan {{ $perusahaanAktif->nama_pemilik }}"
                            class="max-h-16 w-auto object-contain"
                        >
                    @elseif (! $periodeDisetujui)
                        <span class="rounded border border-dashed border-amber-300 bg-amber-50 px-2.5 py-1 text-[10px] font-medium text-amber-700">
                            Menunggu ACC Pimpinan
                        </span>
                    @endif
                </div>

                <div class="border-t border-slate-400 pt-1 font-bold uppercase text-slate-900">
                    {{ $perusahaanAktif->nama_pemilik }}
                </div>
                @if (! empty($perusahaanAktif->jabatan_pemilik))
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                        {{ $perusahaanAktif->jabatan_pemilik }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer Atribusi Sistem / Author Cetak --}}
        <div class="border-t border-slate-200 pt-3 text-center text-[10px] text-slate-400">
            {{ $author['judul'] ?? 'Payroll System pada PT ASM Lampung' }} &bull; Pengembang: {{ $author['nama'] ?? 'Nabela Septiara' }} (NPM: {{ $author['npm'] ?? '2311050052' }})
        </div>
    </div>
</body>
</html>
