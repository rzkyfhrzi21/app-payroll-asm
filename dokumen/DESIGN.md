# Design System & Stitch UI Generation Guide (`DESIGN.md`)
## Coal Hauling Payroll System (Adaptasi Visual Rise Payroll Dashboard)

---

## 1. Filosofi Visual & Referensi Desain
Desain antarmuka mengadaptasi gaya visual **Rise Payroll Dashboard** (*Modern Fintech Clean UI*) yang dikontekstualisasikan untuk perusahaan logistik pengangkutan batubara:
- **Kontras Area Kerja:** Menggabungkan **Dark Navy Sidebar** di sisi kiri (memberikan kesan tegas, industrial, dan fokus) dengan **Crisp Light Grey/White Main Canvas** di sisi kanan agar tabel angka keuangan dan tonase mudah dipindai mata.
- **Hierarki Informasi Keuangan:** Angka krusial seperti *Total Payroll (Net Pay)*, *Total Uang Jalan*, dan *Hasil Bersih Armada* ditampilkan dalam kartu ringkasan berukuran besar dengan kontras tipografi tebal dan banner aksi bergradasi (*Royal Blue to Indigo/Purple*).
- **Indikator Anomali Visual:** Menggunakan ikon peringatan segitiga amber (`⚠️`) di samping nominal uang pada tabel (seperti pada referensi Rise) untuk menandai supir yang memiliki potongan *sparepart* besar, kasbon tinggi, atau data ritase yang perlu ditinjau Pimpinan.

---

## 2. Design Tokens (Tailwind CSS v3.4 Mapping)

### 2.1. Palet Warna (Color Palette)
- **Sidebar Background (Dark):** `#0F111A` s/d `#161927` (`bg-slate-950` / `bg-[#11131F]`)
- **Sidebar Active Item & Accent:** `#3B82F6` (`text-blue-500`, `bg-blue-500/10`)
- **Main Content Background:** `#F8FAFC` (`bg-slate-50`)
- **Card / Surface Background:** `#FFFFFF` (`bg-white`, `border-slate-100`, `shadow-sm`)
- **Primary Brand & CTA Button:** `#2563EB` (`bg-blue-600 hover:bg-blue-700`)
- **Cash Requirement Banner Gradient:** `linear-gradient(90deg, #38BDF8 0%, #4F46E5 60%, #4338CA 100%)` (`bg-gradient-to-r from-sky-400 via-indigo-600 to-indigo-800`)
- **Semantic Financial Colors:**
  - **Net Pay (Take Home Pay):** `#10B981` (`text-emerald-600 font-semibold`)
  - **Warning / Perlu Review (`⚠️`):** `#F59E0B` (`text-amber-500`)
  - **Deductions / Kasbon / Tolak:** `#EF4444` (`text-red-500`)
  - **Muted Secondary Text (Plat Truk / Jabatan):** `#64748B` (`text-slate-500`)

### 2.2. Tipografi (Typography)
- **Font Family Utama:** `Inter` atau `Plus Jakarta Sans` (Sans-serif modern dengan keterbacaan angka tabular yang sangat baik).
- **Aturan Angka Keuangan:** Wajib menerapkan class `tabular-nums` pada seluruh kolom nominal Rupiah dan Tonase agar digit satuan, ribuan, dan jutaan sejajar lurus secara vertikal di dalam tabel.

### 2.3. Tailwind 3.4: Token via `tailwind.config.js`

Breeze Blade stack memakai Tailwind 3.4 + PostCSS. Token didefinisikan di `tailwind.config.js`:

```js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'coal-sidebar': '#11131F',
                'coal-primary': '#2563EB',
                'coal-canvas': '#F8FAFC',
            },
        },
    },
    plugins: [forms],
};
```

Catatan:
- `breeze:install blade` menimpa `vite.config.js`, `postcss.config.js`, `tailwind.config.js`, dan `resources/css/app.css`. Jangan jalankan ulang installer setelah menambahkan token.
- Skeleton Laravel 13 membawa Tailwind 4 (`@tailwindcss/vite`), tetapi Breeze menurunkannya ke Tailwind 3. Hapus `@tailwindcss/vite` dari `package.json` jika masih ada agar tidak membingungkan.
- Selalu tulis warna border eksplisit (`border-slate-100`, `border-slate-200`) dan gunakan `shadow-sm` untuk kartu.
- Template PDF (dompdf) **tidak** memakai `app.css`; gunakan tabel HTML + CSS inline/hex.

