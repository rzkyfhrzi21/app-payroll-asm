import { validasiDataBiayaOperasional } from './expenses.js';

export default function registerAdminLoans(Alpine) {
    Alpine.data('loansPage', () => ({
        modalTambah: false,
        modalEdit: false,
        modalDetail: false,
        modalPotong: false,
        modalHapus: false,
        modalPengeluaranTambah: false,
        kasbonDipilih: {},
        setujuHapus: false,
        errorsPengeluaran: {},
        tabAktif: 'kasbon',

        init() {
            const tabDariUrl = new URLSearchParams(window.location.search).get('tab');
            if (tabDariUrl === 'bpjs' || tabDariUrl === 'kasbon') {
                this.tabAktif = tabDariUrl;
            }
        },

        pilihTab(tab) {
            this.tabAktif = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },

        bukaTambah() {
            this.modalTambah = true;
        },

        bukaPengeluaranTambah() {
            this.errorsPengeluaran = {};
            this.modalPengeluaranTambah = true;
        },

        validasiPengeluaranTambah() {
            const form = document.querySelector('[data-form="loans-pengeluaran-tambah"]');
            if (!form) return true;
            this.errorsPengeluaran = validasiDataBiayaOperasional(form);
            return Object.keys(this.errorsPengeluaran).length === 0;
        },

        bukaEdit(data) {
            this.kasbonDipilih = { ...data };
            this.modalEdit = true;
        },

        bukaDetail(data) {
            this.kasbonDipilih = { ...data };
            this.modalDetail = true;
        },

        bukaPotong(data) {
            this.kasbonDipilih = { ...data };
            this.modalPotong = true;
        },

        bukaHapus(data) {
            this.kasbonDipilih = { ...data };
            this.setujuHapus = false;
            this.modalHapus = true;
        },

        formatRupiahInput(event) {
            const angka = String(event.target.value).replace(/\D/g, '');
            event.target.value = angka === '' ? '' : Number(angka).toLocaleString('id-ID');
        },
    }));

    Alpine.data('panelBpjsMassal', ({ url = '' } = {}) => ({
        url,
        terpilih: [],
        nominal: '',
        sedangSimpan: false,

        daftarCentang() {
            return [...this.$root.querySelectorAll('[data-centang-bpjs]')];
        },

        get semuaTerpilih() {
            const semua = this.daftarCentang();
            return semua.length > 0 && this.terpilih.length === semua.length;
        },

        pilihSemua(aktif) {
            const semua = this.daftarCentang();
            this.terpilih = aktif ? semua.map((input) => input.value) : [];
        },

        formatNominal() {
            const angka = String(this.nominal ?? '').replace(/\D/g, '');
            this.nominal = angka === '' ? '' : Number(angka).toLocaleString('id-ID');
        },

        async simpanBpjs() {
            if (this.sedangSimpan) {
                return;
            }

            if (this.terpilih.length === 0) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Belum Ada Supir Dipilih',
                    action: 'BPJS_TANPA_PILIHAN',
                    status: 'FAILED',
                    message: 'Centang minimal satu supir sebelum menyimpan potongan BPJS.',
                });
                return;
            }

            const nominal = Number(String(this.nominal ?? '').replace(/\D/g, ''));
            this.sedangSimpan = true;

            try {
                const respons = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        ids: this.terpilih.map((id) => Number(id)),
                        potongan_bpjs_bulanan: nominal,
                    }),
                });

                const muatan = await respons.json().catch(() => ({}));

                if (!respons.ok) {
                    throw new Error(muatan.message || 'Potongan BPJS gagal disimpan.');
                }

                window.tampilkanToast?.({
                    type: 'success',
                    title: muatan.title ?? 'Potongan BPJS Disimpan',
                    action: muatan.action ?? 'BULK_BPJS',
                    status: 'COMPLETED',
                    message: muatan.message ?? '',
                });

                window.setTimeout(() => window.location.reload(), 900);
            } catch (galat) {
                this.sedangSimpan = false;
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Gagal Menyimpan BPJS',
                    action: 'BULK_BPJS_FAILED',
                    status: 'FAILED',
                    message: galat?.message ?? 'Terjadi kesalahan saat menyimpan potongan BPJS.',
                });
            }
        },
    }));
}
