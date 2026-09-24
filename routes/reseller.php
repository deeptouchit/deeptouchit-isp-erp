<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Reseller\ResellerBandwidthController;
use App\Http\Controllers\Reseller\ResellerCollectionController;
use App\Http\Controllers\Reseller\ResellerCustomerController;
use App\Http\Controllers\Reseller\ResellerCustomerInvoiceController;
use App\Http\Controllers\Reseller\ResellerDashboardController;
use App\Http\Controllers\Reseller\ResellerInvoiceController;
use App\Http\Controllers\Reseller\ResellerLedgerController;
use App\Http\Controllers\Reseller\ResellerMarginController;
use App\Http\Controllers\Reseller\ResellerPackageController;
use App\Http\Controllers\Reseller\ResellerProfileController;
use App\Http\Controllers\Reseller\ResellerRechargeController;
use App\Http\Controllers\Reseller\ResellerStaffController;
use App\Http\Controllers\Reseller\ResellerTicketController;
use App\Http\Controllers\Tenant\Auth\TenantAuthController;
use App\Http\Middleware\EnsureResellerAdmin;
use App\Http\Middleware\EnsureResellerUser;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ISP Reseller / Sub-ISP Partner Portal Routes
|--------------------------------------------------------------------------
| Access: Sub-ISP Partners, Reseller Admins, Reseller Staff & Field Technicians
| Prefix / URL: /reseller/*
*/

