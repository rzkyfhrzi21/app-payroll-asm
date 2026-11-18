<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN PROFIL SUPIR & ARMADA):
     - Tujuan Halaman: Halaman informasi data pribadi supir, unit armada yang dipegang,
       rekening bank penerima gaji, serta formulir ganti foto profil dan kata sandi.
     - Analogi Dunia Nyata: Seperti kartu pengenal supir dan buku pedoman unit truk:
       supir mengecek apakah plat nomor truknya sudah sesuai, apakah nomor rekening banknya benar,
       dan memperbarui kata sandi login agar akun tetap aman.
     - Data dari Controller (Driver/ProfileController@edit):
       * $profile: Data lengkap supir dari model `Karyawan` join `Truk`.
       * $user: Akun autentikasi login supir dari tabel `users`.
     ============================================================================== -->
@php
    $pengguna = $user ?? auth()->user();
    $toneMap = [
        'amber' => ['bg' => 'bg-warning-surface/60', 'icon_bg' => 'bg-warning-amber/20', 'icon' => 'text-warning-amber', 'badge' => 'bg-warning-amber text-white'],
        'emerald' => ['bg' => 'bg-surface-container-low', 'icon_bg' => 'bg-net-pay-surface', 'icon' => 'text-net-pay-emerald', 'badge' => 'bg-net-pay-surface text-net-pay-emerald'],
        'slate' => ['bg' => 'bg-surface-container-low', 'icon_bg' => 'bg-surface-variant', 'icon' => 'text-primary', 'badge' => 'bg-surface-container text-on-surface-variant'],
    ];
@endphp

