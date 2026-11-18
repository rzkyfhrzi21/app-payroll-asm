# Product Requirement Document & Software Requirement Document (PRD & SRD)

## COAL HAULING PAYROLL SYSTEM (SISTEM PENGGAJIAN SUPIR LOGISTIK BATUBARA)

- **Judul Proyek:** Sistem Informasi Penggajian Karyawan Supir Pengiriman Logistik Batubara
- **Versi Dokumen:** 2.0 (Full Specification)
- **Target Pengguna:** Admin/HRD, Pimpinan Operasional/Keuangan, dan Karyawan (Supir Truk Batubara)
- **Tech Stack:** Laravel Blade (Monolith), Tailwind CSS, Alpine.js, MySQL
- **Status:** Ready for Development & Stitch UI Generation

---

## BAGIAN 1: PRODUCT REQUIREMENTS DOCUMENT (PRD)

### 1.1 Latar Belakang & Problem Statement

Perusahaan pengiriman logistik batubara mengelola penggajian supir dengan variabel operasional harian yang tinggi—meliputi jumlah ritase, tonase timbangan muatan, perbedaan tarif per rute (seperti `MIP-PORT` atau `MIP-36`), pencairan uang jalan harian, hingga pembebanan biaya _service/sparepart_ unit truk. Kendala operasional utama yang diidentifikasi meliputi:

1. **Rawannya Kesalahan Kalkulasi Manual:** Perhitungan komisi supir (10% dari hasil bersih truk setelah dikurangi operasional, akumulasi uang jalan, dan biaya _sparepart_) secara manual di spreadsheet berisiko tinggi menimbulkan salah hitung.
2. **Ketidakjelasan Tracking Kasbon & Potongan Armada:** Pencatatan pinjaman pribadi supir (kasbon), potongan BPJS, serta pemotongan biaya _service/sparepart_ unit truk sering kali tidak terdokumentasi secara terpusat.
3. **Ketiadaan Alur Validasi (Approval) Berjenjang:** Proses pencairan gaji membutuhkan peninjauan (_review_) dan persetujuan (ACC/Tolak) dari Pimpinan sebelum dana ditransfer dan slip gaji diterbitkan kepada supir.

### 1.2 Visi & Prinsip Produk

Menghadirkan platform pengelolaan penggajian logistik batubara yang presisi, transparan, dan terotomasi dari pencatatan surat jalan/ritase harian hingga penerbitan slip gaji resmi.

- **Haulage-Driven Calculation:** Seluruh perhitungan gaji bersumber langsung dari akumulasi data pengantaran harian (ritase & tonase dikali tarif rute) dan beban riil unit armada.
- **Zero-Reconciliation Trip Money:** Uang jalan diberikan per keberangkatan (rit); sisa uang jalan di lapangan langsung menjadi hak pribadi supir tanpa perlu laporan nota kembalian, namun total uang jalan bulanan otomatis menjadi pengurang omzet kotor truk dalam perhitungan komisi 10%.
- **Strict Approval Governance:** Tidak ada slip gaji yang dapat dicetak atau dilihat oleh supir sebelum melewati verifikasi dan status **ACC Penggajian** dari Pimpinan.

### 1.3 Persona Pengguna

| Persona              | Kebutuhan Utama                                                                                                                                                                   | Fitur Terkait (Sesuai Use Case)                                                                                                                                                                                        |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Admin / HRD**      | Menginput data pengantaran harian (ritase/tonase), uang jalan, biaya _sparepart_, kasbon, serta mengeksekusi perhitungan gaji bulanan secara cepat dan akurat.                    | Kelola Data Karyawan, Kelola Jabatan, Kelola Gaji Pokok/Komisi, Kelola Tunjangan, Kelola Potongan/Kasbon, Kelola Uang Jalan, Kelola Absensi/Ritase, Perhitungan Gaji, Kelola Data Penggajian, Riwayat & Cetak Laporan. |
| **Pimpinan**         | Memvalidasi total kebutuhan kas penggajian (_Cash Requirement_), memeriksa rincian potongan _service/sparepart_ & uang jalan per truk, serta memberikan keputusan ACC atau Tolak. | Lihat Data Penggajian (`<<include>>` Review Perhitungan Gaji), ACC Penggajian (`<<extend>>` Tolak Penggajian), Lihat Laporan Penggajian.                                                                               |
| **Karyawan (Supir)** | Melihat transparansi jumlah ritase/tonase muatan batubara yang diangkut, rincian potongan (uang jalan, _sparepart_, BPJS, kasbon), dan mengunduh slip gaji resmi via HP.          | Lihat Data Pribadi ( & Info Armada Truk), Lihat Absensi/Ritase, Lihat Tunjangan dan Potongan, Lihat & Cetak Slip Gaji.                                                                                                 |

