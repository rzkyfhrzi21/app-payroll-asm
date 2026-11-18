# BRIEF KONFIGURASI PHP.INI & OPCACHE (PANDUAN PERCEPATAN LOKAL & PRODUKSI)
**Universal PHP Configuration Guide — OPcache, JIT, Realpath Cache & Tuning Runtime untuk PHP 8.1 s/d 8.5 (Windows, Linux, Docker)**

---

## 📌 HEADER & RINGKASAN KONFIGURASI

Dokumen ini merupakan acuan baku bagi AI Agent dan developer untuk menyetel `php.ini` agar aplikasi PHP/Laravel berjalan cepat, baik di mesin lokal maupun di server produksi. Fokus utama adalah **OPcache** — cache bytecode PHP yang menghilangkan proses kompilasi ulang file pada setiap request.

Pada proyek **Payroll ASM** (Laravel 13, PHP 8.3.14 NTS), pengaktifan OPcache memangkas waktu respons halaman dari **±220 ms menjadi ±54 ms** (sekitar 4x lebih cepat) tanpa mengubah satu baris kode aplikasi.

| No | Kategori Konfigurasi | Target & Poin Kritis | Cakupan Versi |
|:---|:---|:---|:---|
| **1** | **Aktivasi OPcache** | `zend_extension`, `opcache.enable`, urutan pemuatan ekstensi, verifikasi ekstensi benar-benar termuat. | PHP 8.1 – 8.5 |
| **2** | **Alokasi Memori OPcache** | `memory_consumption`, `interned_strings_buffer`, `max_accelerated_files` sesuai ukuran proyek. | PHP 8.1 – 8.5 |
| **3** | **Validasi Timestamp** | `validate_timestamps` & `revalidate_freq` — wajib ON di lokal, OFF di produksi. | PHP 8.1 – 8.5 |
| **4** | **JIT Compiler** | `opcache.jit`, `opcache.jit_buffer_size` — kapan menguntungkan, kapan justru merugikan. | PHP 8.0 – 8.5 |
| **5** | **OPcache CLI & File Cache** | `opcache.enable_cli`, `opcache.file_cache` untuk mempercepat `php artisan` dan test suite. | PHP 8.1 – 8.5 |
| **6** | **Realpath Cache** | `realpath_cache_size`, `realpath_cache_ttl` untuk mengurangi stat() syscall. | PHP 8.1 – 8.5 |
| **7** | **Batas Runtime** | `memory_limit`, `max_execution_time`, `upload_max_filesize`, `post_max_size`, `max_input_vars`. | PHP 8.1 – 8.5 |
| **8** | **Keamanan & Debug** | `display_errors`, `expose_php`, `error_reporting`, `zend.assertions` per lingkungan. | PHP 8.1 – 8.5 |
| **9** | **Lokasi & Edit php.ini** | Perbedaan path per stack (XAMPP, Laragon, PHP manual, Linux, Docker, cPanel). | Multi-lingkungan |
| **10** | **Verifikasi & Troubleshooting** | Perintah cek status OPcache, tabel gejala dan penyebab. | PHP 8.1 – 8.5 |
| **11** | **Checklist Pre-Deploy** | Daftar periksa wajib sebelum rilis ke produksi. | Semua stack |

> **⚠️ PRINSIP UTAMA:** Konfigurasi lokal dan produksi **BERBEDA**. Menyalin setelan produksi ke lokal akan membuat perubahan kode tidak langsung terlihat, dan menyalin setelan lokal ke produksi akan membuang potensi performa sekaligus membocorkan informasi internal.

---

## 1. Mengapa OPcache Penting (Dasar Keputusan)

### 1.1 Masalah Tanpa OPcache
PHP adalah bahasa yang **mengompilasi ulang setiap file pada setiap request**. Untuk Laravel dengan ratusan file vendor, satu request halaman bisa menyentuh 200–400 file. Tanpa OPcache:

1. PHP membaca file dari disk
2. Tokenizer memecah kode menjadi token
3. Parser membangun AST
4. Compiler menghasilkan opcode
5. Opcode dieksekusi
6. Semua hasil kompilasi **dibuang**

Langkah 1–4 adalah kerja yang sia-sia jika file tidak berubah. OPcache menyimpan hasil opcode di memori bersama sehingga langkah 1–4 hanya terjadi sekali.

### 1.2 Bukti Pengukuran Nyata (Payroll ASM, Windows 11, PHP 8.3.14 NTS)

