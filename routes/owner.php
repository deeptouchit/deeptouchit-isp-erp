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

Route::get('/owner', fn() => redirect()->route('owner.login'))->name('owner.index');

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
        Route::get('/dashboard', [OwnerDashboardController::class, 'index'])
            ->middleware('owner.capability:dashboard.view')
            ->name('dashboard');

        // 2. SaaS Subscription Plans
        Route::get('/plans', [OwnerPlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [OwnerPlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [OwnerPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [OwnerPlanController::class, 'destroy'])
            ->middleware('owner.capability:subscriptions.manage')
            ->name('plans.destroy');
        Route::post('/plans/{plan}/toggle', [OwnerPlanController::class, 'toggle'])->name('plans.toggle');
        Route::post('/plans/seed-defaults', [OwnerPlanController::class, 'seedDefaults'])->name('plans.seed-defaults');

        // 3. Tenant Management
        Route::get('/tenants/create', [OwnerTenantController::class, 'create'])
            ->middleware('owner.capability:tenants.create')
            ->name('tenants.create');
        Route::resource('tenants', OwnerTenantController::class)
            ->except(['destroy', 'create', 'store', 'edit', 'update'])
            ->middleware('owner.capability:tenants.view');
        Route::post('/tenants', [OwnerTenantController::class, 'store'])
            ->middleware('owner.capability:tenants.create')
            ->name('tenants.store');
        Route::get('/tenants/{tenant}/edit', [OwnerTenantController::class, 'edit'])
            ->middleware('owner.capability:tenants.update')
            ->name('tenants.edit');
        Route::put('/tenants/{tenant}', [OwnerTenantController::class, 'update'])
            ->middleware('owner.capability:tenants.update')
            ->name('tenants.update');
        Route::post('/tenants/{tenant}/verify-domain', [OwnerTenantController::class, 'verifyDomain'])
            ->middleware(['owner.capability:tenants.update', 'throttle:10,1'])
            ->name('tenants.verify-domain');
        Route::delete('/tenants/{tenant}', [OwnerTenantController::class, 'destroy'])
            ->middleware('owner.capability:tenants.delete')
            ->name('tenants.destroy');
        Route::post('/tenants/{tenant}/toggle-status', [OwnerTenantController::class, 'toggleStatus'])
            ->middleware('owner.capability:tenants.update')
            ->name('tenants.toggle-status');
        Route::post('/tenants/{tenant}/extend-subscription', [OwnerTenantController::class, 'extendSubscription'])
            ->middleware('owner.capability:subscriptions.manage')
            ->name('tenants.extend-subscription');
        Route::post('/tenants/{tenant}/impersonate', [OwnerTenantController::class, 'impersonate'])
            ->middleware(['owner.capability:tenants.impersonate', 'throttle:10,1'])
            ->name('tenants.impersonate');

        // 3.1 Global User Management & Password Control
        Route::get('/users', [OwnerUserController::class, 'index'])->name('users.index');
        Route::post('/users', [OwnerUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [OwnerUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [OwnerUserController::class, 'destroy'])
            ->middleware('owner.capability:users.delete')
            ->name('users.destroy');
        Route::post('/users/{user}/toggle-status', [OwnerUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [OwnerUserController::class, 'resetPassword'])
            ->middleware(['owner.capability:users.reset-password', 'throttle:10,1'])
            ->name('users.reset-password');
        Route::post('/users/{user}/impersonate', [OwnerUserController::class, 'impersonate'])
            ->middleware(['owner.capability:users.impersonate', 'throttle:10,1'])
            ->name('users.impersonate');

        // 4. Invoices & Billing
        Route::get('/billing', [OwnerBillingController::class, 'index'])
            ->middleware('owner.capability:billing.view')->name('billing.index');
        Route::get('/invoices', [OwnerBillingController::class, 'index'])
            ->middleware('owner.capability:billing.view')->name('invoices.index');
        Route::get('/invoices/{invoice}', [OwnerBillingController::class, 'show'])
            ->middleware('owner.capability:billing.view')->name('invoices.show');
        Route::get('/invoices/{invoice}/print', [OwnerBillingController::class, 'printInvoice'])
            ->middleware('owner.capability:billing.view')->name('invoices.print');
        Route::post('/invoices/{invoice}/send-email', [OwnerBillingController::class, 'sendInvoiceEmail'])
            ->middleware(['owner.capability:billing.manage', 'throttle:10,1'])->name('invoices.send-email');
        Route::post('/billing/invoices', [OwnerBillingController::class, 'store'])
            ->middleware('owner.capability:billing.manage')->name('billing.store');
        Route::put('/billing/invoices/{invoice}', [OwnerBillingController::class, 'update'])
            ->middleware('owner.capability:billing.manage')->name('billing.update');
        Route::post('/billing/invoices/{invoice}/toggle-status', [OwnerBillingController::class, 'toggleStatus'])
            ->middleware('owner.capability:billing.manage')->name('billing.toggle-status');
        Route::delete('/billing/invoices/{invoice}', [OwnerBillingController::class, 'destroy'])
            ->middleware('owner.capability:billing.manage')
            ->name('billing.destroy');
        Route::post('/billing/run-engine', [OwnerBillingController::class, 'runEngine'])
            ->middleware(['owner.capability:billing.run-engine', 'throttle:2,1'])->name('billing.run-engine');

        // 5. Dedicated Tenant Subscriptions
        Route::get('/subscriptions', [OwnerSubscriptionController::class, 'index'])
            ->middleware('owner.capability:subscriptions.view')->name('subscriptions.index');
        Route::post('/subscriptions', [OwnerSubscriptionController::class, 'store'])
            ->middleware('owner.capability:subscriptions.manage')->name('subscriptions.store');
        Route::post('/subscriptions/{subscription}/renew', [OwnerSubscriptionController::class, 'renew'])
            ->middleware('owner.capability:subscriptions.manage')->name('subscriptions.renew');
        Route::post('/subscriptions/{subscription}/status', [OwnerSubscriptionController::class, 'updateStatus'])
            ->middleware('owner.capability:subscriptions.manage')->name('subscriptions.status');
        Route::post('/billing/subscriptions/{subscription}/renew', [OwnerSubscriptionController::class, 'renew'])
            ->middleware('owner.capability:subscriptions.manage')->name('billing.subscriptions.renew');
        Route::post('/billing/subscriptions/{subscription}/status', [OwnerSubscriptionController::class, 'updateStatus'])
            ->middleware('owner.capability:subscriptions.manage')->name('billing.subscriptions.status');

        // 6. Dedicated Settled Payments
        Route::get('/payments', [OwnerPaymentController::class, 'index'])
            ->middleware('owner.capability:payments.view')->name('payments.index');

        // 7. Dedicated Gateway Transactions
        Route::get('/transactions', [OwnerTransactionController::class, 'index'])
            ->middleware('owner.capability:transactions.view')->name('transactions.index');

        // 8. Dedicated Tenant Wallets
        Route::get('/wallets', [OwnerWalletController::class, 'index'])->name('wallets.index');
        Route::post('/wallets/{tenant}/adjust', [OwnerWalletController::class, 'adjust'])->name('wallets.adjust');
        Route::post('/billing/wallets/{tenant}/adjust', [OwnerWalletController::class, 'adjust'])->name('billing.wallets.adjust');

        // 9. Dedicated Automation & Cron Control Hub
        Route::get('/automation', [OwnerAutomationController::class, 'index'])->name('automation.index');
        Route::post('/automation/policy', [OwnerAutomationController::class, 'updatePolicy'])->name('automation.policy.update');
        Route::post('/automation/dispatch/{task}', [OwnerAutomationController::class, 'dispatchTask'])
            ->middleware(['owner.capability:automation.dispatch', 'throttle:10,1'])
            ->name('automation.dispatch');
        Route::post('/automation/failed-jobs/{id}/retry', [OwnerAutomationController::class, 'retryFailedJob'])->name('automation.failed-jobs.retry');
        Route::post('/automation/failed-jobs/retry-all', [OwnerAutomationController::class, 'retryAllFailedJobs'])->name('automation.failed-jobs.retry-all');
        Route::delete('/automation/failed-jobs/{id}', [OwnerAutomationController::class, 'deleteFailedJob'])
            ->middleware('owner.capability:automation.dispatch')
            ->name('automation.failed-jobs.delete');
        Route::post('/automation/failed-jobs/flush', [OwnerAutomationController::class, 'flushAllFailedJobs'])
            ->middleware(['owner.capability:automation.dispatch', 'throttle:10,1'])
            ->name('automation.failed-jobs.flush');

        // 10. Payment Gateways (PGW) - Dedicated Menu
        Route::get('/payment-gateways', [OwnerPaymentGatewayController::class, 'index'])
            ->middleware('owner.capability:gateways.view')->name('payment-gateways.index');
        Route::post('/payment-gateways', [OwnerPaymentGatewayController::class, 'update'])
            ->middleware('owner.capability:gateways.manage')->name('payment-gateways.update');
        Route::post('/payment-gateways/test', [OwnerPaymentGatewayController::class, 'test'])
            ->middleware(['owner.capability:gateways.test', 'throttle:10,1'])
            ->name('payment-gateways.test');

        // 11. SMS Gateways - Dedicated Menu
        Route::get('/sms-gateways', [OwnerSmsGatewayController::class, 'index'])
            ->middleware('owner.capability:sms.view')->name('sms-gateways.index');
        Route::post('/sms-gateways/store', [OwnerSmsGatewayController::class, 'storeGateway'])
            ->middleware('owner.capability:sms.manage')->name('sms-gateways.store');
        Route::put('/sms-gateways/{gateway}', [OwnerSmsGatewayController::class, 'updateGateway'])
            ->middleware('owner.capability:sms.manage')->name('sms-gateways.gateway.update');
        Route::post('/sms-gateways/{gateway}/activate', [OwnerSmsGatewayController::class, 'activateGateway'])
            ->middleware('owner.capability:sms.manage')->name('sms-gateways.gateway.activate');
        Route::delete('/sms-gateways/{gateway}', [OwnerSmsGatewayController::class, 'destroyGateway'])
            ->middleware('owner.capability:sms.manage')->name('sms-gateways.gateway.delete');
        Route::post('/sms-gateways', [OwnerSmsGatewayController::class, 'update'])
            ->middleware('owner.capability:sms.manage')->name('sms-gateways.update');
        Route::post('/sms-gateways/test', [OwnerSmsGatewayController::class, 'test'])
            ->middleware(['owner.capability:sms.test', 'throttle:10,1'])
            ->name('sms-gateways.test');
        Route::get('/sms-gateways/balance', [OwnerSmsGatewayController::class, 'checkBalance'])
            ->middleware(['owner.capability:sms.test', 'throttle:10,1'])->name('sms-gateways.balance');

        // 12. Mail & SMTP - Dedicated Menu
        Route::get('/mail-settings', [OwnerMailSettingController::class, 'index'])
            ->middleware('owner.capability:mail.view')->name('mail-settings.index');
        Route::post('/mail-settings', [OwnerMailSettingController::class, 'update'])
            ->middleware('owner.capability:mail.manage')->name('mail-settings.update');
        Route::post('/mail-settings/test', [OwnerMailSettingController::class, 'test'])
            ->middleware(['owner.capability:mail.test', 'throttle:5,1'])->name('mail-settings.test');

        // 13. Security & Backup - Dedicated Menu
        Route::get('/security-backup', [OwnerSecurityBackupController::class, 'index'])->name('security-backup.index');
        Route::post('/security-backup', [OwnerSecurityBackupController::class, 'update'])->name('security-backup.update');
        Route::post('/security-backup/trigger', [OwnerSecurityBackupController::class, 'triggerBackup'])
            ->middleware(['owner.capability:backups.manage', 'throttle:5,1'])
            ->name('security-backup.trigger');
        Route::post('/security-backup/test-restore/{filename}', [OwnerSecurityBackupController::class, 'testRestore'])
            ->middleware(['owner.capability:backups.manage', 'throttle:5,1'])
            ->name('security-backup.test-restore');
        Route::post('/security-backup/upload-offsite/{filename}', [OwnerSecurityBackupController::class, 'uploadOffsite'])
            ->middleware(['owner.capability:backups.manage', 'throttle:5,1'])
            ->name('security-backup.upload-offsite');
        Route::get('/security-backup/download/{filename}', [OwnerSecurityBackupController::class, 'downloadBackup'])
            ->middleware('owner.capability:backups.view')
            ->name('security-backup.download');
        Route::post('/security-backup/delete/{filename}', [OwnerSecurityBackupController::class, 'deleteBackup'])
            ->middleware(['owner.capability:backups.manage', 'throttle:5,1'])
            ->name('security-backup.delete');

        // 14. Carrier Network Engines & Infrastructure Hub
        Route::prefix('network-engines')->name('network-engines.')->group(function () {
            Route::get('/', [OwnerNetworkEngineController::class, 'index'])
                ->middleware('owner.capability:network.view')->name('index');
            Route::get('/settings', [OwnerNetworkEngineController::class, 'settings'])
                ->middleware('owner.capability:network.view')->name('settings');
            Route::post('/settings', [OwnerNetworkEngineController::class, 'updateSettings'])
                ->middleware(['owner.capability:network.manage', 'throttle:10,1'])->name('settings.update');
            Route::get('/audit-logs', [OwnerNetworkEngineController::class, 'auditLogs'])
                ->middleware('owner.capability:network.view')->name('audit-logs');
            Route::post('/generate-script', [OwnerNetworkEngineController::class, 'generateScript'])
                ->middleware(['owner.capability:network.manage', 'throttle:5,1'])->name('generate-script');
            Route::post('/probe-device', [OwnerNetworkEngineController::class, 'probeDevice'])
                ->middleware(['owner.capability:network.manage', 'throttle:10,1'])->name('probe-device');
            Route::post('/test-readiness', [OwnerNetworkEngineController::class, 'testReadiness'])
                ->middleware(['owner.capability:network.view', 'throttle:5,1'])->name('test-readiness');
            Route::post('/generate-keypair', [OwnerNetworkEngineController::class, 'generateKeypair'])
                ->middleware(['owner.capability:network.manage', 'throttle:5,1'])->name('generate-keypair');
        });

        // 14. Financial Revenue & Income Report
        Route::get('/revenue-report', [OwnerRevenueReportController::class, 'index'])->name('revenue-report.index');
        Route::get('/revenue-report/export', [OwnerRevenueReportController::class, 'exportCsv'])->name('reports.revenue.export');
        Route::get('/reports/revenue', [OwnerRevenueReportController::class, 'index'])->name('reports.revenue');

        // 15. Tenant Activity & Audit Logs
        Route::get('/activity-logs', [OwnerActivityLogController::class, 'index'])
            ->middleware('owner.capability:activity-logs.view')->name('activity-logs.index');

        // 16. SMS Consumption & Delivery Logs
        Route::get('/sms-logs', [OwnerSmsLogController::class, 'index'])
            ->middleware('owner.capability:sms.view')->name('sms-logs.index');

        // 16. General Platform Settings
        Route::get('/settings', [OwnerSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [OwnerSettingController::class, 'update'])->name('settings.update');

        // 17. Helpdesk & Support Tickets
        Route::get('/tickets', [OwnerTicketController::class, 'index'])
            ->middleware('owner.capability:tickets.view')->name('tickets.index');
        Route::get('/tickets/{ticket}', [OwnerTicketController::class, 'show'])
            ->middleware('owner.capability:tickets.view')->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [OwnerTicketController::class, 'reply'])
            ->middleware(['owner.capability:tickets.manage', 'throttle:20,1'])->name('tickets.reply');
        Route::post('/tickets/{ticket}/status', [OwnerTicketController::class, 'updateStatus'])
            ->middleware('owner.capability:tickets.manage')->name('tickets.status');
        Route::post('/tickets/{ticket}/priority', [OwnerTicketController::class, 'updatePriority'])
            ->middleware('owner.capability:tickets.manage')->name('tickets.priority');
        Route::delete('/tickets/{ticket}', [OwnerTicketController::class, 'destroy'])
            ->middleware('owner.capability:tickets.manage')
            ->name('tickets.destroy');

        // 18. Live Header Notifications
        Route::get('/notifications', [OwnerNotificationController::class, 'unreadList'])->name('notifications.index');
        Route::post('/notifications/{id}/read', [OwnerNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [OwnerNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });

    // Leave Impersonation
    Route::post('/leave-impersonation', [OwnerTenantController::class, 'leaveImpersonation'])
        ->middleware('throttle:10,1')
        ->name('impersonate.leave');
});
