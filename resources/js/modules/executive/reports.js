export default function registerExecutiveReports(Alpine) {
    Alpine.data('executiveReports', (rows = [], report = {}) => ({
        search: '',
        emailOpen: false,
        emailTo: 'direksi@kafa-logistics.id',
        emailNote: '',

        rowVisible(name, nip, plate) {
            const keyword = this.search.toLowerCase().trim();

            return (
                keyword === '' ||
                String(name).toLowerCase().includes(keyword) ||
                String(nip).toLowerCase().includes(keyword) ||
                String(plate).toLowerCase().includes(keyword)
            );
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