| Skenario | Waktu Respons `/login` (6 request berurutan) |
|:---|:---|
| OPcache **tidak aktif** | 209, 233, 209, 222, 209, 226 ms |
| OPcache **aktif** | 81, 51, 57, 40, 58, 37 ms |

Pengukuran pendukung:

| Metrik | Nilai | Catatan |
|:---|:---|:---|
| Boot framework Laravel | 146 ms | Tanpa OPcache |
| MySQL `SELECT 1` | 0,6 ms | Database bukan penyebab lambat |
| Vite dev server (CSS+JS) | 17–27 ms | Aset bukan penyebab lambat |
| File statis `/build/manifest.json` (0,3 KB) | 109 ms | Bukti biaya kompilasi PHP, bukan ukuran file |
| OPcache terpakai setelah aktif | 27,6 MB / 256 MB | 0 wasted memory |

> **Kesimpulan:** jika satu file statis berukuran 0,3 KB butuh ±109 ms, penyebabnya hampir pasti kompilasi PHP — bukan jaringan, bukan database, bukan aset.

### 1.3 Batasan yang Perlu Dipahami
OPcache **tidak** mempercepat pembuatan proses baru. Pada Windows, `php -r "echo 1;"` tetap ±140 ms meski OPcache aktif, karena itu adalah biaya `CreateProcess` Windows. OPcache hanya berdampak pada **proses yang hidup lama** dan melayani banyak request:

| Proses | OPcache Efektif? |
|:---|:---|
| `php artisan serve` (PHP built-in server) | ✅ Ya — proses tunggal melayani semua request |
| PHP-FPM / Apache mod_php | ✅ Ya — proses worker bertahan |
| CLI sekali jalan (`php artisan migrate`) | ⚠️ Terbatas — perlu `file_cache` (lihat Bagian 5) |
| Setiap request CGI/FastCGI baru tanpa worker | ❌ Tidak — proses selalu baru |

---

## 2. Konfigurasi OPcache Inti (Semua Versi 8.1 – 8.5)

### 2.1 Blok Konfigurasi Dasar

Tambahkan/pastikan baris berikut ada di `php.ini`. Letakkan `zend_extension=opcache` **setelah** blok `extension=` dan **sebelum** blok `[opcache]`.

```ini
; --- Ekstensi (letakkan setelah extension= lainnya) ---
zend_extension=opcache

[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
opcache.save_comments=1
opcache.use_cwd=0
opcache.max_wasted_percentage=5
```

### 2.2 Penjelasan Setiap Direktif

| Direktif | Nilai | Alasan |
|:---|:---|:---|
| `zend_extension=opcache` | — | Memuat OPcache. **Bukan** `extension=`, karena OPcache adalah zend extension. Pada Windows cukup `opcache`, tidak perlu `php_opcache.dll`. |
| `opcache.enable` | `1` | Mengaktifkan cache untuk SAPI web. |
| `opcache.enable_cli` | `1` | Mengaktifkan untuk CLI (`php artisan`, queue worker, test). Berpengaruh pada proses CLI yang berjalan lama. |
| `opcache.memory_consumption` | `256` | Ruang opcode dalam MB. Default 128 MB cukup untuk proyek kecil; Laravel + vendor bisa 200 MB+. |
| `opcache.interned_strings_buffer` | `8` | Ruang string interned dalam MB. Naikkan ke 16 bila proyek punya banyak kelas. |
| `opcache.max_accelerated_files` | `20000` | Jumlah maksimum file di cache. Laravel + Composer vendor ±15.000–25.000 file. |
| `opcache.validate_timestamps` | `1` (dev) / `0` (prod) | Bila `1`, PHP mengecek perubahan file. Wajib `1` di lokal. |
| `opcache.revalidate_freq` | `2` | Interval cek timestamp (detik). Hanya berlaku bila `validate_timestamps=1`. |
| `opcache.save_comments` | `1` | **WAJIB `1` untuk Laravel.** Bila `0`, docblock dibuang dan atribut/annotation pada paket tertentu bisa gagal. |
| `opcache.use_cwd` | `0` | Mempercepat lookup dengan tidak menyertakan working directory pada key. Aman bila nama file unik. |
| `opcache.max_wasted_percentage` | `5` | Ambang memori terbuang sebelum restart otomatis. |

### 2.3 Tabel Alokasi Memori Berdasarkan Ukuran Proyek