### 1.4 Ruang Lingkup MVP (Minimum Viable Product)

| Modul                                        | Prioritas  | Spesifikasi Fungsional                                                                                                                               |
| -------------------------------------------- | ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Autentikasi & Multi-Role Gate**            | P0 (Wajib) | Login sesi terpusat untuk 3 role (`admin`, `pimpinan`, `karyawan`) dengan proteksi middleware dan pengalihan dasbor otomatis.                        |
| **Master Karyawan, Jabatan & Armada**        | P0 (Wajib) | CRUD profil supir, jabatan (skema % komisi gaji pokok), master unit truk (Merk, No. Pol, Jenis), dan master tarif rute (`MIP-PORT`, `MIP-36`, dll). |
| **Operasional Ritase, Absensi & Uang Jalan** | P0 (Wajib) | Pencatatan pengantaran harian supir (Tanggal, No. Pol, Material Batubara, Rit, Tonase/M³, Tarif/Ton, Omzet Angkutan, dan Uang Jalan).                |
| **Biaya Armada, Tunjangan & Kasbon**         | P0 (Wajib) | Input potongan operasional angkutan, biaya _service & sparepart_ per truk, tunjangan supir, iuran BPJS, serta buku kasbon supir.                     |
| **Payroll Calculation Engine**               | P0 (Wajib) | Generator perhitungan gaji bulanan otomatis dengan rumus bagi hasil persentase komisi karyawan dari pendapatan bersih truk dikurangi BPJS dan cicilan kasbon. |
| **Workflow Approval Pimpinan**               | P0 (Wajib) | Fitur pengajuan gaji oleh Admin, antrean review Pimpinan, tombol **ACC Penggajian**, dan modal **Tolak Penggajian** beserta alasan revisi.           |
| **Generator Slip Gaji & Laporan PDF**        | P0 (Wajib) | Cetakan PDF "Rincian Gaji Supir" (tabel detail ritase harian + blok kalkulasi Pendapatan/UJ/S.Part/Komisi/BPJS/Kasbon) dan rekap laporan periode.  |

### 1.3 Batas Kewenangan Otorisasi (Berdasarkan `ACTIVITY_DIAGRAM.drawio`)

Alur pada diagram aktivitas menetapkan pemisahan tugas yang **wajib** dipatuhi:

| Tahap | Pelaku | Kewenangan | Status Periode |
|:--|:--|:--|:--|
| 1 | Admin/HRD | Menjalankan kalkulasi, memeriksa potongan & tunjangan, lalu **mengajukan** kebutuhan kas gaji | `draft` → `pending` |
| 2 | Pimpinan/Direksi | Memeriksa total kas, rincian komisi, potongan sparepart, dan cicilan kasbon pada `/executive/payroll` | `pending` |
| 3a | Pimpinan/Direksi | **Yes (ACC)** — menyetujui periode sehingga slip gaji boleh dicetak | `pending` → `approved` |
| 3b | Pimpinan/Direksi | **No (Tolak/Revisi)** — mengembalikan periode ke Admin dengan catatan | `pending` → `draft` |
| 4 | Admin/HRD | Mencetak slip gaji & laporan periode | `approved` / `paid` |

**Aturan mutlak:**
- **Admin/HRD DILARANG memberikan ACC.** Admin hanya boleh mengajukan (`pending`) dan menarik kembali pengajuan.
- Hanya peran **`pimpinan`** yang boleh mengubah status periode menjadi `approved`.
- Tombol ACC pada seluruh antarmuka Admin bersifat **nonaktif** dan hanya menampilkan status pengajuan.
- Slip gaji tidak boleh diterbitkan sebelum periode berstatus `approved`.
- Setiap keputusan otorisasi dicatat pada audit trail beserta identitas pimpinan.
- Siklus periode penggajian mengikuti **tutup buku manual**, bukan tanggal 1 s/d akhir bulan. Periode dibuka Admin lalu ditutup saat tanggal gajian tiba sehingga rentang boleh melintasi dua bulan (mis. 21 Okt 2026 – 18 Nov 2026). Aturan: hanya satu periode berjalan (`tanggal_selesai` NULL), periode baru mulai H+1 dari tutup buku sebelumnya, dan menutup buku otomatis membuka periode berikutnya (lihat `App\Services\PeriodeGajiLifecycleService`).
- Konsep **vendor/mitra armada sudah dihapus**; seluruh unit dianggap milik perusahaan sendiri.

