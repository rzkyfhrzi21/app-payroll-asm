# BRIEF STANDAR DEV LAUNCHER (MENU INTERAKTIF + TAB TERMINAL)

Dokumen ini adalah **standar yang dapat dipakai ulang di proyek apa pun**, terlepas dari stack teknologi (Go, Node.js, Python, PHP, Flutter, Docker, dan sebagainya). Isinya menjelaskan cara kerja, aturan desain, serta prosedur adaptasi launcher terminal berbasis `.bat` + `.ps1`.

Tujuan sistem: membuka tab terminal yang sudah berada di folder proyek yang tepat dan **menampilkan teks perintah siap copy-paste**, tanpa pernah mengeksekusi perintah secara otomatis.

---

## 1. Filosofi Desain

1. **Satu Jendela, Banyak Tab**
   Semua terminal dibuka sebagai *tab* di jendela Windows Terminal yang sedang aktif (`wt -w 0 new-tab`), bukan jendela terpisah. Jika Windows Terminal tidak tersedia, launcher otomatis jatuh ke `start cmd`.

2. **Teks Siap Copy-Paste (*No Auto-Run*)**
   Perintah tidak pernah dijalankan otomatis. Launcher hanya **mencetak teks perintah** di dalam tab, lalu menampilkan prompt di bawahnya. Pengembang menyalin teks tersebut dan menjalankannya sendiri.

3. **Pemisahan Tampilan dan Logika**
   - File `.ps1` **hanya** menggambar menu (kotak berwarna). Tidak membuka terminal, tidak menjalankan proses.
   - File `.bat` memegang seluruh logika: membaca pilihan, membersihkan proses lama, membuka tab, mencetak perintah.

4. **Aman terhadap Port dan Proses Zombie**
   Sebelum membuka tab backend, launcher memeriksa port yang dipakai, menghentikan PID lama, dan membersihkan sisa executable hasil build sebelumnya.

---

## 2. Struktur File

| File | Peran | Isi |
| --- | --- | --- |
| `dev.bat` / `run.bat` | Titik masuk | Hanya memanggil `dev-menu.bat` |
| `dev-menu.bat` | Pengendali | Menu loop, pemilihan opsi, pembersihan port, pembukaan tab, pencetakan perintah |
| `dev-menu.ps1` | Penampil | Menggambar kotak menu CLI berwarna, lalu selesai |

> Tidak ada file bantuan lain. Launcher sengaja dibuat mandiri agar mudah disalin ke proyek lain.

---

## 3. Alur Kerja

```
dev.bat
   └─> dev-menu.bat
          ├─> powershell -File dev-menu.ps1 -Action menu   (gambar menu, lalu keluar)
          ├─> set /p CHOICE                                 (baca pilihan pengguna)
          └─> :OPEN_TAB <Judul> <Folder> <Perintah>
                 ├─> wt -w 0 new-tab -d "<Folder>" --title "<Judul>" cmd.exe /K "echo <Perintah>"
                 └─> (fallback) start "<Judul>" /d "<Folder>" cmd /K "echo <Perintah>"
```

Di dalam tab, yang terlihat pengguna hanyalah:

```
go run ./cmd/api

D:\path\proyek\backend>
```

Pengguna menyalin baris pertama, menempelkannya di prompt, lalu menekan `ENTER`.

---

## 4. Daftar Opsi Menu (Pola Standar)

Nomor opsi mengikuti pola berikut dan dapat disesuaikan jumlahnya per proyek:

| Opsi | Aksi | Perintah yang dicetak |
| --- | --- | --- |
| `[1]` | Buka tab service utama + tab aplikasi | Perintah backend & frontend |
| `[2]` | Buka tab service utama saja | Perintah backend |
| `[3]` | Buka tab aplikasi (mode pengembangan) | Perintah frontend dev |
| `[4]` | Buka tab aplikasi (mode rilis/produksi) | Perintah frontend release/build |
| `[9]` | Hentikan proses lama, buka ulang kedua tab | Perintah backend & frontend |
| `[0]` | Matikan seluruh proses, keluar | — (browser tidak disentuh) |

Contoh isi perintah untuk berbagai stack (pilih sesuai proyek):