| Skala Proyek | Jumlah File PHP | `memory_consumption` | `interned_strings_buffer` | `max_accelerated_files` |
|:---|:---|:---|:---|:---|
| Skrip kecil / PHP Native | < 200 | 64 | 4 | 2000 |
| Aplikasi sedang | 200 – 2.000 | 128 | 8 | 6000 |
| Laravel + vendor standar | 2.000 – 5.000 | 192 | 8 | 10000 |
| **Laravel + banyak paket (proyek ini: 9.369 file)** | **5.000 – 12.000** | **256** | **8** | **20000** |
| Monolit sangat besar | > 12.000 | 512 | 16 | 50000 |

> **Rumus praktis:** `max_accelerated_files` harus **lebih besar** dari jumlah file PHP yang dieksekusi. Bila cache penuh, OPcache melakukan restart dan performa turun. Hitung dengan:
> ```cmd
> php -r "echo iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('vendor', FilesystemIterator::SKIP_DOTS)));"
> ```
>
> **Contoh nyata proyek ini:** `vendor/` berisi **9.369 file**, dengan `max_accelerated_files=20000` PHP mengalokasikan **32.531 slot** (`max_cached_keys`), dan hanya **1.098 skrip** yang ter-cache pada satu kali eksekusi CLI. Artinya alokasi 20000 sudah longgar dan aman.

### 2.4 Nilai Maksimum yang Diizinkan
`opcache.max_accelerated_files` hanya menerima angka antara **200 dan 1.000.000**. Nilai di luar rentang akan diabaikan dan memakai default.

---

## 3. Konfigurasi per Versi PHP (8.1 s/d 8.5)

### 3.1 Matriks Ketersediaan Direktif

| Direktif | 8.1 | 8.2 | 8.3 | 8.4 | 8.5 | Catatan |
|:---|:---:|:---:|:---:|:---:|:---:|:---|
| `opcache.enable` | ✅ | ✅ | ✅ | ✅ | ✅ | Sejak PHP 5.5 |
| `opcache.enable_cli` | ✅ | ✅ | ✅ | ✅ | ✅ | Default `0` |
| `opcache.memory_consumption` | ✅ | ✅ | ✅ | ✅ | ✅ | Default 128 |
| `opcache.interned_strings_buffer` | ✅ | ✅ | ✅ | ✅ | ✅ | Default 8 |
| `opcache.max_accelerated_files` | ✅ | ✅ | ✅ | ✅ | ✅ | Maks 1.000.000 |
| `opcache.validate_timestamps` | ✅ | ✅ | ✅ | ✅ | ✅ | Default 1 |
| `opcache.save_comments` | ✅ | ✅ | ✅ | ✅ | ✅ | Default 1 — jangan dimatikan di Laravel |
| `opcache.jit` | ✅ | ✅ | ✅ | ✅ | ✅ | Sejak PHP 8.0 |
| `opcache.jit_buffer_size` | ✅ | ✅ | ✅ | ✅ | ✅ | Default `0` (JIT mati) |
| `opcache.file_cache` | ✅ | ✅ | ✅ | ✅ | ✅ | Sejak PHP 7.0 |
| `opcache.preload` | ✅ | ✅ | ✅ | ✅ | ✅ | Sejak PHP 7.4, butuh FPM |

> **Tidak ada direktif OPcache baru yang signifikan antara PHP 8.1 sampai 8.5.** Blok konfigurasi di Bagian 2.1 berlaku untuk seluruh rentang versi tersebut tanpa perubahan.

### 3.2 Catatan Khusus per Versi

**PHP 8.1**
- `opcache.jit` sudah stabil. Bila memakai JIT, gunakan `tracing` bukan `function`.
- `opcache.interned_strings_buffer` tetap 8 sebagai default aman.

**PHP 8.2**
- Readonly classes dan DNF types tidak memengaruhi OPcache.
- Tidak ada perubahan format opcode yang memerlukan penyesuaian.

**PHP 8.3** *(versi proyek ini)*
- Tidak ada perubahan direktif. Format opcode 8.3 tidak kompatibel dengan cache 8.2 — setelah upgrade versi PHP, **hapus cache** bila memakai `file_cache`.
- `opcache.jit_buffer_size` default tetap `0`.

**PHP 8.4**
- Tidak ada direktif OPcache baru.
- Perhatikan: `opcache.save_comments=1` makin penting karena ekosistem banyak memakai atribut PHP.

**PHP 8.5**
- Tetap memakai direktif yang sama. Selalu cek `php -i | findstr opcache` setelah upgrade karena distribusi Windows/Linux tertentu bisa mengubah default.

### 3.3 Perbedaan Windows vs Linux

