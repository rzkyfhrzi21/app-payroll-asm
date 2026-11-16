export default function registerSearch(Alpine) {
    Alpine.data('globalSearch', (panduan = []) => ({
        kataKunci: '',
        hasil: [],
        terbuka: false,
        memuat: false,
        terpilih: 0,
        panduan: Array.isArray(panduan) ? panduan : [],
        jumlahHalaman: Array.isArray(panduan) ? panduan.length : 0,
        pengendaliWaktu: null,

        buka() {
            this.terbuka = true;
        },

        tutup() {
            this.terbuka = false;
            this.terpilih = 0;
        },

        async cari() {
            const kunci = this.kataKunci.trim();

            if (this.pengendaliWaktu) {
                clearTimeout(this.pengendaliWaktu);
            }

            if (kunci === '') {
                this.hasil = [];
                this.memuat = false;
                return;
            }

            this.memuat = true;
            this.terbuka = true;

            this.pengendaliWaktu = setTimeout(async () => {
                try {
                    const respons = await fetch(
                        `/pencarian?q=${encodeURIComponent(kunci)}`,
                        { headers: { Accept: 'application/json' } },
                    );

                    if (!respons.ok) {
                        throw new Error('Pencarian gagal');
                    }

                    const data = await respons.json();
                    this.hasil = Array.isArray(data.hasil) ? data.hasil : [];
                    this.terpilih = 0;
                } catch (_) {
                    this.hasil = [];
                } finally {
                    this.memuat = false;
                }
            }, 120);
        },

        pilihBawah() {
            if (this.hasil.length === 0) {
                return;
            }
            this.terpilih = (this.terpilih + 1) % this.hasil.length;
        },

        pilihAtas() {
            if (this.hasil.length === 0) {
                return;
            }
            this.terpilih = (this.terpilih - 1 + this.hasil.length) % this.hasil.length;
        },

        bukaTerpilih() {
            const tujuan = this.hasil[this.terpilih];
            if (tujuan && tujuan.url) {
                window.location.href = tujuan.url;
            }
        },
    }));
}
