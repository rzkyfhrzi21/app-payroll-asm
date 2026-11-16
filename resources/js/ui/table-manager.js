export default function registerTableManager(Alpine) {
    Alpine.data('pemilihMassalTabel', ({ url = '' } = {}) => ({
        url,
        terpilih: [],
        statusTujuan: '',
        sedangSimpan: false,

        daftarCentang() {
            return [...this.$root.querySelectorAll('[data-centang-massal]')];
        },

        get semuaTerpilih() {
            const semua = this.daftarCentang();
            return semua.length > 0 && this.terpilih.length === semua.length;
        },

        pilihSemuaBaris(aktif) {
            const semua = this.daftarCentang();
            this.terpilih = aktif ? semua.map((input) => input.value) : [];
        },

        async terapkanStatus() {
            if (this.terpilih.length === 0 || this.statusTujuan === '' || this.sedangSimpan) {
                return;
            }

            this.sedangSimpan = true;

            try {
                const respons = await fetch(url, {
                    method: 'PATCH',
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
                        status: this.statusTujuan,
                    }),
                });

                const muatan = await respons.json().catch(() => ({}));

                if (!respons.ok) {
                    throw new Error(muatan.message || 'Status massal gagal disimpan.');
                }

                window.tampilkanToast?.({
                    type: 'success',
                    title: muatan.title ?? 'Status Massal Diperbarui',
                    action: muatan.action ?? 'BULK_STATUS',
                    status: 'COMPLETED',
                    message: muatan.message ?? '',
                });

                window.setTimeout(() => window.location.reload(), 900);
            } catch (galat) {
                this.sedangSimpan = false;
                window.tampilkanToast?.({
                    type: 'error',
                    title: 'Gagal Mengubah Status',
                    action: 'BULK_STATUS_FAILED',
                    status: 'FAILED',
                    message: galat?.message ?? 'Terjadi kesalahan saat menyimpan status.',
                });
            }
        },
    }));
}

// Modal Focus Trap & Auto-Reset
window.siapkanModalSaatBuka = (modalEl, namaModal = '') => {
    if (!modalEl) return;

    const adalahModalTambah = /Tambah$/i.test(namaModal);
    const form = modalEl.querySelector('form[method="POST"]');

    if (adalahModalTambah && form && !form.hasAttribute('data-sedang-submit')) {
        const terikatAlpine = [...form.querySelectorAll('[x-model]')].map((el) => ({
            el,
            nilai: el.value,
            tercentang: el.checked,
        }));

        form.reset();

        terikatAlpine.forEach(({ el, nilai, tercentang }) => {
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = tercentang;
            } else {
                el.value = nilai;
            }
        });
    }

    window.setTimeout(() => {
        window.sinkronkanSemuaSelect2 && window.sinkronkanSemuaSelect2(modalEl);

        const fokusPertama = modalEl.querySelector(
            'input:not([type="hidden"]):not([disabled]):not(.sr-only), .select2-custom-trigger, textarea:not([disabled]), button:not([aria-label="Tutup dialog"]):not([disabled])'
        );
        if (fokusPertama && typeof fokusPertama.focus === 'function') {
            fokusPertama.focus({ preventScroll: true });
        }
    }, 50);
};

window.trapFokusModal = (event, modalEl) => {
    if (!modalEl || event.key !== 'Tab') return;

    const elemenFokus = Array.from(
        modalEl.querySelectorAll(
            'a[href], button:not([disabled]), textarea:not([disabled]), input:not([type="hidden"]):not([disabled]):not(.sr-only), select:not([disabled]):not(.sr-only), [tabindex]:not([tabindex="-1"])'
        )
    ).filter((el) => el.offsetParent !== null);

    if (elemenFokus.length === 0) {
        event.preventDefault();
        return;
    }

    const pertama = elemenFokus[0];
    const terakhir = elemenFokus[elemenFokus.length - 1];

    if (event.shiftKey && document.activeElement === pertama) {
        event.preventDefault();
        terakhir.focus();
    } else if (!event.shiftKey && document.activeElement === terakhir) {
        event.preventDefault();
        pertama.focus();
    }
};

// Custom Searchable Select (Select2 Replacement)
const daftarInstansiSelect2 = [];