| Aspek | Windows | Linux |
|:---|:---|:---|
| Nama ekstensi | `zend_extension=opcache` | `zend_extension=opcache.so` |
| Shared memory | Per-proses (tidak antar-proses) | Benar-benar shared antar worker |
| Dampak pada `php artisan serve` | ✅ Satu proses, tetap efektif | ✅ Efektif |
| Dampak pada FPM multi-worker | ⚠️ Terbatas | ✅ Optimal |
| `file_cache` | Sangat disarankan untuk CLI | Berguna untuk CLI |

> **Penting untuk Windows:** OPcache di Windows tidak berbagi memori antar proses seperti di Linux. Karena `php artisan serve` memakai satu proses PHP yang bertahan, OPcache tetap sangat efektif — inilah yang menghasilkan 4x percepatan pada pengukuran di Bagian 1.2. Namun bila memakai Apache/IIS dengan banyak worker, manfaatnya lebih kecil dibanding Linux. Untuk CLI, gunakan `file_cache` (Bagian 5).

**Bukti pengukuran antar-proses (proyek ini):** dua eksekusi CLI berturut-turut tetap menampilkan `num_cached_scripts = 1098` dengan `opcache_hit_rate` hanya **0,1%–0,2%**. Artinya setiap invokasi CLI benar-benar mulai dari cache kosong, sesuai penjelasan di Bagian 5.

---

## 4. Profil DEV vs PRODUCTION

### 4.1 Profil LOKAL (Development)

Fokus: **perubahan kode langsung terlihat**, error tampil jelas, tidak ada cache yang membingungkan.

```ini
zend_extension=opcache

[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=20000

; KUNCI UNTUK DEV: selalu cek perubahan file
opcache.validate_timestamps=1
opcache.revalidate_freq=0

opcache.save_comments=1
opcache.use_cwd=0

; JIT biasanya TIDAK membantu request pendek; biarkan mati
opcache.jit=disable
opcache.jit_buffer_size=0

; Opsional untuk CLI cepat (lihat Bagian 5)
;opcache.file_cache=D:\Dev\opcache-cache
;opcache.file_cache_only=0
```

> **`revalidate_freq=0`** berarti PHP memeriksa timestamp pada **setiap** request. Sedikit lebih lambat daripada `2`, tetapi menjamin Anda tidak pernah melihat kode lama saat mengedit file. Untuk pengembangan ini pilihan yang benar.

### 4.2 Profil PRODUCTION

Fokus: **kecepatan maksimum**, cache tidak pernah dicek ulang, restart terkontrol saat deploy.

```ini
zend_extension=opcache

[opcache]
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000

; KUNCI UNTUK PROD: jangan pernah cek file, restart saat deploy
opcache.validate_timestamps=0

opcache.save_comments=1
opcache.use_cwd=0
opcache.max_wasted_percentage=10

; JIT: uji dulu, jangan langsung production
opcache.jit=tracing
opcache.jit_buffer_size=64M
```

> **PERINGATAN:** dengan `validate_timestamps=0`, perubahan file **tidak akan** terlihat sampai PHP-FPM di-reload. Wajib reload setiap deploy:
> ```cmd
> :: Linux / systemd
> sudo systemctl reload php8.3-fpm
>
> :: Windows (Laragon/XAMPP) — restart service Apache/PHP
> net stop Apache2.4 && net start Apache2.4
> ```

### 4.3 Ringkasan Perbedaan Kritis

| Direktif | Lokal | Produksi | Alasan |
|:---|:---:|:---:|:---|
| `validate_timestamps` | `1` | `0` | Dev butuh lihat perubahan; prod butuh kecepatan |
| `revalidate_freq` | `0` | (tidak dipakai) | — |
| `enable_cli` | `1` | `0` | Prod tidak butuh; hemat memori |
| `interned_strings_buffer` | `8` | `16` | Prod memuat lebih banyak kelas sekaligus |
| `jit` | `disable` | `tracing` (uji dulu) | JIT untung untuk komputasi berat, bukan request pendek |
| `max_wasted_percentage` | `5` | `10` | Prod jarang restart, beri ruang |

---

## 5. OPcache untuk CLI & File Cache

### 5.1 Mengapa `php artisan test` Tetap Lambat

Setiap perintah CLI membuat **proses baru**, sehingga shared memory OPcache selalu kosong di awal. `opcache.enable_cli=1` hanya membantu di dalam satu proses (misalnya `php artisan serve` atau `queue:work`), bukan antar-invokasi.

### 5.2 Solusi: `file_cache`

`opcache.file_cache` menyimpan opcode ke **disk**, sehingga bertahan antar proses. Ini yang benar-benar mempercepat `php artisan test`, `migrate`, dan seeder berulang.

