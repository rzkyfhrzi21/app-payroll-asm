@php
    $isPimpinan = $user->isPimpinan();
    $layoutKomponen = $isPimpinan ? 'executive-layout' : 'app-layout';
    $labelPeran = $isPimpinan ? 'Pimpinan / Owner' : 'Admin HRD';
    $kata = preg_split('/\s+/', trim((string) $user->name)) ?: [];
    $inisial = '';
    foreach (array_slice($kata, 0, 2) as $bagian) {
        $inisial .= mb_strtoupper(mb_substr($bagian, 0, 1));
    }
    $inisial = $inisial !== '' ? $inisial : 'U';
    $ringkasan = $ringkasan ?? [
        'supir_aktif' => 0,
        'jumlah_truk' => 0,
        'jumlah_periode' => 0,
        'menunggu_acc' => 0,
        'periode_disetujui' => 0,
        'periode_aktif' => ['code' => '—', 'range' => '—'],
    ];
@endphp

<x-dynamic-component :component="$layoutKomponen" title="Profil Akun">
    <div class="flex flex-col gap-6" x-data="{ tabAktif: 'data-pribadi' }">
        {{-- Header halaman --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 font-sans text-label-sm font-bold uppercase tracking-wider text-primary">
                    <span class="h-2 w-2 rounded-full bg-primary"></span>
                    <span>{{ $labelPeran }}</span>
                    <span class="text-slate-text-muted">&bull;</span>
                    <span class="text-slate-text-secondary">ID Pengguna #{{ $user->id }}</span>
                </div>
                <h1 class="font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">
                    Profil Akun &amp; Keamanan
                </h1>
                <p class="font-sans text-body-md text-slate-text-secondary">
                    Kelola informasi identitas akun, kontak aktif, dan pembaruan kata sandi.
                </p>
            </div>
        </div>

        {{-- Kartu Ringkasan Profil (Hero) --}}
        <div @class([
            'relative overflow-hidden rounded-2xl border p-6 shadow-card',
            'border-sidebar-surface bg-sidebar-dark text-white' => $isPimpinan,
            'border-card-border bg-card-bg text-slate-text-primary' => ! $isPimpinan,
        ])>
            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                <div class="flex items-center gap-4">
                    <div class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep font-display text-headline-lg font-bold text-white shadow-hero overflow-hidden">
                        @if (!empty($user->url_foto_profil))
                            <img src="{{ $user->url_foto_profil }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        @else
                            <span>{{ $inisial }}</span>
                        @endif
                        <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-card-bg bg-net-pay-emerald text-white">
                            <span class="material-symbols-outlined text-[0.875rem]">check</span>
                        </span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 @class([
                                'font-display text-headline-md font-bold',
                                'text-white' => $isPimpinan,
                                'text-slate-text-primary' => ! $isPimpinan,
                            ])>{{ $user->name }}</h2>
                            <span class="inline-flex items-center gap-1 rounded-full bg-net-pay-surface px-2.5 py-0.5 font-sans text-label-sm font-bold text-net-pay-emerald">
                                <span class="h-1.5 w-1.5 rounded-full bg-net-pay-emerald"></span>
                                Akun Aktif
                            </span>
                        </div>
                        <span @class([
                            'font-sans text-body-sm font-semibold',
                            'text-banner-sky' => $isPimpinan,
                            'text-primary' => ! $isPimpinan,
                        ])>{{ $labelPeran }}</span>

                        <div @class([
                            'mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 font-sans text-body-sm',
                            'text-slate-text-muted' => $isPimpinan,
                            'text-slate-text-secondary' => ! $isPimpinan,
                        ])>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[1rem]">mail</span>
                                {{ $user->email }}
                            </span>
                            @if ($user->nomor_telepon)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[1rem]">call</span>
                                    {{ $user->nomor_telepon }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div @class([
                    'flex items-center gap-3 rounded-xl px-4 py-3',
                    'bg-white/10 backdrop-blur-sm' => $isPimpinan,
                    'bg-surface-container-low' => ! $isPimpinan,
                ])>
                    <span class="material-symbols-outlined text-[1.5rem] {{ $isPimpinan ? 'text-banner-sky' : 'text-primary' }}">calendar_month</span>
                    <div class="flex flex-col">
                        <span class="font-sans text-label-sm uppercase tracking-wider {{ $isPimpinan ? 'text-white/70' : 'text-slate-text-muted' }}">Periode Aktif</span>
                        <span class="font-display text-headline-sm font-bold {{ $isPimpinan ? 'text-white' : 'text-slate-text-primary' }}">
                            {{ $ringkasan['periode_aktif']['code'] ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4 Kartu Statistik Ringkas (data riil database) --}}
        <div class="grid grid-cols-2 gap-space-md lg:grid-cols-4">
            @if ($isPimpinan)
                <x-ui.stat-tile
                    label="Menunggu ACC"
                    :nilai="(string) $ringkasan['menunggu_acc']"
                    ikon="pending_actions"
                    tone="amber"
                    catatan="Periode siap ditinjau"
                />
                <x-ui.stat-tile
                    label="Periode Disetujui"
                    :nilai="(string) $ringkasan['periode_disetujui']"
                    ikon="task_alt"
                    tone="emerald"
                    catatan="Telah diotorisasi direksi"
                />
                <x-ui.stat-tile
                    label="Armada Aktif"
                    :nilai="(string) $ringkasan['jumlah_truk']"
                    ikon="local_shipping"
                    tone="primary"
                    catatan="Unit truk operasional"
                />
                <x-ui.stat-tile
                    label="Supir Aktif"
                    :nilai="(string) $ringkasan['supir_aktif']"
                    ikon="badge"
                    tone="slate"
                    catatan="Karyawan terdaftar"
                />
            @else
                <x-ui.stat-tile
                    label="Supir Aktif"
                    :nilai="(string) $ringkasan['supir_aktif']"
                    ikon="group"
                    tone="primary"
                    catatan="Karyawan terdaftar aktif"
                />
                <x-ui.stat-tile
                    label="Unit Armada"
                    :nilai="(string) $ringkasan['jumlah_truk']"
                    ikon="local_shipping"
                    tone="emerald"
                    catatan="Truk operasional aktif"
                />
                <x-ui.stat-tile
                    label="Total Periode"
                    :nilai="(string) $ringkasan['jumlah_periode']"
                    ikon="date_range"
                    tone="slate"
                    catatan="Periode penggajian tercatat"
                />
                <x-ui.stat-tile
                    label="Menunggu ACC"
                    :nilai="(string) $ringkasan['menunggu_acc']"
                    ikon="pending_actions"
                    tone="amber"
                    catatan="Diajukan ke pimpinan"
                />
            @endif
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
                <span>Data Pribadi &amp; Kontak</span>
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

        {{-- Konten Tab 1: Data Pribadi & Kontak --}}
        <div x-show="tabAktif === 'data-pribadi'" class="max-w-4xl">

        {{-- Grid Utama: Informasi Pribadi & Keamanan Akun --}}
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- Form Informasi Pribadi & Kontak --}}
            <div class="rounded-2xl border border-card-border bg-card-bg p-6 shadow-card lg:col-span-7">
                <div class="mb-5 flex items-center justify-between border-b border-card-border pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <span class="material-symbols-outlined text-[1.25rem]">person</span>
                        </div>
                        <div>
                            <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Informasi Pribadi &amp; Kontak</h3>
                            <p class="font-sans text-body-sm text-slate-text-secondary">Perbarui nama lengkap, email masuk, nomor telepon, dan alamat.</p>
                        </div>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('profile.update') }}"
                    enctype="multipart/form-data"
                    class="flex flex-col gap-4"
                    x-data="{ fotoPratinjau: '', galatFoto: '' }"
                >
                    @csrf
                    @method('PATCH')

                    <div class="flex flex-col sm:flex-row items-center gap-4 rounded-xl border border-card-border bg-surface-container-low/60 p-4">
                        <div class="relative h-16 w-16 shrink-0 rounded-xl overflow-hidden border border-card-border bg-card-bg flex items-center justify-center font-bold text-primary">
                            {{-- Foto baru yang sedang dipilih langsung dipratinjau; jika belum ada, tampilkan foto tersimpan. --}}
                            <template x-if="fotoPratinjau !== ''">
                                <img :src="fotoPratinjau" alt="Pratinjau foto profil baru" class="h-full w-full object-cover">
                            </template>
                            @if (!empty($user->url_foto_profil))
                                <img x-show="fotoPratinjau === ''" src="{{ $user->url_foto_profil }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                            @else
                                <span x-show="fotoPratinjau === ''" class="text-xl">{{ $inisial }}</span>
                            @endif
                        </div>
                        <div class="flex-1 space-y-1 w-full">
                            <label for="foto_profil" class="font-sans text-label-md font-bold text-slate-text-primary">Foto Profil Pengguna</label>
                            <p class="font-sans text-xs text-slate-text-muted">Format berkas gambar JPG, PNG, atau WEBP (maks. 2 MB).</p>
                            <input
                                type="file"
                                id="foto_profil"
                                name="foto_profil"
                                accept="image/jpeg,image/png,image/webp"
                                class="w-full text-xs text-slate-text-secondary file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20"
                                x-on:change="
                                    const berkas = $event.target.files[0] ?? null;
                                    galatFoto = '';
                                    fotoPratinjau = '';
                                    if (! berkas) { return; }
                                    if (! ['image/jpeg', 'image/png', 'image/webp'].includes(berkas.type)) {
                                        galatFoto = 'Berkas harus berupa gambar JPG, PNG, atau WEBP.';
                                        $event.target.value = '';
                                        return;
                                    }
                                    if (berkas.size > 2 * 1024 * 1024) {
                                        galatFoto = 'Ukuran gambar melebihi 2 MB. Pilih berkas yang lebih kecil.';
                                        $event.target.value = '';
                                        return;
                                    }
                                    fotoPratinjau = URL.createObjectURL(berkas);
                                "
                            >
                            <p x-show="galatFoto !== ''" x-text="galatFoto" x-cloak class="font-sans text-xs font-semibold text-danger-red"></p>
                            <p x-show="fotoPratinjau !== ''" x-cloak class="font-sans text-xs font-semibold text-net-pay-emerald">
                                Pratinjau foto baru siap disimpan.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="name" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Nama Lengkap <span class="text-danger-red">*</span>
                            </label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                required
                                value="{{ old('name', $user->name) }}"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                            @error('name') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Email Akun <span class="text-danger-red">*</span>
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                required
                                value="{{ old('email', $user->email) }}"
                                class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                            @error('email') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="nomor_telepon" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                No. Telepon / WhatsApp
                            </label>
                            <div class="relative flex items-center">
                                <span class="pointer-events-none absolute left-3.5 font-sans text-body-sm font-bold text-slate-text-secondary">+62</span>
                                <input
                                    id="nomor_telepon"
                                    name="nomor_telepon"
                                    type="tel"
                                    inputmode="numeric" maxlength="12" placeholder="81289301123" pattern="8[0-9]{7,11}" title="Diawali angka 8, tanpa angka 0. Contoh: 81289301123"
                                    value="{{ \App\Support\FormatNomorTelepon::keLokal(old('nomor_telepon', $user->nomor_telepon)) }}"
                                    oninput="window.maskTeleponInput(event)"
                                    class="h-10 w-full rounded-lg border border-card-border bg-canvas-bg pl-14 pr-3.5 font-financial-table text-body-md tabular-nums text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                                >
                            </div>
                            <span class="font-sans text-label-sm text-slate-text-muted">Hanya angka dan diawali angka 8, tanpa angka 0.</span>
                            @error('nomor_telepon') <p class="font-sans text-body-sm text-danger-red">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                                Peran Sistem
                            </label>
                            <input
                                type="text"
                                readonly
                                value="{{ $labelPeran }}"
                                class="h-10 w-full cursor-not-allowed rounded-lg border border-card-border bg-surface-container-low px-3.5 font-sans text-body-md font-semibold text-slate-text-secondary"
                            >
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="alamat" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                            Alamat
                        </label>
                        <textarea
                            id="alamat"
                            name="alamat"
                            rows="2"
                            maxlength="255"
                            placeholder="Alamat domisili atau kantor..."
                            class="w-full rounded-lg border border-card-border bg-canvas-bg px-3.5 py-2 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >{{ old('alamat', $user->alamat) }}</textarea>
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
                        <p class="font-sans text-body-sm text-slate-text-secondary">Ubah kata sandi dengan verifikasi kata sandi lama.</p>
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
                        <label for="current_password" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                            Kata Sandi Lama <span class="text-danger-red">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <input
                                id="current_password"
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
                        <label for="password" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                            Kata Sandi Baru <span class="text-danger-red">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <input
                                id="password"
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
                        <label for="password_confirmation" class="font-sans text-label-sm font-semibold uppercase tracking-wider text-slate-text-secondary">
                            Ketik Ulang Kata Sandi Baru <span class="text-danger-red">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <input
                                id="password_confirmation"
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
</x-dynamic-component>
