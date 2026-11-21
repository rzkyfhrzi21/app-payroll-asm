<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN CETAK SLIP GAJI FISIK SUPIR):
     - Tujuan Halaman: Menampilkan lembar fisik Slip Gaji resmi supir siap cetak (Ctrl+P / Printer).
     - Analogi Dunia Nyata: Seperti amplop slip gaji resmi berlogo perusahaan: berisi rincian
       hasil angkutan ritase batubara, bagi hasil komisi supir, uang jalan, potongan kasbon,
       serta stempel dan tanda tangan pemilik perusahaan logistik.
     - Standar Aturan Desain (Sesuai AGENTS.md Bab 3.2):
       * DILARANG menampilkan teks persentase komisi ("Komisi 10%"); gunakan label netral
         seperti "Komisi Supir" atau "Bagi Hasil Komisi Supir".
       * Seluruh nominal dicetak dalam Rupiah bulat tanpa desimal (BIGINT) seperti `Rp 4.521.485`.
       * Membaca logo dan tanda tangan pimpinan dari data perusahaan singleton (`DataPerusahaan`).
     - Data dari Controller:
       * $supir: Detail supir (nama, NIP, nomor plat unit truk, nomor rekening).
       * $rincianGaji: Komponen gaji pokok, komisi, uang jalan, kasbon, dan gaji bersih.
       * $periode: Info kode periode dan tanggal persetujuan gaji.
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
    <title>Slip Gaji {{ $supir['nama'] }} — {{ $periode['code'] }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased p-4 md:p-8">
    <div class="max-w-4xl mx-auto bg-white border border-slate-200 rounded-xl shadow-sm p-6 md:p-8 space-y-6">
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
                Cetak Slip Gaji (PDF)
            </button>
        </div>

        {{-- Header Slip / Kop Perusahaan --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b-2 border-slate-900 pb-4">
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
                    <h1 class="text-xl font-extrabold text-slate-900 mt-0.5">SLIP GAJI &amp; REKAP PENGANTARAN SUPIR</h1>
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
                    <p class="text-xs text-slate-600 mt-0.5">
                        Periode: <strong>{{ $periode['code'] }} ({{ $periode['range'] }})</strong>
                    </p>
                </div>
            </div>
            <div class="text-left sm:text-right font-mono text-xs shrink-0">
                <div class="font-bold text-slate-900">{{ $supir['kode_slip'] }}</div>
                <div class="text-slate-500">Status: {{ $periode['status'] }}</div>
            </div>
        </div>

        {{-- Identitas Supir & Armada --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 border border-slate-200 rounded-lg p-4 text-xs">
            <div>
                <span class="text-slate-500 block">Nama Supir</span>
                <strong class="text-slate-900 text-sm">{{ $supir['nama'] }}</strong>
                <div class="font-mono text-slate-500 text-[11px]">{{ $supir['nik'] }}</div>
            </div>
            <div>
                <span class="text-slate-500 block">Unit Armada</span>
                <strong class="text-slate-900 text-sm">{{ $supir['nopol'] }}</strong>
                <div class="text-slate-500 text-[11px]">{{ $supir['model_truk'] }}</div>
            </div>
            <div>
                <span class="text-slate-500 block">Total Pengantaran &amp; Tonase</span>
                <strong class="text-slate-900 text-sm">{{ $supir['total_rit'] }} Trip / {{ $supir['total_tonase_label'] }} Ton</strong>
                <div class="text-slate-500 text-[11px]">Rata-rata {{ $supir['rata_ton_per_rit'] }} T/Trip</div>
            </div>
            <div>
                <span class="text-slate-500 block">Rekening Transfer</span>
                <strong class="text-slate-900 text-sm">{{ $supir['bank'] }}</strong>
                <div class="font-mono text-slate-600 text-[11px]">{{ $supir['rekening'] }}</div>
            </div>
        </div>

        {{-- Rincian Perhitungan Gaji --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="border border-slate-200 rounded-lg p-4 space-y-2">
                <div class="font-bold uppercase tracking-wider text-slate-500 text-[11px] border-b border-slate-100 pb-1.5">1. Perhitungan Hasil Bersih Armada</div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-600">Total Angkutan Kotor</span>
                    <span class="font-semibold tabular-nums">@rupiah($supir['angkutan_kotor'])</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-600">Potongan Operasional</span>
                    <span class="tabular-nums text-rose-600">- @rupiah($supir['potongan_operasional'])</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-600">Total Uang Jalan</span>
                    <span class="tabular-nums text-amber-700">- @rupiah($supir['uang_jalan'])</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-600">Biaya Sparepart / Perbaikan</span>
                    <span class="tabular-nums text-rose-600">- @rupiah($supir['biaya_sparepart'])</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-200 font-bold text-slate-900">
                    <span>Hasil Bersih Armada</span>
                    <span class="tabular-nums">@rupiah($supir['hasil_bersih_armada'])</span>
                </div>
            </div>

            <div class="border border-slate-200 rounded-lg p-4 space-y-2 flex flex-col justify-between">
                <div class="space-y-2">
                    <div class="font-bold uppercase tracking-wider text-slate-500 text-[11px] border-b border-slate-100 pb-1.5">2. Pendapatan &amp; Potongan Supir</div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-600">Komisi Supir ({{ $supir['persen_komisi_label'] }})</span>
                        <span class="font-bold tabular-nums text-slate-900">@rupiah($supir['gaji_komisi'])</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-600">Potongan BPJS Ketenagakerjaan</span>
                        <span class="tabular-nums text-rose-600">- @rupiah($supir['potongan_bpjs'])</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-600">Potongan Cicilan Kasbon</span>
                        <span class="tabular-nums text-rose-600">- @rupiah($supir['potongan_kasbon'])</span>
                    </div>
                </div>
                <div class="flex justify-between pt-3 border-t-2 border-emerald-600 font-extrabold text-sm text-emerald-700">
                    <span>GAJI BERSIH (TAKE HOME PAY)</span>
                    <span class="tabular-nums">@rupiah($supir['gaji_bersih'])</span>
                </div>
            </div>
        </div>

        {{-- Lampiran Daftar Surat Jalan Pengantaran --}}
        <div class="space-y-2">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Lampiran Catatan Pengantaran &amp; Surat Jalan — {{ count($supir['daftar_ritase']) }} Tiket</h2>
            <div class="overflow-x-auto border border-slate-300 rounded-lg">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300 text-[10px] font-bold uppercase tracking-wider text-slate-600">
                            <th class="py-2 px-2.5 text-center w-10">No</th>
                            <th class="py-2 px-2.5">Tanggal</th>
                            <th class="py-2 px-2.5">No. Surat Jalan</th>
                            <th class="py-2 px-2.5">Rute</th>
                            <th class="py-2 px-2.5">Material</th>
                            <th class="py-2 px-2.5 text-center">Rit</th>
                            <th class="py-2 px-2.5 text-right">Tonase (T)</th>
                            <th class="py-2 px-2.5 text-right">Tarif / Ton</th>
                            <th class="py-2 px-2.5 text-right">Total Angkutan</th>
                            <th class="py-2 px-2.5 text-right">Uang Jalan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($supir['daftar_ritase'] as $tiket)
                            <tr>
                                <td class="py-1.5 px-2.5 text-center font-mono text-[11px] text-slate-500">{{ $tiket['no'] }}</td>
                                <td class="py-1.5 px-2.5 tabular-nums">{{ $tiket['tanggal'] }}</td>
                                <td class="py-1.5 px-2.5 font-mono font-semibold text-blue-700">{{ $tiket['no_do'] }}</td>
                                <td class="py-1.5 px-2.5 font-semibold">{{ $tiket['kode_rute'] }}</td>
                                <td class="py-1.5 px-2.5">{{ $tiket['material'] }}</td>
                                <td class="py-1.5 px-2.5 text-center tabular-nums">{{ $tiket['rit'] }}</td>
                                <td class="py-1.5 px-2.5 text-right tabular-nums">{{ $tiket['tonase_label'] }}</td>
                                <td class="py-1.5 px-2.5 text-right tabular-nums">@rupiah($tiket['tarif_per_ton'])</td>
                                <td class="py-1.5 px-2.5 text-right tabular-nums font-semibold">@rupiah($tiket['total_angkutan'])</td>
                                <td class="py-1.5 px-2.5 text-right tabular-nums">@rupiah($tiket['uang_jalan'])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Blok Pengesahan & Tanda Tangan --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-6 border-t border-slate-200 pt-5 text-xs">
            <div class="space-y-1 text-slate-500 max-w-md">
                @if (! empty($perusahaanAktif->nama_bank) || ! empty($perusahaanAktif->no_rekening))
                    <div>
                        <span class="font-semibold text-slate-700">Sumber Rekening Perusahaan:</span>
                        <span class="font-mono text-slate-800">
                            {{ collect([$perusahaanAktif->nama_bank, $perusahaanAktif->no_rekening])->filter()->implode(' — ') }}
                        </span>
                    </div>
                @endif
                <p>Dokumen ini dicetak dari sistem penggajian resmi {{ $perusahaanAktif->nama_perusahaan }}.</p>
            </div>

            <div class="min-w-[210px] text-center self-end">
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