```ini
; Buat direktori dulu, lalu pastikan dapat ditulis
opcache.file_cache=D:\Dev\opcache-cache
opcache.file_cache_only=0
opcache.file_cache_consistency_checks=1
```

```cmd
:: Windows — buat direktori cache
mkdir D:\Dev\opcache-cache
```

> **Catatan:** hapus isi direktori `file_cache` setiap kali:
> - Upgrade versi PHP (format opcode berubah)
> - Ganti arsitektur (NTS ↔ TS, x86 ↔ x64)
> - Gejala aneh yang tidak bisa dijelaskan (cache korup)
>
> ```cmd
> rmdir /s /q D:\Dev\opcache-cache
> mkdir D:\Dev\opcache-cache
> ```

### 5.3 Jangan Aktifkan `file_cache_only`

`opcache.file_cache_only=1` mematikan shared memory dan hanya memakai disk — jauh lebih lambat. Biarkan `0`.

---

## 6. JIT Compiler (PHP 8.0+)

### 6.1 Kapan JIT Menguntungkan

| Beban Kerja | JIT Membantu? |
|:---|:---|
| Request web CRUD (Laravel, 10–50 ms) | ❌ Tidak signifikan |
| Komputasi matematis berat / loop panjang | ✅ Ya, bisa 2–4x |
| Image processing, enkripsi, parsing besar | ✅ Ya |
| CLI worker berjalan lama | ⚠️ Kadang |

Untuk aplikasi payroll/CRUD seperti proyek ini, **JIT tidak memberi manfaat terasa** dan menambah penggunaan memori. Rekomendasi: **matikan di lokal**, dan bila ingin di produksi, uji A/B dulu.

### 6.2 Konfigurasi JIT yang Aman

```ini
[opcache]
; Mati (default, aman)
opcache.jit=disable
opcache.jit_buffer_size=0

; Bila ingin mengaktifkan (uji dulu!)
;opcache.jit=tracing
;opcache.jit_buffer_size=64M
```

> **`opcache.jit_buffer_size` wajib diisi lebih dari 0** agar JIT benar-benar aktif. Menyetel `opcache.jit=tracing` tanpa buffer = JIT tetap mati. Ini sumber kebingungan yang umum.

### 6.3 Mode JIT

| Mode | Karakteristik | Cocok Untuk |
|:---|:---|:---|
| `disable` | JIT mati | Default, CRUD web |
| `tracing` | Optimasi berdasarkan jejak eksekusi | **Pilihan utama** bila mengaktifkan |
| `function` | Optimasi per fungsi | Jarang lebih baik |
| `1205` | Numeric mask (dianggap warisan) | Tidak disarankan |

---

## 7. Realpath Cache

PHP memanggil `stat()` untuk memverifikasi keberadaan file pada setiap `include`/`require`. Realpath cache menyimpan hasilnya.

```ini
realpath_cache_size=4096k
realpath_cache_ttl=600
```

| Direktif | Lokal | Produksi | Alasan |
|:---|:---:|:---:|:---|
| `realpath_cache_size` | `4096k` | `4096k` – `8192k` | Laravel menyentuh ribuan path |
| `realpath_cache_ttl` | `120` | `600` | Dev perlu revalidasi cepat saat file baru dibuat |

> Nilai `4096k` sudah menjadi default PHP 8+. Naikkan ke `8192k` bila proyek memakai banyak paket. Jangan turunkan di bawah default.

---

## 8. Batas Runtime untuk Aplikasi Laravel

### 8.1 Nilai yang Disarankan

```ini
; Cukup untuk Composer & Laravel
memory_limit=512M

; Dev: tanpa batas (0) agar tidak terputus saat debugging
; Prod: batasi agar proses macet tidak menggantung
max_execution_time=0        ; lokal
;max_execution_time=60      ; produksi

max_input_time=-1

; Naikkan bila ada upload Excel/PDF/dokumen
upload_max_filesize=20M
post_max_size=25M

; Form panjang (mis. import ritase banyak baris)
max_input_vars=5000

; Timezone harus sama dengan config('app.timezone')
date.timezone=Asia/Jakarta
```

### 8.2 Hubungan `post_max_size` dan `upload_max_filesize`

`post_max_size` **harus lebih besar** dari `upload_max_filesize`, karena POST memuat juga field lain, header, dan boundary multipart.

| `upload_max_filesize` | `post_max_size` minimum |
|:---|:---|
| 2M (default) | 8M |
| 10M | 15M |
| 20M | 25M |
| 50M | 60M |