---

## BAGIAN 2: SOFTWARE REQUIREMENTS DOCUMENT (SRD)

### 2.1 Spesifikasi Rumus Perhitungan Gaji (Core Business Logic)

Berdasarkan standar slip gaji perusahaan (_Rincian Gaji Supir_), perhitungan gaji bulanan mengikuti tahapan matematis berikut:

1. **Angkutan per Rit:** $\text{Tonase} \times \text{Tarif Rute per Ton}$ (Contoh: $28,48 \text{ Ton} \times \text{Rp } 158.760 = \text{Rp } 4.521.485$).
2. **Jumlah Angkutan Kotor:** $\sum \text{Angkutan seluruh rit dalam 1 periode cut-off}$ (Contoh: 21 Rit / 610 Ton = $\text{Rp } 92.877.797$).
3. **Pendapatan Angkutan Bersih (`Pend`):** $\text{Jumlah Angkutan Kotor} - \text{Potongan Operasional}$ (Contoh: $\text{Rp } 92.877.797 - \text{Rp } 799.216 = \text{Rp } 92.078.581$).
4. **Hasil Bersih Armada (Basis Komisi):** $\text{Pend} - \text{Jumlah Uang Jalan (`uj`)} - \text{Biaya Service \& Sparepart (`s.part`)}$ (Contoh: $\text{Rp } 92.078.581 - \text{Rp } 43.850.000 - \text{Rp } 6.462.000 = \text{Rp } 41.766.581$).
5. **Gaji Pokok Komisi Supir (`Gaji 10%` / Gross Pay):** $10\% \times \text{Hasil Bersih Armada}$ (Contoh: $10\% \times \text{Rp } 41.766.581 = \text{Rp } 4.176.658$).
6. **Gaji Bersih Diterima (`Net Pay`):** $(\text{Gaji 10\%} + \text{Total Tunjangan}) - (\text{Potongan BPJS} + \text{Potongan Kasbon})$ (Contoh: $\text{Rp } 4.176.658 - \text{Rp } 129.000 - \text{Rp } 500.000 = \text{Rp } 3.547.658$).

### 2.2 Skema Data Relasional Minimum (MySQL)

| Nama Entitas      | Atribut Kunci                                                                                                                                                                                                                                                                                                                            | Hubungan / Relasi                                                          |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| `users`           | `id` (PK), `name`, `email`, `password`, `role` (`admin`, `pimpinan`, `karyawan`)                                                                                                                                                                                                                                                         | 1:1 ke `employees`; 1:N ke `payroll_periods` (sebagai approver)            |
| `positions`       | `id` (PK), `code`, `name`, `commission_percentage` (default `10.00`), `base_salary`                                                                                                                                                                                                                                                      | 1:N ke `employees`                                                         |
| `trucks`          | `id` (PK), `plate_number` (_BE 8639 AAU_), `brand_model` (_Hino Putih_), `vendor_name` (_KAFA_)                                                                                                                                                                                                                                          | 1:N ke `employees`, `delivery_trips`, `payrolls`                           |
| `routes`          | `id` (PK), `code` (_MIP-PORT_, _MIP-36_), `material` (_BATU BARA_), `unit` (`ton`/`rit`/`m3`), `rate_per_unit`, `default_trip_money`                                                                                                                                                                                                     | 1:N ke `delivery_trips`                                                    |
| `employees`       | `id` (PK), `user_id` (FK), `position_id` (FK), `truck_id` (FK), `nik`, `bank_name`, `bank_account`, `bpjs_amount`, `status`                                                                                                                                                                                                              | N:1 ke `positions`, `trucks`; 1:N ke `delivery_trips`, `payrolls`, `loans` |
| `payroll_periods` | `id` (PK), `code` (_Jan-26_), `start_date`, `end_date`, `cutoff_date`, `pay_date`, `status` (`draft`, `pending`, `approved`, `rejected`, `paid`), `approved_by`, `rejection_note`                                                                                                                                                        | 1:N ke `delivery_trips`, `truck_expenses`, `payrolls`                      |
| `delivery_trips`  | `id` (PK), `payroll_period_id` (FK), `employee_id` (FK), `truck_id` (FK), `route_id` (FK), `trip_date`, `rit_count`, `tonnage`, `rate_per_ton`, `haulage_gross`, `trip_money`, `notes`                                                                                                                                                   | N:1 ke `payroll_periods`, `employees`, `trucks`, `routes`                  |
| `truck_expenses`  | `id` (PK), `payroll_period_id` (FK), `employee_id` (FK), `truck_id` (FK), `operational_cost`, `sparepart_service_cost`                                                                                                                                                                                                                   | N:1 ke `payroll_periods`, `employees`, `trucks`                            |
| `allowances`      | `id` (PK), `payroll_period_id` (FK), `employee_id` (FK), `title`, `amount`                                                                                                                                                                                                                                                               | N:1 ke `payroll_periods`, `employees`                                      |
| `loans` (Kasbon)  | `id` (PK), `employee_id` (FK), `loan_date`, `total_amount`, `installment_amount`, `remaining_balance`, `status`                                                                                                                                                                                                                          | N:1 ke `employees`                                                         |
| `payrolls`        | `id` (PK), `payroll_period_id` (FK), `employee_id` (FK), `truck_id` (FK), `total_rit`, `total_tonnage`, `gross_haulage`, `operational_cost`, `net_haulage`, `total_trip_money`, `sparepart_cost`, `net_truck_result`, `commission_rate`, `gross_pay`, `total_allowances`, `bpjs_deduction`, `kasbon_deduction`, `net_pay`, `has_warning` | N:1 ke `payroll_periods`, `employees`, `trucks`                            |

