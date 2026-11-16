<x-app-layout :title="$title ?? 'Halaman Admin'">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center gap-1 font-sans text-label-sm uppercase tracking-wider text-slate-text-muted">
                <span>Dashboard</span>
                <span class="material-symbols-outlined text-[0.875rem]">chevron_right</span>
                <span class="mark-label font-semibold">Admin / HRD</span>
            </div>
            <h1 class="font-display text-headline-lg tracking-tight text-slate-text-primary">{{ $title ?? 'Halaman Admin' }}</h1>
            <p class="max-w-3xl font-sans text-body-md text-slate-text-secondary">Halaman ini sudah memiliki route dan controller, tetapi desain antarmukanya belum tersedia pada paket Stitch v1.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('admin.payroll.index') }}"
                class="flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2 font-sans text-body-md font-semibold text-on-primary shadow-sm transition-all hover:bg-primary"
            >
                <span class="material-symbols-outlined text-[1.125rem]">arrow_back</span>
                Kembali ke Payroll
            </a>
            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-surface-container-lowest px-4 py-2 font-sans text-body-md font-semibold text-slate-text-primary shadow-sm transition-all hover:bg-surface-container-low"
                onclick="window.alert('Desain halaman ini akan diturunkan dari komponen tabel, filter, dan drawer yang sudah ada pada halaman Admin lain.')"
            >
                <span class="material-symbols-outlined text-[1.125rem] text-slate-text-secondary">design_services</span>
                Rencana Desain
            </button>
        </div>
    </div>

    <div class="flex flex-col items-center justify-center gap-5 rounded-xl bg-card-bg p-space-2xl text-center shadow-card">
        <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-banner-sky via-banner-indigo to-banner-deep text-on-primary shadow-hero">
            <span class="material-symbols-outlined text-[2.5rem]">draw</span>
        </div>

        <div class="flex max-w-xl flex-col gap-2">
            <h2 class="font-display text-headline-lg font-bold tracking-tight text-slate-text-primary">Desain Belum Tersedia</h2>
            <p class="font-sans text-body-md text-slate-text-secondary">
                {{ $description ?? 'Halaman ini belum memiliki desain Stitch. Tombol menu diarahkan ke alert sampai desain disetujui agar tidak ada layout yang dikarang sendiri.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-2">
            <span class="rounded-full bg-warning-surface px-3 py-1 font-sans text-label-sm font-semibold text-warning-amber">Status: Backlog Desain</span>
            <span class="rounded-full bg-surface-container px-3 py-1 font-sans text-label-sm font-semibold text-slate-text-secondary">Route &amp; Controller: Siap</span>
            <span class="rounded-full bg-net-pay-surface px-3 py-1 font-sans text-label-sm font-semibold text-net-pay-emerald">Navigasi: Aktif</span>
        </div>

        <div class="mt-2 w-full max-w-2xl rounded-xl bg-surface-container-low p-4 text-left">
            <div class="flex items-start gap-2.5">
                <span class="material-symbols-outlined mt-0.5 text-[1.25rem] text-primary">info</span>
                <p class="font-sans text-body-sm text-slate-text-secondary">
                    Sesuai kesepakatan, halaman tanpa desain tidak dibuat layout baru. Implementasi akan menggunakan ulang komponen yang sudah ada
                    (header, kartu ringkasan, filter bar, tabel, drawer, dan modal) setelah pola finalnya disetujui.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
