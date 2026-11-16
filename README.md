# Sistem Penggajian Supir Logistik Batubara (Payroll ASM)

> 🎓 **INFORMASI PROYEK TUGAS AKHIR / SKRIPSI:**  
> **Aplikasi ini dirancang dan dibangun sebagai proyek penelitian Tugas Akhir / Skripsi** di bidang Rekayasa Perangkat Lunak / Sistem Informasi.  
> Seluruh data yang tersaji di dalam basis data sistem—termasuk nama pengemudi, nomor induk karyawan (NIP), nomor registrasi polisi armada truk, rute jalur tambang, nominal transaksi ritase, jadwal angsuran kasbon, serta foto bukti fisik surat jalan—merupakan **DATA SIMULASI / MANIPULASI (DUMMY DATA)** yang digunakan untuk kebutuhan demonstrasi, pengujian skenario fungsional, dan validasi matematis algoritma penggajian. Sistem ini **tidak memuat data rahasia maupun data operasional riil dari perusahaan komersial manapun**.

---

## 1. Deskripsi & Tujuan Aplikasi

Dalam industri logistik transportasi batubara (*coal haulage*), penggajian pengemudi truk tronton tidak menggunakan sistem gaji bulanan statis (*fixed monthly salary*), melainkan berbasis pada **kinerja operasional harian di lapangan (ritase, tonase muatan, rute jalan, dan beban perawatan armada)**.

Tujuan utama dari pengembangan sistem ini adalah **menghadirkan standarisasi dan otomatisasi alur penggajian berbasis kejadian (event-driven operational lifecycle)** yang dimulai sejak **Surat Jalan pertama kali diterbitkan di pos operasional hingga pencairan dana disahkan oleh Direksi**. 

Sistem ini memecahkan berbagai risiko bisnis nyata, seperti:
- Mencegah timbulnya surat jalan fiktif melalui validasi foto bukti fisik bertanda tangan penerima dan normalisasi data timbangan.
- Menjamin tidak ada surat jalan yang tertinggal (*unverified trips*) saat gaji diajukan ke Direksi melalui mekanisme blokir otomatis (*strict pre-condition check*).
- Menghilangkan perselisihan pembagian komisi supir melalui rekonsiliasi berjenjang 4 tahap berpresisi bilangan bulat Rupiah (`BIGINT`).
- Mengotomatiskan pemotongan angsuran kasbon supir tepat pada saat pencairan dana gaji dieksekusi oleh Pimpinan.
- Memberikan transparansi kepada pengemudi melalui **Portal Mandiri Supir** dan fitur **Simulasi & Kalkulator Gaji (1 s.d. 31 Rit)**.

---

## 2. Diagram Alur Bisnis Penggajian End-to-End

Berikut adalah alur aktivitas sistem dari penugasan Surat Jalan hingga pengesahan slip gaji:

![Activity Diagram Alur Penggajian](../docs/ACTIVITY_DIAGRAM.png)

---

## 3. Tahapan Alur Operasional Penggajian (Urutan Bernomor 1–10)

Seluruh proses di dalam aplikasi mengikuti urutan bernomor yang mengikat antar peran (*Admin HRD*, *Supir*, dan *Pimpinan/Direksi*):

1. **Pembuatan Surat Jalan (`draft` atau langsung `terbit`) — Admin HRD (`/admin/operational/trips`):**
   - Admin membuat Surat Jalan baru untuk penugasan supir, nomor polisi armada, rute koridor tambang, dan uang jalan jalan operasional.
   - Status awal dapat disetel ke **`draft`** (*Draf / Belum Diterbitkan*) atau langsung **`terbit`** (*Terbit / Siap Dikirim Supir*).
   - **Kunci Keamanan:** Surat Jalan berstatus **`draft` TIDAK BOLEH terlihat di Portal Supir (`/my/trips`)** dan setiap aksi supir (`mulai-kirim` atau `konfirmasi`) atas Surat Jalan `draft` otomatis ditolak oleh server dengan HTTP `403`.

2. **Penerbitan Surat Jalan (`draft` → `terbit`) — Admin HRD (`/admin/operational/trips`):**
   - Admin menerbitkan Surat Jalan draf (satuan maupun massal) menjadi status **`terbit`** agar penugasan resmi muncul di gawai/portal supir (`/my/trips`).

3. **Mulai Pengantaran oleh Supir (`terbit` → `sedang_dikirim`) — Supir (`/my/trips`):**
   - Supir melihat Surat Jalan berstatus **`terbit`** di Portal Supir, lalu menekan tombol **"Mulai Kirim"** (`PATCH /my/trips/{trip}/mulai-kirim`) saat armada mulai bergerak mengangkut batubara.
   - Status Surat Jalan otomatis berubah menjadi **`sedang_dikirim`** (*Sedang Dikirim*).

