<?php

use App\Http\Controllers\Owner\OwnerActivityLogController;
use App\Http\Controllers\Owner\OwnerAuthController;
use App\Http\Controllers\Owner\OwnerAutomationController;
use App\Http\Controllers\Owner\OwnerBillingController;
use App\Http\Controllers\Owner\OwnerDashboardController;
use App\Http\Controllers\Owner\OwnerMailSettingController;
use App\Http\Controllers\Owner\OwnerNetworkEngineController;
use App\Http\Controllers\Owner\OwnerNotificationController;
use App\Http\Controllers\Owner\OwnerPaymentController;
use App\Http\Controllers\Owner\OwnerPaymentGatewayController;
use App\Http\Controllers\Owner\OwnerPlanController;
use App\Http\Controllers\Owner\OwnerRevenueReportController;
use App\Http\Controllers\Owner\OwnerSecurityBackupController;
use App\Http\Controllers\Owner\OwnerSettingController;
use App\Http\Controllers\Owner\OwnerSmsGatewayController;
use App\Http\Controllers\Owner\OwnerSmsLogController;
use App\Http\Controllers\Owner\OwnerSubscriptionController;
use App\Http\Controllers\Owner\OwnerTenantController;
use App\Http\Controllers\Owner\OwnerTicketController;
use App\Http\Controllers\Owner\OwnerTransactionController;
use App\Http\Controllers\Owner\OwnerUserController;
use App\Http\Controllers\Owner\OwnerWalletController;
use App\Http\Middleware\OwnerMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Owner (SaaS Super Admin) Routes
|--------------------------------------------------------------------------
| Prefix: owner
| Domain / URL: https://somitysoft.com/owner/*
*/

