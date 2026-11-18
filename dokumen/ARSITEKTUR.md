# Arsitektur Sistem & Spesifikasi Teknis: Coal Hauling Payroll System

**Versi Dokumen:** 1.2  
**Tanggal:** November 2026  
**Status:** Panduan Arsitektur Monolith Laravel 13 + Blade + MySQL  
**Lokasi File:** `docs/ARSITEKTUR.md`  
**Target Aplikasi:** Web Portal Admin/HRD, Panel Approval Pimpinan, & Employee Self-Service Supir  
**Revisi 1.2:** Struktur folder, route, controller, dan view diselaraskan dengan 18 layar Stitch v1 (login, Admin/HRD, Pimpinan, Portal Supir).

---

## 1. Ringkasan Arsitektur & Pemisahan Portal Akses

Sistem dibangun menggunakan arsitektur **Modular Monolith berbasis Laravel Blade** dengan satu basis data terpusat (**MySQL**). Pemisahan ruang kerja dilakukan melalui **Role-Based Middleware & Scoped Views**:

1. **Workspace Admin / HRD (`/admin/*`):**
   - Layar: Payroll Bulanan (`/payroll`), Data Master (`/master/employees`, `/master/trucks`, `/master/routes`, `/master/positions`), Operasional & Ritase (`/operational/trips`, `/operational/expenses`), Kasbon & Potongan (`/operational/loans`), Kalender Operasional (`/operational/calendar`), dan Laporan & Slip Gaji (`/reports`).
   - Fokus pada input data intensif (tabel pengantaran harian/ritase, uang jalan, biaya *service & sparepart* truk, buku kasbon) dan eksekusi *Payroll Engine*.
   - Antarmuka dioptimalkan untuk layar desktop lebar (*wide data-table*).
2. **Workspace Pimpinan (`/executive/*`):**
   - Layar: Executive Dashboard (`/executive/dashboard`), Data Penggajian & Review ACC (`/payroll/review`), Laporan Finansial (`/reports/financial`), Kalender Operasional *read-only* (`/executive/calendar`), dan Monitoring Ritase (`/executive/trips`).
   - Fokus pada pengawasan eksekutif: kartu *Cash Requirement* (total dana gaji yang harus disiapkan), audit rincian potongan per armada, serta aksi satu klik **ACC Penggajian** atau **Tolak Penggajian** (dengan catatan revisi).
3. **Workspace Karyawan / Supir (`/my/*`):**
   - Layar: Slip Gaji (`/my-payroll`), Riwayat Ritase & DO (`/my-payroll/trips`), Buku Kasbon (`/my-payroll/loans`), dan Profil & Armada (`/my-payroll/profile`).
   - Fokus pada keterbacaan di perangkat seluler (*responsive mobile view*): melihat histori tanggal keberangkatan, tonase muatan, uang jalan yang diterima, sisa kasbon, dan tombol unduh PDF **Rincian Gaji Supir**.
   - Satu view responsif dipakai untuk desktop dan mobile; akses per-record dibatasi `PayrollPolicy` dan `LoanPolicy` sehingga supir hanya melihat datanya sendiri.

Setelah login Breeze, `/dashboard` mengarahkan pengguna ke *landing page* sesuai role: Admin -> `/admin/payroll`, Pimpinan -> `/executive/dashboard`, Supir -> `/my/payroll`.

---

## 2. Tech Stack & Library Utama

