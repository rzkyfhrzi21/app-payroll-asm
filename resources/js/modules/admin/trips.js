import { rupiah, numberOnly, parseNumber } from '../../core/helpers.js';

export default function registerAdminTrips(Alpine) {
    Alpine.data('tripsPage', (rows = [], summary = {}, period = {}) => ({
        modalRitaseForm: false,
        modalRitaseDetail: false,
        modalSuratJalan: false,
        modalRitaseHapus: false,
        sedangVerifikasiSj: false,
        ritaseDipilih: {},
        suratJalanDipilih: {},
        indeksFotoAktif: 0,
        setujuRitaseHapus: false,
        editing: false,
        search: '',
        filterSupir: 'all',
        filterRute: 'all',
        filterJenis: 'all',
        filterStatus: 'all',
        tanggalDari: '',
        tanggalSampai: '',
        baris: Array.isArray(rows) ? rows : [],
        form: {
            id: null,
            date: '',
            id_karyawan: '',
            id_truk: '',
            plate: '',
            truck: '',
            id_rute: '',
            material: '',
            rate: 0,
            tonnage: '',
            rit: 1,
            trip_money: 0,
            nama_penerima: '',
            status: 'draft',
            daftar_foto: [],
            foto_dihapus: [],
        },

        init() {
            this.form.date = new Date().toISOString().slice(0, 10);
        },

        get daftarFotoSuratJalanDipilih() {
            const daftar = Array.isArray(this.suratJalanDipilih?.daftar_foto_surat_jalan)
                ? this.suratJalanDipilih.daftar_foto_surat_jalan.filter((item) => typeof item === 'string' && item.trim() !== '')
                : [];
            if (daftar.length > 0) {
                return daftar;
            }
            if (this.suratJalanDipilih?.punya_foto_asli && this.suratJalanDipilih?.foto_surat_jalan) {
                return [this.suratJalanDipilih.foto_surat_jalan];
            }
            return [];
        },

        get fotoAktifSuratJalan() {
            if (this.daftarFotoSuratJalanDipilih.length > 0) {
                return this.daftarFotoSuratJalanDipilih[this.indeksFotoAktif] || this.daftarFotoSuratJalanDipilih[0];
            }
            return this.suratJalanDipilih?.foto_surat_jalan || '/images/placeholders/surat-jalan-default.svg';
        },

        get daftarFotoGaleriLightbox() {
            if (this.daftarFotoSuratJalanDipilih.length > 0) {
                return this.daftarFotoSuratJalanDipilih;
            }
            return [this.fotoAktifSuratJalan];
        },

        toggleHapusFotoAdmin(url) {
            if (!url) return;
            const daftar = Array.isArray(this.form.foto_dihapus) ? [...this.form.foto_dihapus] : [];
            const indeks = daftar.indexOf(url);
            if (indeks === -1) {
                daftar.push(url);
            } else {
                daftar.splice(indeks, 1);
            }
            this.form.foto_dihapus = daftar;
        },

        bersihkanInputFotoAdmin() {
            const inputFoto = document.getElementById('foto_surat_jalan_admin');
            if (inputFoto) {
                inputFoto.value = '';
                inputFoto.dispatchEvent(new Event('change'));
            }
        },

        get adaFilterAktif() {
            return (
                this.search.trim() !== '' ||
                this.filterSupir !== 'all' ||
                this.filterRute !== 'all' ||
                this.filterJenis !== 'all' ||
                this.filterStatus !== 'all' ||
                this.tanggalDari !== '' ||
                this.tanggalSampai !== ''
            );
        },

        resetFilter() {
            this.search = '';
            this.filterSupir = 'all';
            this.filterRute = 'all';
            this.filterJenis = 'all';
            this.filterStatus = 'all';
            this.tanggalDari = '';
            this.tanggalSampai = '';
            window.setTimeout(() => window.sinkronkanSemuaSelect2 && window.sinkronkanSemuaSelect2(document), 30);
        },

        bukaDetailRitase(row) {
            this.ritaseDipilih = { ...row };
            this.modalRitaseDetail = true;
        },

        bukaModalSuratJalan(row) {
            this.suratJalanDipilih = { ...(row || this.ritaseDipilih || {}) };
            this.indeksFotoAktif = 0;
            this.modalSuratJalan = true;
        },

        async ubahStatusSuratJalan(idRitase, statusTujuan) {
            if (!idRitase || this.sedangVerifikasiSj) return;
            this.sedangVerifikasiSj = true;
            const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const respons = await fetch('/admin/operational/trips/status-massal', {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Interaksi-Ajax': '1',
                        'X-CSRF-TOKEN': tokenCsrf,
                    },
                    body: JSON.stringify({
                        ids: [Number(idRitase)],
                        status: statusTujuan,
                    }),
                });

                const hasil = await respons.json().catch(() => ({}));
                if (!respons.ok || hasil.success === false) {
                    window.tampilkanToast?.({
                        type: 'error',
                        title: hasil.title || 'Gagal Mengubah Status Surat Jalan',
                        message: hasil.message || 'Status surat jalan tidak dapat diperbarui.',
                        data: hasil.data || {},
                    });
                    return;
                }

                window.simpanToastRedirect?.(hasil);
                window.tampilkanToast?.(hasil);
                this.modalSuratJalan = false;
                window.setTimeout(() => window.location.reload(), 280);
            } catch (_) {
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Gangguan Koneksi Jaringan',
                    message: 'Tidak dapat menghubungi server untuk memperbarui status surat jalan.',
                });
            } finally {
                this.sedangVerifikasiSj = false;
            }
        },

        bukaHapusRitase(row) {
            this.ritaseDipilih = { ...row };
            this.setujuRitaseHapus = false;
            this.modalRitaseHapus = true;
        },

        get tonnageValue() {
            return parseNumber(this.form.tonnage);
        },

        get totalHaulage() {
            return Math.round(this.tonnageValue * (Number(this.form.rate) || 0));
        },

        formatRp(value) {
            return rupiah(value);
        },

        formatNumber(value) {
            return numberOnly(value);
        },

        parseNumber,

        rowVisible(driver, sj, date, route, jenis, nip, dateIso = '', status = '') {
            const keyword = this.search.toLowerCase().trim();
            const matchesSearch =
                keyword === '' ||
                String(driver).toLowerCase().includes(keyword) ||
                String(sj).toLowerCase().includes(keyword) ||
                String(date).toLowerCase().includes(keyword);

            const matchesSupir = this.filterSupir === 'all' || String(nip) === String(this.filterSupir);
            const matchesRute = this.filterRute === 'all' || String(route) === String(this.filterRute);
            const matchesJenis = this.filterJenis === 'all' || String(jenis) === String(this.filterJenis);
            const matchesStatus = this.filterStatus === 'all' || !status || String(status) === String(this.filterStatus);
            const matchesDari = !this.tanggalDari || !dateIso || String(dateIso) >= String(this.tanggalDari);
            const matchesSampai = !this.tanggalSampai || !dateIso || String(dateIso) <= String(this.tanggalSampai);

            return matchesSearch && matchesSupir && matchesRute && matchesJenis && matchesStatus && matchesDari && matchesSampai;
        },

        openCreate() {
            this.editing = false;
            this.errorsRitase = {};
            this.form = {
                id: null,
                date: new Date().toISOString().slice(0, 10),
                id_karyawan: '',
                id_truk: '',
                plate: '',
                truck: '',
                id_rute: '',
                material: '',
                rate: 0,
                tonnage: '',
                rit: 1,
                trip_money: 0,
                nama_penerima: '',
                status: 'draft',
                daftar_foto: [],
                foto_dihapus: [],
            };
            this.bersihkanInputFotoAdmin();
            this.modalRitaseForm = true;
            window.setTimeout(() => window.sinkronkanSemuaSelect2 && window.sinkronkanSemuaSelect2(document), 40);
        },

        openEdit(row) {
            this.editing = true;
            this.errorsRitase = {};
            this.form = {
                id: row.id,
                date: row.date_iso || '',
                id_karyawan: String(row.id_karyawan || ''),
                id_truk: String(row.id_truk || ''),
                plate: row.plate || '',
                truck: row.truck || '',
                id_rute: String(row.id_rute || ''),
                material: row.material || '',
                rate: Number(row.rate) || 0,
                tonnage: String(row.tonnage || '').replace('.', ','),
                rit: Number(row.rit) || 1,
                trip_money: Number(row.trip_money) || 0,
                nama_penerima: row.nama_penerima || '',
                status: row.status || 'draft',
                daftar_foto: Array.isArray(row.daftar_foto_surat_jalan) ? [...row.daftar_foto_surat_jalan] : [],
                foto_dihapus: [],
            };
            this.bersihkanInputFotoAdmin();
            this.modalRitaseForm = true;
            window.setTimeout(() => window.sinkronkanSemuaSelect2 && window.sinkronkanSemuaSelect2(document), 40);
        },

        closeDrawer() {
            this.modalRitaseForm = false;
        },

        syncDriver(event) {
            const selectEl = event?.target || document.querySelector('select[name="id_karyawan"]');
            const idDipilih = String(selectEl?.value ?? this.form.id_karyawan ?? '');
            this.form.id_karyawan = idDipilih;

            const opsi = selectEl?.querySelector(`option[value="${idDipilih}"]`);
            if (!opsi || !idDipilih) {
                return;
            }

            const idTrukDefault = opsi.getAttribute('data-truk') || '';
            if (idTrukDefault) {
                this.form.id_truk = String(idTrukDefault);
                this.form.plate = opsi.getAttribute('data-plate') || '';
                this.form.truck = opsi.getAttribute('data-truck') || '';
            }

            if (this.errorsRitase.id_karyawan) {
                delete this.errorsRitase.id_karyawan;
            }
            if (this.form.id_truk && this.errorsRitase.id_truk) {
                delete this.errorsRitase.id_truk;
            }

            window.setTimeout(() => window.sinkronkanSemuaSelect2 && window.sinkronkanSemuaSelect2(document), 25);
        },

        syncTruck(event) {
            const selectEl = event?.target || document.querySelector('select[name="id_truk"]');
            const idTruk = String(selectEl?.value ?? this.form.id_truk ?? '');
            this.form.id_truk = idTruk;

            const opsi = selectEl?.querySelector(`option[value="${idTruk}"]`);
            if (!opsi || !idTruk) {
                this.form.plate = '';
                this.form.truck = '';
                return;
            }

            this.form.plate = opsi.getAttribute('data-plate') || '';
            this.form.truck = opsi.getAttribute('data-truck') || '';
            if (this.errorsRitase.id_truk) {
                delete this.errorsRitase.id_truk;
            }
        },

        syncRoute(event) {
            const selectEl = event?.target || document.querySelector('select[name="id_rute"]');
            const idRute = String(selectEl?.value ?? this.form.id_rute ?? '');
            this.form.id_rute = idRute;

            const opsi = selectEl?.querySelector(`option[value="${idRute}"]`);
            if (!opsi || !idRute) {
                this.form.rate = 0;
                this.form.trip_money = 0;
                this.form.material = '';
                return;
            }

            this.form.rate = Number(opsi.getAttribute('data-rate') || 0);
            this.form.trip_money = Number(opsi.getAttribute('data-trip-money') || 0);
            this.form.material = opsi.getAttribute('data-material') || '';
            if (this.errorsRitase.id_rute) {
                delete this.errorsRitase.id_rute;
            }
        },

        errorsRitase: {},
        validasiRitase() {
            const galat = {};
            if (!this.form.date || !/^\d{4}-\d{2}-\d{2}$/.test(this.form.date)) {
                galat.date = 'Tanggal pengantaran wajib diisi dengan format tanggal yang valid.';
            }
            if (!this.form.id_karyawan) {
                galat.id_karyawan = 'Supir pengantar wajib dipilih.';
            }
            if (!this.form.id_truk) {
                galat.id_truk = 'Unit armada truk wajib dipilih.';
            }
            if (!this.form.id_rute) {
                galat.id_rute = 'Rute pengangkutan wajib dipilih.';
            }

            const ton = parseNumber(String(this.form.tonnage || ''));
            if (String(this.form.tonnage || '').trim() === '' || Number.isNaN(ton)) {
                galat.tonnage = 'Tonase wajib diisi berupa angka.';
            } else if (ton < 0.01) {
                galat.tonnage = 'Tonase minimal 0,01 ton.';
            } else if (ton > 100) {
                galat.tonnage = 'Tonase maksimal 100 ton per baris.';
            }

            const ritRaw = String(this.form.rit ?? '').trim();
            const rit = Number(ritRaw);
            if (ritRaw === '' || !Number.isInteger(rit)) {
                galat.rit = 'Jumlah ritase harus berupa bilangan bulat.';
            } else if (rit < 1 || rit > 20) {
                galat.rit = 'Jumlah ritase harus antara 1 sampai 20 kali.';
            }

            const uangJalan = Number(this.form.trip_money ?? 0);
            if (Number.isNaN(uangJalan) || uangJalan < 0) {
                galat.trip_money = 'Uang jalan tidak boleh negatif.';
            } else if (uangJalan > 999999999) {
                galat.trip_money = 'Uang jalan melebihi batas maksimal (Rp 999.999.999).';
            }

            this.errorsRitase = galat;
            const daftarPesan = Object.values(galat);
            if (daftarPesan.length > 0) {
                window.tampilkanToast({
                    type: 'error',
                    title: 'Validasi Surat Jalan Belum Lengkap',
                    action: 'validation_error',
                    status: 'failed',
                    message: daftarPesan[0],
                });
                return false;
            }

            return true;
        },
    }));
}
