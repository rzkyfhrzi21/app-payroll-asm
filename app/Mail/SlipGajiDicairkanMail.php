<?php

namespace App\Mail;

use App\Models\Penggajian;
use App\Models\PeriodeGaji;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ============================================================================
 * MAIL: SLIP GAJI SUPIR SUDAH DICAIRKAN (PENCAIRAN GAJI)
 * ============================================================================
 *
 * Analogi Sederhana:
 * Ibarat "Surat pemberitahuan resmi" yang dimasukkan ke kotak surat elektronik
 * supir: memberi tahu bahwa gaji periode tertentu sudah ditransfer, lengkap dengan
 * rincian gaji bersih, potongan kasbon, dan tautan ke slip gaji di portal supir.
 *
 * Catatan teknis:
 * - Mailable ini sengaja memakai data primitif (array) agar aman diserialisasi
 *   ke queue (QUEUE_CONNECTION=database) tanpa memuat ulang relasi Eloquent.
 * ============================================================================
 */
class SlipGajiDicairkanMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{nama: string, nip: string, periode: string, rentang: string, gaji_bersih: int, potongan_kasbon: int, potongan_bpjs: int, rit: int, tonase: string, tanggal_cair: string, nama_perusahaan: string, bank: string, nomor_rekening: string}  $rincian
     */
    public function __construct(
        public array $rincian,
    ) {}

    /**
     * Susun mailable dari model Penggajian yang baru dicairkan.
     */
    public static function dariPenggajian(Penggajian $penggajian, PeriodeGaji $periode): self
    {
        $karyawan = $penggajian->karyawan;
        $perusahaan = \App\Models\DataPerusahaan::profilAktif();

        return new self([
            'nama' => (string) ($karyawan?->nama_lengkap ?? 'Supir'),
            'nip' => (string) ($karyawan?->nomor_induk_karyawan ?? '—'),
            'periode' => (string) $periode->kode,
            'rentang' => trim(($periode->tanggal_mulai?->format('d/m/Y') ?? '—').' s/d '.($periode->tanggal_selesai?->format('d/m/Y') ?? '—')),
            'gaji_bersih' => (int) $penggajian->gaji_bersih,
            'potongan_kasbon' => (int) $penggajian->potongan_kasbon,
            'potongan_bpjs' => (int) $penggajian->potongan_bpjs,
            'rit' => (int) $penggajian->total_rit,
            'tonase' => number_format(((int) $penggajian->total_tonase_kg) / 1000, 2, ',', '.'),
            'tanggal_cair' => ($penggajian->dicairkan_pada ?? now())->translatedFormat('d F Y H:i').' WIB',
            'nama_perusahaan' => (string) ($perusahaan->nama_perusahaan ?? config('app.name')),
            'bank' => (string) ($karyawan?->nama_bank ?? '—'),
            'nomor_rekening' => (string) ($karyawan?->nomor_rekening ?? '—'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Slip Gaji Periode '.$this->rincian['periode'].' Sudah Dicairkan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.slip-gaji-dicairkan',
            with: ['rincian' => $this->rincian],
        );
    }
}