| Stack | Service utama | Aplikasi / frontend |
| --- | --- | --- |
| Go + Flutter | `go run ./cmd/api` | `flutter run -d chrome` |
| Node.js | `npm run dev` | `npm run client` |
| Python | `uvicorn app.main:app --reload` | `npm run dev` |
| PHP / Laravel | `php artisan serve` | `npm run dev` |
| Docker | `docker compose up api` | `docker compose up web` |

---

## 5. Variabel Konfigurasi di `dev-menu.bat`

Sesuaikan blok berikut saat memakai launcher di proyek baru:

```bat
set "PROJECT_DIR=%~dp0"
set "PS1=%PROJECT_DIR%dev-menu.ps1"
set "BACKEND_DIR=%PROJECT_DIR%backend-codelab"   REM folder service utama
set "APP_DIR=%PROJECT_DIR%app-codelab"           REM folder aplikasi/frontend
```

Nomor port yang dibersihkan berada di subroutine `:KILL_OLD_BACKEND` (contoh standar: `8080`).

---

## 6. SOP Penggunaan

1. Jalankan `dev.bat` (atau `run.bat`) dari root proyek.
2. Pilih nomor opsi, misalnya `1` untuk membuka backend dan aplikasi sekaligus.
3. Tab muncul di jendela terminal yang sama, masing-masing sudah berisi teks perintahnya.
4. Blok teks perintah, copy, tempel ke prompt, tekan `ENTER`.
5. Untuk menghentikan semuanya, pilih `0`.

---

## 7. Penanganan Insiden

**Gejala:** backend gagal start dengan pesan `bind: address already in use` (atau sejenisnya pada stack lain).

**Penanganan:**
- Pilih opsi `[9]` atau `[2]` pada menu dev.
- Subroutine `:KILL_OLD_BACKEND` akan mencari PID pemakai port lewat `netstat -aon | findstr ":8080 "`, lalu menghentikannya dengan `taskkill /F /T /PID`.
- Sisa executable hasil build lama (misalnya `api.exe`) juga dibersihkan agar tidak menahan file.

**Catatan:** opsi `[0]` sengaja tidak menutup browser. Hanya proses server dan aplikasi yang dihentikan.

---

## 8. Batasan Teknis yang Disengaja

Launcher **tidak** berusaha mengetikkan perintah ke buffer input CMD secara otomatis. Pendekatan tersebut sudah dicoba dan terbukti rapuh:

| Pendekatan | Masalah yang muncul |
| --- | --- |
| `doskey` macro | Perintah tetap harus diketik/dipanggil; bukan prefill sungguhan |
| `PSReadLine::Insert` | `NullReferenceException` pada host PowerShell non-interaktif |
| `WScript.Shell SendKeys` | Karakter pertama hilang saat jendela baru dibuka; teks antar tab bisa tercampur |
| Injeksi `WriteConsoleInput` | Bergantung pada PID console; Windows Terminal memayungi tab sehingga PID jendela bukan milik proses target |

Karena itu standar ini memakai pendekatan **cetak teks**. Hasilnya deterministik di semua versi Windows, tidak bergantung pada fokus jendela, dan tidak merusak riwayat perintah.

---

## 9. Cara Menerapkan ke Proyek Lain

1. Salin tiga file: `dev.bat`, `dev-menu.bat`, `dev-menu.ps1` ke root proyek tujuan.
2. Sesuaikan `BACKEND_DIR` dan `APP_DIR` pada `dev-menu.bat`.
3. Ganti teks perintah pada setiap pemanggilan `:OPEN_TAB` sesuai stack proyek.
4. Sesuaikan nomor port pada `:KILL_OLD_BACKEND`.
5. Sesuaikan judul tab (`--title`) agar mudah dikenali di Windows Terminal.
6. Uji dengan opsi yang tidak mematikan proses apa pun terlebih dahulu (misalnya opsi `[3]`).

---

## 10. Checklist Verifikasi

- [ ] Menu tampil rapi dan border kotak rata.
- [ ] Tab terbuka di jendela terminal yang sama, bukan jendela baru.
- [ ] Setiap tab berada di folder proyek yang benar.
- [ ] Teks perintah tercetak dan **tidak** tereksekusi otomatis.
- [ ] Opsi `[9]` berhasil menghentikan proses lama sebelum membuka tab baru.
- [ ] Opsi `[0]` menutup proses server/aplikasi tanpa menutup browser.
- [ ] Tidak ada jendela PowerShell yang tertinggal terbuka.
