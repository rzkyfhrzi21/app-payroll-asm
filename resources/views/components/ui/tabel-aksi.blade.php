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
        posisiMenuAksi: { top: '0px', left: '0px', tinggiMaks: 'none' },
        bukaMenuAksi(event) {
            if (this.menuAksiTerbuka) {
                this.menuAksiTerbuka = false;
                return;
            }

            const tombol = event.currentTarget.getBoundingClientRect();
            const lebarMenu = {{ (int) $lebar }};
            const jarak = 6;
            const batasKanan = window.innerWidth - lebarMenu - 8;

            // Posisi awal: menempel tepat di bawah tombol. Menu diukur setelah
            // dirender, baru digeser bila ruang di bawah tidak cukup.
            this.posisiMenuAksi = {
                top: (tombol.bottom + jarak) + 'px',
                left: Math.max(8, Math.min(batasKanan, tombol.right - lebarMenu)) + 'px',
                tinggiMaks: 'none',
            };
            this.menuAksiTerbuka = true;

            this.$nextTick(() => {
                const menu = this.$refs.menuAksi;
                if (!menu) return;

                const tinggiMenu = menu.offsetHeight;
                const ruangBawah = window.innerHeight - tombol.bottom - jarak - 8;
                const ruangAtas = tombol.top - jarak - 8;

                let atas = tombol.bottom + jarak;
                let tinggiMaks = 'none';

                if (tinggiMenu > ruangBawah) {
                    if (tinggiMenu <= ruangAtas) {
                        // Cukup ruang di atas: tempel ke sisi atas tombol.
                        atas = tombol.top - jarak - tinggiMenu;
                    } else {
                        // Tidak muat di kedua sisi: pakai sisi yang lebih lapang,
                        // batasi tinggi menu agar isinya tetap bisa digulir.
                        const pakaiAtas = ruangAtas > ruangBawah;
                        atas = pakaiAtas ? 8 : tombol.bottom + jarak;
                        tinggiMaks = Math.max(120, pakaiAtas ? ruangAtas : ruangBawah) + 'px';
                    }
                }

                this.posisiMenuAksi = {
                    ...this.posisiMenuAksi,
                    top: Math.round(Math.max(8, atas)) + 'px',
                    tinggiMaks,
                };
            });
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
        x-ref="menuAksi"
        x-transition.origin.top.right
        :style="'position: fixed; top: ' + posisiMenuAksi.top + '; left: ' + posisiMenuAksi.left + '; width: {{ (int) $lebar }}px; max-height: ' + posisiMenuAksi.tinggiMaks + ';'"
        class="z-[70] overflow-y-auto rounded-xl border border-card-border bg-card-bg p-1.5 text-left shadow-drawer"
        role="menu"
        @click="menuAksiTerbuka = false"
    >
        {{ $slot }}
    </div>
</div>