| Komponen | Pilihan Teknologi / Package | Peran & Justifikasi |
|---|---|---|
| **Backend Framework** | Laravel 13.x (PHP 8.3 - 8.5) | Pondasi utama routing, autentikasi sesi, validasi, dan Eloquent ORM. Laravel 13 rilis 17 Maret 2026 dan tidak lagi mendukung PHP 8.2. |
| **Database** | MySQL 8.0+ | Penyimpanan relasional dengan dukungan transaksi ACID (`DB::transaction`) untuk kalkulasi gaji dan saldo kasbon. Presisi uang disimpan dalam `BIGINT` Rupiah bulat tanpa desimal, berat dalam `tonase_kg BIGINT`, dan komisi dalam `SMALLINT` basis poin (1000 bp = 10,00%). |
| **Frontend Templating** | Laravel Blade + Anonymous Components | Server-side rendering cepat, bersih, dan modular tanpa beban kompilasi SPA. |
| **Styling Framework** | Tailwind CSS v3.4.x (via Vite + PostCSS, bawaan Breeze) | Styling utilitas presisi untuk mereplikasi tata letak dashboard *Rise* (dark sidebar, gradient banner, clean table). Token memakai `tailwind.config.js`. Skeleton Laravel 13 membawa Tailwind 4, tetapi Breeze Blade stack mem-pin Tailwind 3.1+ dan menimpa `vite.config.js` + `app.css`, sehingga proyek konsisten di Tailwind 3.4. |
| **Micro-Interactivity** | Alpine.js v3.x | Mengelola state ringan di sisi klien: *tab switching* (`Current`/`Previous`), modal form input ritase, *live preview* kalkulasi nominal dinamis, live search debounce 200–300ms, dan kalkulator simulasi gaji supir. |
| **Icons & Typography** | Google Fonts Material Symbols Outlined + Inter & Plus Jakarta Sans | Variable icon font Google Material Symbols Outlined (opsz 20..48, wght 100..700) untuk seluruh antarmuka + font monospace JetBrains Mono untuk presisi visual angka tabel finansial. |
| **WhatsApp Gateway** | Fonnte API Gateway (`https://api.fonnte.com/send`) | Layanan gateway pesan WhatsApp via `App\Services\PengirimWhatsAppService` untuk mengirimkan notifikasi instan pencairan gaji supir, ringkasan gaji bersih, potongan kasbon, dan tautan slip gaji. Bekerja secara *fault-tolerant* (kegagalan gateway tidak menggagalkan pencairan dana). |
| **Email SMTP Delivery** | Gmail SMTP (`smtp.gmail.com`) via Symfony Mailer | Pengiriman email notifikasi slip gaji supir (`SlipGajiDicairkanMail`) dan laporan eksekutif via protokol SMTP resmi Google (Port 587 STARTTLS / Port 465 SSL) menggunakan 16 digit Google App Password (Sandi Aplikasi). |
| **Pengolahan Media & WebP** | PHP GD / `StandarMediaGambarService` | Pemrosesan, kompresi, dan konversi multi-foto bukti fisik surat jalan lapangan bertanda tangan penerima, foto profil supir, serta logo perusahaan menjadi format `.webp` efisien (maks 2 MB per foto) sebelum disimpan ke storage lokal. |
| **Data Visualization** | Native SVG & CSS Conic-Gradient Components | Diagram visual donat komposisi unit armada dan statistik biaya operasional dibuat murni dengan **SVG Native dan CSS gradient** (`<x-ui.donut-chart />`), **tanpa dependensi library JavaScript/React chart** (seperti Chart.js atau Recharts), menjaga aplikasi tetap ringan dan sesuai prinsip monolitik Laravel SSR. |
| **PDF Generator & Print** | Native Media Print CSS (`@media print`) & Dompdf | Format cetak fisik slip gaji, buku mutasi kasbon, dan laporan rekapitulasi penggajian dengan layout kop surat dan tanda tangan digital profil perusahaan (`data_perusahaan`). |
| **Keamanan & Rate Limiting** | `StandarKeamananMiddleware` & Throttle Limiter | Header keamanan OWASP (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, `X-Robots-Tag: noindex, nofollow, noarchive`), audit logging mutasi ke `storage/logs/security-audit.log`, rate limiting login/lupa sandi (5 percobaan per 15 menit), serta rate limiting interaksi admin (60 req/menit). |
| **Debug Tooling (Dev)** | `barryvdh/laravel-debugbar` v4.4+ | Inspeksi query, route, view, dan timeline saat development (membantu mendeteksi N+1 pada tabel payroll). Install sebagai dev-dependency dan wajib `DEBUGBAR_ENABLED=false` di production. |
| **Autentikasi** | Laravel Breeze 2.4+ (Blade Stack) | Scaffolding login/logout, profil, dan update password standar; dikustomisasi untuk 3 role via `RoleMiddleware`. Paket 2.4.2 mengizinkan Laravel 13 (`illuminate ^13.0`), meski README resminya masih menyebut untuk Laravel 11 ke bawah; stubs-nya memakai Tailwind 3. |
### 2.1. Prasyarat Runtime & Deployment

- **PHP 8.3 - 8.5** (wajib; Composer akan menolak PHP 8.2). Ekstensi: `pdo_mysql`, `mbstring`, `dom`, `fileinfo`, `openssl`, `tokenizer`, `xml`, `curl`.
- **Composer 2.2+** dan **Node.js 20.19+ / 22.12+** (kebutuhan Vite 8).
- **MySQL 8.0+** dengan transaksi InnoDB.
- Document root server **wajib** diarahkan ke `public/`, bukan root proyek. File `.htaccess` lama di root bukan bagian dari Laravel dan harus diarsipkan saat deploy.
- Shared hosting: pastikan PHP selector tersedia 8.3+. Jika hosting masih 8.2, Laravel 12 adalah fallback terakhir (security support sampai Februari 2027).
- **Breeze Blade, bukan starter kit SPA:** React, Vue, Livewire, dan Inertia **tidak dipakai**. Autentikasi memakai `laravel/breeze` Blade stack (`composer require laravel/breeze --dev`, lalu `php artisan breeze:install blade`). Karena Breeze mem-pin Tailwind 3, proyek memakai **Tailwind 3.4**, bukan Tailwind 4 bawaan skeleton; `@tailwindcss/vite` yang tidak terpakai boleh dihapus dari `package.json`.
- **Debugbar hanya untuk lokal:** install `barryvdh/laravel-debugbar` sebagai dev-dependency. Wajib set `DEBUGBAR_ENABLED=false` (atau hapus package saat deploy) di production agar tidak membocorkan query dan data payroll.

