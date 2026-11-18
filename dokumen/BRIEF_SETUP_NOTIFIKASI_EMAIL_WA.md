# Setup Notifikasi Gaji: Gmail SMTP & WhatsApp Fonnte

Panduan ini menjelaskan cara mengaktifkan notifikasi otomatis ke supir setiap kali
Pimpinan menekan **Cairkan Dana** di `/executive/payroll`. Setiap supir yang baris
gajinya berubah menjadi `paid` akan menerima **email slip gaji** (SMTP) dan
**pesan WhatsApp** (gateway Fonnte).

---

## 1. Ringkasan Alur

1. Pimpinan meng-ACC gaji supir (`approved`).
2. Pimpinan menekan **Cairkan Dana** → baris `penggajian` menjadi `paid`, angsuran kasbon ditandai `terpotong`.
3. `NotifikasiPencairanGajiService` mengirim, **per supir yang dicairkan**:
   - Email `SlipGajiDicairkanMail` ke `users.email` via SMTP.
   - Pesan WhatsApp ke `users.nomor_telepon` via Fonnte.
4. Kegagalan kirim **tidak** membatalkan pencairan — hanya dicatat di `storage/logs/laravel.log`.
5. Respons sukses menampilkan jumlah kirim: `Notifikasi terkirim ke N email dan M WhatsApp supir.`

Kontak yang dipakai adalah akun login supir (`karyawan.id_pengguna` → `users.email`
dan `users.nomor_telepon`). Pastikan keduanya terisi di
`/admin/master/employees` (Kelola Akun Pengguna) atau di profil supir.

---

## 2. Gmail SMTP — Apakah bagus pakai SMTP biasa?

**Untuk skala satu perusahaan dengan puluhan supir: ya, cukup.** Gmail SMTP memakai
infrastruktur Google, TLS, dan gratis. Batasnya:

| Aspek | Gmail SMTP (gratis) | Gmail Workspace | Alternatif (Brevo/Mailgun/Resend) |
|:---|:---|:---|:---|
| Kuota kirim | ± 500 email/hari | ± 2.000/hari | 300–3.000/bulan gratis |
| Pengirim tampil | alamat `@gmail.com` Anda | domain perusahaan | domain perusahaan |
| SPF/DKIM | milik Google | perlu setup DNS | perlu setup DNS |
| Risiko masuk spam | agak tinggi untuk penerima massal | rendah | paling rendah |
| Biaya | gratis | berbayar per user | gratis–murah |

Kebutuhan proyek ini ± 40 email/bulan (1 email per supir per periode), jauh di bawah
kuota. Jadi **SMTP Gmail memadai**. Pertimbangkan penyedia transaksional bila nanti:

- alamat pengirim harus `no-reply@perusahaan.co.id` (bukan gmail.com), atau
- volume melewati kuota harian, atau
- banyak email masuk folder spam (penerima Gmail lain biasanya tetap menerima).

### 2.1 Yang harus disiapkan di akun Gmail

1. **Akun Gmail pengirim** khusus sistem, mis. `payroll.perusahaan@gmail.com`.
   Jangan pakai akun pribadi admin.
2. **Verifikasi 2 Langkah (2-Step Verification) wajib AKTIF.**
   Google hanya mengizinkan App Password bila 2FA aktif.
   Buka <https://myaccount.google.com/security> → *2-Step Verification* → aktifkan.
3. **App Password (16 karakter)**:
   <https://myaccount.google.com/apppasswords> → beri nama `Payroll ASM` →
   *Create*. Salin 16 karakter yang muncul (ditampilkan sekali, dengan spasi —
   spasi boleh dihapus).
   - **Jangan** memakai kata sandi login Gmail biasa: diblokir sejak 2022 untuk SMTP.
   - **Jangan** memakai "Less secure app access": sudah dihapus Google.
4. **Cek kebijakan pembatasan**: bila akun baru, buka Gmail sekali di browser dan
   selesaikan verifikasi nomor HP agar pengiriman SMTP tidak diblokir.

