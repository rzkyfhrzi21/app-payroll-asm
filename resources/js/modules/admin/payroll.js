import { rupiah } from '../../core/helpers.js';

export default function registerAdminPayroll(Alpine) {
    Alpine.data('payrollPage', (rows = [], summary = {}, period = {}, trips = []) => ({
        tab: 'all',
        search: '',
        fleet: 'all',
        calculating: false,
        drawerOpen: false,
        selected: null,
        editDriverForm: {
            biaya_sparepart: 0,
            total_tunjangan: 0,
            potongan_bpjs: 0,
        },
        transferOpen: false,
        transferTab: 'import',
        approvalOpen: false,
        approvalMode: 'approve',
        rejectReason: '',
        modalPayrollHapus: false,
        payrollHapusTarget: null,

        formatRp(value) {
            return rupiah(value);
        },

        get previewSparepart() {
            return Math.max(0, Number(this.editDriverForm.biaya_sparepart || 0));
        },

        get previewHasilBersihTruk() {
            if (!this.selected) return 0;
            const sparepartAwal = Number(this.selected.sparepart || 0);
            const sparepartBaru = this.previewSparepart;
            const hasilBersihAwal = Number(this.selected.net_result || 0);
            if (sparepartBaru === sparepartAwal) return hasilBersihAwal;
            return Math.max(0, hasilBersihAwal + sparepartAwal - sparepartBaru);
        },

        get previewKomisiSupir() {
            if (!this.selected) return 0;
            const sparepartAwal = Number(this.selected.sparepart || 0);
            if (this.previewSparepart === sparepartAwal && this.selected.gross_pay !== undefined) {
                return Number(this.selected.gross_pay || 0);
            }
            const persenKomisi = Number(this.selected.persentase_komisi ?? 10);
            return Math.round(this.previewHasilBersihTruk * (persenKomisi / 100));
        },

        get previewTunjangan() {
            return Math.max(0, Number(this.editDriverForm.total_tunjangan || 0));
        },

        get previewBpjs() {
            return Math.max(0, Number(this.editDriverForm.potongan_bpjs || 0));
        },

        get previewGajiBersih() {
            if (!this.selected) return 0;
            const kasbon = Number(this.selected.kasbon || 0);
            const potonganHistoris = Number(this.selected.potongan_historis || 0);
            const penambahanHistoris = Number(this.selected.penambahan_historis || 0);
            return Math.max(
                0,
                this.previewKomisiSupir + this.previewTunjangan - this.previewBpjs - kasbon - potonganHistoris + penambahanHistoris
            );
        },

        async kirimAksiPayrollAjax(url, metode = 'POST', payload = {}) {
            const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const formData = new FormData();
            if (metode !== 'POST') {
                formData.append('_method', metode);
            }
            Object.entries(payload).forEach(([kunci, nilai]) => {
                formData.append(kunci, nilai ?? '');
            });

            try {
                const respons = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        'X-CSRF-TOKEN': tokenCsrf,
                    },
                    body: formData,
                });
                const hasil = await respons.json();
                if (!respons.ok || hasil.success === false) {
                    window.tampilkanToast({
                        type: 'error',
                        title: hasil.title || 'Operasi Gagal',
                        action: hasil.action || 'error',
                        status: 'failed',
                        message: hasil.message || 'Permintaan tidak dapat diproses.',
                        data: hasil.data || {},
                    });
                    return null;
                }
                window.simpanToastRedirect(hasil);
                window.tampilkanToast(hasil);
                window.setTimeout(() => {
                    if (hasil.redirect) {
                        window.location.href = hasil.redirect;
                    } else {
                        window.location.reload();
                    }
                }, 280);
                return hasil;
            } catch (_) {
                window.tampilkanToast({
                    type: 'error',
                    title: 'Gangguan Koneksi Jaringan',
                    action: 'network_error',
                    status: 'failed',
                    message: 'Tidak dapat menghubungi server untuk memproses aksi payroll.',
                });
                return null;
            }
        },

        bukaHapusPayroll(row) {
            this.payrollHapusTarget = row;
            this.modalPayrollHapus = true;
        },

        async konfirmasiHapusPayroll() {
            const idPenggajian = this.payrollHapusTarget?.id;
            const kodePeriode = encodeURIComponent(period.code || 'Jan-26');
            this.modalPayrollHapus = false;
            if (!idPenggajian) return;

            await this.kirimAksiPayrollAjax(
                `/admin/payroll/${kodePeriode}/driver/${idPenggajian}`,
                'DELETE'
            );
        },

        rowVisible(name, plate, vendor, hasWarning) {
            const keyword = this.search.toLowerCase().trim();
            const matchesSearch =
                keyword === '' ||
                String(name).toLowerCase().includes(keyword) ||
                String(plate).toLowerCase().includes(keyword);

            const matchesTab = this.tab === 'all' || hasWarning;
            const matchesFleet = this.fleet === 'all' || vendor === this.fleet;

            return matchesSearch && matchesTab && matchesFleet;
        },

        resetFilterPayroll() {
            this.search = '';
            this.tab = 'all';
            this.fleet = 'all';
            window.tampilkanToast({
                type: 'success',
                title: 'Filter Disetel Ulang',
                action: 'filter_reset',
                status: 'completed',
                message: 'Pencarian dan filter penggajian telah dikembalikan ke semua data.',
            });
        },

        openDrawer(row) {
            this.selected = row;
            this.editDriverForm = {
                biaya_sparepart: Number(row?.sparepart || 0),
                total_tunjangan: Number(row?.tunjangan || 0),
                potongan_bpjs: Number(row?.bpjs || 0),
            };
            this.drawerOpen = true;
        },

        closeDrawer() {
            this.drawerOpen = false;
        },

        async saveDriver() {
            if (!this.selected?.id) return;
            const kodePeriode = encodeURIComponent(period.code || 'Jan-26');
            const hasil = await this.kirimAksiPayrollAjax(
                `/admin/payroll/${kodePeriode}/driver/${this.selected.id}`,
                'PATCH',
                {
                    biaya_sparepart: this.editDriverForm.biaya_sparepart,
                    total_tunjangan: this.editDriverForm.total_tunjangan,
                    potongan_bpjs: this.editDriverForm.potongan_bpjs,
                }
            );
            if (hasil) {
                this.drawerOpen = false;
            }
        },

        printSlip() {
            const urlSlip = this.selected?.url_cetak_slip;
            window.tampilkanToast({
                type: 'success',
                title: 'Slip Gaji Siap Dicetak',
                action: 'export',
                status: 'completed',
                message: `Membuka slip gaji supir "${this.selected?.name || '-'}" periode ${period.code || '-'} di tab baru.`,
            });
            if (urlSlip) {
                window.open(urlSlip, '_blank', 'noopener');
            }
        },

        async calculate() {
            if (this.calculating) {
                return;
            }

            this.calculating = true;
            const kodePeriode = encodeURIComponent(period.code || 'Jan-26');
            await this.kirimAksiPayrollAjax(`/admin/payroll/${kodePeriode}/calculate`, 'POST');
            this.calculating = false;
        },

        openApproval(mode) {
            this.approvalMode = mode;
            this.approvalOpen = true;
            this.rejectReason = '';
        },

        async confirmApproval() {
            if (this.approvalMode === 'reject' && this.rejectReason.trim() === '') {
                window.tampilkanToast({
                    type: 'error',
                    title: 'Alasan Penolakan Wajib Diisi',
                    action: 'reject',
                    status: 'failed',
                    message: 'Silakan isi alasan penolakan sebelum mengirim revisi ke Admin HRD.',
                });
                return;
            }

            const kodePeriode = encodeURIComponent(period.code || 'Jan-26');

            const url = this.approvalMode === 'reject'
                ? `/admin/payroll/${kodePeriode}/approval`
                : `/admin/payroll/${kodePeriode}/submit`;

            const isi = this.approvalMode === 'reject'
                ? { mode: 'reject', alasan_penolakan: this.rejectReason.trim() }
                : {};

            const hasil = await this.kirimAksiPayrollAjax(url, this.approvalMode === 'reject' ? 'POST' : 'PATCH', isi);

            if (hasil) {
                this.approvalOpen = false;
            }
        },
    }));
}
