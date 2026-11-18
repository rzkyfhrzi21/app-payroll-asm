# PEMBAHASAN ARSITEKTUR, ANALISIS PERFORMA, & BRIEFING FITUR IMPOR-EKSPOR EXCEL
**Sistem Penggajian Supir Logistik Batubara — PT Adiguna Sejahtera Mandiri (PT ASM)**  
*Dokumen analisis latensi unggah, audit integritas data, dan spesifikasi teknis fitur migrasi Excel.*

> **Status dokumen:** revisi 18 November 2026. Bagian performa dan beberapa klaim teknis pada versi sebelumnya tidak sesuai kode; angka pada dokumen ini berasal dari pengukuran langsung terhadap basis data TiDB Cloud yang dipakai aplikasi.

---

## DAFTAR ISI
1. [Ringkasan Temuan](#1-ringkasan-temuan)
2. [Analisis Akar Masalah Performa](#2-analisis-akar-masalah-performa)
3. [Solusi Optimasi yang Diterapkan](#3-solusi-optimasi-yang-diterapkan)
4. [Analisis Berkas Template Kosong](#4-analisis-berkas-template-kosong)
5. [Briefing Teknis untuk Agen Pengembang](#5-briefing-teknis-untuk-agen-pengembang)
6. [Daftar Berkas Terlibat](#6-daftar-berkas-terlibat)
7. [Status Surat Jalan, Pencairan, & Kasbon Hasil Migrasi](#7-status-surat-jalan-pencairan--kasbon-hasil-migrasi)
8. [Verifikasi Sebelum Menyatakan Selesai](#8-verifikasi-sebelum-menyatakan-selesai)
9. [Riwayat Revisi Dokumen](#9-riwayat-revisi-dokumen)

---

## 1. RINGKASAN TEMUAN

Fitur migrasi Excel memiliki **dua jalur eksekusi yang berbeda karakter**:

| Jalur | Method | Sifat | Beban dominan |
|:---|:---|:---|:---|
| Pratinjau (baca) | `analisisStagingMigrasi()` | Hanya membaca & menyimpan staging ke cache | 8 query SELECT master + 1 tulis cache |
| Eksekusi (tulis) | `eksekusiStagingMigrasi()` | Menulis user, karyawan, truk, rute, ritase, penggajian, kasbon | INSERT/UPDATE massal + transaksi |

Temuan utama:

1. **N+1 per baris ritase terjadi di jalur eksekusi, bukan di jalur pratinjau.** Versi lama `eksekusiStagingMigrasi()` menembak `Rute::where()` dan `Truk::with('jenisTruk')->where()` untuk setiap baris ritase. Pada 11 slip / 268 baris terukur **625 query dan 81,74 detik**.
2. **Jalur pratinjau sudah memakai preloading** dan tidak melakukan query per baris. Terukur **11 slip ≈ 1,8 detik**, **220 slip ≈ 3,2 detik**, dan berkas `DUMMY Slip Gaji.xlsx` (100 slip / 2.227 ritase) ≈ **3,45 detik**.
3. **RTT TiDB Cloud terukur ±100–150 ms per query**, bukan 40–70 ms. Ini yang membuat target "< 0,5 detik" pada dokumen lama tidak mungkin dicapai.
4. **Impor `sudah_cair` sekarang menulis jejak pencairan dan menyelesaikan kasbon**: `dicairkan_pada` terisi, angsuran berstatus `terpotong`, `sisa_pinjaman` berkurang, dan kasbon menjadi `lunas` saat sisa mencapai 0.
5. **Data ritase hasil migrasi selalu berstatus `terverifikasi`** pada jalur staging, agar ikut dihitung `rekonsiliasiPeriode()` dan tidak memblokir pengajuan periode.
6. **Unduh "Contoh Format Migrasi" dan "Template Kosong" menyajikan dua berkas berbeda**: contoh berisi data (`DUMMY Slip Gaji.xlsx`), template masih kosong (`CONTOH FORMAT Slip Gaji.xlsx`).

---

## 2. ANALISIS AKAR MASALAH PERFORMA

### 2.1. Dua Jalur, Dua Pola Query

**A. Jalur pratinjau — `analisisStagingMigrasi()`**

Method ini memuat seluruh master ke memori lebih dulu, lalu memproses baris Excel tanpa menyentuh database per baris:

```php
$petaPeriodeGaji = PeriodeGaji::query()->get(['kode', 'status'])->keyBy('kode');
$petaKaryawan    = Karyawan::query()->with('pengguna')->get()->keyBy('nama_lengkap');
$petaTruk        = Truk::query()->with('jenisTruk')->get()->keyBy('nomor_polisi');
$petaRute        = Rute::query()->get()->keyBy('kode');
$daftarEmailTerpakai = User::query()->pluck('email')->map(fn ($e) => strtolower((string) $e))->all();
$daftarNikTerpakai   = Karyawan::withTrashed()->pluck('nomor_induk_karyawan')->all();
```

Jumlah query SELECT-nya **8**, bukan 4: `Karyawan::with('pengguna')` dan `Truk::with('jenisTruk')` masing-masing menghasilkan 2 query. Ditambah satu operasi tulis cache staging. Tidak ada query di dalam loop baris ritase.

**B. Jalur eksekusi — `eksekusiStagingMigrasi()`**

Versi lama melakukan, untuk setiap baris ritase:
- `Rute::where('kode', ...)->first()` → 1 query;
- `Truk::with('jenisTruk')->where('nomor_polisi', ...)->first()` → 2 query;
- ditambah satu `INSERT` per baris ritase.

Untuk 11 slip / 268 baris terukur **625 query / 81,74 detik**. Setelah perbaikan (preload master, hapus per periode, `INSERT` per 200 baris) menjadi **19 query / 2,84 detik**. Berkas 55 slip / 1.340 baris selesai dalam **24 query / 4,12 detik**.

### 2.2. Latensi Jaringan & Matematika

- **Host:** `gateway01.ap-southeast-1.prod.aws.tidbcloud.com:4000` (AWS Singapura).
- **RTT terukur:** ±**100–150 ms** per query melalui TLS.
- Jika pola lama dipakai untuk 8 supir × 20 baris: `8×8 + 160×3 = 544` query (bukan 384), dengan 100–150 ms menjadi **54–82 detik**. Angka ini konsisten dengan 81,74 detik yang benar-benar terjadi.

> **Kesimpulan:** penyebab utama bukan ukuran berkas atau CPU, melainkan **jumlah round-trip query ke database remote**. Optimasi harus menurunkan jumlah query, bukan sekadar mempercepat loop PHP.

---

## 3. SOLUSI OPTIMASI YANG DITERAPKAN

### 3.1. Jalur Baca (Pratinjau)
- Preload 8 SELECT master ke Collection / hash map di awal method.
- Pengecekan supir, truk, rute, periode, NIK, dan email dilakukan di RAM.
- Hasil analisis disimpan ke cache `migrasi_staging_{token}` dengan TTL 2 jam.
- **Target realistis:** ±1,2–3,5 detik tergantung jumlah slip; bukan "< 0,5 detik" atau "< 800 ms".

### 3.2. Jalur Tulis (Eksekusi)
1. Preload master (truk, rute, periode, karyawan, email, NIK) satu kali.
2. Kumpulkan baris ritase di memori, lalu `INSERT` per **200 baris**.
3. Hapus ritase lama per pasangan periode–karyawan dengan satu query per periode.
4. Agregasi baris `penggajian` di memori dengan kunci `id_periode_gaji|id_karyawan`. **Jangan memakai `upsert()`**: tabel `penggajian` tidak memiliki unique index pada pasangan kolom tersebut.
5. Muat seluruh angsuran kasbon terkait satu kali, cocokkan di memori.
6. Bungkus seluruh mutasi dalam `DB::transaction()`.
7. **Periksa ulang kunci periode di dalam transaksi** dengan `lockForUpdate()`, karena staging dapat berumur hingga 2 jam.

### 3.3. Hasil Pengukuran

| Skenario | Sebelum | Sesudah |
|:---|:---|:---|
| Eksekusi 11 slip / 268 baris | 625 query, 81,74 s | 19 query, 2,84 s |
| Eksekusi 55 slip / 1.340 baris | — | 24 query, 4,12 s |
| Pratinjau 11 slip | — | ±1,8 s |
| Pratinjau 220 slip | — | ±3,2 s |

---

## 4. ANALISIS BERKAS TEMPLATE KOSONG

Berkas `dokumen/CONTOH FORMAT Slip Gaji.xlsx` memiliki 3 sheet:
- `INPUT DATA SLIP` — lembar data yang diproses;
- `PANDUAN FORMAT VISUAL` — panduan, otomatis dilewati;
- `PETUNJUK & BATASAN KOLOM` — petunjuk, otomatis dilewati.

Struktur `INPUT DATA SLIP`:
- **Baris 1:** judul `RINCIAN GAJI SUPIR`.
- **Baris 2:** kalimat standar nama sheet.
- **Baris 3:** identitas supir: `NAMA SUPIR` + `[NAMA SUPIR]`, `MERK`, `PERIODE`.
- **Baris 4:** `PLAT` + `[NO POLISI]`.
- **Baris 6:** header tabel (`Tanggal`, `No. Pol`, `Material`, `Vol`, `Ton`, `Rit`, `Tarif/Ton`, `Angkutan`, `Uang Jalan`, `Keterangan`).
- **Baris 8–32:** baris transaksi.
- **Baris 33:** baris `JUMLAH`.

Validator hanya mencatat ritase jika `tonase_kg > 0`. Pada template kosong seluruh tonase bernilai 0, sehingga `uraikanSatuBlokSlip()` menghasilkan `ritase = []`, `pecahMenjadiBlokSlip()` mengembalikan `[]`, dan **pemanggil** (`analisisStagingMigrasi()`) yang melempar `RuntimeException`.

Pesan galat yang sekarang dipakai:

> *"Berkas tidak memuat satu pun transaksi ritase yang terisi. Jika Anda mengunggah template kosong, isi minimal 1 baris tanggal, jumlah rit, dan tonase muatan (lebih dari 0) terlebih dahulu. Jika memakai format lain, pastikan berkas memuat blok "RINCIAN GAJI SUPIR" pada lembar data supir."*

---

## 5. BRIEFING TEKNIS UNTUK AGEN PENGEMBANG

Gunakan teks berikut sebagai spesifikasi kerja. Angka dan nama method sudah disesuaikan dengan implementasi aktual.

```markdown
### PROMPT PENGERJAAN: PRATINJAU, VALIDASI BENTROK, & EKSEKUSI IMPOR EXCEL PENGGAJIAN

Anda bertindak sebagai Senior Laravel Fullstack Engineer untuk proyek Sistem Penggajian Supir Logistik Batubara (Laravel Blade + Tailwind CSS + MySQL/TiDB Serverless).

#### 1. ALUR PENGUNGGAHAN & PEMBUKAAN TAB BARU
- Form impor pada `resources/views/components/payroll/excel-sync-modal.blade.php` wajib memiliki `action="{{ route('admin.reports.migrasi.preview') }}"`, `target="_blank"`, dan `data-no-ajax`.
- Tombol utama berlabel "Buka Pratinjau & Validasi Data (Tab Baru)".
- `resources/js/ui/table-manager.js` (`pasangFormAjaxModal`) TIDAK boleh mencegat form dengan `target="_blank"` atau `data-no-ajax`.
- Endpoint `POST /admin/reports/migrasi/preview` memvalidasi berkas:
  - ukuran maksimal **25 MB**;
  - ekstensi **`.xlsx`, `.csv`, atau `.txt`** (rule server: `extensions:xlsx,csv,txt`);
  - berkas `.xls` lama ditolak dengan pesan agar disimpan ulang sebagai `.xlsx`.
- Berkas diparsing tanpa menulis ke database. Staging disimpan di cache `migrasi_staging_{token}` (TTL 2 jam), lalu redirect ke `GET /admin/reports/migrasi/preview/{token}`.

#### 2. OPTIMASI LATENSI (ANTI-N+1)
- Database TiDB Cloud (AWS Singapura) memiliki RTT ±100–150 ms per query.
- Jalur pratinjau: preload seluruh master (Karyawan, Truk, Rute, Periode, set NIK, set Email) di awal `analisisStagingMigrasi()`. Dilarang query per baris Excel.
- Jalur eksekusi: preload master, kumpulkan baris ritase, `INSERT` per 200 baris, dan hapus ritase lama per periode. Dilarang `Rute::where()` / `Truk::where()` di dalam loop baris.
- Jangan memakai `upsert()` untuk `penggajian` karena tidak ada unique index `(id_periode_gaji, id_karyawan)`. Gunakan peta in-memory lalu `create()` / `save()`.

#### 3. PENGECEKAN ENTITAS & RESOLUSI BENTROK 3-ARAH
A. Resolusi Supir:
   - Tandai `[TERDAFTAR]` vs `[BELUM TERDAFTAR]`.
   - Opsi: "Gabungkan ke Supir Terdaftar", "Buat Akun Baru Otomatis", atau "Lewati Impor Supir Ini".
   - NIK otomatis: `DRF-{INISIAL}-{2 DIGIT ACAK}` (dapat disunting admin).
   - Email default: `{NAMASUPIR}_ASM@gmail.com` (lowercase alfanumerik, diberi nomor urut bila bentrok).
   - Password default: `password`; role `supir`; status `aktif`.
   - Tombol massal: "Pilih Buat Semua Akun Baru" dan "Pilih Lewati Semua Supir Baru".
B. Resolusi Unit Armada:
   - Tandai `[TERDAFTAR]` vs `[BELUM TERDAFTAR]`.
   - Opsi: "Daftarkan Unit Baru Otomatis" atau "Hubungkan ke Unit Terdaftar".
   - Tombol "Daftar via Modal" membuka modal AJAX untuk mendaftarkan plat/jenis/merek tanpa meninggalkan tab pratinjau.
C. Resolusi Rute:
   - Keputusan hanya berdasarkan ada/tidaknya `kode` rute di master. Tarif dan uang jalan **ditampilkan** sebagai informasi, bukan dasar keputusan.
D. Periode Target:
   - Jika periode berstatus `approved` atau `paid`, tampilkan banner "PERIODE TERKUNCI" dan nonaktifkan tombol eksekusi.
   - **Wajib cek ulang status periode di dalam transaksi eksekusi** memakai `lockForUpdate()`; staging bisa berumur 2 jam dan ACC Pimpinan bisa terjadi setelah pratinjau dibuat.

#### 4. DETEKSI ANOMALI OPERASIONAL
1. "Anomali Unit Berbeda pada Tanggal & Rute Sama": supir, tanggal, dan kode rute sama, tetapi plat truk berbeda.
2. "Anomali Supir Banyak Unit": dalam satu tanggal yang sama, seorang supir memakai lebih dari satu plat truk (tanpa syarat rute sama).
3. "Anomali Selisih Angkutan": selisih nominal antara `Tonase × Tarif/Ton` dan angka angkutan di berkas melebihi Rp 1.000.
Ketiga anomali wajib tampil di panel peringatan halaman pratinjau.

#### 5. TABEL PRATINJAU REKAPITULASI
Kolom aktual: `No | Supir di Excel | Plat Truk | Rit | Tonase | Uang Jalan | Sparepart | Komisi Supir | BPJS & Kasbon | Gaji Bersih | Status Aksi | Rincian`.
- Tidak ada kolom "Angkutan Kotor" pada tabel pratinjau.
- Baris supir dapat diperluas untuk melihat rincian harian: `Tanggal | No. Pol | Material | Rit | Tonase | Tarif/Ton | Total Angkutan | Uang Jalan | Rute`.
- Footer menghitung akumulasi ritase, tonase, dan gaji bersih.

#### 6. EKSEKUSI & PROGRESS BAR
- Tombol eksekusi membuka modal progress, lalu mengirim AJAX `POST /admin/reports/migrasi/eksekusi-async`.
- Client memantau `GET /admin/reports/migrasi/status-progress/{token}` setiap 350 ms.
- **Catatan arsitektur:** tidak ada queue/job. Request HTTP ditahan sampai transaksi selesai. Pada `php artisan serve` dengan satu worker, polling akan mengantre di belakang request impor sehingga progress bar tampak diam; gunakan `--workers=4` saat menguji progress.
- Setelah selesai, tampilkan pesan sukses dan redirect ke `/admin/reports` disertai toast.

#### 7. TRANSAKSI, BATCHING, & INTEGRITAS DATA
- Seluruh mutasi dibungkus `DB::transaction()`.
- Urutan: preload master → daftarkan unit/rute baru → hapus ritase lama per periode → insert ritase per 200 baris → hitung & simpan penggajian → selesaikan kasbon (bila `sudah_cair`).
- Jika satu langkah gagal, seluruh mutasi di-rollback.
- Data ritase hasil migrasi wajib berstatus `terverifikasi` agar `rekonsiliasiPeriode()` menghitungnya dan `PayrollController::submit()` tidak memblokir periode karena SJ berstatus `draft`.

#### 8. TAUTAN BERKAS CONTOH & TEMPLATE
- "Unduh Contoh Format Migrasi" → `dokumen/DUMMY Slip Gaji.xlsx` (100 sheet berisi data, dapat langsung diuji unggah).
- "Template Kosong" → `dokumen/CONTOH FORMAT Slip Gaji.xlsx` (3 sheet, masih placeholder).
- Keduanya disajikan sebagai `.xlsx`. Jangan mengarahkan kedua tautan ke berkas yang sama.

#### 9. STANDAR VERIFIKASI
Jalankan dari folder `app-payroll-asm`:
1. `php artisan view:clear`
2. `php artisan view:cache`
3. `php ..\Zzz\verifikasi-halaman.php`
4. `php ..\Zzz\cek-layanan-payroll.php`
5. `php artisan test`
6. `npm run build`
```

---

## 6. DAFTAR BERKAS TERLIBAT

| No | Berkas | Tanggung Jawab |
|:---|:---|:---|
| 1 | `app/Services/ExcelPayrollMigrationService.php` | Ekstraksi spreadsheet, `generateDefaultNik()`, `generateDefaultEmail()`, `analisisStagingMigrasi()` (preloading), `catatProgress()`, `eksekusiStagingMigrasi()` (transaksi + batching + kasbon). |
| 2 | `app/Http/Controllers/Admin/ReportController.php` | `unggahPreviewMigrasi()`, `tampilPreviewMigrasi()`, `eksekusiPreviewMigrasi()`, `eksekusiAsyncMigrasi()`, `statusProgressMigrasi()`, `tambahUnitAjax()`, `unduhDummyMigrasi()`, `unduhTemplateMigrasi()`. |
| 3 | `app/Http/Requests/Admin/ImportMigrasiPayrollRequest.php` | Validasi unggahan: wajib file, maks 25 MB, ekstensi `.xlsx/.csv/.txt`, periode target, status pencairan. |
| 4 | `resources/views/components/payroll/excel-sync-modal.blade.php` | Modal impor: `target="_blank"`, `data-no-ajax`, `accept=".xlsx,.csv,.txt"`, tautan unduh. |
| 5 | `resources/views/admin/reports/preview-migrasi.blade.php` | Halaman pratinjau: KPI, panel anomali, resolusi supir/truk/rute, tabel rekapitulasi, modal unit AJAX, modal progress. |
| 6 | `resources/js/ui/table-manager.js` | Pengecualian AJAX untuk form `target="_blank"` / `data-no-ajax`. |
| 7 | `resources/js/modules/admin/reports.js` | Validasi berkas sisi klien: hanya `.xlsx`, `.csv`, `.txt`. |
| 8 | `routes/web.php` | Rute pratinjau, eksekusi, status progress, tambah unit, export, unduh template/contoh. |
| 9 | `tests/Feature/Admin/PreviewDanValidasiMigrasiExcelTest.php` | Test NIK/email, anomali (rute ganda & banyak unit), resolusi 3-arah, periode terkunci, eksekusi async, kasbon `sudah_cair`, validasi `.xls`, template kosong, unduh berkas. |
| 10 | `dokumen/CONTOH FORMAT Slip Gaji.xlsx` | Template kosong 3 sheet (placeholder). |
| 11 | `dokumen/DUMMY Slip Gaji.xlsx` | Contoh berisi data: 100 sheet, 2.227 ritase; dipakai tautan "Contoh Format Migrasi". |

> **Jalur lama:** `POST /admin/reports/migrasi/import` → `ReportController::imporMigrasi()` → `imporBerkasMigrasi()` masih terdaftar. Jalur ini memakai pola per-baris dan menulis `ritase.status = 'draft'` untuk `belum_cair`. Jalur tersebut **bukan** bagian dari alur pratinjau dan tidak boleh dipakai untuk impor baru.

---

## 7. STATUS SURAT JALAN, PENCAIRAN, & KASBON HASIL MIGRASI

### 7.1. Status Ritase
- Jalur staging selalu menulis `ritase.status = 'terverifikasi'`, termasuk untuk impor historis `belum_cair`.
- Alasan: `PayrollCalculationService::rekonsiliasiPeriode()` hanya menghitung ritase `terverifikasi`; jika ditulis `draft`, gaji supir hasil migrasi menjadi Rp 0.
- Alasan kedua: `PayrollController::submit()` mengembalikan HTTP 422 bila periode masih memiliki SJ `draft/terbit/sedang_dikirim/menunggu_verifikasi`, sehingga periode migrasi akan terkunci permanen.
- Status dana tetap dibedakan di `penggajian.status` dan `periode_gaji.status` (`draft` untuk belum cair, `paid` untuk sudah cair).

### 7.2. Audit Pencairan
Impor dengan `status_pencairan = sudah_cair` mengisi:
- `penggajian.status = paid` dan `penggajian.dicairkan_pada`;
- `periode_gaji.status = paid` dan `periode_gaji.dicairkan_pada`;
- `periode_gaji.tanggal_bayar` bila tanggal pencairan diisi.

### 7.3. Penyelesaian Kasbon
Untuk impor `sudah_cair`, sistem:
1. Memuat seluruh angsuran kasbon supir terkait satu kali.
2. Mencocokkan angsuran dengan periode bila `id_periode_gaji` sama **atau** `tanggal_rencana` berada di rentang periode (mengikuti kriteria `hitungPotonganKasbonPeriode()`).
3. Menandai angsuran `terjadwal` menjadi `terpotong`, mengisi `tanggal_potong`, dan mengisi `id_periode_gaji` bila masih kosong.
4. Mengurangi `kasbon.sisa_pinjaman` sebesar total angsuran yang dipotong dan mengubah status menjadi `lunas` saat sisa mencapai 0.

Dengan demikian tidak ada lagi kondisi "gaji sudah `paid` tetapi utang kasbon tidak pernah lunas" seperti pada versi sebelumnya.

---

## 8. VERIFIKASI SEBELUM MENYATAKAN SELESAI

Jalankan dari folder `app-payroll-asm`:

```cmd
php artisan view:clear
php artisan view:cache
php ..\Zzz\verifikasi-halaman.php
php ..\Zzz\cek-layanan-payroll.php
php artisan test
npm run build
```

Ambang kelulusan:
- Seluruh halaman yang diverifikasi mengembalikan **HTTP 200**.
- `cek-layanan-payroll` melaporkan **SEMUA COCOH**.
- `php artisan test` tidak menambah kegagalan baru.
- Data uji yang dibuat wajib dibersihkan dan diverifikasi bersih.
- **Dilarang** menjalankan `php artisan config:cache` di lokal.

---

## 9. RIWAYAT REVISI DOKUMEN

| Tanggal | Perubahan |
|:---|:---|
| 18-11-2026 | Memisahkan analisis jalur baca (pratinjau) dan jalur tulis (eksekusi); mengoreksi RTT menjadi 100–150 ms; mengoreksi matematika 384 → 544 query; menghapus target "< 0,5 detik"/"< 800 ms"; menambahkan batching insert, pengecekan ulang periode di dalam transaksi, status ritase `terverifikasi`, audit pencairan, dan penyelesaian kasbon; mengoreksi nomor baris template, kolom tabel pratinjau, serta tautan unduh contoh vs template kosong; menambahkan catatan jalur lama `imporBerkasMigrasi()`. |
