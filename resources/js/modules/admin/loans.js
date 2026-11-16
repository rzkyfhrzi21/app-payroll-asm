import { validasiDataBiayaOperasional } from './expenses.js';

export default function registerAdminLoans(Alpine) {
    Alpine.data('loansPage', () => ({
        modalTambah: false,
        modalEdit: false,
        modalDetail: false,
        modalPotong: false,
        modalHapus: false,
        modalPengeluaranTambah: false,
        modalTunjanganTambah: false,
        kasbonDipilih: {},
        setujuHapus: false,
        errorsPengeluaran: {},
        tabAktif: 'kasbon',

        init() {
            const tabDariUrl = new URLSearchParams(window.location.search).get('tab');
            if (tabDariUrl === 'bpjs' || tabDariUrl === 'kasbon') {
                this.tabAktif = tabDariUrl;
            }
        },

        pilihTab(tab) {
            this.tabAktif = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },

        bukaTambah() {
            this.modalTambah = true;
        },

        bukaPengeluaranTambah() {
            this.errorsPengeluaran = {};
            this.modalPengeluaranTambah = true;
        },

        validasiPengeluaranTambah() {
            const form = document.querySelector('[data-form="loans-pengeluaran-tambah"]');
            if (!form) return true;
            this.errorsPengeluaran = validasiDataBiayaOperasional(form);
            return Object.keys(this.errorsPengeluaran).length === 0;
        },

        bukaEdit(data) {
            this.kasbonDipilih = { ...data };
            this.modalEdit = true;
        },

        bukaDetail(data) {
            this.kasbonDipilih = { ...data };
            this.modalDetail = true;
        },

        bukaPotong(data) {
            this.kasbonDipilih = { ...data };
            this.modalPotong = true;
        },

        bukaHapus(data) {
            this.kasbonDipilih = { ...data };
            this.setujuHapus = false;
            this.modalHapus = true;
        },

        formatRupiahInput(event) {
            const angka = String(event.target.value).replace(/\D/g, '');
            event.target.value = angka === '' ? '' : Number(angka).toLocaleString('id-ID');
        },
    }));

    Alpine.data('panelBpjsMassal', ({ url = '' } = {}) => ({
        url,
        terpilih: [],
        nominal: '',
        sedangSimpan: false,

        daftarCentang() {
            return [...this.$root.querySelectorAll('[data-centang-bpjs]')];
        },

        get semuaTerpilih() {
            const semua = this.daftarCentang();
            return semua.length > 0 && this.terpilih.length === semua.length;
        },

        pilihSemua(aktif) {
            const semua = this.daftarCentang();
            this.terpilih = aktif ? semua.map((input) => input.value) : [];
        },

        formatNominal() {
            const angka = String(this.nominal ?? '').replace(/\D/g, '');
            this.nominal = angka === '' ? '' : Number(angka).toLocaleString('id-ID');
        },

        async simpanBpjs() {
            if (this.sedangSimpan) {
                return;
            }

            if (this.terpilih.length === 0) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Belum Ada Supir Dipilih',
                    action: 'BPJS_TANPA_PILIHAN',
                    status: 'FAILED',
                    message: 'Centang minimal satu supir sebelum menyimpan potongan BPJS.',
                });
                return;
            }

            const nominal = Number(String(this.nominal ?? '').replace(/\D/g, ''));
            this.sedangSimpan = true;

            try {
                const respons = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        ids: this.terpilih.map((id) => Number(id)),
                        potongan_bpjs_bulanan: nominal,
                    }),
                });

                const muatan = await respons.json().catch(() => ({}));

                if (!respons.ok) {
                    throw new Error(muatan.message || 'Potongan BPJS gagal disimpan.');
                }

                window.tampilkanToast?.({
                    type: 'success',
                    title: muatan.title ?? 'Potongan BPJS Disimpan',
                    action: muatan.action ?? 'BULK_BPJS',
                    status: 'COMPLETED',
                    message: muatan.message ?? '',
                });

                window.setTimeout(() => window.location.reload(), 900);
            } catch (galat) {
                this.sedangSimpan = false;
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Gagal Menyimpan BPJS',
                    action: 'BULK_BPJS_FAILED',
                    status: 'FAILED',
                    message: galat?.message ?? 'Terjadi kesalahan saat menyimpan potongan BPJS.',
                });
            }
        },
    }));

    Alpine.data('panelTunjangan', (daftarAwal = [], idPeriode = null) => ({
        daftarSupir: (daftarAwal ?? []).map((baris) => ({
            id: Number(baris.id),
            nama: baris.nama ?? '',
            nip: baris.nip ?? '',
            tunjangan: Number(baris.tunjangan ?? 0),
            komisi: Number(baris.komisi ?? 0),
            gajiBersih: Number(baris.gajiBersih ?? baris.gaji_bersih_periode ?? 0),
        })),
        idPeriode,
        idKaryawan: '',
        jenisTunjangan: 'TUNJANGAN TARGET PENGANTARAN',
        modeTunjangan: 'set',
        nominal: '0',
        memuat: false,
        cariSupir: '',
        filterTunjangan: 'semua',
        halamanAktif: 1,
        perHalaman: 6,

        get daftarSupirTerfilter() {
            const kunci = String(this.cariSupir || '').toLowerCase().trim();
            return this.daftarSupir.filter((s) => {
                const cocokKunci = kunci === '' ||
                    String(s.nama).toLowerCase().includes(kunci) ||
                    String(s.nip).toLowerCase().includes(kunci);
                let cocokStatus = true;
                if (this.filterTunjangan === 'punya') {
                    cocokStatus = s.tunjangan > 0;
                } else if (this.filterTunjangan === 'tanpa') {
                    cocokStatus = s.tunjangan === 0;
                }
                return cocokKunci && cocokStatus;
            });
        },

        get totalHalaman() {
            return Math.max(1, Math.ceil(this.daftarSupirTerfilter.length / this.perHalaman));
        },

        get daftarSupirHalamanIni() {
            const hal = Math.min(this.halamanAktif, this.totalHalaman);
            const mulai = (hal - 1) * this.perHalaman;
            return this.daftarSupirTerfilter.slice(mulai, mulai + this.perHalaman);
        },

        pindahHalaman(target) {
            const hal = Number(target);
            if (hal >= 1 && hal <= this.totalHalaman) {
                this.halamanAktif = hal;
            }
        },

        resetFilterTabel() {
            this.cariSupir = '';
            this.filterTunjangan = 'semua';
            this.halamanAktif = 1;
        },

        get supirTerpilih() {
            return this.daftarSupir.find((supir) => String(supir.id) === String(this.idKaryawan)) ?? null;
        },

        get nominalAngka() {
            return Number(String(this.nominal).replace(/\D/g, '')) || 0;
        },

        get nilaiAkhir() {
            const sekarang = Number(this.supirTerpilih?.tunjangan ?? 0);
            return this.modeTunjangan === 'tambah' ? sekarang + this.nominalAngka : this.nominalAngka;
        },

        pilihSupir(supir) {
            this.idKaryawan = String(supir.id);
            this.modeTunjangan = 'set';
            this.nominal = new Intl.NumberFormat('id-ID').format(Number(supir.tunjangan ?? 0));
            this.$nextTick(() => document.getElementById('tunjangan_nominal')?.focus());
        },

        async simpanTunjangan(event) {
            if (this.memuat) return;

            if (this.idKaryawan === '') {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Supir Belum Dipilih',
                    action: 'VALIDATION_ERROR',
                    status: 'failed',
                    message: 'Pilih supir penerima tunjangan terlebih dahulu.',
                });
                return;
            }

            this.memuat = true;
            const form = event.target;

            try {
                const respons = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value
                            || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            || '',
                    },
                    body: new FormData(form),
                });

                const hasil = await respons.json().catch(() => ({}));

                if (!respons.ok || hasil.success === false) {
                    window.tampilkanToast?.({
                        type: 'error',
                        title: hasil.title || 'Tunjangan Gagal Disimpan',
                        action: hasil.action || 'STORE_ALLOWANCE_FAILED',
                        status: hasil.status || 'failed',
                        message: hasil.message || 'Server menolak permintaan. Periksa kembali isian formulir.',
                    });
                    return;
                }

                const totalBaru = Number(hasil?.data?.total_tunjangan ?? this.nilaiAkhir);
                const gajiBaru = Number(hasil?.data?.gaji_bersih ?? 0);

                this.daftarSupir = this.daftarSupir.map((supir) => (
                    String(supir.id) === String(this.idKaryawan)
                        ? { ...supir, tunjangan: totalBaru, gajiBersih: gajiBaru > 0 ? gajiBaru : supir.gajiBersih }
                        : supir
                ));

                this.nominal = new Intl.NumberFormat('id-ID').format(totalBaru);

                window.tampilkanToast?.({
                    type: 'success',
                    title: hasil.title || 'Tunjangan Disimpan',
                    action: hasil.action || 'STORE_ALLOWANCE_SUPIR',
                    status: 'COMPLETED',
                    message: hasil.message || 'Tunjangan supir berhasil disimpan.',
                });
            } catch (_) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Gangguan Koneksi',
                    action: 'network_error',
                    status: 'failed',
                    message: 'Tidak dapat menghubungi server. Coba lagi setelah koneksi pulih.',
                });
            } finally {
                this.memuat = false;
            }
        },

        formatRupiah(nilai) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(nilai ?? 0));
        },

        formatRupiahInput(event) {
            const angka = String(event.target.value).replace(/\D/g, '');
            const terformat = angka === '' ? '0' : new Intl.NumberFormat('id-ID').format(Number(angka));
            event.target.value = terformat;
            return terformat;
        },
    }));
}
