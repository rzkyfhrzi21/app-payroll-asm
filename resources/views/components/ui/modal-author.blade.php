<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN MODAL TENTANG PENGEMBANG / PENELITI):
     - Tujuan Komponen: Menampilkan kartu profil lengkap mahasiswa/pengembang sistem
       (Nama, NPM, Judul Skripsi, Email, WhatsApp, Instagram, Alamat).
     - Analogi Dunia Nyata: Seperti halaman "Tentang Aplikasi & Kredit Penulis" di bagian
       belakang buku laporan atau menu About di software perusahaan.
     - Sumber Data (Config & Environment .env):
       * Membaca `$infoAuthor` yang dibagikan oleh `AppServiceProvider` dari `config('app.author')`.
       * Nilai diambil dari variabel `.env` (`AUTHOR_*`). Bila variabel di `.env` kosong
         atau tidak ditulis, otomatis memakai nilai bawaan (default) dari `config/app.php`.
     - Interaksi & Event:
       * Dipanggil melalui event Alpine.js `@buka-modal-author.window` dari dropdown akun
         atau tautan footer halaman.
       * Memakai komponen kerangka bersama `x-ui.modal-shell` dengan `:zIndex="80"`.
     ============================================================================== -->
@php
    $author = $infoAuthor ?? config('app.author', []);
    $namaAuthor = $author['nama'] ?? 'Nabela Septiara';
    $npmAuthor = $author['npm'] ?? '2311050052';
    $judulAuthor = $author['judul'] ?? 'Payroll System pada PT ASM Lampung';
    $emailAuthor = $author['email'] ?? 'septiaranabila559@gmail.com';
    $whatsappAuthor = $author['whatsapp'] ?? '085760898417';
    $instagramAuthor = ltrim((string) ($author['instagram'] ?? 'nbelaasp_'), '@');
    $alamatAuthor = $author['alamat'] ?? 'Kosan Dakwah';

    $pecahKata = preg_split('/\s+/', trim((string) $namaAuthor)) ?: [];
    $inisialAuthor = '';
    foreach (array_slice($pecahKata, 0, 2) as $potongan) {
        $inisialAuthor .= mb_strtoupper(mb_substr($potongan, 0, 1));
    }
    $inisialAuthor = $inisialAuthor !== '' ? $inisialAuthor : 'NS';

    $waAngka = preg_replace('/\D/', '', (string) $whatsappAuthor) ?? '';
    if (str_starts_with($waAngka, '0')) {
        $waAngka = '62'.substr($waAngka, 1);
    }
@endphp

<div x-data="{ modalAuthorTerbuka: false }" @buka-modal-author.window="modalAuthorTerbuka = true">
    <x-ui.modal-shell
        name="modalAuthorTerbuka"
        title="Tentang Pengembang & Penelitian"
        :subtitle="$judulAuthor"
        icon="school"
        badge="Identitas Author"
        accent="gradient"
        maxWidth="xl"
        :zIndex="80"
    >
        <div class="flex flex-col gap-4 px-space-lg pb-space-lg">
            {{-- Kartu Identitas Utama Peneliti --}}
            <div class="flex flex-col gap-4 rounded-xl border border-card-border bg-surface-container-low/70 p-4 sm:flex-row sm:items-center">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep font-display text-headline-md font-bold text-white shadow-sm">
                    {{ $inisialAuthor }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">{{ $namaAuthor }}</h3>
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 font-mono text-label-sm font-bold text-primary">
                            NPM {{ $npmAuthor }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Rincian Informasi Penelitian & Kontak --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-card-border bg-card-bg p-3.5 sm:col-span-2">
                    <span class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-muted">Judul Penelitian / Proyek</span>
                    <p class="mt-1 font-sans text-body-md font-semibold text-slate-text-primary">{{ $judulAuthor }}</p>
                </div>

                <a
                    href="mailto:{{ $emailAuthor }}"
                    class="flex items-center gap-3 rounded-xl border border-card-border bg-card-bg p-3.5 transition-colors hover:border-primary/40 hover:bg-surface-container-low"
                >
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <span class="material-symbols-outlined text-[1.125rem]">mail</span>
                    </div>
                    <div class="min-w-0">
                        <span class="block font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-muted">Email Aktif</span>
                        <span class="block truncate font-sans text-body-sm font-semibold text-slate-text-primary">{{ $emailAuthor }}</span>
                    </div>
                </a>

                <a
                    href="{{ $waAngka !== '' ? 'https://wa.me/'.$waAngka : '#' }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-3 rounded-xl border border-card-border bg-card-bg p-3.5 transition-colors hover:border-net-pay-emerald/50 hover:bg-surface-container-low"
                >
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-net-pay-surface text-net-pay-emerald">
                        <span class="material-symbols-outlined text-[1.125rem]">call</span>
                    </div>
                    <div class="min-w-0">
                        <span class="block font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-muted">WhatsApp</span>
                        <span class="block truncate font-mono text-body-sm font-semibold text-slate-text-primary">{{ $whatsappAuthor }}</span>
                    </div>
                </a>

                <a
                    href="https://instagram.com/{{ $instagramAuthor }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-3 rounded-xl border border-card-border bg-card-bg p-3.5 transition-colors hover:border-banner-indigo/40 hover:bg-surface-container-low"
                >
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-banner-indigo/10 text-banner-indigo">
                        <span class="material-symbols-outlined text-[1.125rem]">alternate_email</span>
                    </div>
                    <div class="min-w-0">
                        <span class="block font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-muted">Instagram</span>
                        <span class="block truncate font-sans text-body-sm font-semibold text-slate-text-primary">{{ '@'.$instagramAuthor }}</span>
                    </div>
                </a>

                <div class="flex items-center gap-3 rounded-xl border border-card-border bg-card-bg p-3.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface-container text-slate-text-secondary">
                        <span class="material-symbols-outlined text-[1.125rem]">location_on</span>
                    </div>
                    <div class="min-w-0">
                        <span class="block font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-muted">Alamat</span>
                        <span class="block truncate font-sans text-body-sm font-semibold text-slate-text-primary">{{ $alamatAuthor }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end border-t border-card-border bg-surface-container-low px-space-lg py-space-md">
            <button
                type="button"
                @click="modalAuthorTerbuka = false"
                class="rounded-lg bg-primary px-4 py-2 font-sans text-body-sm font-semibold text-on-primary shadow-sm transition-colors hover:bg-banner-indigo"
            >
                Tutup
            </button>
        </div>
    </x-ui.modal-shell>
</div>