4. **Konfirmasi Tiba di Tujuan & Bukti Fisik Timbangan (`sedang_dikirim` → `menunggu_verifikasi`) — Supir (`/my/trips`):**
   - Saat tiba di stockpile atau pelabuhan pembongkaran, supir menekan tombol **"Konfirmasi Sampai"**.
   - Supir **wajib** mengisi **`nama_penerima`** (otomatis dinormalisasi menjadi HURUF BESAR oleh model `Ritase`), tonase hasil timbangan jembatan, dan **wajib** mengunggah minimal 1 (`>= 1`) foto bukti surat jalan fisik yang telah dibubuhi tanda tangan/stempel penerima (`foto_surat_jalan`, berkas gambar otomatis dikonversi ke `.webp` dengan batas maks 2 MB per foto).
   - Status Surat Jalan berubah menjadi **`menunggu_verifikasi`** (*Menunggu Verifikasi Admin*).

5. **Verifikasi Surat Jalan Lapangan (`menunggu_verifikasi` → `terverifikasi`) — Admin HRD (`/admin/operational/trips`):**
   - Admin memeriksa keabsahan foto bukti fisik bertanda tangan, kesesuaian tonase muatan, dan identitas penerima (dapat diverifikasi satuan maupun massal).
   - Admin menandai Surat Jalan sebagai **`terverifikasi`** (*Berhasil Terkirim & Terverifikasi*). Hanya surat jalan dengan status `terverifikasi` yang sah untuk diperhitungkan dalam penggajian bulanan.

6. **Rekonsiliasi Penggajian & Blokir Pengajuan (`draft` → `pending`) — Admin HRD (`/admin/payroll`):**
   - Layanan `PayrollCalculationService::rekonsiliasiPeriode()` **hanya** menghitung surat jalan yang berstatus **`terverifikasi`**.
   - **Blokir Integritas:** Ketika Admin mengajukan rekapitulasi gaji ke Pimpinan (`POST /admin/payroll/{period}/submit`), server **wajib memblokir dengan HTTP `422`** apabila masih ditemukan Surat Jalan pada periode tersebut yang berstatus `draft`, `terbit`, `sedang_dikirim`, atau `menunggu_verifikasi`. Seluruh perjalanan harus tuntas diverifikasi atau dibatalkan terlebih dahulu.
   - Setelah lolos verifikasi, status periode berubah menjadi **`pending`** (*Menunggu Otorisasi Pimpinan*).

7. **Audit & Otorisasi Bertahap Direksi — Pimpinan / Direksi (`/executive/payroll`):**
   - Pimpinan memeriksa kebutuhan likuiditas kas (*cash requirement*), total komisi pengemudi, rekap uang jalan, beban sparepart per unit armada, serta potongan kewajiban (BPJS dan kasbon).
   - Pimpinan berwenang memberikan **ACC Satuan** per supir, **ACC Baris Terpilih** via checkbox, atau **ACC Semua** (`approved`).
   - Jika ditemukan selisih atau anomali data, Pimpinan dapat mengembalikan periode ke status **`draft`** (*Tolak / Revisi*) disertai **`catatan_penolakan`**.

8. **Pencairan Dana (`approved` → `paid`) & Pemotongan Kasbon Otomatis — Pimpinan (`/executive/payroll`):**
   - Pimpinan mengeksekusi pencairan dana (`PATCH /executive/payroll/{period}/disburse`) untuk baris gaji berstatus `approved` menjadi **`paid`** (*Sudah Dicairkan*).
   - Proses pencairan otomatis menandai jadwal tagihan `angsuran_kasbon` periode berjalan menjadi **`terpotong`** dan mengurangi saldo `kasbon.sisa_pinjaman` (otomatis berstatus **`lunas`** saat sisa pinjaman mencapai `0`).

9. **Penerbitan Slip Gaji & Laporan Keuangan Resmi (`data_perusahaan`):**
   - Slip gaji dan laporan keuangan periode hanya diterbitkan dan dapat diunduh supir/admin setelah periode berstatus disetujui (`approved` / `paid`).
   - Identitas legal perusahaan (kop surat, logo, nomor rekening bank pencairan, nama & tanda tangan pemilik) dikelola secara terpusat oleh Pimpinan di `/executive/company` pada tabel `data_perusahaan`.

10. **Simulasi Mandiri & Perencanaan Ritase Supir — Supir (`/my/simulation`):**
    - Supir dapat membuat proyeksi penghasilan mandiri dengan menyusun 1 s.d. 31 baris surat jalan simulasi berdasarkan rute-rute resmi aktif.
    - Supir dapat memilih mode potongan: **Data Riil Sistem** (otomatis menarik beban sparepart unit armadanya, potongan BPJS, dan cicilan kasbon bulan berjalan) atau **Input Manual Sendiri** (memasukkan perkiraan angka bebas untuk menganalisis berbagai skenario).
    - Hasil simulasi dapat disimpan ke arsip akun supir pada tabel `simulasi_gaji` atau dimuat ulang kapan saja.

---

## 4. Rumus Kalkulasi Gaji Supir 4 Tahap