> **Gejala bila salah:** file kecil pun gagal terunggah, atau `$_POST` kosong tanpa pesan error yang jelas. Laravel melaporkan `PostTooLargeException`.

### 8.3 `max_input_vars` untuk Form Besar

Default `1000` memotong form dengan banyak field secara senyap — hanya sebagian data yang masuk. Untuk form payroll dengan puluhan baris ritase, naikkan ke `5000`.

---

## 9. Keamanan & Debug per Lingkungan

| Direktif | Lokal | Produksi | Alasan |
|:---|:---|:---|:---|
| `display_errors` | `On` | `Off` | Prod tidak boleh menampilkan stack trace ke pengguna |
| `error_reporting` | `E_ALL` | `E_ALL & ~E_DEPRECATED` | Prod tidak perlu noise deprecated |
| `expose_php` | `Off` | `Off` | Menyembunyikan versi PHP dari header `X-Powered-By` |
| `zend.assertions` | `1` | `-1` | `-1` = assertion dikompilasi keluar, lebih cepat |
| `log_errors` | `On` | `On` | Selalu ON |

```ini
; --- LOKAL ---
display_errors=On
error_reporting=E_ALL
expose_php=Off
zend.assertions=1

; --- PRODUCTION ---
display_errors=Off
error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT
expose_php=Off
zend.assertions=-1
log_errors=On
```

> **`expose_php=Off`** sebaiknya di kedua lingkungan — tidak ada ruginya dan menutup satu vektor informasi.

---

## 10. Menemukan & Mengedit php.ini per Lingkungan

### 10.1 Cek Lokasi File Aktif

```cmd
:: Cara paling andal — menampilkan path yang BENAR-BENAR dipakai
php --ini
```

Contoh keluaran:
```
Loaded Configuration File:         D:\Dev\php-8.3.14-nts\php.ini
Scan for additional .ini files in: (none)
```

> **Penting:** bila `Loaded Configuration File` kosong, PHP memakai default bawaan dan **tidak membaca php.ini sama sekali**. Ini penyebab umum "sudah saya ubah tapi tidak berefek".

### 10.2 Lokasi Umum per Stack

| Stack | Path php.ini | Catatan |
|:---|:---|:---|
| PHP manual (Windows) | `C:\php\php.ini` atau sesuai instalasi | Proyek ini: `D:\Dev\php-8.3.14-nts\php.ini` |
| XAMPP (Windows) | `C:\xampp\php\php.ini` | Edit juga `php\php.ini` bila ada duplikat |
| Laragon (Windows) | `C:\laragon\bin\php\php-8.x\php.ini` | Pilih folder versi yang dipakai |
| Ubuntu/Debian (FPM) | `/etc/php/8.3/fpm/php.ini` | Untuk web |
| Ubuntu/Debian (CLI) | `/etc/php/8.3/cli/php.ini` | Untuk `php artisan` |
| Alpine/Docker | `/usr/local/etc/php/php.ini` | Sering perlu dibuat dari `php.ini-production` |
| cPanel / Shared Hosting | Tidak bisa diedit langsung | Gunakan `.user.ini` atau panel "Select PHP Version → Options" |

> **Linux FPM punya DUA php.ini.** Mengubah `cli/php.ini` tidak memengaruhi web, dan sebaliknya. Ini penyebab umum setelan "tidak berpengaruh".

### 10.3 Backup Sebelum Mengubah

```cmd
:: Windows
copy "D:\Dev\php-8.3.14-nts\php.ini" "D:\Dev\php-8.3.14-nts\php.ini.backup"

:: Linux
sudo cp /etc/php/8.3/fpm/php.ini /etc/php/8.3/fpm/php.ini.backup
```

### 10.4 Shared Hosting Tanpa Akses php.ini

Buat `.user.ini` di root aplikasi (berlaku untuk PHP-FPM/CGI):

```ini
; .user.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
memory_limit=256M
upload_max_filesize=20M
post_max_size=25M
```

Atau via `.htaccess` (bila `AllowOverride` mengizinkan):

```apache
php_value memory_limit 256M
php_value upload_max_filesize 20M
php_value post_max_size 25M
```

> Tidak semua direktif `zend_extension` bisa disetel lewat `.user.ini` atau `.htaccess` — pemuatan ekstensi wajib dari `php.ini` utama.

### 10.5 Docker

```dockerfile
FROM php:8.3-fpm

RUN docker-php-ext-install opcache pdo_mysql

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
```

