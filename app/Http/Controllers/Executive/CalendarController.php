<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        return view('executive.calendar', [
            'period' => DemoData::period(),
            'days' => DemoData::calendarDays(),
            'summary' => DemoData::executiveSummary(),
        ]);
    }
}