export const bangunSearchableSelect = (selectEl) => {
    if (
        !selectEl ||
        selectEl.dataset.select2Aktif === '1' ||
        selectEl.hasAttribute('data-no-select2') ||
        selectEl.hasAttribute('multiple') ||
        selectEl.closest('[x-data*="calendarPanel"]')
    ) {
        return;
    }

    selectEl.dataset.select2Aktif = '1';

    const diDalamModalField = Boolean(selectEl.closest('.relative')?.querySelector('.material-symbols-outlined:not(.right-2):not(.right-3)'));
    const ikonKiri = selectEl.parentElement?.querySelector('.material-symbols-outlined.pointer-events-none:first-child');
    if (ikonKiri && ikonKiri.textContent.trim() !== 'expand_more') {
        ikonKiri.classList.add('z-10');
    }
    const panahBawaan = selectEl.parentElement?.querySelector('.material-symbols-outlined.pointer-events-none:last-child');
    if (panahBawaan && panahBawaan.textContent.trim() === 'expand_more') {
        panahBawaan.style.display = 'none';
    }

    const kelasAsli = selectEl.className || '';
    const pakaiPaddingKiriIkon = /\bpl-9\b|\bpl-10\b/.test(kelasAsli) || diDalamModalField;
    const tinggiKompak = /\bh-9\b|\bpy-1\.5\b|\bpy-2\b/.test(kelasAsli) && !/\bpy-2\.5\b/.test(kelasAsli);

    selectEl.classList.add('sr-only');
    selectEl.setAttribute('tabindex', '-1');

    const wrapper = document.createElement('div');
    wrapper.className = 'select2-custom-wrapper';

    const tombolTrigger = document.createElement('button');
    tombolTrigger.type = 'button';
    tombolTrigger.className = [
        'select2-custom-trigger',
        pakaiPaddingKiriIkon ? 'pl-9 pr-3' : 'px-3',
        tinggiKompak ? 'h-9 py-1.5' : 'py-2.5',
        kelasAsli.includes('bg-surface-container-low')
            ? 'bg-surface-container-low hover:bg-surface-container/70'
            : kelasAsli.includes('bg-canvas-bg')
              ? 'bg-canvas-bg hover:bg-surface-container-low'
              : 'bg-surface border border-slate-200/80 hover:bg-slate-50',
    ].join(' ');

    const labelTeks = document.createElement('span');
    labelTeks.className = 'truncate font-medium text-slate-text-primary';

    const ikonPanah = document.createElement('span');
    ikonPanah.className = 'material-symbols-outlined shrink-0 text-[1.125rem] text-slate-text-secondary transition-transform duration-150';
    ikonPanah.textContent = 'expand_more';

    tombolTrigger.appendChild(labelTeks);
    tombolTrigger.appendChild(ikonPanah);

    const dropdown = document.createElement('div');
    dropdown.className = 'select2-custom-dropdown hidden';

    const cariWrapper = document.createElement('div');
    cariWrapper.className = 'select2-custom-search';

    const ikonCari = document.createElement('span');
    ikonCari.className = 'material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[1rem] text-slate-text-muted';
    ikonCari.textContent = 'search';

    const inputCari = document.createElement('input');
    inputCari.type = 'text';
    inputCari.className = 'select2-custom-search-input';
    inputCari.placeholder = 'Ketik untuk mencari...';

    cariWrapper.appendChild(ikonCari);
    cariWrapper.appendChild(inputCari);
    dropdown.appendChild(cariWrapper);

    const daftarOpsiEl = document.createElement('div');
    daftarOpsiEl.className = 'select2-custom-options';
    dropdown.appendChild(daftarOpsiEl);

    const perbaruiLabelTrigger = () => {
        const opsiTerpilih = selectEl.options[selectEl.selectedIndex];
        const teks = opsiTerpilih ? opsiTerpilih.textContent.trim() : 'Pilih...';
        labelTeks.textContent = teks;
    };

    const renderOpsi = (kataKunci = '') => {
        daftarOpsiEl.innerHTML = '';
        const kata = kataKunci.toLowerCase().trim();
        const opsiList = Array.from(selectEl.options);
        let cocok = 0;

        opsiList.forEach((opsi) => {
            const teks = opsi.textContent.trim();
            if (kata && !teks.toLowerCase().includes(kata)) {
                return;
            }
            cocok++;

            const tombolOpsi = document.createElement('button');
            tombolOpsi.type = 'button';
            tombolOpsi.className = [
                'select2-custom-option',
                opsi.selected ? 'is-selected' : '',
            ].filter(Boolean).join(' ');

            const teksSpan = document.createElement('span');
            teksSpan.textContent = teks;
            tombolOpsi.appendChild(teksSpan);

            if (opsi.selected) {
                const centang = document.createElement('span');
                centang.className = 'material-symbols-outlined text-[1rem] text-primary';
                centang.textContent = 'check';
                tombolOpsi.appendChild(centang);
            }

            tombolOpsi.addEventListener('click', () => {
                selectEl.value = opsi.value;
                selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                perbaruiLabelTrigger();
                tutupDropdown();
                tombolTrigger.focus();
            });

            daftarOpsiEl.appendChild(tombolOpsi);
        });

        if (cocok === 0) {
            const kosong = document.createElement('div');
            kosong.className = 'px-3 py-2 text-center text-xs text-slate-text-muted font-sans';
            kosong.textContent = 'Tidak ada pilihan yang cocok';
            daftarOpsiEl.appendChild(kosong);
        }
    };

    const bukaDropdown = () => {
        daftarInstansiSelect2.forEach((inst) => {
            if (inst.selectEl !== selectEl) inst.tutupDropdown();
        });

        dropdown.classList.remove('hidden');
        ikonPanah.style.transform = 'rotate(180deg)';
        inputCari.value = '';
        renderOpsi('');

        const rect = tombolTrigger.getBoundingClientRect();
        dropdown.style.minWidth = `${Math.max(rect.width, 220)}px`;

        window.setTimeout(() => inputCari.focus(), 30);
    };

    const tutupDropdown = () => {
        dropdown.classList.add('hidden');
        ikonPanah.style.transform = '';
    };

    tombolTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        if (dropdown.classList.contains('hidden')) {
            bukaDropdown();
        } else {
            tutupDropdown();
        }
    });

    dropdown.addEventListener('click', (e) => e.stopPropagation());

    inputCari.addEventListener('input', () => {
        renderOpsi(inputCari.value);
    });

    inputCari.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            e.stopPropagation();
            tutupDropdown();
            tombolTrigger.focus();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const tombolPertama = daftarOpsiEl.querySelector('button:not([disabled])');
            if (tombolPertama) tombolPertama.click();
        }
    });

    selectEl.addEventListener('change', () => perbaruiLabelTrigger());

    perbaruiLabelTrigger();
    daftarInstansiSelect2.push({ selectEl, perbaruiLabelTrigger, tutupDropdown });

    selectEl.parentNode.insertBefore(wrapper, selectEl.nextSibling);
    wrapper.appendChild(selectEl);
    wrapper.appendChild(tombolTrigger);
    wrapper.appendChild(dropdown);
};