### 2.3 Desain Antarmuka Route Web (Laravel Monolith Contract)

| Metode     | URI Route                                               | Role            | Fungsi & Aksi Utama                                                                                  |
| ---------- | ------------------------------------------------------- | --------------- | ---------------------------------------------------------------------------------------------------- |
| `GET/POST` | `/login`, `/logout`                                     | Semua           | Autentikasi sesi pengguna dan penghancuran sesi.                                                     |
| `GET`      | `/dashboard`                                            | Semua           | Ringkasan metrik sesuai role (siklus aktif, kasbon, antrean ACC).                                    |
| `RESOURCE` | `/master/employees`, `/positions`, `/trucks`, `/routes` | Admin           | CRUD data karyawan/supir, jabatan, unit truk, dan tarif rute.                                        |
| `RESOURCE` | `/operational/trips`                                    | Admin           | Input & kelola pengantaran harian (absensi ritase, tonase, & uang jalan).                            |
| `RESOURCE` | `/operational/expenses`, `/allowances`, `/loans`        | Admin           | Kelola potongan operasional/sparepart truk, tunjangan, dan kasbon supir.                             |
| `GET`      | `/payroll`                                              | Admin, Pimpinan | Halaman utama Payroll Dashboard (gaya Rise UI: kartu Pay Cycle, Cash Requirement, & tabel karyawan). |
| `POST`     | `/payroll/{period}/calculate`                           | Admin           | Menjalankan kalkulasi ulang seluruh slip gaji pada periode berjalan.                                 |
| `PATCH`    | `/payroll/{period}/submit`                              | Admin           | Mengirimkan pengajuan penggajian ke Pimpinan (`status = pending`).                                   |
| `PATCH`    | `/payroll/{period}/approve`                             | Pimpinan        | Menyetujui penggajian (`status = approved`).                                                         |
| `PATCH`    | `/payroll/{period}/reject`                              | Pimpinan        | Menolak penggajian disertai catatan revisi (`status = rejected`).                                    |
| `GET`      | `/payroll/slip/{payroll}/pdf`                           | Semua           | Mengunduh/mencetak PDF "Rincian Gaji Supir" berformat tabel ritase lengkap.                          |
| `GET`      | `/my-payroll`, `/my-trips`, `/my-profile`               | Karyawan        | Portal mandiri supir untuk mengecek ritase, potongan, dan mengunduh slip gaji.                       |

### 2.4 Batasan Non-Fungsional & Keamanan

1. **Integritas Transaksi Database:** Proses kalkulasi gaji (`calculate`) dan persetujuan (`approve`/`reject`) wajib dibungkus dalam `DB::transaction()` agar pemotongan saldo kasbon konsisten.
2. **Presisi Keuangan & Tonase:** Seluruh nilai mata uang disimpan dalam tipe `BIGINT` (Rupiah bulat tanpa sen) atau `DECIMAL(15,2)`, sedangkan tonase menggunakan `DECIMAL(8,2)` untuk mencegah selisih pembulatan pada perkalian tarif.
3. **Otorisasi Ketat:** Middleware Role (`role:admin`, `role:pimpinan`, `role:karyawan`) dan Policy memastikan supir hanya dapat melihat slip gaji miliknya sendiri dan hanya untuk periode yang telah berstatus `approved` atau `paid`.
