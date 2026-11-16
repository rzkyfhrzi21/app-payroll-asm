export default function registerDriverDashboard(Alpine) {
    Alpine.data('driverPayroll', (payroll = {}, trips = [], profile = {}) => ({
        tableOpen: true,
        pdfState: 'idle',

        downloadPdf() {
            if (this.pdfState !== 'idle') {
                return;
            }

            this.pdfState = 'loading';
            window.open('/my/payroll/slip', '_blank', 'noopener');

            window.setTimeout(() => {
                this.pdfState = 'done';
                window.tampilkanToast?.({
                    type: 'success',
                    title: 'Slip Gaji Siap Dicetak',
                    action: 'DOWNLOAD_SLIP_GAJI',
                    status: 'COMPLETED',
                    message: `Slip gaji ${profile.name || 'Supir'} periode ${payroll.period_code || 'aktif'} dibuka di tab baru untuk diunduh/dicetak PDF.`,
                });

                window.setTimeout(() => {
                    this.pdfState = 'idle';
                }, 2000);
            }, 400);
        },
    }));
}
