<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">

    @php
        $pageTitle = html_entity_decode((string) ($title ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $author = $infoAuthor ?? config('app.author', []);
    @endphp
    <meta name="author" content="{{ $author['nama'] ?? 'Nabela Septiara' }} (NPM: {{ $author['npm'] ?? '2311050052' }})">
    <meta name="description" content="{{ $author['judul'] ?? 'Payroll System pada PT ASM Lampung' }}">

    <title>{{ $pageTitle !== '' ? $pageTitle.' — ' : '' }}{{ !empty($dataPerusahaan?->kode_perusahaan) ? $dataPerusahaan->kode_perusahaan.' • ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ !empty($dataPerusahaan?->url_logo) ? $dataPerusahaan->url_logo : asset('favicon.svg') }}">

    {{-- Anti-FOUC Persistensi Status Sidebar Admin (BRIEF Bab 6.2) --}}
    <script>
        (function () {
            try {
                if (localStorage.getItem('admin_sidebar_collapsed') === 'true') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    <style>
        @media (min-width: 1024px) {
            html.sidebar-collapsed .admin-sidebar-panel { width: 4.5rem !important; }
            html.sidebar-collapsed .admin-sidebar-hide { display: none !important; }
            html.sidebar-collapsed .admin-sidebar-item { justify-content: center !important; padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
            html.sidebar-collapsed .admin-main-wrapper { padding-left: 4.5rem !important; }
            html.sidebar-collapsed .admin-topbar-header { left: 4.5rem !important; }
        }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="bg-canvas-bg font-sans text-slate-text-primary antialiased"
    x-data="{
        sidebarOpen: false,
        calendarPanelOpen: false,
        sidebarCollapsed: localStorage.getItem('admin_sidebar_collapsed') === 'true',
        toggleSidebarDesktop() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            try {
                localStorage.setItem('admin_sidebar_collapsed', this.sidebarCollapsed ? 'true' : 'false');
            } catch (e) {}
            document.documentElement.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
        }
    }"
>
    <x-ui.toast-stack />
    <x-ui.lightbox />
    <x-ui.modal-author />

    <div class="min-h-screen bg-canvas-bg">
        <x-layout.admin-sidebar />
        <x-layout.calendar-panel />

        <div
            class="admin-main-wrapper min-h-screen transition-all duration-300"
            :class="sidebarCollapsed ? 'lg:pl-[4.5rem]' : 'lg:pl-[16.25rem]'"
        >
            <x-layout.topbar />

            <main class="w-full px-4 pb-10 pt-20 sm:px-6 lg:px-8">
                <div class="mx-auto flex w-full max-w-[100rem] flex-col gap-6">
                    @if (session('galat'))
                        <div
                            x-data="{ show: true }"
                            x-show="show"
                            x-transition
                            class="flex items-start gap-3 rounded-xl border border-danger-red/30 bg-danger-surface px-4 py-3 text-body-md text-slate-text-primary shadow-card"
                        >
                            <span class="material-symbols-outlined text-[1.25rem] text-danger-red">error</span>
                            <p class="flex-1">{{ session('galat') }}</p>
                            <button type="button" class="text-slate-text-muted hover:text-slate-text-primary" @click="show = false">
                                <span class="material-symbols-outlined text-[1.125rem]">close</span>
                            </button>
                        </div>
                    @endif

                    @if (session('status'))
                        <div
                            x-data="{ show: true }"
                            x-show="show"
                            x-transition
                            class="flex items-start gap-3 rounded-xl border border-net-pay-emerald/30 bg-net-pay-surface px-4 py-3 text-body-md text-slate-text-primary shadow-card"
                        >
                            <span class="material-symbols-outlined text-[1.25rem] text-net-pay-emerald">check_circle</span>
                            <p class="flex-1">{{ session('status') }}</p>
                            <button type="button" class="text-slate-text-muted hover:text-slate-text-primary" @click="show = false">
                                <span class="material-symbols-outlined text-[1.125rem]">close</span>
                            </button>
                        </div>
                    @endif

                    {{ $slot }}

                    {{-- Footer Copyright Global --}}
                    <footer class="mt-auto border-t border-card-border/80 pt-6 pb-2 text-center font-sans text-xs text-slate-text-muted space-y-1">
                        <div>
                            &copy; {{ date('Y') }} <strong>{{ $dataPerusahaan->nama_perusahaan ?? config('app.name') }}</strong> &bull; {{ $author['judul'] ?? 'Payroll System pada PT ASM Lampung' }}
                        </div>
                        <div>
                            Dikembangkan oleh
                            <button type="button" @click="$dispatch('buka-modal-author')" class="font-semibold text-primary hover:underline">
                                {{ $author['nama'] ?? 'Nabela Septiara' }}
                            </button>
                            (NPM: {{ $author['npm'] ?? '2311050052' }})
                        </div>
                    </footer>
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
