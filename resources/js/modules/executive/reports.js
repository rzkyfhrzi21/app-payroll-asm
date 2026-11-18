export default function registerExecutiveReports(Alpine) {
    Alpine.data('executiveReports', (rows = [], report = {}) => ({
        search: '',
        modelUnit: '',
        ruteTerpilih: '',
        emailOpen: false,
        get filterLaporanAktif() {
            return this.search.trim() !== '' || this.modelUnit !== '' || this.ruteTerpilih !== '';
        },

        get jumlahTerlihat() {
            return rows.filter((baris) =>
                this.rowVisible(baris.name, baris.nip, baris.plate, baris.model_truk ?? '', baris.rute ?? [])
            ).length;
        },
        rowVisible(name, nip, plate, modelTruk = '', rute = []) {
            const keyword = this.search.toLowerCase().trim();

            const cocokKataKunci =
                keyword === '' ||
                String(name).toLowerCase().includes(keyword) ||
                String(nip).toLowerCase().includes(keyword) ||
                String(plate).toLowerCase().includes(keyword);

            const cocokModel = this.modelUnit === '' || String(modelTruk) === this.modelUnit;
            const cocokRute =
                this.ruteTerpilih === '' || (Array.isArray(rute) && rute.includes(this.ruteTerpilih));

            return cocokKataKunci && cocokModel && cocokRute;
        },

        resetFilterLaporan() {
            this.search = '';
            this.modelUnit = '';
            this.ruteTerpilih = '';
        },

        sendEmail() {
            if (!this.emailTo.trim()) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Email Belum Diisi',
                    message: 'Email penerima laporan direksi wajib diisi.',
                });
                return;
            }

            window.tampilkanToast?.({
                type: 'success',
                title: 'Laporan Dikirim',
                message: `Laporan keuangan ${report.doc_id ?? ''} dikirimkan ke alamat email ${this.emailTo}.`,
            });
            this.emailOpen = false;
        },
    }));
}