### 2.2 Isi `.env`

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=tls
MAIL_USERNAME="payroll.perusahaan@gmail.com"
MAIL_PASSWORD="abcdefghijklmnop"
MAIL_FROM_ADDRESS="${MAIL_USERNAME}"
MAIL_FROM_NAME="Payroll ASM"
```

Catatan:

- Port `587` + `MAIL_SCHEME=tls` (STARTTLS) adalah pilihan teraman dan paling
  kompatibel. Alternatif: port `465` dengan `MAIL_SCHEME=smtps`.
- `MAIL_FROM_ADDRESS` **harus sama** dengan `MAIL_USERNAME`. Gmail menolak
  (`550-5.7.1`) bila alamat pengirim berbeda dari akun yang login.
- Nama pengirim bebas (tampil di kotak masuk penerima).

### 2.3 Uji kirim

```cmd
php artisan config:clear
php artisan tinker
```

```php
Mail::raw('Uji SMTP Gmail', fn ($m) => $m->to('alamat-uji@example.com')->subject('Uji Payroll'));
```

Bila muncul `Expected response code "250" but got code "535"` → App Password salah
atau 2FA belum aktif. Bila `Connection could not be established` → port terblokir
jaringan kantor; coba port `465` + `MAIL_SCHEME=smtps`.

**Development:** biarkan `MAIL_MAILER=log`. Seluruh isi email masuk ke
`storage/logs/laravel.log` sehingga tidak perlu kredensial nyata saat menyusun
tampilan.

---

## 3. WhatsApp Fonnte

Fonnte (<https://fonnte.com>) adalah gateway WhatsApp tidak resmi (WhatsApp Web
automation) yang populer di Indonesia. Cukup scan QR dari HP, tanpa Meta Business API.

### 3.1 Langkah pendaftaran

1. Daftar di <https://fonnte.com> → verifikasi email.
2. **Device** → *Add Device* → beri nama (mis. `Payroll ASM`) → **Scan QR**
   dengan WhatsApp di HP nomor pengirim (gunakan nomor khusus perusahaan,
   karena semua notifikasi terkirim dari nomor ini).
3. Tunggu status device **Connected**.
4. **Token** device ditampilkan di halaman device (atau menu *Profile*).
   Salin token tersebut.
5. **Isi saldo**: Fonnte memakai model prabayar per pesan. Tanpa saldo, API
   membalas `status: false` dengan alasan saldo tidak cukup.

### 3.2 Isi `.env`

```env
FONNTE_TOKEN=xxxxxxxxxxxxxxxxxxxx
FONNTE_URL=https://api.fonnte.com/send
FONNTE_AKTIF=true
FONNTE_TIMEOUT=15
```

- Token kosong / `FONNTE_AKTIF=false` → fitur WA **dilewati diam-diam**, pencairan gaji
  tetap sukses. Ini kondisi default di `.env.example`.
- Nomor tujuan otomatis dinormalisasi ke `62...` (tanpa `+`), jadi data supir boleh
  tersimpan sebagai `0812...`, `+62 812-...`, atau `62812...`.

### 3.3 Uji kirim

```cmd
php artisan tinker
```

```php
app(App\Services\PengirimWhatsAppService::class)->kirimPesan('081234567890', 'Uji notifikasi payroll');
```

Nilai balik `true` = Fonnte menerima pesan. Cek juga **Device → Message Log** di
dashboard Fonnte.

### 3.4 Hal yang perlu diketahui

- **Nomor HP harus terdaftar di WhatsApp**; nomor tanpa WA akan gagal.
- Fonnte adalah layanan pihak ketiga tidak resmi. Untuk kebutuhan korporat jangka
  panjang dengan SLA, pertimbangkan WhatsApp Cloud API resmi Meta.
- Jangan mengirim pengumuman massal mendadak dari nomor baru: risiko nomor
  diblokir WhatsApp. Kirim bertahap dan hanya transaksional (notifikasi gaji).
- Simpan token hanya di `.env`; jangan commit ke git (`.env` sudah di-.gitignore).

---

## 4. API Fonnte yang dipakai

```http
POST https://api.fonnte.com/send
Authorization: <FONNTE_TOKEN>
Content-Type: application/x-www-form-urlencoded

target=6281234567890&message=...&countryCode=62
```

Balasan sukses: `{"status":true,"detail":"success",...}`.
Balasan gagal: `{"status":false,"reason":"..."}` — dicatat ke log sebagai warning,
pencairan gaji tetap berjalan.

Dokumentasi resmi: <https://docs.fonnte.com/send-message>.

---

## 5. Berkas terlibat

| Berkas | Fungsi |
|:---|:---|
| `config/services.php` (`fonnte`) | Token, URL, timeout, dan saklar aktif WA |
| `app/Services/PengirimWhatsAppService.php` | Normalisasi nomor, penyusunan pesan, HTTP ke Fonnte |
| `app/Services/NotifikasiPencairanGajiService.php` | Orkestrasi kirim email + WA per supir yang dicairkan |
| `app/Mail/SlipGajiDicairkanMail.php` | Mailable slip gaji (data primitif, aman untuk queue) |
| `resources/views/emails/slip-gaji-dicairkan.blade.php` | Template HTML email |
| `app/Http/Controllers/Executive/PayrollReviewController.php` (`disburse`) | Titik pemicu setelah pencairan |
| `tests/Feature/Executive/NotifikasiPencairanGajiTest.php` | Uji email, WA, token kosong, dan kegagalan gateway |

---

## 6. Checklist sebelum go-live

- [ ] Akun Gmail pengirim dibuat, 2FA aktif, App Password disalin.
- [ ] `.env` produksi: `MAIL_MAILER=smtp`, kredensial Gmail terisi, `MAIL_FROM_ADDRESS` = `MAIL_USERNAME`.
- [ ] `php artisan config:clear` setelah mengubah `.env`.
- [ ] Device Fonnte tersambung, token terisi, saldo cukup.
- [ ] Nomor WhatsApp & email seluruh supir terisi di data karyawan/akun.
- [ ] Uji satu supir: cairkan satu baris gaji, cek email masuk (termasuk folder spam) dan WhatsApp.
- [ ] `php artisan test --filter=NotifikasiPencairanGajiTest` lulus.
