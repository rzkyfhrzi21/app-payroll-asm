<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN CETAK SURAT JALAN / DO):
     - Tujuan Halaman: Menampilkan dokumen cetak fisik Surat Jalan (Delivery Order) resmi
       yang siap dicetak ke printer di atas kertas A4 portrait.
     - Analogi Dunia Nyata: Seperti surat jalan rangkap 3 yang dibawa supir di dashboard truk:
       berisi identitas armada batubara, nomor DO, instruksi muatan, uang jalan, serta kolom
       stempel & tanda tangan basah (Admin Pengirim, Supir, dan Penerima di pelabuhan).
     - Aturan CSS Cetak (@media print):
       * Tombol aksi (.no-print) disembunyikan otomatis saat dialog cetak muncul (Ctrl+P).
       * Membaca logo dan tanda tangan digital dari storage publik (`storage/data-perusahaan/...`).
     - Data dari Controller:
       * $trip: Model `Ritase` lengkap dengan relasi karyawan, truk, dan rute.
       * $perusahaanAktif: Singleton `DataPerusahaan` (kop surat, alamat, nama direktur).
     ============================================================================== -->
@php
    $perusahaanAktif = $dataPerusahaan ?? \App\Models\DataPerusahaan::profilAktif();
    $tanggalFormatted = $trip->tanggal_ritase?->translatedFormat('d F Y') ?? $trip->tanggal_ritase?->format('d/m/Y') ?? '—';

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

    $labelStatus = match ($trip->status) {
        'terverifikasi' => 'Terverifikasi (Selesai)',
        'sedang_dikirim' => 'Sedang Dikirim',
        'menunggu_verifikasi' => 'Menunggu Verifikasi Admin',
        'terbit' => 'Terbit (Siap Dikirim)',
        'dibatalkan' => 'Dibatalkan',
        default => 'Draf (Belum Terbit)',
    };
    $author = $infoAuthor ?? config('app.author', []);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="{{ $author['nama'] ?? 'Nabela Septiara' }} (NPM: {{ $author['npm'] ?? '2311050052' }})">
    <meta name="description" content="{{ $author['judul'] ?? 'Payroll System pada PT ASM Lampung' }}">
    <title>Surat Jalan {{ $trip->nomor_surat_jalan }} — {{ $karyawan?->nama_lengkap ?? 'Supir' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
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
                href="{{ route('admin.operational.trips.index', $periode?->kode ? ['periode' => $periode->kode] : []) }}"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-blue-600"
            >
                <span class="material-symbols-outlined text-[1rem]">arrow_back</span>
                <span>Kembali ke Operasional Surat Jalan</span>
            </a>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-2xs transition-colors"
                >
                    <span class="material-symbols-outlined text-[1rem]">print</span>
                    <span>Cetak Surat Jalan (PDF)</span>
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
                    <div class="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">Nomor Surat Jalan</div>
                    <div class="font-mono text-base font-extrabold text-blue-700">{{ $trip->nomor_surat_jalan }}</div>
                </div>
                <div class="mt-1 text-xs text-slate-500">Tanggal: <strong class="text-slate-800">{{ $tanggalFormatted }}</strong></div>
            </div>
        </div>

        {{-- Judul Surat Jalan --}}
        <div class="text-center py-1">
            <h2 class="text-base sm:text-lg font-black uppercase tracking-wider text-slate-900 underline decoration-slate-900 decoration-2 underline-offset-4">
                SURAT JALAN PENGANTARAN LOGISTIK BATUBARA
            </h2>
            <p class="mt-1 text-xs text-slate-500">Dokumen resmi bukti penugasan armada dan pengantaran muatan sebagai dasar perhitungan penggajian supir.</p>
        </div>

        {{-- Kotak Identitas Pengemudi & Unit Armada --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-2">
                <div class="text-xs font-bold uppercase tracking-wider text-blue-900 border-b border-slate-200 pb-1 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[1rem]">person</span>
                    Identitas Pengemudi (Supir)
                </div>
                <div class="grid grid-cols-3 gap-1 text-xs">
                    <span class="text-slate-500">Nama Supir</span>
                    <span class="col-span-2 font-bold text-slate-900">: {{ $karyawan?->nama_lengkap ?? '—' }}</span>
                    <span class="text-slate-500">Nomor Induk / NIP</span>
                    <span class="col-span-2 font-mono font-semibold text-slate-800">: {{ $karyawan?->nomor_induk_karyawan ?? '—' }}</span>
                    <span class="text-slate-500">No. Telepon / HP</span>
                    <span class="col-span-2 text-slate-800">: {{ $karyawan?->pengguna?->nomor_telepon ?? '—' }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-2">
                <div class="text-xs font-bold uppercase tracking-wider text-blue-900 border-b border-slate-200 pb-1 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[1rem]">local_shipping</span>
                    Unit Armada Pengangkut
                </div>
                <div class="grid grid-cols-3 gap-1 text-xs">
                    <span class="text-slate-500">Nomor Polisi</span>
                    <span class="col-span-2 font-mono font-bold text-slate-900">: {{ $truk?->nomor_polisi ?? '—' }}</span>
                    <span class="text-slate-500">Merek / Model</span>
                    <span class="col-span-2 text-slate-800">: {{ $truk?->merek_model ?? '—' }}</span>
                    <span class="text-slate-500">Jenis Armada</span>
                    <span class="col-span-2 font-medium text-slate-800">: {{ $truk?->jenisTruk?->nama ?? 'TRONTON' }}</span>
                </div>
            </div>
        </div>

        {{-- Tabel Rincian Muatan & Biaya Jalan --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr class="bg-slate-100 font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                        <th class="py-2.5 px-3 text-center w-10">No</th>
                        <th class="py-2.5 px-3">Rute / Koridor</th>
                        <th class="py-2.5 px-3">Material</th>
                        <th class="py-2.5 px-3 text-center">Rit</th>
                        <th class="py-2.5 px-3 text-right">Tonase Bersih</th>
                        <th class="py-2.5 px-3 text-right">Tarif / Ton</th>
                        <th class="py-2.5 px-3 text-right">Nilai Angkutan</th>
                        <th class="py-2.5 px-3 text-right">Uang Jalan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr class="font-medium">
                        <td class="py-3 px-3 text-center text-slate-500">1</td>
                        <td class="py-3 px-3">
                            <span class="font-bold text-slate-900">{{ $rute?->kode ?? '—' }}</span>
                            <div class="text-[0.6875rem] text-slate-500">{{ $rute?->asal ?? 'LOKASI ASAL' }} &rarr; {{ $rute?->tujuan ?? 'LOKASI TUJUAN' }}</div>
                        </td>
                        <td class="py-3 px-3 font-semibold text-slate-800">{{ $rute?->material ?? 'BATUBARA' }}</td>
                        <td class="py-3 px-3 text-center font-bold">{{ $trip->jumlah_rit }}</td>
                        <td class="py-3 px-3 text-right font-mono font-bold">{{ number_format($trip->tonase_kg / 1000, 2, ',', '.') }} Ton</td>
                        <td class="py-3 px-3 text-right tabular-nums">@rupiah($trip->tarif_per_ton)</td>
                        <td class="py-3 px-3 text-right tabular-nums font-bold text-slate-900">@rupiah($trip->total_angkutan)</td>
                        <td class="py-3 px-3 text-right tabular-nums font-bold text-emerald-700">@rupiah($trip->uang_jalan)</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 font-bold border-t-2 border-slate-200">
                        <td colspan="4" class="py-2.5 px-3 text-right uppercase tracking-wider text-slate-600">Total Muatan &amp; Uang Jalan:</td>
                        <td class="py-2.5 px-3 text-right font-mono">{{ number_format($trip->tonase_kg / 1000, 2, ',', '.') }} Ton</td>
                        <td class="py-2.5 px-3"></td>
                        <td class="py-2.5 px-3 text-right font-bold text-slate-900">@rupiah($trip->total_angkutan)</td>
                        <td class="py-2.5 px-3 text-right font-bold text-emerald-700">@rupiah($trip->uang_jalan)</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Keterangan Penerima & Catatan Lapangan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="rounded-xl border border-slate-200 p-3.5 space-y-1">
                <span class="font-bold uppercase tracking-wider text-slate-600">Nama Petugas Penerima / Timbangan:</span>
                <p class="font-semibold text-slate-900">{{ !empty($trip->nama_penerima) ? $trip->nama_penerima : 'Belum diisi (diisi saat tiba di tujuan)' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-3.5 space-y-1">
                <span class="font-bold uppercase tracking-wider text-slate-600">Catatan Pengantaran:</span>
                <p class="text-slate-800">{{ !empty($trip->catatan_pengantaran) ? $trip->catatan_pengantaran : 'Pengantaran normal sesuai standar operasional pengantaran logistik.' }}</p>
            </div>
        </div>

        {{-- 3 Kolom Tanda Tangan Resmi --}}
        <div class="pt-2">
            <div class="grid grid-cols-3 gap-4 text-center text-xs">
                <div class="flex flex-col justify-between h-36 border border-slate-200 rounded-xl p-3 bg-slate-50/30">
                    <div class="font-semibold text-slate-700 uppercase tracking-wide">Petugas Pengirim / Dispatcher</div>
                    <div class="mt-auto">
                        <div class="border-b border-slate-400 w-3/4 mx-auto mb-1"></div>
                        <div class="font-bold text-slate-900">{{ auth()->user()->name ?? 'Admin Logistik' }}</div>
                        <div class="text-[0.6875rem] text-slate-500">{{ $perusahaanAktif->nama_perusahaan ?? 'PT Adiguna Sejahtera Mandiri' }}</div>
                    </div>
                </div>

                <div class="flex flex-col justify-between h-36 border border-slate-200 rounded-xl p-3 bg-slate-50/30">
                    <div class="font-semibold text-slate-700 uppercase tracking-wide">Pengemudi (Supir)</div>
                    <div class="mt-auto">
                        <div class="border-b border-slate-400 w-3/4 mx-auto mb-1"></div>
                        <div class="font-bold text-slate-900">{{ $karyawan?->nama_lengkap ?? 'Supir Armada' }}</div>
                        <div class="text-[0.6875rem] text-slate-500">{{ $karyawan?->nomor_induk_karyawan ?? 'Pengemudi' }}</div>
                    </div>
                </div>

                <div class="flex flex-col justify-between h-36 border border-slate-200 rounded-xl p-3 bg-slate-50/30">
                    <div class="font-semibold text-slate-700 uppercase tracking-wide">Penerima Barang / Tujuan</div>
                    <div class="mt-auto">
                        <div class="border-b border-slate-400 w-3/4 mx-auto mb-1"></div>
                        <div class="font-bold text-slate-900">{{ !empty($trip->nama_penerima) ? $trip->nama_penerima : '(..........................................)' }}</div>
                        <div class="text-[0.6875rem] text-slate-500">Petugas Penerima Tujuan</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Lembar Catatan --}}
        <div class="border-t border-slate-200 pt-3 flex flex-col sm:flex-row items-center justify-between gap-2 text-[0.6875rem] text-slate-500">
            <div>Lembar 1: Kantor Pusat (HRD/Payroll) &bull; Lembar 2: Supir Armada &bull; Lembar 3: Penerima Tujuan</div>
            <div>Dicetak pada: {{ now()->translatedFormat('d/m/Y H:i') }} WIB &bull; {{ $author['judul'] ?? 'Payroll System pada PT ASM Lampung' }} ({{ $author['nama'] ?? 'Nabela Septiara' }} - {{ $author['npm'] ?? '2311050052' }})</div>
        </div>
    </div>
</body>
</html>
