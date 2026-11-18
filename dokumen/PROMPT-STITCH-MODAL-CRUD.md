PROMPT STITCH — MODAL CRUD DATA MASTER
Sistem Penggajian Supir Batubara (CoalTrans - PT KAFA)
Laravel Blade + Tailwind + Alpine.js (modal, bukan halaman baru)

CATATAN PEMAKAIAN
- Copy seluruh isi di bawah garis putus-putus ke Stitch.
- Desain visual (warna, tipografi, spacing) sudah ada di Stitch, jadi prompt
  ini sengaja hanya membahas FITUR, FIELD, VALIDASI, dan PERILAKU.
- Bahasa label UI: Indonesia.

================================================================================

Buat 3 komponen modal (dialog) CRUD untuk aplikasi penggajian supir batubara.
Ikuti design system, palet warna, tipografi, dan komponen kartu/tabel yang
sudah ada di project ini. Jangan membuat style baru.

Konteks navigasi:
Modal dipanggil dari halaman Data Master yang sudah ada tombolnya:
- Modal Karyawan  <- halaman "Data Supir & Karyawan" (/admin/master/employees)
- Modal Truk      <- halaman "Unit Armada Truk" (/admin/master/trucks)
- Modal Rute      <- halaman "Tarif Rute & Jalur Hauling" (/admin/master/routes)

Setiap entitas butuh 3 modal: TAMBAH (create), EDIT (update), HAPUS (confirm).
Modal Edit memakai isian yang sama dengan Tambah, tetapi sudah terisi data
baris yang dipilih dan judulnya "Ubah ...".

================================================================================
MODAL 1 — KARYAWAN / SUPIR
================================================================================

Judul tambah : "Tambah Data Supir"
Judul edit   : "Ubah Data Supir: {nama_lengkap}"

FIELD INPUT (urutan tampil):

1. Nomor Induk Karyawan
   - Teks, wajib, maks 20 karakter
   - Contoh placeholder: DRV-KAFA-043
   - Saat EDIT: read-only (tidak boleh diubah)
   - Validasi: harus unik, tidak boleh sama dengan karyawan lain
   - Pesan error: "Nomor induk sudah dipakai karyawan lain."

2. Nama Lengkap
   - Teks, wajib, maks 150 karakter
   - Validasi: minimal 3 karakter
   - Pesan error: "Nama lengkap wajib diisi minimal 3 karakter."

3. Jabatan
   - Teks, wajib, maks 100 karakter
   - Placeholder: Supir Tronton
   - Boleh diketik bebas (bukan dropdown)

4. Vendor
   - Teks, opsional, maks 100 karakter
   - Placeholder: PT KAFA
   - Helper text kecil: "Kosongkan bila supir internal."

5. Persentase Komisi
   - Input angka dengan suffix "%", opsional
   - Rentang 0 - 100, mendukung 2 desimal (contoh 9,50)
   - Default saat tambah: 10,00
   - Helper text: "Dipakai untuk hitung gaji komisi supir."
   - Validasi: tidak boleh negatif, tidak boleh lebih dari 100
   - Pesan error: "Persentase komisi harus antara 0 sampai 100."

6. Nama Bank
   - Teks, opsional, maks 50 karakter
   - Placeholder: BCA
   - Saran isian (datalist, tetap boleh ketik bebas): BCA, BNI, BRI, MANDIRI

7. Nomor Rekening
   - Teks, opsional, maks 30 karakter
   - Hanya angka, tanpa spasi/titik/strip saat disimpan
   - Saat mengetik, format otomatis dengan titik per 3 digit
   - Validasi: bila diisi, panjang 6 - 20 digit
   - Pesan error: "Nomor rekening harus 6 sampai 20 digit angka."
   - Catatan UI: bila Nama Bank kosong tetapi Nomor Rekening diisi,
     tampilkan peringatan kuning inline "Nama bank belum dipilih."

8. Potongan BPJS Bulanan
   - Input Rupiah, opsional
   - Format otomatis "Rp 129.000" saat mengetik, nilai disimpan sebagai angka
   - Default saat tambah: Rp 129.000
   - Validasi: bilangan bulat, tidak boleh negatif
   - Helper text: "Potongan tetap setiap periode penggajian."

9. Akun Login Supir (HANYA saat TAMBAH)
   - Teks email, wajib untuk supir baru, maks 150 karakter
   - Dipakai supir untuk masuk ke Portal Supir
   - Validasi: format email valid + belum terdaftar
   - Pesan error: "Email sudah terdaftar. Gunakan email lain."
   - Saat EDIT: field ini disembunyikan, ganti dengan teks statis
     "Akun login: {email} (tidak dapat diubah dari sini)"
   - Helper text: "Sandi awal dibuat otomatis dan dikirim ke email ini."