---

## 3. Struktur Layout & Spesifikasi Komponen Utama

### 3.1. Sidebar Navigasi (Kiri - Lebar `260px`)
- **Header Sidebar:** Logo Perusahaan Logistik Batubara + Toggle tema/indikator Role (`Admin HRD` / `Pimpinan`).
- **Daftar Menu (Sesuai Use Case):**
  1. `Dashboard`
  2. `Review & Approval` (dengan badge bulat biru jumlah pengajuan *pending*)
  3. `Payroll (Penggajian)` (dengan badge amber `Active`)
  4. `Absensi & Ritase Harian`
  5. `Uang Jalan & Rute`
  6. `Service & Sparepart Truk`
  7. `Potongan & Kasbon`
  8. `Data Karyawan & Armada`
  9. `Laporan Penggajian`
- **Footer Sidebar (Gradient Glass Card):** Kartu kecil berlatar gradasi biru-ungu gelap yang menampilkan *"Total Kasbon Aktif Supir"* atau *"Periode Aktif: Jan-26"* beserta tautan `Log out`.

### 3.2. Halaman Utama Payroll (Adaptasi Presisi Gambar Referensi Rise)
1. **Top Action Bar:**
   - Kiri: Judul besar `Payroll` + Sub-navigasi horizontal dengan ikon: `Current (Periode Berjalan)`, `Previous (Riwayat)`, `Tunjangan`, `Tambah Supir`, `Pengaturan Tarif`.
   - Kanan: Tombol biru `Hitung Gaji Otomatis`, tombol outline `Input Ritase Cepat`, dan `Cetak Rekap PDF` + Avatar profil pengguna.
2. **Hero Summary Section (Grid 12 Kolom):**
   - **Kolom Kiri (5 Kolom) - Pay Cycle Card:**
     - Kartu atas: Ikon kalender biru + Judul `Pay Cycle (Periode Ritase)` + Tanggal `21 Agu 2026 - 18 Sep 2026`.
     - Dua kartu kecil di bawahnya: `Batas Input Ritase & S.Part` (**19 Sep**) dan `Tanggal Gajian / Pay Date` (**25 Sep**).
   - **Kolom Kanan (7 Kolom) - Cash Requirement & Approval Card:**
     - Bagian atas putih (3 metrik sejajar):
       1. `Total Angkutan Bersih`: **Rp 920.785.810**
       2. `Total Uang Jalan Cair`: **Rp 438.500.000**
       3. `Total Potongan S.Part`: **Rp 64.620.000**
     - Bagian bawah (Banner Gradasi Biru-Ungu):
       - Kiri: `Total Gaji Supir (Net Pay)` (**Rp 35.476.580**) | Tengah: `Status` (**Menunggu ACC**).
       - Kanan: Tombol gelap kontras tinggi `Ajukan ACC` (untuk Admin) atau `ACC Penggajian` & `Tolak` (untuk Pimpinan).
3. **Employee Payroll Table (Bagian Bawah):**
   - **Tab Filter Tabel:** `Semua Supir (24)`, `Perlu Review / Warning (4)`, `Non-Aktif / Paused`.
   - **Struktur 8 Kolom Tabel:**
     1. `Nama Supir & Unit Truk`: Avatar + **Kuwat Slamet** + sub-teks `BE 8639 AAU • Hino Putih (KAFA)`.
     2. `Rit / Tonase`: **21 Rit** (`610,00 Ton`).
     3. `Hasil Bersih Truk`: **Rp 41.766.581** (Pendapatan setelah dikurangi Operasional, UJ, & S.Part).
     4. `Gaji Komisi (10%)`: **Rp 4.176.658** (dengan ikon `⚠️` jika ada catatan khusus).
     5. `Potongan (BPJS & Kasbon)`: **Rp 629.000** (rincian: BPJS 129rb + Kasbon 500rb).
     6. `Tunjangan`: **Rp 0**.
     7. `Uang Jalan (Info)`: **Rp 43.850.000** (dana jalan yang sudah dibawa supir).
     8. `Net Pay (Diterima)`: **Rp 3.547.658** (teks hijau emerald tebal) + tombol titik tiga (`...`) untuk *Lihat Rincian Slip Gaji*.

