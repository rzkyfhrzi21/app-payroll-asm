<!-- ======================================================
     KOMPONEN UI: PENINJAU FOTO LAYAR PENUH (FULLSCREEN LIGHTBOX)
     
     Analogi Sederhana:
     Komponen ini ibarat "Kaca Pembesar / Proyektor Foto Layar Lebar".
     Saat admin atau supir mengklik gambar thumbnail surat jalan atau nota belanja,
     foto tersebut akan diperbesar memenuhi layar dengan latar gelap, lengkap dengan
     tombol geser kiri/kanan jika surat jalan memiliki banyak lembaran foto.
====================================================== -->
<div
    x-data="lightboxPreviewManager()"
    x-show="terbuka"
    x-cloak
    @open-lightbox.window="buka($event.detail)"
    @keydown.escape.window="tutup()"
    @keydown.arrow-left.window="if (terbuka) sebelumnya()"
    @keydown.arrow-right.window="if (terbuka) berikutnya()"
    style="z-index: 98;"
    class="fixed inset-0 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-md"
    @click.self="tutup()"
    role="dialog"
    aria-modal="true"
    aria-label="Pratinjau Media"
>
    {{-- Tombol Silang (×) di Pojok Kanan Atas --}}
    <button
        type="button"
        @click="tutup()"
        class="fixed right-5 top-5 z-20 flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white shadow-xl backdrop-blur-md transition-all hover:border-rose-500 hover:bg-rose-600"
        aria-label="Tutup Pratinjau"
    >
        <span class="material-symbols-outlined text-[24px]">close</span>
    </button>

    <div class="relative flex max-h-[92vh] w-full max-w-4xl flex-col items-center justify-center overflow-hidden rounded-2xl border border-white/15 bg-slate-900/90 p-4 shadow-2xl">
        <div
            x-show="galeri.length > 1"
            x-cloak
            class="mb-2 flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 font-sans text-xs font-semibold text-white"
        >
            <span class="material-symbols-outlined text-[16px]">photo_library</span>
            <span>Foto <span x-text="indeksAktif + 1"></span> / <span x-text="galeri.length"></span></span>
        </div>

        <div class="relative flex w-full items-center justify-center">
            <button
                type="button"
                x-show="galeri.length > 1"
                x-cloak
                @click.stop="sebelumnya()"
                class="absolute left-2 z-10 flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-slate-950/75 text-white shadow-lg backdrop-blur-sm transition-all hover:bg-primary"
                aria-label="Foto Sebelumnya"
            >
                <span class="material-symbols-outlined text-[22px]">chevron_left</span>
            </button>

            <template x-if="jenis === 'video' && url">
                <video
                    :src="url"
                    controls
                    autoplay
                    class="max-h-[72vh] w-full rounded-xl bg-black"
                ></video>
            </template>

            <template x-if="jenis !== 'video' && url">
                <img
                    :src="url"
                    :alt="judul || 'Pratinjau Gambar'"
                    class="max-h-[70vh] w-auto max-w-full rounded-xl object-contain shadow-lg"
                >
            </template>

            <button
                type="button"
                x-show="galeri.length > 1"
                x-cloak
                @click.stop="berikutnya()"
                class="absolute right-2 z-10 flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-slate-950/75 text-white shadow-lg backdrop-blur-sm transition-all hover:bg-primary"
                aria-label="Foto Berikutnya"
            >
                <span class="material-symbols-outlined text-[22px]">chevron_right</span>
            </button>
        </div>

        <div
            x-show="galeri.length > 1"
            x-cloak
            class="mt-3 flex max-w-full items-center gap-2 overflow-x-auto pb-1"
        >
            <template x-for="(itemUrl, idx) in galeri" :key="idx">
                <button
                    type="button"
                    @click.stop="pilihIndeks(idx)"
                    :class="idx === indeksAktif ? 'ring-2 ring-primary opacity-100 scale-105' : 'opacity-60 hover:opacity-90'"
                    class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-white/20 bg-slate-950 transition-all"
                    :aria-label="'Pilih Foto ' + (idx + 1)"
                >
                    <img :src="itemUrl" alt="Thumbnail" class="h-full w-full object-cover">
                </button>
            </template>
        </div>

        <div x-show="judul || caption" class="mt-3 text-center">
            <h4 class="font-display text-sm font-bold text-white" x-text="judul"></h4>
            <p class="mt-0.5 font-sans text-xs text-slate-300" x-text="caption"></p>
        </div>
    </div>
</div>