### 2.2. Matriks Kompatibilitas Package (Laravel 13)

| Package | Versi Minimum | Status |
|---|---|---|
| `laravel/framework` | 13.x | ✅ PHP ^8.3 |
| `barryvdh/laravel-dompdf` | 3.1+ | ✅ `illuminate/support ^13.0` |
| `maatwebsite/excel` | 4.0+ | ✅ PHP ^8.3, `illuminate ^12\|^13` (API v4 berbeda dari tutorial v3) |
| `blade-ui-kit/blade-icons` | 1.11+ | ✅ `illuminate ^13.0` |
| `mallardduck/blade-lucide-icons` | 2.0+ | ✅ via `blade-icons ^1.8` |
| `laravel/breeze` | 2.4+ | ✅ `illuminate ^13.0`; stubs memakai Tailwind 3 |
| `barryvdh/laravel-debugbar` | 4.4+ | ✅ `illuminate ^11\|^12\|^13` (dev-only) |


### 2.3. Konfigurasi Layanan Notifikasi (Gmail SMTP & Fonnte WhatsApp)

Sistem menerapkan arsitektur notifikasi multi-saluran (*dual-channel event-driven notification*) yang terpicu saat Pimpinan mengeksekusi pencairan dana gaji (`PATCH /executive/payroll/{period}/disburse`) melalui `App\Services\NotifikasiPencairanGajiService`:

```text
Pencairan Dana Disahkan Pimpinan (Status: approved -> paid)
  │
  ├──► 1. Pemotongan Saldo Kasbon Otomatis (angsuran_kasbon.status = terpotong)
  │
  └──► 2. NotifikasiPencairanGajiService::kirimUntukPenggajian()
         ├──► [Saluran Email]: SlipGajiDicairkanMail -> Gmail SMTP (smtp.gmail.com)
         │      └─ Rincian: Periode, Gaji Bersih, Potongan Kasbon/BPJS, Tautan Portal
         │
         └──► [Saluran WhatsApp]: PengirimWhatsAppService -> Fonnte API Gateway
                └─ Pesan WA: Rekap penerimaan, info transfer rekening bank, ucapan terima kasih
```

#### 2.3.1. Spesifikasi Email SMTP Gmail
- **Host SMTP:** `smtp.gmail.com`
- **Port & Enkripsi:**
  - **Rekomendasi (Port 587):** Menggunakan protokol STARTTLS. Konfigurasi `MAIL_PORT=587` dengan `MAIL_SCHEME=null` (atau kosong). Symfony Mailer secara otomatis meng-upgrade koneksi ke TLS terenkripsi.
  - **Alternatif (Port 465):** Menggunakan enkripsi SSL/TLS langsung. Konfigurasi `MAIL_PORT=465` dengan `MAIL_SCHEME=smtps`.
