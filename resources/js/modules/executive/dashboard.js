import { rupiah } from '../../core/helpers.js';

export default function registerExecutiveDashboard(Alpine) {
    Alpine.data('executiveDashboard', (queue = [], summary = {}, period = {}) => ({
        queueTab: 'all',
        approvalOpen: false,
        decision: 'approve',
        rejectNote: '',
        detailOpen: false,
        detail: null,
        sedangMemproses: false,

        get kodePeriode() {
            return encodeURIComponent(period.code || 'Jan-26');
        },

        queueVisible(vendorSlug) {
            if (this.queueTab === 'all') {
                return true;
            }
            return String(vendorSlug || '') === String(this.queueTab);
        },

        formatRp(value) {
            return rupiah(value);
        },

        openDetail(row) {
            this.detail = row;
            this.detailOpen = true;
        },

        async kirimKeputusanEksekutif(url, method = 'POST', payload = {}) {
            if (this.sedangMemproses) return;
            this.sedangMemproses = true;

            try {
                const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const formData = new FormData();
                if (method !== 'POST') {
                    formData.append('_method', method);
                }
                Object.entries(payload).forEach(([kunci, nilai]) => {
                    formData.append(kunci, String(nilai ?? ''));
                });

                const respon = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': tokenCsrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        Accept: 'application/json',
                    },
                    body: formData,
                });

                const hasil = await respon.json().catch(() => ({}));
                if (respon.ok && hasil.success !== false) {
                    window.tampilkanToast?.({
                        type: 'success',
                        title: hasil.title || 'Otorisasi Pimpinan Berhasil',
                        action: hasil.action || 'EXECUTIVE_ACTION',
                        status: hasil.status || 'COMPLETED',
                        message: hasil.message || 'Keputusan Pimpinan berhasil disimpan.',
                    });
                    window.setTimeout(() => window.location.reload(), 900);
                } else {
                    window.tampilkanToast?.({
                        type: 'error',
                        title: hasil.title || 'Otorisasi Gagal',
                        action: hasil.action || 'EXECUTIVE_ERROR',
                        status: hasil.status || 'FAILED',
                        message: hasil.message || 'Terjadi kesalahan saat menyimpan keputusan Pimpinan.',
                    });
                }
            } finally {
                this.sedangMemproses = false;
            }
        },

        confirmAcc(idPenggajian = null, name = '') {
            this.detailOpen = false;
            const payload = idPenggajian
                ? { id_penggajian: Number(idPenggajian) }
                : (name ? { nama_supir: name } : {});
            this.kirimKeputusanEksekutif(`/executive/payroll/${this.kodePeriode}/approve`, 'PATCH', payload);
        },

        confirmAccBatch() {
            this.kirimKeputusanEksekutif(`/executive/payroll/${this.kodePeriode}/approve-batch`, 'POST');
        },

        confirmDisburse() {
            this.kirimKeputusanEksekutif(`/executive/payroll/${this.kodePeriode}/approve`, 'PATCH');
        },

        submitDecision() {
            if (this.decision === 'reject') {
                if (this.rejectNote.trim() === '') {
                    window.tampilkanToast?.({
                        type: 'error',
                        title: 'Catatan Revisi Wajib Diisi',
                        action: 'VALIDATION_ERROR',
                        status: 'FAILED',
                        message: 'Tuliskan alasan atau catatan revisi sebelum mengembalikan rekap penggajian ke Admin.',
                    });
                    return;
                }

                this.approvalOpen = false;
                this.kirimKeputusanEksekutif(`/executive/payroll/${this.kodePeriode}/reject`, 'PATCH', {
                    alasan_penolakan: this.rejectNote.trim(),
                });
            } else {
                this.approvalOpen = false;
                this.kirimKeputusanEksekutif(`/executive/payroll/${this.kodePeriode}/approve`, 'PATCH');
            }
        },
    }));
}