PERILAKU MODAL KARYAWAN
- Tombol footer: "Batal" (kiri, sekunder) dan "Simpan Data Supir" (kanan, primer)
- Saat menyimpan: tombol berubah jadi spinner + teks "Menyimpan..." dan disabled
- Setelah sukses: modal tertutup, tabel langsung menampilkan baris baru/terubah,
  muncul toast hijau "Data supir berhasil disimpan."
- Bila validasi gagal: modal tetap terbuka, field bermasalah diberi border merah
  dan pesan error di bawah field, fokus otomatis ke field pertama yang error.
- Bila server error: tampilkan banner merah di atas body modal
  "Gagal menyimpan data. Silakan coba lagi."
- Tutup modal lewat tombol X, tombol Batal, klik backdrop, atau tombol Esc.
  Bila ada perubahan belum disimpan, tampilkan konfirmasi
  "Perubahan belum disimpan. Tutup formulir?"

================================================================================
MODAL 2 — UNIT ARMADA TRUK
================================================================================

Judul tambah : "Tambah Unit Armada Truk"
Judul edit   : "Ubah Unit Armada: {nomor_polisi}"

FIELD INPUT (urutan tampil):

1. Nomor Polisi
   - Teks, wajib, maks 15 karakter
   - Otomatis diubah ke HURUF BESAR saat mengetik
   - Contoh placeholder: BE 8639 AAU
   - Validasi: unik antar unit, format minimal berisi 1 huruf dan 1 angka
   - Pesan error: "Nomor polisi sudah terdaftar pada unit lain."

2. Merek & Model
   - Teks, wajib, maks 100 karakter
   - Placeholder: Hino 500 TI Putih
   - Saran isian (datalist) dari merek yang sudah ada, tetap boleh ketik bebas:
     Hino 500 TI Putih, Hino 500 TI Hijau, Fuso Fighter, Scania P360

3. Vendor
   - Teks, opsional, maks 100 karakter
   - Placeholder: PT KAFA
   - Helper text: "Kosongkan bila unit milik sendiri."
   - Di bawah field tampilkan pilihan cepat (chip): "KAFA" dan "Subkon"
     Chip hanya mengisi field, tetap bisa diedit manual.

PERILAKU MODAL TRUK
- Sama seperti modal karyawan: Batal / "Simpan Unit", loading state, toast
  hijau "Data armada berhasil disimpan.", validasi inline, konfirmasi tutup.
- Tambahkan blok info ringan di bawah form (bukan input):
  "Jumlah ritase dan tonase dihitung otomatis dari data ritase.
   Tidak perlu diisi manual."
- Bila nomor polisi yang diubah sudah dipakai unit lain, tampilkan error
  pada field, bukan alert.

================================================================================
MODAL 3 — TARIF RUTE HAULING
================================================================================

Judul tambah : "Tambah Tarif Rute Hauling"
Judul edit   : "Ubah Tarif Rute: {kode}"

FIELD INPUT (urutan tampil):

1. Kode Rute
   - Teks, wajib, maks 20 karakter
   - Otomatis HURUF BESAR, spasi diganti tanda hubung (-)
   - Placeholder: MIP-PORT
   - Saat EDIT: read-only
   - Validasi: unik antar rute
   - Pesan error: "Kode rute sudah digunakan."
   - Helper text: "Dipakai sebagai kode pada surat jalan dan slip gaji."

2. Material
   - Teks, wajib, maks 50 karakter
   - Placeholder: BATU BARA
   - Otomatis HURUF BESAR
   - Saran isian (datalist): BATU BARA, OVERBURDEN, PASIR

3. Tarif per Ton
   - Input Rupiah, wajib
   - Format otomatis "Rp 158.760" saat mengetik
   - Validasi: bilangan bulat lebih dari 0
   - Pesan error: "Tarif per ton wajib diisi dan harus lebih dari 0."
   - Helper text: "Total angkutan dihitung dari tonase x tarif ini."

4. Uang Jalan Default
   - Input Rupiah, wajib
   - Format otomatis "Rp 2.250.000"
   - Validasi: bilangan bulat, boleh 0, tidak boleh negatif
   - Pesan error: "Uang jalan tidak boleh negatif."
   - Helper text: "Nilai awal saat membuat ritase baru untuk rute ini."