Route::prefix('owner')->name('owner.')->group(function () {
    
    // Guest Owner Authentication Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [OwnerAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [OwnerAuthController::class, 'login'])->name('login.submit');
    });

    // Authenticated Owner Routes
    Route::middleware([OwnerMiddleware::class])->group(function () {
        Route::post('/logout', [OwnerAuthController::class, 'logout'])->name('logout');
        
        // 1. Dashboard Overview
        Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');

        // 2. SaaS Subscription Plans
        Route::get('/plans', [OwnerPlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [OwnerPlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [OwnerPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [OwnerPlanController::class, 'destroy'])->name('plans.destroy');
        Route::post('/plans/{plan}/toggle', [OwnerPlanController::class, 'toggle'])->name('plans.toggle');
        Route::post('/plans/seed-defaults', [OwnerPlanController::class, 'seedDefaults'])->name('plans.seed-defaults');

        // 3. Tenant Management
        Route::resource('tenants', OwnerTenantController::class);
        Route::post('/tenants/{tenant}/toggle-status', [OwnerTenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
        Route::post('/tenants/{tenant}/extend-subscription', [OwnerTenantController::class, 'extendSubscription'])->name('tenants.extend-subscription');
        Route::get('/tenants/{tenant}/impersonate', [OwnerTenantController::class, 'impersonate'])->name('tenants.impersonate');

        // 3.1 Global User Management & Password Control
        Route::get('/users', [OwnerUserController::class, 'index'])->name('users.index');
        Route::post('/users', [OwnerUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [OwnerUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [OwnerUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/toggle-status', [OwnerUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [OwnerUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::get('/users/{user}/impersonate', [OwnerUserController::class, 'impersonate'])->name('users.impersonate');

        // 4. Invoices & Billing
        Route::get('/billing', [OwnerBillingController::class, 'index'])->name('billing.index');
        Route::get('/invoices', [OwnerBillingController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [OwnerBillingController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/print', [OwnerBillingController::class, 'printInvoice'])->name('invoices.print');
        Route::post('/invoices/{invoice}/send-email', [OwnerBillingController::class, 'sendInvoiceEmail'])->name('invoices.send-email');
        Route::post('/billing/invoices', [OwnerBillingController::class, 'store'])->name('billing.store');
        Route::put('/billing/invoices/{invoice}', [OwnerBillingController::class, 'update'])->name('billing.update');
        Route::post('/billing/invoices/{invoice}/toggle-status', [OwnerBillingController::class, 'toggleStatus'])->name('billing.toggle-status');
        Route::delete('/billing/invoices/{invoice}', [OwnerBillingController::class, 'destroy'])->name('billing.destroy');
        Route::post('/billing/run-engine', [OwnerBillingController::class, 'runEngine'])->name('billing.run-engine');

        // 5. Dedicated Tenant Subscriptions
        Route::get('/subscriptions', [OwnerSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions', [OwnerSubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::post('/subscriptions/{subscription}/renew', [OwnerSubscriptionController::class, 'renew'])->name('subscriptions.renew');
        Route::post('/subscriptions/{subscription}/status', [OwnerSubscriptionController::class, 'updateStatus'])->name('subscriptions.status');
        Route::post('/billing/subscriptions/{subscription}/renew', [OwnerSubscriptionController::class, 'renew'])->name('billing.subscriptions.renew');
        Route::post('/billing/subscriptions/{subscription}/status', [OwnerSubscriptionController::class, 'updateStatus'])->name('billing.subscriptions.status');

        // 6. Dedicated Settled Payments
        Route::get('/payments', [OwnerPaymentController::class, 'index'])->name('payments.index');

        // 7. Dedicated Gateway Transactions
        Route::get('/transactions', [OwnerTransactionController::class, 'index'])->name('transactions.index');

        // 8. Dedicated Tenant Wallets
        Route::get('/wallets', [OwnerWalletController::class, 'index'])->name('wallets.index');
        Route::post('/wallets/{tenant}/adjust', [OwnerWalletController::class, 'adjust'])->name('wallets.adjust');
        Route::post('/billing/wallets/{tenant}/adjust', [OwnerWalletController::class, 'adjust'])->name('billing.wallets.adjust');

        // 9. Dedicated Automation & Cron Control Hub
        Route::get('/automation', [OwnerAutomationController::class, 'index'])->name('automation.index');
        Route::post('/automation/policy', [OwnerAutomationController::class, 'updatePolicy'])->name('automation.policy.update');
        Route::post('/automation/dispatch/{task}', [OwnerAutomationController::class, 'dispatchTask'])->name('automation.dispatch');
        Route::post('/automation/failed-jobs/{id}/retry', [OwnerAutomationController::class, 'retryFailedJob'])->name('automation.failed-jobs.retry');
        Route::post('/automation/failed-jobs/retry-all', [OwnerAutomationController::class, 'retryAllFailedJobs'])->name('automation.failed-jobs.retry-all');
        Route::delete('/automation/failed-jobs/{id}', [OwnerAutomationController::class, 'deleteFailedJob'])->name('automation.failed-jobs.delete');
        Route::post('/automation/failed-jobs/flush', [OwnerAutomationController::class, 'flushAllFailedJobs'])->name('automation.failed-jobs.flush');

        // 10. Payment Gateways (PGW) - Dedicated Menu
        Route::get('/payment-gateways', [OwnerPaymentGatewayController::class, 'index'])->name('payment-gateways.index');
        Route::post('/payment-gateways', [OwnerPaymentGatewayController::class, 'update'])->name('payment-gateways.update');
        Route::post('/payment-gateways/test', [OwnerPaymentGatewayController::class, 'test'])->name('payment-gateways.test');

        // 11. SMS Gateways - Dedicated Menu
        Route::get('/sms-gateways', [OwnerSmsGatewayController::class, 'index'])->name('sms-gateways.index');
        Route::post('/sms-gateways', [OwnerSmsGatewayController::class, 'update'])->name('sms-gateways.update');
        Route::post('/sms-gateways/test', [OwnerSmsGatewayController::class, 'test'])->name('sms-gateways.test');
        Route::get('/sms-gateways/balance', [OwnerSmsGatewayController::class, 'checkBalance'])->name('sms-gateways.balance');

        // 12. Mail & SMTP - Dedicated Menu
        Route::get('/mail-settings', [OwnerMailSettingController::class, 'index'])->name('mail-settings.index');
        Route::post('/mail-settings', [OwnerMailSettingController::class, 'update'])->name('mail-settings.update');
        Route::post('/mail-settings/test', [OwnerMailSettingController::class, 'test'])->name('mail-settings.test');

        // 13. Security & Backup - Dedicated Menu
        Route::get('/security-backup', [OwnerSecurityBackupController::class, 'index'])->name('security-backup.index');
        Route::post('/security-backup', [OwnerSecurityBackupController::class, 'update'])->name('security-backup.update');
        Route::post('/security-backup/trigger', [OwnerSecurityBackupController::class, 'triggerBackup'])->name('security-backup.trigger');
        Route::post('/security-backup/test-restore/{filename}', [OwnerSecurityBackupController::class, 'testRestore'])->name('security-backup.test-restore');
        Route::post('/security-backup/upload-offsite/{filename}', [OwnerSecurityBackupController::class, 'uploadOffsite'])->name('security-backup.upload-offsite');
        Route::get('/security-backup/download/{filename}', [OwnerSecurityBackupController::class, 'downloadBackup'])->name('security-backup.download');
        Route::post('/security-backup/delete/{filename}', [OwnerSecurityBackupController::class, 'deleteBackup'])->name('security-backup.delete');

        // 14. Carrier Network Engines & Infrastructure Hub
        Route::prefix('network-engines')->name('network-engines.')->group(function () {
            Route::get('/', [OwnerNetworkEngineController::class, 'index'])->name('index');
            Route::get('/settings', [OwnerNetworkEngineController::class, 'settings'])->name('settings');
            Route::post('/settings', [OwnerNetworkEngineController::class, 'updateSettings'])->name('settings.update');
            Route::get('/audit-logs', [OwnerNetworkEngineController::class, 'auditLogs'])->name('audit-logs');
            Route::post('/generate-script', [OwnerNetworkEngineController::class, 'generateScript'])->name('generate-script');
            Route::post('/probe-device', [OwnerNetworkEngineController::class, 'probeDevice'])->name('probe-device');
            Route::post('/test-readiness', [OwnerNetworkEngineController::class, 'testReadiness'])->name('test-readiness');
            Route::post('/generate-keypair', [OwnerNetworkEngineController::class, 'generateKeypair'])->name('generate-keypair');
        });

        // 14. Financial Revenue & Income Report
        Route::get('/revenue-report', [OwnerRevenueReportController::class, 'index'])->name('revenue-report.index');
        Route::get('/revenue-report/export', [OwnerRevenueReportController::class, 'exportCsv'])->name('reports.revenue.export');
        Route::get('/reports/revenue', [OwnerRevenueReportController::class, 'index'])->name('reports.revenue');

        // 15. Tenant Activity & Audit Logs
        Route::get('/activity-logs', [OwnerActivityLogController::class, 'index'])->name('activity-logs.index');

        // 16. SMS Consumption & Delivery Logs
        Route::get('/sms-logs', [OwnerSmsLogController::class, 'index'])->name('sms-logs.index');

        // 16. General Platform Settings
        Route::get('/settings', [OwnerSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [OwnerSettingController::class, 'update'])->name('settings.update');

        // 17. Helpdesk & Support Tickets
        Route::get('/tickets', [OwnerTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [OwnerTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [OwnerTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('/tickets/{ticket}/status', [OwnerTicketController::class, 'updateStatus'])->name('tickets.status');
        Route::post('/tickets/{ticket}/priority', [OwnerTicketController::class, 'updatePriority'])->name('tickets.priority');
        Route::delete('/tickets/{ticket}', [OwnerTicketController::class, 'destroy'])->name('tickets.destroy');

        // 18. Live Header Notifications
        Route::get('/notifications', [OwnerNotificationController::class, 'unreadList'])->name('notifications.index');
        Route::post('/notifications/{id}/read', [OwnerNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [OwnerNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });

    // Leave Impersonation
    Route::get('/leave-impersonation', [OwnerTenantController::class, 'leaveImpersonation'])->name('impersonate.leave');
});
