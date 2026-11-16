import { rupiah, parseNumber } from '../../core/helpers.js';

export default function registerDriverTrips(Alpine) {
    Alpine.data('driverTrips', (trips = [], profile = {}, payroll = {}) => ({
        search: '',
        route: 'all',
        status: 'all',
        modalKonfirmasiPengantaran: false,
        modalSuratJalanSupir: false,
        sedangMemulaiId: null,
        suratJalanDipilih: {},
        indeksFotoAktif: 0,
        formKonfirmasi: {
            id: null,
            sj: '',
            date: '',
            route: '',
            material: '',
            tonase_ton: '',
            rate: 0,
            trip_money: 0,
            nama_penerima: '',
            punya_foto_asli: false,
            daftar_foto: [],
            foto_dihapus: [],
            catatan_pengantaran: '',
        },

        get punyaFotoSetelahHapus() {
            const daftar = Array.isArray(this.formKonfirmasi.daftar_foto) ? this.formKonfirmasi.daftar_foto : [];
            const dihapus = Array.isArray(this.formKonfirmasi.foto_dihapus) ? this.formKonfirmasi.foto_dihapus : [];
            return daftar.filter((url) => !dihapus.includes(url)).length > 0;
        },

        get daftarFotoSuratJalanDipilih() {
            const daftar = Array.isArray(this.suratJalanDipilih?.daftar_foto_surat_jalan)
                ? this.suratJalanDipilih.daftar_foto_surat_jalan.filter((item) => typeof item === 'string' && item.trim() !== '')
                : [];
            if (daftar.length > 0) {
                return daftar;
            }
            if (this.suratJalanDipilih?.punya_foto_asli && this.suratJalanDipilih?.foto_surat_jalan) {
                return [this.suratJalanDipilih.foto_surat_jalan];
            }
            return [];
        },

        get fotoAktifSuratJalan() {
            if (this.daftarFotoSuratJalanDipilih.length > 0) {
                return this.daftarFotoSuratJalanDipilih[this.indeksFotoAktif] || this.daftarFotoSuratJalanDipilih[0];
            }
            return this.suratJalanDipilih?.foto_surat_jalan || '/images/placeholders/surat-jalan-default.svg';
        },

        get daftarFotoGaleriLightbox() {
            if (this.daftarFotoSuratJalanDipilih.length > 0) {
                return this.daftarFotoSuratJalanDipilih;
            }
            return [this.fotoAktifSuratJalan];
        },

        get estimasiAngkutan() {
            const ton = parseNumber(String(this.formKonfirmasi.tonase_ton || '0'));
            const tarif = Number(this.formKonfirmasi.rate || 0);
            return Math.round((Number.isNaN(ton) ? 0 : ton) * tarif);
        },

        formatRp(value) {
            return rupiah(value);
        },

        toggleHapusFotoKonfirmasi(url) {
            if (!url) return;
            const daftar = Array.isArray(this.formKonfirmasi.foto_dihapus) ? [...this.formKonfirmasi.foto_dihapus] : [];
            const indeks = daftar.indexOf(url);
            if (indeks === -1) {
                daftar.push(url);
            } else {
                daftar.splice(indeks, 1);
            }
            this.formKonfirmasi.foto_dihapus = daftar;
        },

        bukaKonfirmasiPengantaran(trip) {
            const daftarFotoTersimpan = Array.isArray(trip.daftar_foto_surat_jalan)
                ? [...trip.daftar_foto_surat_jalan]
                : [];
            this.formKonfirmasi = {
                id: trip.id,
                sj: trip.sj || '-',
                date: trip.date || '-',
                route: trip.route || '-',
                material: trip.material || 'BATU BARA',
                tonase_ton: trip.tonnage_input || String(trip.tonnage || '').replace('.', ','),
                rate: Number(trip.rate || 0),
                trip_money: Number(trip.trip_money || 0),
                nama_penerima: trip.nama_penerima || '',
                punya_foto_asli: Boolean(trip.punya_foto_asli) || daftarFotoTersimpan.length > 0,
                daftar_foto: daftarFotoTersimpan,
                foto_dihapus: [],
                catatan_pengantaran: trip.catatan_pengantaran || '',
            };
            const inputFoto = document.getElementById('foto_surat_jalan');
            if (inputFoto) {
                inputFoto.value = '';
                inputFoto.dispatchEvent(new Event('change'));
            }
            this.modalKonfirmasiPengantaran = true;
        },

        bukaSuratJalanSupir(trip) {
            this.suratJalanDipilih = { ...trip };
            this.indeksFotoAktif = 0;
            this.modalSuratJalanSupir = true;
        },

        async mulaiPengiriman(trip) {
            if (!trip?.id || this.sedangMemulaiId !== null) return;
            this.sedangMemulaiId = trip.id;
            const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const respons = await fetch(`/my/trips/${trip.id}/mulai-kirim`, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        'X-CSRF-TOKEN': tokenCsrf,
                    },
                });

                const hasil = await respons.json().catch(() => ({}));
                if (!respons.ok || hasil.success === false) {
                    window.tampilkanToast?.({
                        type: 'error',
                        title: hasil.title || 'Gagal Memulai Pengiriman',
                        message: hasil.message || 'Status pengantaran surat jalan tidak dapat diperbarui.',
                        data: hasil.data || {},
                    });
                    return;
                }

                window.simpanToastRedirect?.(hasil);
                window.location.reload();
            } catch (_) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Gangguan Koneksi Jaringan',
                    message: 'Tidak dapat menghubungi server untuk memulai pengiriman.',
                });
            } finally {
                this.sedangMemulaiId = null;
            }
        },

        tripVisible(route, sj, status, statusKode = '') {
            const keyword = this.search.toLowerCase().trim();
            const matchesSearch = keyword === '' || String(sj).toLowerCase().includes(keyword);
            const matchesRoute = this.route === 'all' || route === this.route;

            let matchesStatus = true;

            if (this.status === 'terbit') {
                matchesStatus = statusKode === 'terbit' || status === 'Terbit (Siap Dikirim)';
            } else if (this.status === 'sedang_dikirim') {
                matchesStatus = statusKode === 'sedang_dikirim' || status === 'Sedang Dikirim';
            } else if (this.status === 'valid') {
                matchesStatus = statusKode === 'terverifikasi' || status === 'Terverifikasi';
            } else if (this.status === 'menunggu_verifikasi') {
                matchesStatus = statusKode === 'menunggu_verifikasi' || status === 'Menunggu Verifikasi Admin';
            } else if (this.status === 'pending') {
                matchesStatus = ['terbit', 'sedang_dikirim', 'menunggu_verifikasi'].includes(statusKode)
                    || (statusKode === '' && status !== 'Terverifikasi' && status !== 'Dibatalkan');
            }

            return matchesSearch && matchesRoute && matchesStatus;
        },
    }));
}