`docker/php/opcache.ini`:
```ini
zend_extension=opcache
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.jit=disable
opcache.jit_buffer_size=0
```

> Pada image resmi `php:*`, OPcache sudah dikompilasi di dalamnya. Cukup tambahkan file konfigurasi di `conf.d/` — tidak perlu mengunduh DLL/SO.

---

## 11. Verifikasi Setelah Perubahan

### 11.1 Cek Ekstensi Benar-benar Termuat

```cmd
php -r "echo 'opcache_loaded=', extension_loaded('Zend OPcache') ? 'YA' : 'TIDAK', PHP_EOL;"
```

Harus menghasilkan `opcache_loaded=YA`.

### 11.2 Cek Nilai Direktif Aktif

```cmd
php -i | findstr /C:"opcache.enable" /C:"opcache.memory_consumption" /C:"opcache.max_accelerated_files" /C:"opcache.validate_timestamps"
```

Di PowerShell:
```powershell
php -i | Select-String -Pattern '^opcache\.(enable|memory_consumption|max_accelerated_files|validate_timestamps)'
```

> Format `nama => nilai_aktif => nilai_asli` menampilkan nilai efektif dan nilai dari php.ini. Bandingkan keduanya — bila berbeda, ada setelan lain yang menimpa.

### 11.3 Cek Status Runtime (Paling Akurat)

```cmd
php -r "print_r(opcache_get_status(false));"
```

Perhatikan:
- `opcache_enabled` harus `1`
- `cache_full` harus kosong/false
- `used_memory` vs `free_memory` — bila `free_memory` mendekati 0, naikkan `memory_consumption`
- `wasted_memory` — bila `current_wasted_percentage` mendekati `max_wasted_percentage`, cache sering restart

### 11.4 Uji Percepatan Nyata

```cmd
:: Ukur 6 request berturut-turut ke halaman yang sama
for /L %i in (1,1,6) do @curl -o NUL -s -w "%%{time_total}s\n" http://127.0.0.1:8888/login
```

Interpretasi:
- **Request pertama** selalu lebih lambat (cache masih dingin)
- **Request ke-2 dan seterusnya** seharusnya jauh lebih cepat
- Bila semua request sama lambatnya → OPcache **tidak** termuat atau proses selalu baru

> **Setelah mengubah php.ini, WAJIB restart server.** Proses yang sudah berjalan memegang konfigurasi lama di memori. Ini penyebab paling umum "sudah diubah tapi tidak ada efek".

```cmd
:: Matikan server dev (Ctrl+C), lalu jalankan ulang
php artisan serve --host=127.0.0.1 --port=8888
```

---

## 12. Troubleshooting

| Gejala | Kemungkinan Penyebab | Solusi |
|:---|:---|:---|
| `extension_loaded('Zend OPcache')` = TIDAK | `zend_extension` dikomentari, atau salah tulis `extension=` | Gunakan `zend_extension=opcache`, bukan `extension=` |
| Sudah diubah tapi tidak ada efek | Server belum di-restart | Restart `php artisan serve` / FPM / Apache |
| Perubahan kode tidak terlihat | `validate_timestamps=0` | Setel `1` di lokal, atau restart |
| Justru error setelah `save_comments=0` | Docblock/atribut dibuang | Kembalikan `opcache.save_comments=1` |
| Cache penuh / sering restart | `max_accelerated_files` terlalu kecil | Naikkan ke 20000–50000 |
| `free_memory` hampir 0 | `memory_consumption` kurang | Naikkan ke 256–512 |
| JIT tidak aktif meski `jit=tracing` | `jit_buffer_size=0` | Setel `jit_buffer_size=64M` |
| `php --ini` → `Loaded Configuration File` kosong | PHP tidak menemukan php.ini | Set `PHPRC` atau letakkan php.ini di path yang dicari |
| CLI lambat, web cepat | Setiap CLI = proses baru | Aktifkan `opcache.file_cache` (Bagian 5) |
| Web lambat, CLI cepat | Salah edit `cli/php.ini` bukan `fpm/php.ini` | Edit php.ini milik SAPI yang dipakai |
| Error aneh setelah upgrade PHP | `file_cache` dari versi lama | Hapus direktori `file_cache` |
| Upload file gagal senyap | `post_max_size` < `upload_max_filesize` | Naikkan `post_max_size` |

### 12.1 Gejala Khusus Laravel: Cache Konfigurasi di Lokal

> **⚠️ PELAJARAN DARI PROYEK INI:** menjalankan `php artisan config:cache` di lingkungan lokal dapat memicu kerusakan data.

