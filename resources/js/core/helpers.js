import Holidays from 'date-holidays';

export const kalenderLiburId = new Holidays('ID');

export const rupiah = (value) =>
    'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value) || 0);

export const numberOnly = (value) =>
    new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value) || 0);

export const parseNumber = (value) => {
    const normalized = String(value ?? '')
        .replace(/[^\d,.-]/g, '')
        .replace(/\./g, '')
        .replace(',', '.');

    return Number(normalized) || 0;
};

export const KUNCI_STORAGE_TOAST = 'pending_interaksi_toast';

window.tampilkanToast = (detail = {}) => {
    window.dispatchEvent(new CustomEvent('notify-toast', { detail }));
};

window.simpanToastRedirect = (detail = {}) => {
    try {
        window.sessionStorage.setItem(KUNCI_STORAGE_TOAST, JSON.stringify(detail));
    } catch (_) {
        // Abaikan bila storage tidak tersedia
    }
};

window.bukaLightbox = (detail = {}) => {
    window.dispatchEvent(new CustomEvent('open-lightbox', { detail }));
};

window.buatSvgAvatarDataUri = (inisial = 'U', warnaBg = '#1E40AF') => {
    const teks = String(inisial || 'U').trim().slice(0, 3).toUpperCase();
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160"><rect width="160" height="160" rx="80" fill="${warnaBg}"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#FFFFFF" font-family="Inter, sans-serif" font-size="56" font-weight="700">${teks}</text></svg>`;
    return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`;
};

window.tanganiGambarRusak = (imgEl, inisialFallback = 'U') => {
    if (!imgEl || imgEl.dataset.fallbackTerpasang === '1') return;
    imgEl.dataset.fallbackTerpasang = '1';
    imgEl.onerror = null;
    const inisial = inisialFallback || imgEl.getAttribute('alt') || 'U';
    imgEl.src = window.buatSvgAvatarDataUri(inisial);
};

window.validasiUkuranGambarInput = (event, batasMb = 2) => {
    const input = event?.target;
    const daftarBerkas = Array.from(input?.files || []);
    if (daftarBerkas.length === 0) return true;

    const batasByte = batasMb * 1024 * 1024;
    for (const berkas of daftarBerkas) {
        const ekstensiValid = /\.(jpe?g|png|webp)$/i.test(berkas.name);
        const mimeValid = ['image/jpeg', 'image/png', 'image/webp'].includes(berkas.type);

        if (!ekstensiValid || !mimeValid) {
            input.value = '';
            window.tampilkanToast({
                type: 'error',
                title: 'Format Gambar Tidak Didukung',
                action: 'upload_validation',
                status: 'failed',
                message: `Berkas "${berkas.name}" ditolak. Gunakan gambar berformat JPG, PNG, atau WEBP (otomatis dikonversi ke .webp).`,
            });
            return false;
        }

        if (berkas.size > batasByte) {
            const ukuranMb = (berkas.size / (1024 * 1024)).toFixed(2);
            input.value = '';
            window.tampilkanToast({
                type: 'error',
                title: `Ukuran Gambar Melebihi ${batasMb} MB`,
                action: 'upload_validation',
                status: 'failed',
                message: `Ukuran gambar "${berkas.name}" (${ukuranMb} MB) melebihi batas maksimal ${batasMb} MB.`,
            });
            return false;
        }
    }

    return true;
};

/**
 * Masker input nomor telepon/WhatsApp: hanya angka, tanpa awalan 0/62.
 */
window.maskTeleponInput = (event) => {
    const input = event?.target;
    if (!input) return;

    let digit = String(input.value || '').replace(/\D/g, '').replace(/^62/, '').replace(/^0+/, '');
    digit = digit.slice(0, 12);

    if (input.value !== digit) input.value = digit;
};

window.bersihkanPratinjauFoto = (wadah, wadahGrid = null) => {
    const gambar = wadah?.querySelector('[data-gambar-pratinjau]');
    if (gambar) {
        if (gambar.dataset.objectUrl) {
            URL.revokeObjectURL(gambar.dataset.objectUrl);
            delete gambar.dataset.objectUrl;
        }
        gambar.removeAttribute('src');
    }
    if (wadah) {
        wadah.classList.add('hidden');
    }

    if (wadahGrid) {
        if (Array.isArray(wadahGrid._daftarObjectUrl)) {
            wadahGrid._daftarObjectUrl.forEach((urlObjek) => URL.revokeObjectURL(urlObjek));
        }
        wadahGrid._daftarObjectUrl = [];
        wadahGrid.innerHTML = '';
        wadahGrid.classList.add('hidden');
    }
};

