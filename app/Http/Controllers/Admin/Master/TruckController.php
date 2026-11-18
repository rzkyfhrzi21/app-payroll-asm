<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Concerns\MengaturPaginasiTabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrukRequest;
use App\Http\Requests\UpdateTrukRequest;
use App\Models\JenisTruk;
use App\Models\Truk;
use App\Services\PayrollDataService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: MASTER ARMADA TRUK & JENIS TRUK (TRUCK MASTER CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Buku Inventaris Bengkel & Garasi Armada Truk Batubara".
 * Di sinilah kantor mendata seluruh kendaraan fisik milik perusahaan sendiri:
 * pelat nomor polisinya, merek kendaraannya, supir tetap yang memegang kunci,
 * serta kategori jenis truknya (misal: Tronton 10 Roda atau Fuso).
 * 
 * Pola Modal Bersarang:
 * Pengelolaan kategori "Jenis Truk" (tambah, edit, hapus jenis truk) dikelola
 * langsung dari dalam jendela popup (modal di atas modal) pada halaman ini,
 * tanpa perlu berpindah ke halaman lain.
 * ============================================================================
 */
class TruckController extends Controller
{
    // Menggunakan trait pembantu paginasi tabel dan respon notifikasi standar
    use MengaturPaginasiTabel, ResponInteraksiStandar;

    /**
     * Menampilkan Daftar Master Unit Armada Truk
     * 
     * Alur Kerja Bernomor:
     * (1) Baca kata kunci pencarian, filter status unit, dan filter penugasan supir.
     * (2) Ambil daftar unit truk terpaginasi dari database.
     * (3) Ambil diagram komposisi donat dan ringkasan unit aktif/menganggur.
     * (4) Buka tampilan 'admin.master.trucks.index'.
     */
    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $perHalaman = $this->ambilJumlahBarisPerHalaman($request);
        $filter = [
            'cari'      => $this->ambilKataKunciPencarian($request),
            'status'    => $this->ambilFilterTeks($request, 'status'),
            'penugasan' => $this->ambilFilterTeks($request, 'penugasan'),
            'jenis'     => $this->ambilFilterTeks($request, 'jenis'),
        ];

        return view('admin.master.trucks.index', [
            'trucks'           => $layananDataPayroll->dataTrukMasterTerpaginasi($filter, $perHalaman),
            'summary'          => $layananDataPayroll->dataTrukMaster()['ringkasan'],
            'truckComposition' => $layananDataPayroll->komposisiTruk(),
            'drivers'          => $layananDataPayroll->supirUntukPilihanArmada(),
            'jenisTruk'        => $layananDataPayroll->daftarJenisTruk(),
            'jenisTrukSemua'   => $this->dataJenisTruk(),
            'filter'           => $filter,
            'perPage'          => $perHalaman,
        ]);
    }

    /**
     * Data jenis truk beserta hitungan unit terpakai & menganggur.
     */
    private function dataJenisTruk()
    {
        return JenisTruk::query()
            ->withCount([
                'truk',
                'truk as truk_terpakai_count' => fn ($kueri) => $kueri->whereNotNull('id_karyawan'),
                'truk as truk_menganggur_count' => fn ($kueri) => $kueri->whereNull('id_karyawan'),
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function simpanJenis(Request $request): RedirectResponse|JsonResponse
    {
        $jenisBaru = JenisTruk::create($this->validasiJenis($request));

        return $this->responSuksesStandar(
            permintaan: $request,
            ruteTujuan: 'admin.master.trucks.index',
            pesan: "Jenis truk '{$jenisBaru->nama}' ({$jenisBaru->merek_model}) berhasil disimpan.",
            judul: 'Jenis Truk Ditambahkan',
            aksi: 'CREATE_JENIS_TRUK',
            dataTambahan: [
                'id' => $jenisBaru->id,
                'nama' => $jenisBaru->nama,
            ],
        );
    }

    public function perbaruiJenis(Request $request, JenisTruk $jenisTruk): RedirectResponse|JsonResponse
    {
        $jenisTruk->update($this->validasiJenis($request, $jenisTruk->id));

        return $this->responSuksesStandar(
            permintaan: $request,
            ruteTujuan: 'admin.master.trucks.index',
            pesan: "Jenis truk '{$jenisTruk->nama}' ({$jenisTruk->merek_model}) berhasil diperbarui.",
            judul: 'Jenis Truk Diperbarui',
            aksi: 'UPDATE_JENIS_TRUK',
            dataTambahan: [
                'id' => $jenisTruk->id,
                'nama' => $jenisTruk->nama,
            ],
        );
    }

    /**
     * Jenis yang masih dipakai unit tidak boleh dihapus.
     */
    public function hapusJenis(Request $request, JenisTruk $jenisTruk): RedirectResponse|JsonResponse
    {
        $jumlahUnit = $jenisTruk->truk()->count();

        if ($jumlahUnit > 0) {
            return $this->responGalatStandar(
                permintaan: $request,
                ruteTujuan: 'admin.master.trucks.index',
                pesan: "Jenis '{$jenisTruk->nama}' masih dipakai oleh {$jumlahUnit} unit armada. Data tidak dapat dihapus.",
                judul: 'Penghapusan Jenis Truk Ditolak',
                aksi: 'DELETE_JENIS_TRUK_LOCKED',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $namaJenis = $jenisTruk->nama;
        $jenisTruk->delete();

        return $this->responSuksesStandar(
            permintaan: $request,
            ruteTujuan: 'admin.master.trucks.index',
            pesan: "Jenis truk '{$namaJenis}' berhasil dihapus dari database.",
            judul: 'Jenis Truk Dihapus',
            aksi: 'DELETE_JENIS_TRUK',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validasiJenis(Request $request, ?int $abaikanId = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:jenis_truk,nama'.($abaikanId ? ','.$abaikanId : '')],
            'merek_model' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [
            'nama.required' => 'Nama jenis wajib diisi.',
            'nama.unique' => 'Nama jenis sudah digunakan.',
            'merek_model.required' => 'Merek & model wajib diisi.',
        ]);
    }

    public function store(
        StoreTrukRequest $permintaan,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|JsonResponse {
        $data = $permintaan->validated();
        if ($permintaan->hasFile('foto_unit')) {
            $data['foto_unit'] = $layananMediaGambar->validasiDanSimpanWebp(
                $permintaan->file('foto_unit'),
                'foto-armada',
                'foto_unit'
            );
        }

        $trukBaru = Truk::create($data);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.trucks.index',
            pesan: "Unit armada baru '{$trukBaru->nomor_polisi}' ({$trukBaru->merek_model}) berhasil disimpan ke dalam database.",
            judul: 'Unit Armada Ditambahkan',
            aksi: 'CREATE_TRUK',
            dataTambahan: [
                'id' => $trukBaru->id,
                'nomor_polisi' => $trukBaru->nomor_polisi,
            ],
        );
    }

    public function update(
        UpdateTrukRequest $permintaan,
        Truk $truck,
        \App\Services\StandarMediaGambarService $layananMediaGambar
    ): RedirectResponse|JsonResponse {
        $data = $permintaan->validated();
        if ($permintaan->hasFile('foto_unit')) {
            $data['foto_unit'] = $layananMediaGambar->validasiDanSimpanWebp(
                $permintaan->file('foto_unit'),
                'foto-armada',
                'foto_unit'
            );
        }

        $truck->update($data);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.trucks.index',
            pesan: "Data unit armada '{$truck->nomor_polisi}' ({$truck->merek_model}) berhasil diperbarui.",
            judul: 'Unit Armada Diperbarui',
            aksi: 'UPDATE_TRUK',
            dataTambahan: [
                'id' => $truck->id,
                'nomor_polisi' => $truck->nomor_polisi,
            ],
        );
    }

    /**
     * Mengubah status aktif/nonaktif unit armada.
     */
    public function toggleStatus(Request $permintaan, Truk $truck): RedirectResponse|JsonResponse
    {
        $statusBaru = $truck->status === 'aktif' ? 'nonaktif' : 'aktif';
        $truck->update(['status' => $statusBaru]);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.trucks.index',
            pesan: "Status unit '{$truck->nomor_polisi}' diubah menjadi ".ucfirst($statusBaru).'.',
            judul: 'Status Unit Diperbarui',
            aksi: 'STATUS_TRUK',
            dataTambahan: [
                'id' => $truck->id,
                'status' => $statusBaru,
            ],
        );
    }

    /**
     * Unit yang sudah dipakai ritase/penggajian tidak boleh dihapus.
     */
    /**
     * Mengubah status banyak unit sekaligus dari checkbox tabel.
     */
    public function statusMassal(Request $permintaan): RedirectResponse|JsonResponse
    {
        $data = $permintaan->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:truk,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(Truk::daftarStatus()))],
        ]);

        $jumlahBaris = Truk::query()->whereIn('id', $data['ids'])->update(['status' => $data['status']]);
        $labelStatus = Truk::daftarStatus()[$data['status']] ?? $data['status'];

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.trucks.index',
            pesan: "Status {$jumlahBaris} unit armada berhasil diubah menjadi {$labelStatus}.",
            judul: 'Status Armada Massal Diperbarui',
            aksi: 'BULK_STATUS_TRUK',
            dataTambahan: ['jumlah' => $jumlahBaris, 'status' => $data['status']],
        );
    }

    public function destroy(Request $permintaan, Truk $truck, PayrollDataService $layananDataPayroll): RedirectResponse|JsonResponse
    {
        $ketergantungan = $layananDataPayroll->periksaKetergantungan('truk', $truck->id);

        if (array_sum($ketergantungan) > 0) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.master.trucks.index',
                pesan: "Unit '{$truck->nomor_polisi}' dipakai pada {$ketergantungan['ritase']} ritase dan {$ketergantungan['penggajian']} slip gaji. Data tidak dapat dihapus.",
                judul: 'Penghapusan Unit Ditolak',
                aksi: 'DELETE_TRUK_LOCKED',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $nopolDihapus = $truck->nomor_polisi;
        $truck->delete();

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.trucks.index',
            pesan: "Data unit armada '{$nopolDihapus}' berhasil dihapus dari database.",
            judul: 'Unit Armada Dihapus',
            aksi: 'DELETE_TRUK',
            dataTambahan: ['nomor_polisi' => $nopolDihapus],
        );
    }
}
