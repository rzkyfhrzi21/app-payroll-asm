<?php

use App\Http\Controllers\Admin\InteraksiTabelController;
use App\Http\Controllers\Admin\Master\EmployeeController;
use App\Http\Controllers\Admin\Master\RouteController as MasterRouteController;
use App\Http\Controllers\Admin\Master\TruckController;
use App\Http\Controllers\Admin\Operational\DeliveryTripController;
use App\Http\Controllers\Admin\Operational\EmployeeFinanceController;
use App\Http\Controllers\Admin\Operational\OperationalCalendarController;
use App\Http\Controllers\Admin\Operational\TruckExpenseController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\LoanController as DriverLoanController;
use App\Http\Controllers\Driver\ProfileController as DriverProfileController;
use App\Http\Controllers\Driver\TripHistoryController as DriverTripHistoryController;
use App\Http\Controllers\Executive\CalendarController as ExecutiveCalendarController;
use App\Http\Controllers\Executive\CompanyProfileController;
use App\Http\Controllers\Executive\DashboardController as ExecutiveDashboardController;
use App\Http\Controllers\Executive\FinancialReportController;
use App\Http\Controllers\Executive\PayrollReviewController;
use App\Http\Controllers\Executive\TripMonitorController;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PencarianController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// Fallback penyajian media dari disk `public` bila symlink `public/storage`
// belum dibuat (mis. hosting tanpa akses `php artisan storage:link`).
Route::get('/storage/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('media.publik');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Workspace Admin / HRD  →  /admin/*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin', 'throttle:interaksi-admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    Route::post('/interaksi/tabel', InteraksiTabelController::class)->name('admin.interaksi.tabel');

    Route::get('/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index');
    Route::post('/payroll/periode-baru', [PayrollController::class, 'buatPeriodeBaru'])->name('admin.payroll.period.create');
    Route::post('/payroll/{period}/calculate', [PayrollController::class, 'calculate'])->name('admin.payroll.calculate');
    Route::patch('/payroll/{period}/submit', [PayrollController::class, 'submit'])->name('admin.payroll.submit');
    Route::post('/payroll/{period}/approval', [PayrollController::class, 'approval'])->name('admin.payroll.approval');
    Route::patch('/payroll/{period}/driver/{penggajian}', [PayrollController::class, 'updateDriver'])->name('admin.payroll.driver.update');
    Route::delete('/payroll/{period}/driver/{penggajian}', [PayrollController::class, 'destroyDriver'])->name('admin.payroll.driver.destroy');
    Route::post('/payroll/{period}/import', [PayrollController::class, 'import'])->name('admin.payroll.import');
    Route::get('/payroll/{period}/export', [PayrollController::class, 'export'])->name('admin.payroll.export');

    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/export', [ReportController::class, 'exportArsip'])->name('admin.reports.export');
    Route::post('/reports/migrasi/import', [ReportController::class, 'imporMigrasi'])->name('admin.reports.migrasi.import');
    Route::get('/reports/migrasi/export', [ReportController::class, 'eksporMigrasi'])->name('admin.reports.migrasi.export');
    Route::get('/reports/migrasi/template', [ReportController::class, 'unduhTemplateMigrasi'])->name('admin.reports.migrasi.template');
    Route::get('/reports/migrasi/dummy', [ReportController::class, 'unduhDummyMigrasi'])->name('admin.reports.migrasi.dummy');
    Route::get('/reports/{periode}', [ReportController::class, 'show'])->name('admin.reports.show');
    Route::get('/reports/{periode}/export', [ReportController::class, 'exportPeriode'])->name('admin.reports.periode.export');
    Route::get('/reports/{periode}/cetak', [ReportController::class, 'cetakRekapPeriode'])->name('admin.reports.periode.print');
    Route::get('/reports/{periode}/slip/{penggajian}', [ReportController::class, 'cetakSlipSupir'])->name('admin.reports.slip');

    Route::prefix('master')->name('admin.master.')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::patch('/employees/status-massal', [EmployeeController::class, 'statusMassal'])->name('employees.status-massal');
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::patch('/employees/{employee}/status', [EmployeeController::class, 'toggleStatus'])->name('employees.status');
        Route::get('/employees/{employee}/detail', [EmployeeController::class, 'detailSupir'])->name('employees.detail');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        Route::get('/trucks', [TruckController::class, 'index'])->name('trucks.index');
        Route::post('/trucks', [TruckController::class, 'store'])->name('trucks.store');
        Route::patch('/trucks/status-massal', [TruckController::class, 'statusMassal'])->name('trucks.status-massal');
        Route::patch('/trucks/{truck}', [TruckController::class, 'update'])->name('trucks.update');
        Route::patch('/trucks/{truck}/status', [TruckController::class, 'toggleStatus'])->name('trucks.status');
        Route::delete('/trucks/{truck}', [TruckController::class, 'destroy'])->name('trucks.destroy');

        Route::get('/routes', [MasterRouteController::class, 'index'])->name('routes.index');
        Route::post('/routes', [MasterRouteController::class, 'store'])->name('routes.store');
        Route::patch('/routes/status-massal', [MasterRouteController::class, 'statusMassal'])->name('routes.status-massal');
        Route::patch('/routes/{route}', [MasterRouteController::class, 'update'])->name('routes.update');
        Route::patch('/routes/{route}/status', [MasterRouteController::class, 'toggleStatus'])->name('routes.status');
        Route::delete('/routes/{route}', [MasterRouteController::class, 'destroy'])->name('routes.destroy');

        Route::post('/jenis-truk', [TruckController::class, 'simpanJenis'])->name('jenis-truk.store');
        Route::patch('/jenis-truk/{jenisTruk}', [TruckController::class, 'perbaruiJenis'])->name('jenis-truk.update');
        Route::delete('/jenis-truk/{jenisTruk}', [TruckController::class, 'hapusJenis'])->name('jenis-truk.destroy');

        // Kelola akun staf (Admin HRD). Supir dibuat lewat form supir.
        Route::post('/akun', [EmployeeController::class, 'simpanAkun'])->name('akun.store');
        Route::patch('/akun/{akun}', [EmployeeController::class, 'perbaruiAkun'])->name('akun.update');
        Route::delete('/akun/{akun}', [EmployeeController::class, 'hapusAkun'])->name('akun.destroy');
    });

    Route::prefix('operational')->name('admin.operational.')->group(function () {
        Route::get('/trips', [DeliveryTripController::class, 'index'])->name('trips.index');
        Route::post('/trips', [DeliveryTripController::class, 'store'])->name('trips.store');
        Route::patch('/trips/status-massal', [DeliveryTripController::class, 'statusMassal'])->name('trips.status-massal');
        Route::get('/trips/{trip}/cetak', [DeliveryTripController::class, 'cetak'])->name('trips.print');
        Route::patch('/trips/{trip}', [DeliveryTripController::class, 'update'])->name('trips.update');
        Route::delete('/trips/{trip}', [DeliveryTripController::class, 'destroy'])->name('trips.destroy');
        Route::get('/trips/export', [DeliveryTripController::class, 'export'])->name('trips.export');

        Route::get('/expenses', [TruckExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [TruckExpenseController::class, 'store'])->name('expenses.store');
        Route::patch('/expenses/status-massal', [TruckExpenseController::class, 'statusMassal'])->name('expenses.status-massal');
        Route::patch('/expenses/{expense}', [TruckExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [TruckExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::get('/loans', [EmployeeFinanceController::class, 'index'])->name('loans.index');
        Route::post('/loans', [EmployeeFinanceController::class, 'storeLoan'])->name('loans.store');
        Route::patch('/loans/status-massal', [EmployeeFinanceController::class, 'statusMassalLoan'])->name('loans.status-massal');
        Route::patch('/loans/{loan}', [EmployeeFinanceController::class, 'updateLoan'])->name('loans.update');
        Route::delete('/loans/{loan}', [EmployeeFinanceController::class, 'destroyLoan'])->name('loans.destroy');
        Route::patch('/loans/{loan}/angsuran/{angsuran}', [EmployeeFinanceController::class, 'potongAngsuran'])->name('loans.potong');
        Route::post('/loans/bpjs', [EmployeeFinanceController::class, 'storeBpjs'])->name('loans.bpjs');
        Route::post('/loans/bpjs-massal', [EmployeeFinanceController::class, 'storeBpjsMassal'])->name('loans.bpjs-massal');
        Route::post('/loans/allowances', [EmployeeFinanceController::class, 'storeAllowance'])->name('loans.allowances');
        Route::post('/loans/debit-memos', [EmployeeFinanceController::class, 'storeDebitMemo'])->name('loans.debit-memos');

        // Route calendar dimatikan sementara; diganti panel kalender kanan.
        // Route::get('/calendar', [OperationalCalendarController::class, 'index'])->name('calendar.index');
        // Route::post('/calendar', [OperationalCalendarController::class, 'store'])->name('calendar.store');
        // Route::patch('/calendar/{date}/validate', [OperationalCalendarController::class, 'validate'])->name('calendar.validate');
        // Route::patch('/calendar/{date}/lock', [OperationalCalendarController::class, 'lock'])->name('calendar.lock');
    });
});

/*
|--------------------------------------------------------------------------
| Workspace Pimpinan  →  /executive/*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:pimpinan', 'throttle:interaksi-admin'])->prefix('executive')->name('executive.')->group(function () {
    Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('dashboard');

    Route::get('/payroll', [PayrollReviewController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/{period}', [PayrollReviewController::class, 'show'])->name('payroll.show');
    Route::patch('/payroll/{period}/approve', [PayrollReviewController::class, 'approve'])->name('payroll.approve');
    Route::patch('/payroll/{period}/reject', [PayrollReviewController::class, 'reject'])->name('payroll.reject');
    Route::post('/payroll/{period}/approve-batch', [PayrollReviewController::class, 'approveBatch'])->name('payroll.approve-batch');
    Route::patch('/payroll/{period}/disburse', [PayrollReviewController::class, 'disburse'])->name('payroll.disburse');

    Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [FinancialReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('/reports/export', [FinancialReportController::class, 'export'])->name('reports.export');
    Route::post('/reports/email', [FinancialReportController::class, 'email'])->name('reports.email');

    // Route::get('/calendar', [ExecutiveCalendarController::class, 'index'])->name('calendar');
    Route::get('/trips', [TripMonitorController::class, 'index'])->name('trips');

    Route::get('/company', [CompanyProfileController::class, 'edit'])->name('company.edit');
    Route::patch('/company', [CompanyProfileController::class, 'update'])->name('company.update');
});

/*
|--------------------------------------------------------------------------
| Portal Karyawan / Supir  →  /my/*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:supir'])->prefix('my')->name('driver.')->group(function () {
    Route::get('/payroll', [DriverDashboardController::class, 'index'])->name('dashboard');
    Route::get('/payroll/slip', [DriverDashboardController::class, 'cetakSlip'])->name('payroll.slip');
    Route::get('/trips', [DriverTripHistoryController::class, 'index'])->name('trips.index');
    Route::patch('/trips/{trip}/mulai-kirim', [DriverTripHistoryController::class, 'mulaiPengiriman'])->name('trips.start-delivery');
    Route::patch('/trips/{trip}/konfirmasi', [DriverTripHistoryController::class, 'konfirmasi'])->name('trips.confirm');

    Route::get('/loans', [DriverLoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/cetak', [DriverLoanController::class, 'cetak'])->name('loans.print');
    Route::post('/loans', [DriverLoanController::class, 'store'])->name('loans.store');

    Route::get('/simulation', [\App\Http\Controllers\Driver\SalarySimulationController::class, 'index'])->name('simulation.index');
    Route::post('/simulation', [\App\Http\Controllers\Driver\SalarySimulationController::class, 'store'])->name('simulation.store');
    Route::delete('/simulation/{simulasi}', [\App\Http\Controllers\Driver\SalarySimulationController::class, 'destroy'])->name('simulation.destroy');
    Route::get('/profile', [DriverProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [DriverProfileController::class, 'update'])->name('profile.update');
});

Route::middleware('auth')->group(function () {
    // Pencarian global topbar: hasil mengikuti peran pengguna.
    Route::get('/pencarian', PencarianController::class)->name('pencarian');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