<x-driver-layout title="Profil & Armada">
    <div class="flex flex-col gap-5" x-data="{ tabAktif: 'data-pribadi' }">
        {{-- Header halaman --}}
        <div class="flex flex-col justify-between gap-space-md rounded-xl bg-card-bg p-space-md shadow-card lg:flex-row lg:items-center">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 font-sans text-label-sm text-slate-text-secondary">
                    <span>Portal Supir</span>
                    <span class="material-symbols-outlined text-[0.8125rem] text-slate-text-muted">chevron_right</span>
                    <span class="font-semibold text-slate-text-primary">Profil Supir &amp; Info Armada</span>
                </div>
                <h1 class="font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">
                    Profil Karyawan Supir &amp; Info Unit Armada
                </h1>
            </div>
        </div>

        {{-- Kartu Profil Utama (Hero) --}}
        <div class="overflow-hidden rounded-2xl border border-card-border bg-card-bg shadow-card">
            <div class="relative flex h-24 items-start justify-between bg-gradient-to-r from-banner-sky via-banner-indigo to-banner-deep p-space-md">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-0.5 font-sans text-label-sm font-semibold uppercase tracking-wider text-white backdrop-blur-md">
                    <span class="h-1.5 w-1.5 rounded-full bg-net-pay-emerald"></span>
                    Karyawan Aktif
                </span>
                <span class="rounded-lg bg-sidebar-dark/40 px-2.5 py-1 font-financial-table text-label-sm font-semibold text-white backdrop-blur">
                    NIP: {{ $profile['nip'] }}
                </span>
            </div>

            <div class="flex flex-col justify-between gap-4 p-space-lg pt-0 lg:flex-row lg:items-end">
                <div class="-mt-10 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl border-4 border-card-bg bg-surface-container-high font-display text-headline-lg font-bold text-primary shadow-md overflow-hidden">
                        @if (!empty($pengguna?->url_foto_profil))
                            <img src="{{ $pengguna->url_foto_profil }}" alt="{{ $profile['name'] }}" class="h-full w-full object-cover">
                        @else
                            <span>{{ $profile['initial'] }}</span>
                        @endif
                        <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-net-pay-emerald text-white shadow-sm">
                            <span class="material-symbols-outlined text-[0.875rem]">check</span>
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="font-display text-headline-md font-bold text-slate-text-primary">{{ $profile['name'] }}</h2>
                        <div class="flex flex-wrap items-center gap-2 font-sans text-body-sm text-slate-text-secondary">
                            <span class="font-semibold text-primary">Supir</span>
                            <span>&bull;</span>
                            <span>{{ $profile['email'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-surface-container-low px-3.5 py-2.5">
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Unit Pegangan</span>
                        <div class="font-display text-body-md font-bold text-slate-text-primary">{{ $profile['plate'] }}</div>
                    </div>
                    <div class="rounded-xl bg-surface-container-low px-3.5 py-2.5">
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Skema Komisi</span>
                        <div class="font-display text-body-md font-bold text-primary">{{ $profile['scheme'] }}</div>
                    </div>
                    <div class="col-span-2 rounded-xl bg-surface-container-low px-3.5 py-2.5 sm:col-span-1">
                        <span class="font-sans text-label-sm uppercase text-slate-text-muted">Potongan BPJS</span>
                        <div class="font-display text-body-md font-bold text-net-pay-emerald">{{ $profile['bpjs'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4 KPI Ringkas --}}
        <div class="grid grid-cols-2 gap-space-md lg:grid-cols-4">
            <x-ui.stat-tile label="Total Pengantaran" :nilai="$profile['rit_total'].' Trip'" ikon="local_shipping" tone="primary" catatan="Akumulasi perjalanan" />
            <x-ui.stat-tile label="Rata-rata Tonase" :nilai="$profile['rata_tonase'].' Ton'" ikon="scale" tone="amber" catatan="Per pengantaran" />
            <x-ui.stat-tile label="Rata-rata Harian" :nilai="$profile['rata_rit_harian'].' Rit/Hari'" ikon="speed" tone="emerald" catatan="Performa operasional" />
            <x-ui.stat-tile label="Periode Aktif" :nilai="$period['code']" ikon="calendar_month" tone="slate" :catatan="$period['range']" />
        </div>

        {{-- Subtab Selector --}}
        <div class="flex items-center gap-2 border-b border-card-border pb-1">
            <button
                type="button"
                @click="tabAktif = 'data-pribadi'"
                :class="tabAktif === 'data-pribadi' ? 'bg-primary text-on-primary font-bold shadow-sm' : 'bg-card-bg text-slate-text-secondary hover:bg-surface-container-low'"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 font-sans text-body-sm transition-all"
            >
                <span class="material-symbols-outlined text-[1.125rem]">person</span>
                <span>Data Pribadi &amp; Info Armada</span>
            </button>
            <button
                type="button"
                @click="tabAktif = 'keamanan'"
                :class="tabAktif === 'keamanan' ? 'bg-primary text-on-primary font-bold shadow-sm' : 'bg-card-bg text-slate-text-secondary hover:bg-surface-container-low'"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 font-sans text-body-sm transition-all"
            >
                <span class="material-symbols-outlined text-[1.125rem]">shield_lock</span>
                <span>Keamanan &amp; Kata Sandi</span>
            </button>
        </div>

        {{-- Konten Tab 1: Data Pribadi & Armada --}}
        <div x-show="tabAktif === 'data-pribadi'" class="flex flex-col gap-6">
        {{-- Grid Utama: Edit Profil & Kontak + Unit Armada + Ubah Password --}}
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- Kiri: Form Profil & Kontak + Rekening Payroll --}}
            <div class="flex flex-col gap-6 lg:col-span-7">
                <div class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card">
                    <div class="mb-5 flex items-center gap-3 border-b border-card-border pb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <span class="material-symbols-outlined text-[1.25rem]">person</span>
                        </div>
                        <div>
                            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Data Pribadi &amp; Kontak</h3>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Perbarui nama, email akun, nomor WhatsApp/telepon, dan alamat domisili Anda.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('driver.profile.update') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                        @csrf
                        @method('PATCH')

                        <div class="flex flex-col sm:flex-row items-center gap-4 rounded-xl border border-card-border bg-surface-container-low/60 p-4">
                            <div class="relative h-16 w-16 shrink-0 rounded-xl overflow-hidden border border-card-border bg-card-bg flex items-center justify-center font-bold text-primary">
                                @if (!empty($pengguna?->url_foto_profil))
                                    <img src="{{ $pengguna->url_foto_profil }}" alt="{{ $pengguna->name }}" class="h-full w-full object-cover">
                                @else
                                    <span class="text-xl">{{ $profile['initial'] }}</span>
                                @endif
                            </div>
                            <div class="flex-1 space-y-1 w-full">
                                <label for="driver_foto_profil" class="font-sans text-label-md font-bold text-slate-text-primary">Foto Profil Pengemudi</label>
                                <p class="font-sans text-xs text-slate-text-muted">Format berkas gambar JPG, PNG, atau WEBP (maks. 2 MB).</p>
                                <input type="file" id="driver_foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-text-secondary file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1.5">
                                <label for="driver_name" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                    Nama Lengkap <span class="text-danger-red">*</span>
                                </label>
                                <input
                                    id="driver_name"
                                    name="name"
                                    type="text"
                                    required
                                    value="{{ old('name', $pengguna?->name) }}"
                                    class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                                >
                                @error('name') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="driver_email" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                    Email Akun <span class="text-danger-red">*</span>
                                </label>
                                <input
                                    id="driver_email"
                                    name="email"
                                    type="email"
                                    required
                                    value="{{ old('email', $pengguna?->email) }}"
                                    class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                                >
                                @error('email') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1.5">
                                <label for="driver_phone" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                    No. Telepon / WhatsApp
                                </label>
                                <div class="relative flex items-center">
                                    <span class="pointer-events-none absolute left-3.5 font-sans text-body-sm font-bold text-slate-text-secondary">+62</span>
                                    <input
                                        id="driver_phone"
                                        name="nomor_telepon"
                                        type="tel"
                                        inputmode="numeric" maxlength="12" placeholder="81289301123" pattern="8[0-9]{7,11}" title="Diawali angka 8, tanpa angka 0. Contoh: 81289301123"
                                        value="{{ \App\Support\FormatNomorTelepon::keLokal(old('nomor_telepon', $pengguna?->nomor_telepon)) }}"
                                        oninput="window.maskTeleponInput(event)"
                                        class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg pl-14 pr-3.5 font-financial-table text-body-md tabular-nums text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    >
                                </div>
                                <span class="font-sans text-label-sm text-slate-text-muted">Hanya angka dan diawali angka 8, tanpa angka 0.</span>
                                @error('nomor_telepon') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                    Rekening Transfer Payroll
                                </label>
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $profile['bank'] }} — {{ $profile['account'] }}"
                                    class="h-10 w-full cursor-not-allowed rounded-lg border border-card-border bg-surface-container-low px-3.5 font-sans text-body-md font-semibold text-slate-text-secondary"
                                >
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="driver_address" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Alamat Domisili
                            </label>
                            <textarea
                                id="driver_address"
                                name="alamat"
                                rows="2"
                                maxlength="255"
                                placeholder="Alamat domisili saat ini..."
                                class="w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 py-2 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >{{ old('alamat', $pengguna?->alamat) }}</textarea>
                            @error('alamat') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-end border-t border-card-border pt-4">
                            <button
                                type="submit"
                                class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary-container px-5 font-display text-headline-sm text-on-primary shadow-md transition-all hover:bg-primary active:scale-95"
                            >
                                <span class="material-symbols-outlined text-[1.125rem]">save</span>
                                Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Spesifikasi Unit Armada Pegangan --}}
                <div class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-banner-indigo/10 text-banner-indigo">
                                <span class="material-symbols-outlined text-[1.25rem]">rv_hookup</span>
                            </div>
                            <div>
                                <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Unit Armada Pegangan</h3>
                                <p class="font-sans text-body-sm text-slate-text-secondary">{{ $profile['truck'] }}</p>
                            </div>
                        </div>
                        <span class="rounded-lg bg-sidebar-dark px-3 py-1 font-display text-label-md font-bold text-white">
                            {{ $profile['plate'] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @foreach ($truckCompliance as $item)
                            <!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN PROFIL SUPIR & ARMADA):
     - Tujuan Halaman: Halaman informasi data pribadi supir, unit armada yang dipegang,
       rekening bank penerima gaji, serta formulir ganti foto profil dan kata sandi.
     - Analogi Dunia Nyata: Seperti kartu pengenal supir dan buku pedoman unit truk:
       supir mengecek apakah plat nomor truknya sudah sesuai, apakah nomor rekening banknya benar,
       dan memperbarui kata sandi login agar akun tetap aman.
     - Data dari Controller (Driver/ProfileController@edit):
       * $profile: Data lengkap supir dari model `Karyawan` join `Truk`.
       * $user: Akun autentikasi login supir dari tabel `users`.
     ============================================================================== -->
@php $tone = $toneMap[$item['tone']] ?? $toneMap['slate']; @endphp
                            <div class="flex flex-col justify-between gap-2 rounded-xl p-3.5 {{ $tone['bg'] }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-sans text-label-sm font-semibold text-slate-text-secondary">{{ $item['label'] }}</span>
                                    <span class="material-symbols-outlined text-[1rem] {{ $tone['icon'] }}">{{ $item['icon'] }}</span>
                                </div>
                                <span class="font-display text-body-md font-bold text-slate-text-primary">{{ $item['note'] }}</span>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-sans text-label-sm text-slate-text-muted">{{ $item['footer'] }}</span>
                                    <span class="rounded px-1.5 py-0.5 font-sans text-[0.6875rem] font-bold {{ $tone['badge'] }}">{{ $item['badge'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        </div>

        {{-- Konten Tab 2: Keamanan Akun & Ubah Kata Sandi --}}
        <div x-show="tabAktif === 'keamanan'" x-cloak class="max-w-2xl">
            <div class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card" x-data="formUbahKataSandi()">
                <div class="mb-5 flex items-center gap-3 border-b border-card-border pb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-warning-surface text-warning-amber">
                        <span class="material-symbols-outlined text-[1.25rem]">shield_lock</span>
                    </div>
                    <div>
                        <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Keamanan Akun</h3>
                        <p class="font-sans text-body-sm text-slate-text-secondary">Ubah kata sandi akun portal supir Anda.</p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('password.update') }}"
                    novalidate
                    @submit="if (!validasi($el)) $event.preventDefault()"
                    class="flex flex-col gap-4"
                >
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col gap-1.5">
                        <label for="driver_current_password" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                            Kata Sandi Lama <span class="text-danger-red">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <input
                                id="driver_current_password"
                                name="current_password"
                                :type="lihatLama ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                :class="galat.current_password ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg pl-3.5 pr-10 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                            <button type="button" @click="lihatLama = !lihatLama" class="absolute right-2.5 text-slate-text-muted hover:text-slate-text-primary">
                                <span class="material-symbols-outlined text-[1.125rem]" x-text="lihatLama ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                        <p x-show="galat.current_password" x-text="galat.current_password" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        @if ($errors->updatePassword->has('current_password'))
                            <p class="font-sans text-body-sm text-danger-red">{{ $errors->updatePassword->first('current_password') }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="driver_password" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                            Kata Sandi Baru <span class="text-danger-red">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <input
                                id="driver_password"
                                name="password"
                                :type="lihatBaru ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                :class="galat.password ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg pl-3.5 pr-10 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                            <button type="button" @click="lihatBaru = !lihatBaru" class="absolute right-2.5 text-slate-text-muted hover:text-slate-text-primary">
                                <span class="material-symbols-outlined text-[1.125rem]" x-text="lihatBaru ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                        <span class="font-sans text-label-sm text-slate-text-muted">Minimal 8 karakter.</span>
                        <p x-show="galat.password" x-text="galat.password" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        @if ($errors->updatePassword->has('password'))
                            <p class="font-sans text-body-sm text-danger-red">{{ $errors->updatePassword->first('password') }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="driver_password_confirmation" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                            Ketik Ulang Kata Sandi Baru <span class="text-danger-red">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <input
                                id="driver_password_confirmation"
                                name="password_confirmation"
                                :type="lihatKonfirmasi ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                :class="galat.password_confirmation ? 'ring-2 ring-danger-red' : ''"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg pl-3.5 pr-10 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                            <button type="button" @click="lihatKonfirmasi = !lihatKonfirmasi" class="absolute right-2.5 text-slate-text-muted hover:text-slate-text-primary">
                                <span class="material-symbols-outlined text-[1.125rem]" x-text="lihatKonfirmasi ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                        <p x-show="galat.password_confirmation" x-text="galat.password_confirmation" class="font-sans text-body-sm text-danger-red" x-cloak></p>
                        @if ($errors->updatePassword->has('password_confirmation'))
                            <p class="font-sans text-body-sm text-danger-red">{{ $errors->updatePassword->first('password_confirmation') }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-end border-t border-card-border pt-4">
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center gap-2 rounded-lg bg-sidebar-dark px-5 font-display text-headline-sm text-white shadow-md transition-all hover:bg-sidebar-surface active:scale-95"
                        >
                            <span class="material-symbols-outlined text-[1.125rem]">lock_reset</span>
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-driver-layout>
