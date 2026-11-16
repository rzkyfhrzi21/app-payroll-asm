<x-guest-layout title="Atur Ulang Kata Sandi">
    <div class="relative flex min-h-screen w-full flex-col items-center justify-center overflow-hidden bg-sidebar-dark p-4 sm:p-6 lg:p-8">
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(#38BDF8 1px, transparent 1px); background-size: 20px 20px;"></div>

        <div class="relative z-10 mb-6 flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep text-white shadow-hero">
                <span class="material-symbols-outlined text-[1.5rem]">local_shipping</span>
            </div>
            <div class="flex flex-col">
                <span class="font-display text-headline-sm font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                <span class="font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">Pemulihan Akun</span>
            </div>
        </div>

        <div class="relative z-10 w-full max-w-lg rounded-2xl bg-card-bg p-6 shadow-2xl sm:p-8">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 font-sans text-label-sm font-bold uppercase tracking-wider text-primary">
                <span class="material-symbols-outlined text-[0.95rem]">key</span>
                Kata Sandi Baru
            </span>

            <h1 class="mt-3 font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">
                Atur Ulang Kata Sandi
            </h1>
            <p class="mt-1 font-sans text-body-md text-slate-text-secondary">
                Buat kata sandi baru yang kuat lalu ketik ulang untuk konfirmasi.
            </p>

            @if ($errors->any())
                <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-danger-red/30 bg-danger-surface px-4 py-3 font-sans text-body-sm text-slate-text-primary">
                    <span class="material-symbols-outlined mt-0.5 text-[1.125rem] text-danger-red">error</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" class="mt-6 flex flex-col gap-4">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="font-sans text-label-md font-semibold text-slate-text-primary">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="h-11 w-full rounded-xl border border-card-border bg-canvas-bg px-4 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="font-sans text-label-md font-semibold text-slate-text-primary">Kata Sandi Baru</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="h-11 w-full rounded-xl border border-card-border bg-canvas-bg px-4 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password_confirmation" class="font-sans text-label-md font-semibold text-slate-text-primary">Ketik Ulang Kata Sandi Baru</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="h-11 w-full rounded-xl border border-card-border bg-canvas-bg px-4 font-sans text-body-md text-slate-text-primary focus:border-primary focus:bg-card-bg focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                </div>

                <button
                    type="submit"
                    class="mt-2 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-primary-container px-5 font-display text-headline-sm text-on-primary shadow-md transition-all hover:bg-primary"
                >
                    <span class="material-symbols-outlined text-[1.125rem]">save</span>
                    Simpan Kata Sandi Baru
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
