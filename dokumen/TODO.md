# TODO — Sistem Penggajian Supir Logistik Batubara (CoalTrans Payroll)

> **Status dokumen:** digabung dari `TODO.txt` (audit 13 Nov 2026) dan `TODO.md` (rencana kerja) pada 14 Nov 2026.
> **Prinsip dokumen:** hanya memuat pekerjaan berskala modul/fitur. Perbaikan kosmetik (penutup `<div>`, spasi, warna tombol) **tidak** dicatat di sini — itu masuk commit biasa.

---

## 1. Ringkasan Kondisi

| Aspek | Kondisi |
| :--- | :--- |
| Stack | Laravel 13 monolith, Blade + Tailwind 3.4 + Alpine.js, MySQL |
| Role | 3 peran: **Admin HRD**, **Pimpinan**, **Supir** |
| Data master | Supir, unit armada, tarif rute, periode gaji — CRUD lengkap + paginasi server-side |
| Operasional | Surat jalan, pengeluaran armada, kasbon ber-cicilan — query database |
| Penggajian | `PayrollCalculationService` aktif; alur ajukan → ACC/tolak Pimpinan berjalan |
| Portal Supir | Slip gaji, riwayat pengantaran, kasbon mandiri, profil — query database |
| Laporan | Arsip periode, slip cetak, ekspor — berjalan |
| Data contoh | Sudah tidak dipakai di layar utama |

---

## 2. Aturan Baku Proyek

### 2.1 Arsitektur

- **Satu halaman = satu entitas utama.** Entitas pendukung kecil (mis. Jenis Truk) memakai modal bersarang `x-ui.modal-shell` dengan `z-index` berlapis 50 → 60 → 70.
- **Dilarang** menambah React/Vue/Inertia/Livewire/SPA. Grafik memakai SVG native atau CSS.
- Logika perhitungan gaji **wajib** terpusat di `app/Services/PayrollCalculationService.php`.
- Nama variabel/fungsi buatan sendiri: Bahasa Indonesia camelCase tanpa singkatan (`$totalTonaseKg`, `hitungGajiBersih()`).
- Teks entitas disimpan **HURUF BESAR** lewat trait `NormalisasiHurufBesar` (kecuali email, password, enum status, angka, hash).

### 2.2 Tipe Data (tanpa DECIMAL/FLOAT)

| Data | Tipe | Contoh |
| :--- | :--- | :--- |
| Nominal Rupiah | `BIGINT` | `3547658` |
| Berat | `BIGINT` kg | `28480` = 28,48 ton |
| Volume | `BIGINT` liter | `1000` = 1 m³ |
| Persentase komisi | `SMALLINT` basis poin | `1000` = 10,00 % |

### 2.3 Urutan Perhitungan Gaji (wajib sama dengan slip fisik)

1. `total_angkutan_kotor` = SUM(ritase.total_angkutan)
2. `angkutan_bersih` = kotor − potongan_operasional
3. `hasil_bersih_armada` = bersih − uang_jalan − biaya_sparepart
4. `gaji_komisi` = round(hasil_bersih_armada × komisi_bp / 10000)
5. `gaji_bersih` = gaji_komisi + tunjangan − BPJS − potongan_kasbon

### 2.4 Hak Akses

| Peran | Wewenang |
| :--- | :--- |
| **Admin HRD** | Kelola supir, armada, rute, surat jalan, pengeluaran, kasbon, hitung gaji, ajukan ke Pimpinan. **Tidak** dapat membuat akun Pimpinan. |
| **Pimpinan** | Review & ACC/tolak pengajuan gaji, laporan finansial, monitoring. **Dapat** membuat akun Pimpinan & Admin HRD. |
| **Supir** | Portal mandiri: slip, riwayat pengantaran + konfirmasi, kasbon, profil. |

---

## 3. Riwayat Tahapan Selesai

| No | Fokus | Cakupan |
| :-: | :--- | :--- |
| 1 | Fondasi & keamanan | Breeze Blade, `StandarKeamananMiddleware`, `ResponInteraksiStandar`, komponen modal/toast/lightbox, `NormalisasiHurufBesar` |
| 2 | Data master | Supir (NIP otomatis `DRF-{INISIAL}-{2 angka}`, akun portal), armada (+ modal Jenis Truk), rute (+ tarif & uang jalan) |
| 3 | Operasional | Surat jalan (tonase × tarif, pratinjau bersarang), pengeluaran armada, kasbon tenor maks 3× |
| 4 | Paginasi & terminologi | Paginasi server-side 25/50/100 di seluruh tabel; istilah *Ritase* → *Pengantaran/Surat Jalan* |
| 5 | Konfirmasi pengantaran oleh supir | Form konfirmasi tonase timbangan + catatan + foto bukti di `/my/trips`, tersinkron ke admin |
| 6 | Tunjangan & kasbon mandiri | CRUD tunjangan per supir; `POST /my/loans` menyimpan kasbon nyata + jadwal angsuran |
| 7 | Alur otorisasi Pimpinan | Ajukan → ACC/tolak + catatan revisi; kunci cetak slip setelah disetujui |
| 8 | Verifikasi | Halaman HTTP 200, `cek-layanan-payroll` SEMUA COCOH, test & build bersih |

