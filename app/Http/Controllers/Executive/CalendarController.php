<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: KALENDER KERJA EKSEKUTIF (EXECUTIVE CALENDAR CONTROLLER)
 * ============================================================================
 * 
 * Analogi Sederhana:
 * File ini ibarat "Kalender Meja Kerja Direktur / Pimpinan Perusahaan".
 * Di sini pimpinan dapat memantau tanggal-tanggal penting operasional tambang:
 * hari-hari puncak pengiriman batubara, tanggal tutup buku (cut-off) penggajian,
 * serta jadwal tanggal pembayaran transfer gaji supir.
 * ============================================================================
 */
class CalendarController extends Controller
{
    /**
     * Menampilkan Kalender Agenda Eksekutif
     */
    public function index(): View
    {
        return view('executive.calendar', [
            'period'  => DemoData::period(),
            'days'    => DemoData::calendarDays(),
            'summary' => DemoData::executiveSummary(),
        ]);
    }
}
