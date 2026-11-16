@php
    $perusahaanAktif = $perusahaan ?? $dataPerusahaan ?? \App\Models\DataPerusahaan::profilAktif();

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

    $logoUrlSaatIni = $buatUrlMedia($perusahaanAktif->logo_perusahaan ?? null);
    $ttdUrlSaatIni = $buatUrlMedia($perusahaanAktif->ttd_pemilik ?? null);

    $keadaanAwal = [
        'namaPerusahaan' => (string) old('nama_perusahaan', $perusahaanAktif->nama_perusahaan ?? ''),
        'kodePerusahaan' => (string) old('kode_perusahaan', $perusahaanAktif->kode_perusahaan ?? ''),
        'alamat' => (string) old('alamat', $perusahaanAktif->alamat ?? ''),
        'kota' => (string) old('kota', $perusahaanAktif->kota ?? ''),
        'noTelepon' => (string) old('no_telepon', $perusahaanAktif->no_telepon ?? ''),
        'email' => (string) old('email', $perusahaanAktif->email ?? ''),
        'namaPemilik' => (string) old('nama_pemilik', $perusahaanAktif->nama_pemilik ?? ''),
        'jabatanPemilik' => (string) old('jabatan_pemilik', $perusahaanAktif->jabatan_pemilik ?? ''),
        'namaBank' => (string) old('nama_bank', $perusahaanAktif->nama_bank ?? ''),
        'noRekening' => (string) old('no_rekening', $perusahaanAktif->no_rekening ?? ''),
        'logoUrlSaatIni' => $logoUrlSaatIni,
        'ttdUrlSaatIni' => $ttdUrlSaatIni,
        'hapusLogo' => (bool) old('hapus_logo', false),
        'hapusTtd' => (bool) old('hapus_ttd', false),
    ];

    $tanggalPreview = now()->translatedFormat('d F Y');
@endphp

