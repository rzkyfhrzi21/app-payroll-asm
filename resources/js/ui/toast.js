import { KUNCI_STORAGE_TOAST } from '../core/helpers.js';

export default function registerToast(Alpine) {
    Alpine.data('toastStackManager', (daftarAwal = []) => ({
        antrean: [],

        init() {
            this.inisiator();
        },

        inisiator() {
            const awal = Array.isArray(daftarAwal) ? [...daftarAwal] : [];

            try {
                const tersimpan = window.sessionStorage.getItem(KUNCI_STORAGE_TOAST);
                if (tersimpan) {
                    window.sessionStorage.removeItem(KUNCI_STORAGE_TOAST);
                    const parsed = JSON.parse(tersimpan);
                    if (parsed && parsed.message) {
                        awal.push(parsed);
                    }
                }
            } catch (_) {
                // Abaikan JSON tidak valid
            }

            awal.forEach((item) => this.tambahToast(item));
        },

        tambahToast(item = {}) {
            if (!item || (!item.message && !item.title)) {
                return;
            }

            const statusBawah = String(item.status || '').toLowerCase();
            const tipe =
                item.type === 'error' ||
                item.success === false ||
                statusBawah === 'failed' ||
                statusBawah === 'validation_failed'
                    ? 'error'
                    : 'success';
            const durasi = tipe === 'success' ? (Number(item.durationMs) || 2000) : 0;
            const idToast = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

            let rincian = [];
            if (Array.isArray(item.details)) {
                rincian = item.details;
            } else if (item.data && item.data.errors && typeof item.data.errors === 'object') {
                rincian = Object.values(item.data.errors).flat();
            }

            const objekToast = {
                id: idToast,
                type: tipe,
                title: item.title || (tipe === 'error' ? 'Operasi Gagal' : 'Operasi Berhasil'),
                message: item.message || '',
                details: rincian,
                terlihat: true,
                durationMs: durasi,
                sisaMs: durasi,
                progressPersen: 100,
                dijeda: false,
                intervalId: null,
            };

            this.antrean.unshift(objekToast);

            if (tipe === 'success' && durasi > 0) {
                const langkahMs = 40;
                objekToast.intervalId = window.setInterval(() => {
                    const target = this.antrean.find((t) => t.id === idToast);
                    if (!target) {
                        window.clearInterval(objekToast.intervalId);
                        return;
                    }
                    if (target.dijeda) {
                        return;
                    }
                    target.sisaMs = Math.max(0, target.sisaMs - langkahMs);
                    target.progressPersen = Math.max(0, Math.min(100, (target.sisaMs / target.durationMs) * 100));
                    if (target.sisaMs <= 0) {
                        this.tutupToast(idToast);
                    }
                }, langkahMs);
            }
        },

        jedaTimer(idToast) {
            const target = this.antrean.find((t) => t.id === idToast);
            if (target && target.type === 'success') {
                target.dijeda = true;
            }
        },

        lanjutTimer(idToast) {
            const target = this.antrean.find((t) => t.id === idToast);
            if (target && target.type === 'success') {
                target.dijeda = false;
            }
        },

        tutupToast(idToast) {
            const indeks = this.antrean.findIndex((t) => t.id === idToast);
            if (indeks === -1) {
                return;
            }
            const target = this.antrean[indeks];
            if (target.intervalId) {
                window.clearInterval(target.intervalId);
            }
            target.terlihat = false;
            window.setTimeout(() => {
                this.antrean = this.antrean.filter((t) => t.id !== idToast);
            }, 180);
        },
    }));
}
