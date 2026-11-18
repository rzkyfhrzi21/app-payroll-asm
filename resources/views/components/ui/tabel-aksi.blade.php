<!-- ==============================================================================
     KOMENTAR ORANG TUA (LOGIKA ALUR TAMPILAN TOMBOL AKSI TABEL):
     - Tujuan Komponen: Menggantikan deretan ikon aksi terpisah (Detail/Edit/Hapus/Cetak)
       pada setiap baris tabel menjadi SATU tombol "Aksi" berisi menu dropdown.
     - Analogi Dunia Nyata: Seperti satu laci serbaguna di meja kerja: daripada menaruh
       empat alat terpisah di atas meja (makan tempat), semuanya dimasukkan ke satu laci
       yang dibuka hanya saat dibutuhkan.
     - Kenapa memakai position: fixed:
       Tabel dibungkus `overflow-x-auto`/`overflow-hidden` sehingga menu `absolute`
       akan TERPOTONG pada baris-baris terakhir. Posisi menu dihitung dari
       getBoundingClientRect() lalu dirender `fixed` agar selalu terlihat utuh.
     - Pemakaian:
         Panggil komponen "ui.tabel-aksi", lalu isi slot-nya dengan komponen
         "ui.tabel-aksi-item" untuk setiap pilihan aksi.
         PENTING: jangan menulis contoh tag komponen di dalam komentar Blade,
         karena Blade tetap mengompilasi tag tersebut dan memicu rekursi tak terbatas.
     ============================================================================== -->
@props([
    'label' => 'Menu Aksi',
    'lebar' => 224,
])

<div
    class="relative inline-block text-left"
    x-data="{
        menuAksiTerbuka: false,
        posisiMenuAksi: { top: '0px', left: '0px' },
        bukaMenuAksi(event) {
            const kotak = event.currentTarget.getBoundingClientRect();
            const lebarMenu = {{ (int) $lebar }};
            const batasKanan = window.innerWidth - lebarMenu - 8;
            const kiri = Math.max(8, Math.min(batasKanan, kotak.right - lebarMenu));
            const tinggiPerkiraan = 240;
            const lebihBaikKeAtas = (kotak.bottom + tinggiPerkiraan) > window.innerHeight && kotak.top > tinggiPerkiraan;
            const atas = lebihBaikKeAtas ? Math.max(8, kotak.top - tinggiPerkiraan) : (kotak.bottom + 6);
            this.posisiMenuAksi = { top: atas + 'px', left: kiri + 'px' };
            this.menuAksiTerbuka = !this.menuAksiTerbuka;
        },
    }"
    @click.outside="menuAksiTerbuka = false"
    @keydown.escape.window="menuAksiTerbuka = false"
>
    <button
        type="button"
        @click="bukaMenuAksi($event)"
        class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-card-border bg-card-bg px-3 font-sans text-label-md font-semibold text-slate-text-primary shadow-2xs transition-all hover:border-primary/40 hover:bg-surface-container-low focus:outline-none focus:ring-2 focus:ring-primary/40"
        :aria-expanded="menuAksiTerbuka"
        aria-haspopup="menu"
        title="{{ $label }}"
    >
        <span>Pilih Aksi</span>
        <span class="material-symbols-outlined text-[1.25rem] text-slate-text-secondary transition-transform duration-150" :class="menuAksiTerbuka ? 'rotate-180' : ''">expand_more</span>
    </button>

    <div
        x-show="menuAksiTerbuka"
        x-cloak
        x-transition.origin.top.right
        :style="'position: fixed; top: ' + posisiMenuAksi.top + '; left: ' + posisiMenuAksi.left + '; width: {{ (int) $lebar }}px;'"
        class="z-[70] overflow-hidden rounded-xl border border-card-border bg-card-bg p-1.5 text-left shadow-drawer"
        role="menu"
    >
        {{ $slot }}
    </div>
</div>
