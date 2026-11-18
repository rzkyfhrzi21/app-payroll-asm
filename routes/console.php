<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Mail;

Artisan::command('kirim:uji-email {tujuan? : Alamat email penerima uji coba}', function (?string $tujuan = null) {
    $mailer = config('mail.default');
    $host = config('mail.mailers.smtp.host');
    $port = config('mail.mailers.smtp.port');
    $username = (string) config('mail.mailers.smtp.username', '');
    $password = (string) config('mail.mailers.smtp.password', '');
    $fromAddress = config('mail.from.address');
    $fromName = config('mail.from.name');

    $this->info('====================================================');
    $this->info('   UJI COBA PENGIRIMAN EMAIL SMTP GMAIL            ');
    $this->info('====================================================');
    $this->line("• Mailer Default  : <comment>{$mailer}</comment>");
    $this->line("• Host SMTP       : <comment>{$host}:{$port}</comment>");
    $this->line("• Email Pengirim  : <comment>".($username !== '' ? $username : '(kosong)')."</comment>");
    $this->line("• Alamat Dari     : <comment>{$fromName} <{$fromAddress}></comment>");
    $this->line('• Status Sandi    : '.($password !== '' ? '<info>Terisi ('.strlen($password).' karakter)</info>' : '<error>KOSONG / BELUM DIISI</error>'));
    $this->newLine();

    if (trim($username) === '' || trim($password) === '' || str_contains($username, 'example.com') || str_contains($username, 'akunpengirim')) {
        $this->error('❌ Email pengirim atau Sandi Aplikasi Google belum diisi di file .env.');
        $this->newLine();
        $this->line('👉 Langkah Mudah Setup Gmail SMTP:');
        $this->line('1. Buka Akun Google Anda: https://myaccount.google.com/security');
        $this->line('2. Pastikan "Verifikasi 2 Langkah" (2-Step Verification) sudah AKTIF.');
        $this->line('3. Buka menu "Sandi Aplikasi" (App Passwords) atau cari "App Passwords" di pencarian Akun Google.');
        $this->line('4. Buat sandi aplikasi baru dengan nama "Payroll ASM", Google akan memberi 16 huruf.');
        $this->line('5. Buka file .env dan masukkan:');
        $this->line('   MAIL_MAILER=smtp');
        $this->line('   MAIL_HOST=smtp.gmail.com');
        $this->line('   MAIL_PORT=587');
        $this->line('   MAIL_SCHEME=null');
        $this->line('   MAIL_USERNAME="email_anda@gmail.com"');
        $this->line('   MAIL_PASSWORD="xxxx xxxx xxxx xxxx"');
        $this->line('   MAIL_FROM_ADDRESS="${MAIL_USERNAME}"');
        $this->line('6. Jalankan kembali: php artisan kirim:uji-email '.$tujuan);
        return 1;
    }

    $target = $tujuan ?: $username;
    $this->info("Sedang menghubungkan ke {$host}:{$port} dan mengirim email ke: {$target}...");

    try {
        Mail::raw(
            "Halo,\n\nIni adalah email uji coba dari Sistem Penggajian Supir Logistik Batubara (Payroll ASM).\nKoneksi SMTP Gmail berhasil terhubung dengan sempurna pada ".now()->translatedFormat('d F Y H:i:s')." WIB.\n\nSistem siap mengirimkan slip gaji digital ke seluruh supir.\n\nSalam,\n".config('app.name'),
            function ($message) use ($target) {
                $message->to($target)
                    ->subject('[UJI COBA] Koneksi SMTP Gmail - '.config('app.name'));
            }
        );

        $this->info("✅ BERHASIL! Email uji coba berhasil terkirim ke {$target}.");
        $this->line('   Silakan periksa kotak masuk (Inbox) atau folder Spam pada akun email penerima.');
        return 0;
    } catch (\Throwable $e) {
        $this->error('❌ GAGAL MENGIRIM EMAIL: '.$e->getMessage());
        $this->newLine();
        $this->line('🔍 Diagnosa Masalah:');
        if (str_contains($e->getMessage(), '535') || str_contains($e->getMessage(), 'BadCredentials')) {
            $this->warn('• Kode 535 (Bad Credentials): Autentikasi ditolak Google.');
            $this->warn('  Pastikan Anda menggunakan 16 karakter "Sandi Aplikasi / App Password", BUKAN kata sandi login Gmail biasa.');
        } elseif (str_contains($e->getMessage(), 'Connection') || str_contains($e->getMessage(), 'timed out')) {
            $this->warn('• Connection Timeout: Port 587 mungkin diblokir oleh provider internet.');
            $this->warn('  Solusi: Coba ubah ke Port 465 dengan SSL di .env:');
            $this->warn('  MAIL_PORT=465');
            $this->warn('  MAIL_SCHEME=smtps');
        }
        return 1;
    }
})->purpose('Uji koneksi dan pengiriman email SMTP Gmail');

Artisan::command('kirim:uji-wa {nomor : Nomor WhatsApp tujuan, contoh: 081289301123}', function (string $nomor) {
    $layanan = app(\App\Services\PengirimWhatsAppService::class);

    $this->info('====================================================');
    $this->info('   UJI COBA GATEWAY WHATSAPP FONNTE                ');
    $this->info('====================================================');
    $this->line('• Endpoint URL : <comment>'.config('services.fonnte.url').'</comment>');
    $this->line('• Status Token : '.($layanan->siapKirim() ? '<info>Terisi</info>' : '<error>KOSONG</error>'));
    $this->newLine();

    if (! $layanan->siapKirim()) {
        $this->error('❌ Token Fonnte belum diisi di .env (FONNTE_TOKEN).');
        $this->line('   Dapatkan token di https://fonnte.com -> Device -> Token.');
        return 1;
    }

    $this->info("Mengirim pesan WhatsApp uji coba ke: {$nomor}...");
    $pesan = "Halo,\n\nIni adalah pesan uji coba dari Sistem Penggajian Supir Logistik Batubara (Payroll ASM).\nIntegrasi WhatsApp Gateway Fonnte aktif pada ".now()->translatedFormat('d F Y H:i:s')." WIB.";

    $hasil = $layanan->kirimPesan($nomor, $pesan);

    if ($hasil) {
        $this->info("✅ BERHASIL! Pesan berhasil diterima oleh Gateway Fonnte dan diteruskan ke nomor {$nomor}.");
        return 0;
    }

    $this->error("❌ Gagal mengirim pesan ke gateway Fonnte. Periksa storage/logs/laravel.log untuk detailnya.");
    return 1;
})->purpose('Uji koneksi dan pengiriman pesan WhatsApp via Fonnte');
