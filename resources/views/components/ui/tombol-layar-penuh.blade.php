<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN TOMBOL FULLSCREEN):
     - Tujuan Komponen: Tombol di topbar untuk memperbesar tampilan aplikasi ke satu layar penuh
       (Fullscreen API bawaan browser), sangat berguna bagi operator timbangan atau kasir gaji.
     - Analogi Dunia Nyata: Seperti saklar proyektor di ruang pemantauan logistik: sekali pencet,
       semua bilah menu browser tersembunyi sehingga layar fokus penuh ke data penting perusahaan.
     - Interaksi & Event:
       * Menggunakan Alpine.js dan memanggil `document.documentElement.requestFullscreen()`.
       * Memantau event browser 'fullscreenchange' agar ikon berubah otomatis saat tombol ESC ditekan.
     ============================================================================== -->
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
