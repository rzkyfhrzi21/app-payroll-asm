import { kalenderLiburId } from '../../core/helpers.js';

export default function registerShared(Alpine) {
    Alpine.data('calendarPanel', () => {
        const sekarang = new Date();
        const formatIso = (tahun, bulan, tanggal) =>
            `${tahun}-${String(bulan + 1).padStart(2, '0')}-${String(tanggal).padStart(2, '0')}`;
        const hariIniIso = formatIso(sekarang.getFullYear(), sekarang.getMonth(), sekarang.getDate());

        return {
            bulanAktif: sekarang.getMonth(),
            tahunAktif: sekarang.getFullYear(),
            tanggalDipilih: hariIniIso,
            daftarBulan: [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
            ],
            daftarHari: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],

            get daftarTahun() {
                const hasil = [];
                for (let t = 2020; t <= 2035; t++) {
                    hasil.push(t);
                }
                return hasil;
            },

            ambilPetaLibur(tahun) {
                const peta = {};
                const daftar = kalenderLiburId.getHolidays(Number(tahun)) || [];
                for (const item of daftar) {
                    if (item.type !== 'public') {
                        continue;
                    }
                    const iso = String(item.date || '').slice(0, 10);
                    if (!iso) {
                        continue;
                    }
                    if (!peta[iso]) {
                        peta[iso] = [];
                    }
                    if (!peta[iso].includes(item.name)) {
                        peta[iso].push(item.name);
                    }
                }
                return peta;
            },

            get daftarSelKalender() {
                const tahun = Number(this.tahunAktif);
                const bulan = Number(this.bulanAktif);
                const petaLibur = this.ambilPetaLibur(tahun);

                const awalBulan = new Date(tahun, bulan, 1);
                const offsetSenin = (awalBulan.getDay() + 6) % 7;
                const jumlahHariBulan = new Date(tahun, bulan + 1, 0).getDate();
                const jumlahHariBulanLalu = new Date(tahun, bulan, 0).getDate();

                const sel = [];

                for (let i = offsetSenin - 1; i >= 0; i--) {
                    const tanggal = jumlahHariBulanLalu - i;
                    const d = new Date(tahun, bulan - 1, tanggal);
                    const iso = formatIso(d.getFullYear(), d.getMonth(), d.getDate());
                    const petaLalu = d.getFullYear() === tahun ? petaLibur : this.ambilPetaLibur(d.getFullYear());
                    const liburNasional = petaLalu[iso] || [];
                    const isMinggu = d.getDay() === 0;

                    sel.push({
                        iso,
                        hari: tanggal,
                        luarBulan: true,
                        isHariIni: iso === hariIniIso,
                        isMinggu,
                        liburNasional,
                        isTanggalMerah: isMinggu || liburNasional.length > 0,
                    });
                }

                for (let tanggal = 1; tanggal <= jumlahHariBulan; tanggal++) {
                    const d = new Date(tahun, bulan, tanggal);
                    const iso = formatIso(tahun, bulan, tanggal);
                    const liburNasional = petaLibur[iso] || [];
                    const isMinggu = d.getDay() === 0;

                    sel.push({
                        iso,
                        hari: tanggal,
                        luarBulan: false,
                        isHariIni: iso === hariIniIso,
                        isMinggu,
                        liburNasional,
                        isTanggalMerah: isMinggu || liburNasional.length > 0,
                    });
                }

                let sisa = 1;
                while (sel.length % 7 !== 0) {
                    const d = new Date(tahun, bulan + 1, sisa);
                    const iso = formatIso(d.getFullYear(), d.getMonth(), d.getDate());
                    const petaDepan = d.getFullYear() === tahun ? petaLibur : this.ambilPetaLibur(d.getFullYear());
                    const liburNasional = petaDepan[iso] || [];
                    const isMinggu = d.getDay() === 0;

                    sel.push({
                        iso,
                        hari: sisa,
                        luarBulan: true,
                        isHariIni: iso === hariIniIso,
                        isMinggu,
                        liburNasional,
                        isTanggalMerah: isMinggu || liburNasional.length > 0,
                    });
                    sisa++;
                }

                return sel;
            },

            get daftarLiburBulanIni() {
                const tahun = Number(this.tahunAktif);
                const bulan = Number(this.bulanAktif);
                const awalan = `${tahun}-${String(bulan + 1).padStart(2, '0')}-`;
                const peta = this.ambilPetaLibur(tahun);

                return Object.entries(peta)
                    .filter(([iso]) => iso.startsWith(awalan))
                    .sort(([a], [b]) => a.localeCompare(b))
                    .map(([iso, namaDaftar]) => {
                        const [t, b, h] = iso.split('-').map(Number);
                        const tglObj = new Date(t, b - 1, h);
                        const labelTanggal = new Intl.DateTimeFormat('id-ID', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                        }).format(tglObj);

                        return {
                            iso,
                            hariAngka: h,
                            labelTanggal,
                            alasan: namaDaftar.join(', '),
                        };
                    });
            },

            get infoTanggalDipilih() {
                const [t, b, h] = String(this.tanggalDipilih || hariIniIso).split('-').map(Number);
                const tglObj = new Date(t, (b || 1) - 1, h || 1);
                const peta = this.ambilPetaLibur(t);
                const liburNasional = peta[this.tanggalDipilih] || [];
                const isMinggu = tglObj.getDay() === 0;
                const catatan = [...liburNasional];
                if (isMinggu) {
                    catatan.push('Hari Libur Akhir Pekan (Minggu)');
                }

                return {
                    iso: this.tanggalDipilih,
                    judul: new Intl.DateTimeFormat('id-ID', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                    }).format(tglObj),
                    isTanggalMerah: isMinggu || liburNasional.length > 0,
                    catatan,
                };
            },

            bulanSebelumnya() {
                if (this.bulanAktif === 0) {
                    this.bulanAktif = 11;
                    this.tahunAktif = Math.max(2020, Number(this.tahunAktif) - 1);
                } else {
                    this.bulanAktif--;
                }
            },

            bulanBerikutnya() {
                if (this.bulanAktif === 11) {
                    this.bulanAktif = 0;
                    this.tahunAktif = Math.min(2035, Number(this.tahunAktif) + 1);
                } else {
                    this.bulanAktif++;
                }
            },

            keHariIni() {
                const kini = new Date();
                this.bulanAktif = kini.getMonth();
                this.tahunAktif = kini.getFullYear();
                this.tanggalDipilih = hariIniIso;
            },

            pilihTanggal(sel) {
                this.tanggalDipilih = sel.iso;
                if (sel.luarBulan) {
                    const [t, b] = sel.iso.split('-').map(Number);
                    this.tahunAktif = t;
                    this.bulanAktif = b - 1;
                }
            },
        };
    });

    Alpine.data('loginPage', () => ({
        showPassword: false,

        togglePassword() {
            const input = this.$refs.password;
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            this.showPassword = input.type === 'text';
        },
    }));

    Alpine.data('formUbahKataSandi', () => ({
        lihatLama: false,
        lihatBaru: false,
        lihatKonfirmasi: false,
        galat: {},

        validasi(form) {
            const error = {};
            const lama = form.querySelector('[name="current_password"]')?.value || '';
            const baru = form.querySelector('[name="password"]')?.value || '';
            const konfirmasi = form.querySelector('[name="password_confirmation"]')?.value || '';

            if (!lama) error.current_password = 'Kata sandi lama wajib diisi.';
            if (!baru) error.password = 'Kata sandi baru wajib diisi.';
            else if (baru.length < 8) error.password = 'Kata sandi baru minimal 8 karakter.';
            if (!konfirmasi) error.password_confirmation = 'Ketik ulang kata sandi baru wajib diisi.';
            else if (baru && konfirmasi !== baru) error.password_confirmation = 'Konfirmasi kata sandi baru tidak cocok.';

            this.galat = error;
            return Object.keys(error).length === 0;
        },
    }));

    Alpine.data('calendarPage', (days = []) => {
        const defaultIndex = Math.max(days.findIndex((day) => day.day === 19 && !day.muted), 0);

        return {
            days,
            selectedIndex: defaultIndex,

            get activeCell() {
                return this.days[this.selectedIndex] ?? this.days[0] ?? { day: '-', events: [] };
            },

            selectDay(index) {
                this.selectedIndex = index;
            },
        };
    });

    Alpine.data('executiveCalendar', (days = []) => {
        const defaultIndex = Math.max(days.findIndex((day) => day.day === 19 && !day.muted), 0);

        return {
            days,
            selectedIndex: defaultIndex,

            get activeCell() {
                return this.days[this.selectedIndex] ?? this.days[0] ?? { day: '-', events: [] };
            },

            selectDay(index) {
                this.selectedIndex = index;
            },
        };
    });

    Alpine.data('jamRealtime', () => ({
        jam: '--:--:--',
        tanggal: '—',
        timer: null,

        mulai() {
            this.perbarui();
            this.timer = setInterval(() => this.perbarui(), 1000);
        },

        perbarui() {
            const sekarang = new Date();

            this.jam = sekarang.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });

            this.tanggal = sekarang.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            });
        },

        destroy() {
            clearInterval(this.timer);
        },
    }));
}