Seluruh perhitungan matematis dikonsolidasikan pada satu kelas layanan tunggal: `App\Services\PayrollCalculationService::hitungKomponenGaji()`.  
Semua nilai nominal uang menggunakan bilangan bulat Rupiah (`BIGINT`) untuk mencegah galat pembulatan desimal (*floating-point rounding errors*), berat muatan dalam kilogram (`tonase_kg BIGINT`), dan persentase komisi supir disimpan sebagai basis poin (`persentase_komisi_bp SMALLINT`, di mana `1000` = `10,00%`).

```text
Ritase Terverifikasi (tonase_kg × tarif_per_ton)
  └─► Total Angkutan Kotor (Bruto)
        └─ (-) Potongan Operasional
              └─► Angkutan Bersih (Pendapatan Bersih Angkutan)
                    └─ (-) Total Uang Jalan
                    └─ (-) Biaya Sparepart Armada (Biaya Unit Supir)
                          └─► Hasil Bersih Armada
                                └─ (×) Basis Poin Komisi / 10.000 (Dibulatkan ke Rupiah)
                                      └─► Gaji Komisi Supir
                                            └─ (+) Total Tunjangan Tetap / Bonus
                                            └─ (-) Potongan BPJS Ketenagakerjaan
                                            └─ (-) Potongan Kasbon (Jadwal Angsuran Berjalan)
                                            └─ (-) Potongan Historis Lain
                                                  └─► GAJI BERSIH (Take-Home Pay Diterima Supir)
```

1. **Tahap 1 — Pendapatan Angkutan Bersih:**  
   `angkutan_bersih = max(0, total_angkutan_kotor - potongan_operasional)`
2. **Tahap 2 — Hasil Bersih Armada:**  
   `hasil_bersih_armada = max(0, angkutan_bersih - total_uang_jalan - biaya_sparepart)`
3. **Tahap 3 — Hak Komisi Supir:**  
   `gaji_komisi = (int) round(($hasil_bersih_armada * $persentase_komisi_bp) / 10000)`
4. **Tahap 4 — Gaji Bersih / Take-Home Pay Supir:**  
   `gaji_bersih = max(0, gaji_komisi + total_tunjangan - potongan_bpjs - potongan_kasbon - potongan_historis_lain)`

---

## 5. Matriks Peran & Hak Akses Pengguna (Role-Based Access Control)

| Peran | Prefix URL | Hak Akses & Tanggung Jawab Operasional |
|---|---|---|
| **Admin HRD** (`admin`) | `/admin/*` | Pengelolaan data master (supir, armada truk, rute), penerbitan & verifikasi surat jalan lapangan, rekonsiliasi payroll bulanan, pengajuan pengesahan ke Pimpinan (`submit`). **Dilarang keras menyetujui / memberi ACC.** |
| **Pimpinan / Direksi** (`pimpinan`) | `/executive/*` | Pemegang otoritas tertinggi pengesahan gaji: ACC satuan per supir, ACC batch/massal, penolakan/revisi periode ke draf, eksekusi pencairan dana gaji (`disburse`), serta tata kelola data legal perusahaan (`/executive/company`). |
| **Pengemudi / Supir** (`supir`) | `/my/*` | Akses mandiri supir: menerima surat jalan terbit, tombol mulai kirim, konfirmasi serah terima muatan & unggah bukti foto fisik, pemantauan buku kasbon & jadwal cicilan, kalkulator simulasi gaji (1–31 rit), serta unduh slip gaji resmi. |

---

## 6. Panduan Menjalankan Proyek di Lingkungan Lokal (CMD Windows)

```cmd
:: 1. Masuk ke folder proyek Laravel
cd app-payroll-asm

:: 2. Pasang dependensi PHP dan Node.js
composer install
npm install

:: 3. Konfigurasi Environment & Kunci Aplikasi
copy .env.example .env
php artisan key:generate

:: 4. Migrasi Database dan Muat Data Simulasi Akademik
php artisan migrate:fresh --seed

:: 5. Kompilasi Aset Frontend (Tailwind CSS & Blade)
npm run build

:: 6. Jalankan Server Pengembangan Lokal
php artisan serve --port=8888
```

Setelah server aktif, akses aplikasi melalui peramban web di: `http://127.0.0.1:8888`

### Akun Uji Coba Default (Kredensial Simulasi):
- **Akun Admin HRD:** `admin@payroll.test` | Kata Sandi: `password`
- **Akun Pimpinan Direksi:** `pimpinan@payroll.test` | Kata Sandi: `password`
- **Akun Supir:** `supir@payroll.test` | Kata Sandi: `password`

---

## 7. Pengujian Otomatis & Verifikasi Kepatuhan (QA Test Suite)

Aplikasi dilengkapi dengan rangkaian unit & feature test komprehensif untuk memastikan seluruh aturan bisnis dan alur keamanan OWASP terpenuhi:

```cmd
:: Menjalankan seluruh pengujian fungsional dan keamanan
php artisan test

:: Verifikasi integritas kompilasi template Blade
php artisan view:clear
php artisan view:cache

:: Verifikasi rekonsiliasi rumus matematika slip fisik (Single Source of Truth)
php ..\Zzz\cek-layanan-payroll.php

:: Verifikasi status HTTP 200 seluruh rute sistem
php ..\Zzz\verifikasi-halaman.php
```