Penyebabnya:

1. `config:cache` membekukan seluruh nilai `.env` ke `bootstrap/cache/config.php`
2. `phpunit.xml` menyetel `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`
3. Setelah config di-cache, **env dari phpunit.xml tidak lagi dibaca**
4. Test yang memakai trait `RefreshDatabase` jadi menunjuk ke **MySQL asli**
5. `RefreshDatabase` mengosongkan seluruh tabel database pengembangan

**Aturan:**

```cmd
:: JANGAN lakukan ini di lokal bila ada test dengan RefreshDatabase
php artisan config:cache

:: Gunakan ini di lokal
php artisan config:clear
php artisan view:cache
```

Bila ingin tetap memakai `config:cache` di lokal, lindungi test dengan database terpisah di `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="3309"/>
<env name="DB_DATABASE" value="app-payroll-asm-testing"/>
```

Lalu buat database khusus test tersebut. Ini memastikan test tidak pernah menyentuh data pengembangan.

---

## 13. Perintah Cepat (Cheat Sheet)

```cmd
:: === CEK ===
php --ini
php -v
php -r "echo extension_loaded('Zend OPcache') ? 'OPcache: ON' : 'OPcache: OFF', PHP_EOL;"
php -i | findstr /C:"opcache"
php -r "print_r(opcache_get_status(false));"

:: === SETELAH UBAH php.ini ===
:: 1. Restart server dev
:: 2. Bersihkan cache aplikasi (bukan cache OPcache)
php artisan config:clear
php artisan cache:clear
php artisan view:cache

:: === BERSIHKAN FILE CACHE OPCACHE (setelah upgrade PHP) ===
rmdir /s /q D:\Dev\opcache-cache
mkdir D:\Dev\opcache-cache

:: === RESTART DI PRODUKSI ===
:: Linux
sudo systemctl reload php8.3-fpm
:: Windows
net stop Apache2.4 && net start Apache2.4
```

---

## 14. Checklist Pre-Deployment Konfigurasi PHP

Sebelum rilis ke staging/produksi, seluruh poin berikut **WAJIB berstatus sesuai**:

### 14.1 OPcache

- [ ] `zend_extension=opcache` aktif dan `extension_loaded('Zend OPcache')` = `YA`.
- [ ] `opcache.enable=1`.
- [ ] `opcache.memory_consumption` minimal 128, disarankan 256 untuk Laravel.
- [ ] `opcache.max_accelerated_files` lebih besar dari jumlah file PHP di `vendor`.
- [ ] `opcache.save_comments=1` (jangan dimatikan pada Laravel).
- [ ] `opcache_get_status()` menunjukkan `cache_full` = false dan `wasted_memory` rendah.
- [ ] `opcache.validate_timestamps=0` **di produksi**, `=1` **di lokal**.
- [ ] Sudah diverifikasi bahwa reload FPM/service dilakukan setiap deploy (karena timestamp validation mati).

### 14.2 Runtime

- [ ] `memory_limit` minimal 256M (Composer & seeder butuh lebih).
- [ ] `upload_max_filesize` dan `post_max_size` sesuai kebutuhan unggah, dengan `post_max_size` lebih besar.
- [ ] `max_input_vars` cukup untuk form terbesar (minimal 3000 untuk form payroll).
- [ ] `date.timezone` sama dengan `config('app.timezone')`.

### 14.3 Keamanan

- [ ] `display_errors=Off` di produksi.
- [ ] `expose_php=Off` di semua lingkungan.
- [ ] `log_errors=On` dengan `error_log` menunjuk lokasi yang dapat ditulis.
- [ ] `zend.assertions=-1` di produksi.
- [ ] File `php.ini` tidak dapat diakses publik (di luar document root).

### 14.4 Aplikasi (Laravel)

- [ ] `APP_DEBUG=false` di produksi.
- [ ] `php artisan config:cache`, `route:cache`, `view:cache` dijalankan di produksi.
- [ ] `php artisan optimize:clear` dijalankan sebelum cache ulang setelah deploy.
- [ ] `storage/` dan `bootstrap/cache/` dapat ditulis proses web.
- [ ] Verifikasi test tidak menunjuk database produksi sebelum dijalankan.

### 14.5 Bukti Kinerja

- [ ] Waktu respons halaman utama diukur **sebelum** dan **sesudah** perubahan konfigurasi.
- [ ] Request ke-2 dan seterusnya terbukti lebih cepat dari request pertama.
- [ ] Tidak ada regresi fungsi setelah perubahan (jalankan test suite).