---

## 4. Kumpulan Prompt Siap Pakai untuk Stitch (`Stitch Prompts`)

Salin prompt di bawah ini satu per satu ke dalam **Stitch** (Anda juga dapat melampirkan gambar referensi *Rise Payroll* saat memasukkan Prompt 1):

### Prompt 1: Halaman Utama Payroll Dashboard (Admin & Pimpinan)
> **Prompt untuk Stitch:**
> "Create a modern desktop web application UI for a Coal Logistics Driver Payroll System ('Payroll Penggajian Supir Batubara') inspired by the Rise Payroll dashboard layout.
> **Layout Structure:**
> - **Left Sidebar (Dark Navy `#11131F`):** Company logo 'COALTRANS PAYROLL', navigation items (Dashboard, Review Approval with badge '4', Payroll with 'Active' pill, Absensi & Ritase, Uang Jalan, Sparepart Truk, Kasbon & Potongan, Data Supir, Laporan). At the bottom of the sidebar, add a sleek blue-purple gradient card showing 'Total Kasbon Aktif: Rp 12.500.000' and a Log out link.
> - **Main Content Area (Light `#F8FAFC` background):**
>   - **Top Header:** Large title 'Payroll', top-right action buttons ('Hitung Gaji' primary blue button, 'Input Ritase' outline button, 'Cetak Laporan' outline button, user avatar). Below the title, horizontal tab links: 'Current (Periode Berjalan)', 'Previous (Riwayat)', 'Kelola Tunjangan', 'Tambah Supir', 'Pengaturan Rute'.
>   - **Middle Summary Grid (2 Columns):**
>     - **Left Area:** A white card with calendar icon titled 'Periode Ritase (Pay Cycle): 21 Agu - 18 Sep 2026'. Below it, two side-by-side white cards: 'Batas Akhir Input: 19 Sep' and 'Tanggal Gajian: 25 Sep'.
>     - **Right Area (Cash Requirement Card):** Top white section with 3 financial metrics: 'Total Angkutan Bersih: Rp 920.785.810', 'Total Uang Jalan: Rp 438.500.000', 'Potongan Sparepart: Rp 64.620.000'. Bottom section is a vibrant blue-to-indigo gradient banner displaying 'Total Net Pay Supir: Rp 35.476.580', 'Status: Menunggu ACC', and a dark button 'ACC Penggajian'.
>   - **Bottom Data Table Card:**
>     - Table filter tabs: 'Semua Supir (18)', 'Perlu Review (3)', 'Non-Aktif'.
>     - Clean data table with columns: 'Nama Supir & Truk' (Avatar, Name e.g. 'Kuwat Slamet', subtitle 'BE 8639 AAU - Hino Putih'), 'Rit / Ton' ('21 Rit / 610 Ton'), 'Hasil Bersih Truk' ('Rp 41.766.581'), 'Gaji 10% (Gross)' ('Rp 4.176.658' with small amber warning triangle icon), 'Potongan BPJS & Kasbon' ('Rp 629.000'), 'Uang Jalan (Cair)' ('Rp 43.850.000'), 'Net Pay' ('Rp 3.547.658' in bold emerald green), and a three-dots action menu. Use clean Inter typography and tabular numbers."

