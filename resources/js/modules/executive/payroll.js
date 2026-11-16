import { rupiah } from '../../core/helpers.js';

export default function registerExecutivePayroll(Alpine) {
    Alpine.data('executivePayroll', (rows = [], summary = {}, period = {}) => ({
        search: '',
        category: 'all',
        filterOpen: false,
        detailOpen: false,
        detail: null,
        detailTab: 'formula',
        batchOpen: false,
        selected: [],
        aksiMassalTerpilih: '',
        sedangMemproses: false,
        reviseOpen: false,
        reviseNote: '',

        // State Verifikasi 2 Langkah (Password Pimpinan) saat Pencairan Dana Gaji
        modalKonfirmasiCair: false,
        targetCairIds: [],
        kataSandiCair: '',
        lihatKataSandiCair: false,
        labelTargetCair: 'semua supir yang telah di-ACC',

        get daftarBaris() {
            return Array.isArray(rows) ? rows : [];
        },

        get barisDapatDipilih() {
            return this.daftarBaris.filter((row) => {
                const belumPaid = (row.status_kode || 'draft') !== 'paid';
                const punyaId = Number(row.id || 0) > 0;
                const tampil = this.rowVisible(row.name, row.nip, row.plate, '', Boolean(row.anomaly));
                return belumPaid && punyaId && tampil;
            });
        },

        get semuaTerpilih() {
            const kandidat = this.barisDapatDipilih;
            if (kandidat.length === 0) return false;
            const idTerpilih = new Set(this.selectedIds);
            return kandidat.every((row) => idTerpilih.has(Number(row.id)));
        },

        get selectedIds() {
            return this.selected
                .map((id) => Number(id))
                .filter((id) => Number.isInteger(id) && id > 0);
        },

        get selectedBelumAccIds() {
            const idTerpilih = new Set(this.selectedIds);
            return this.daftarBaris
                .filter((row) => idTerpilih.has(Number(row.id)) && (row.status_kode || 'draft') === 'draft')
                .map((row) => Number(row.id));
        },

        get selectedSiapCairIds() {
            const idTerpilih = new Set(this.selectedIds);
            return this.daftarBaris
                .filter((row) => idTerpilih.has(Number(row.id)) && row.status_kode === 'approved')
                .map((row) => Number(row.id));
        },

        openTolak() {
            this.reviseNote = '';
            this.reviseOpen = true;
        },

        kirimRevisi() {
            if (this.reviseNote.trim() === '') {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Catatan Revisi Wajib Diisi',
                    action: 'VALIDATION_ERROR',
                    status: 'FAILED',
                    message: 'Tuliskan catatan revisi agar Admin HRD tahu bagian mana yang harus diperbaiki.',
                });
                return;
            }

            this.reviseOpen = false;
            this.kirimKeputusanPayroll(`/executive/payroll/${this.kodePeriode}/reject`, 'PATCH', {
                alasan_penolakan: this.reviseNote.trim(),
            });
        },

        get periodLabel() {
            return period.code ?? '-';
        },

        get kodePeriode() {
            return encodeURIComponent(period.code || 'Jan-26');
        },

        formatRp(value) {
            return rupiah(value);
        },

        rowVisible(name, nip, plate, vendor, anomaly) {
            const keyword = this.search.toLowerCase().trim();
            const matchesSearch =
                keyword === '' ||
                String(name).toLowerCase().includes(keyword) ||
                String(nip).toLowerCase().includes(keyword) ||
                String(plate).toLowerCase().includes(keyword);

            let matchesCategory = true;
            if (this.category === 'ready') {
                matchesCategory = !anomaly;
            }

            return matchesSearch && matchesCategory;
        },

        resetFilter() {
            this.search = '';
            this.category = 'all';
        },

        toggleAll(checked) {
            this.selected = checked
                ? this.barisDapatDipilih.map((row) => String(row.id))
                : [];
        },

        openDetail(row) {
            this.detail = row;
            this.detailTab = 'formula';
            this.detailOpen = true;
        },

        async kirimKeputusanPayroll(url, method = 'POST', payload = {}) {
            if (this.sedangMemproses) return;
            this.sedangMemproses = true;

            try {
                const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const respon = await fetch(url, {
                    method: method || 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenCsrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload || {}),
                });

                const hasil = await respon.json().catch(() => ({}));
                if (respon.ok && hasil.success !== false) {
                    const toastPayload = {
                        type: 'success',
                        title: hasil.title || 'Otorisasi Payroll Berhasil',
                        action: hasil.action || 'EXECUTIVE_PAYROLL',
                        status: hasil.status || 'COMPLETED',
                        message: hasil.message || 'Status penggajian periode berhasil diperbarui.',
                    };
                    window.simpanToastRedirect?.(toastPayload);
                    window.tampilkanToast?.(toastPayload);
                    window.setTimeout(() => window.location.reload(), 650);
                } else {
                    window.tampilkanToast?.({
                        type: 'error',
                        title: hasil.title || 'Otorisasi Gagal',
                        action: hasil.action || 'EXECUTIVE_ERROR',
                        status: hasil.status || 'FAILED',
                        message: hasil.message || 'Gagal memperbarui status penggajian periode.',
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
                : { nama_supir: name || '' };
            this.kirimKeputusanPayroll(`/executive/payroll/${this.kodePeriode}/approve`, 'PATCH', payload);
        },

        accTerpilih() {
            const ids = this.selectedBelumAccIds.length > 0 ? this.selectedBelumAccIds : this.selectedIds;
            if (ids.length === 0) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Belum Ada Supir Dipilih',
                    message: 'Centang minimal satu baris supir untuk di-ACC.',
                });
                return;
            }
            this.kirimKeputusanPayroll(`/executive/payroll/${this.kodePeriode}/approve-batch`, 'POST', { ids });
        },

        // --- Verifikasi 2 Langkah Pencairan Dana Gaji ---
        bukaKonfirmasiPencairan(ids = [], label = '') {
            this.targetCairIds = ids;
            this.labelTargetCair = label || (ids.length === 0 ? 'seluruh supir yang siap cair' : `${ids.length} supir terpilih`);
            this.kataSandiCair = '';
            this.lihatKataSandiCair = false;
            this.modalKonfirmasiCair = true;
            this.$nextTick(() => {
                const inputPass = document.getElementById('password_pencairan_pimpinan');
                if (inputPass) inputPass.focus();
            });
        },

        cairkanSatuan(idPenggajian, namaSupir = '') {
            if (!idPenggajian) return;
            this.detailOpen = false;
            this.bukaKonfirmasiPencairan([Number(idPenggajian)], namaSupir ? `supir ${namaSupir}` : '1 supir terpilih');
        },

        cairkanTerpilih() {
            const ids = this.selectedSiapCairIds;
            if (ids.length === 0) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Belum Ada Baris Siap Cair Dipilih',
                    message: 'Centang baris supir berstatus Disetujui (Siap Cair) untuk dicairkan.',
                });
                return;
            }
            this.bukaKonfirmasiPencairan(ids, `${ids.length} supir terpilih`);
        },

        cairkanSemua() {
            this.bukaKonfirmasiPencairan([], 'seluruh supir pada periode ini');
        },

        eksekusiPencairanDenganPassword() {
            if (!this.kataSandiCair || this.kataSandiCair.trim() === '') {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Kata Sandi Wajib Diisi',
                    action: 'PASSWORD_REQUIRED',
                    status: 'FAILED',
                    message: 'Ketik kata sandi akun Pimpinan Anda untuk mengonfirmasi pencairan dana.',
                });
                return;
            }

            const payload = {
                password: this.kataSandiCair,
            };

            if (this.targetCairIds.length > 0) {
                payload.ids = this.targetCairIds;
            }

            this.modalKonfirmasiCair = false;
            this.kirimKeputusanPayroll(`/executive/payroll/${this.kodePeriode}/disburse`, 'PATCH', payload);
        },

        eksekusiAksiMassal() {
            if (this.aksiMassalTerpilih === 'acc') {
                this.accTerpilih();
            } else if (this.aksiMassalTerpilih === 'cairkan') {
                this.cairkanTerpilih();
            }
            this.aksiMassalTerpilih = '';
        },

        requestRevision() {
            const note = window.prompt('Tulis catatan revisi untuk Admin HRD:', 'Mohon cek ulang komponen perhitungan gaji pada periode ini.');

            if (note === null) {
                return;
            }

            if (note.trim() === '') {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Catatan Revisi Kosong',
                    action: 'VALIDATION_ERROR',
                    status: 'FAILED',
                    message: 'Catatan revisi tidak boleh kosong saat mengembalikan data ke Admin.',
                });
                return;
            }

            this.detailOpen = false;
            this.kirimKeputusanPayroll(`/executive/payroll/${this.kodePeriode}/reject`, 'PATCH', {
                alasan_penolakan: note.trim(),
            });
        },

        confirmBatch() {
            this.batchOpen = false;
            this.kirimKeputusanPayroll(`/executive/payroll/${this.kodePeriode}/approve-batch`, 'POST', {});
        },
    }));
}
