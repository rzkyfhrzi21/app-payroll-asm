export default function registerAdminReports(Alpine) {
    Alpine.data('reportsArchivePage', (barisPeriode = [], filterAwal = {}) => ({
        daftarPeriode: Array.isArray(barisPeriode) ? barisPeriode : [],
        filterBulan: filterAwal.bulan || 'all',
        filterTahun: filterAwal.tahun || 'all',
        filterStatus: filterAwal.status || 'all',
        kataKunci: filterAwal.cari || '',
        halamanAktif: 1,
        perHalaman: 25,
        modalRingkasanTerbuka: false,
        periodeDipilih: null,
        pesanToast: '',
        tampilToast: false,

        get barisTerfilter() {
            const kunci = String(this.kataKunci || '').toLowerCase().trim();

            return this.daftarPeriode.filter((baris) => {
                const cocokBulan = this.filterBulan === 'all' || String(baris.bulan) === String(this.filterBulan);
                const cocokTahun = this.filterTahun === 'all' || String(baris.tahun) === String(this.filterTahun);
                const cocokStatus = this.filterStatus === 'all' || String(baris.status_filter) === String(this.filterStatus);

                const cocokKunci =
                    kunci === '' ||
                    String(baris.kode || '').toLowerCase().includes(kunci) ||
                    String(baris.rentang_tanggal || '').toLowerCase().includes(kunci) ||
                    String(baris.status_label || '').toLowerCase().includes(kunci) ||
                    String(baris.tanggal_acc || '').toLowerCase().includes(kunci);

                return cocokBulan && cocokTahun && cocokStatus && cocokKunci;
            });
        },

        get totalHalaman() {
            return Math.max(1, Math.ceil(this.barisTerfilter.length / this.perHalaman));
        },

        get barisHalamanIni() {
            const hal = Math.min(this.halamanAktif, this.totalHalaman);
            const mulai = (hal - 1) * this.perHalaman;
            return this.barisTerfilter.slice(mulai, mulai + this.perHalaman);
        },

        get indeksAwal() {
            if (this.barisTerfilter.length === 0) return 0;
            const hal = Math.min(this.halamanAktif, this.totalHalaman);
            return (hal - 1) * this.perHalaman + 1;
        },

        get indeksAkhir() {
            const hal = Math.min(this.halamanAktif, this.totalHalaman);
            return Math.min(this.barisTerfilter.length, hal * this.perHalaman);
        },

        pindahHalaman(halaman) {
            if (halaman >= 1 && halaman <= this.totalHalaman) {
                this.halamanAktif = halaman;
                this.sinkronPaginasiAjax();
            }
        },

        ubahPerHalaman() {
            this.halamanAktif = 1;
            this.sinkronPaginasiAjax();
        },

        sinkronPaginasiAjax() {
            if (typeof window.queryTabelAjaxPost === 'function') {
                window
                    .queryTabelAjaxPost('laporan', {
                        cari: this.kataKunci,
                        filter: {
                            bulan: this.filterBulan,
                            tahun: this.filterTahun,
                            status: this.filterStatus,
                        },
                        halaman: this.halamanAktif,
                        per_halaman: this.perHalaman,
                    })
                    .catch(() => {});
            }
        },

        get totalSupirTerfilter() {
            return this.barisTerfilter.reduce((total, item) => total + Number(item.jumlah_supir || 0), 0);
        },

        get totalRitTerfilter() {
            return this.barisTerfilter.reduce((total, item) => total + Number(item.total_rit || 0), 0);
        },

        get totalTonaseKgTerfilter() {
            return this.barisTerfilter.reduce((total, item) => total + Number(item.total_tonase_kg || 0), 0);
        },

        get totalTonaseTerfilterLabel() {
            return (this.totalTonaseKgTerfilter / 1000).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        get totalNetPayTerfilter() {
            return this.barisTerfilter.reduce((total, item) => total + Number(item.total_net_pay || 0), 0);
        },

        get totalKasbonTerfilter() {
            return this.barisTerfilter.reduce((total, item) => total + Number(item.total_kasbon || 0), 0);
        },

        resetFilter() {
            this.filterBulan = 'all';
            this.filterTahun = 'all';
            this.filterStatus = 'all';
            this.kataKunci = '';
            this.munculkanToast('Filter arsip periode disetel ulang ke semua data.');
        },

        bukaHalamanPeriode(url) {
            if (url) {
                window.location.href = url;
            }
        },

        bukaRingkasanPeriode(baris) {
            this.periodeDipilih = baris;
            this.modalRingkasanTerbuka = true;
        },

        tutupRingkasanPeriode() {
            this.modalRingkasanTerbuka = false;
        },

        formatRupiah(nilai) {
            return 'Rp ' + Number(nilai || 0).toLocaleString('id-ID');
        },

        formatAngka(nilai) {
            return Number(nilai || 0).toLocaleString('id-ID');
        },

        munculkanToast(pesan) {
            this.pesanToast = pesan;
            this.tampilToast = true;
            setTimeout(() => {
                this.tampilToast = false;
            }, 3000);
        },
    }));

    Alpine.data('reportsPeriodDetailPage', (daftarSupir = [], periode = {}, cariAwal = '') => ({
        semuaSupir: Array.isArray(daftarSupir) ? daftarSupir : [],
        infoPeriode: periode || {},
        kataKunci: cariAwal || '',
        tabFilter: 'semua',
        halamanAktif: 1,
        perHalaman: 25,
        modalRitaseTerbuka: false,
        supirDipilih: null,
        cariTiketRitase: '',

        get supirTerfilter() {
            const kunci = String(this.kataKunci || '').toLowerCase().trim();

            return this.semuaSupir.filter((supir) => {
                const cocokCari =
                    kunci === '' ||
                    String(supir.nama || '').toLowerCase().includes(kunci) ||
                    String(supir.nik || '').toLowerCase().includes(kunci) ||
                    String(supir.nopol || '').toLowerCase().includes(kunci) ||
                    String(supir.model_truk || '').toLowerCase().includes(kunci) ||
                    String(supir.vendor || '').toLowerCase().includes(kunci);

                let cocokTab = true;
                if (this.tabFilter === 'anomali') {
                    cocokTab = Boolean(supir.anomali);
                } else if (this.tabFilter === 'tanpa_kasbon') {
                    cocokTab = Boolean(supir.tanpa_kasbon);
                } else if (this.tabFilter.startsWith('vendor:')) {
                    const targetVendor = this.tabFilter.replace('vendor:', '');
                    cocokTab = String(supir.vendor) === targetVendor;
                }

                return cocokCari && cocokTab;
            });
        },

        get totalHalaman() {
            return Math.max(1, Math.ceil(this.supirTerfilter.length / this.perHalaman));
        },

        get supirHalamanIni() {
            const halamanValid = Math.min(this.halamanAktif, this.totalHalaman);
            const mulai = (halamanValid - 1) * this.perHalaman;
            return this.supirTerfilter.slice(mulai, mulai + this.perHalaman);
        },

        get indeksAwal() {
            if (this.supirTerfilter.length === 0) {
                return 0;
            }
            const halamanValid = Math.min(this.halamanAktif, this.totalHalaman);
            return (halamanValid - 1) * this.perHalaman + 1;
        },

        get indeksAkhir() {
            const halamanValid = Math.min(this.halamanAktif, this.totalHalaman);
            return Math.min(this.supirTerfilter.length, halamanValid * this.perHalaman);
        },

        get akumulasiTerfilter() {
            const daftar = this.supirTerfilter;
            const totalTonaseKg = daftar.reduce((sum, item) => sum + Number(item.total_tonase_kg || 0), 0);

            return {
                jumlah_supir: daftar.length,
                total_rit: daftar.reduce((sum, item) => sum + Number(item.total_rit || 0), 0),
                total_tonase_ton: (totalTonaseKg / 1000).toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }),
                total_omzet: daftar.reduce((sum, item) => sum + Number(item.total_angkutan_kotor || 0), 0),
                total_uang_jalan: daftar.reduce((sum, item) => sum + Number(item.total_uang_jalan || 0), 0),
                total_sparepart: daftar.reduce((sum, item) => sum + Number(item.biaya_sparepart || 0), 0),
                total_komisi: daftar.reduce((sum, item) => sum + Number(item.gaji_komisi || 0), 0),
                total_potongan: daftar.reduce((sum, item) => sum + Number(item.total_potongan || 0), 0),
                total_net_pay: daftar.reduce((sum, item) => sum + Number(item.gaji_bersih || 0), 0),
            };
        },

        get tiketRitaseTerfilter() {
            const daftar = Array.isArray(this.supirDipilih?.daftar_ritase)
                ? this.supirDipilih.daftar_ritase
                : [];
            const kunci = String(this.cariTiketRitase || '').toLowerCase().trim();

            if (kunci === '') {
                return daftar;
            }

            return daftar.filter((tiket) => (
                String(tiket.no_do || '').toLowerCase().includes(kunci) ||
                String(tiket.kode_rute || '').toLowerCase().includes(kunci) ||
                String(tiket.tanggal || '').toLowerCase().includes(kunci)
            ));
        },

        pindahHalaman(target) {
            const targetAngka = Number(target);
            if (targetAngka >= 1 && targetAngka <= this.totalHalaman) {
                this.halamanAktif = targetAngka;
                this.sinkronPaginasiAjax();
            }
        },

        ubahPerHalaman() {
            this.halamanAktif = 1;
            this.sinkronPaginasiAjax();
        },

        sinkronPaginasiAjax() {
            if (typeof window.queryTabelAjaxPost === 'function') {
                window
                    .queryTabelAjaxPost('penggajian', {
                        cari: this.kataKunci,
                        filter: {
                            tab: this.tabFilter,
                            periode: this.infoPeriode?.kode || '',
                        },
                        halaman: this.halamanAktif,
                        per_halaman: this.perHalaman,
                    })
                    .catch(() => {});
            }
        },

        resetPencarian() {
            this.kataKunci = '';
            this.tabFilter = 'semua';
            this.halamanAktif = 1;
        },

        bukaModalRitaseSupir(supir) {
            this.supirDipilih = supir;
            this.cariTiketRitase = '';
            this.modalRitaseTerbuka = true;
        },

        tutupModalRitaseSupir() {
            this.modalRitaseTerbuka = false;
        },

        formatRupiah(nilai) {
            return 'Rp ' + Number(nilai || 0).toLocaleString('id-ID');
        },

        formatAngka(nilai) {
            return Number(nilai || 0).toLocaleString('id-ID');
        },
    }));

    Alpine.data('modalMigrasiExcel', (konfigurasi = {}) => ({
        terbuka: Boolean(konfigurasi.bukaOtomatis),
        tabAktif: konfigurasi.tabAwal || 'import',
        targetPeriode: konfigurasi.periodeDefault || 'auto',
        kodePeriodeBaru: '',
        statusPencairan: konfigurasi.statusPencairanAwal || 'belum_cair',
        tanggalPencairan: konfigurasi.tanggalHariIni || '',
        konfirmasiPencairan: false,
        namaBerkas: '',
        ukuranBerkas: '',
        pesanGalat: '',
        sedangMemproses: false,
        formatEkspor: 'multi_sheet',
        periodeEkspor: konfigurasi.periodeEksporDefault || 'all',

        bukaModal(opsi = {}) {
            if (opsi.tab) {
                this.tabAktif = opsi.tab;
            }
            if (opsi.periode) {
                this.targetPeriode = opsi.periode;
                this.periodeEkspor = opsi.periode;
            }
            this.pesanGalat = '';
            this.terbuka = true;
        },

        tutupModal() {
            if (!this.sedangMemproses) {
                this.terbuka = false;
            }
        },

        pilihBerkas(event) {
            const berkas = event.target.files?.[0];
            this.pesanGalat = '';

            if (!berkas) {
                this.namaBerkas = '';
                this.ukuranBerkas = '';
                return;
            }

            const ekstensiValid = /\.(csv|txt|xlsx|xls)$/i.test(berkas.name);
            if (!ekstensiValid) {
                this.pesanGalat = 'Format berkas harus berupa .csv, .xlsx, atau .xls.';
                event.target.value = '';
                this.namaBerkas = '';
                this.ukuranBerkas = '';
                return;
            }

            this.namaBerkas = berkas.name;
            const ukuranKb = berkas.size / 1024;
            this.ukuranBerkas = ukuranKb >= 1024
                ? (ukuranKb / 1024).toFixed(2) + ' MB'
                : Math.max(1, Math.round(ukuranKb)) + ' KB';
        },

        hapusBerkas(inputRef) {
            this.namaBerkas = '';
            this.ukuranBerkas = '';
            this.pesanGalat = '';
            if (inputRef) {
                inputRef.value = '';
            }
        },

        validasiSebelumSubmit(event) {
            this.pesanGalat = '';

            if (!this.namaBerkas) {
                event.preventDefault();
                this.pesanGalat = 'Silakan pilih berkas Excel (.xlsx) atau CSV (.csv) terlebih dahulu.';
                return false;
            }

            if (this.targetPeriode === 'baru' && String(this.kodePeriodeBaru || '').trim() === '') {
                event.preventDefault();
                this.pesanGalat = 'Silakan isi Kode Periode Baru (contoh: Des-25 atau Feb-26).';
                return false;
            }

            if (!this.konfirmasiPencairan) {
                event.preventDefault();
                this.pesanGalat = 'Harap centang konfirmasi status pencairan gaji (Sudah Dicairkan / Belum Dicairkan) sebelum memproses import.';
                return false;
            }

            this.sedangMemproses = true;
            return true;
        },
    }));
}
