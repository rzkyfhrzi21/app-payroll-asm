/**
 * ============================================================================
 * MODUL: TABEL AJAX SERVER-SIDE (TANPA QUERY STRING)
 * ============================================================================
 *
 * Analogi Sederhana:
 * Ibarat memesan arsip lewat telepon: kita sebutkan kriteria (cari/filter),
 * petugas mengambilkan, layar tidak perlu dimuat ulang dan URL tetap bersih.
 *
 * Alur Kerja Bernomor:
 * (1) Saat init, ambil state filter dari sessionStorage (bila ada) lalu kirim
 *     permintaan pertama agar hasil tetap mengikuti pilihan terakhir pengguna.
 * (2) Setiap perubahan filter/cari (debounce 300ms) memicu permintaan POST.
 * (3) Respons berisi rows_html + meta; isi <tbody> diganti tanpa reload.
 * (4) Nomor halaman dirender ulang sesuai meta terbaru.
 * (5) State filter disimpan kembali ke sessionStorage.
 */

const formatAngka = (nilai) => Number(nilai || 0).toLocaleString('id-ID');

export default function registerTabelAjax(Alpine) {
    Alpine.data('tabelAjaxServer', (konfigurasi = {}) => ({
        entitas: konfigurasi.entitas || '',
        endpoint: konfigurasi.endpoint || '',
        targetId: konfigurasi.targetId || '',
        label: konfigurasi.label || 'data',
        kolom: Number(konfigurasi.kolom || 10),
        kunciSimpan: konfigurasi.kunciSimpan || '',
        perHalaman: Number(konfigurasi.perHalamanAwal || 25),
        sedangMemuat: false,
        pesanGalat: '',
        cari: '',
        filter: konfigurasi.filterAwal || {},
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: Number(konfigurasi.perHalamanAwal || 25),
            total: 0,
            from: 0,
            to: 0,
        },
        timerCari: null,
        timerFilter: null,
        pendengarAlpineTerpasang: false,

        init() {
            this.pulihkanState();
            this.pasangPendengarFilter();
            this.pasangPendengarReset();
            this.perbaruiTombolReset();
            this.muatHalaman(1, true);
        },

        /**
         * Tampilkan tombol "Reset Filter" hanya ketika ada filter yang berbeda
         * dari nilai awal halaman. Diperlukan karena filter kini berjalan tanpa
         * memuat ulang halaman (atribut SSR saja akan basi).
         */
        perbaruiTombolReset() {
            const wadah = this.ambilWadah();
            const nilaiAwal = konfigurasi.filterAwal || {};
            const adaKataKunci = String(this.cari || '').trim() !== '';
            const adaFilterBerbeda = Object.entries(this.filter).some(([nama, nilai]) => {
                const teksNilai = String(nilai ?? '');

                return teksNilai !== String(nilaiAwal[nama] ?? '')
                    && teksNilai !== ''
                    && teksNilai !== 'all';
            });
            const tampil = adaKataKunci || adaFilterBerbeda;

            wadah.querySelectorAll('[data-tombol-reset-filter]').forEach((tombol) => {
                tombol.classList.toggle('hidden', !tampil);
            });
        },

        /**
         * Tombol "Reset Filter" di halaman memanggil window.resetTabelAjax().
         * Tanpa ini, menekan Reset hanya memuat ulang halaman lalu memulihkan
         * filter lama dari sessionStorage (terlihat seperti tombol rusak).
         */
        pasangPendengarReset() {
            this.pendengarReset = (peristiwa) => {
                const entitas = peristiwa?.detail?.entitas;
                if (entitas && entitas !== this.entitas) {
                    return;
                }
                this.resetFilterTabel();
            };

            window.addEventListener('tabel-ajax:reset', this.pendengarReset);
        },

        get teksRentang() {
            if (this.meta.total === 0) {
                return '0–0';
            }

            return `${formatAngka(this.meta.from)}–${formatAngka(this.meta.to)}`;
        },

        get daftarNomorHalaman() {
            const total = Number(this.meta.last_page || 1);
            const aktif = Number(this.meta.current_page || 1);
            const hasil = [];

            for (let halaman = 1; halaman <= total; halaman++) {
                if (total <= 7 || halaman === 1 || halaman === total || Math.abs(halaman - aktif) <= 1) {
                    hasil.push(halaman);
                }
            }

            return hasil;
        },

        formatAngka(nilai) {
            return formatAngka(nilai);
        },

        pulihkanState() {
            if (!this.kunciSimpan) {
                return;
            }

            try {
                const mentah = window.sessionStorage.getItem(this.kunciSimpan);
                if (!mentah) {
                    return;
                }

                const state = JSON.parse(mentah);
                if (state && typeof state === 'object') {
                    this.cari = String(state.cari || '');

                    // Nilai filter yang ditentukan server (mis. periode halaman)
                    // adalah PATOKAN dan tidak boleh ditimpa state lama. Filter
                    // biasa (status, kategori, dst.) tetap dipulihkan.
                    const nilaiAwal = konfigurasi.filterAwal || {};
                    const filterGabungan = { ...this.filter };

                    Object.entries(state.filter || {}).forEach(([nama, nilai]) => {
                        const patokan = nilaiAwal[nama];
                        const terkunci = patokan !== undefined
                            && patokan !== null
                            && String(patokan) !== ''
                            && String(patokan) !== 'all';

                        if (!terkunci) {
                            filterGabungan[nama] = nilai;
                        }
                    });

                    this.filter = filterGabungan;
                    this.perHalaman = Number(state.perHalaman || this.perHalaman);
                    this.sinkronkanKeInput();
                }
            } catch (_) {
                // sessionStorage tidak tersedia atau isinya rusak: abaikan.
            }
        },

        simpanState() {
            if (!this.kunciSimpan) {
                return;
            }

            try {
                window.sessionStorage.setItem(this.kunciSimpan, JSON.stringify({
                    cari: this.cari,
                    filter: this.filter,
                    perHalaman: this.perHalaman,
                }));
            } catch (_) {
                // Mode privat / kuota penuh: abaikan.
            }
        },

        /**
         * Tulis state ke elemen filter di halaman (input cari + select filter),
         * supaya tampilan kontrol ikut sesuai nilai yang dipulihkan.
         */
        /**
         * Wadah pencarian kontrol filter.
         * Prioritas: elemen bertanda data-wadah-tabel="{entitas}" (bisa berada di
         * kartu filter terpisah dari footer), lalu leluhur terdekat, lalu document.
         */
        ambilWadah() {
            if (this.entitas) {
                const khusus = document.querySelector(`[data-wadah-tabel="${this.entitas}"]`);
                if (khusus) {
                    return khusus;
                }
            }

            return this.$el?.closest('[data-wadah-tabel]') || document;
        },

        sinkronkanKeInput() {
            const wadah = this.ambilWadah();

            const cariElemen = (pemilih) => Array.from(wadah.querySelectorAll(pemilih))
                .find((elemen) => !elemen.closest('[role="dialog"]') && elemen.dataset.noTabelAjax === undefined) || null;

            const inputCari = cariElemen('input[name="cari"], [data-filter-cari]');
            if (inputCari && inputCari.value !== this.cari) {
                inputCari.value = this.cari;
            }

            Object.entries(this.filter).forEach(([nama, nilai]) => {
                const elemen = cariElemen(`[name="${nama}"], [data-filter-nama="${nama}"]`);
                if (elemen && String(elemen.value) !== String(nilai ?? '')) {
                    elemen.value = nilai;
                }
            });
        },

        /**
         * Pasang pendengar pada kontrol filter nyata di halaman (bukan form submit).
         */
        pasangPendengarFilter() {
            const wadah = this.ambilWadah();

            const inputCari = wadah.querySelector('input[name="cari"], [data-filter-cari]');
            if (inputCari && inputCari.dataset.tabelAjaxTerpasang !== '1') {
                inputCari.dataset.tabelAjaxTerpasang = '1';
                inputCari.addEventListener('input', () => {
                    this.cari = String(inputCari.value || '').trim();
                    window.clearTimeout(this.timerCari);
                    this.timerCari = window.setTimeout(() => this.muatHalaman(1), 300);
                });
            }

            // Select dan input tanggal: nilai filter dikirim saat berubah.
            // Kontrol di dalam modal/form CRUD dikecualikan agar tidak ikut memicu
            // pemuatan tabel ketika pengguna mengisi formulir.
            wadah.querySelectorAll('select[name], input[type="date"][name], input[type="month"][name], [data-filter-pilih]').forEach((elemen) => {
                if (elemen.dataset.tabelAjaxTerpasang === '1' || elemen.dataset.noSelect2 !== undefined && elemen.name === '') {
                    return;
                }

                if (elemen.closest('[role="dialog"]')) {
                    return;
                }

                // Kontrol navigasi halaman (mis. pemilih periode) tidak boleh
                // memicu permintaan AJAX; ia mengganti konteks seluruh halaman.
                if (elemen.dataset.noTabelAjax !== undefined) {
                    return;
                }

                const nama = elemen.getAttribute('name');
                if (!nama) {
                    return;
                }

                elemen.dataset.tabelAjaxTerpasang = '1';
                elemen.addEventListener('change', () => {
                    this.filter = { ...this.filter, [nama]: elemen.value };
                    window.clearTimeout(this.timerFilter);
                    this.timerFilter = window.setTimeout(() => this.muatHalaman(1), 120);
                });
            });
        },

        /**
         * Pasang HTML baris ke <tbody> lalu aktifkan direktif Alpine di dalamnya.
         */
        async terapkanBarisAlpine(tbody, html) {
            const alpine = window.Alpine;

            if (alpine && typeof alpine.destroyTree === 'function') {
                alpine.destroyTree(tbody);
            }

            tbody.innerHTML = html;

            if (!alpine) {
                return;
            }

            if (typeof alpine.initTree === 'function' && alpine.version) {
                // Alpine sudah berjalan: direktif bisa langsung diinisialisasi.
                alpine.initTree(tbody);
                return;
            }

            // Alpine belum selesai start: tunggu event alpine:initialized.
            if (!this.pendengarAlpineTerpasang) {
                this.pendengarAlpineTerpasang = true;
                window.addEventListener('alpine:initialized', () => {
                    const sasaran = document.getElementById(this.targetId);
                    if (sasaran) {
                        window.Alpine?.initTree(sasaran);
                    }
                }, { once: true });
            }
        },

        async resetFilterTabel() {
            this.cari = '';
            this.filter = { ...(konfigurasi.filterAwal || {}) };
            this.perHalaman = Number(konfigurasi.perHalamanAwal || 25);
            this.sinkronkanKeInput();
            this.perbaruiTombolReset();
            this.simpanState();
            await this.muatHalaman(1);
        },

        async muatHalaman(halaman = 1, senyap = false) {
            if (!this.endpoint || !this.targetId || this.sedangMemuat) {
                return;
            }

            const nomorHalaman = Math.min(Math.max(Number(halaman) || 1, 1), Math.max(1, Number(this.meta.last_page || 1)));
            this.sedangMemuat = true;
            this.pesanGalat = '';

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const respons = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        entitas: this.entitas,
                        cari: this.cari,
                        filter: this.filter,
                        kolom: this.kolom,
                        halaman: nomorHalaman,
                        per_halaman: this.perHalaman,
                    }),
                });

                const muatan = await respons.json().catch(() => ({}));

                if (!respons.ok || muatan.success === false) {
                    throw new Error(muatan.message || `Gagal memuat ${this.label}.`);
                }

                const tbody = document.getElementById(this.targetId);
                if (tbody) {
                    // Baris hasil AJAX berisi direktif Alpine (x-model centang massal,
                    // dropdown aksi, @click). Tanpa initTree, direktif itu tidak aktif.
                    await this.terapkanBarisAlpine(tbody, muatan.data.rows_html || '');
                }

                this.meta = { ...this.meta, ...(muatan.data.meta || {}) };
                this.perbaruiTombolReset();
                this.simpanState();
            } catch (galat) {
                this.pesanGalat = galat?.message || `Gagal memuat ${this.label}. Silakan coba lagi.`;

                if (!senyap) {
                    window.tampilkanToast?.({
                        type: 'error',
                        title: 'Gagal Memuat Data Tabel',
                        message: this.pesanGalat,
                    });
                }
            } finally {
                this.sedangMemuat = false;
            }
        },
    }));

    // Helper global: dipakai tombol "Reset Filter" di halaman mana pun.
    // Entitas opsional; bila kosong, semua tabel yang mendengarkan ikut mereset.
    window.resetTabelAjax = (entitas = null) => {
        window.dispatchEvent(new CustomEvent('tabel-ajax:reset', { detail: { entitas } }));
    };
}
