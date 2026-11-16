export default function registerDriverProfile(Alpine) {
    Alpine.data('driverProfilePage', (profile = {}) => ({
        contactOpen: false,
        form: {
            phone: profile.phone ?? '',
            contactName: 'Siti Aminah (Istri)',
            contactPhone: '0812-7788-9900',
        },

        copyEmail() {
            const email = profile.email ?? '';

            if (navigator.clipboard && email) {
                navigator.clipboard.writeText(email);
                window.tampilkanToast?.({
                    type: 'success',
                    title: 'Email Disalin',
                    message: `Email ${email} berhasil disalin ke clipboard.`,
                });
                return;
            }

            window.alert(`Email supir: ${email}`);
        },

        saveContact() {
            if (!this.form.phone.trim() || !this.form.contactPhone.trim()) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Data Belum Lengkap',
                    message: 'Nomor WhatsApp dan nomor kontak darurat wajib diisi.',
                });
                return;
            }

            const formEl = document.querySelector('form[action*="/my/profile"]');
            if (formEl) {
                formEl.submit();
            } else {
                this.contactOpen = false;
            }
        },
    }));
}