PANEL SIMULASI (khusus modal rute, tampil di dalam modal, read-only)
Tampilkan kotak ringkasan yang menghitung langsung saat user mengetik:
- Baris "Contoh: 28,48 ton x Rp 158.760"  -> "= Rp 4.521.485"
- Baris "Sisa setelah uang jalan Rp 2.250.000" -> "= Rp 2.271.485"
- Catatan kecil: "Pembulatan ke rupiah terdekat. Angka hanya simulasi."
Perhitungan memakai pembulatan ke bawah pada hasil tonase x tarif,
lalu dikurangi uang jalan.

PERILAKU MODAL RUTE
- Batal / "Simpan Rute", loading state, toast "Tarif rute berhasil disimpan."
- Saat user mengubah Tarif per Ton pada rute yang sudah punya ritase,
  tampilkan peringatan kuning di dalam modal:
  "Rute ini sudah dipakai pada N ritase. Perubahan tarif tidak mengubah
   nilai ritase yang sudah tercatat."
  (N diisi jumlah ritase; kalau 0, peringatan tidak muncul.)

================================================================================
MODAL 4 — KONFIRMASI HAPUS (berlaku untuk ketiga entitas)
================================================================================

Judul : "Hapus Data?"  /  subjek mengikuti entitas
Isi  : "Data {label} akan dihapus permanen dan tidak dapat dikembalikan."

Aturan tampilan pesan per entitas:
- Karyawan yang sudah punya ritase atau penggajian:
  tombol hapus DISABLED, dan tampilkan penjelasan:
  "Supir ini memiliki {N} ritase dan {M} slip gaji. Data tidak dapat dihapus."
  Sediakan alternatif tombol sekunder "Nonaktifkan Supir" bila tersedia.
- Truk yang sudah dipakai pada ritase atau penggajian:
  "Unit ini dipakai pada {N} ritase. Data tidak dapat dihapus."
  Tombol hapus disabled.
- Rute yang sudah dipakai pada ritase:
  "Rute ini dipakai pada {N} ritase. Data tidak dapat dihapus."
  Tombol hapus disabled.

Tombol footer: "Batal" (sekunder) dan "Hapus" (merah/danger).
Wajib ada pola konfirmasi ganda: tombol Hapus baru aktif setelah user
mencentang checkbox "Saya memahami data akan dihapus permanen."
Saat proses: tombol jadi spinner "Menghapus...", lalu toast
"Data berhasil dihapus." dan baris hilang dari tabel tanpa reload halaman.

================================================================================
ATURAN UMUM UNTUK KETIGA MODAL
================================================================================

A. Struktur modal
   - Header: judul + tombol tutup (X)
   - Body: field form, bisa discroll bila tinggi melebihi layar
   - Footer sticky: tombol Batal dan tombol Simpan selalu terlihat
   - Lebar modal sedang (bukan full screen), di layar kecil jadi full width
   - Backdrop gelap semi transparan, konten di belakang tidak bisa diklik

B. Validasi
   - Validasi saat field kehilangan fokus dan saat submit
   - Error tampil di bawah field terkait, tidak memakai popup browser
   - Field wajib ditandai tanda bintang atau label "wajib"
   - Fokus otomatis ke field pertama yang error setelah submit gagal

C. Format angka
   - Semua nominal Rupiah tampil dengan pemisah titik: Rp 1.072.758.000
   - Input Rupiah memakai format saat mengetik, bukan setelah simpan
   - Persentase memakai koma desimal: 10,00%

D. Kondisi antrean/tombol
   - Tombol simpan disabled bila: form belum berubah (mode edit),
     ada field wajib kosong, atau proses sedang berjalan
   - Cegah klik ganda saat menyimpan

E. Aksesibilitas
   - Fokus terperangkap di dalam modal selama terbuka
   - Label setiap input terhubung ke field-nya
   - Tombol punya teks jelas, bukan hanya ikon
   - Status error dibacakan pembaca layar (aria-invalid, aria-describedby)

F. Keadaan khusus
   - Loading state saat modal dibuka dalam mode edit (ambil data)
   - Empty state bila data tidak ditemukan: "Data tidak ditemukan."
   - Bila sesi habis saat menyimpan: tampilkan pesan
     "Sesi Anda berakhir. Silakan masuk kembali." tanpa menutup form

G. Yang TIDAK perlu dibuat
   - Tidak perlu halaman baru, cukup modal di atas halaman tabel yang ada
   - Tidak perlu upload file atau foto
   - Tidak perlu field KIR, STNK, nomor rangka, atau nomor mesin
   - Tidak perlu field jabatan sebagai master terpisah
