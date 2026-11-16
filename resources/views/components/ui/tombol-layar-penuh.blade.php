@props([
    'judul' => 'Layar Penuh',
])

{{--
    Tombol layar penuh (Fullscreen API) yang dipakai pada seluruh topbar
    (Admin, Pimpinan, Supir). Status ikon mengikuti event browser sehingga
    tetap benar walau pengguna keluar dengan tombol ESC.
--}}
<button
    type="button"
    x-data="{
        layarPenuh: false,
        didukung: document.fullscreenEnabled === true,
        async alihkan() {
            try {
                if (! document.fullscreenElement) {
                    await document.documentElement.requestFullscreen();
                } else {
                    await document.exitFullscreen();
                }
            } catch (e) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Layar Penuh Tidak Tersedia',
                    action: 'FULLSCREEN_FAILED',
                    status: 'FAILED',
                    message: 'Peramban menolak permintaan layar penuh. Coba tekan F11 sebagai gantinya.',
                });
            }
        },
    }"
    x-init="
        layarPenuh = document.fullscreenElement !== null;
        document.addEventListener('fullscreenchange', () => { layarPenuh = document.fullscreenElement !== null; });
    "
    x-show="didukung"
    @click="alihkan()"
    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-text-secondary transition-colors hover:bg-surface-container-low hover:text-slate-text-primary"
    :title="layarPenuh ? 'Keluar dari layar penuh' : '{{ $judul }}'"
    :aria-label="layarPenuh ? 'Keluar dari layar penuh' : '{{ $judul }}'"
    aria-pressed="false"
>
    <span class="material-symbols-outlined text-[1.25rem]" x-text="layarPenuh ? 'fullscreen_exit' : 'fullscreen'">fullscreen</span>
</button>
