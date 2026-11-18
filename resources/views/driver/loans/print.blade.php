<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN CETAK BUKU KASBON SUPIR):
     - Tujuan Halaman: Dokumen fisik Buku Kasbon & Jadwal Cicilan Supir siap cetak kertas A4.
     - Analogi Dunia Nyata: Lembaran perjanjian dan kartu cicilan kasbon yang ditandatangani
       oleh supir dan bagian keuangan perusahaan sebagai bukti tertulis pengambilan uang muka.
     - Data dari Controller:
       * $karyawan: Data supir peminjam.
       * $kasbon: Data pinjaman pokok dari tabel `kasbon`.
       * $angsuran: Jadwal potongan gaji dari tabel `angsuran_kasbon`.
     ============================================================================== -->
@php
    $perusahaanAktif = $dataPerusahaan ?? \App\Models\DataPerusahaan::profilAktif();
    $tanggalHariIni = now()->translatedFormat('d F Y');

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
    $author = $infoAuthor ?? config('app.author', []);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="{{ $author['nama'] ?? 'Nabela Septiara' }} (NPM: {{ $author['npm'] ?? '2311050052' }})">
    <meta name="description" content="{{ $author['judul'] ?? 'Payroll System pada PT ASM Lampung' }}">
    <title>Buku Kasbon Supir — {{ $karyawan?->nama_lengkap ?? 'Supir' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; }
            .sheet-container { border: none !important; box-shadow: none !important; margin: 0 !important; max-width: 100% !important; padding: 1.5rem !important; }
            @page { size: A4 portrait; margin: 12mm 15mm; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased p-4 md:p-8">
    <div class="sheet-container max-w-4xl mx-auto bg-white border border-slate-200 rounded-2xl shadow-sm p-6 md:p-8 space-y-6">
        {{-- Top Action Bar (No Print) --}}
        <div class="no-print flex items-center justify-between border-b border-slate-200 pb-4">
            <a
                href="{{ route('driver.loans.index') }}"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-blue-600"
            >
                <span class="material-symbols-outlined text-[1rem]">arrow_back</span>
                <span>Kembali ke Buku Kasbon</span>
            </a>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-2xs transition-colors"
                >
                    <span class="material-symbols-outlined text-[1rem]">print</span>
                    <span>Cetak / Simpan PDF</span>
                </button>
            </div>
        </div>

        {{-- Header Dokumen / Kop Perusahaan --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b-2 border-slate-900 pb-4">
            <div class="flex items-start gap-3.5">
                @if ($urlLogoPerusahaan !== '')
                    <img
                        src="{{ $urlLogoPerusahaan }}"
                        alt="Logo {{ $perusahaanAktif->nama_perusahaan }}"
                        class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 bg-white object-contain p-1"
                    >
                @else
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white font-bold text-xl">
                        {{ strtoupper(substr($perusahaanAktif->nama_perusahaan ?? 'ASM', 0, 3)) }}
                    </div>
                @endif
                <div class="space-y-0.5">
                    <h1 class="text-xl font-black uppercase tracking-tight text-slate-900">{{ $perusahaanAktif->nama_perusahaan ?? 'PT ADIGUNA SEJAHTERA MANDIRI' }}</h1>
                    <p class="text-xs font-medium text-slate-600">{{ $perusahaanAktif->alamat_lengkap ?? 'Jl. Lintas Sumatera Km. 12' }} &bull; {{ $perusahaanAktif->kota ?? 'Lampung' }}</p>
                    <p class="text-xs text-slate-500">Telp: {{ $perusahaanAktif->nomor_telepon ?? '-' }} &bull; Email: {{ $perusahaanAktif->email_resmi ?? '-' }}</p>
                </div>
            </div>
            <div class="sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0">
                <div class="inline-block rounded-md bg-slate-100 border border-slate-200 px-3 py-1.5 text-right">
                    <div class="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">Buku Kasbon &amp; Cicilan</div>
                    <div class="font-mono text-base font-extrabold text-blue-700">KB-{{ $karyawan?->nomor_induk_karyawan ?? '001' }}</div>
                </div>
                <div class="mt-1 text-xs text-slate-500">Per Tanggal: <strong class="text-slate-800">{{ $tanggalHariIni }}</strong></div>
            </div>
        </div>

        {{-- Judul Dokumen --}}
        <div class="text-center py-1">
            <h2 class="text-base sm:text-lg font-black uppercase tracking-wider text-slate-900 underline decoration-slate-900 decoration-2 underline-offset-4">
                BUKU CATATAN PINJAMAN KASBON &amp; ANGSURAN PENGEMUDI
            </h2>
            <p class="mt-1 text-xs text-slate-500">Rekapitulasi resmi sisa saldo pinjaman, jadwal pemotongan gaji, dan mutasi kasbon supir.</p>
        </div>

        {{-- Identitas Pengemudi & Saldo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-2 text-xs">
                <div class="font-bold uppercase tracking-wider text-blue-900 border-b border-slate-200 pb-1">Identitas Pengemudi</div>
                <div class="grid grid-cols-3 gap-1">
                    <span class="text-slate-500">Nama Supir</span>
                    <span class="col-span-2 font-bold text-slate-900">: {{ $karyawan?->nama_lengkap ?? '—' }}</span>
                    <span class="text-slate-500">NIP Pengemudi</span>
                    <span class="col-span-2 font-mono font-semibold text-slate-800">: {{ $karyawan?->nomor_induk_karyawan ?? '—' }}</span>
                    <span class="text-slate-500">Status Akun</span>
                    <span class="col-span-2 font-semibold text-emerald-700">: Aktif Terdaftar</span>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-2 text-xs">
                <div class="font-bold uppercase tracking-wider text-blue-900 border-b border-slate-200 pb-1">Ringkasan Saldo Kasbon</div>
                <div class="grid grid-cols-3 gap-1">
                    <span class="text-slate-500">Total Pinjaman Aktif</span>
                    <span class="col-span-2 font-mono font-bold text-slate-900">: @rupiah($loan['total_amount'] ?? 0)</span>
                    <span class="text-slate-500">Sisa Saldo Kasbon</span>
                    <span class="col-span-2 font-mono font-bold text-amber-700">: @rupiah($loan['outstanding'] ?? 0)</span>
                    <span class="text-slate-500">Potongan Periode Berjalan</span>
                    <span class="col-span-2 font-mono font-bold text-emerald-700">: @rupiah($loan['installment'] ?? 0)</span>
                </div>
            </div>
        </div>

        {{-- Tabel Jadwal Angsuran --}}
        <div class="space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Jadwal Angsuran &amp; Pemotongan Gaji</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="bg-slate-100 font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                            <th class="py-2 px-3 text-center w-12">Cicilan</th>
                            <th class="py-2 px-3">Periode Rencana</th>
                            <th class="py-2 px-3 text-right">Nominal Angsuran</th>
                            <th class="py-2 px-3 text-right">Sisa Saldo</th>
                            <th class="py-2 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-mono">
                        @forelse ($schedule as $angsuran)
                            <tr>
                                <td class="py-2.5 px-3 text-center font-bold">#{{ $angsuran['month'] ?? '-' }}</td>
                                <td class="py-2.5 px-3 font-sans">{{ $angsuran['period'] ?? '-' }}</td>
                                <td class="py-2.5 px-3 text-right font-bold">@rupiah($angsuran['amount'] ?? 0)</td>
                                <td class="py-2.5 px-3 text-right text-slate-600">@rupiah($angsuran['remaining'] ?? 0)</td>
                                <td class="py-2.5 px-3 text-center font-sans">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ ($angsuran['status'] ?? '') === 'Terpotong Gaji' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $angsuran['status'] ?? 'Menunggu Pemotongan' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-500 font-sans">Tidak ada jadwal cicilan kasbon aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel Mutasi Kasbon --}}
        <div class="space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Riwayat Mutasi &amp; Penarikan Kasbon</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="bg-slate-100 font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                            <th class="py-2 px-3">Tanggal</th>
                            <th class="py-2 px-3">Keterangan / Keperluan</th>
                            <th class="py-2 px-3 text-center">Jenis</th>
                            <th class="py-2 px-3 text-right">Nominal</th>
                            <th class="py-2 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-sans">
                        @forelse ($mutations as $mutasi)
                            <tr>
                                <td class="py-2 px-3 text-slate-600 font-mono">{{ $mutasi['date'] ?? '-' }}</td>
                                <td class="py-2 px-3 font-medium text-slate-900">{{ $mutasi['desc'] ?? '-' }}</td>
                                <td class="py-2 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ ($mutasi['type'] ?? '') === 'Penarikan' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ $mutasi['type'] ?? 'Mutasi' }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">@rupiah($mutasi['amount'] ?? 0)</td>
                                <td class="py-2 px-3 text-center text-[11px] text-slate-500">{{ $mutasi['status'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-500 font-sans">Belum ada riwayat mutasi kasbon.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tanda Tangan --}}
        <div class="pt-4">
            <div class="grid grid-cols-2 gap-8 text-center text-xs">
                <div class="flex flex-col justify-between h-32 border border-slate-200 rounded-xl p-3 bg-slate-50/30">
                    <div class="font-semibold text-slate-700 uppercase tracking-wide">Pengemudi (Supir)</div>
                    <div>
                        <div class="border-b border-slate-400 w-3/4 mx-auto mb-1"></div>
                        <div class="font-bold text-slate-900">{{ $karyawan?->nama_lengkap ?? 'Supir' }}</div>
                        <div class="text-[0.6875rem] text-slate-500">{{ $karyawan?->nomor_induk_karyawan ?? 'Pengemudi' }}</div>
                    </div>
                </div>

                <div class="flex flex-col justify-between h-32 border border-slate-200 rounded-xl p-3 bg-slate-50/30">
                    <div class="font-semibold text-slate-700 uppercase tracking-wide">Kasir Keuangan Perusahaan</div>
                    <div>
                        <div class="border-b border-slate-400 w-3/4 mx-auto mb-1"></div>
                        <div class="font-bold text-slate-900">{{ $perusahaanAktif->nama_pemilik ?? 'Pimpinan Perusahaan' }}</div>
                        <div class="text-[0.6875rem] text-slate-500">{{ $perusahaanAktif->nama_perusahaan ?? 'PT ASM' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Atribusi Sistem / Author Cetak --}}
        <div class="border-t border-slate-200 pt-3 text-center text-[10px] text-slate-400">
            {{ $author['judul'] ?? 'Payroll System pada PT ASM Lampung' }} &bull; Pengembang: {{ $author['nama'] ?? 'Nabela Septiara' }} (NPM: {{ $author['npm'] ?? '2311050052' }})
        </div>
    </div>
</body>
</html>