<x-executive-layout title="Profil Perusahaan">
    <div
        class="flex flex-col gap-6"
        x-data="formulirProfilPerusahaan(@js($keadaanAwal))"
    >
        <x-ui.page-header
            title="Profil Perusahaan"
            subtitle="Kelola identitas resmi perusahaan, rekening operasional, serta logo dan tanda tangan pengesahan untuk slip gaji &amp; laporan cetak."
            badge="Data Master Singleton"
            breadcrumb="Profil Perusahaan"
        >
            <span class="inline-flex items-center gap-1.5 rounded-xl border border-card-border bg-card-bg px-3.5 py-2 font-sans text-label-md text-slate-text-secondary shadow-card">
                <span class="material-symbols-outlined text-[1rem] text-banner-indigo">verified_user</span>
                <span>Akses Khusus Pimpinan / Pemilik</span>
            </span>
        </x-ui.page-header>

        @if ($errors->any())
            <div class="flex items-start gap-3 rounded-xl border border-danger-red/30 bg-danger-surface px-4 py-3.5 text-body-md text-slate-text-primary shadow-card">
                <span class="material-symbols-outlined mt-0.5 text-[1.25rem] text-danger-red">error</span>
                <div class="flex-1">
                    <p class="font-semibold text-danger-red">Periksa kembali data yang diisikan:</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4 text-body-sm text-slate-text-primary">
                        @foreach ($errors->all() as $galat)
                            <li>{{ $galat }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-12">
            {{-- Kolom Kiri: Form Edit Singleton --}}
            <form
                method="POST"
                action="{{ route('executive.company.update') }}"
                enctype="multipart/form-data"
                class="flex flex-col gap-6 xl:col-span-7"
            >
                @csrf
                @method('PATCH')

                {{-- Bagian 1: Identitas Perusahaan --}}
                <section class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card">
                    <div class="mb-5 flex items-center gap-3 border-b border-card-border pb-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-banner-indigo/10 text-banner-indigo">
                            <span class="material-symbols-outlined text-[1.25rem]">apartment</span>
                        </div>
                        <div>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Identitas Perusahaan</h2>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Ditampilkan pada kop surat slip gaji supir, rekapitulasi periode, dan arsip laporan. Teks selain email otomatis disimpan dalam HURUF BESAR.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="nama_perusahaan" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Nama Perusahaan <span class="text-danger-red">*</span>
                            </label>
                            <input
                                id="nama_perusahaan"
                                name="nama_perusahaan"
                                type="text"
                                required
                                maxlength="150"
                                x-model="namaPerusahaan"
                                value="{{ old('nama_perusahaan', $perusahaanAktif->nama_perusahaan) }}"
                                placeholder="Contoh: PT KAFA COALTRANS MINING LOGISTICS"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('nama_perusahaan')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="kode_perusahaan" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Kode / Singkatan Perusahaan
                            </label>
                            <input
                                id="kode_perusahaan"
                                name="kode_perusahaan"
                                type="text"
                                maxlength="30"
                                x-model="kodePerusahaan"
                                value="{{ old('kode_perusahaan', $perusahaanAktif->kode_perusahaan) }}"
                                placeholder="Contoh: COALTRANS"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('kode_perusahaan')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="bidang_usaha" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Bidang / Jasa Perusahaan
                            </label>
                            <input
                                id="bidang_usaha"
                                name="bidang_usaha"
                                type="text"
                                maxlength="150"
                                value="{{ old('bidang_usaha', $perusahaanAktif->bidang_usaha) }}"
                                placeholder="Contoh: LOGISTIK BATUBARA & ANGKUTAN ALAT BERAT"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('bidang_usaha')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="tanggal_berdiri" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Waktu / Tanggal Berdiri Perusahaan
                            </label>
                            <input
                                id="tanggal_berdiri"
                                name="tanggal_berdiri"
                                type="date"
                                value="{{ old('tanggal_berdiri', $perusahaanAktif->tanggal_berdiri?->format('Y-m-d')) }}"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('tanggal_berdiri')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="kota" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Kota / Lokasi Pengesahan
                            </label>
                            <input
                                id="kota"
                                name="kota"
                                type="text"
                                maxlength="100"
                                x-model="kota"
                                value="{{ old('kota', $perusahaanAktif->kota) }}"
                                placeholder="Contoh: PALEMBANG"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('kota')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="no_telepon" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Nomor Telepon Kantor
                            </label>
                            <input
                                id="no_telepon"
                                name="no_telepon"
                                type="text"
                                maxlength="30"
                                x-model="noTelepon"
                                value="{{ old('no_telepon', $perusahaanAktif->no_telepon) }}"
                                placeholder="Contoh: 0711-000000"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('no_telepon')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Email Perusahaan
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                maxlength="120"
                                x-model="email"
                                value="{{ old('email', $perusahaanAktif->email) }}"
                                placeholder="finance@perusahaan.co.id"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('email')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="alamat" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Alamat Lengkap Kantor
                            </label>
                            <textarea
                                id="alamat"
                                name="alamat"
                                rows="3"
                                maxlength="500"
                                x-model="alamat"
                                placeholder="JL. LINTAS SUMATERA KM 12, PALEMBANG"
                                class="w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 py-2.5 font-sans text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >{{ old('alamat', $perusahaanAktif->alamat) }}</textarea>
                            @error('alamat')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- Bagian 2: Pemilik & Pengesahan --}}
                <section class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card">
                    <div class="mb-5 flex items-center gap-3 border-b border-card-border pb-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-net-pay-surface text-net-pay-emerald">
                            <span class="material-symbols-outlined text-[1.25rem]">draw</span>
                        </div>
                        <div>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Pemilik &amp; Pengesahan Dokumen</h2>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Nama pejabat penandatangan yang tercetak pada blok pengesahan slip gaji dan rekapitulasi periode.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="nama_pemilik" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Nama Pemilik / Pimpinan <span class="text-danger-red">*</span>
                            </label>
                            <input
                                id="nama_pemilik"
                                name="nama_pemilik"
                                type="text"
                                required
                                maxlength="120"
                                x-model="namaPemilik"
                                value="{{ old('nama_pemilik', $perusahaanAktif->nama_pemilik) }}"
                                placeholder="Contoh: H. AHMAD WIJAYA"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('nama_pemilik')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="jabatan_pemilik" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Jabatan Pemilik
                            </label>
                            <input
                                id="jabatan_pemilik"
                                name="jabatan_pemilik"
                                type="text"
                                maxlength="80"
                                x-model="jabatanPemilik"
                                value="{{ old('jabatan_pemilik', $perusahaanAktif->jabatan_pemilik) }}"
                                placeholder="Contoh: DIREKTUR UTAMA"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('jabatan_pemilik')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- Bagian 3: Rekening Perusahaan --}}
                <section class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card">
                    <div class="mb-5 flex items-center gap-3 border-b border-card-border pb-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-warning-surface text-warning-amber">
                            <span class="material-symbols-outlined text-[1.25rem]">account_balance</span>
                        </div>
                        <div>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Rekening Perusahaan</h2>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Rekening sumber pencairan gaji operasional perusahaan.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="nama_bank" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Nama Bank
                            </label>
                            <input
                                id="nama_bank"
                                name="nama_bank"
                                type="text"
                                maxlength="80"
                                x-model="namaBank"
                                value="{{ old('nama_bank', $perusahaanAktif->nama_bank) }}"
                                placeholder="Contoh: BANK MANDIRI"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('nama_bank')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="no_rekening" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Nomor Rekening
                            </label>
                            <input
                                id="no_rekening"
                                name="no_rekening"
                                type="text"
                                maxlength="50"
                                x-model="noRekening"
                                value="{{ old('no_rekening', $perusahaanAktif->no_rekening) }}"
                                placeholder="Contoh: 1120001234567"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-mono text-body-md uppercase text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                            @error('no_rekening')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- Bagian 4: Upload & Preview Logo serta Tanda Tangan --}}
                <section class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card">
                    <div class="mb-5 flex items-center gap-3 border-b border-card-border pb-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-container text-primary">
                            <span class="material-symbols-outlined text-[1.25rem]">image</span>
                        </div>
                        <div>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Logo &amp; Tanda Tangan Digital</h2>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Unggah gambar JPG, PNG, atau WEBP maksimal 2 MB. Pratinjau diperbarui otomatis sebelum disimpan.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {{-- Upload Logo Perusahaan --}}
                        <div class="flex flex-col gap-3 rounded-xl border border-card-border bg-canvas-bg/70 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <label for="logo_perusahaan" class="font-sans text-label-md font-bold text-slate-text-primary">
                                    Logo Perusahaan
                                </label>
                                <span class="rounded bg-surface-container px-2 py-0.5 font-sans text-[0.6875rem] font-semibold text-slate-text-secondary">Maks. 2 MB</span>
                            </div>

                            <div class="relative flex h-36 w-full items-center justify-center overflow-hidden rounded-xl border border-dashed border-card-border bg-white p-3">
                                <template x-if="logoTampilUrl()">
                                    <button
                                        type="button"
                                        @click="$dispatch('open-lightbox', { url: logoTampilUrl(), judul: 'Logo Perusahaan', caption: namaPerusahaanAtauBawaan() })"
                                        class="group relative flex h-full w-full items-center justify-center"
                                        title="Klik untuk memperbesar logo"
                                    >
                                        <img
                                            :src="logoTampilUrl()"
                                            alt="Pratinjau Logo Perusahaan"
                                            class="max-h-28 w-auto max-w-full object-contain transition-transform group-hover:scale-105"
                                        >
                                        <span class="absolute bottom-1 right-1 rounded-md bg-slate-900/75 px-2 py-0.5 font-sans text-[0.625rem] font-semibold text-white opacity-0 transition-opacity group-hover:opacity-100">
                                            Perbesar
                                        </span>
                                    </button>
                                </template>
                                <template x-if="! logoTampilUrl()">
                                    <div class="flex flex-col items-center gap-1 text-center text-slate-text-muted">
                                        <span class="material-symbols-outlined text-[2rem]">add_photo_alternate</span>
                                        <span class="font-sans text-body-sm font-medium">
                                            <span x-text="hapusLogo && logoUrlSaatIni ? 'Logo akan dihapus saat disimpan' : 'Belum ada logo perusahaan'"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>

                            <input
                                x-ref="inputLogo"
                                id="logo_perusahaan"
                                name="logo_perusahaan"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                @change="tanganiBerkasGambar($event, 'logo')"
                                class="w-full rounded-lg border border-card-border bg-white py-1.5 pl-2.5 pr-3 font-sans text-body-sm text-slate-text-primary file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-banner-indigo/10 file:px-3 file:py-1 file:font-semibold file:text-banner-indigo hover:file:bg-banner-indigo/20"
                            >

                            <p x-show="galatUkuranLogo" x-cloak class="font-sans text-body-sm font-medium text-danger-red" x-text="galatUkuranLogo"></p>

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <button
                                    type="button"
                                    x-show="logoPreviewBaru !== ''"
                                    x-cloak
                                    @click="batalkanGambarBaru('logo')"
                                    class="inline-flex items-center gap-1 rounded-lg border border-card-border bg-white px-2.5 py-1 font-sans text-label-sm font-semibold text-slate-text-secondary hover:bg-surface-container-low"
                                >
                                    <span class="material-symbols-outlined text-[0.875rem]">close</span>
                                    Batalkan pilihan baru
                                </button>

                                @if ($logoUrlSaatIni !== '')
                                    <label class="inline-flex cursor-pointer items-center gap-2 font-sans text-body-sm font-medium text-danger-red">
                                        <input
                                            type="checkbox"
                                            name="hapus_logo"
                                            value="1"
                                            x-model="hapusLogo"
                                            @change="if (hapusLogo) batalkanGambarBaru('logo')"
                                            class="h-4 w-4 rounded border-card-border text-danger-red focus:ring-danger-red/30"
                                        >
                                        <span>Hapus logo saat ini</span>
                                    </label>
                                @endif
                            </div>

                            @error('logo_perusahaan')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Upload Tanda Tangan Pemilik --}}
                        <div class="flex flex-col gap-3 rounded-xl border border-card-border bg-canvas-bg/70 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <label for="ttd_pemilik" class="font-sans text-label-md font-bold text-slate-text-primary">
                                    Tanda Tangan Pemilik
                                </label>
                                <span class="rounded bg-surface-container px-2 py-0.5 font-sans text-[0.6875rem] font-semibold text-slate-text-secondary">Maks. 2 MB</span>
                            </div>

                            <div class="relative flex h-36 w-full items-center justify-center overflow-hidden rounded-xl border border-dashed border-card-border bg-white p-3">
                                <template x-if="ttdTampilUrl()">
                                    <button
                                        type="button"
                                        @click="$dispatch('open-lightbox', { url: ttdTampilUrl(), judul: 'Tanda Tangan Pemilik', caption: namaPemilikAtauBawaan() })"
                                        class="group relative flex h-full w-full items-center justify-center"
                                        title="Klik untuk memperbesar tanda tangan"
                                    >
                                        <img
                                            :src="ttdTampilUrl()"
                                            alt="Pratinjau Tanda Tangan Pemilik"
                                            class="max-h-28 w-auto max-w-full object-contain transition-transform group-hover:scale-105"
                                        >
                                        <span class="absolute bottom-1 right-1 rounded-md bg-slate-900/75 px-2 py-0.5 font-sans text-[0.625rem] font-semibold text-white opacity-0 transition-opacity group-hover:opacity-100">
                                            Perbesar
                                        </span>
                                    </button>
                                </template>
                                <template x-if="! ttdTampilUrl()">
                                    <div class="flex flex-col items-center gap-1 text-center text-slate-text-muted">
                                        <span class="material-symbols-outlined text-[2rem]">signature</span>
                                        <span class="font-sans text-body-sm font-medium">
                                            <span x-text="hapusTtd && ttdUrlSaatIni ? 'Tanda tangan akan dihapus saat disimpan' : 'Belum ada gambar tanda tangan'"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>

                            <input
                                x-ref="inputTtd"
                                id="ttd_pemilik"
                                name="ttd_pemilik"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                @change="tanganiBerkasGambar($event, 'ttd')"
                                class="w-full rounded-lg border border-card-border bg-white py-1.5 pl-2.5 pr-3 font-sans text-body-sm text-slate-text-primary file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-banner-indigo/10 file:px-3 file:py-1 file:font-semibold file:text-banner-indigo hover:file:bg-banner-indigo/20"
                            >

                            <p x-show="galatUkuranTtd" x-cloak class="font-sans text-body-sm font-medium text-danger-red" x-text="galatUkuranTtd"></p>

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <button
                                    type="button"
                                    x-show="ttdPreviewBaru !== ''"
                                    x-cloak
                                    @click="batalkanGambarBaru('ttd')"
                                    class="inline-flex items-center gap-1 rounded-lg border border-card-border bg-white px-2.5 py-1 font-sans text-label-sm font-semibold text-slate-text-secondary hover:bg-surface-container-low"
                                >
                                    <span class="material-symbols-outlined text-[0.875rem]">close</span>
                                    Batalkan pilihan baru
                                </button>

                                @if ($ttdUrlSaatIni !== '')
                                    <label class="inline-flex cursor-pointer items-center gap-2 font-sans text-body-sm font-medium text-danger-red">
                                        <input
                                            type="checkbox"
                                            name="hapus_ttd"
                                            value="1"
                                            x-model="hapusTtd"
                                            @change="if (hapusTtd) batalkanGambarBaru('ttd')"
                                            class="h-4 w-4 rounded border-card-border text-danger-red focus:ring-danger-red/30"
                                        >
                                        <span>Hapus tanda tangan saat ini</span>
                                    </label>
                                @endif
                            </div>

                            @error('ttd_pemilik')
                                <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- Verifikasi Keamanan Kata Sandi Pimpinan --}}
                <div class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card space-y-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-banner-indigo/10 text-banner-indigo">
                            <span class="material-symbols-outlined text-[1.375rem]">security</span>
                        </div>
                        <div>
                            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Verifikasi Keamanan Akun Pimpinan</h3>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Wajib memasukkan kata sandi akun Pimpinan Anda untuk menyimpan perubahan identitas profil perusahaan.</p>
                        </div>
                    </div>

                    <div class="max-w-md flex flex-col gap-1.5">
                        <label for="password_pimpinan" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                            Kata Sandi Akun Pimpinan <span class="text-danger-red">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined pointer-events-none absolute left-3 text-[1.125rem] text-slate-text-muted">key</span>
                            <input
                                id="password_pimpinan"
                                name="password"
                                type="password"
                                required
                                placeholder="Masukkan kata sandi akun Pimpinan Anda..."
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg pl-9 pr-3 font-sans text-body-md text-slate-text-primary focus:border-banner-indigo focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-banner-indigo/20"
                            >
                        </div>
                        @error('password')
                            <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Tombol Simpan --}}
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-card-border bg-card-bg px-6 py-4 shadow-card">
                    <p class="font-sans text-body-sm text-slate-text-secondary">
                        Perubahan akan langsung diterapkan pada seluruh slip gaji &amp; laporan cetak.
                    </p>
                    <div class="flex items-center gap-2.5">
                        <button
                            type="button"
                            @click="resetFormulir()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-card-border bg-white px-4 py-2.5 font-sans text-body-md font-semibold text-slate-text-secondary transition-colors hover:bg-surface-container-low hover:text-slate-text-primary"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">restart_alt</span>
                            <span>Reset</span>
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-banner-indigo px-5 py-2.5 font-sans text-body-md font-semibold text-white shadow-hero transition-colors hover:bg-banner-deep focus:outline-none focus:ring-2 focus:ring-banner-indigo/40"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">save</span>
                            <span>Simpan Profil Perusahaan</span>
                        </button>
                    </div>
                </div>
            </form>

            {{-- Kolom Kanan: Kartu Preview Langsung Kop Surat & Blok Tanda Tangan --}}
            <aside class="flex flex-col gap-5 xl:sticky xl:top-20 xl:col-span-5">
                <div class="rounded-2xl border border-card-border bg-card-bg p-5 shadow-card">
                    <div class="mb-4 flex items-center justify-between gap-2 border-b border-card-border pb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[1.25rem] text-banner-indigo">preview</span>
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Preview Kop Surat &amp; Tanda Tangan</h2>
                        </div>
                        <span class="rounded-full bg-banner-indigo/10 px-2.5 py-0.5 font-sans text-[0.6875rem] font-bold uppercase tracking-wider text-banner-indigo">
                            Live Preview
                        </span>
                    </div>

                    {{-- Kertas Mockup Dokumen Cetak --}}
                    <div class="rounded-xl border border-slate-300 bg-white p-5 text-slate-900 shadow-sm">
                        {{-- Kop Surat --}}
                        <div class="flex items-start gap-3.5 border-b-2 border-slate-900 pb-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 p-1">
                                <template x-if="logoTampilUrl()">
                                    <img :src="logoTampilUrl()" alt="Logo Preview" class="h-full w-full object-contain">
                                </template>
                                <template x-if="! logoTampilUrl()">
                                    <span class="material-symbols-outlined text-[1.75rem] text-slate-400">apartment</span>
                                </template>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span
                                        x-show="kodePerusahaan.trim() !== ''"
                                        class="rounded bg-blue-50 px-1.5 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider text-blue-700"
                                        x-text="kodePerusahaan.trim().toUpperCase()"
                                    ></span>
                                    <span class="font-sans text-[10px] font-bold uppercase tracking-widest text-blue-700">
                                        DOKUMEN RESMI PENGGAJIAN
                                    </span>
                                </div>
                                <h3
                                    class="mt-0.5 break-words font-display text-sm font-extrabold uppercase tracking-tight text-slate-900"
                                    x-text="namaPerusahaanAtauBawaan()"
                                ></h3>
                                <p
                                    class="mt-0.5 break-words text-[11px] leading-snug text-slate-600"
                                    x-text="alamatLengkapPreview()"
                                ></p>
                                <p class="mt-0.5 flex flex-wrap gap-x-3 text-[10px] text-slate-500">
                                    <span x-show="noTelepon.trim() !== ''" x-text="'Telp: ' + noTelepon.trim()"></span>
                                    <span x-show="email.trim() !== ''" x-text="'Email: ' + email.trim()"></span>
                                </p>
                            </div>
                        </div>

                        {{-- Isi Contoh Ringkas --}}
                        <div class="my-4 space-y-2 border-b border-dashed border-slate-200 pb-4">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold uppercase tracking-wide text-slate-800">SLIP GAJI &amp; REKAP PENGANTARAN SUPIR</span>
                                <span class="font-mono text-[10px] text-slate-500">SLIP-JAN-26-DRV-001</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 rounded-lg bg-slate-50 p-2.5 text-[10px] text-slate-600">
                                <div>
                                    <span class="block text-slate-400">Periode Penggajian</span>
                                    <strong class="text-slate-800">JAN-26 (01–31 Jan 2026)</strong>
                                </div>
                                <div>
                                    <span class="block text-slate-400">Rekening Sumber Perusahaan</span>
                                    <strong class="text-slate-800" x-text="rekeningPreview()"></strong>
                                </div>
                            </div>
                        </div>

                        {{-- Blok Tanda Tangan --}}
                        <div class="flex items-end justify-between gap-4 pt-1">
                            <div class="max-w-[50%] text-[10px] text-slate-500">
                                <p class="font-semibold text-slate-700">Catatan Pengesahan</p>
                                <p class="mt-0.5 leading-relaxed">
                                    Tanda tangan digital tercetak otomatis pada dokumen slip gaji &amp; rekap periode yang telah disetujui Pimpinan.
                                </p>
                            </div>

                            <div class="min-w-[165px] text-center text-[11px]">
                                <p class="text-slate-600">
                                    <span x-text="(kota.trim() || 'PALEMBANG').toUpperCase()"></span>, {{ $tanggalPreview }}
                                </p>
                                <p class="text-[10px] text-slate-500">Mengetahui / Mengesahkan,</p>

                                <div class="my-1.5 flex h-16 items-center justify-center">
                                    <template x-if="ttdTampilUrl()">
                                        <img :src="ttdTampilUrl()" alt="Tanda Tangan Preview" class="max-h-14 w-auto object-contain">
                                    </template>
                                    <template x-if="! ttdTampilUrl()">
                                        <span class="rounded border border-dashed border-slate-300 px-3 py-2 text-[10px] italic text-slate-400">
                                            (Area Tanda Tangan)
                                        </span>
                                    </template>
                                </div>

                                <p
                                    class="border-t border-slate-400 pt-1 font-bold uppercase text-slate-900"
                                    x-text="namaPemilikAtauBawaan()"
                                ></p>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wide text-slate-600"
                                    x-text="(jabatanPemilik.trim() || 'PIMPINAN PERUSAHAAN').toUpperCase()"
                                ></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-banner-indigo/20 bg-banner-indigo/5 p-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-[1.25rem] text-banner-indigo">info</span>
                        <div class="space-y-1 font-sans text-body-sm text-slate-text-secondary">
                            <p class="font-semibold text-slate-text-primary">Integrasi Otomatis Dokumen</p>
                            <p>Logo, identitas, rekening, dan tanda tangan pemilik digunakan langsung pada:</p>
                            <ul class="list-disc space-y-0.5 pl-4">
                                <li>Slip Gaji Supir (<span class="font-mono text-[0.75rem]">/admin/reports/{periode}/slip/{id}</span>)</li>
                                <li>Rekapitulasi Penggajian Cetak (<span class="font-mono text-[0.75rem]">/admin/reports/{periode}/cetak</span>)</li>
                                <li>Halaman Arsip Laporan &amp; Slip Gaji</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script>
        function formulirProfilPerusahaan(awal) {
            return {
                namaPerusahaan: awal.namaPerusahaan || '',
                kodePerusahaan: awal.kodePerusahaan || '',
                alamat: awal.alamat || '',
                kota: awal.kota || '',
                noTelepon: awal.noTelepon || '',
                email: awal.email || '',
                namaPemilik: awal.namaPemilik || '',
                jabatanPemilik: awal.jabatanPemilik || '',
                namaBank: awal.namaBank || '',
                noRekening: awal.noRekening || '',
                logoUrlSaatIni: awal.logoUrlSaatIni || '',
                ttdUrlSaatIni: awal.ttdUrlSaatIni || '',
                logoPreviewBaru: '',
                ttdPreviewBaru: '',
                hapusLogo: Boolean(awal.hapusLogo),
                hapusTtd: Boolean(awal.hapusTtd),
                galatUkuranLogo: '',
                galatUkuranTtd: '',

                logoTampilUrl() {
                    if (this.logoPreviewBaru !== '') {
                        return this.logoPreviewBaru;
                    }
                    if (!this.hapusLogo && this.logoUrlSaatIni !== '') {
                        return this.logoUrlSaatIni;
                    }
                    return '';
                },

                ttdTampilUrl() {
                    if (this.ttdPreviewBaru !== '') {
                        return this.ttdPreviewBaru;
                    }
                    if (!this.hapusTtd && this.ttdUrlSaatIni !== '') {
                        return this.ttdUrlSaatIni;
                    }
                    return '';
                },

                namaPerusahaanAtauBawaan() {
                    return (this.namaPerusahaan.trim() || 'NAMA PERUSAHAAN BELUM DIISI').toUpperCase();
                },

                namaPemilikAtauBawaan() {
                    return (this.namaPemilik.trim() || 'NAMA PEMILIK').toUpperCase();
                },

                alamatLengkapPreview() {
                    const bagian = [];
                    if (this.alamat.trim() !== '') {
                        bagian.push(this.alamat.trim().toUpperCase());
                    }
                    if (this.kota.trim() !== '') {
                        bagian.push(this.kota.trim().toUpperCase());
                    }
                    return bagian.length > 0 ? bagian.join(' — ') : 'Alamat kantor belum dilengkapi';
                },

                rekeningPreview() {
                    const bank = this.namaBank.trim().toUpperCase();
                    const rekening = this.noRekening.trim().toUpperCase();
                    if (bank !== '' && rekening !== '') {
                        return bank + ' • ' + rekening;
                    }
                    if (bank !== '' || rekening !== '') {
                        return bank || rekening;
                    }
                    return 'Belum diatur';
                },

                tanganiBerkasGambar(event, jenis) {
                    const berkas = event.target.files && event.target.files[0];
                    const batasByte = 2 * 1024 * 1024;

                    if (jenis === 'logo') {
                        this.galatUkuranLogo = '';
                    } else {
                        this.galatUkuranTtd = '';
                    }

                    if (!berkas) {
                        this.batalkanGambarBaru(jenis);
                        return;
                    }

                    if (berkas.size > batasByte) {
                        const pesan = 'Ukuran berkas melebihi 2 MB. Pilih gambar yang lebih kecil.';
                        if (jenis === 'logo') {
                            this.galatUkuranLogo = pesan;
                        } else {
                            this.galatUkuranTtd = pesan;
                        }
                        this.batalkanGambarBaru(jenis);
                        return;
                    }

                    const pembaca = new FileReader();
                    pembaca.onload = (e) => {
                        const hasil = String(e.target?.result || '');
                        if (jenis === 'logo') {
                            this.logoPreviewBaru = hasil;
                            this.hapusLogo = false;
                        } else {
                            this.ttdPreviewBaru = hasil;
                            this.hapusTtd = false;
                        }
                    };
                    pembaca.readAsDataURL(berkas);
                },

                batalkanGambarBaru(jenis) {
                    if (jenis === 'logo') {
                        this.logoPreviewBaru = '';
                        if (this.$refs.inputLogo) {
                            this.$refs.inputLogo.value = '';
                        }
                    } else {
                        this.ttdPreviewBaru = '';
                        if (this.$refs.inputTtd) {
                            this.$refs.inputTtd.value = '';
                        }
                    }
                },

                resetFormulir() {
                    this.namaPerusahaan = awal.namaPerusahaan || '';
                    this.kodePerusahaan = awal.kodePerusahaan || '';
                    this.alamat = awal.alamat || '';
                    this.kota = awal.kota || '';
                    this.noTelepon = awal.noTelepon || '';
                    this.email = awal.email || '';
                    this.namaPemilik = awal.namaPemilik || '';
                    this.jabatanPemilik = awal.jabatanPemilik || '';
                    this.namaBank = awal.namaBank || '';
                    this.noRekening = awal.noRekening || '';
                    this.hapusLogo = Boolean(awal.hapusLogo);
                    this.hapusTtd = Boolean(awal.hapusTtd);
                    this.galatUkuranLogo = '';
                    this.galatUkuranTtd = '';
                    this.batalkanGambarBaru('logo');
                    this.batalkanGambarBaru('ttd');
                },
            };
        }
    </script>
</x-executive-layout>
