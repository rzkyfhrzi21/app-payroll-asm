<x-guest-layout title="Masuk">
    <div class="flex min-h-screen w-full items-center justify-center bg-canvas-bg p-3 sm:p-6 lg:p-8">
        <div
            class="grid w-full max-w-7xl overflow-hidden rounded-2xl bg-card-bg shadow-2xl lg:min-h-[47.5rem] lg:grid-cols-12"
            x-data="loginPage"
        >
            {{-- Panel kiri: branding industrial --}}
            <div class="relative flex flex-col justify-between overflow-hidden bg-sidebar-dark p-6 text-on-primary sm:p-8 lg:col-span-5 lg:p-12">
                <div class="pointer-events-none absolute inset-0 opacity-[0.18]" style="background-image: radial-gradient(#38BDF8 1px, transparent 1px); background-size: 18px 18px;"></div>
                <div class="pointer-events-none absolute -left-24 top-1/3 h-72 w-72 rounded-full bg-banner-indigo/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-banner-sky/20 blur-3xl"></div>

                <div class="relative z-10 flex items-center gap-3">
                    @if (!empty($dataPerusahaan?->url_logo))
                        <img
                            src="{{ $dataPerusahaan->url_logo }}"
                            alt="Logo {{ $dataPerusahaan->nama_perusahaan }}"
                            class="h-12 w-12 shrink-0 rounded-xl bg-white object-contain p-1 shadow-hero"
                        >
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep shadow-hero">
                            <span class="material-symbols-outlined text-[1.625rem]">local_shipping</span>
                        </div>
                    @endif
                    <div class="flex flex-col min-w-0">
                        <span class="font-display text-headline-sm font-bold tracking-tight text-white leading-tight truncate max-w-[18rem]">{{ $dataPerusahaan->nama_perusahaan ?? config('app.name') }}</span>
                        <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-muted truncate">{{ $dataPerusahaan->bidang_usaha ?: 'Sistem Informasi Penggajian Supir' }}</span>
                    </div>
                </div>

                <div class="relative z-10 my-auto py-10">

                    <h1 class="mb-4 font-display text-headline-lg font-extrabold leading-snug tracking-tight text-white">
                        Sistem Informasi Penggajian Supir Terintegrasi
                    </h1>
                    <p class="mb-8 font-sans text-body-md leading-relaxed text-slate-text-muted">
                        Otomatisasi perhitungan gaji dan bagi hasil komisi supir berbasis verifikasi surat jalan pengantaran, rekonsiliasi uang jalan, cicilan kasbon, serta penerbitan slip gaji resmi dalam satu dasbor.
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-sidebar-surface/80 p-4 backdrop-blur-sm">
                            <div class="mb-2 flex items-center gap-2 text-banner-sky">
                                <span class="material-symbols-outlined text-[1.25rem]">calculate</span>
                                <span class="mark-label-invert font-sans text-label-sm uppercase tracking-wide">Rekonsiliasi</span>
                            </div>
                            <div class="font-display text-headline-sm font-bold text-white">4 Tahap Presisi</div>
                            <div class="mt-1 font-sans text-body-sm text-slate-text-muted">Perhitungan Surat Jalan, Uang Jalan &amp; Komisi</div>
                        </div>
                        <div class="rounded-xl bg-sidebar-surface/80 p-4 backdrop-blur-sm">
                            <div class="mb-2 flex items-center gap-2 text-net-pay-emerald">
                                <span class="material-symbols-outlined text-[1.25rem]">verified</span>
                                <span class="mark-label-invert font-sans text-label-sm uppercase tracking-wide">Validasi Kas</span>
                            </div>
                            <div class="font-display text-headline-sm font-bold text-white">100% Akurat</div>
                            <div class="mt-1 font-sans text-body-sm text-slate-text-muted">Otorisasi Gaji Berjenjang &amp; Slip Resmi</div>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 flex items-center justify-between pt-4 font-sans text-body-sm text-slate-text-muted">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[0.9375rem] text-banner-sky">pin_drop</span>
                        <span>{{ $dataPerusahaan->alamat ? ($dataPerusahaan->alamat . ' • ' . $dataPerusahaan->kota) : 'Bandar Lampung, Indonesia' }}</span>
                    </span>
                </div>
            </div>

            {{-- Panel kanan: form autentikasi --}}
            <div class="flex flex-col justify-between bg-card-bg p-6 sm:p-10 lg:col-span-7 lg:p-14">
                <div class="w-full">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            @if (!empty($dataPerusahaan?->url_logo))
                                <img
                                    src="{{ $dataPerusahaan->url_logo }}"
                                    alt="Logo {{ $dataPerusahaan->nama_perusahaan }}"
                                    class="h-8 w-8 shrink-0 rounded-lg bg-surface-container object-contain p-0.5"
                                >
                            @else
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-container-high">
                                    <span class="material-symbols-outlined text-[1.25rem] text-primary">account_balance_wallet</span>
                                </div>
                            @endif
                            <span class="font-display text-headline-sm font-bold tracking-tight text-slate-text-primary">{{ $dataPerusahaan->nama_perusahaan ?? config('app.name') }}</span>
                        </div>
                    </div>

                    <h2 class="mb-2 font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">Masuk ke Sistem</h2>
                    <p class="mb-6 font-sans text-body-md text-slate-text-secondary">Masukkan email dan kata sandi akun Anda.</p>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    @if ($errors->any())
                        <div class="mb-4 flex items-start gap-2 rounded-lg border border-danger-red/30 bg-danger-surface px-3.5 py-3 font-sans text-body-sm text-slate-text-primary">
                            <span class="material-symbols-outlined text-[1.125rem] text-danger-red">error</span>
                            <div>
                                <p class="font-semibold">Login gagal.</p>
                                <p>{{ $errors->first() }}</p>
                            </div>
                        </div>
                    @endif

                    <form class="space-y-5" method="POST" action="{{ route('login') }}">
                        @csrf

                        <div>
                            <label class="mb-2 block font-sans text-label-md text-slate-text-primary" for="email">Email</label>
                            <div class="relative flex items-center">
                                <div class="pointer-events-none absolute left-3.5 flex items-center text-slate-text-muted">
                                    <span class="material-symbols-outlined text-[1.25rem]">mail</span>
                                </div>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    x-ref="email"
                                    placeholder="nama@perusahaan.com"
                                    class="w-full rounded-lg bg-surface-container-low py-3 pl-11 pr-4 font-sans text-body-md text-slate-text-primary shadow-sm transition-all placeholder:text-slate-text-muted focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    value="{{ old('email') }}"
                                >
                            </div>
                            @error('email')
                                <p class="mt-1.5 font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="block font-sans text-label-md text-slate-text-primary" for="password">Kata Sandi</label>
                                <a href="{{ route('password.request') }}" class="font-sans text-body-sm font-medium text-primary hover:underline focus:outline-none">Lupa Kata Sandi?</a>
                            </div>
                            <div class="relative flex items-center">
                                <div class="pointer-events-none absolute left-3.5 flex items-center text-slate-text-muted">
                                    <span class="material-symbols-outlined text-[1.25rem]">lock</span>
                                </div>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••••••"
                                    class="w-full rounded-lg bg-surface-container-low py-3 pl-11 pr-12 font-sans text-body-md text-slate-text-primary shadow-sm transition-all placeholder:text-slate-text-muted focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    x-ref="password"
                                >
                                <button
                                    type="button"
                                    class="absolute right-3.5 flex items-center text-slate-text-muted transition-colors hover:text-slate-text-primary focus:outline-none"
                                    @click="togglePassword()"
                                >
                                    <span class="material-symbols-outlined text-[1.25rem]" x-text="showPassword ? 'visibility' : 'visibility_off'"></span>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 font-sans text-body-sm text-danger-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex cursor-pointer select-none items-center gap-2.5">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    class="h-4 w-4 rounded border-outline-variant bg-surface-container-low text-primary focus:ring-primary/30"
                                >
                                <span class="font-sans text-body-sm text-slate-text-secondary">Ingat saya pada perangkat ini (30 Hari)</span>
                            </label>
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="group flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3.5 font-display text-headline-sm text-on-primary shadow-md transition-all hover:bg-banner-indigo hover:shadow-lg active:scale-[0.99]"
                            >
                                <span>Masuk</span>
                                <span class="material-symbols-outlined text-[1.25rem] transition-transform group-hover:translate-x-1">arrow_forward</span>
                            </button>
                        </div>
                    </form>

                        @php
                            $author = $infoAuthor ?? config('app.author', []);
                        @endphp
                        <div class="mt-6 pt-4 border-t border-card-border/60 text-center font-sans text-xs text-slate-text-muted">
                            &copy; {{ date('Y') }} <strong>{{ $dataPerusahaan->nama_perusahaan ?? config('app.name') }}</strong> &bull;
                            <button type="button" @click="$dispatch('buka-modal-author')" class="hover:text-primary hover:underline transition-colors">
                                {{ $author['nama'] ?? 'Nabela Septiara' }}
                            </button>
                            &bull; Seluruh hak cipta dilindungi.
                        </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush
</x-guest-layout>
