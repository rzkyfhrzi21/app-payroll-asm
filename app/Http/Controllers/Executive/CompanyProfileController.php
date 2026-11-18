<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Http\Requests\Executive\UpdateDataPerusahaanRequest;
use App\Models\DataPerusahaan;
use App\Services\StandarMediaGambarService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: KELOLA PROFIL & IDENTITAS PERUSAHAAN (COMPANY PROFILE CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Ruang Pengaturan Akta, Kop Surat, & Stempel Resmi Kantor".
 * Hanya akun Pimpinan/Direktur yang memiliki hak akses untuk mengelola data ini.
 * Informasi yang diatur di sini (seperti logo kantor, tanda tangan digital pimpinan,
 * nomor rekening resmi, dan alamat PT) akan otomatis disematkan pada setiap
 * cetakan lembar slip gaji supir dan laporan keuangan eksekutif.
 * ============================================================================
 */
class CompanyProfileController extends Controller
{
    // Menggunakan trait respon interaksi standar notifikasi toast
    use ResponInteraksiStandar;

    /**
     * Menampilkan Formulir Pengaturan Profil Perusahaan
     */
    public function edit(): View
    {
        return view('executive.company.index', [
            'perusahaan' => DataPerusahaan::profilAktif(),
        ]);
    }

    /**
     * Memperbarui Informasi Identitas Resmi Perusahaan
     * 
     * Alur Kerja Bernomor:
     * (1) Validasi isian formulir (nama PT, nama pimpinan, jabatan, rekening, alamat).
     * (2) Ambil atau siapkan baris data tunggal perusahaan (Singleton).
     * (3) Jika ada file logo baru diunggah, simpan format WebP dan hapus berkas logo lama.
     * (4) Jika ada file tanda tangan baru, simpan format WebP dan hapus berkas ttd lama.
     * (5) Simpan seluruh data ke database dan kirimkan notifikasi toast sukses.
     */
    public function update(
        UpdateDataPerusahaanRequest $permintaan,
        StandarMediaGambarService $layananMediaGambar,
    ): RedirectResponse|JsonResponse {
        $dataValid = $permintaan->validated();
        $perusahaan = DataPerusahaan::query()->orderBy('id')->first() ?? new DataPerusahaan();

        $atributSimpan = collect($dataValid)
            ->except(['logo_perusahaan', 'ttd_pemilik', 'hapus_logo', 'hapus_ttd'])
            ->all();

        if ($permintaan->hasFile('logo_perusahaan')) {
            $jalurLogoBaru = $layananMediaGambar->validasiDanSimpanWebp(
                $permintaan->file('logo_perusahaan'),
                'logo-perusahaan',
                'logo_perusahaan',
            );
            $this->hapusBerkasPublik($perusahaan->logo_perusahaan);
            $atributSimpan['logo_perusahaan'] = $jalurLogoBaru;
        } elseif ($permintaan->boolean('hapus_logo')) {
            $this->hapusBerkasPublik($perusahaan->logo_perusahaan);
            $atributSimpan['logo_perusahaan'] = null;
        }

        if ($permintaan->hasFile('ttd_pemilik')) {
            $jalurTtdBaru = $layananMediaGambar->validasiDanSimpanWebp(
                $permintaan->file('ttd_pemilik'),
                'ttd-pemilik',
                'ttd_pemilik',
            );
            $this->hapusBerkasPublik($perusahaan->ttd_pemilik);
            $atributSimpan['ttd_pemilik'] = $jalurTtdBaru;
        } elseif ($permintaan->boolean('hapus_ttd')) {
            $this->hapusBerkasPublik($perusahaan->ttd_pemilik);
            $atributSimpan['ttd_pemilik'] = null;
        }

        $perusahaan->fill($atributSimpan);
        $perusahaan->save();
        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'executive.company.edit',
            pesan: "Profil perusahaan '{$perusahaan->nama_perusahaan}' berhasil diperbarui.",
            judul: 'Profil Perusahaan Diperbarui',
            aksi: 'UPDATE_DATA_PERUSAHAAN',
            dataTambahan: [
                'id' => $perusahaan->id,
                'nama_perusahaan' => $perusahaan->nama_perusahaan,
                'logo_perusahaan' => $perusahaan->logo_perusahaan,
                'ttd_pemilik' => $perusahaan->ttd_pemilik,
            ],
        );
    }

    private function hapusBerkasPublik(?string $jalurPublik): void
    {
        if ($jalurPublik === null || trim($jalurPublik) === '') {
            return;
        }

        $jalurRelatif = ltrim((string) preg_replace('#^/storage/#', '', $jalurPublik), '/');

        if ($jalurRelatif !== '' && Storage::disk('public')->exists($jalurRelatif)) {
            Storage::disk('public')->delete($jalurRelatif);
        }
    }
}
