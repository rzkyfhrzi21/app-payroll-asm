export default function registerLightbox(Alpine) {
    Alpine.data('lightboxPreviewManager', () => ({
        terbuka: false,
        url: '',
        judul: '',
        caption: '',
        jenis: 'image',
        galeri: [],
        indeksAktif: 0,

        buka(detail = {}) {
            const daftar = Array.isArray(detail?.galeri)
                ? detail.galeri.filter((item) => typeof item === 'string' && item.trim() !== '')
                : [];

            if (daftar.length > 0) {
                this.galeri = daftar;
                const indeksDiminta = Number.isInteger(detail.indeks)
                    ? detail.indeks
                    : (detail.url ? daftar.indexOf(detail.url) : 0);
                this.indeksAktif = indeksDiminta >= 0 && indeksDiminta < daftar.length ? indeksDiminta : 0;
                this.url = daftar[this.indeksAktif];
            } else if (detail && detail.url) {
                this.galeri = [detail.url];
                this.indeksAktif = 0;
                this.url = detail.url;
            } else {
                return;
            }

            this.judul = detail.judul || detail.title || 'Pratinjau Media';
            this.caption = detail.caption || '';
            this.perbaruiJenis(detail.jenis);
            this.terbuka = true;
        },

        perbaruiJenis(jenisEksplisit = null) {
            const apakahVideo = /\.(mp4|webm|ogg)$/i.test(this.url) || jenisEksplisit === 'video';
            this.jenis = apakahVideo ? 'video' : 'image';
        },

        pilihIndeks(indeks) {
            if (!Array.isArray(this.galeri) || this.galeri.length === 0) return;
            const batas = this.galeri.length;
            const target = ((Number(indeks) % batas) + batas) % batas;
            this.indeksAktif = target;
            this.url = this.galeri[target];
            this.perbaruiJenis();
        },

        sebelumnya() {
            if (this.galeri.length <= 1) return;
            this.pilihIndeks(this.indeksAktif - 1);
        },

        berikutnya() {
            if (this.galeri.length <= 1) return;
            this.pilihIndeks(this.indeksAktif + 1);
        },

        tutup() {
            this.terbuka = false;
            this.url = '';
            this.galeri = [];
            this.indeksAktif = 0;
        },
    }));
}