---

## 4. Pekerjaan Tersisa — Skala Modul

### 4.1 Selesai pada 14 Nov 2026

- [x] **Paginasi server-side penuh** — SQL `LIMIT`/`OFFSET` di 7 tabel admin; total periode tetap dihitung lewat agregat SQL, bukan baris halaman.
- [x] **Penyelarasan peran** — nilai role `karyawan` diganti `supir`; hanya tersisa `admin`, `pimpinan`, `supir`.
- [x] **Penghapusan master jabatan** — tabel `jabatan` dan kolom `karyawan.jabatan` dihapus. Skema komisi & BPJS kini murni per-supir (field sudah ada di form supir).
- [x] **Kelola akun pengguna** — modal di `/admin/master/employees` (Admin HRD + Supir) dan portal Pimpinan (semua peran).
- [x] **Sub-tab detail supir** — modal detail kini punya tab *Profil*, *Pengeluaran Operasional*, dan *Kasbon & Potongan*.
- [x] **Dashboard admin** — `/admin/dashboard` sebagai halaman ringkasan tersendiri; `/admin/payroll` fokus ke lembar kerja gaji.
- [x] **Struktur sidebar** — grup **Keuangan & Audit** (Dashboard, Payroll, Laporan) berada tepat setelah menu utama.
- [x] **Akses jaringan lokal** — gerbang izin + token acak; localhost selalu lolos, perangkat lain hanya saat dinyalakan lewat Dev Menu.

### 4.2 Prioritas P0 — Belum Dikerjakan

- [ ] **Iuran BPJS Ketenagakerjaan** — rincian program JKK/JKM/JHT/JP per peserta. Butuh tabel `iuran_bpjs`, route, form request. Titik masuk: `/admin/operational/loans`.
- [ ] **Tunjangan khusus & insentif ritase** — matriks kebijakan berbasis tier (mis. >20 rit = Rp 400.000). Butuh tabel `tunjangan` terpisah dari akumulasi `penggajian.total_tunjangan`.
- [ ] **Memo debet biaya armada** — rekap beban per unit sebelum diajukan bersama payroll. Butuh tabel `biaya_armada` + unggah nota + status persetujuan.
- [ ] **Identitas lengkap supir** — NIK KTP, SIM B2 Umum + masa berlaku, SIMPER, hasil MCU, nomor rangka/mesin. Selama kolom belum ada, kartu terkait ditampilkan "Belum Tersedia" (jangan tampilkan data palsu).

### 4.3 Prioritas P1

- [ ] **Profil Admin HRD** — `/admin/account`: identitas pegawai, hak akses modul, ubah sandi, riwayat aktivitas.
- [ ] **Profil Pimpinan** — `/executive/profile`: level otorisasi, kontak resmi, parameter delegasi, riwayat keputusan approval.
- [ ] **Riwayat perubahan tarif rute** — tabel tersendiri agar perubahan tarif dapat diaudit.
- [ ] **Halaman error kustom** — 403/404/419/500 masih halaman default Laravel.
- [ ] **Validasi akses Pimpinan** — pastikan `/executive/trips` dan laporan benar-benar read-only.

### 4.4 Prioritas P2

- [ ] **Ekspor & impor** — ekspor master data, ekspor kasbon, impor payroll massal (sebagian masih simulasi).
- [ ] **Cetak tambahan** — Buku Kasbon PDF, ID card supir.
- [ ] **Pusat notifikasi & pencarian global** di topbar (pencarian sudah ada, notifikasi belum).

---

## 5. Keputusan yang Sudah Ditetapkan

| Tanggal | Keputusan |
| :--- | :--- |
| 13 Nov 2026 | Halaman `/admin/master/positions` dihapus — skema jabatan tidak dipakai sebagai modul terpisah. |
| 13 Nov 2026 | Tab "Biaya Operasional & Sparepart" dihapus dari Operasional Ritase. Biaya operasional diajukan bersama paket payroll, bukan input harian. |
| 14 Nov 2026 | Tabel `jabatan` **dan** kolom `karyawan.jabatan` dihapus. Komisi % dan BPJS menjadi atribut per-supir. |
| 14 Nov 2026 | Role `karyawan` diganti `supir`. Tidak ada peran lain. |
| 14 Nov 2026 | Admin HRD tidak boleh membuat akun Pimpinan (mencegah naik privilege). Pembuatan akun Pimpinan hanya dari portal Pimpinan. |
| 14 Nov 2026 | Istilah "plafon" dilarang; gunakan "batas kasbon" atau "total pinjaman". |

---

