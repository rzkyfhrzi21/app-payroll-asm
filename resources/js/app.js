import Alpine from 'alpinejs';
import './core/helpers.js';

import registerToast from './ui/toast.js';
import registerLightbox from './ui/lightbox.js';
import registerSearch from './ui/search.js';
import registerTableManager from './ui/table-manager.js';
import registerTabelAjax from './ui/tabel-ajax.js';

import registerShared from './modules/shared/auth.js';
import registerAdminPayroll from './modules/admin/payroll.js';
import registerAdminTrips from './modules/admin/trips.js';
import registerAdminExpenses from './modules/admin/expenses.js';
import registerAdminLoans from './modules/admin/loans.js';
import registerAdminMaster from './modules/admin/master.js';
import registerAdminReports from './modules/admin/reports.js';

import registerExecutiveDashboard from './modules/executive/dashboard.js';
import registerExecutivePayroll from './modules/executive/payroll.js';
import registerExecutiveReports from './modules/executive/reports.js';
import registerExecutiveTrips from './modules/executive/trips.js';

import registerDriverDashboard from './modules/driver/dashboard.js';
import registerDriverTrips from './modules/driver/trips.js';
import registerDriverLoans from './modules/driver/loans.js';
import registerDriverProfile from './modules/driver/profile.js';

window.Alpine = Alpine;

// Store global kecil: dipakai agar tumpukan toast tidak menimpa kartu progres
// unggah/impor yang juga berada di pojok kanan atas.
Alpine.store('panelProgres', { aktif: false });

// 1. UI Infrastructure
registerToast(Alpine);
registerLightbox(Alpine);
registerSearch(Alpine);
registerTableManager(Alpine);
registerTabelAjax(Alpine);

// 2. Shared & Auth Components
registerShared(Alpine);

// 3. Admin Domain Modules
registerAdminPayroll(Alpine);
registerAdminTrips(Alpine);
registerAdminExpenses(Alpine);
registerAdminLoans(Alpine);
registerAdminMaster(Alpine);
registerAdminReports(Alpine);

// 4. Executive Domain Modules
registerExecutiveDashboard(Alpine);
registerExecutivePayroll(Alpine);
registerExecutiveReports(Alpine);
registerExecutiveTrips(Alpine);

// 5. Driver Domain Modules
registerDriverDashboard(Alpine);
registerDriverTrips(Alpine);
registerDriverLoans(Alpine);
registerDriverProfile(Alpine);

Alpine.start();
