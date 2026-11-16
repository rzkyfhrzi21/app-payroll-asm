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

class CompanyProfileController extends Controller
{
    use ResponInteraksiStandar;

    public function edit(): View
    {
        return view('executive.company.index', [
            'perusahaan' => DataPerusahaan::profilAktif(),
        ]);
    }

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