## 6. Skema Database Ringkas

**Tabel inti:** `users`, `karyawan`, `truk`, `jenis_truk`, `rute`, `periode_gaji`, `ritase`, `kasbon`, `angsuran_kasbon`, `penggajian`, `biaya_operasional`.

**Relasi kunci:**

- `users` 1:1 `karyawan` (akun login supir)
- `karyawan` 1:N `ritase`, `kasbon`, `penggajian`
- `truk` 1:N `ritase`; `truk.id_karyawan` → supir pemegang unit
- `periode_gaji` 1:N `ritase`, `penggajian`, `biaya_operasional`
- `kasbon` 1:N `angsuran_kasbon`

**Tabel yang belum ada (menyusul bila diperlukan):** `iuran_bpjs`, `tunjangan`, `biaya_armada`, `log_aktivitas`, `pengaturan`, `riwayat_tarif_rute`.

**Generator & validator ERD:** `Zzz/gen-erd.php`, `Zzz/check-erd.php`.

---

## 7. Pemetaan Kolom Slip Gaji

| Baris slip | Kolom database |
| :--- | :--- |
| NAMA SUPIR | `karyawan.nama_lengkap` |
| VENDOR | `karyawan.vendor` |
| No. Pol | `truk.nomor_polisi` |
| Material | `rute.material` |
| Tanggal | `ritase.tanggal_ritase` |
| Rit / Ton / M³ | `ritase.jumlah_rit`, `tonase_kg`, `volume_liter` |
| Angkutan /Ton, total | `ritase.tarif_per_ton`, `total_angkutan` |
| Uang Jalan | `ritase.uang_jalan` |
| Jumlah Angkutan | `penggajian.total_angkutan_kotor` |
| Potongan Operasional | `penggajian.potongan_operasional` |
| Setelah Operasional | `penggajian.angkutan_bersih` |
| Jumlah Uang Jalan | `penggajian.total_uang_jalan` |
| Biaya Service & S.part | `penggajian.biaya_sparepart` |
| Hasil Bersih Armada | `penggajian.hasil_bersih_armada` |
| Persentase Komisi | `penggajian.persentase_komisi` |
| Gaji Komisi | `penggajian.gaji_komisi` |
| Tunjangan | `penggajian.total_tunjangan` |
| Potongan BPJS | `penggajian.potongan_bpjs` |
| Potongan Kasbon | `penggajian.potongan_kasbon` |
| **GAJI BERSIH** | `penggajian.gaji_bersih` |

---

## 8. Catatan Teknis & Verifikasi

**Perintah verifikasi wajib sebelum menyatakan selesai:**

```cmd
php artisan view:clear
php artisan view:cache
php ..\Zzz\verifikasi-halaman.php
php ..\Zzz\cek-layanan-payroll.php
php artisan test
npm run build
```

**Ambang lulus:** semua halaman terverifikasi HTTP 200; `cek-layanan-payroll` melaporkan SEMUA COCOH; tidak ada kegagalan test baru (`ExampleTest` memang gagal karena `/` redirect ke `/login` — kondisi bawaan).

**Aturan lingkungan:**

- `php artisan config:cache` **dilarang** di lokal (membuat `phpunit.xml` tidak terbaca).
- Semua scratch/script uji disimpan di `Zzz/`, bukan di root proyek, dan dihapus setelah verifikasi.
- Data uji yang dibuat wajib dibersihkan dan diverifikasi bersih.
- OPcache wajib aktif; lihat `docs/BRIEF_SETUP_PHP_INI.md`.

**Script pendukung di `Zzz/`:**

| Script | Fungsi |
| :--- | :--- |
| `verifikasi-halaman.php` | Status HTTP seluruh halaman + simpan HTML |
| `cek-layanan-payroll.php` | Bandingkan output service dengan total seed Jan-26 & Nov-25 |
| `cek-data-db.php` | Jumlah baris + validasi angka slip |
| `cek-koneksi-db.php` | Cek koneksi 127.0.0.1 & localhost port 3309 |
| `check-erd.php` | Validasi XML ERD, relasi putus, kolom tanpa komentar |
| `gen-erd.php` | Menulis ulang `docs/ERD_DATABASE.drawio` |

---

## 9. Catatan Desain

- Tampilan ERD hitam-putih (grayscale): header `#E6E6E6`, isi putih, garis `#666666`, teks `#111111`, latar putih eksplisit agar ekspor PNG tidak transparan.
- Penanda audit teks: `.mark-label` (kuning = label kolom) dan `.mark-dummy` (merah = data contoh). Target akhir: merah = 0 setelah seluruh layar memakai database; kuning boleh tetap sebagai penanda kolom.

<!-- CATATAN SEMENTARA: Fitur tunjangan (total_tunjangan) untuk sementara dihapus dari aplikasi (branch master). Versi lengkap dengan tunjangan tersimpan di branch v1. Isi dokumen ini belum diubah. -->