Route::middleware(['web'])->group(function () {

    // 1. Reseller Guest Authentication (Redirect to Single Unified Portal Login)
    Route::middleware('guest')->group(function () {
        Route::get('/reseller/login', fn() => redirect()->route('tenant.login'))->name('reseller.login');
        Route::get('/r/{code}/login', fn() => redirect()->route('tenant.login'))->name('reseller.code.login');
    });

    // 2. Authenticated Reseller Portal Routes (All Reseller Roles)
    Route::middleware(['auth', EnsureResellerUser::class])->group(function () {

        // Logout
        Route::post('/reseller/logout', [TenantAuthController::class, 'logout'])->name('reseller.logout');

        // Landing Dashboard
        Route::get('/reseller/dashboard', [ResellerDashboardController::class, 'index'])->name('reseller.dashboard');
        Route::get('/reseller', fn() => redirect()->route('reseller.dashboard'))->name('reseller.index');

        // Live Header Notifications
        Route::get('/reseller/notifications', [NotificationController::class, 'unreadList'])->name('reseller.notifications.unread');
        Route::post('/reseller/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('reseller.notifications.read');
        Route::post('/reseller/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('reseller.notifications.mark-all-read');

        // Customer Management (Collectors & Techs have access to view, search & collect)
        Route::get('/reseller/customers', [ResellerCustomerController::class, 'index'])->name('reseller.customers.index');
        Route::get('/reseller/customers/create', [ResellerCustomerController::class, 'create'])->name('reseller.customers.create');
        Route::post('/reseller/customers', [ResellerCustomerController::class, 'store'])->name('reseller.customers.store');
        Route::get('/reseller/customers/bulk-payments', [ResellerCustomerController::class, 'bulkPaymentPage'])->name('reseller.customers.bulk-payments');
        Route::get('/reseller/customers/bulk-payments/print', [ResellerCustomerController::class, 'printBulkPayments'])->name('reseller.customers.bulk-payments.print');
        Route::post('/reseller/customers/bulk-payments/process', [ResellerCustomerController::class, 'processBulkPayment'])->name('reseller.customers.bulk-payments.process');
        Route::get('/reseller/customers/export', [ResellerCustomerController::class, 'exportCsv'])->name('reseller.customers.export');
        Route::get('/reseller/customers/print', [ResellerCustomerController::class, 'printReport'])->name('reseller.customers.print');
        Route::get('/reseller/customers/{id}', [ResellerCustomerController::class, 'show'])->whereNumber('id')->name('reseller.customers.show');
        Route::get('/reseller/customers/{id}/edit', [ResellerCustomerController::class, 'edit'])->whereNumber('id')->name('reseller.customers.edit');
        Route::put('/reseller/customers/{id}', [ResellerCustomerController::class, 'update'])->whereNumber('id')->name('reseller.customers.update');
        Route::post('/reseller/customers/{id}/toggle-status', [ResellerCustomerController::class, 'toggleStatus'])->whereNumber('id')->name('reseller.customers.toggle-status');
        Route::post('/reseller/customers/{id}/toggle-mikrotik', [ResellerCustomerController::class, 'toggleMikrotikSecret'])->whereNumber('id')->name('reseller.customers.toggle-mikrotik');
        Route::post('/reseller/customers/{id}/renew', [ResellerCustomerController::class, 'renewSubscription'])->whereNumber('id')->name('reseller.customers.renew');
        Route::post('/reseller/customers/{id}/sync-mikrotik', [ResellerCustomerController::class, 'syncMikrotik'])->whereNumber('id')->name('reseller.customers.sync-mikrotik');
        Route::post('/reseller/customers/{id}/ping', [ResellerCustomerController::class, 'pingCustomer'])->whereNumber('id')->name('reseller.customers.ping');
        Route::post('/reseller/customers/{id}/pay-bill', [ResellerCustomerController::class, 'payBill'])->whereNumber('id')->name('reseller.customers.pay-bill');
        Route::post('/reseller/customers/{id}/payment', [ResellerCustomerController::class, 'payBill'])->whereNumber('id')->name('reseller.customers.payment');
        Route::get('/reseller/customers/{id}/bangla-qr', [ResellerCustomerController::class, 'showBanglaQrPage'])->whereNumber('id')->name('reseller.customers.bangla-qr');
        Route::get('/reseller/customers/{id}/bkash/callback', [ResellerCustomerController::class, 'handleBkashPaymentCallback'])->whereNumber('id')->name('reseller.customers.bkash.callback');
        Route::post('/reseller/customers/{id}/disconnect', [ResellerCustomerController::class, 'disconnectSession'])->whereNumber('id')->name('reseller.customers.disconnect');
        Route::post('/reseller/customers/{id}/kick-session', [ResellerCustomerController::class, 'disconnectSession'])->whereNumber('id')->name('reseller.customers.kick-session');
        Route::post('/reseller/customers/{id}/update-pppoe', [ResellerCustomerController::class, 'updatePppoe'])->whereNumber('id')->name('reseller.customers.update-pppoe');
        Route::post('/reseller/customers/{id}/update-status', [ResellerCustomerController::class, 'updateStatus'])->whereNumber('id')->name('reseller.customers.update-status');
        Route::post('/reseller/customers/{id}/update-package', [ResellerCustomerController::class, 'updatePackage'])->whereNumber('id')->name('reseller.customers.update-package');
        Route::post('/reseller/customers/sync-online-sessions', [ResellerCustomerController::class, 'syncOnlineSessions'])->name('reseller.customers.sync-online');
        Route::get('/reseller/customers/{id}/traffic', [ResellerCustomerController::class, 'getTraffic'])->whereNumber('id')->name('reseller.customers.traffic');
        Route::get('/reseller/customers/{id}/usage-history', [ResellerCustomerController::class, 'getUsageHistory'])->whereNumber('id')->name('reseller.customers.usage-history');

        // Collection Reports & Money Receipts (Collector & Admin)
        Route::get('/reseller/collections', [ResellerCollectionController::class, 'index'])->name('reseller.collections.index');
        Route::get('/reseller/collections/{id}/receipt', [ResellerCollectionController::class, 'receipt'])->whereNumber('id')->name('reseller.collections.receipt');
        Route::post('/reseller/collections/{id}/approve', [ResellerCollectionController::class, 'approvePayment'])->whereNumber('id')->name('reseller.collections.approve');
        Route::post('/reseller/collections/{id}/reject', [ResellerCollectionController::class, 'rejectPayment'])->whereNumber('id')->name('reseller.collections.reject');
        Route::get('/reseller/collections/export', [ResellerCollectionController::class, 'exportCsv'])->name('reseller.collections.export');
        Route::get('/reseller/collections/print', [ResellerCollectionController::class, 'printReport'])->name('reseller.collections.print');

        // Support & Tickets (All Roles)
        Route::get('/reseller/tickets', [ResellerTicketController::class, 'index'])->name('reseller.tickets.index');
        Route::get('/reseller/tickets/topbar-messages', [ResellerTicketController::class, 'getTopbarMessages'])->name('reseller.tickets.topbar-messages');
        Route::post('/reseller/tickets', [ResellerTicketController::class, 'store'])->name('reseller.tickets.store');
        Route::get('/reseller/tickets/export', [ResellerTicketController::class, 'exportCsv'])->name('reseller.tickets.export');
        Route::get('/reseller/tickets/print', [ResellerTicketController::class, 'printReport'])->name('reseller.tickets.print');
        Route::get('/reseller/tickets/{id}', [ResellerTicketController::class, 'show'])->whereNumber('id')->name('reseller.tickets.show');
        Route::post('/reseller/tickets/{id}/reply', [ResellerTicketController::class, 'reply'])->whereNumber('id')->name('reseller.tickets.reply');
        Route::post('/reseller/tickets/{id}/close', [ResellerTicketController::class, 'closeTicket'])->whereNumber('id')->name('reseller.tickets.close');

        // Partner Profile & Settings (All Roles)
        Route::get('/reseller/profile', [ResellerProfileController::class, 'index'])->name('reseller.profile');
        Route::put('/reseller/profile', [ResellerProfileController::class, 'update'])->name('reseller.profile.update');
        Route::post('/reseller/profile/password', [ResellerProfileController::class, 'updatePassword'])->name('reseller.profile.password');
        Route::get('/reseller/profile/export', [ResellerProfileController::class, 'exportProfileCsv'])->name('reseller.profile.export');
        Route::get('/reseller/profile/certificate', [ResellerProfileController::class, 'printCertificate'])->name('reseller.profile.certificate');

        // Bandwidth & MRTG Telemetry (Admin & Technicians)
        Route::get('/reseller/bandwidth', [ResellerBandwidthController::class, 'index'])->name('reseller.bandwidth.index');
        Route::get('/reseller/bandwidth/traffic', [ResellerBandwidthController::class, 'trafficTelemetry'])->name('reseller.bandwidth.traffic');
        Route::post('/reseller/bandwidth/request-upgrade', [ResellerBandwidthController::class, 'requestUpgrade'])->name('reseller.bandwidth.request-upgrade');
        Route::get('/reseller/bandwidth/export', [ResellerBandwidthController::class, 'exportCsv'])->name('reseller.bandwidth.export');
        Route::get('/reseller/bandwidth/print', [ResellerBandwidthController::class, 'printReport'])->name('reseller.bandwidth.print');

        // 3. Reseller Admin ONLY Protected Routes (Financials, Packages, Staff & Margins)
        Route::middleware([EnsureResellerAdmin::class])->group(function () {
            // Customer Deletion
            Route::delete('/reseller/customers/{id}', [ResellerCustomerController::class, 'destroy'])->whereNumber('id')->name('reseller.customers.destroy');

            // Assigned Packages & Wholesale Rates
            Route::get('/reseller/packages', [ResellerPackageController::class, 'index'])->name('reseller.packages.index');
            Route::get('/reseller/packages/export', [ResellerPackageController::class, 'exportCsv'])->name('reseller.packages.export');
            Route::get('/reseller/packages/print', [ResellerPackageController::class, 'printReport'])->name('reseller.packages.print');
            Route::get('/reseller/packages/{id}', [ResellerPackageController::class, 'show'])->whereNumber('id')->name('reseller.packages.show');

            // Margin & Profit Rates
            Route::get('/reseller/margins', [ResellerMarginController::class, 'index'])->name('reseller.margins.index');
            Route::get('/reseller/margins/export', [ResellerMarginController::class, 'exportCsv'])->name('reseller.margins.export');
            Route::get('/reseller/margins/print', [ResellerMarginController::class, 'printReport'])->name('reseller.margins.print');
            Route::get('/reseller/margins/{id}', [ResellerMarginController::class, 'show'])->whereNumber('id')->name('reseller.margins.show');

            // Wallet Recharge & Top-up Ledger
            Route::get('/reseller/recharge', [ResellerRechargeController::class, 'index'])->name('reseller.recharge.index');
            Route::post('/reseller/recharge', [ResellerRechargeController::class, 'store'])->name('reseller.recharge.store');
            Route::post('/reseller/recharge/online', [ResellerRechargeController::class, 'onlineRecharge'])->name('reseller.recharge.online');
            Route::post('/reseller/recharge/bkash/initiate', [ResellerRechargeController::class, 'initiateBkashCheckout'])->name('reseller.recharge.bkash.initiate');
            Route::get('/reseller/recharge/bkash/callback', [ResellerRechargeController::class, 'handleBkashCallback'])->name('reseller.recharge.bkash.callback');
            Route::get('/reseller/recharge/{id}/receipt', [ResellerRechargeController::class, 'receipt'])->whereNumber('id')->name('reseller.recharge.receipt');
            Route::get('/reseller/recharge/export', [ResellerRechargeController::class, 'exportCsv'])->name('reseller.recharge.export');
            Route::get('/reseller/recharge/print', [ResellerRechargeController::class, 'printReport'])->name('reseller.recharge.print');

            // Wholesale Invoices & Billing
            Route::get('/reseller/invoices', [ResellerInvoiceController::class, 'index'])->name('reseller.invoices.index');
            Route::get('/reseller/invoices/{id}', [ResellerInvoiceController::class, 'show'])->whereNumber('id')->name('reseller.invoices.show');
            Route::post('/reseller/invoices/{id}/pay-wallet', [ResellerInvoiceController::class, 'payWithWallet'])->whereNumber('id')->name('reseller.invoices.pay-wallet');
            Route::get('/reseller/invoices/export', [ResellerInvoiceController::class, 'exportCsv'])->name('reseller.invoices.export');
            Route::get('/reseller/invoices/print-report', [ResellerInvoiceController::class, 'printReport'])->name('reseller.invoices.print-report');
            Route::get('/reseller/invoices/{id}/print', [ResellerInvoiceController::class, 'printInvoice'])->whereNumber('id')->name('reseller.invoices.print');

            // Customer Invoices (Retail Monthly Billing)
            Route::get('/reseller/customer-invoices', [ResellerCustomerInvoiceController::class, 'index'])->name('reseller.customer-invoices.index');
            Route::get('/reseller/customer-invoices/export', [ResellerCustomerInvoiceController::class, 'exportCsv'])->name('reseller.customer-invoices.export');
            Route::get('/reseller/customer-invoices/print-report', [ResellerCustomerInvoiceController::class, 'printReport'])->name('reseller.customer-invoices.print-report');
            Route::get('/reseller/customer-invoices/{id}', [ResellerCustomerInvoiceController::class, 'show'])->whereNumber('id')->name('reseller.customer-invoices.show');
            Route::post('/reseller/customer-invoices/{id}/pay', [ResellerCustomerInvoiceController::class, 'markPaid'])->whereNumber('id')->name('reseller.customer-invoices.pay');
            Route::get('/reseller/customer-invoices/{id}/print', [ResellerCustomerInvoiceController::class, 'printInvoice'])->whereNumber('id')->name('reseller.customer-invoices.print');

            // Wallet Transaction Ledger
            Route::get('/reseller/ledger', [ResellerLedgerController::class, 'index'])->name('reseller.ledger.index');
            Route::get('/reseller/ledger/{id}', [ResellerLedgerController::class, 'show'])->whereNumber('id')->name('reseller.ledger.show');
            Route::get('/reseller/ledger/export', [ResellerLedgerController::class, 'exportCsv'])->name('reseller.ledger.export');
            Route::get('/reseller/ledger/print', [ResellerLedgerController::class, 'printReport'])->name('reseller.ledger.print');

            // Staff & Collectors Directory
            Route::get('/reseller/staff', [ResellerStaffController::class, 'index'])->name('reseller.staff.index');
            Route::post('/reseller/staff', [ResellerStaffController::class, 'store'])->name('reseller.staff.store');
            Route::put('/reseller/staff/{id}', [ResellerStaffController::class, 'update'])->whereNumber('id')->name('reseller.staff.update');
            Route::delete('/reseller/staff/{id}', [ResellerStaffController::class, 'destroy'])->whereNumber('id')->name('reseller.staff.destroy');
            Route::post('/reseller/staff/{id}/toggle-status', [ResellerStaffController::class, 'toggleStatus'])->whereNumber('id')->name('reseller.staff.toggle-status');
            Route::get('/reseller/staff/export', [ResellerStaffController::class, 'exportCsv'])->name('reseller.staff.export');
            Route::get('/reseller/staff/print', [ResellerStaffController::class, 'printReport'])->name('reseller.staff.print');
        });

    });

});
