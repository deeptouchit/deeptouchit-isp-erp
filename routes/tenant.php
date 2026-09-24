<?php

use App\Http\Controllers\Tenant\Auth\TenantAuthController;
use App\Http\Controllers\Tenant\TenantBillingController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\TenantTicketController;
use App\Http\Controllers\Tenant\Upstream\TenantUpstreamController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Tenant\Network\MikrotikController;
use App\Http\Controllers\Tenant\Network\NasController;
use App\Http\Controllers\Tenant\Network\InternetPackageController;
use App\Http\Controllers\Tenant\Network\OltController;
use App\Http\Controllers\Tenant\Network\OltLicenseController;
use App\Http\Controllers\Tenant\Network\OnuController;
use App\Http\Controllers\Tenant\Network\IpPoolController;
use App\Http\Controllers\Tenant\Network\VlanController;
use App\Http\Controllers\Tenant\Network\RoutingController;
use App\Http\Controllers\Tenant\Network\NetworkMapController;
use App\Http\Controllers\Tenant\Reseller\ResellerDirectoryController;
use App\Http\Controllers\Tenant\Reseller\ResellerSubscriptionController;
use App\Http\Controllers\Tenant\Reseller\ResellerInvoiceController;
use App\Http\Controllers\Tenant\Reseller\ResellerWalletController;
use App\Http\Controllers\Tenant\Reseller\ResellerLedgerController;
use App\Http\Controllers\Tenant\Reseller\ResellerRechargeController;
use App\Http\Controllers\Tenant\Reseller\ResellerBandwidthController;
use App\Http\Controllers\Tenant\Reseller\ResellerBandwidthPlanController;
use App\Http\Controllers\Tenant\Staff\TenantStaffController;
use App\Http\Controllers\Tenant\Collector\TenantCollectorDashboardController;
use App\Http\Controllers\Tenant\Collector\TenantCollectorPaymentController;
use App\Http\Controllers\Tenant\Customer\TenantCustomerController;
use App\Http\Controllers\Tenant\Customer\TenantCustomerPaymentController;
use App\Http\Controllers\Tenant\Customer\TenantCoverageZoneController;
use App\Http\Controllers\Tenant\Finance\TenantCustomerInvoiceController;
use App\Http\Controllers\Tenant\Finance\TenantCustomerPaymentReceiptController;
use App\Http\Controllers\Tenant\Finance\TenantCashHandoverController;
use App\Http\Controllers\Tenant\Finance\TenantWholesaleBillingController;
use App\Http\Controllers\Tenant\Finance\TenantGatewayTransactionController;
use App\Http\Controllers\Tenant\Finance\TenantExpenseAccountController;
use App\Http\Controllers\Tenant\Finance\TenantFinancialLedgerController;
use App\Http\Controllers\Tenant\Report\TenantRevenueReportController;
use App\Http\Controllers\Tenant\Report\TenantCollectionReportController;
use App\Http\Controllers\Tenant\Report\TenantMrtgReportController;
use App\Http\Controllers\Tenant\Report\TenantBtrcReportController;
use App\Http\Controllers\Tenant\Report\TenantInventoryReportController;
use App\Http\Controllers\Tenant\Helpdesk\TenantFieldJobController;
use App\Http\Controllers\Tenant\Helpdesk\TenantInstallationTaskController;
use App\Http\Controllers\Tenant\Helpdesk\TenantResellerEscalationController;
use App\Http\Controllers\Tenant\Helpdesk\TenantSlaAnalyticsController;
use App\Http\Controllers\Tenant\Settings\TenantProfileController;
use App\Http\Controllers\Tenant\Settings\TenantSmsGatewayController;
use App\Http\Controllers\Tenant\Settings\TenantAutomationController;
use App\Http\Controllers\Tenant\Settings\TenantRbacController;
use App\Http\Controllers\Tenant\Settings\TenantBackupController;
use App\Http\Controllers\Tenant\Settings\TenantAuditLogController;
use App\Http\Controllers\Tenant\Settings\TenantPaymentGatewayController;
use App\Http\Middleware\EnsureTenantUser;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ISP Tenant Platform Routes
|--------------------------------------------------------------------------
| Access: ISP Tenant Administrators & Staff
| Modules: Authentication, Portal Dashboard, Subscription, Billing & Support
*/

