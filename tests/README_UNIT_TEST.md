# Dokumentasi Lengkap Automated Test Suite (Unit & Feature Tests)

Dokumen ini merangkum seluruh skenario pengujian otomatis, nama fungsi test, fitur yang diuji, serta nilai variabel dan muatan data (payload) yang digunakan dalam pengetesan pada sistem `app-payroll-asm`.

Total Pengujian: **136 Tests, 960 Assertions (100% Passed)**.

---

## Daftar Berkas Pengujian

1. [`tests/Unit/LogikaBisnisTest.php`](#1-testsunitlogikabisnistestphp)
2. [`tests/Feature/AlurSuratJalanDanAccTest.php`](#2-testsfeaturealursuratjalandanacctestphp)
3. [`tests/Feature/Admin/MutasiCrudAdminTest.php`](#3-testsfeatureadminmutasicrudadmintestphp)
4. [`tests/Feature/Admin/OtorisasiPayrollAdminTest.php`](#4-testsfeatureadminotorisasipayrolladmintestphp)
5. [`tests/Feature/Admin/HalamanAdminTest.php`](#5-testsfeatureadminhalamanadmintestphp)
6. [`tests/Feature/Admin/TabelAjaxAdminTest.php`](#6-testsfeatureadmintabelajaxadmintestphp)
7. [`tests/Feature/Executive/HalamanPimpinanTest.php`](#7-testsfeatureexecutivehalamanpimpinantestphp)
8. [`tests/Feature/Executive/DataPerusahaanTest.php`](#8-testsfeatureexecutivedataperusahaantestphp)
9. [`tests/Feature/Security/KeamananAdminTest.php`](#9-testsfeaturesecuritykeamananadmintestphp)
10. [`tests/Feature/Auth/LoginAdminTest.php`](#10-testsfeatureauthloginadmintestphp)
11. [`tests/Feature/Auth/AuthenticationTest.php`](#11-testsfeatureauthauthenticationtestphp)
12. [`tests/Feature/Auth/PasswordUpdateTest.php`](#12-testsfeatureauthpasswordupdatetestphp)
13. [`tests/Feature/Auth/PasswordResetTest.php`](#13-testsfeatureauthpasswordresettestphp)
14. [`tests/Feature/ProfileTest.php`](#14-testsfeatureprofiletestphp)

---

### 1. `tests/Unit/LogikaBisnisTest.php`
Pengujian unit murni tanpa HTTP layer untuk memverifikasi kalkulasi formula matematika, integritas kasbon, dan trait database.

| Nama Fungsi | Fitur yang Diuji | Variabel & Input Uji | Ekspektasi |
|---|---|---|---|
| `test_hitung_komponen_gaji_memakai_rumus_slip_dan_presisi_bulat()` | Rumus 4 tahap kalkulasi komisi supir & gaji bersih | `total_rit = 21`, `tonase_kg = 609740`, `total_angkutan_kotor = 92877797`, `potongan_operasional = 799216`, `total_uang_jalan = 43850000`, `biaya_sparepart = 6462000`, `persentase_komisi_bp = 1000`, `potongan_bpjs = 129000`, `potongan_kasbon = 500000` | `angkutan_bersih = 92078581`<br>`hasil_bersih_armada = 41766581`<br>`gaji_komisi = 4176658`<br>`gaji_bersih = 3547658` (persis slip fisik Kuwat Slamet) |
| `test_hitung_komponen_gaji_tidak_pernah_negatif()` | Penjaga nilai minimum nol `max(0, ...)` | `total_uang_jalan = 100000000`, `biaya_sparepart = 50000000` (jauh melebihi pendapatan kotor `total_angkutan_kotor = 10000000`) | Seluruh turunan: `hasil_bersih_armada = 0`, `gaji_komisi = 0`, `gaji_bersih = 0` (tidak ada angka minus) |
| `test_kasbon_dibagi_rata_dan_sisa_masuk_cicilan_terakhir()` | Pembagian cicilan kasbon ganjil | Kasbon `nominal = 1000000`, `tenor = 3` kali potong | Angsuran 1: `333.000`<br>Angsuran 2: `333.000`<br>Angsuran 3 (terakhir): `334.000`<br>Total pas: `1.000.000` |
| `test_angsuran_terpotong_mengurangi_sisa_dan_melunasi_kasbon()` | Pelunasan bertahap kasbon | Kasbon `1.000.000`, 3 angsuran. Potong angsuran ke-1 (`333.000`), ke-2 (`333.000`), ke-3 (`334.000`) | Sisa berkurang ke `667.000`, lalu `334.000`, lalu `0`. Status kasbon berubah otomatis menjadi `lunas`. |
| `test_batas_kasbon_dan_tenor_maksimal_sesuai_konstanta_model()` | Batas SOP Kasbon | Model `Kasbon::BATAS_MAKSIMAL`, `Kasbon::TENOR_MAKSIMAL` | Batas nominal: `Rp 1.000.000`<br>Tenor maksimal: `3` periode potong |
| `test_trait_normalisasi_huruf_besar_hanya_menyentuh_kolom_entitas()` | Trait `NormalisasiHurufBesar` | `Truk`: `nomor_polisi = 'be 1234 ab'`, `merek_model = 'hino tronton'` | Tersimpan di database otomatis menjadi `BE 1234 AB` dan `HINO TRONTON` tanpa menyentuh kolom password/email. |
| `test_query_daftar_master_tidak_mengalami_n_plus_one()` | Optimasi Eager Loading query master | Karyawan berelasi dengan pengguna, truk, rute | Kueri SQL konstan saat baris bertambah (bebas N+1 problem). |
| `test_ringkasan_payroll_menghitung_total_dari_baris_periode()` | Agregasi ringkasan periode payroll | Koleksi baris `Penggajian` periode Jan-26 | Total supir, total rit, total tonase, omzet, dan total kas penggajian terhitung akurat. |
| `test_format_periode_mengikuti_label_bahasa_indonesia()` | Format tanggal Indonesia | Model `PeriodeGaji` bulan Januari 2026 | Format: `1 Jan 2026 — 31 Jan 2026`, status `Draf Perhitungan` / `Disetujui Pimpinan`. |

---

### 2. `tests/Feature/AlurSuratJalanDanAccTest.php`
Pengujian alur bisnis end-to-end penugasan surat jalan, validasi upload foto, verifikasi admin, pemblokiran pengajuan payroll, ACC bertahap Pimpinan, hingga pencairan paid.

| Nama Fungsi | Fitur yang Diuji | Variabel & Input Uji | Ekspektasi |
|---|---|---|---|
| `test_admin_membuat_surat_jalan_dengan_status_default_draft_dan_opsi_terbit()` | Pembuatan SJ Draf vs Terbit | POST `/admin/operational/trips`<br>SJ 1 (default): `tonase_ton = 25,00` (tanpa status)<br>SJ 2: `tonase_ton = 26,00`, `status = 'terbit'` | SJ 1 tersimpan dengan status `draft` (tidak muncul di supir).<br>SJ 2 tersimpan dengan status `terbit` (muncul di `/my/trips`). |
| `test_supir_wajib_mengisi_nama_penerima_dan_foto_saat_konfirmasi_sj()` | Form Konfirmasi Sampai Supir | PATCH `/my/trips/{id}/konfirmasi`<br>1. Uji tanpa foto & nama penerima: `tonase_ton = 28,48`<br>2. Uji lengkap: `nama_penerima = 'budi jetty 2'`, foto `UploadedFile::fake()->image('bukti.png')` | Uji 1: Gagal validasi 422 (`nama_penerima` dan `foto_surat_jalan` wajib).<br>Uji 2: Sukses 200, status berubah `menunggu_verifikasi`, nama penerima dinormalisasi `BUDI JETTY 2`. |
| `test_admin_diblokir_ajukan_payroll_sebelum_sj_terverifikasi_lalu_berhasil()` | Blokir Pengajuan Payroll bila SJ belum terverifikasi | SJ berstatus `terbit` lalu `menunggu_verifikasi`.<br>PATCH `/admin/payroll/{period}/submit` | Ditolak HTTP `422` (periode tetap `draft`).<br>Setelah Admin verifikasi (`status = 'terverifikasi'`), pengajuan sukses `200` dan periode menjadi `pending`. |
| `test_pimpinan_acc_satuan_acc_checkbox_revisi_dan_pencairan_paid()` | Otorisasi bertahap Pimpinan di `/executive/payroll` | 3 baris penggajian (`supirA`, `supirB`, `supirC`)<br>1. ACC satuan supirA: `PATCH .../approve` (`id_penggajian`)<br>2. ACC checkbox supirB & supirC: `POST .../approve-batch` (`ids`)<br>3. Minta revisi: `PATCH .../reject` (`alasan_penolakan = 'Cek kasbon supir B'`)<br>4. Pencairan: `PATCH .../disburse` (`ids = [A, B]`) | 1. Status supirA menjadi `approved`.<br>2. Seluruh supir menjadi `approved` & periode ikut `approved`.<br>3. Periode kembali ke `draft` dengan `catatan_penolakan`.<br>4. Baris gaji menjadi `paid`, `angsuran_kasbon` ditandai `terpotong`. Periode `paid` menolak pencairan ulang (422). |
| `test_supir_dapat_mengunggah_banyak_foto_surat_jalan_dan_terbaca_di_supir_dan_admin()` | Multi-upload foto bukti SJ | PATCH `/my/trips/{id}/konfirmasi`<br>`foto_surat_jalan` berupa array 2 berkas WebP | Tersimpan dalam kolom JSON array `foto_surat_jalan` (`>= 2` berkas terkonversi). Tampil di galeri modal supir & admin. |
| `test_validasi_multi_foto_menolak_ukuran_lebih_dari_2_mb_dan_hapus_foto_selektif()` | Batas ukuran foto 2 MB & Hapus Foto parsial | 1. Berkas gambar ukuran 3 MB (`size = 3072 KB`)<br>2. `hapus_foto = ['surat-jalan/foto1.webp']` | 1. Ditolak 422 (maksimal 2 MB per foto).<br>2. Foto terpilih dihapus dari array JSON tanpa menghapus foto lainnya. |
| `test_alur_kurir_mulai_kirim_sedang_dikirim_otorisasi_dan_blokir_payroll()` | Alur `draft` &rarr; `terbit` &rarr; `sedang_dikirim` &rarr; `menunggu_verifikasi` | 1. Supir lain akses mulai-kirim SJ orang lain<br>2. Supir pemilik mulai-kirim: `PATCH /my/trips/{id}/mulai-kirim`<br>3. Ulangi mulai-kirim saat sudah berjalan<br>4. Submit payroll saat masih `sedang_dikirim` | 1. Akses ditolak 403 (FORBIDDEN).<br>2. Sukses 200, status berubah `sedang_dikirim`.<br>3. Ditolak 422 (transisi tidak valid).<br>4. Ditolak 422 (tidak boleh ada SJ berstatus `sedang_dikirim` saat ajukan payroll). |

---

### 3. `tests/Feature/Admin/MutasiCrudAdminTest.php`
Pengujian seluruh endpoint penambahan, perubahan, dan penghapusan data master dan transaksi admin.

| Nama Fungsi | Fitur yang Diuji | Variabel & Input Uji | Ekspektasi |
|---|---|---|---|
| `test_simpan_supir_baru_menghasilkan_json_standar_dan_data_uppercase()` | Tambah supir & normalisasi uppercase | POST `/admin/master/employees`<br>`nama_lengkap = 'agus setiawan'`, `nomor_induk_karyawan = 'drv-099'`, `phone = '08123456789'` | Respon `200` JSON baku (`action: CREATE_EMPLOYEE`). Tersimpan uppercase: `AGUS SETIAWAN` & `DRV-099`. |
| `test_validasi_gagal_mengembalikan_422_dengan_objek_errors()` | Validasi FormRequest standar | POST `/admin/master/employees`<br>Payload kosong `{}` | HTTP `422`, JSON memuat field `errors` spesifik (`nama_lengkap`, `nomor_induk_karyawan`, dll). |
| `test_tambah_dan_ubah_unit_armada()` | CRUD Armada Truk | POST `/admin/master/trucks`<br>`nomor_polisi = 'be 1234 aa'`, `merek_model = 'hino 500'` | Disimpan uppercase `BE 1234 AA`, update berhasil mengubah model menjadi `HINO 700`. |
| `test_nomor_polisi_duplikat_ditolak()` | Integritas Unique Plat Truk | POST truk dengan `nomor_polisi = 'BE 8639 AAU'` yang sudah ada | HTTP `422` (validasi unique nopol gagal). |
| `test_crud_rute_menyimpan_tarif_sebagai_integer()` | CRUD Rute & Jalur Tambang | POST `/admin/master/routes`<br>`tarif_per_ton = '158.760'`, `uang_jalan = '2.250.000'` | Tersimpan sebagai integer murni Rupiah: `158760` dan `2250000` bebas desimal. |
| `test_crud_jenis_truk_lewat_modal_bersarang()` | CRUD Jenis Truk di dalam modal | POST `/admin/master/trucks/jenis`<br>`nama = 'TRONTON HEAVY'` | Tersimpan ke tabel `jenis_truk` dan dapat dipilih di form truk tanpa reload halaman master. |
| `test_simpan_biaya_operasional_dan_tolak_hapus_setelah_disetujui()` | Proteksi Hapus Biaya Operasional Disetujui | Biaya `status = 'disetujui'`, `nominal = 500000`.<br>DELETE `/admin/operational/expenses/{id}` | Penghapusan ditolak HTTP `422` (`INTEGRITY_LOCKED`) demi integritas audit pengeluaran. |
| `test_simpan_kasbon_membuat_jadwal_cicilan_dan_potong_angsuran()` | Pengajuan Kasbon Ber-cicilan | POST `/admin/operational/loans`<br>`nominal = '900.000'`, `tenor = 3` | Dibuatkan 3 baris di `angsuran_kasbon` masing-masing `300.000`. Status kasbon `approved`. |
| `test_kasbon_melebihi_batas_maksimal_ditolak()` | Batas Maksimal Kasbon Rp 1.000.000 | POST kasbon dengan `nominal = '1.500.000'` | Ditolak HTTP `422` (melebihi batas maksimal Rp 1.000.000). |
| `test_update_bpjs_supir_mengubah_potongan_bulanan()` | Perbarui Iuran BPJS Supir | POST `/admin/operational/loans/bpjs`<br>`id_karyawan = 1`, `potongan_bpjs = 129000` | Tersinkronkan ke profil karyawan dan diperbarui pada slip periode aktif. |
| `test_kalkulasi_dan_pengajuan_payroll()` | Hitung Gaji & Rekonsiliasi | POST `/admin/payroll/{period}/calculate` | Menghitung ritase terverifikasi, omzet bruto, potongan uang jalan & sparepart, komisi 10%, dan take home pay. |
| `test_admin_tidak_boleh_approve_periode_payroll()` | Batasan Kewenangan ACC Admin | POST `/admin/payroll/{period}/approval` dengan `mode = 'approve'` | Ditolak HTTP `403` / `422` (Admin dilarang memberikan ACC). |

---

### 4. `tests/Feature/Admin/OtorisasiPayrollAdminTest.php`
Pengujian pemisahan kewenangan otorisasi antara Admin HRD dan Pimpinan (sesuai Activity Diagram).

| Nama Fungsi | Fitur yang Diuji | Variabel & Input Uji | Ekspektasi |
|---|---|---|---|
| `test_admin_tidak_boleh_memberikan_acc()` | Larangan ACC oleh Admin | POST `/admin/payroll/{period}/approval` mode `approve` | Ditolak server dengan respon `422` / `403`. Status periode tetap tidak berubah. |
| `test_admin_boleh_mengajukan_ke_pimpinan()` | Pengajuan Periode (`draft` &rarr; `pending`) | PATCH `/admin/payroll/{period}/submit` | Sukses `200`, status berubah menjadi `pending` (*Menunggu ACC Pimpinan*). |
| `test_admin_boleh_menarik_pengajuan()` | Penarikan Pengajuan (`pending` &rarr; `draft`) | POST `/admin/payroll/{period}/approval` mode `reject` | Sukses `200`, status kembali ke `draft` untuk diperbaiki Admin. |
| `test_halaman_payroll_admin_tidak_menampilkan_tombol_acc()` | Audit Antarmuka Admin | GET `/admin/payroll` | Tombol 'ACC' atau 'Setujui' tidak ada di UI admin; hanya ada tombol 'Ajukan ke Pimpinan'. |

---

### 5. `tests/Feature/Admin/HalamanAdminTest.php`
Pengujian render antarmuka seluruh halaman master, operasional, payroll, dan laporan arsip admin.

| Dataset / Nama Fungsi | Halaman yang Diuji | Status & Assertions |
|---|---|---|
| Dataset `"dashboard"` | `/admin/dashboard` | HTTP `200`, memuat sidebar, ringkasan hari ini, kartu kesiapan supir. |
| Dataset `"payroll"` | `/admin/payroll` | HTTP `200`, memuat tabel supir, kalkulasi gaji, drawer rincian slip. |
| Dataset `"master supir"` | `/admin/master/employees` | HTTP `200`, kolom No, tombol detail supir, modal tambah supir. |
| Dataset `"master armada"` | `/admin/master/trucks` | HTTP `200`, diagram donat SVG armada, modal CRUD jenis truk. |
| Dataset `"master rute"` | `/admin/master/routes` | HTTP `200`, tarif per ton, uang jalan default, alamat asal/tujuan. |
| Dataset `"operasional surat jalan"` | `/admin/operational/trips` | HTTP `200`, tabel surat jalan, status filter, modal buat SJ draf/terbit. |
| Dataset `"operasional kasbon"` | `/admin/operational/loans` | HTTP `200`, tab kasbon & tab BPJS, jadwal angsuran cicilan. |
| Dataset `"laporan arsip"` | `/admin/reports` | HTTP `200`, tabel riwayat periode bulanan, tombol cetak PDF & ekspor. |
| `test_tabel_master_memiliki_kolom_no_dan_tombol_detail()` | Standar Kolom Tabel Master | Setiap baris memuat nomor urut tampilan (bukan ID DB) dan tombol visibility (detail). |
| `test_penghapusan_master_terikat_ditolak_server_side()` | Proteksi Hapus Data Master Terikat | Menghapus supir yang memiliki transaksi ritase & kasbon ditolak HTTP `422` (`INTEGRITY_LOCKED`). |

---

### 6. `tests/Feature/Admin/TabelAjaxAdminTest.php`
Pengujian endpoint interaksi tabel AJAX POST (`POST /admin/interaksi/tabel`).

| Nama Fungsi | Fitur yang Diuji | Input & Parameter | Ekspektasi |
|---|---|---|---|
| Dataset #0 - #7 | Schema JSON tabel per entitas (`karyawan`, `truk`, `rute`, `ritase`, `kasbon`, dll) | POST payload: `{ entitas: '...', halaman: 1, per_halaman: 25 }` | Struktur JSON baku: `success`, `data: { baris, total, total_halaman, halaman_aktif }`. |
| `test_pagination_membatasi_per_halaman_maksimal_seratus()` | Batas Paginasi Keamanan | `per_halaman = 9999` | Dibatasi otomatis oleh server ke maksimal `100` baris per halaman. |
| `test_sorting_memakai_whitelist_kolom()` | Proteksi SQL Injection Sorting | `sort_by = "id; DROP TABLE users--"`, `sort_dir = "desc"` | Kolom ilegal dinetralkan ke whitelist default (`id`), kueri aman. |
| `test_tabel_tidak_mengalami_n_plus_one()` | Pengujian Query Counter N+1 | Eager loading seluruh relasi entitas tabel | Jumlah kueri SQL tidak membengkak seiring bertambahnya baris data. |

---

### 7. `tests/Feature/Executive/HalamanPimpinanTest.php`
Pengujian seluruh halaman Pimpinan/Direksi (`/executive/*`).

| Nama Fungsi | Fitur yang Diuji | Variabel & Skenario | Ekspektasi |
|---|---|---|---|
| `test_seluruh_halaman_pimpinan_dapat_di_render()` | Render Dashboard, Payroll, Reports, Trips, Kalender, Company | Akun role `pimpinan` login | Seluruh halaman mengembalikan HTTP `200`. |
| `test_tahap_pemilihan_periode_tampil_sebelum_rincian_supir()` | Alur Pemilihan Periode Otorisasi | Akses `/executive/payroll` tanpa `?periode=` | Menampilkan kartu grid daftar periode; rincian supir baru terbuka saat periode dipilih. |
| `test_otorisasi_acc_mengembalikan_json_standar_dan_mengubah_status()` | Keputusan ACC Pimpinan | PATCH `/executive/payroll/{period}/approve` | Status baris gaji supir berubah menjadi `approved`, respon JSON baku. |
| `test_penolakan_pimpinan_mengembalikan_periode_ke_draf()` | Keputusan Tolak/Revisi Pimpinan | PATCH `/executive/payroll/{period}/reject`<br>`alasan_penolakan = 'Perbaiki nota sparepart unit'` | Periode kembali ke `draft`, catatan tersimpan di `catatan_penolakan`. |
| `test_peran_selain_pimpinan_tidak_boleh_masuk()` | Role Guard Middleware | Akun `admin` dan `supir` mengakses `/executive/*` | HTTP `403` (Forbidden). |

---

### 8. `tests/Feature/Executive/DataPerusahaanTest.php`
Pengujian singleton profil perusahaan (`data_perusahaan`) yang dikelola Pimpinan.

| Nama Fungsi | Fitur yang Diuji | Variabel & Skenario | Ekspektasi |
|---|---|---|---|
| `test_pimpinan_dapat_membuka_halaman_profil_perusahaan_dan_memperbarui_data()` | Edit Profil Perusahaan | PATCH `/executive/company`<br>`nama_perusahaan = 'PT ANUGERAH SUMBER MAKMUR'`, `nama_bank = 'MANDIRI'`, `no_rekening = '140-00-998822-1'` | Data tersimpan, dinormalisasi uppercase untuk kolom nama & bank. |
| `test_peran_admin_dan_supir_ditolak_mengakses_profil_perusahaan()` | Otoritas Tunggal Pimpinan | Admin / Supir mengakses `/executive/company` | Ditolak HTTP `403`. |
| `test_slip_gaji_dan_laporan_menampilkan_data_perusahaan_dari_database()` | Integrasi Kop Surat & Laporan | Render slip gaji dan laporan cetak | Kop surat, nama pemilik, nomor rekening, dan logo otomatis membaca nilai dari database `data_perusahaan`. |

---

### 9. `tests/Feature/Security/KeamananAdminTest.php`
Pengujian kepatuhan keamanan berlapis (OWASP Top 10, Rate Limiting, sanitasi berkas, dan XSS).

| Nama Fungsi | Fitur yang Diuji | Payload Serangan Uji | Ekspektasi |
|---|---|---|---|
| `test_payload_sql_injection_dinetralkan_pada_pencarian_dan_sorting()` | SQL Injection Protection | `cari = "' OR 1=1 --"`, `sort_by = "SLEEP(5)"` | Dinetralkan oleh parameter binding Eloquent & whitelist sorting. |
| `test_payload_xss_ter_escape_saat_render_halaman()` | Cross-Site Scripting (XSS) | Catatan supir: `<script>alert('XSS')</script>` | Di-escape oleh Blade template engine menjadi `&lt;script&gt;`, skrip tidak dieksekusi. |
| `test_rate_limiter_login_memblokir_percobaan_keenam()` | Rate Limiting Brute Force Login | 5 kali percobaan login kata sandi salah berturut-turut pada akun yang sama | Percobaan ke-6 diblokir dengan HTTP `429` (*Too Many Requests*) selama 900 detik (15 menit). |
| `test_upload_foto_melebihi_dua_megabita_ditolak()` | Batas Ukuran Berkas Gambar | Unggah berkas gambar `size = 2.5 MB` (2560 KB) | Ditolak HTTP `422` dengan pesan validasi ukuran maksimal 2 MB. |
| `test_upload_dengan_ekstensi_skrip_ditolak()` | Eksekusi Berkas Berbahaya | Unggah berkas `shell.php.png` atau `payload.sh` | Ditolak validasi MIME & ekstensi gambar (hanya menerima JPG, PNG, WEBP). |
| `test_header_keamanan_dan_noindex_pada_login()` | Security Headers Middleware | Header respons HTTP | Memuat `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, dan `X-Robots-Tag: noindex`. |

---

### 10. `tests/Feature/Auth/LoginAdminTest.php` & Autentikasi Pengguna
Pengujian flow autentikasi, pengalihan peran (Admin &rarr; `/admin/dashboard`, Pimpinan &rarr; `/executive/dashboard`, Supir &rarr; `/my/payroll`), logout, dan reset password.

| Nama Fungsi | Fitur yang Diuji | Parameter Uji | Ekspektasi |
|---|---|---|---|
| `test_setiap_peran_diarahkan_ke_dashboard_masing_masing()` | Role-Based Dashboard Redirection | Login user admin (`role:admin`), pimpinan (`role:pimpinan`), supir (`role:supir`) | Masing-masing diarahkan ke rute dashboard perannya secara tepat. |
| `test_login_gagal_ajax_mengembalikan_422_dengan_objek_errors()` | Respons Login AJAX Gagal | POST `/login` dengan kata sandi salah | Respon JSON `422` dengan error `email` tanpa membocorkan eksistensi akun. |
| `test_users_can_authenticate_using_the_login_screen()` | Login Web Standar | Form POST `/login` kredensial valid | Sesi aktif, diarahkan ke dashboard. |
| `test_password_can_be_updated()` | Ubah Kata Sandi Akun | PUT `/password`<br>`current_password = 'password'`, `password = 'new-password-123'` | Kata sandi terenkripsi baru berhasil disimpan ke database. |

---

## Ringkasan Perintah Menjalankan Pengujian

```cmd
:: Jalankan seluruh test suite
php artisan test

:: Jalankan satu berkas test spesifik
php artisan test tests/Feature/AlurSuratJalanDanAccTest.php

:: Jalankan satu fungsi test spesifik
php artisan test --filter=test_pimpinan_acc_satuan_acc_checkbox_revisi_dan_pencairan_paid
```
