export const validasiDataBiayaOperasional = (form) => {
    const galat = {};
    const tanggal = form?.querySelector('[name="tanggal_biaya"]')?.value || '';
    const idTruk = form?.querySelector('[name="id_truk"]')?.value || '';
    const kategori = form?.querySelector('[name="kategori"]')?.value || '';
    const nominalRaw = (form?.querySelector('[name="nominal"]')?.value || '').replace(/\./g, '').trim();
    const nominal = Number(nominalRaw);
    const nomorNota = form?.querySelector('[name="nomor_nota"]')?.value || '';
    const keterangan = form?.querySelector('[name="keterangan"]')?.value || '';
    const status = form?.querySelector('[name="status"]')?.value || '';

    if (!tanggal || !/^\d{4}-\d{2}-\d{2}$/.test(tanggal)) {
        galat.tanggal_biaya = 'Tanggal pengeluaran wajib diisi.';
    }
    if (!idTruk) galat.id_truk = 'Unit armada wajib dipilih.';
    if (!kategori) galat.kategori = 'Kategori pengeluaran wajib dipilih.';
    if (nominalRaw === '' || !/^\d+$/.test(nominalRaw)) {
        galat.nominal = 'Nominal wajib diisi berupa angka bulat.';
    } else if (nominal < 1) {
        galat.nominal = 'Nominal minimal Rp 1.';
    } else if (nominal > 999999999) {
        galat.nominal = 'Nominal maksimal Rp 999.999.999.';
    }
    if (nomorNota.length > 50) galat.nomor_nota = 'Nomor nota maksimal 50 karakter.';
    if (keterangan.length > 200) galat.keterangan = 'Keterangan maksimal 200 karakter.';
    if (!status) galat.status = 'Status wajib dipilih.';

    return galat;
};

export default function registerAdminExpenses(Alpine) {
    Alpine.data('expensesPage', (barisBiaya = [], periode = {}) => ({
        modalBiayaTambah: false,
        modalBiayaEdit: false,
        modalBiayaDetail: false,
        modalBiayaHapus: false,

        biayaEdit: {},
        biayaDipilih: {},
        biayaHapus: {},
        konfirmasiHapus: false,
        errorsTambah: {},
        errorsEdit: {},

        validasiFormBiaya(form) {
            return validasiDataBiayaOperasional(form);
        },

        validasiExpensesTambah() {
            const form = document.querySelector('[data-form="biaya-tambah"]');
            if (!form) return true;
            this.errorsTambah = this.validasiFormBiaya(form);
            return Object.keys(this.errorsTambah).length === 0;
        },

        validasiExpensesEdit() {
            const form = document.querySelector('[data-form="biaya-edit"]');
            if (!form) return true;
            this.errorsEdit = this.validasiFormBiaya(form);
            return Object.keys(this.errorsEdit).length === 0;
        },

        bukaTambah() {
            this.errorsTambah = {};
            this.modalBiayaTambah = true;
        },

        bukaEdit(baris) {
            this.errorsEdit = {};
            this.biayaEdit = { ...baris };
            this.modalBiayaEdit = true;
            this.$nextTick(() => {
                const selKategori = document.querySelector('[data-modal="biaya-edit"] [name="kategori"]');
                const selUnit = document.querySelector('[data-modal="biaya-edit"] [name="id_truk"]');
                const selStatus = document.querySelector('[data-modal="biaya-edit"] [name="status"]');
                if (selKategori) selKategori.value = baris.kategori;
                if (selUnit) selUnit.value = baris.id_truk;
                if (selStatus) selStatus.value = baris.status;
            });
        },

        bukaDetail(baris) {
            this.biayaDipilih = { ...baris };
            this.modalBiayaDetail = true;
        },

        bukaHapus(baris) {
            this.biayaHapus = { ...baris };
            this.konfirmasiHapus = false;
            this.modalBiayaHapus = true;
        },
    }));
}