- **Autentikasi:** Wajib menggunakan **Google App Password (Sandi Aplikasi)** 16 digit, **BUKAN** kata sandi login akun Google biasa.
  1. Buka Akun Google di [https://myaccount.google.com/security](https://myaccount.google.com/security).
  2. Aktifkan **Verifikasi 2 Langkah (2-Step Verification)** jika belum aktif.
  3. Masuk ke menu **Sandi Aplikasi (App Passwords)**.
  4. Buat sandi aplikasi baru dengan nama aplikasi (contoh: `Payroll ASM`).
  5. Salin 16 karakter sandi yang dihasilkan (tanpa spasi) ke variabel `MAIL_PASSWORD` di `.env`.
- **Uji Koneksi CLI:** Jalankan perintah `php artisan kirim:uji-email {alamat_email_tujuan}` untuk memvalidasi koneksi langsung dari terminal.

#### 2.3.2. Spesifikasi WhatsApp Gateway Fonnte
- **Endpoint API:** `POST https://api.fonnte.com/send`
- **Header Autentikasi:** `Authorization: {FONNTE_TOKEN}`
- **Format Nomor Tujuan:** Dinormalisasi secara otomatis oleh `FormatNomorTelepon` ke format kode negara internasional Indonesia (`628xxxxxxxxxx` tanpa spasi, tanda strip, atau awalan `+`).
- **Uji Koneksi CLI:** Jalankan perintah `php artisan kirim:uji-wa {nomor_whatsapp}` untuk memverifikasi kuota dan konektivitas perangkat WhatsApp di Fonnte.

#### 2.3.3. Prinsip Ketahanan & Fault Tolerance
Kegagalan jaringan internet, kesalahan konfigurasi SMTP, atau kehabisan kuota Fonnte **DILARANG KERAS** membatalkan atau me-rollback transaksi pencairan dana gaji yang telah berhasil di database (`DB::transaction`). Seluruh kegagalan pengiriman notifikasi ditangkap secara aman (*graceful error handling*) dan dicatat ke `storage/logs/laravel.log` untuk diaudit tanpa mengganggu jalannya aplikasi.
---

## 3. Struktur Direktori Proyek (Laravel Custom Convention)

```text
app-payroll-asm/
├── app/
│   ├── Enums/
│   │   ├── UserRole.php                # admin, pimpinan, karyawan
│   │   ├── PayrollStatus.php           # draft, calculated, pending, approved, rejected, paid
│   │   ├── TripStatus.php              # draft, validated, locked
│   │   └── LoanStatus.php              # pending, approved, rejected, paid
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                   # Breeze: login, logout, reset & update password
│   │   │   ├── DashboardController.php # /dashboard → redirect sesuai role
│   │   │   ├── PayrollSlipController.php # Slip HTML + PDF (Admin, Pimpinan, Supir via Policy)
│   │   │   ├── Admin/
│   │   │   │   ├── PayrollController.php        # Payroll Bulanan, hitung, submit, import/export
│   │   │   │   ├── ReportController.php         # Laporan & arsip slip per periode
│   │   │   │   ├── Master/
│   │   │   │   │   ├── EmployeeController.php   # Data supir/karyawan & rekening payroll
│   │   │   │   │   ├── TruckController.php      # Unit armada & status KIR
│   │   │   │   │   ├── RouteController.php      # Tarif rute hauling
│   │   │   │   │   └── PositionController.php   # Skema jabatan & komisi
│   │   │   │   └── Operational/
│   │   │   │       ├── DeliveryTripController.php        # Ritase harian + export
│   │   │   │       ├── TruckExpenseController.php        # Biaya operasional & sparepart
│   │   │   │       ├── EmployeeFinanceController.php     # Kasbon, BPJS, tunjangan, memo debet
│   │   │   │       └── OperationalCalendarController.php # Kalender & kunci data ritase
│   │   │   ├── Executive/
│   │   │   │   ├── DashboardController.php       # Executive dashboard & analitik
│   │   │   │   ├── PayrollReviewController.php   # Review, ACC, tolak, batch approve
│   │   │   │   ├── FinancialReportController.php # Rekap finansial, PDF/Excel, email
│   │   │   │   ├── CalendarController.php        # Kalender operasional (read-only)
│   │   │   │   └── TripMonitorController.php     # Monitoring ritase (read-only)
│   │   │   └── Driver/
│   │   │       ├── DashboardController.php       # Slip gaji + ringkasan ritase
│   │   │       ├── TripHistoryController.php     # Riwayat ritase & surat jalan
│   │   │       ├── LoanController.php            # Buku kasbon & pengajuan
│   │   │       └── ProfileController.php         # Profil, armada, rekening
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php      # role:admin | pimpinan | karyawan
│   │   └── Requests/
│   │       ├── Auth/                   # Breeze: LoginRequest
│   │       ├── Admin/
│   │       │   ├── Master/             # Store/Update Employee, Truck, Route, Position
│   │       │   ├── Operational/        # Store/Update Trip, Expense, Loan, Deduction, CalendarEvent
│   │       │   └── Payroll/            # CalculatePayrollRequest, ImportPayrollRequest
│   │       ├── Executive/
│   │       │   ├── ApprovePayrollRequest.php
│   │       │   └── RejectPayrollRequest.php
│   │       └── Driver/
│   │           ├── StoreLoanRequest.php
│   │           └── UpdateProfileRequest.php
│   ├── Models/                         # User, Employee, Position, Truck, Route, DeliveryTrip,
│   │                                   # TruckExpense, Loan, BpjsDeduction, Allowance, DebitMemo,
│   │                                   # PayrollPeriod, Payroll, PayrollApproval, OperationalSchedule
│   ├── Policies/
│   │   ├── PayrollPolicy.php           # Admin/Pimpinan review; Karyawan hanya slip miliknya yang approved/paid
│   │   ├── DeliveryTripPolicy.php
│   │   └── LoanPolicy.php
│   ├── Services/
│   │   ├── PayrollCalculationService.php # Ritase → omzet → UJ & sparepart → komisi 10% → net pay
│   │   ├── PayrollApprovalService.php    # Transisi status, batch ACC, audit trail
│   │   ├── PayrollImportService.php      # Validasi & simpan import Excel multi-sheet
│   │   ├── PayrollExportService.php      # Export Excel multi-sheet & PDF laporan
│   │   └── ExecutiveDashboardService.php # Agregasi tren volume, komposisi biaya, antrean ACC
│   └── Support/
│       └── FormatHelper.php            # Format Rupiah, tonase, dan periode
├── bootstrap/
│   └── app.php                         # Alias RoleMiddleware + konfigurasi middleware
├── database/
│   ├── migrations/                     # users, employees, positions, trucks, routes, delivery_trips,
│   │                                   # truck_expenses, loans, bpjs_deductions, allowances, debit_memos,
│   │                                   # payroll_periods, payrolls, payroll_approvals, operational_schedules
│   └── seeders/
│       └── CoalLogisticsDemoSeeder.php # Data demo: 35 supir, 42 truk, 4 rute, periode Jan-26
├── docs/
│   ├── PRD.md
│   ├── ARSITEKTUR.md
│   ├── DESIGN.md
│   └── ...                             # Brief modul, referensi gambar, dokumen pendukung
├── resources/
│   ├── css/
│   │   └── app.css                     # @tailwind base/components/utilities (Tailwind 3.4)
│   ├── js/
│   │   └── app.js                      # Alpine.js sudah disertakan Breeze
│   └── views/
│       ├── auth/                       # Breeze: login & update password
│       ├── layouts/
│       │   ├── app.blade.php           # Shell Admin HRD (sidebar gelap)
│       │   ├── executive.blade.php     # Shell Pimpinan
│       │   ├── driver.blade.php        # Shell Supir (desktop + mobile responsif)
│       │   └── guest.blade.php         # Layout login Breeze
│       ├── components/
│       │   ├── layout/
│       │   │   ├── admin-sidebar.blade.php
│       │   │   ├── executive-sidebar.blade.php
│       │   │   ├── driver-nav.blade.php
│       │   │   └── topbar.blade.php
│       │   ├── ui/
│       │   │   ├── stat-card.blade.php
│       │   │   ├── status-badge.blade.php
│       │   │   ├── period-selector.blade.php
│       │   │   ├── page-header.blade.php
│       │   │   ├── modal.blade.php
│       │   │   └── empty-state.blade.php
│       │   ├── payroll/
│       │   │   ├── summary-banner.blade.php
│       │   │   ├── payroll-table.blade.php
│       │   │   ├── data-transfer-modal.blade.php # Import/export Excel multi-sheet
│       │   │   └── approval-modal.blade.php      # ACC / tolak + alasan revisi
│       │   ├── operational/
│       │   │   ├── trip-form-modal.blade.php
│       │   │   ├── trip-table.blade.php
│       │   │   ├── employee-finance-tabs.blade.php
│       │   │   └── calendar-grid.blade.php
│       │   ├── master/
│       │   │   ├── employee-table.blade.php
│       │   │   ├── truck-table.blade.php
│       │   │   └── route-table.blade.php
│       │   └── slip/
│       │       ├── slip-summary.blade.php
│       │       └── slip-detail.blade.php
│       ├── admin/
│       │   ├── payroll/index.blade.php
│       │   ├── reports/index.blade.php
│       │   ├── master/{employees,trucks,routes,positions}/index.blade.php
│       │   └── operational/{trips,expenses,loans,calendar}/index.blade.php
│       ├── executive/
│       │   ├── dashboard.blade.php
│       │   ├── payroll-review/{index,show}.blade.php
│       │   ├── reports/index.blade.php
│       │   ├── calendar/index.blade.php
│       │   └── trips/index.blade.php
│       ├── driver/
│       │   ├── dashboard.blade.php
│       │   ├── trips/index.blade.php
│       │   ├── loans/index.blade.php
│       │   └── profile/index.blade.php
│       ├── payroll/
│       │   └── slip.blade.php          # Halaman rincian slip (shared, responsif)
│       ├── pdf/
│       │   ├── payroll-slip.blade.php   # Dompdf: slip resmi
│       │   ├── financial-report.blade.php
│       │   ├── loan-book.blade.php      # Dompdf: buku kasbon supir
│       │   └── driver-id-card.blade.php # Dompdf: ID card supir
│       └── profile/                    # Breeze
└── routes/
    ├── web.php                         # 3 grup role + route slip shared
    └── auth.php                        # Breeze: login, logout, update password
```

### 3.1. Peta Layar Stitch v1 → Route, Controller, View

Pemetaan ini menjadi acuan implementasi: satu layar Stitch boleh menghasilkan beberapa route (index/store/export), tetapi tidak boleh menggandakan controller hanya karena ada varian desktop dan mobile.

#### 3.1.1. Admin / HRD

| Layar Stitch v1 | Route (name → URI) | Controller@method | View |
|---|---|---|---|
| `kalender_operasional_ritase_siklus_payroll_coaltrans_payroll` | `admin.operational.calendar.index` → `GET /admin/operational/calendar` | `Admin\Operational\OperationalCalendarController@index` | `admin/operational/calendar/index` |
| `kelola_operasional_ritase_harian` | `admin.operational.trips.index` → `GET /admin/operational/trips` | `Admin\Operational\DeliveryTripController@index` | `admin/operational/trips/index` |
| `kelola_data_master_supir_armada_tarif_rute` | `admin.master.employees.index` → `GET /admin/master/employees`; `admin.master.trucks.index` → `GET /admin/master/trucks`; `admin.master.routes.index` → `GET /admin/master/routes`; `admin.master.positions.index` → `GET /admin/master/positions` | `Admin\Master\{Employee,Truck,Route,Position}Controller@index` | `admin/master/{employees,trucks,routes,positions}/index` |
| `kelola_kasbon_potongan_karyawan_coaltrans_payroll` | `admin.operational.loans.index` → `GET /admin/operational/loans` | `Admin\Operational\EmployeeFinanceController@index` | `admin/operational/loans/index` |
| `payroll_bulanan_review_approval` | `admin.payroll.index` → `GET /admin/payroll`; `admin.payroll.calculate` → `POST /admin/payroll/{period}/calculate`; `admin.payroll.submit` → `PATCH /admin/payroll/{period}/submit` | `Admin\PayrollController@index/calculate/submit` | `admin/payroll/index` |
| `modal_import_export_excel_multi_sheet` | `admin.payroll.import` → `POST /admin/payroll/{period}/import`; `admin.payroll.export` → `GET /admin/payroll/{period}/export` | `Admin\PayrollController@import/export` | `components/payroll/data-transfer-modal` |
| `rincian_slip_gaji_supir_kalkulasi_10` | `payroll.slip.show` → `GET /admin/payroll/slip/{payroll}`; `payroll.slip.pdf` → `GET /admin/payroll/slip/{payroll}/pdf` | `PayrollSlipController@show/pdf` | `payroll/slip` + `pdf/payroll-slip` |
| Laporan & Slip Gaji (menu sidebar, belum ada layar Stitch) | `admin.reports.index` → `GET /admin/reports` | `Admin\ReportController@index` | `admin/reports/index` |

Catatan: `modal_import_export_excel_multi_sheet` bukan halaman mandiri. Modal dipicu dari `admin/payroll/index` dan tidak memerlukan route `GET` sendiri. Aksi tulis pada layar kasbon (modal "Ajukan Kasbon", BPJS, tunjangan, memo debet) ditangani `EmployeeFinanceController@storeLoan/storeBpjs/storeAllowance/storeDebitMemo`; ritase memakai `store/update/destroy/export`, biaya memakai `store/update`, dan kalender memakai `store/validate/lock`.

#### 3.1.2. Pimpinan

| Layar Stitch v1 | Route (name → URI) | Controller@method | View |
|---|---|---|---|
| `executive_dashboard_laporan_analitik_pimpinan` | `executive.dashboard` → `GET /executive/dashboard` | `Executive\DashboardController@index` | `executive/dashboard` |
| `data_penggajian_pimpinan_review_perhitungan_gaji` | `executive.payroll.index` → `GET /executive/payroll`; `executive.payroll.show` → `GET /executive/payroll/{period}`; `executive.payroll.approve` → `PATCH /executive/payroll/{period}/approve`; `executive.payroll.reject` → `PATCH /executive/payroll/{period}/reject`; `executive.payroll.approve-batch` → `POST /executive/payroll/{period}/approve-batch` | `Executive\PayrollReviewController@index/show/approve/reject/approveBatch` | `executive/payroll/index` |
| `modal_otorisasi_tolak_revisi_pimpinan` | `executive.payroll.approve` → `PATCH /admin/payroll/{period}/approve`; `executive.payroll.reject` → `PATCH /admin/payroll/{period}/reject`; `executive.payroll.approve-batch` → `POST /admin/payroll/review/{period}/approve-batch` | `Executive\PayrollReviewController@approve/reject/approveBatch` | `components/payroll/approval-modal` |
| `laporan_penggajian_rekapitulasi_finansial_pimpinan` | `executive.reports.index` → `GET /executive/reports`; `executive.reports.pdf` → `GET /executive/reports/pdf`; `executive.reports.export` → `GET /executive/reports/export`; `executive.reports.email` → `POST /executive/reports/email` | `Executive\FinancialReportController@index/pdf/export/email` | `executive/reports/index` |
| Kalender operasional (read-only, turunan layar admin) | `executive.calendar` → `GET /executive/calendar` | `Executive\CalendarController@index` | `executive/calendar/index` |
| Monitoring ritase (turunan layar admin) | `executive.trips` → `GET /executive/trips` | `Executive\TripMonitorController@index` | `executive/trips/index` |

Catatan: menu **Kalender Operasional** dan **Monitoring Ritase** pada sidebar Pimpinan belum punya layar Stitch khusus. Implementasi memakai ulang komponen `calendar-grid` dan `trip-table` dalam mode *read-only*.

#### 3.1.3. Supir / Karyawan

| Layar Stitch v1 | Route (name → URI) | Controller@method | View |
|---|---|---|---|
| `portal_supir_slip_gaji_ritase_resmi_desktop` | `driver.dashboard` → `GET /my/payroll` | `Driver\DashboardController@index` | `driver/dashboard` |
| `portal_supir_slip_gaji_ritase_resmi_acc` (mobile) | sama dengan desktop | sama | sama (responsif) |
| `portal_supir_buku_kasbon_potongan_desktop` | `driver.loans.index` → `GET /my/loans`; `driver.loans.store` → `POST /my/loans` | `Driver\LoanController@index/store` | `driver/loans/index` |
| `portal_supir_kasbon_potongan_karyawan` (mobile) | sama dengan desktop | sama | sama (responsif) |
| `portal_supir_profil_info_armada_desktop` | `driver.profile.edit` → `GET /my/profile`; `driver.profile.update` → `PATCH /my/profile` | `Driver\ProfileController@edit/update` | `driver/profile/index` |
| `portal_supir_data_pribadi_info_armada` (mobile) | sama dengan desktop | sama | sama (responsif) |
| Riwayat ritase & DO (tab pada portal supir) | `driver.trips.index` → `GET /my/trips` | `Driver\TripHistoryController@index` | `driver/trips/index` |

Catatan: portal supir memakai satu prefix `/my-payroll/*`. Route lama di PRD (`/my-trips`, `/my-profile`) dipertahankan sebagai redirect ke route baru agar tautan lama tidak pecah.

#### 3.1.4. Guest / Shared

| Layar Stitch v1 | Route (name → URI) | Controller@method | View |
|---|---|---|---|
| `login_multi_role_coaltrans_payroll` | `login` → `GET /login`; `POST /login` | Breeze `Auth\AuthenticatedSessionController@create/store` | `auth/login` |
| Logout semua role | `logout` → `POST /logout` | Breeze `Auth\AuthenticatedSessionController@destroy` | — |

### 3.2. Catatan Hasil Analisis Stitch v1

1. **18 layar** terdiri dari 1 login, 7 layar Admin/HRD (termasuk 1 modal dan 1 slip), 4 layar Pimpinan (termasuk 1 modal), dan 6 layar Supir (3 desktop + 3 mobile). Tiga layar mobile supir adalah varian responsif, bukan halaman baru.
2. Stitch memakai **dua varian sidebar Admin** (varian kalender/kasbon dan varian payroll/operasional). Implementasi menyatukannya menjadi satu sidebar kanonik: Dashboard & Payroll, Operasional Ritase, Kasbon & Potongan, Data Master, Kalender Operasional, Laporan & Slip Gaji.
3. `modal_import_export_excel_multi_sheet` dan `modal_otorisasi_tolak_revisi_pimpinan` adalah **komponen modal**, bukan route halaman. Keduanya dipanggil dari halaman payroll dan review.
4. Login memakai 3 tab role di sisi UI (`owner`/`admin`/`driver`), tetapi backend tetap satu `POST /login` Breeze. Tab hanya label; nilainya dipetakan ke `pimpinan`/`admin`/`karyawan` dan ditentukan dari kolom `users.role`, bukan dari session yang dikirim browser.
5. Beberapa menu sidebar belum memiliki layar Stitch: Laporan & Slip Gaji (Admin), Kalender Operasional dan Monitoring Ritase (Pimpinan), serta Riwayat Ritase & DO (Supir). Untuk tahap pertama, halaman turunan ini memakai komponen tabel/kalender yang sama dan ditandai sebagai backlog desain.
6. Pembatasan data tidak cukup dengan `RoleMiddleware`. Slip, ritase, dan kasbon milik supir wajib dicek lagi melalui `PayrollPolicy`, `DeliveryTripPolicy`, dan `LoanPolicy` agar tidak bisa diakses lewat ID milik supir lain; slip baru terlihat setelah status `approved` atau `paid`.
7. Semua data master dinormalkan ke prefix `/master/*` (`/master/positions`, bukan `/positions`) agar konsisten dengan `/master/employees`, `/master/trucks`, dan `/master/routes`. PRD dapat diperbarui saat implementasi.
8. `/payroll` khusus Admin/HRD, sedangkan Pimpinan memakai `/payroll/review`. Pemisahan ini mencegah tabel input dan aksi ACC bercampur dalam satu halaman.
9. Tidak ada layar dashboard Admin terpisah di Stitch v1. Halaman **Payroll Bulanan** berfungsi ganda sebagai landing page dan dashboard operasional Admin.

### 3.3. Kerangka Route Group

```php
// routes/web.php
Route::redirect('/', '/login');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/payroll', [Admin\PayrollController::class, 'index'])->name('admin.payroll.index');
    Route::post('/payroll/{period}/calculate', [Admin\PayrollController::class, 'calculate'])->name('admin.payroll.calculate');
    // master/*, operational/*, reports
});

Route::middleware(['auth', 'role:pimpinan'])->group(function () {
    Route::get('/executive/dashboard', [Executive\DashboardController::class, 'index'])->name('executive.dashboard');
    Route::get('/payroll/review', [Executive\PayrollReviewController::class, 'index'])->name('executive.payroll.index');
    Route::patch('/payroll/{period}/approve', [Executive\PayrollReviewController::class, 'approve'])->name('executive.payroll.approve');
    // reports/financial, executive/calendar, executive/trips
});

Route::middleware(['auth', 'role:karyawan'])->group(function () {
    Route::get('/my-payroll', [Driver\DashboardController::class, 'index'])->name('driver.dashboard');
    // my-payroll/trips, my-payroll/loans, my-payroll/profile
});

// Shared lintas role; hak per-record dicek Policy
Route::middleware('auth')->group(function () {
    Route::get('/payroll/slip/{payroll}', [PayrollSlipController::class, 'show'])->name('payroll.slip.show');
    Route::get('/payroll/slip/{payroll}/pdf', [PayrollSlipController::class, 'pdf'])->name('payroll.slip.pdf');
});
```

Alias `role` didaftarkan di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class]);
})
```

### 3.4. Backlog Layar yang Sudah Ada di Navigasi tetapi Belum Ada Desain

Tabel ini hanya memuat menu/tab yang **belum punya layar utuh**. Menu yang sudah terwakili halaman lain (mis. `Dashboard & Payroll`, `Payroll & Approval`, `Absensi & Ritase`) tidak dimasukkan.

| Portal | Menu / Tab | Kondisi desain saat ini | Rekomendasi |
|---|---|---|---|
| Admin | Dashboard (sidebar varian A) | Tidak ada layar khusus; belum jelas bedanya dengan Payroll Bulanan | Konfirmasi; sementara arahkan ke `/payroll` |
| Admin | Laporan & Slip Gaji | Belum ada halaman daftar laporan; baru ada rincian slip | Buat halaman arsip periode + export PDF/Excel |
| Admin | Operasional → Biaya Operasional & Sparepart | Tab ada, tetapi konten/tabel belum didesain | Buat halaman atau modal input biaya armada |
| Admin | Data Master → Unit Armada Truk | Baru kartu ringkasan & status KIR; belum ada tabel CRUD truk | Buat tabel armada + form tambah/edit |
| Admin | Data Master → Tarif Rute & Jalur Hauling | Baru kartu 3 rute; belum ada tabel CRUD tarif | Buat tabel tarif + form rute |
| Admin | Data Master → Skema Jabatan & Komisi | Belum ada konten | Buat tabel skema komisi/jabatan |
| Admin | Kasbon → Iuran BPJS Ketenagakerjaan | Baru kartu ringkasan; belum ada tabel/CRUD | Buat tabel iuran per supir |
| Admin | Kasbon → Tunjangan Operasional Khusus | Baru kartu ringkasan; belum ada tabel/CRUD | Buat tabel tunjangan |
| Admin | Kasbon → Memo Debet Biaya Armada | Hanya label tab; belum ada konten | Buat tabel memo debet |
| Pimpinan | Kalender Operasional | Tidak ada layar pimpinan; hanya versi admin | Reuse kalender admin mode *read-only* |
| Pimpinan | Monitoring Ritase | Tidak ada layar pimpinan; hanya versi admin | Reuse tabel ritase admin mode *read-only* |
| Supir | Riwayat Ritase & DO (sidebar desktop & bottom nav mobile) | Belum ada halaman mandiri; hanya tabel manifest di dashboard | Buat halaman riwayat + filter periode/DO |

Catatan tambahan: link **"Lupa Kata Sandi?"** di halaman login juga belum punya desain Stitch. Breeze sudah menyediakan halaman reset password default yang bisa disesuaikan dengan layout `guest`.

Prioritas desain:

1. **Prioritas 1 (memblokir alur):** Laporan & Slip Gaji Admin, Biaya Operasional & Sparepart, Riwayat Ritase & DO Supir, Kalender & Monitoring Ritase Pimpinan.
2. **Prioritas 2 (melengkapi master):** Unit Armada Truk, Tarif Rute, Skema Jabatan & Komisi, BPJS, Tunjangan, Memo Debet.
3. Untuk sementara, halaman turunan memakai komponen yang sudah ada (`trip-table`, `calendar-grid`, `employee-table`, `payroll-table`) dan **belum dibuat route baru** sampai desainnya disetujui.

---

## 4. Alur Eksekusi `PayrollCalculationService`

Saat Admin menekan tombol **"Hitung / Kalkulasi Gaji"** pada periode tertentu:
1. Sistem memulai `DB::beginTransaction()`.
2. Mengambil seluruh supir berstatus `active`.
3. Untuk setiap supir pada rentang tanggal periode (`start_date` s/d `end_date`):
   - Menjumlahkan `rit_count`, `tonnage`, `haulage_gross` ($\sum \text{Angkutan}$), dan `trip_money` ($\sum \text{Uang Jalan}$) dari tabel `delivery_trips`.
   - Mengambil beban `operational_cost` dan `sparepart_service_cost` dari tabel `truck_expenses`.
   - Menghitung `net_haulage` = `haulage_gross` - `operational_cost`.
   - Menghitung `net_truck_result` = `net_haulage` - `total_trip_money` - `sparepart_service_cost`.
   - Menghitung `gross_pay` = `round(net_truck_result * (commission_percentage / 100))`.
   - Mengambil potongan `bpjs_amount` dan cicilan aktif `kasbon` dari tabel `loans`.
   - Menghitung `net_pay` = `gross_pay` + `total_allowances` - `bpjs_deduction` - `kasbon_deduction`.
   - Memberikan flag `has_warning = true` (ikon segitiga kuning seperti pada UI Rise) apabila `net_truck_result < 0`, atau ada ritase yang belum memiliki tarif, atau potongan kasbon melebihi komisi gaji.
4. Menyimpan/memperbarui baris di tabel `payrolls` dan meng-update total agregat (`total_payroll_amount`, `total_trip_money`, `total_net_pay`) di tabel `payroll_periods`.
5. `DB::commit()`.
