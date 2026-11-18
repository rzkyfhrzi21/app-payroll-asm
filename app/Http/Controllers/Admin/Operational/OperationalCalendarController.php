<?php

namespace App\Http\Controllers\Admin\Operational;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: KALENDER JADWAL OPERASIONAL (OPERATIONAL CALENDAR CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kalender Dinding Jadwal Keberangkatan Truk di Kantor Tambang".
 * Halaman ini memudahkan staf admin melihat sebaran hari kerja dalam satu bulan:
 * hari mana yang sibuk pengiriman batubara, tanggal berapa ada jadwal servis truk,
 * dan kapan tanggal cut-off tutup buku gaji bulanan.
 * ============================================================================
 */
class OperationalCalendarController extends Controller
{
    /**
     * Menampilkan Tampilan Kalender Bulanan
     */
    public function index(): View
    {
        return view('admin.operational.calendar.index', [
            'days'   => DemoData::calendarDays(),
            'period' => DemoData::period(),
        ]);
    }

    /**
     * Menyimpan Catatan Agenda Baru di Kalender
     */
    public function store(Request $request): RedirectResponse
    {
        return back()->with('status', 'Simulasi tambah jadwal operasional. Belum ada data yang ditulis ke database.');
    }

    /**
     * Memvalidasi Seluruh Data Ritase pada Satu Tanggal Tertentu
     */
    public function validate(Request $request, string $date): RedirectResponse
    {
        return back()->with('status', "Simulasi validasi data ritase tanggal {$date}.");
    }

    /**
     * Mengunci Tanggal agar Tidak Ada Lagi Surat Jalan yang Diedit
     */
    public function lock(Request $request, string $date): RedirectResponse
    {
        return back()->with('status', "Simulasi kunci data ritase tanggal {$date}.");
    }
}
