import { rupiah } from '../../core/helpers.js';

/**
 * State Alpine dashboard Pimpinan.
 *
 * Catatan: daftar "Antrean Otorisasi Payroll Supir" sudah dihapus dari dashboard.
 * ACC/pencairan tetap dikerjakan di halaman /executive/payroll, jadi modul ini
 * hanya menyisakan drawer rincian slip supir yang dibuka dari tabel ringkasan.
 */
export default function registerExecutiveDashboard(Alpine) {
    Alpine.data('executiveDashboard', (summary = {}, period = {}) => ({
        detailOpen: false,
        detail: null,

        get kodePeriode() {
            return encodeURIComponent(period.code || '');
        },

        formatRp(value) {
            return rupiah(value);
        },

        openDetail(row) {
            this.detail = row;
            this.detailOpen = true;
        },
    }));
}
