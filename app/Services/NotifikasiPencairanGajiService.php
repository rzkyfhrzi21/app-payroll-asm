<?php

namespace App\Services;

use App\Mail\SlipGajiDicairkanMail;
use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ============================================================================
 * LAYANAN: NOTIFIKASI PENCAIRAN GAJI KE EMAIL & WHATSAPP SUPIR
 * ============================================================================
 *
 * Analogi Sederhana:
 * Ibarat "Petugas Pengumuman Gajian": begitu bendahara (Pimpinan) menekan tombol
 * Cairkan Dana, petugas ini langsung menghubungi setiap supir yang gajinya cair
 * melalui dua saluran sekaligus: email (SMTP) dan WhatsApp (Fonnte).
 *
 * Aturan Penting:
 * (1) Notifikasi hanya dikirim untuk baris `penggajian` yang benar-benar berubah
 *     menjadi `paid` pada aksi pencairan tersebut — bukan seluruh periode.
 * (2) Kegagalan email/WA TIDAK BOLEH membatalkan pencairan gaji (uang sudah
 *     dipindahkan). Semua kegagalan hanya dicatat di log.
 * (3) Bila kontak supir kosong, notifikasi saluran tersebut dilewati.
 * ============================================================================
 */
class NotifikasiPencairanGajiService
{
    public function __construct(
        private readonly PengirimWhatsAppService $layananWhatsApp,
    ) {}

    /**
     * Kirim notifikasi pencairan untuk baris penggajian yang baru dicairkan.
     *
     * @param  array<int, int>  $daftarIdPenggajian  ID baris penggajian yang dicairkan pada aksi ini.
     * @return array{email_terkirim: int, wa_terkirim: int, dilewati: int}
     */
    public function kirimUntukPenggajian(PeriodeGaji $periode, array $daftarIdPenggajian): array
    {
        $ringkasan = ['email_terkirim' => 0, 'wa_terkirim' => 0, 'dilewati' => 0];

        $idBersih = array_values(array_unique(array_filter(
            array_map('intval', $daftarIdPenggajian),
            fn (int $id): bool => $id > 0
        )));

        if ($idBersih === []) {
            return $ringkasan;
        }

        $daftarPenggajian = Penggajian::query()
            ->with(['karyawan.pengguna'])
            ->where('id_periode_gaji', $periode->id)
            ->whereIn('id', $idBersih)
            ->where('status', 'paid')
            ->get();

        foreach ($daftarPenggajian as $penggajian) {
            $pengguna = $penggajian->karyawan?->pengguna;

            // (1) Email via SMTP.
            $alamatEmail = trim((string) ($pengguna?->email ?? ''));

            if ($alamatEmail !== '') {
                try {
                    Mail::to($alamatEmail)->send(
                        SlipGajiDicairkanMail::dariPenggajian($penggajian, $periode)
                    );
                    $ringkasan['email_terkirim']++;
                } catch (\Throwable $galat) {
                    Log::warning('Notifikasi email pencairan gaji gagal dikirim.', [
                        'email' => $alamatEmail,
                        'id_penggajian' => $penggajian->id,
                        'pesan_galat' => $galat->getMessage(),
                    ]);
                }
            } else {
                $ringkasan['dilewati']++;
            }

            // (2) WhatsApp via Fonnte.
            $nomorTelepon = trim((string) ($pengguna?->nomor_telepon ?? ''));

            if ($nomorTelepon === '') {
                $ringkasan['dilewati']++;

                continue;
            }

            $pesan = $this->layananWhatsApp->susunPesanSlipGaji([
                'nama' => (string) ($penggajian->karyawan?->nama_lengkap ?? 'Supir'),
                'periode' => (string) $periode->kode,
                'gaji_bersih' => (int) $penggajian->gaji_bersih,
                'potongan_kasbon' => (int) $penggajian->potongan_kasbon,
                'tanggal_cair' => ($penggajian->dicairkan_pada ?? now())->translatedFormat('d F Y'),
                'nama_perusahaan' => (string) (\App\Models\DataPerusahaan::profilAktif()->nama_perusahaan ?? config('app.name')),
            ]);

            if ($this->layananWhatsApp->kirimPesan($nomorTelepon, $pesan)) {
                $ringkasan['wa_terkirim']++;
            }
        }

        return $ringkasan;
    }
}