/**
 * Menampilkan pratinjau besar dari berkas gambar yang baru dipilih (mendukung single & multi-file).
 */
window.prosesInputFoto = (event, idPratinjau) => {
    const input = event?.target;
    const wadah = typeof idPratinjau === 'string' ? document.getElementById(idPratinjau) : idPratinjau;
    const gambar = wadah?.querySelector('[data-gambar-pratinjau]');
    const keterangan = wadah?.querySelector('[data-keterangan-berkas]');
    const wadahGrid =
        (typeof idPratinjau === 'string' ? document.getElementById(`${idPratinjau}-grid`) : null) ||
        wadah?.parentElement?.querySelector('[data-pratinjau-grid]') ||
        null;

    if (!input || !wadah || !gambar) return false;

    window.bersihkanPratinjauFoto(wadah, wadahGrid);

    if (!window.validasiUkuranGambarInput(event, 2)) {
        return false;
    }

    const daftarBerkas = Array.from(input.files || []);
    if (daftarBerkas.length === 0) {
        return false;
    }

    if (input.multiple && wadahGrid) {
        const daftarUrlBaru = [];
        daftarBerkas.forEach((berkas, indeks) => {
            const urlObjek = URL.createObjectURL(berkas);
            daftarUrlBaru.push(urlObjek);

            const kartu = document.createElement('div');
            kartu.className = 'flex flex-col overflow-hidden rounded-lg border border-card-border bg-card-bg p-1.5 shadow-sm';

            const bungkusGambar = document.createElement('div');
            bungkusGambar.className = 'relative h-24 w-full overflow-hidden rounded-md bg-slate-900/5';

            const imgEl = document.createElement('img');
            imgEl.src = urlObjek;
            imgEl.alt = berkas.name;
            imgEl.className = 'h-full w-full object-cover';

            const lencana = document.createElement('span');
            lencana.className = 'absolute left-1 top-1 rounded bg-slate-950/75 px-1.5 py-0.5 font-sans text-[0.625rem] font-semibold text-white';
            lencana.textContent = `Foto #${indeks + 1}`;

            bungkusGambar.appendChild(imgEl);
            bungkusGambar.appendChild(lencana);

            const namaEl = document.createElement('span');
            namaEl.className = 'mt-1.5 truncate font-sans text-[0.6875rem] font-semibold text-slate-text-primary';
            namaEl.title = berkas.name;
            namaEl.textContent = berkas.name;

            const ukuranEl = document.createElement('span');
            ukuranEl.className = 'font-sans text-[0.625rem] text-slate-text-secondary';
            ukuranEl.textContent = `${(berkas.size / (1024 * 1024)).toFixed(2)} MB`;

            kartu.appendChild(bungkusGambar);
            kartu.appendChild(namaEl);
            kartu.appendChild(ukuranEl);
            wadahGrid.appendChild(kartu);
        });

        wadahGrid._daftarObjectUrl = daftarUrlBaru;
        wadahGrid.classList.remove('hidden');
        return true;
    }

    const berkas = daftarBerkas[0];
    const url = URL.createObjectURL(berkas);
    gambar.dataset.objectUrl = url;
    gambar.src = url;
    wadah.classList.remove('hidden');

    if (keterangan) {
        keterangan.textContent = `${berkas.name} • ${(berkas.size / (1024 * 1024)).toFixed(2)} MB`;
    }

    return true;
};

window.queryTabelAjaxPost = async (entitas, opsi = {}) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const halaman = Number(opsi.halaman ?? 1) || 1;
    const perHalaman = Number(opsi.per_halaman ?? 25) || 25;
    const url = `/admin/interaksi/tabel?entitas=${encodeURIComponent(entitas)}&halaman=${halaman}&per_halaman=${perHalaman}`;
    const respons = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Interaksi-Ajax': '1',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({
            entitas,
            cari: opsi.cari ?? '',
            filter: opsi.filter ?? {},
            sort_by: opsi.sort_by ?? 'id',
            sort_dir: opsi.sort_dir ?? 'desc',
            halaman,
            per_halaman: perHalaman,
            page: halaman,
            per_page: perHalaman,
        }),
    });

    if (!respons.ok) {
        throw new Error(`HTTP ${respons.status}`);
    }

    const json = await respons.json();
    if (json && json.success === false) {
        throw new Error(json.message || 'Gagal memuat data tabel.');
    }

    return json;
};
