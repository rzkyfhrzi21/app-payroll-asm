<?php

namespace App\Http\Controllers\Admin\Operational;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationalCalendarController extends Controller
{
    public function index(): View
    {
        return view('admin.operational.calendar.index', [
            'days' => DemoData::calendarDays(),
            'period' => DemoData::period(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return back()->with('status', 'Simulasi tambah jadwal operasional. Belum ada data yang ditulis ke database.');
    }

    public function validate(Request $request, string $date): RedirectResponse
    {
        return back()->with('status', "Simulasi validasi data ritase tanggal {$date}.");
    }

    public function lock(Request $request, string $date): RedirectResponse
    {
        return back()->with('status', "Simulasi kunci data ritase tanggal {$date}.");
    }
}
