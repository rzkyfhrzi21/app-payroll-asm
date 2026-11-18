<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * LAYANAN: PENGIRIM WHATSAPP GATEWAY FONNTE (FONNTE WHATSAPP SERVICE)
 * ============================================================================
 *
 * Analogi Sederhana:
 * File ini ibarat "Petugas Kurir WhatsApp" di kantor HRD. Saat gaji supir dicairkan,
 * petugas ini menulis pesan singkat berisi periode, nominal gaji bersih, dan
 * nominal yang sudah ditransfer, lalu mengirimkannya ke nomor HP supir lewat
 * layanan pihak ketiga Fonnte (https://fonnte.com).
 *
 * Cara Kerja:
 * (1) Token dibaca dari config('services.fonnte.token') alias FONNTE_TOKEN di .env.
 *     Jika token kosong atau FONNTE_AKTIF=false, layanan langsung berhenti tanpa
 *     error sehingga lingkungan lokal & pengujian tidak terganggu.
 * (2) Nomor telepon dinormalisasi ke format internasional (+62...) oleh
 *     FormatNomorTelepon, lalu Fonnte menerima angka tanpa tanda "+".
 * (3) Kesalahan jaringan/gateway TIDAK BOLEH menggagalkan proses pencairan gaji:
 *     seluruh kegagalan dicatat ke log, bukan dilempar sebagai exception.
 * ============================================================================
 */
class PengirimWhatsAppService
{
    /**
     * Apakah pengiriman WhatsApp siap dipakai (token terisi & fitur aktif)?
     */
    public function siapKirim(): bool
    {
        return (bool) config('services.fonnte.aktif', true)
            && trim((string) config('services.fonnte.token', '')) !== '';
    }

    /**
     * Kirim satu pesan teks WhatsApp ke satu nomor.
     *
     * @return bool true bila gateway menerima pesan, false bila dilewati/gagal.
     */
    public function kirimPesan(?string $nomorTelepon, string $pesan): bool
    {
        if (! $this->siapKirim()) {
            return false;
        }

        $tujuan = $this->nomorUntukFonnte($nomorTelepon);

        if ($tujuan === null) {
            Log::warning('Notifikasi WhatsApp dilewati: nomor telepon supir tidak valid.', [
                'nomor_telepon' => $nomorTelepon,
            ]);

            return false;
        }

        try {
            $respons = Http::withHeaders([
                'Authorization' => (string) config('services.fonnte.token'),
            ])
                ->asForm()
                ->timeout((int) config('services.fonnte.timeout', 15))
                ->post((string) config('services.fonnte.url'), [
                    'target' => $tujuan,
                    'message' => $pesan,
                    'countryCode' => '62',
                ]);
        } catch (\Throwable $galat) {
            Log::warning('Notifikasi WhatsApp gagal dikirim (koneksi).', [
                'nomor' => $tujuan,
                'pesan_galat' => $galat->getMessage(),
            ]);

            return false;
        }

        // Fonnte membalas JSON dengan status=true bila pesan masuk antrean device.
        if (! $respons->successful() || $respons->json('status') === false) {
            Log::warning('Notifikasi WhatsApp ditolak gateway Fonnte.', [
                'nomor' => $tujuan,
                'kode_http' => $respons->status(),
                'balasan' => $respons->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Ubah nomor tersimpan di database (+62 812-... ) menjadi "62812..." untuk Fonnte.
     */
    public function nomorUntukFonnte(?string $nomorTelepon): ?string
    {
        $standar = \App\Support\FormatNomorTelepon::keStandar($nomorTelepon);

        if ($standar === null) {
            return null;
        }

        $digit = preg_replace('/\D/', '', $standar) ?? '';

        return $digit === '' ? null : $digit;
    }

    /**
     * Susun isi pesan slip gaji supir yang siap kirim WhatsApp.
     *
     * @param  array{nama: string, periode: string, gaji_bersih: int, potongan_kasbon: int, tanggal_cair: string, nama_perusahaan: string}  $data
     */
    public function susunPesanSlipGaji(array $data): string
    {
        $baris = [
            '*SLIP GAJI SUDAH DICAIRKAN*',
            $data['nama_perusahaan'],
            '',
            'Halo '.$data['nama'].',',
            'Gaji Anda untuk periode *'.$data['periode'].'* sudah dicairkan pada '.$data['tanggal_cair'].'.',
            '',
            'Gaji Bersih: *'.$this->formatRupiah($data['gaji_bersih']).'*',
        ];

        if (($data['potongan_kasbon'] ?? 0) > 0) {
            $baris[] = 'Potongan kasbon: '.$this->formatRupiah($data['potongan_kasbon']);
        }

        $baris[] = '';
        $baris[] = 'Silakan cek slip gaji lengkap di portal supir: '.url('/my/payroll');
        $baris[] = '';
        $baris[] = 'Pesan otomatis sistem penggajian, mohon tidak membalas.';

        return implode("\n", $baris);
    }

    private function formatRupiah(int $nominal): string
    {
        return 'Rp '.number_format($nominal, 0, ',', '.');
    }
}
