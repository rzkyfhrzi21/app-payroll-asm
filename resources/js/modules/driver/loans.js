import { rupiah, numberOnly, parseNumber } from '../../core/helpers.js';

export default function registerDriverLoans(Alpine) {
    Alpine.data('driverLoans', (loan = {}, mutations = [], schedule = [], profile = {}) => ({
        filter: 'all',
        modalOpen: false,
        form: {
            nominal: 500000,
            tenor: 2,
            category: 'darurat',
            note: '',
        },

        get monthly() {
            return Math.round(Number(this.form.nominal) / Math.max(Number(this.form.tenor) || 1, 1));
        },

        formatRp(value) {
            return rupiah(value);
        },

        formatNumber(value) {
            return numberOnly(value);
        },

        parseNumber,

        submit() {
            if (!this.form.nominal || Number(this.form.nominal) < 100000) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Nominal Tidak Valid',
                    message: 'Nominal pengajuan kasbon minimal Rp 100.000.',
                });
                return;
            }

            if (!this.form.note.trim()) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Alasan Wajib Diisi',
                    message: 'Alasan pengajuan kasbon wajib diisi.',
                });
                return;
            }

            const formEl = document.querySelector('form[action*="/my/loans"]');
            if (formEl) {
                formEl.submit();
            } else {
                this.modalOpen = false;
            }
        },
    }));
}