window.sinkronkanSemuaSelect2 = (root = document) => {
    root.querySelectorAll('select').forEach((sel) => bangunSearchableSelect(sel));
    daftarInstansiSelect2.forEach((inst) => inst.perbaruiLabelTrigger());
};

document.addEventListener('click', () => {
    daftarInstansiSelect2.forEach((inst) => inst.tutupDropdown());
    window.setTimeout(() => {
        daftarInstansiSelect2.forEach((inst) => inst.perbaruiLabelTrigger());
    }, 30);
});

// Interactive Table Manager
export const buatElemenStatusAjaxTabel = (kartuTabel) => {
    const barLoading = document.createElement('div');
    barLoading.setAttribute('data-loading-bar', '1');
    barLoading.className = 'hidden border-b border-blue-100 bg-blue-50/80 px-4 py-2 font-sans text-label-sm font-semibold text-primary';
    barLoading.innerHTML = `
        <div class="flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-2">
                <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-primary border-t-transparent"></span>
                <span>Memperbarui data...</span>
            </span>
            <span class="h-2 w-24 animate-pulse rounded bg-primary/20"></span>
        </div>
    `;

    const barError = document.createElement('div');
    barError.setAttribute('data-error-state', '1');
    barError.className = 'hidden border-b border-rose-200 bg-rose-50 px-4 py-2.5 font-sans text-body-sm text-rose-800';
    barError.innerHTML = `
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[1.125rem] text-rose-600">error</span>
                <span data-error-pesan class="font-medium">Gagal memuat data dari server.</span>
            </div>
            <button type="button" data-aksi-retry-ajax class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-1 font-sans text-label-sm font-bold text-white shadow-2xs transition-colors hover:bg-rose-700">
                <span class="material-symbols-outlined text-[0.95rem]">refresh</span>
                <span>Coba Lagi</span>
            </button>
        </div>
    `;

    kartuTabel.insertBefore(barError, kartuTabel.firstChild);
    kartuTabel.insertBefore(barLoading, kartuTabel.firstChild);

    return { barLoading, barError };
};

