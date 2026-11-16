<x-guest-layout title="Lupa Kata Sandi">
    <div class="relative flex min-h-screen w-full flex-col items-center justify-center overflow-hidden bg-sidebar-dark p-4 sm:p-6 lg:p-8">
        {{-- Dekorasi latar --}}
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(#38BDF8 1px, transparent 1px); background-size: 20px 20px;"></div>
        <div class="pointer-events-none absolute -left-24 top-1/4 h-72 w-72 rounded-full bg-banner-indigo/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-banner-sky/20 blur-3xl"></div>

        {{-- Brand header --}}
        <div class="relative z-10 mb-6 flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep text-white shadow-hero">
                <span class="material-symbols-outlined text-[1.5rem]">local_shipping</span>
            </div>
            <div class="flex flex-col">
                <span class="font-display text-headline-sm font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">Logistik &amp; Penggajian Batubara</span>
            </div>
        </div>

        {{-- Kontainer utama 12 kolom --}}
        <div class="relative z-10 grid w-full max-w-5xl items-start gap-6 lg:grid-cols-12">
            {{-- Kolom kiri: Form pemulihan kata sandi --}}
            <div class="rounded-2xl bg-card-bg p-6 shadow-2xl sm:p-8 lg:col-span-7">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 font-sans text-label-sm font-bold uppercase tracking-wider text-primary">
                        <span class="material-symbols-outlined text-[0.95rem]">lock_reset</span>
                        Modul Keamanan
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-sans text-label-sm font-semibold text-net-pay-emerald">
                        <span class="h-2 w-2 rounded-full bg-net-pay-emerald"></span>
                        Layanan Aktif
                    </span>
                </div>

                <h1 class="font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">
                    Lupa Kata Sandi Anda?
                </h1>
                <p class="mt-2 font-sans text-body-md leading-relaxed text-slate-text-secondary">
                    Masukkan alamat email akun yang terdaftar pada sistem untuk menerima tautan pemulihan kata sandi.
                </p>

                <x-auth-session-status class="mt-4" :status="session('status')" />

                @if ($errors->any())
                    <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-danger-red/30 bg-danger-surface px-4 py-3 font-sans text-body-sm text-slate-text-primary">
                        <span class="material-symbols-outlined mt-0.5 text-[1.125rem] text-danger-red">error</span>
                        <div class="flex flex-col">
                            <span class="font-semibold text-danger-red">Permintaan gagal</span>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-5">
                    @csrf

                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <label for="email" class="font-sans text-label-md font-semibold text-slate-text-primary">
                                Alamat Email Terdaftar
                            </label>
                            <span class="rounded bg-surface-container-low px-2 py-0.5 font-sans text-[0.6875rem] font-semibold text-slate-text-muted">
                                Verifikasi Email
                            </span>
                        </div>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined pointer-events-none absolute left-3.5 text-[1.125rem] text-slate-text-muted">alternate_email</span>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="contoh: nama@perusahaan.co.id"
                                class="h-11 w-full rounded-xl border border-card-border bg-canvas-bg pl-10 pr-4 font-sans text-body-md text-slate-text-primary placeholder:text-slate-text-muted focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                        </div>
                        <span class="font-sans text-label-sm text-slate-text-muted">
                            Gunakan email aktif yang terdaftar pada akun Admin HRD, Pimpinan, atau Supir.
                        </span>
                    </div>

                    <div class="flex items-start gap-3 rounded-xl border border-primary/15 bg-primary/5 p-4">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-[1.25rem] text-primary">verified_user</span>
                        <div class="flex flex-col gap-0.5">
                            <span class="font-sans text-body-sm font-semibold text-primary">Otorisasi Terenkripsi</span>
                            <p class="font-sans text-label-sm leading-relaxed text-slate-text-secondary">
                                Tautan atur ulang kata sandi hanya berlaku sementara. Apabila email sudah tidak aktif, hubungi Admin HRD untuk pembaruan data akun.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 pt-1 sm:flex-row">
                        <button
                            type="submit"
                            class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-primary-container px-5 font-display text-headline-sm text-on-primary shadow-md transition-all hover:bg-primary active:scale-[0.99]"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">mark_email_read</span>
                            Kirim Instruksi Pemulihan
                        </button>
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-surface-container-low px-5 font-sans text-body-md font-semibold text-slate-text-primary transition-colors hover:bg-surface-container"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">arrow_back</span>
                            Kembali ke Login
                        </a>
                    </div>
                </form>
            </div>

            {{-- Kolom kanan: Kartu bantuan & status --}}
            <div class="flex flex-col gap-4 lg:col-span-5">
                <div class="rounded-2xl bg-card-bg p-6 shadow-card">
                    <div class="mb-3 flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-warning-surface text-warning-amber">
                            <span class="material-symbols-outlined text-[1.25rem]">support_agent</span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="font-display text-headline-sm font-bold text-slate-text-primary">Kendala Akses Akun?</h2>
                            <p class="mt-0.5 font-sans text-body-sm text-slate-text-secondary">
                                Khusus supir armada yang berganti nomor atau lupa email terdaftar.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl bg-surface-container-low p-4">
                        <div class="flex items-center justify-between">
                            <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-muted">Bantuan Verifikasi</span>
                            <span class="font-sans text-label-sm font-semibold text-net-pay-emerald">Admin HRD Pool</span>
                        </div>
                        <p class="mt-1.5 font-sans text-body-sm text-slate-text-primary">
                            Kunjungi loket administrasi HRD dengan membawa kartu identitas karyawan (NIP) untuk reset kata sandi manual.
                        </p>
                    </div>

                    <div class="mt-4 flex flex-col gap-2">
                        <span class="font-sans text-label-sm font-bold uppercase tracking-wider text-slate-text-secondary">Langkah Pemulihan Mandiri</span>
                        <div class="flex items-start gap-2 font-sans text-body-sm text-slate-text-secondary">
                            <span class="material-symbols-outlined mt-0.5 text-[1rem] text-primary">check_circle</span>
                            <span>Masukkan email akun dan klik Kirim Instruksi Pemulihan.</span>
                        </div>
                        <div class="flex items-start gap-2 font-sans text-body-sm text-slate-text-secondary">
                            <span class="material-symbols-outlined mt-0.5 text-[1rem] text-primary">check_circle</span>
                            <span>Buka tautan reset yang dikirim ke kotak masuk email Anda.</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-card-bg p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 font-sans text-body-sm font-semibold text-slate-text-primary">
                            <span class="material-symbols-outlined text-[1.125rem] text-primary">dns</span>
                            Server Autentikasi
                        </div>
                        <span class="rounded-full bg-net-pay-surface px-2.5 py-0.5 font-sans text-label-sm font-bold text-net-pay-emerald">Terhubung</span>
                    </div>
                    <p class="mt-1.5 font-sans text-label-sm text-slate-text-muted">
                        Seluruh permintaan pemulihan kata sandi diproses terenkripsi melalui sistem otorisasi internal.
                    </p>
                </div>
            </div>
        </div>

        <p class="relative z-10 mt-8 text-center font-sans text-label-sm text-slate-text-muted">
            &copy; {{ date('Y') }} {{ config('app.name') }} &bull; Portal Penggajian &amp; Logistik Batubara
        </p>
    </div>
</x-guest-layout>