### Prompt 2: Modal / Drawer Detail "Rincian Slip Gaji Supir" (Sesuai Foto Slip Gaji Fisik)
> **Prompt untuk Stitch:**
> "Design a clean wide modal or slide-over drawer UI titled 'Rincian Gaji Supir - Periode Jan-26' for a Coal Hauling Payroll web app.
> - **Top Driver Info Card:** Shows Driver Name: 'KUWAT SLAMET', Truck Brand: 'HINO PUTIH', License Plate: 'BE 8639 AAU', Vendor: 'KAFA', and buttons for 'Download PDF Slip' and 'Print'.
> - **Main Haulage Table (Tabel Pengantaran Harian):** Compact bordered table showing daily coal delivery rows with columns: 'Tanggal' (21/08/2026 to 18/09/2026), 'No. Pol' (BE 8639 AAU), 'Material' (BATU BARA), 'Rit' (1), 'Tonase' (e.g. 28,48 Ton), 'Tarif / Ton' (Rp 158.760), 'Angkutan' (Rp 4.521.485), 'Uang Jalan' (Rp 2.250.000), and 'Keterangan Rute' (MIP-PORT / MIP-36). Footer row shows totals: '21 Rit', '610 Ton', 'Total Angkutan: Rp 92.877.797', 'Total Uang Jalan: Rp 43.850.000'.
> - **Bottom Calculation Summary Box (Right-aligned financial breakdown card):**
>   - Jumlah Angkutan Kotor: Rp 92.877.797
>   - Potongan Operasional: - Rp 799.216
>   - Pendapatan Bersih Angkutan (Pend): Rp 92.078.581
>   - Dikurangi Total Uang Jalan (UJ): - Rp 43.850.000
>   - Dikurangi Biaya Service & Sparepart (S.Part): - Rp 6.462.000
>   - **Hasil Bersih Armada:** **Rp 41.766.581**
>   - **Gaji Komisi Supir (10%):** **Rp 4.176.658**
>   - Potongan BPJS: - Rp 129.000
>   - Potongan Kasbon: - Rp 500.000
>   - **Take Home Pay (Gaji Diterima):** **Rp 3.547.658** (highlighted in emerald green box)."

### Prompt 3: Halaman Input Pengantaran Harian / Ritase & Uang Jalan (Admin)
> **Prompt untuk Stitch:**
> "Create a desktop page UI for 'Kelola Absensi & Pengantaran Harian (Ritase Batubara)' using the same dark left sidebar and light main content style.
> - **Top Form Card (Quick Trip Entry):** Input fields in a clean grid: Tanggal Keberangkatan, Pilih Supir & Truk (auto-fills BE 8639 AAU - Hino Putih), Pilih Rute (MIP-PORT @ Rp 158.760/ton or MIP-36 @ Rp 119.880/ton), Berat Muatan (Tonase e.g., 28.48), Auto-calculated 'Total Angkutan' preview (Rp 4.521.485), Input 'Uang Jalan Cair' (Rp 2.250.000), and 'Simpan Ritase' primary button.
> - **Bottom Section:** Filter by Driver, Truck Plate, and Period, followed by a clean data table of recorded daily trips with edit and delete actions."

### Prompt 4: Portal Mandiri Supir (Mobile-First Responsive View untuk Aktor Karyawan)
> **Prompt untuk Stitch:**
> "Create a mobile-responsive employee self-service dashboard UI for a truck driver ('Portal Supir Batubara').
> - **Header:** Greeting 'Halo, Kuwat Slamet', badge showing active truck 'BE 8639 AAU • Hino Putih (Vendor: KAFA)'.
> - **Summary Card (Blue-Indigo Gradient):** Current Period 'Agu - Sep 2026', 'Estimasi Gaji Bersih (Net Pay): Rp 3.547.658', 'Total Muatan: 21 Rit (610 Ton)', and a white button 'Unduh Slip Gaji (PDF)'.
> - **Two Mini Cards:** 'Uang Jalan Diterima: Rp 43.850.000' and 'Sisa Potongan Kasbon: Rp 500.000'.
> - **Bottom List:** 'Riwayat Pengantaran Harian (Ritase)' showing cards per date (18/09/2026 • MIP-PORT • 28,48 Ton • Uang Jalan Rp 2.250.000)."

<!-- CATATAN SEMENTARA: Fitur tunjangan (total_tunjangan) untuk sementara dihapus dari aplikasi (branch master). Versi lengkap dengan tunjangan tersimpan di branch v1. Isi dokumen ini belum diubah. -->