export const bacaFilterKontekstualHalaman = () => {
    const cariInput = document.querySelector(
        'input[x-model*="search"], input[x-model*="kataKunci"]'
    );
    const tglDariInput = document.querySelector('input[type="date"][x-model*="tanggalDari"]');
    const tglSampaiInput = document.querySelector('input[type="date"][x-model*="tanggalSampai"]');

    return {
        cari: cariInput ? cariInput.value.trim() : '',
        filter: {
            tanggal_dari: tglDariInput ? tglDariInput.value : '',
            tanggal_sampai: tglSampaiInput ? tglSampaiInput.value : '',
        },
    };
};

export const pasangManajerTabelInteraktif = () => {
    document.querySelectorAll('.tabel-status-dan-paginasi').forEach((wadahFooter) => {
        if (wadahFooter.dataset.terpasang === '1') return;
        wadahFooter.dataset.terpasang = '1';

        const kartuTabel = wadahFooter.closest('.overflow-hidden, .shadow-card') || wadahFooter.parentElement;
        const tabel = kartuTabel?.querySelector('table');
        const tbody = tabel?.querySelector('tbody');
        if (!tabel || !tbody) return;

        const namaEntitas = wadahFooter.dataset.entity || '';
        const labelEntitas = wadahFooter.dataset.entityLabel || 'data';
        const ekspresiReset = wadahFooter.dataset.resetExpr || '';

        const { barLoading, barError } = buatElemenStatusAjaxTabel(kartuTabel);
        const teksErrorEl = barError.querySelector('[data-error-pesan]');
        const tombolRetryEl = barError.querySelector('[data-aksi-retry-ajax]');

        const semuaBarisAsli = () =>
            Array.from(tbody.querySelectorAll(':scope > tr:not([data-baris-empty-dinamis])'));

        const barisEmptyDinamis = document.createElement('tr');
        barisEmptyDinamis.setAttribute('data-baris-empty-dinamis', '1');
        barisEmptyDinamis.className = 'hidden';
        const jumlahKolom = tabel.querySelectorAll('thead th').length || 10;

        barisEmptyDinamis.innerHTML = `
            <td colspan="${jumlahKolom}" class="px-4 py-12 text-center">
                <div class="mx-auto flex max-w-md flex-col items-center justify-center gap-2">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-surface-container text-slate-text-muted">
                        <span class="material-symbols-outlined text-[1.75rem]">manage_search</span>
                    </div>
                    <h3 class="font-display text-headline-sm font-bold text-slate-text-primary">Data Tidak Ditemukan</h3>
                    <p class="font-sans text-body-sm text-slate-text-secondary">
                        Tidak ada baris ${labelEntitas} yang cocok dengan kata kunci pencarian atau kombinasi filter saat ini.
                    </p>
                    <button type="button" data-aksi-reset-empty class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3.5 py-2 font-sans text-label-md font-semibold text-primary transition-colors hover:bg-primary hover:text-on-primary">
                        <span class="material-symbols-outlined text-[1rem]">filter_alt_off</span>
                        Reset Pencarian &amp; Filter
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(barisEmptyDinamis);

        const tombolResetEmpty = barisEmptyDinamis.querySelector('[data-aksi-reset-empty]');
        if (tombolResetEmpty) {
            tombolResetEmpty.addEventListener('click', () => {
                const tombolResetHalaman = document.querySelector(
                    `button[\\@click="${ekspresiReset}"], button[x-on\\:click="${ekspresiReset}"]`
                );
                if (tombolResetHalaman) {
                    tombolResetHalaman.click();
                } else {
                    document.querySelectorAll('input[type="text"], input[type="date"]').forEach((inp) => {
                        if (inp.value) {
                            inp.value = '';
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                }
            });
        }

        let halamanAktif = 1;
        let perHalaman = 25;
        let jumlahCocokTerakhir = -1;
        let timerAjax = null;

        wadahFooter.innerHTML = `
            <div class="flex flex-col items-center justify-between gap-3 px-4 py-3 sm:flex-row">
                <div class="flex flex-wrap items-center gap-3 font-sans text-body-sm text-slate-text-secondary">
                    <span data-info-rentang>Menampilkan <strong class="text-slate-text-primary">0–0</strong> dari <strong class="text-slate-text-primary">0</strong> ${labelEntitas}</span>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-text-muted">Baris:</span>
                        <select data-pilih-per-halaman data-no-select2 class="rounded-lg border border-card-border bg-card-bg px-2 py-1 font-sans text-label-md font-semibold text-slate-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-1.5" data-navigasi-halaman></div>
            </div>
        `;

        const infoRentangEl = wadahFooter.querySelector('[data-info-rentang]');
        const pilihPerHalamanEl = wadahFooter.querySelector('[data-pilih-per-halaman]');
        const navigasiHalamanEl = wadahFooter.querySelector('[data-navigasi-halaman]');

        const sinkronkanViaAjaxPost = async () => {
            if (!namaEntitas || typeof window.queryTabelAjaxPost !== 'function') return;

            barError.classList.add('hidden');
            const timerTampilLoading = window.setTimeout(() => {
                barLoading.classList.remove('hidden');
            }, 300);

            try {
                const konteks = bacaFilterKontekstualHalaman();
                await window.queryTabelAjaxPost(namaEntitas, {
                    cari: konteks.cari,
                    filter: konteks.filter,
                    halaman: halamanAktif,
                    per_halaman: perHalaman,
                });
            } catch (_) {
                barError.classList.remove('hidden');
                if (teksErrorEl) {
                    teksErrorEl.textContent = `Gagal memuat data ${labelEntitas}. Silakan coba lagi.`;
                }
            } finally {
                window.clearTimeout(timerTampilLoading);
                barLoading.classList.add('hidden');
            }
        };

        const jadwalkanSinkronAjax = () => {
            if (!namaEntitas) return;
            clearTimeout(timerAjax);
            timerAjax = window.setTimeout(() => sinkronkanViaAjaxPost(), 180);
        };

        if (tombolRetryEl) {
            tombolRetryEl.addEventListener('click', () => sinkronkanViaAjaxPost());
        }

        const hitungDanRenderPaginasi = (paksaRenderTombol = false) => {
            const barisData = semuaBarisAsli().filter((tr) => !tr.querySelector('td[colspan]'));
            if (barisData.length === 0) {
                barisEmptyDinamis.classList.add('hidden');
                return;
            }

            const barisCocok = barisData.filter((tr) => tr.style.display !== 'none');
            const totalCocok = barisCocok.length;

            if (totalCocok !== jumlahCocokTerakhir) {
                halamanAktif = 1;
                paksaRenderTombol = true;
                jumlahCocokTerakhir = totalCocok;
                jadwalkanSinkronAjax();
            }

            const totalHalaman = Math.max(1, Math.ceil(totalCocok / perHalaman));
            if (halamanAktif > totalHalaman) halamanAktif = totalHalaman;

            const indeksMulai = (halamanAktif - 1) * perHalaman;
            const indeksAkhir = Math.min(totalCocok, indeksMulai + perHalaman);

            barisData.forEach((tr) => tr.classList.add('hidden-by-pagination'));

            barisCocok.forEach((tr, idx) => {
                if (idx >= indeksMulai && idx < indeksAkhir) {
                    tr.classList.remove('hidden-by-pagination');
                }
            });

            if (totalCocok === 0) {
                barisEmptyDinamis.classList.remove('hidden');
            } else {
                barisEmptyDinamis.classList.add('hidden');
            }

            if (infoRentangEl) {
                const awalTampil = totalCocok === 0 ? 0 : indeksMulai + 1;
                infoRentangEl.innerHTML = `Menampilkan <strong class="text-slate-text-primary">${awalTampil}–${indeksAkhir}</strong> dari <strong class="text-slate-text-primary">${totalCocok}</strong> ${labelEntitas}`;
            }

            if (paksaRenderTombol && navigasiHalamanEl) {
                navigasiHalamanEl.innerHTML = '';

                const buatTombolHal = (labelHtml, targetHal, disabled = false, aktif = false) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.disabled = disabled;
                    btn.className = [
                        'inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg px-2.5 font-sans text-label-md font-bold transition-colors',
                        aktif
                            ? 'bg-primary text-on-primary shadow-sm'
                            : 'border border-card-border bg-card-bg text-slate-text-primary hover:bg-surface-container-low disabled:opacity-40 disabled:cursor-not-allowed',
                    ].join(' ');
                    btn.innerHTML = labelHtml;
                    if (!disabled && !aktif) {
                        btn.addEventListener('click', () => {
                            halamanAktif = targetHal;
                            hitungDanRenderPaginasi(true);
                            jadwalkanSinkronAjax();
                        });
                    }
                    return btn;
                };

                navigasiHalamanEl.appendChild(
                    buatTombolHal('<span class="material-symbols-outlined text-[1rem]">chevron_left</span>', halamanAktif - 1, halamanAktif <= 1)
                );

                for (let h = 1; h <= totalHalaman; h++) {
                    if (totalHalaman <= 7 || h === 1 || h === totalHalaman || Math.abs(h - halamanAktif) <= 1) {
                        navigasiHalamanEl.appendChild(buatTombolHal(String(h), h, false, h === halamanAktif));
                    } else if (Math.abs(h - halamanAktif) === 2) {
                        const titik = document.createElement('span');
                        titik.className = 'px-1 text-slate-text-muted';
                        titik.textContent = '…';
                        navigasiHalamanEl.appendChild(titik);
                    }
                }

                navigasiHalamanEl.appendChild(
                    buatTombolHal('<span class="material-symbols-outlined text-[1rem]">chevron_right</span>', halamanAktif + 1, halamanAktif >= totalHalaman)
                );
            }
        };

        if (pilihPerHalamanEl) {
            pilihPerHalamanEl.addEventListener('change', () => {
                perHalaman = Math.min(100, Math.max(5, Number(pilihPerHalamanEl.value) || 25));
                halamanAktif = 1;
                hitungDanRenderPaginasi(true);
                jadwalkanSinkronAjax();
            });
        }

        const pengamat = new MutationObserver(() => {
            hitungDanRenderPaginasi(false);
        });
        pengamat.observe(tbody, { attributes: true, subtree: true, attributeFilter: ['style'] });

        window.setTimeout(() => hitungDanRenderPaginasi(true), 60);
    });

    [
        { selector: '#tablePeriode', entitas: 'laporan', label: 'arsip periode' },
        { selector: '#payrollTable', entitas: 'penggajian', label: 'penggajian supir' },
    ].forEach(({ selector, entitas, label }) => {
        const tabelLaporan = document.querySelector(selector);
        if (!tabelLaporan || tabelLaporan.dataset.ajaxTerpasang === '1') return;
        tabelLaporan.dataset.ajaxTerpasang = '1';

        const kartu = tabelLaporan.closest('.overflow-hidden') || tabelLaporan.parentElement;
        if (!kartu) return;

        const { barLoading, barError } = buatElemenStatusAjaxTabel(kartu);
        const teksErrorEl = barError.querySelector('[data-error-pesan]');
        const tombolRetryEl = barError.querySelector('[data-aksi-retry-ajax]');

        const panggilAjaxLaporan = async () => {
            barError.classList.add('hidden');
            const timerTampilLoading = window.setTimeout(() => {
                barLoading.classList.remove('hidden');
            }, 300);
            try {
                const konteks = bacaFilterKontekstualHalaman();
                await window.queryTabelAjaxPost(entitas, {
                    cari: konteks.cari,
                    filter: konteks.filter,
                    halaman: 1,
                    per_halaman: 25,
                });
            } catch (_) {
                barError.classList.remove('hidden');
                if (teksErrorEl) {
                    teksErrorEl.textContent = `Gagal memuat ${label}. Silakan coba lagi.`;
                }
            } finally {
                window.clearTimeout(timerTampilLoading);
                barLoading.classList.add('hidden');
            }
        };

        if (tombolRetryEl) {
            tombolRetryEl.addEventListener('click', () => panggilAjaxLaporan());
        }

        window.setTimeout(() => panggilAjaxLaporan(), 120);
    });
};

export const pasangFormFilterServer = () => {
    document.querySelectorAll('form[data-filter-server]').forEach((form) => {
        if (form.dataset.terpasang === '1') return;
        form.dataset.terpasang = '1';

        const kirimFormulir = () => {
            form.querySelectorAll('input[name="page"]').forEach((input) => input.remove());

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        };

        form.querySelectorAll('[data-filter-debounce]').forEach((elemen) => {
            let timer = null;

            elemen.addEventListener('input', () => {
                clearTimeout(timer);
                timer = window.setTimeout(kirimFormulir, 300);
            });
        });

        form.querySelectorAll('[data-filter-submit]').forEach((elemen) => {
            elemen.addEventListener('change', kirimFormulir);
        });
    });
};

export const pasangFormAjaxModal = () => {
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const metode = (form.getAttribute('method') || 'GET').toUpperCase();
        if (metode !== 'POST') {
            return;
        }

        const diDalamModal = Boolean(form.closest('[role="dialog"]'));
        const pakaiAjaxEksplisit = form.hasAttribute('data-ajax-post');
        const adalahImporExcel = Boolean(form.querySelector('input[name="berkas_migrasi"]'));
        const abaikanAjax = form.hasAttribute('data-no-ajax') || adalahImporExcel;

        if ((!diDalamModal && !pakaiAjaxEksplisit) || abaikanAjax) {
            return;
        }

        event.preventDefault();

        const tombolSubmit = form.querySelector('button[type="submit"], input[type="submit"]');
        const teksAwalTombol = tombolSubmit ? tombolSubmit.innerHTML : '';
        if (tombolSubmit) {
            tombolSubmit.disabled = true;
            tombolSubmit.classList.add('opacity-70', 'cursor-wait');
        }

        const tokenCsrf =
            form.querySelector('input[name="_token"]')?.value ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            '';

        try {
            const respons = await fetch(form.action || window.location.href, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Interaksi-Ajax': '1',
                    'X-CSRF-TOKEN': tokenCsrf,
                },
                body: new FormData(form),
            });

            const tipeKonten = respons.headers.get('content-type') || '';
            if (!tipeKonten.includes('application/json')) {
                window.location.reload();
                return;
            }

            const hasil = await respons.json();

            if (!respons.ok || hasil.success === false) {
                window.tampilkanToast({
                    type: 'error',
                    title: hasil.title || 'Operasi Gagal',
                    action: hasil.action || 'error',
                    status: 'failed',
                    message: hasil.message || 'Terjadi kesalahan saat memproses permintaan.',
                    data: hasil.data || {},
                });
                return;
            }

            window.simpanToastRedirect(hasil);
            window.tampilkanToast(hasil);

            const urlCetakBaru = hasil?.data?.url_cetak || hasil?.data?.print_url;
            if (urlCetakBaru) {
                window.open(urlCetakBaru, '_blank');
            }

            window.setTimeout(() => {
                if (hasil.redirect) {
                    window.location.href = hasil.redirect;
                } else {
                    window.location.reload();
                }
            }, 280);
        } catch (_) {
            window.tampilkanToast({
                type: 'error',
                title: 'Gangguan Koneksi Jaringan',
                action: 'network_error',
                status: 'failed',
                message: 'Tidak dapat menghubungi server. Periksa koneksi jaringan Anda lalu coba kembali.',
            });
        } finally {
            if (tombolSubmit) {
                tombolSubmit.disabled = false;
                tombolSubmit.classList.remove('opacity-70', 'cursor-wait');
                if (teksAwalTombol) {
                    tombolSubmit.innerHTML = teksAwalTombol;
                }
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    window.sinkronkanSemuaSelect2(document);
    pasangFormFilterServer();
    pasangManajerTabelInteraktif();
    pasangFormAjaxModal();
});