Route::middleware(['web'])->group(function () {

    // 1. Guest Authentication Routes
    Route::middleware('guest')->group(function () {
        Route::get('/admin/login', [TenantAuthController::class, 'showLoginForm'])->name('tenant.login');
        Route::post('/admin/login', [TenantAuthController::class, 'login'])->name('tenant.login.submit');
        Route::get('/t/{slug}/login', [TenantAuthController::class, 'showLoginForm'])->name('tenant.slug.login');
    });

    // 2. Authenticated ISP Tenant Portal Routes
    Route::middleware(['auth', EnsureTenantUser::class])->group(function () {

        // Logout
        Route::post('/admin/logout', [TenantAuthController::class, 'logout'])->name('tenant.logout');

        // Landing Dashboard (https://somitysoft.com/admin/dashboard)
        Route::get('/admin/dashboard', [TenantDashboardController::class, 'index'])->name('tenant.dashboard');
        Route::get('/admin', fn() => redirect()->route('tenant.dashboard'))->name('tenant.dashboard.base');

        // Live Header Notifications
        Route::get('/admin/notifications', [NotificationController::class, 'unreadList'])->name('tenant.notifications.unread');
        Route::post('/admin/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('tenant.notifications.read');
        Route::post('/admin/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('tenant.notifications.mark-all-read');

        // Staff / User Profile (https://somitysoft.com/admin/profile)
        Route::get('/admin/profile', [TenantProfileController::class, 'userProfile'])->name('tenant.profile');
        Route::put('/admin/profile', [TenantProfileController::class, 'updateUserProfile'])->name('tenant.profile.update');
        Route::post('/admin/profile/password', [TenantProfileController::class, 'updatePassword'])->name('tenant.profile.password');

        // Network Infrastructure & Engine Management (Prefix: admin/network)
        Route::prefix('admin/network')->name('tenant.network.')->middleware('permission:network.routers,network.radius,network.olts_onus,network.packages,network.ip_pools')->group(function () {
            // MikroTik Routers Fleet Management
            Route::get('/mikrotik', [MikrotikController::class, 'index'])->name('mikrotik');
            Route::post('/mikrotik', [MikrotikController::class, 'store'])->name('mikrotik.store');
            Route::post('/mikrotik/test-connection', [MikrotikController::class, 'testConnection'])->name('mikrotik.test');
            Route::get('/mikrotik/{router}', [MikrotikController::class, 'show'])->name('mikrotik.show');
            Route::put('/mikrotik/{router}', [MikrotikController::class, 'update'])->name('mikrotik.update');
            Route::delete('/mikrotik/{router}', [MikrotikController::class, 'destroy'])->name('mikrotik.destroy');
            Route::post('/mikrotik/{router}/sync', [MikrotikController::class, 'sync'])->name('mikrotik.sync');
            Route::get('/mikrotik/{router}/interfaces', [MikrotikController::class, 'interfaces'])->name('mikrotik.interfaces');
            Route::get('/mikrotik/{router}/sessions', [MikrotikController::class, 'sessions'])->name('mikrotik.sessions');
            Route::post('/mikrotik/{router}/reboot', [MikrotikController::class, 'reboot'])->name('mikrotik.reboot');
            Route::patch('/mikrotik/{router}/toggle-status', [MikrotikController::class, 'toggleStatus'])->name('mikrotik.toggle-status');
            Route::get('/mikrotik/{router}/telemetry', [MikrotikController::class, 'telemetry'])->name('mikrotik.telemetry');
            Route::get('/nas', [NasController::class, 'index'])->name('nas');
            Route::post('/nas', [NasController::class, 'store'])->name('nas.store');
            Route::put('/nas/{nas}', [NasController::class, 'update'])->name('nas.update');
            Route::delete('/nas/{nas}', [NasController::class, 'destroy'])->name('nas.destroy');
            Route::patch('/nas/{nas}/toggle-status', [NasController::class, 'toggleStatus'])->name('nas.toggle-status');
            Route::post('/nas/{nas}/test-coa', [NasController::class, 'testCoA'])->name('nas.test-coa');
            Route::get('/nas/freeradius-config', [NasController::class, 'downloadClientsConf'])->name('nas.config');

            // Bandwidth & Internet Packages Management
            Route::get('/packages', [InternetPackageController::class, 'index'])->name('packages');
            Route::post('/packages', [InternetPackageController::class, 'store'])->name('packages.store');
            Route::post('/packages/import-mikrotik', [InternetPackageController::class, 'importFromMikrotik'])->name('packages.import-mikrotik');
            Route::get('/packages/router-pools/{routerId}', [InternetPackageController::class, 'getRouterIpPools'])->name('packages.router-pools');
            Route::put('/packages/{package}', [InternetPackageController::class, 'update'])->name('packages.update');
            Route::delete('/packages/{package}', [InternetPackageController::class, 'destroy'])->name('packages.destroy');
            Route::patch('/packages/{package}/toggle-status', [InternetPackageController::class, 'toggleStatus'])->name('packages.toggle-status');
            Route::post('/packages/{package}/sync', [InternetPackageController::class, 'syncMikrotik'])->name('packages.sync');
            // OLT & Optical Network Infrastructure
            Route::get('/olt', [OltController::class, 'index'])->name('olt');
            Route::get('/olt/create', [OltController::class, 'create'])->name('olt.create');
            Route::post('/olt', [OltController::class, 'store'])->name('olt.store');
            Route::post('/olt/test-connection', [OltController::class, 'testConnection'])->name('olt.test');
            Route::get('/olt/{olt}', [OltController::class, 'show'])->name('olt.show');
            Route::get('/olt/{olt}/edit', [OltController::class, 'edit'])->name('olt.edit');
            Route::put('/olt/{olt}', [OltController::class, 'update'])->name('olt.update');
            Route::delete('/olt/{olt}', [OltController::class, 'destroy'])->name('olt.destroy');
            Route::post('/olt/{olt}/sync', [OltController::class, 'sync'])->name('olt.sync');
            Route::post('/olt/{olt}/reboot-onu', [OltController::class, 'rebootOnu'])->name('olt.reboot-onu');
            Route::get('/olt/{olt}/autofind', [OltController::class, 'autofind'])->name('olt.autofind');
            Route::get('/olt/{olt}/telemetry', [OltController::class, 'telemetry'])->name('olt.telemetry');
            Route::get('/olt/{olt}/port-stats', [OltController::class, 'portStats'])->name('olt.port-stats');
            Route::post('/olt/{olt}/port-stats/reset', [OltController::class, 'resetPortStats'])->name('olt.port-stats.reset');
            // OLT License Governance & Lifecycle
            Route::get('/license', [OltLicenseController::class, 'index'])->name('license');
            Route::post('/license/{olt}/update', [OltLicenseController::class, 'update'])->name('license.update');
            Route::post('/license/{olt}/sync', [OltLicenseController::class, 'sync'])->name('license.sync');
            Route::post('/license/{olt}/auto-renew', [OltLicenseController::class, 'toggleAutoRenew'])->name('license.auto-renew');
            Route::get('/onu', [OnuController::class, 'index'])->name('onu');
            Route::get('/onu/{onu}', [OnuController::class, 'show'])->name('onu.show');
            Route::put('/onu/{onu}', [OnuController::class, 'update'])->name('onu.update');
            Route::post('/onu/{onu}/reboot', [OnuController::class, 'reboot'])->name('onu.reboot');
            Route::post('/onu/{onu}/factory-reset', [OnuController::class, 'factoryReset'])->name('onu.factory-reset');
            Route::get('/onu/{onu}/optical', [OnuController::class, 'optical'])->name('onu.optical');
            Route::get('/ip-pools', [IpPoolController::class, 'index'])->name('ip-pools');
            Route::post('/ip-pools', [IpPoolController::class, 'store'])->name('ip-pools.store');
            Route::post('/ip-pools/import-mikrotik', [IpPoolController::class, 'importFromMikrotik'])->name('ip-pools.import-mikrotik');
            Route::get('/ip-pools/{ipPool}', [IpPoolController::class, 'show'])->name('ip-pools.show');
            Route::put('/ip-pools/{ipPool}', [IpPoolController::class, 'update'])->name('ip-pools.update');
            Route::delete('/ip-pools/{ipPool}', [IpPoolController::class, 'destroy'])->name('ip-pools.destroy');
            Route::patch('/ip-pools/{ipPool}/toggle-status', [IpPoolController::class, 'toggleStatus'])->name('ip-pools.toggle-status');
            Route::post('/ip-pools/{ipPool}/sync', [IpPoolController::class, 'syncMikrotik'])->name('ip-pools.sync');
            Route::get('/vlans', [VlanController::class, 'index'])->name('vlans');
            Route::post('/vlans', [VlanController::class, 'store'])->name('vlans.store');
            Route::post('/vlans/import-mikrotik', [VlanController::class, 'importFromMikrotik'])->name('vlans.import-mikrotik');
            Route::get('/vlans/router-interfaces/{router}', [VlanController::class, 'getRouterInterfaces'])->name('vlans.router-interfaces');
            Route::get('/vlans/{vlan}', [VlanController::class, 'show'])->name('vlans.show');
            Route::put('/vlans/{vlan}', [VlanController::class, 'update'])->name('vlans.update');
            Route::delete('/vlans/{vlan}', [VlanController::class, 'destroy'])->name('vlans.destroy');
            Route::patch('/vlans/{vlan}/toggle-status', [VlanController::class, 'toggleStatus'])->name('vlans.toggle-status');
            Route::post('/vlans/{vlan}/sync', [VlanController::class, 'syncMikrotik'])->name('vlans.sync');
            Route::get('/routing', [RoutingController::class, 'index'])->name('routing');
            Route::post('/routing', [RoutingController::class, 'store'])->name('routing.store');
            Route::post('/routing/import-mikrotik', [RoutingController::class, 'importFromMikrotik'])->name('routing.import-mikrotik');
            Route::get('/routing/{route}', [RoutingController::class, 'show'])->name('routing.show');
            Route::put('/routing/{route}', [RoutingController::class, 'update'])->name('routing.update');
            Route::delete('/routing/{route}', [RoutingController::class, 'destroy'])->name('routing.destroy');
            Route::patch('/routing/{route}/toggle-status', [RoutingController::class, 'toggleStatus'])->name('routing.toggle-status');
            Route::post('/routing/{route}/sync', [RoutingController::class, 'syncMikrotik'])->name('routing.sync');
            Route::get('/map', [NetworkMapController::class, 'index'])->name('map');
        });

        // Reseller Network Management (Prefix: admin/resellers)
        Route::prefix('admin/resellers')->name('tenant.resellers.')->middleware('permission:resellers.view,resellers.create,resellers.edit,resellers.wallet')->group(function () {
            // 2. Subscriptions & Billing (Panel Licenses & Renewals)
            Route::get('/subscriptions', [ResellerSubscriptionController::class, 'index'])->name('subscriptions');
            Route::post('/subscriptions/renew', [ResellerSubscriptionController::class, 'renew'])->name('subscriptions.renew');
            Route::post('/subscriptions/generate-bills', [ResellerSubscriptionController::class, 'generateMonthlyBills'])->name('subscriptions.generate-bills');
            Route::post('/subscriptions/update-charge', [ResellerSubscriptionController::class, 'updatePanelCharge'])->name('subscriptions.update-charge');
            Route::post('/subscriptions/{id}/toggle-status', [ResellerSubscriptionController::class, 'togglePanelStatus'])->whereNumber('id')->name('subscriptions.toggle-status');

            // 3. Invoices & Receipts (Subscription & Bandwidth Invoices)
            Route::get('/invoices', [ResellerInvoiceController::class, 'index'])->name('invoices');
            Route::post('/invoices/{id}/pay', [ResellerInvoiceController::class, 'recordPayment'])->whereNumber('id')->name('invoices.pay');
            Route::delete('/invoices/{id}', [ResellerInvoiceController::class, 'destroy'])->whereNumber('id')->name('invoices.destroy');

            // 4. Wallets & Balances (Prepaid Balances, Credit Limits & Overdrafts)
            Route::get('/wallets', [ResellerWalletController::class, 'index'])->name('wallets');
            Route::post('/wallets/adjust', [ResellerWalletController::class, 'adjustBalance'])->name('wallets.adjust');
            Route::post('/wallets/credit-limit', [ResellerWalletController::class, 'updateCreditLimit'])->name('wallets.credit-limit');

            // 5. Recharge & Adjustment Ledger (Audit Logs & Transaction Vouchers)
            Route::get('/ledger', [ResellerLedgerController::class, 'index'])->name('ledger');

            // 6. Recharge History & Payment Requests (Online PGW, Bank & Cash Top-ups)
            Route::get('/recharge', [ResellerRechargeController::class, 'index'])->name('recharge');
            Route::post('/recharge', [ResellerRechargeController::class, 'store'])->name('recharge.store');
            Route::post('/recharge/{id}/approve', [ResellerRechargeController::class, 'approve'])->whereNumber('id')->name('recharge.approve');
            Route::post('/recharge/{id}/reject', [ResellerRechargeController::class, 'reject'])->whereNumber('id')->name('recharge.reject');
            Route::get('/recharge/{id}/receipt', [ResellerRechargeController::class, 'receipt'])->whereNumber('id')->name('recharge.receipt');

            // 7. Bandwidth Allocation & Wholesale MRTG (Committed CIR, Burstable MIR, VLAN & Router Simple Queues)
            Route::get('/bandwidth', [ResellerBandwidthController::class, 'index'])->name('bandwidth');
            Route::get('/capacity', [ResellerBandwidthController::class, 'capacity'])->name('capacity');
            Route::post('/bandwidth', [ResellerBandwidthController::class, 'saveAllocation'])->name('bandwidth.save');
            Route::post('/bandwidth/{id}/sync', [ResellerBandwidthController::class, 'syncMikrotik'])->whereNumber('id')->name('bandwidth.sync');
            Route::post('/bandwidth/{id}/toggle-status', [ResellerBandwidthController::class, 'toggleStatus'])->whereNumber('id')->name('bandwidth.toggle-status');
            Route::delete('/bandwidth/{id}', [ResellerBandwidthController::class, 'deleteAllocation'])->whereNumber('id')->name('bandwidth.delete');
            Route::post('/bandwidth/generate-bills', [ResellerBandwidthController::class, 'generateMonthlyBills'])->name('bandwidth.generate-bills');
            Route::get('/bandwidth/invoices/{id}', [ResellerBandwidthController::class, 'showInvoice'])->whereNumber('id')->name('bandwidth.invoice.show');
            Route::post('/bandwidth/invoices/{id}/pay', [ResellerBandwidthController::class, 'collectPayment'])->whereNumber('id')->name('bandwidth.invoice.pay');
            Route::get('/bandwidth/invoices/{id}/receipt', [ResellerBandwidthController::class, 'printReceipt'])->whereNumber('id')->name('bandwidth.invoice.receipt');

            // 7b. Wholesale Bandwidth Rate Plans / Tariff Cards
            Route::get('/bandwidth-plans', [ResellerBandwidthPlanController::class, 'index'])->name('bandwidth-plans');
            Route::post('/bandwidth-plans', [ResellerBandwidthPlanController::class, 'store'])->name('bandwidth-plans.store');
            Route::post('/bandwidth-plans/{id}', [ResellerBandwidthPlanController::class, 'update'])->whereNumber('id')->name('bandwidth-plans.update');
            Route::post('/bandwidth-plans/{id}/toggle-status', [ResellerBandwidthPlanController::class, 'toggleStatus'])->whereNumber('id')->name('bandwidth-plans.toggle-status');
            Route::delete('/bandwidth-plans/{id}', [ResellerBandwidthPlanController::class, 'destroy'])->whereNumber('id')->name('bandwidth-plans.destroy');

            // 1. Reseller Directory & Onboarding (All Resellers List, Contact Details & Profiles)
            Route::get('/', [ResellerDirectoryController::class, 'index'])->name('index');
            Route::post('/', [ResellerDirectoryController::class, 'store'])->name('store');
            Route::get('/{reseller}', [ResellerDirectoryController::class, 'show'])->whereNumber('reseller')->name('show');
            Route::put('/{reseller}', [ResellerDirectoryController::class, 'update'])->whereNumber('reseller')->name('update');
            Route::delete('/{reseller}', [ResellerDirectoryController::class, 'destroy'])->whereNumber('reseller')->name('destroy');
            Route::post('/{reseller}/toggle-status', [ResellerDirectoryController::class, 'toggleStatus'])->whereNumber('reseller')->name('toggle-status');
            Route::post('/{reseller}/adjust-wallet', [ResellerDirectoryController::class, 'adjustWallet'])->whereNumber('reseller')->name('adjust-wallet');
        });

        // 4. Staff Management & HRM Routes (Prefix: admin/staff)
        Route::prefix('admin/staff')->name('tenant.staff.')->middleware('permission:settings.rbac,customers.view')->group(function () {
            Route::get('/', [TenantStaffController::class, 'index'])->name('index');
            Route::get('/isp', [TenantStaffController::class, 'ispStaff'])->name('isp');
            Route::get('/resellers', [TenantStaffController::class, 'resellerRoster'])->name('resellers');
            Route::get('/attendance', [TenantStaffController::class, 'attendance'])->name('attendance');
            Route::post('/attendance/mark', [TenantStaffController::class, 'markAttendance'])->name('attendance.mark');
            Route::post('/attendance/punch', [TenantStaffController::class, 'quickPunch'])->name('attendance.punch');
            Route::get('/attendance/export', [TenantStaffController::class, 'exportAttendanceCsv'])->name('attendance.export');
            Route::get('/logs', [TenantStaffController::class, 'activityLogs'])->name('logs');
            Route::get('/commissions', [TenantStaffController::class, 'commissions'])->name('commissions');
            Route::post('/', [TenantStaffController::class, 'store'])->name('store');
            Route::get('/{staff}', [TenantStaffController::class, 'show'])->whereNumber('staff')->name('show');
            Route::put('/{staff}', [TenantStaffController::class, 'update'])->whereNumber('staff')->name('update');
            Route::delete('/{staff}', [TenantStaffController::class, 'destroy'])->whereNumber('staff')->name('destroy');
            Route::post('/{staff}/toggle-status', [TenantStaffController::class, 'toggleStatus'])->whereNumber('staff')->name('toggle-status');
            Route::post('/{staff}/reset-password', [TenantStaffController::class, 'resetPassword'])->whereNumber('staff')->name('reset-password');
        });

        // 4b. Field Bill Collector Hub Routes (Prefix: admin/collector)
        Route::prefix('admin/collector')->name('tenant.collector.')->group(function () {
            Route::get('/dashboard', fn() => redirect()->route('tenant.dashboard'))->name('dashboard');
            Route::get('/due-customers', [TenantCollectorDashboardController::class, 'dueCustomers'])->name('due-customers');
            Route::get('/handover', [TenantCollectorDashboardController::class, 'handover'])->name('handover');
            Route::post('/handover', [TenantCollectorDashboardController::class, 'submitHandover'])->name('handover.submit');
            Route::post('/handover/store', [TenantCollectorDashboardController::class, 'submitHandover'])->name('handover.store');
            Route::post('/collect', [TenantCollectorPaymentController::class, 'collectBill'])->name('collect');
            Route::post('/collect-bill', [TenantCollectorPaymentController::class, 'collectBill'])->name('collect-bill');
        });

        // 5. Customer Management (CRM & Subscribers) Routes (Prefix: admin/customers)
        Route::prefix('admin/customers')->name('tenant.customers.')->middleware('permission:customers.view,customers.create,customers.edit,customers.delete')->group(function () {
            Route::get('/', [TenantCustomerController::class, 'index'])->name('index');
            Route::get('/direct', [TenantCustomerController::class, 'index'])->defaults('scope', 'isp')->name('direct');
            Route::get('/due', [TenantCustomerController::class, 'index'])->defaults('status', 'due')->name('due');
            Route::get('/online', [TenantCustomerController::class, 'index'])->defaults('online_status', 'online')->name('online');
            
            // Coverage Zones & Areas Dedicated Routes
            Route::get('/zones', [TenantCoverageZoneController::class, 'index'])->name('zones');
            Route::post('/zones', [TenantCoverageZoneController::class, 'store'])->name('zones.store');
            Route::put('/zones/{id}', [TenantCoverageZoneController::class, 'update'])->whereNumber('id')->name('zones.update');
            Route::delete('/zones/{id}', [TenantCoverageZoneController::class, 'destroy'])->whereNumber('id')->name('zones.destroy');
            Route::post('/zones/{id}/toggle-status', [TenantCoverageZoneController::class, 'toggleStatus'])->whereNumber('id')->name('zones.toggle-status');

            Route::get('/disconnected', [TenantCustomerController::class, 'index'])->defaults('status', 'disconnected')->name('disconnected');
            Route::get('/create', [TenantCustomerController::class, 'create'])->name('create');
            Route::post('/', [TenantCustomerController::class, 'store'])->name('store');
            Route::get('/import-template', [TenantCustomerController::class, 'downloadSampleTemplate'])->name('import-template');
            Route::post('/import', [TenantCustomerController::class, 'importCsv'])->name('import');
            Route::get('/export', [TenantCustomerController::class, 'exportCsv'])->name('export');
            Route::get('/{customer}', [TenantCustomerController::class, 'show'])->whereNumber('customer')->name('show');
            Route::get('/{customer}/edit', [TenantCustomerController::class, 'edit'])->whereNumber('customer')->name('edit');
            Route::put('/{customer}', [TenantCustomerController::class, 'update'])->whereNumber('customer')->name('update');
            Route::delete('/{customer}', [TenantCustomerController::class, 'destroy'])->whereNumber('customer')->name('destroy');
            Route::post('/{customer}/toggle-status', [TenantCustomerController::class, 'toggleStatus'])->whereNumber('customer')->name('toggle-status');
            Route::post('/{customer}/toggle-mikrotik', [TenantCustomerController::class, 'toggleMikrotikSecret'])->whereNumber('customer')->name('toggle-mikrotik');
            Route::post('/{customer}/update-status', [TenantCustomerController::class, 'updateStatus'])->whereNumber('customer')->name('update-status');
            Route::post('/{customer}/renew', [TenantCustomerController::class, 'renewSubscription'])->whereNumber('customer')->name('renew');
            Route::post('/{customer}/sync-mikrotik', [TenantCustomerController::class, 'syncMikrotik'])->whereNumber('customer')->name('sync-mikrotik');
            Route::post('/sync-all-mikrotik', [TenantCustomerController::class, 'syncAllMikrotik'])->name('sync-all-mikrotik');
            Route::post('/sync-online-sessions', [TenantCustomerController::class, 'syncOnlineSessions'])->name('sync-online');
            Route::post('/{customer}/kick-session', [TenantCustomerController::class, 'kickSession'])->whereNumber('customer')->name('kick-session');
            Route::post('/{customer}/ping', [TenantCustomerController::class, 'pingCustomer'])->whereNumber('customer')->name('ping');
            Route::post('/{customer}/update-pppoe', [TenantCustomerController::class, 'updatePppoe'])->whereNumber('customer')->name('update-pppoe');
            Route::post('/{customer}/update-package', [TenantCustomerController::class, 'updatePackage'])->whereNumber('customer')->name('update-package');
            Route::post('/{customer}/update-monthly-bill', [TenantCustomerController::class, 'updateMonthlyBill'])->whereNumber('customer')->name('update-monthly-bill');
            Route::post('/{customer}/update-billing-cycle', [TenantCustomerController::class, 'updateBillingCycle'])->whereNumber('customer')->name('update-billing-cycle');
            Route::post('/{customer}/update-balance', [TenantCustomerController::class, 'updateBalance'])->whereNumber('customer')->name('update-balance');
            Route::get('/bulk-payments', [TenantCustomerPaymentController::class, 'bulkPaymentPage'])->name('bulk-payments');
            Route::get('/bulk-payments/print', [TenantCustomerPaymentController::class, 'printBulkPayments'])->name('bulk-payments.print');
            Route::post('/bulk-payments/process', [TenantCustomerPaymentController::class, 'processBulkPayment'])->name('bulk-payments.process');
            Route::post('/bulk-pay-bills', [TenantCustomerPaymentController::class, 'processBulkPayment'])->name('bulk-pay-bills');
            Route::post('/{customer}/pay-bill', [TenantCustomerPaymentController::class, 'receivePayment'])->whereNumber('customer')->name('pay-bill');
            Route::get('/payments/{payment}/receipt', [TenantCustomerPaymentController::class, 'printReceipt'])->whereNumber('payment')->name('payments.receipt');
            Route::get('/{customer}/payments', [TenantCustomerPaymentController::class, 'history'])->whereNumber('customer')->name('payments.history');
            Route::post('/{customer}/transfer-reseller', [TenantCustomerController::class, 'transferReseller'])->whereNumber('customer')->name('transfer-reseller');
            Route::post('/{customer}/update-note', [TenantCustomerController::class, 'updateNote'])->whereNumber('customer')->name('update-note');
            Route::post('/batch-traffic', [TenantCustomerController::class, 'getBatchTraffic'])->name('batch-traffic');
            Route::get('/{customer}/traffic', [TenantCustomerController::class, 'getTraffic'])->whereNumber('customer')->name('traffic');
            Route::get('/{customer}/usage-history', [TenantCustomerController::class, 'getUsageHistory'])->whereNumber('customer')->name('usage-history');
        });

        // Billing & Finance (Prefix: admin/finance)
        Route::prefix('admin/finance')->name('tenant.finance.')->middleware('permission:billing.invoices,billing.collect,billing.cash_handover,billing.wholesale,billing.gateways,billing.expenses,billing.ledger')->group(function () {
            // 1. Customer Invoices & Bills
            Route::get('/invoices', [TenantCustomerInvoiceController::class, 'index'])->name('invoices');
            Route::post('/invoices', [TenantCustomerInvoiceController::class, 'store'])->name('invoices.store');
            Route::post('/invoices/generate-monthly', [TenantCustomerInvoiceController::class, 'generateMonthlyInvoices'])->name('invoices.generate-monthly');
            Route::get('/invoices/{id}', [TenantCustomerInvoiceController::class, 'show'])->whereNumber('id')->name('invoices.show');
            Route::post('/invoices/{id}/pay', [TenantCustomerInvoiceController::class, 'recordPayment'])->whereNumber('id')->name('invoices.pay');
            Route::post('/invoices/{id}/cancel', [TenantCustomerInvoiceController::class, 'cancel'])->whereNumber('id')->name('invoices.cancel');
            Route::delete('/invoices/{id}', [TenantCustomerInvoiceController::class, 'destroy'])->whereNumber('id')->name('invoices.destroy');

            // 2. Payment Collections & Receipts
            Route::get('/payments', [TenantCustomerPaymentReceiptController::class, 'index'])->name('payments');
            Route::post('/payments', [TenantCustomerPaymentReceiptController::class, 'store'])->name('payments.store');
            Route::get('/payments/export', [TenantCustomerPaymentReceiptController::class, 'exportCsv'])->name('payments.export');
            Route::get('/payments/{id}', [TenantCustomerPaymentReceiptController::class, 'show'])->whereNumber('id')->name('payments.show');
            Route::post('/payments/{id}/sms', [TenantCustomerPaymentReceiptController::class, 'sendSms'])->whereNumber('id')->name('payments.sms');
            Route::post('/payments/{id}/void', [TenantCustomerPaymentReceiptController::class, 'voidPayment'])->whereNumber('id')->name('payments.void');
            Route::post('/payments/{id}/approve', [TenantCustomerPaymentReceiptController::class, 'approvePayment'])->whereNumber('id')->name('payments.approve');
            Route::post('/payments/{id}/reject', [TenantCustomerPaymentReceiptController::class, 'rejectPayment'])->whereNumber('id')->name('payments.reject');

            // 3. Daily Cash Handover & Closing
            Route::get('/cash-handover', [TenantCashHandoverController::class, 'index'])->name('cash-handover');
            Route::post('/cash-handover', [TenantCashHandoverController::class, 'store'])->name('cash-handover.store');
            Route::get('/cash-handover/collector-cash', [TenantCashHandoverController::class, 'getCollectorPendingCash'])->name('cash-handover.collector-cash');
            Route::get('/cash-handover/export', [TenantCashHandoverController::class, 'exportCsv'])->name('cash-handover.export');
            Route::get('/cash-handover/{id}', [TenantCashHandoverController::class, 'show'])->whereNumber('id')->name('cash-handover.show');
            Route::post('/cash-handover/{id}/approve', [TenantCashHandoverController::class, 'approve'])->whereNumber('id')->name('cash-handover.approve');
            Route::post('/cash-handover/{id}/reject', [TenantCashHandoverController::class, 'reject'])->whereNumber('id')->name('cash-handover.reject');

            // 3b. Collector Portal & Hub
            Route::get('/collector/dashboard', [TenantCollectorDashboardController::class, 'index'])->name('collector.dashboard');
            Route::get('/collector/due-customers', [TenantCollectorDashboardController::class, 'dueCustomers'])->name('collector.due-customers');
            Route::get('/collector/handover', [TenantCollectorDashboardController::class, 'handover'])->name('collector.handover');
            Route::post('/collector/handover', [TenantCollectorDashboardController::class, 'submitHandover'])->name('collector.handover.submit');
            Route::post('/collector/collect', [TenantCollectorPaymentController::class, 'collectBill'])->name('collector.collect');

            // 4. Reseller Wholesale Billing
            Route::get('/wholesale-billing', [TenantWholesaleBillingController::class, 'index'])->name('wholesale-billing');
            Route::post('/wholesale-billing', [TenantWholesaleBillingController::class, 'store'])->name('wholesale-billing.store');
            Route::post('/wholesale-billing/generate-monthly', [TenantWholesaleBillingController::class, 'generateMonthlyBills'])->name('wholesale-billing.generate-monthly');
            Route::get('/wholesale-billing/export', [TenantWholesaleBillingController::class, 'exportCsv'])->name('wholesale-billing.export');
            Route::get('/wholesale-billing/{id}', [TenantWholesaleBillingController::class, 'show'])->whereNumber('id')->name('wholesale-billing.show');
            Route::post('/wholesale-billing/{id}/pay', [TenantWholesaleBillingController::class, 'recordPayment'])->whereNumber('id')->name('wholesale-billing.pay');
            Route::post('/wholesale-billing/{id}/cancel', [TenantWholesaleBillingController::class, 'cancel'])->whereNumber('id')->name('wholesale-billing.cancel');
            Route::delete('/wholesale-billing/{id}', [TenantWholesaleBillingController::class, 'destroy'])->whereNumber('id')->name('wholesale-billing.destroy');

            // 5. Online Gateway Transactions
            Route::get('/gateway-transactions', [TenantGatewayTransactionController::class, 'index'])->name('gateway-transactions');
            Route::get('/gateway-transactions/export', [TenantGatewayTransactionController::class, 'exportCsv'])->name('gateway-transactions.export');
            Route::get('/gateway-transactions/{id}', [TenantGatewayTransactionController::class, 'show'])->whereNumber('id')->name('gateway-transactions.show');
            Route::post('/gateway-transactions/{id}/verify', [TenantGatewayTransactionController::class, 'verifyStatus'])->whereNumber('id')->name('gateway-transactions.verify');
            Route::post('/gateway-transactions/{id}/refund', [TenantGatewayTransactionController::class, 'refund'])->whereNumber('id')->name('gateway-transactions.refund');

            // 6. Income & Expense Accounts
            Route::get('/expenses', [TenantExpenseAccountController::class, 'index'])->name('expenses');
            Route::post('/expenses', [TenantExpenseAccountController::class, 'store'])->name('expenses.store');
            Route::post('/expenses/category', [TenantExpenseAccountController::class, 'storeCategory'])->name('expenses.category');
            Route::get('/expenses/export', [TenantExpenseAccountController::class, 'exportCsv'])->name('expenses.export');
            Route::get('/expenses/{id}', [TenantExpenseAccountController::class, 'show'])->whereNumber('id')->name('expenses.show');
            Route::post('/expenses/{id}/approve', [TenantExpenseAccountController::class, 'approve'])->whereNumber('id')->name('expenses.approve');
            Route::post('/expenses/{id}/reject', [TenantExpenseAccountController::class, 'reject'])->whereNumber('id')->name('expenses.reject');
            Route::delete('/expenses/{id}', [TenantExpenseAccountController::class, 'destroy'])->whereNumber('id')->name('expenses.destroy');

            // 7. Financial Statements & General Ledger
            Route::get('/ledger', [TenantFinancialLedgerController::class, 'index'])->name('ledger');
            Route::get('/ledger/export', [TenantFinancialLedgerController::class, 'exportCsv'])->name('ledger.export');
            Route::get('/ledger/print', [TenantFinancialLedgerController::class, 'printStatement'])->name('ledger.print');
        });

        // 7. Reports & Analytics Routes (Prefix: admin/reports)
        Route::prefix('admin/reports')->name('tenant.reports.')->middleware('permission:reports.revenue,reports.collection,reports.mrtg,reports.btrc,reports.inventory')->group(function () {
            // 1. Revenue & Growth Report
            Route::get('/revenue', [TenantRevenueReportController::class, 'index'])->name('revenue');
            Route::get('/revenue/export', [TenantRevenueReportController::class, 'exportCsv'])->name('revenue.export');
            Route::get('/revenue/print', [TenantRevenueReportController::class, 'printReport'])->name('revenue.print');

            // 2. Collection & Due Report
            Route::get('/collection', [TenantCollectionReportController::class, 'index'])->name('collection');
            Route::get('/collection/export', [TenantCollectionReportController::class, 'exportCsv'])->name('collection.export');
            Route::get('/collection/print', [TenantCollectionReportController::class, 'printReport'])->name('collection.print');

            // 3. Bandwidth & MRTG Graph Analytics
            Route::get('/mrtg', [TenantMrtgReportController::class, 'index'])->name('mrtg');
            Route::get('/mrtg/live', [TenantMrtgReportController::class, 'liveData'])->name('mrtg.live');
            Route::get('/mrtg/export', [TenantMrtgReportController::class, 'exportCsv'])->name('mrtg.export');
            Route::get('/mrtg/print', [TenantMrtgReportController::class, 'printReport'])->name('mrtg.print');

            // 4. BTRC Compliance Log & Regulatory Audit
            Route::get('/btrc', [TenantBtrcReportController::class, 'index'])->name('btrc');
            Route::get('/btrc/export', [TenantBtrcReportController::class, 'exportCsv'])->name('btrc.export');
            Route::get('/btrc/print', [TenantBtrcReportController::class, 'printReport'])->name('btrc.print');

            // 5. Equipment & Hardware Inventory Report
            Route::get('/inventory', [TenantInventoryReportController::class, 'index'])->name('inventory');
            Route::get('/inventory/export', [TenantInventoryReportController::class, 'exportCsv'])->name('inventory.export');
            Route::get('/inventory/print', [TenantInventoryReportController::class, 'printReport'])->name('inventory.print');
        });

        // Subscription & Billing Routes (Prefix: admin/billing)
        Route::prefix('admin/billing')->name('tenant.billing.')->group(function () {
            Route::get('/', [TenantBillingController::class, 'dashboard'])->name('dashboard');
            Route::get('/invoices', [TenantBillingController::class, 'invoices'])->name('invoices');
            Route::get('/payments', [TenantBillingController::class, 'payments'])->name('payments');
            Route::get('/invoices/{invoice}', [TenantBillingController::class, 'showInvoice'])->name('invoice.show');
            Route::post('/invoices/{invoice}/pay', [TenantBillingController::class, 'initiatePayment'])->name('pay');
            Route::get('/payment/callback', [TenantBillingController::class, 'paymentCallback'])->name('payment.callback');
            Route::get('/suspended', [TenantBillingController::class, 'suspended'])->name('suspended');
        });

        // Upstream Bandwidth & Carrier Accounting (Prefix: admin/upstream)
        Route::prefix('admin/upstream')->name('tenant.upstream.')->group(function () {
            Route::get('/', [TenantUpstreamController::class, 'index'])->name('index');
            Route::post('/providers', [TenantUpstreamController::class, 'storeProvider'])->name('provider.store');
            Route::put('/providers/{id}', [TenantUpstreamController::class, 'updateProvider'])->whereNumber('id')->name('provider.update');
            Route::post('/providers/{id}/toggle-status', [TenantUpstreamController::class, 'toggleStatus'])->whereNumber('id')->name('provider.toggle-status');
            Route::delete('/providers/{id}', [TenantUpstreamController::class, 'destroyProvider'])->whereNumber('id')->name('provider.destroy');

            // Invoices & Bills
            Route::get('/invoices', [TenantUpstreamController::class, 'invoices'])->name('invoices');
            Route::post('/invoices', [TenantUpstreamController::class, 'storeInvoice'])->name('invoice.store');
            Route::delete('/invoices/{id}', [TenantUpstreamController::class, 'destroyInvoice'])->whereNumber('id')->name('invoice.destroy');

            // Payments & Vouchers
            Route::get('/payments', [TenantUpstreamController::class, 'payments'])->name('payments');
            Route::post('/payments', [TenantUpstreamController::class, 'storePayment'])->name('payment.store');
            Route::get('/payments/{id}/voucher', [TenantUpstreamController::class, 'voucher'])->whereNumber('id')->name('payment.voucher');
            Route::delete('/payments/{id}', [TenantUpstreamController::class, 'destroyPayment'])->whereNumber('id')->name('payment.destroy');
        });

        // Helpdesk & Support Tickets Routes (Prefix: admin/support)
        Route::prefix('admin/support')->name('tenant.tickets.')->middleware('permission:support.tickets,support.field_jobs,support.installations,support.escalations,support.sla')->group(function () {
            // Support Tickets
            Route::get('/', [TenantTicketController::class, 'index'])->name('index');
            Route::get('/create', [TenantTicketController::class, 'create'])->name('create');
            Route::post('/', [TenantTicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [TenantTicketController::class, 'show'])->whereNumber('ticket')->name('show');
            Route::post('/{ticket}/reply', [TenantTicketController::class, 'reply'])->whereNumber('ticket')->name('reply');
            Route::post('/{id}/close', [TenantTicketController::class, 'closeTicket'])->whereNumber('id')->name('close');
            Route::post('/{id}/reopen', [TenantTicketController::class, 'reopenTicket'])->whereNumber('id')->name('reopen');

            // 2. Assigned Field Jobs & Dispatch Roster
            Route::get('/field-jobs', [TenantFieldJobController::class, 'index'])->name('field-jobs');
            Route::post('/field-jobs', [TenantFieldJobController::class, 'store'])->name('field-jobs.store');
            Route::post('/field-jobs/{id}/status', [TenantFieldJobController::class, 'updateStatus'])->whereNumber('id')->name('field-jobs.status');
            Route::get('/field-jobs/{id}/print', [TenantFieldJobController::class, 'printJobSheet'])->whereNumber('id')->name('field-jobs.print');
            Route::get('/field-jobs/export', [TenantFieldJobController::class, 'exportCsv'])->name('field-jobs.export');

            // 3. New Installation Tasks & Provisioning Pipeline
            Route::get('/installations', [TenantInstallationTaskController::class, 'index'])->name('installations');
            Route::post('/installations', [TenantInstallationTaskController::class, 'store'])->name('installations.store');
            Route::post('/installations/{id}/step', [TenantInstallationTaskController::class, 'updateStage'])->whereNumber('id')->name('installations.step');
            Route::get('/installations/{id}/print', [TenantInstallationTaskController::class, 'printActivationSlip'])->whereNumber('id')->name('installations.print');
            Route::get('/installations/export', [TenantInstallationTaskController::class, 'exportCsv'])->name('installations.export');

            // 4. Sub-ISP & Reseller Technical Escalations
            Route::get('/escalations', [TenantResellerEscalationController::class, 'index'])->name('escalations');
            Route::post('/escalations', [TenantResellerEscalationController::class, 'store'])->name('escalations.store');
            Route::post('/escalations/{id}/status', [TenantResellerEscalationController::class, 'updateStatus'])->whereNumber('id')->name('escalations.status');
            Route::get('/escalations/{id}/print', [TenantResellerEscalationController::class, 'printReport'])->whereNumber('id')->name('escalations.print');
            Route::get('/escalations/export', [TenantResellerEscalationController::class, 'exportCsv'])->name('escalations.export');

            // 5. Helpdesk, NOC & Field SLA Analytics & Performance Scorecard
            Route::get('/sla-analytics', [TenantSlaAnalyticsController::class, 'index'])->name('sla-analytics');
            Route::get('/sla-analytics/export', [TenantSlaAnalyticsController::class, 'exportCsv'])->name('sla-analytics.export');
            Route::get('/sla-analytics/print', [TenantSlaAnalyticsController::class, 'printReport'])->name('sla-analytics.print');
        });

        // 9. System Settings & Administration Routes (Prefix: admin/settings)
        Route::get('/admin/settings', fn() => redirect()->route('tenant.settings.payment-gateways'));
        Route::prefix('admin/settings')->name('tenant.settings.')->middleware('permission:settings.profile,settings.sms_gateway,settings.automation,settings.rbac,settings.backup,settings.audit_logs,billing.gateways')->group(function () {
            // 1. ISP Company Profile & Branding
            Route::get('/profile', [TenantProfileController::class, 'index'])->name('profile');
            Route::post('/profile', [TenantProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile/test-email', [TenantProfileController::class, 'testEmailConnection'])->name('profile.test-email');
            Route::get('/profile/print', [TenantProfileController::class, 'printCertificate'])->name('profile.print');

            // 2. SMS & Notification Templates & Delivery Logs
            Route::get('/sms-gateway', [TenantSmsGatewayController::class, 'index'])->name('sms-gateway');
            Route::put('/sms-gateway/templates/{id}', [TenantSmsGatewayController::class, 'updateTemplate'])->whereNumber('id')->name('sms-gateway.templates.update');
            Route::post('/sms-gateway/templates/{id}/toggle-status', [TenantSmsGatewayController::class, 'toggleTemplateStatus'])->whereNumber('id')->name('sms-gateway.templates.toggle-status');
            Route::post('/sms-gateway/test', [TenantSmsGatewayController::class, 'testDispatch'])->name('sms-gateway.test');
            Route::get('/sms-gateway/export', [TenantSmsGatewayController::class, 'exportCsv'])->name('sms-gateway.export');
            Route::get('/sms-gateway/print', [TenantSmsGatewayController::class, 'printReport'])->name('sms-gateway.print');
            Route::delete('/sms-gateway/logs/clear', [TenantSmsGatewayController::class, 'clearLogs'])->name('sms-gateway.logs.clear');
            Route::delete('/sms-gateway/logs/{id}', [TenantSmsGatewayController::class, 'deleteLog'])->whereNumber('id')->name('sms-gateway.logs.delete');

            // 3. Automation & Auto-Cut Rules
            Route::get('/automation', [TenantAutomationController::class, 'index'])->name('automation');
            Route::post('/automation', [TenantAutomationController::class, 'update'])->name('automation.update');
            Route::post('/automation/run/{task}', [TenantAutomationController::class, 'runJob'])->name('automation.run');
            Route::get('/automation/export', [TenantAutomationController::class, 'exportCsv'])->name('automation.export');
            Route::get('/automation/print', [TenantAutomationController::class, 'printPolicy'])->name('automation.print');
            Route::delete('/automation/logs/clear', [TenantAutomationController::class, 'clearLogs'])->name('automation.logs.clear');
            Route::delete('/automation/logs/{id}', [TenantAutomationController::class, 'deleteLog'])->whereNumber('id')->name('automation.logs.delete');

            // 4. Role-Based Access Control (RBAC) & Permissions
            Route::get('/rbac', [TenantRbacController::class, 'index'])->name('rbac');
            Route::post('/rbac', [TenantRbacController::class, 'store'])->name('rbac.store');
            Route::get('/rbac/{id}', [TenantRbacController::class, 'show'])->whereNumber('id')->name('rbac.show');
            Route::put('/rbac/{id}', [TenantRbacController::class, 'update'])->whereNumber('id')->name('rbac.update');
            Route::delete('/rbac/{id}', [TenantRbacController::class, 'destroy'])->whereNumber('id')->name('rbac.destroy');
            Route::post('/rbac/{id}/clone', [TenantRbacController::class, 'cloneRole'])->whereNumber('id')->name('rbac.clone');
            Route::post('/rbac/{id}/toggle-status', [TenantRbacController::class, 'toggleStatus'])->whereNumber('id')->name('rbac.toggle-status');
            Route::get('/rbac/export', [TenantRbacController::class, 'exportCsv'])->name('rbac.export');
            Route::get('/rbac/print', [TenantRbacController::class, 'printReport'])->name('rbac.print');

            // 5. System Backup & Maintenance
            Route::get('/backup', [TenantBackupController::class, 'index'])->name('backup');
            Route::post('/backup/snapshot', [TenantBackupController::class, 'createSnapshot'])->name('backup.snapshot');
            Route::post('/backup/{id}/verify', [TenantBackupController::class, 'verifySnapshot'])->whereNumber('id')->name('backup.verify');
            Route::get('/backup/{id}/download', [TenantBackupController::class, 'download'])->whereNumber('id')->name('backup.download');
            Route::post('/backup/{id}/restore', [TenantBackupController::class, 'restore'])->whereNumber('id')->name('backup.restore');
            Route::delete('/backup/{id}', [TenantBackupController::class, 'destroy'])->whereNumber('id')->name('backup.destroy');
            Route::post('/backup/maintenance/{action}', [TenantBackupController::class, 'runMaintenance'])->name('backup.maintenance');
            Route::get('/backup/export', [TenantBackupController::class, 'exportCsv'])->name('backup.export');
            Route::get('/backup/print', [TenantBackupController::class, 'printReport'])->name('backup.print');

            // 6. Activity & Audit Trail
            Route::get('/audit-logs', [TenantAuditLogController::class, 'index'])->name('audit-logs');
            Route::get('/audit-logs/{id}', [TenantAuditLogController::class, 'show'])->whereNumber('id')->name('audit-logs.show');
            Route::delete('/audit-logs/{id}', [TenantAuditLogController::class, 'destroy'])->whereNumber('id')->name('audit-logs.destroy');
            Route::post('/audit-logs/purge', [TenantAuditLogController::class, 'purge'])->name('audit-logs.purge');
            Route::get('/audit-logs/export', [TenantAuditLogController::class, 'exportCsv'])->name('audit-logs.export');
            Route::get('/audit-logs/print', [TenantAuditLogController::class, 'printReport'])->name('audit-logs.print');

            // 7. Payment Gateways (bKash & Nagad Merchant API)
            Route::get('/payment-gateways', [TenantPaymentGatewayController::class, 'index'])->name('payment-gateways');
            Route::post('/payment-gateways/bkash', [TenantPaymentGatewayController::class, 'updateBkash'])->name('payment-gateways.bkash');
            Route::post('/payment-gateways/nagad', [TenantPaymentGatewayController::class, 'updateNagad'])->name('payment-gateways.nagad');
            Route::post('/payment-gateways/bank-qr', [TenantPaymentGatewayController::class, 'updateBankQr'])->name('payment-gateways.bank-qr');
            Route::post('/payment-gateways/toggle/{slug}', [TenantPaymentGatewayController::class, 'toggleGateway'])->name('payment-gateways.toggle');
            Route::post('/payment-gateways/test/bkash', [TenantPaymentGatewayController::class, 'testBkash'])->name('payment-gateways.test.bkash');
            Route::post('/payment-gateways/test/nagad', [TenantPaymentGatewayController::class, 'testNagad'])->name('payment-gateways.test.nagad');
            Route::get('/gateways', fn() => redirect()->route('tenant.settings.payment-gateways'));
        });

    });

});
