<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Concerns\MengaturPaginasiTabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRuteRequest;
use App\Http\Requests\UpdateRuteRequest;
use App\Models\Rute;
use App\Services\PayrollDataService;
use App\Support\ResponInteraksiStandar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    use MengaturPaginasiTabel, ResponInteraksiStandar;

    public function index(Request $request, PayrollDataService $layananDataPayroll): View
    {
        $perHalaman = $this->ambilJumlahBarisPerHalaman($request);
        $filter = [
            'cari' => $this->ambilKataKunciPencarian($request),
            'status' => $this->ambilFilterTeks($request, 'status'),
            'material' => $this->ambilFilterTeks($request, 'material'),
        ];

        return view('admin.master.routes.index', [
            'routes' => $layananDataPayroll->daftarRuteTerpaginasi($filter, $perHalaman),
            'summary' => $layananDataPayroll->ringkasanRute(),
            'materialOptions' => $layananDataPayroll->pilihanMaterialRute(),
            'filter' => $filter,
            'perPage' => $perHalaman,
        ]);
    }

    public function store(StoreRuteRequest $permintaan): RedirectResponse|JsonResponse
    {
        $ruteBaru = Rute::create($permintaan->validated());
        $tarifFormatted = 'Rp '.number_format((int) $ruteBaru->tarif_per_ton, 0, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.routes.index',
            pesan: "Tarif rute '{$ruteBaru->kode}' ({$ruteBaru->material}, {$tarifFormatted}/Ton) berhasil disimpan ke dalam database.",
            judul: 'Tarif Rute Ditambahkan',
            aksi: 'CREATE_RUTE',
            dataTambahan: [
                'id' => $ruteBaru->id,
                'kode' => $ruteBaru->kode,
            ],
        );
    }

    public function update(UpdateRuteRequest $permintaan, Rute $route): RedirectResponse|JsonResponse
    {
        $route->update($permintaan->validated());
        $tarifFormatted = 'Rp '.number_format((int) $route->tarif_per_ton, 0, ',', '.');

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.routes.index',
            pesan: "Tarif rute '{$route->kode}' ({$route->material}, {$tarifFormatted}/Ton) berhasil diperbarui.",
            judul: 'Tarif Rute Diperbarui',
            aksi: 'UPDATE_RUTE',
            dataTambahan: [
                'id' => $route->id,
                'kode' => $route->kode,
            ],
        );
    }

    /**
     * Mengubah status aktif/nonaktif rute.
     */
    public function toggleStatus(Request $permintaan, Rute $route): RedirectResponse|JsonResponse
    {
        $statusBaru = $route->status === 'aktif' ? 'nonaktif' : 'aktif';
        $route->update(['status' => $statusBaru]);

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.routes.index',
            pesan: "Status rute '{$route->kode}' diubah menjadi ".ucfirst($statusBaru).'.',
            judul: 'Status Rute Diperbarui',
            aksi: 'STATUS_RUTE',
            dataTambahan: [
                'id' => $route->id,
                'status' => $statusBaru,
            ],
        );
    }

    /**
     * Rute yang sudah dipakai ritase tidak boleh dihapus.
     */
    /**
     * Mengubah status banyak rute sekaligus dari checkbox tabel.
     */
    public function statusMassal(Request $permintaan): RedirectResponse|JsonResponse
    {
        $data = $permintaan->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:rute,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(Rute::daftarStatus()))],
        ]);

        $jumlahBaris = Rute::query()->whereIn('id', $data['ids'])->update(['status' => $data['status']]);
        $labelStatus = Rute::daftarStatus()[$data['status']] ?? $data['status'];

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.routes.index',
            pesan: "Status {$jumlahBaris} rute berhasil diubah menjadi {$labelStatus}.",
            judul: 'Status Rute Massal Diperbarui',
            aksi: 'BULK_STATUS_RUTE',
            dataTambahan: ['jumlah' => $jumlahBaris, 'status' => $data['status']],
        );
    }

    public function destroy(Request $permintaan, Rute $route, PayrollDataService $layananDataPayroll): RedirectResponse|JsonResponse
    {
        $ketergantungan = $layananDataPayroll->periksaKetergantungan('rute', $route->id);

        if (array_sum($ketergantungan) > 0) {
            return $this->responGalatStandar(
                permintaan: $permintaan,
                ruteTujuan: 'admin.master.routes.index',
                pesan: "Rute '{$route->kode}' sudah dipakai pada {$ketergantungan['ritase']} ritase. Data tidak dapat dihapus.",
                judul: 'Penghapusan Rute Ditolak',
                aksi: 'DELETE_RUTE_LOCKED',
                statusKode: 'INTEGRITY_LOCKED',
            );
        }

        $kodeDihapus = $route->kode;
        $route->delete();

        return $this->responSuksesStandar(
            permintaan: $permintaan,
            ruteTujuan: 'admin.master.routes.index',
            pesan: "Tarif rute '{$kodeDihapus}' berhasil dihapus dari database.",
            judul: 'Tarif Rute Dihapus',
            aksi: 'DELETE_RUTE',
            dataTambahan: ['kode' => $kodeDihapus],
        );
    }
}
