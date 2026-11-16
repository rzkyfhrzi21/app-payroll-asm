export default function registerAdminMaster(Alpine) {
    Alpine.data('masterPage', (employees = [], summary = {}) => ({
        search: '',
        status: 'all',
        filterUnitSupir: 'all',
        filterPenugasanTruk: 'all',
        filterMaterialRute: 'all',
        addOpen: false,

        modalSupirTambah: false,
        modalSupirEdit: false,
        modalSupirHapus: false,
        modalSupirDetail: false,
        modalTrukTambah: false,
        modalTrukEdit: false,
        modalTrukHapus: false,
        modalTrukDetail: false,
        modalRuteTambah: false,
        modalRuteEdit: false,
        modalRuteHapus: false,
        modalRuteDetail: false,
        modalJenis: false,
        modalJenisEdit: false,
        modalJenisHapus: false,
        modalAkun: false,
        modalAkunEdit: false,
        modalAkunHapus: false,

        jenisDipilih: {},
        setujuJenisHapus: false,
        akunDipilih: {},
        setujuAkunHapus: false,

        setujuSupirHapus: false,
        setujuTrukHapus: false,
        setujuRuteHapus: false,

        supirDipilih: {},
        trukDipilih: {},
        ruteDipilih: {},

        tabDetailSupir: 'profil',
        memuatDetailSupir: false,
        detailSupir: {
            ringkasan: {},
            pengeluaran: [],
            kasbon: [],
        },

        nipBaru: 'DRF-SPR-48',
        nipDiubahManual: false,
        duaAngkaAcakNip: '48',
        namaTerakhirNip: '',
        kataSandiBaru: 'Drv#4829',
        lihatPasswordBaru: false,
        komisiTambah: '10',
        bpjsTambah: '129.000',

        buatDuaAngkaAcak() {
            return String(Math.floor(10 + Math.random() * 90));
        },

        teleponLokal(nilai) {
            return String(nilai ?? '')
                .replace(/\D/g, '')
                .replace(/^62/, '')
                .replace(/^0+/, '');
        },

        hitungInisialNama(nama) {
            const bagian = String(nama || '')
                .trim()
                .toUpperCase()
                .replace(/[^A-Z0-9\s]/g, ' ')
                .split(/\s+/)
                .filter(Boolean);

            if (bagian.length === 0) return 'SPR';
            if (bagian.length === 1) {
                return bagian[0].slice(0, 3).padEnd(2, 'X');
            }
            return bagian
                .slice(0, 3)
                .map((kata) => kata.charAt(0))
                .join('');
        },

        perbaruiNipDariNama(nama) {
            this.namaTerakhirNip = String(nama || '');
            if (this.nipDiubahManual) return;
            const inisial = this.hitungInisialNama(this.namaTerakhirNip);
            this.nipBaru = `DRF-${inisial}-${this.duaAngkaAcakNip}`;
        },

        acakUlangNip() {
            this.nipDiubahManual = false;
            this.duaAngkaAcakNip = this.buatDuaAngkaAcak();
            const inisial = this.hitungInisialNama(this.namaTerakhirNip);
            this.nipBaru = `DRF-${inisial}-${this.duaAngkaAcakNip}`;
        },

        acakKataSandiBaru() {
            const hurufBesar = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const hurufKecil = 'abcdefghijkmnpqrstuvwxyz';
            const angka = '23456789';
            const simbol = '#@!*';
            const pilih = (str) => str.charAt(Math.floor(Math.random() * str.length));

            const karakter = [
                pilih(hurufBesar),
                pilih(hurufKecil),
                pilih(hurufKecil),
                pilih(simbol),
                pilih(angka),
                pilih(angka),
                pilih(angka),
                pilih(hurufBesar),
            ];
            this.kataSandiBaru = karakter.join('');
        },

        rowVisible(name, nip, plate, status) {
            const keyword = this.search.toLowerCase().trim();
            const matchesSearch =
                keyword === '' ||
                String(name).toLowerCase().includes(keyword) ||
                String(nip).toLowerCase().includes(keyword) ||
                String(plate).toLowerCase().includes(keyword);

            const matchesStatus = this.status === 'all' || status === this.status;

            const punyaUnit = Boolean(plate) && String(plate).trim() !== '' && String(plate).trim() !== '—' && String(plate).trim() !== '-';
            let matchesUnit = true;
            if (this.filterUnitSupir === 'pegang') {
                matchesUnit = punyaUnit;
            } else if (this.filterUnitSupir === 'belum') {
                matchesUnit = !punyaUnit;
            }

            return matchesSearch && matchesStatus && matchesUnit;
        },

        trukVisible(nopol, model, vendor, supir, status) {
            const keyword = this.search.toLowerCase().trim();
            const matchesSearch =
                keyword === '' ||
                String(nopol || '').toLowerCase().includes(keyword) ||
                String(model || '').toLowerCase().includes(keyword) ||
                String(vendor || '').toLowerCase().includes(keyword) ||
                String(supir || '').toLowerCase().includes(keyword);

            const matchesStatus = this.status === 'all' || String(status) === String(this.status);

            const dipegangSupir = Boolean(supir) && String(supir).trim() !== '' && String(supir).trim() !== '—' && String(supir).trim() !== '-';
            let matchesPenugasan = true;
            if (this.filterPenugasanTruk === 'dipegang') {
                matchesPenugasan = dipegangSupir;
            } else if (this.filterPenugasanTruk === 'cadangan') {
                matchesPenugasan = !dipegangSupir;
            }

            return matchesSearch && matchesStatus && matchesPenugasan;
        },

        ruteVisible(kode, material, asal, tujuan, status) {
            const keyword = this.search.toLowerCase().trim();
            const matchesSearch =
                keyword === '' ||
                String(kode || '').toLowerCase().includes(keyword) ||
                String(material || '').toLowerCase().includes(keyword) ||
                String(asal || '').toLowerCase().includes(keyword) ||
                String(tujuan || '').toLowerCase().includes(keyword);

            const matchesStatus = this.status === 'all' || String(status) === String(this.status);
            const matchesMaterial = this.filterMaterialRute === 'all' || String(material) === String(this.filterMaterialRute);

            return matchesSearch && matchesStatus && matchesMaterial;
        },

        resetFilterMaster() {
            this.search = '';
            this.status = 'all';
            this.filterUnitSupir = 'all';
            this.filterPenugasanTruk = 'all';
            this.filterMaterialRute = 'all';
        },

        bukaTambah(kind) {
            this.addOpen = false;

            if (kind === 'driver') {
                this.nipDiubahManual = false;
                this.namaTerakhirNip = '';
                this.duaAngkaAcakNip = this.buatDuaAngkaAcak();
                this.nipBaru = `DRF-SPR-${this.duaAngkaAcakNip}`;
                this.acakKataSandiBaru();
                this.lihatPasswordBaru = false;
            }

            const peta = {
                driver: 'modalSupirTambah',
                truck: 'modalTrukTambah',
                route: 'modalRuteTambah',
            };

            if (peta[kind]) {
                this[peta[kind]] = true;
            }
        },

        bukaEditSupir(data) {
            this.supirDipilih = { ...data, telepon_lokal: this.teleponLokal(data.phone) };
            this.setujuSupirHapus = false;
            this.modalSupirEdit = true;
        },

        bukaDetailSupir(data) {
            this.supirDipilih = { ...data };
            this.tabDetailSupir = 'profil';
            this.detailSupir = { ringkasan: {}, pengeluaran: [], kasbon: [] };
            this.memuatDetailSupir = false;
            this.modalSupirDetail = true;
        },

        muatDetailSupir() {
            const idSupir = this.supirDipilih?.id;

            if (!idSupir || this.memuatDetailSupir || this.detailSupir.pengeluaran.length > 0 || this.detailSupir.kasbon.length > 0) {
                return;
            }

            const dasarUrl = this.$root?.dataset?.urlDetailSupir ?? '/admin/master/employees';

            this.memuatDetailSupir = true;

            fetch(`${dasarUrl}/${idSupir}/detail`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then((respons) => (respons.ok ? respons.json() : Promise.reject(respons)))
                .then((muatan) => {
                    this.detailSupir = {
                        ringkasan: muatan?.data?.ringkasan ?? {},
                        pengeluaran: muatan?.data?.pengeluaran ?? [],
                        kasbon: muatan?.data?.kasbon ?? [],
                    };
                })
                .catch(() => {
                    window.tampilkanToast?.({
                        type: 'error',
                        title: 'Gagal Memuat Rincian',
                        action: 'LOAD_EMPLOYEE_DETAIL_FAILED',
                        status: 'failed',
                        message: 'Rincian supir tidak dapat dimuat. Coba tutup dan buka ulang modal detail.',
                    });
                })
                .finally(() => {
                    this.memuatDetailSupir = false;
                });
        },

        bukaHapusSupir(data) {
            this.supirDipilih = { ...data };
            this.setujuSupirHapus = false;
            this.modalSupirHapus = true;
        },

        bukaEditTruk(data) {
            this.trukDipilih = { ...data };
            this.setujuTrukHapus = false;
            this.modalTrukEdit = true;
        },

        bukaDetailTruk(data) {
            this.trukDipilih = { ...data };
            this.modalTrukDetail = true;
        },

        bukaHapusTruk(data) {
            this.trukDipilih = { ...data };
            this.setujuTrukHapus = false;
            this.modalTrukHapus = true;
        },

        bukaEditRute(data) {
            this.ruteDipilih = { ...data };
            this.setujuRuteHapus = false;
            this.modalRuteEdit = true;
        },

        bukaDetailRute(data) {
            this.ruteDipilih = { ...data };
            this.modalRuteDetail = true;
        },

        bukaHapusRute(data) {
            this.ruteDipilih = { ...data };
            this.setujuRuteHapus = false;
            this.modalRuteHapus = true;
        },

        bukaEditJenis(data) {
            this.jenisDipilih = { ...data };
            this.modalJenisEdit = true;
        },

        bukaHapusJenis(data) {
            this.jenisDipilih = { ...data };
            this.setujuJenisHapus = false;
            this.modalJenisHapus = true;
        },

        bukaModalAkun() {
            this.modalAkun = true;
        },

        bukaEditAkun(data) {
            this.akunDipilih = { ...data, telepon_lokal: this.teleponLokal(data.nomor_telepon) };
            this.modalAkunEdit = true;
        },

        bukaHapusAkun(data) {
            this.akunDipilih = { ...data };
            this.setujuAkunHapus = false;
            this.modalAkunHapus = true;
        },

        pilihJenisTambah(event) {
            const opsi = event.target.selectedOptions[0];
            if (!opsi) return;

            const model = opsi.getAttribute('data-model');
            const kolomModel = document.getElementById('merek_model');
            if (model && kolomModel) {
                kolomModel.value = model;
            }
        },

        hapusData() {
            this.$refs.formHapus.submit();
        },

        formatRupiahInput(event) {
            const angka = String(event.target.value).replace(/\D/g, '');
            event.target.value = angka === '' ? '' : Number(angka).toLocaleString('id-ID');
        },

        paksaHurufBesar(event) {
            const posisi = event.target.selectionStart;
            event.target.value = String(event.target.value).toUpperCase();
            event.target.setSelectionRange(posisi, posisi);
        },
    }));

    Alpine.data('jenisTrukPage', () => ({
        modalTambah: false,
        modalEdit: false,
        modalHapus: false,
        dipilih: {},
        setujuHapus: false,

        bukaTambah() {
            this.modalTambah = true;
        },

        bukaEdit(data) {
            this.dipilih = { ...data };
            this.modalEdit = true;
        },

        bukaHapus(data) {
            this.dipilih = { ...data };
            this.setujuHapus = false;
            this.modalHapus = true;
        },
    }));
}
