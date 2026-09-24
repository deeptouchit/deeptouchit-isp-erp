<?php

use App\Http\Controllers\Admin\AdminAdministratorController;
use App\Http\Controllers\Admin\AdminAgentController;
use App\Http\Controllers\Admin\AdminApiKeyController;
use App\Http\Controllers\Admin\AdminApiLogController;
use App\Http\Controllers\Admin\AdminApiRateLimitController;
use App\Http\Controllers\Admin\AdminApiUserController;
use App\Http\Controllers\Admin\AdminAnnouncementController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminCannedResponseController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminCreditController;
use App\Http\Controllers\Admin\AdminDatabaseLogController;
use App\Http\Controllers\Admin\AdminBackupSettingController;
use App\Http\Controllers\Admin\AdminDefaultSettingController;
use App\Http\Controllers\Admin\AdminBrandingSettingController;
use App\Http\Controllers\Admin\AdminDepartmentController;
use App\Http\Controllers\Admin\AdminDomainAliasController;
use App\Http\Controllers\Admin\AdminEmailSettingController;
use App\Http\Controllers\Admin\AdminFtpController;
use App\Http\Controllers\Admin\AdminGatewayController;
use App\Http\Controllers\Admin\AdminGeneralSettingController;
use App\Http\Controllers\Admin\AdminLocalizationSettingController;
use App\Http\Controllers\Admin\AdminHostingAccountController;
use App\Http\Controllers\Admin\AdminHostingActivityController;
use App\Http\Controllers\Admin\AdminIntegrationController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\AdminLoginHistoryController;
use App\Http\Controllers\Admin\AdminMailLogController;
use App\Http\Controllers\Admin\AdminNetworkController;
use App\Http\Controllers\Admin\AdminNotificationSettingController;
use App\Http\Controllers\Admin\AdminPackageManagerController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminPhpLogController;
use App\Http\Controllers\Admin\AdminProcessController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminRootCommandController;
use App\Http\Controllers\Admin\AdminSecurityLogController;
use App\Http\Controllers\Admin\AdminSecuritySettingController;
use App\Http\Controllers\Admin\AdminServiceController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\AdminSftpController;
use App\Http\Controllers\Admin\AdminSmsSettingController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminSubdomainController;
use App\Http\Controllers\Admin\AdminSystemLogController;
use App\Http\Controllers\Admin\AdminSystemGroupController;
use App\Http\Controllers\Admin\AdminSystemUserController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminTwoFactorController;
use App\Http\Controllers\Admin\AdminWebServerLogController;
use App\Http\Controllers\Admin\AdminWebhookController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatabaseController as AdminDatabaseController;
use App\Http\Controllers\Admin\InfrastructureHealthController;
use App\Http\Controllers\Admin\InfrastructureMaintenanceController;
use App\Http\Controllers\Admin\InfrastructureResourceController;
use App\Http\Controllers\Admin\InfrastructureServiceController;
use App\Http\Controllers\Admin\PhpManagerController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\ServerGroupController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TerminalController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WebsiteController as AdminWebsiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HostingOS Server Owner / Root Administrator Panel Routes
|--------------------------------------------------------------------------
*/

// Admin Guest Routes (Login)
Route::middleware('guest')->group(function () {
    Route::get('login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminLoginController::class, 'login'])->middleware('throttle.login');
});

// Protected Admin Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('logout', [AdminLoginController::class, 'logout'])->name('logout');

    // 1. Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/telemetry', [DashboardController::class, 'telemetry'])->name('dashboard.telemetry');

    // 2. Infrastructure & Servers
    Route::prefix('infrastructure')->name('infrastructure.')->group(function () {
        Route::get('servers', [ServerController::class, 'index'])->name('servers');
        Route::get('groups', [ServerGroupController::class, 'index'])->name('groups');
        Route::post('groups', [ServerGroupController::class, 'store'])->name('groups.store');
        Route::put('groups/{group}', [ServerGroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{group}', [ServerGroupController::class, 'destroy'])->name('groups.destroy');
        Route::get('health', [InfrastructureHealthController::class, 'index'])->name('health');
        Route::post('health/probe', [InfrastructureHealthController::class, 'probeAll'])->name('health.probe');
        Route::get('resources', [InfrastructureResourceController::class, 'index'])->name('resources');
        Route::post('resources/recalculate', [InfrastructureResourceController::class, 'recalculate'])->name('resources.recalculate');
        Route::get('services', [InfrastructureServiceController::class, 'index'])->name('services');
        Route::post('services/{service}/manage', [InfrastructureServiceController::class, 'manage'])->name('services.manage');
        Route::post('services/sync-all', [InfrastructureServiceController::class, 'syncAll'])->name('services.sync-all');
        Route::get('maintenance', [InfrastructureMaintenanceController::class, 'index'])->name('maintenance');
        Route::post('maintenance/nodes/{server}/toggle', [InfrastructureMaintenanceController::class, 'toggleNode'])->name('maintenance.node.toggle');
        Route::post('maintenance/routines/run', [InfrastructureMaintenanceController::class, 'runRoutine'])->name('maintenance.routines.run');
    });

    Route::prefix('servers/{server}')->name('servers.')->group(function () {
        Route::post('verify', [ServerController::class, 'verify'])->name('verify');
        Route::post('discover', [ServerController::class, 'discover'])->name('discover');
        Route::post('sync', [ServerController::class, 'sync'])->name('sync');
        Route::post('maintenance', [ServerController::class, 'maintenance'])->name('maintenance');
        Route::post('services', [ServerController::class, 'manageService'])->name('services');
        Route::post('host-key/approve', [ServerController::class, 'approveHostKey'])->name('host-key.approve');
        Route::post('credentials/rotate', [ServerController::class, 'rotateCredential'])->name('credentials.rotate');
    });
    Route::resource('servers', ServerController::class);

    // 3. Customers
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('all', [CustomerController::class, 'all'])->name('all');
        Route::get('active', [CustomerController::class, 'active'])->name('active');
        Route::get('suspended', [CustomerController::class, 'suspended'])->name('suspended');
        Route::get('activity', [CustomerController::class, 'activity'])->name('activity');
    });
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
    Route::resource('users', UserController::class);

    // 4. Hosting
    Route::prefix('hosting')->name('hosting.')->group(function () {
        Route::get('accounts', [AdminHostingAccountController::class, 'index'])->name('accounts');
        Route::post('accounts', [AdminHostingAccountController::class, 'store'])->name('accounts.store');
        Route::post('accounts/{subscription}/toggle-status', [AdminHostingAccountController::class, 'toggleStatus'])->name('accounts.toggle-status');
        Route::match(['post', 'put'], 'accounts/{subscription}/change-plan', [AdminHostingAccountController::class, 'changePlan'])->name('accounts.change-plan');
        Route::match(['post', 'put'], 'accounts/{subscription}/change-php', [AdminHostingAccountController::class, 'changePhp'])->name('accounts.change-php');
        Route::delete('accounts/{subscription}', [AdminHostingAccountController::class, 'destroy'])->name('accounts.destroy');

        Route::get('packages', [PlanController::class, 'index'])->name('packages');
        Route::get('domains', [AdminWebsiteController::class, 'index'])->name('domains');
        
        Route::get('subdomains', [AdminSubdomainController::class, 'index'])->name('subdomains');
        Route::post('subdomains', [AdminSubdomainController::class, 'store'])->name('subdomains.store');
        Route::post('subdomains/{website}/issue-ssl', [AdminSubdomainController::class, 'issueSsl'])->name('subdomains.issue-ssl');
        Route::put('subdomains/{website}/change-php', [AdminSubdomainController::class, 'changePhp'])->name('subdomains.change-php');
        Route::post('subdomains/{website}/toggle-status', [AdminSubdomainController::class, 'toggleStatus'])->name('subdomains.toggle-status');
        Route::delete('subdomains/{website}', [AdminSubdomainController::class, 'destroy'])->name('subdomains.destroy');

        Route::get('aliases', [AdminDomainAliasController::class, 'index'])->name('aliases');
        Route::post('aliases', [AdminDomainAliasController::class, 'store'])->name('aliases.store');
        Route::post('aliases/{alias}/issue-ssl', [AdminDomainAliasController::class, 'issueSsl'])->name('aliases.issue-ssl');
        Route::post('aliases/{alias}/toggle-status', [AdminDomainAliasController::class, 'toggleStatus'])->name('aliases.toggle-status');
        Route::delete('aliases/{alias}', [AdminDomainAliasController::class, 'destroy'])->name('aliases.destroy');

        Route::get('activity', [AdminHostingActivityController::class, 'index'])->name('activity');
        Route::get('activity/export', [AdminHostingActivityController::class, 'export'])->name('activity.export');
    });
    Route::get('plans/api/metrics', [PlanController::class, 'apiMetrics'])->name('plans.api.metrics');
    Route::post('plans/{plan}/clone', [PlanController::class, 'clone'])->name('plans.clone');
    Route::post('plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('plans.toggle-status');
    Route::resource('plans', PlanController::class);
    Route::post('websites/{website}/issue-ssl', [AdminWebsiteController::class, 'issueSsl'])->name('websites.issue-ssl');
    Route::put('websites/{website}/change-php', [AdminWebsiteController::class, 'changePhp'])->name('websites.change-php');
    Route::put('websites/{website}/change-docroot', [AdminWebsiteController::class, 'updateDocumentRoot'])->name('websites.change-docroot');
    Route::post('websites/{website}/toggle-status', [AdminWebsiteController::class, 'toggleStatus'])->name('websites.toggle-status');
    Route::resource('websites', AdminWebsiteController::class)->only(['index', 'store', 'destroy']);

    // 5. PHP Manager
    Route::prefix('php-manager')->name('php.')->group(function () {
        Route::get('/', [PhpManagerController::class, 'index'])->name('index');
        Route::get('versions', [PhpManagerController::class, 'index'])->name('versions');
        Route::get('install-remove', [PhpManagerController::class, 'installRemove'])->name('install-remove');
        Route::post('install-remove/install', [PhpManagerController::class, 'installVersion'])->name('install-version');
        Route::post('install-remove/uninstall', [PhpManagerController::class, 'uninstallVersion'])->name('uninstall-version');
        Route::post('install-remove/extension', [PhpManagerController::class, 'toggleExtension'])->name('toggle-extension');
        Route::get('pools', [PhpManagerController::class, 'pools'])->name('pools');
        Route::post('pools/update', [PhpManagerController::class, 'updatePool'])->name('pools.update');
        Route::post('pools/create', [PhpManagerController::class, 'createPool'])->name('pools.create');
        Route::post('pools/restart', [PhpManagerController::class, 'restartPool'])->name('pools.restart');
        Route::delete('pools/{version}/{poolName}', [PhpManagerController::class, 'deletePool'])->name('pools.delete');
        Route::get('extensions', [PhpManagerController::class, 'extensions'])->name('extensions');
        Route::post('extensions/toggle', [PhpManagerController::class, 'toggleExtension'])->name('extensions.toggle');
        Route::post('extensions/install-recommended', [PhpManagerController::class, 'installRecommendedStack'])->name('extensions.install-recommended');
        Route::get('configuration', [PhpManagerController::class, 'configuration'])->name('configuration');
        Route::post('configuration/update', [PhpManagerController::class, 'updateConfiguration'])->name('configuration.update');
        Route::post('configuration/reset', [PhpManagerController::class, 'resetConfiguration'])->name('configuration.reset');
        Route::post('configuration/raw', [PhpManagerController::class, 'updateRawConfiguration'])->name('configuration.raw');
        Route::get('per-site', [PhpManagerController::class, 'perSite'])->name('per-site');
        Route::post('per-site/switch', [PhpManagerController::class, 'switchSiteVersion'])->name('per-site.switch');
        Route::post('per-site/batch-switch', [PhpManagerController::class, 'batchSwitchSiteVersion'])->name('per-site.batch-switch');
        Route::post('per-site/compatibility', [PhpManagerController::class, 'checkCompatibility'])->name('per-site.compatibility');
        Route::post('per-site/drift-repair', [PhpManagerController::class, 'repairDrift'])->name('per-site.drift-repair');
        Route::get('logs', [PhpManagerController::class, 'logs'])->name('logs');
        Route::post('logs/clear', [PhpManagerController::class, 'clearLogs'])->name('logs.clear');
        Route::post('clear-logs', [PhpManagerController::class, 'clearLogs'])->name('clear-logs');
        
        // Actions
        Route::post('/service', [PhpManagerController::class, 'serviceAction'])->name('service');
        Route::post('/config', [PhpManagerController::class, 'updateConfiguration'])->name('config');
        Route::post('/switch-site', [PhpManagerController::class, 'switchSiteVersion'])->name('switch-site');
        Route::post('/compatibility', [PhpManagerController::class, 'checkCompatibility'])->name('compatibility');
        Route::post('/pool', [PhpManagerController::class, 'updatePool'])->name('pool');
        Route::post('/drift/repair', [PhpManagerController::class, 'repairDrift'])->name('drift.repair');
        Route::post('/opcache-flush', [PhpManagerController::class, 'flushOpcache'])->name('opcache-flush');
        Route::post('/set-default', [PhpManagerController::class, 'setDefaultVersion'])->name('set-default');
    });

    // 6. Databases
    Route::prefix('databases')->name('databases.')->group(function () {
        Route::get('mysql', [AdminDatabaseController::class, 'index'])->name('mysql');
        Route::get('list', [AdminDatabaseController::class, 'list'])->name('list');
        Route::get('users', [AdminDatabaseController::class, 'users'])->name('users');
        Route::post('users/create', [AdminDatabaseController::class, 'createUser'])->name('users.create');
        Route::post('users/password', [AdminDatabaseController::class, 'changeUserPassword'])->name('users.password');
        Route::post('users/host', [AdminDatabaseController::class, 'updateUserHost'])->name('users.host');
        Route::get('logs', [AdminDatabaseController::class, 'logs'])->name('logs');
        Route::post('logs/clear', [AdminDatabaseController::class, 'clearLogs'])->name('logs.clear');
        Route::get('sso/{database?}', [AdminDatabaseController::class, 'sso'])->name('sso');
        Route::get('phpmyadmin/{database?}', [AdminDatabaseController::class, 'sso'])->name('phpmyadmin');
        Route::post('{database}/repair', [AdminDatabaseController::class, 'repair'])->name('repair');
        Route::post('{database}/password', [AdminDatabaseController::class, 'changePassword'])->name('password');
        Route::get('{database}/export', [AdminDatabaseController::class, 'export'])->name('export');
        Route::post('kill/{processId}', [AdminDatabaseController::class, 'killProcess'])->name('kill');

        // PostgreSQL Routes
        Route::get('postgres', [\App\Http\Controllers\Admin\PostgresDatabaseController::class, 'index'])->name('postgres');
        Route::post('postgres/store', [\App\Http\Controllers\Admin\PostgresDatabaseController::class, 'store'])->name('postgres.store');
        Route::post('postgres/password', [\App\Http\Controllers\Admin\PostgresDatabaseController::class, 'changePassword'])->name('postgres.password');
        Route::post('postgres/vacuum', [\App\Http\Controllers\Admin\PostgresDatabaseController::class, 'vacuum'])->name('postgres.vacuum');
        Route::get('postgres/{name}/export', [\App\Http\Controllers\Admin\PostgresDatabaseController::class, 'export'])->name('postgres.export');
        Route::post('postgres/kill/{processId}', [\App\Http\Controllers\Admin\PostgresDatabaseController::class, 'killProcess'])->name('postgres.kill');
        Route::post('postgres/destroy', [\App\Http\Controllers\Admin\PostgresDatabaseController::class, 'destroy'])->name('postgres.destroy');
    });
    Route::resource('databases', AdminDatabaseController::class)->only(['index', 'store', 'destroy']);

    // 7. Email
    Route::prefix('email')->name('email.')->group(function () {
        Route::get('domains', [\App\Http\Controllers\Admin\EmailDomainController::class, 'index'])->name('domains');
        Route::post('domains', [\App\Http\Controllers\Admin\EmailDomainController::class, 'store'])->name('domains.store');
        Route::get('domains/{emailDomain}/verify-dns', [\App\Http\Controllers\Admin\EmailDomainController::class, 'verifyDns'])->name('domains.verify-dns');
        Route::post('domains/{emailDomain}/dkim', [\App\Http\Controllers\Admin\EmailDomainController::class, 'generateDkim'])->name('domains.dkim');
        Route::post('domains/{emailDomain}/catchall', [\App\Http\Controllers\Admin\EmailDomainController::class, 'updateCatchall'])->name('domains.catchall');
        Route::post('domains/{emailDomain}/toggle', [\App\Http\Controllers\Admin\EmailDomainController::class, 'toggleStatus'])->name('domains.toggle');
        Route::delete('domains/{emailDomain}', [\App\Http\Controllers\Admin\EmailDomainController::class, 'destroy'])->name('domains.destroy');

        Route::get('accounts', [\App\Http\Controllers\Admin\EmailAccountController::class, 'index'])->name('accounts');
        Route::post('accounts', [\App\Http\Controllers\Admin\EmailAccountController::class, 'store'])->name('accounts.store');
        Route::post('accounts/{emailAccount}/password', [\App\Http\Controllers\Admin\EmailAccountController::class, 'changePassword'])->name('accounts.password');
        Route::post('accounts/{emailAccount}/quota', [\App\Http\Controllers\Admin\EmailAccountController::class, 'updateQuota'])->name('accounts.quota');
        Route::post('accounts/{emailAccount}/forwarding', [\App\Http\Controllers\Admin\EmailAccountController::class, 'updateForwarding'])->name('accounts.forwarding');
        Route::post('accounts/{emailAccount}/toggle', [\App\Http\Controllers\Admin\EmailAccountController::class, 'toggleStatus'])->name('accounts.toggle');
        Route::delete('accounts/{emailAccount}', [\App\Http\Controllers\Admin\EmailAccountController::class, 'destroy'])->name('accounts.destroy');

        Route::get('forwarders', [\App\Http\Controllers\Admin\EmailForwarderController::class, 'index'])->name('forwarders');
        Route::post('forwarders', [\App\Http\Controllers\Admin\EmailForwarderController::class, 'store'])->name('forwarders.store');
        Route::put('forwarders/{emailForwarder}', [\App\Http\Controllers\Admin\EmailForwarderController::class, 'update'])->name('forwarders.update');
        Route::post('forwarders/{emailForwarder}/toggle', [\App\Http\Controllers\Admin\EmailForwarderController::class, 'toggleStatus'])->name('forwarders.toggle');
        Route::delete('forwarders/{emailForwarder}', [\App\Http\Controllers\Admin\EmailForwarderController::class, 'destroy'])->name('forwarders.destroy');

        Route::get('auto-responders', [\App\Http\Controllers\Admin\EmailAutoResponderController::class, 'index'])->name('auto-responders');
        Route::post('auto-responders', [\App\Http\Controllers\Admin\EmailAutoResponderController::class, 'store'])->name('auto-responders.store');
        Route::put('auto-responders/{emailAutoResponder}', [\App\Http\Controllers\Admin\EmailAutoResponderController::class, 'update'])->name('auto-responders.update');
        Route::post('auto-responders/{emailAutoResponder}/toggle', [\App\Http\Controllers\Admin\EmailAutoResponderController::class, 'toggleStatus'])->name('auto-responders.toggle');
        Route::delete('auto-responders/{emailAutoResponder}', [\App\Http\Controllers\Admin\EmailAutoResponderController::class, 'destroy'])->name('auto-responders.destroy');

        Route::get('dkim-spf', [\App\Http\Controllers\Admin\EmailSecurityController::class, 'index'])->name('dkim-spf');
        Route::post('dkim-spf/{emailDomain}/dkim', [\App\Http\Controllers\Admin\EmailSecurityController::class, 'generateDkim'])->name('dkim-spf.dkim');
        Route::post('dkim-spf/{emailDomain}/spf', [\App\Http\Controllers\Admin\EmailSecurityController::class, 'updateSpf'])->name('dkim-spf.spf');
        Route::post('dkim-spf/{emailDomain}/dmarc', [\App\Http\Controllers\Admin\EmailSecurityController::class, 'updateDmarc'])->name('dkim-spf.dmarc');
        Route::get('dkim-spf/{emailDomain}/verify-dns', [\App\Http\Controllers\Admin\EmailSecurityController::class, 'verifyDns'])->name('dkim-spf.verify-dns');

        Route::get('spam', [\App\Http\Controllers\Admin\EmailSpamController::class, 'index'])->name('spam');
        Route::post('spam', [\App\Http\Controllers\Admin\EmailSpamController::class, 'update'])->name('spam.update');
        Route::post('spam/restart', [\App\Http\Controllers\Admin\EmailSpamController::class, 'restartDaemon'])->name('spam.restart');

        Route::get('relay', [\App\Http\Controllers\Admin\EmailRelayController::class, 'index'])->name('relay');
        Route::post('relay', [\App\Http\Controllers\Admin\EmailRelayController::class, 'update'])->name('relay.update');
        Route::post('relay/test', [\App\Http\Controllers\Admin\EmailRelayController::class, 'testRelay'])->name('relay.test');

        Route::get('logs', [\App\Http\Controllers\Admin\EmailLogController::class, 'index'])->name('logs');
        Route::post('logs/clear', [\App\Http\Controllers\Admin\EmailLogController::class, 'clear'])->name('logs.clear');
    });

    // 8. DNS
    Route::prefix('dns')->name('dns.')->group(function () {
        Route::get('zones', [\App\Http\Controllers\Admin\DnsZoneController::class, 'index'])->name('zones');
        Route::post('zones', [\App\Http\Controllers\Admin\DnsZoneController::class, 'store'])->name('zones.store');
        Route::get('zones/{dnsZone}/raw', [\App\Http\Controllers\Admin\DnsZoneController::class, 'show'])->name('zones.raw');
        Route::post('zones/{dnsZone}/toggle-status', [\App\Http\Controllers\Admin\DnsZoneController::class, 'toggleStatus'])->name('zones.toggle-status');
        Route::post('zones/{dnsZone}/toggle-dnssec', [\App\Http\Controllers\Admin\DnsZoneController::class, 'toggleDnssec'])->name('zones.toggle-dnssec');
        Route::post('zones/{dnsZone}/reload', [\App\Http\Controllers\Admin\DnsZoneController::class, 'reloadZone'])->name('zones.reload');
        Route::get('zones/{dnsZone}/export', [\App\Http\Controllers\Admin\DnsZoneController::class, 'exportZone'])->name('zones.export');
        Route::get('zones/{dnsZone}/verify-dns', [\App\Http\Controllers\Admin\DnsZoneController::class, 'verifyDns'])->name('zones.verify-dns');
        Route::post('zones/reload-daemon', [\App\Http\Controllers\Admin\DnsZoneController::class, 'reloadDaemon'])->name('zones.reload-daemon');
        Route::delete('zones/{dnsZone}', [\App\Http\Controllers\Admin\DnsZoneController::class, 'destroy'])->name('zones.destroy');

        Route::get('records', [\App\Http\Controllers\Admin\DnsRecordController::class, 'index'])->name('records');
        Route::post('records', [\App\Http\Controllers\Admin\DnsRecordController::class, 'store'])->name('records.store');
        Route::put('records/{dnsRecord}', [\App\Http\Controllers\Admin\DnsRecordController::class, 'update'])->name('records.update');
        Route::post('records/{dnsRecord}/toggle-status', [\App\Http\Controllers\Admin\DnsRecordController::class, 'toggleStatus'])->name('records.toggle-status');
        Route::post('records/apply-preset', [\App\Http\Controllers\Admin\DnsRecordController::class, 'applyPreset'])->name('records.apply-preset');
        Route::delete('records/{dnsRecord}', [\App\Http\Controllers\Admin\DnsRecordController::class, 'destroy'])->name('records.destroy');

        Route::get('templates', [\App\Http\Controllers\Admin\DnsTemplateController::class, 'index'])->name('templates');
        Route::post('templates', [\App\Http\Controllers\Admin\DnsTemplateController::class, 'store'])->name('templates.store');
        Route::put('templates/{dnsTemplate}', [\App\Http\Controllers\Admin\DnsTemplateController::class, 'update'])->name('templates.update');
        Route::post('templates/{dnsTemplate}/set-default', [\App\Http\Controllers\Admin\DnsTemplateController::class, 'setDefault'])->name('templates.set-default');
        Route::post('templates/{dnsTemplate}/duplicate', [\App\Http\Controllers\Admin\DnsTemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::post('templates/{dnsTemplate}/apply', [\App\Http\Controllers\Admin\DnsTemplateController::class, 'apply'])->name('templates.apply');
        Route::delete('templates/{dnsTemplate}', [\App\Http\Controllers\Admin\DnsTemplateController::class, 'destroy'])->name('templates.destroy');

        Route::get('nameservers', [\App\Http\Controllers\Admin\NameserverController::class, 'index'])->name('nameservers');
        Route::post('nameservers', [\App\Http\Controllers\Admin\NameserverController::class, 'store'])->name('nameservers.store');
        Route::put('nameservers/{nameserver}', [\App\Http\Controllers\Admin\NameserverController::class, 'update'])->name('nameservers.update');
        Route::post('nameservers/{nameserver}/toggle-status', [\App\Http\Controllers\Admin\NameserverController::class, 'toggleStatus'])->name('nameservers.toggle-status');
        Route::post('nameservers/{nameserver}/test-probe', [\App\Http\Controllers\Admin\NameserverController::class, 'testProbe'])->name('nameservers.test-probe');
        Route::post('nameservers/test-all', [\App\Http\Controllers\Admin\NameserverController::class, 'testAll'])->name('nameservers.test-all');
        Route::post('nameservers/sync-zones', [\App\Http\Controllers\Admin\NameserverController::class, 'syncZones'])->name('nameservers.sync-zones');
        Route::delete('nameservers/{nameserver}', [\App\Http\Controllers\Admin\NameserverController::class, 'destroy'])->name('nameservers.destroy');

        Route::get('diagnostics', [\App\Http\Controllers\Admin\DnsDiagnosticsController::class, 'index'])->name('diagnostics');
        Route::post('diagnostics/dig', [\App\Http\Controllers\Admin\DnsDiagnosticsController::class, 'executeDig'])->name('diagnostics.dig');
        Route::post('diagnostics/rdns', [\App\Http\Controllers\Admin\DnsDiagnosticsController::class, 'checkRdns'])->name('diagnostics.rdns');
    });

    // 9. Security
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [SecurityController::class, 'index'])->name('index');
        Route::get('ssl', [\App\Http\Controllers\Admin\SslController::class, 'index'])->name('ssl');
        Route::post('ssl/issue-letsencrypt', [\App\Http\Controllers\Admin\SslController::class, 'issueLetsEncrypt'])->name('ssl.issue-letsencrypt');
        Route::post('ssl/install-custom', [\App\Http\Controllers\Admin\SslController::class, 'installCustom'])->name('ssl.install-custom');
        Route::post('ssl/generate-self-signed', [\App\Http\Controllers\Admin\SslController::class, 'generateSelfSigned'])->name('ssl.generate-self-signed');
        Route::post('ssl/{sslCertificate}/toggle-force-https', [\App\Http\Controllers\Admin\SslController::class, 'toggleForceHttps'])->name('ssl.toggle-force-https');
        Route::post('ssl/{sslCertificate}/toggle-auto-renew', [\App\Http\Controllers\Admin\SslController::class, 'toggleAutoRenew'])->name('ssl.toggle-auto-renew');
        Route::post('ssl/{sslCertificate}/renew', [\App\Http\Controllers\Admin\SslController::class, 'renew'])->name('ssl.renew');
        Route::get('ssl/{sslCertificate}/download/{part}', [\App\Http\Controllers\Admin\SslController::class, 'download'])->name('ssl.download');
        Route::delete('ssl/{sslCertificate}', [\App\Http\Controllers\Admin\SslController::class, 'destroy'])->name('ssl.destroy');

        Route::get('firewall', [\App\Http\Controllers\Admin\FirewallController::class, 'index'])->name('firewall');
        Route::post('firewall', [\App\Http\Controllers\Admin\FirewallController::class, 'store'])->name('firewall.store');
        Route::put('firewall/{firewallRule}', [\App\Http\Controllers\Admin\FirewallController::class, 'update'])->name('firewall.update');
        Route::post('firewall/toggle-master', [\App\Http\Controllers\Admin\FirewallController::class, 'toggleMaster'])->name('firewall.toggle-master');
        Route::post('firewall/apply-preset', [\App\Http\Controllers\Admin\FirewallController::class, 'applyPreset'])->name('firewall.apply-preset');
        Route::post('firewall/sync', [\App\Http\Controllers\Admin\FirewallController::class, 'syncSystem'])->name('firewall.sync');
        Route::delete('firewall/{firewallRule}', [\App\Http\Controllers\Admin\FirewallController::class, 'destroy'])->name('firewall.destroy');

        Route::get('blocklist', [\App\Http\Controllers\Admin\IpBlockController::class, 'index'])->name('blocklist');
        Route::post('blocklist', [\App\Http\Controllers\Admin\IpBlockController::class, 'store'])->name('blocklist.store');
        Route::post('blocklist/bulk', [\App\Http\Controllers\Admin\IpBlockController::class, 'bulkStore'])->name('blocklist.bulk');
        Route::put('blocklist/{ipBlock}', [\App\Http\Controllers\Admin\IpBlockController::class, 'update'])->name('blocklist.update');
        Route::delete('blocklist/{ipBlock}', [\App\Http\Controllers\Admin\IpBlockController::class, 'destroy'])->name('blocklist.destroy');
        Route::get('blocklist/export', [\App\Http\Controllers\Admin\IpBlockController::class, 'export'])->name('blocklist.export');

        Route::get('allowlist', [\App\Http\Controllers\Admin\IpAllowlistController::class, 'index'])->name('allowlist');
        Route::post('allowlist', [\App\Http\Controllers\Admin\IpAllowlistController::class, 'store'])->name('allowlist.store');
        Route::post('allowlist/my-ip', [\App\Http\Controllers\Admin\IpAllowlistController::class, 'allowMyIp'])->name('allowlist.my-ip');
        Route::put('allowlist/{ipAllowlist}', [\App\Http\Controllers\Admin\IpAllowlistController::class, 'update'])->name('allowlist.update');
        Route::delete('allowlist/{ipAllowlist}', [\App\Http\Controllers\Admin\IpAllowlistController::class, 'destroy'])->name('allowlist.destroy');
        Route::get('allowlist/export', [\App\Http\Controllers\Admin\IpAllowlistController::class, 'export'])->name('allowlist.export');

        Route::get('fail2ban', [\App\Http\Controllers\Admin\Fail2banController::class, 'index'])->name('fail2ban');
        Route::post('fail2ban/ban', [\App\Http\Controllers\Admin\Fail2banController::class, 'ban'])->name('fail2ban.ban');
        Route::post('fail2ban/unban', [\App\Http\Controllers\Admin\Fail2banController::class, 'unban'])->name('fail2ban.unban');
        Route::post('fail2ban/unban-all', [\App\Http\Controllers\Admin\Fail2banController::class, 'unbanAll'])->name('fail2ban.unban-all');
        Route::post('fail2ban/restart', [\App\Http\Controllers\Admin\Fail2banController::class, 'restart'])->name('fail2ban.restart');
        Route::post('fail2ban/elevate', [\App\Http\Controllers\Admin\Fail2banController::class, 'elevate'])->name('fail2ban.elevate');
        Route::post('fail2ban/whitelist', [\App\Http\Controllers\Admin\Fail2banController::class, 'whitelist'])->name('fail2ban.whitelist');

        Route::get('events', [\App\Http\Controllers\Admin\SecurityEventsController::class, 'index'])->name('events');
        Route::post('events/purge', [\App\Http\Controllers\Admin\SecurityEventsController::class, 'purge'])->name('events.purge');
        Route::post('events/quick-block', [\App\Http\Controllers\Admin\SecurityEventsController::class, 'quickBlockIp'])->name('events.quick-block');
        Route::get('events/export', [\App\Http\Controllers\Admin\SecurityEventsController::class, 'export'])->name('events.export');
    });

    // 10. Files & Storage
    Route::prefix('files')->name('files.')->group(function () {
        Route::get('manager', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'index'])->name('manager');
        Route::post('manager/create-folder', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'createFolder'])->name('manager.create-folder');
        Route::post('manager/create-file', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'createFile'])->name('manager.create-file');
        Route::post('manager/upload', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'upload'])->name('manager.upload');
        Route::post('manager/rename', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'rename'])->name('manager.rename');
        Route::delete('manager/delete', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'destroy'])->name('manager.delete');
        Route::get('manager/edit', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'edit'])->name('manager.edit');
        Route::post('manager/save', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'save'])->name('manager.save');
        Route::post('manager/permissions', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'permissions'])->name('manager.permissions');
        Route::post('manager/compress', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'compress'])->name('manager.compress');
        Route::post('manager/extract', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'extract'])->name('manager.extract');
        Route::get('manager/download', [\App\Http\Controllers\Admin\AdminFileManagerController::class, 'download'])->name('manager.download');

        Route::get('storage', [\App\Http\Controllers\Admin\AdminStorageController::class, 'index'])->name('storage');
        Route::post('storage/vacuum-logs', [\App\Http\Controllers\Admin\AdminStorageController::class, 'vacuumLogs'])->name('storage.vacuum-logs');
        Route::post('storage/flush-cache', [\App\Http\Controllers\Admin\AdminStorageController::class, 'flushCache'])->name('storage.flush-cache');

        // FTP Accounts
        Route::get('ftp', [AdminFtpController::class, 'index'])->name('ftp');
        Route::post('ftp', [AdminFtpController::class, 'store'])->name('ftp.store');
        Route::post('ftp/{ftpAccount}/toggle', [AdminFtpController::class, 'toggleStatus'])->name('ftp.toggle');
        Route::post('ftp/{ftpAccount}/password', [AdminFtpController::class, 'changePassword'])->name('ftp.password');
        Route::delete('ftp/{ftpAccount}', [AdminFtpController::class, 'destroy'])->name('ftp.destroy');
        Route::post('ftp/config', [AdminFtpController::class, 'updateConfig'])->name('ftp.config');

        // SFTP Users
        Route::get('sftp', [AdminSftpController::class, 'index'])->name('sftp');
        Route::post('sftp', [AdminSftpController::class, 'store'])->name('sftp.store');
        Route::post('sftp/{sftpUser}/toggle', [AdminSftpController::class, 'toggleStatus'])->name('sftp.toggle');
        Route::post('sftp/{sftpUser}/password', [AdminSftpController::class, 'changePassword'])->name('sftp.password');
        Route::post('sftp/{sftpUser}/key', [AdminSftpController::class, 'updateKey'])->name('sftp.key');
        Route::delete('sftp/{sftpUser}', [AdminSftpController::class, 'destroy'])->name('sftp.destroy');

        Route::get('quotas', [\App\Http\Controllers\Admin\AdminQuotaController::class, 'index'])->name('quotas');
        Route::post('quotas/{subscription}', [\App\Http\Controllers\Admin\AdminQuotaController::class, 'update'])->name('quotas.update');
        Route::get('cleanup', [\App\Http\Controllers\Admin\AdminCleanupController::class, 'index'])->name('cleanup');
        Route::post('cleanup/{category}', [\App\Http\Controllers\Admin\AdminCleanupController::class, 'clean'])->name('cleanup.clean');
        Route::post('cleanup-all', [\App\Http\Controllers\Admin\AdminCleanupController::class, 'cleanAll'])->name('cleanup.all');
    });

    // 11. Backups
    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminBackupController::class, 'index'])->name('index');
        Route::get('dashboard', [\App\Http\Controllers\Admin\AdminBackupController::class, 'index'])->name('dashboard');
        Route::post('/', [\App\Http\Controllers\Admin\AdminBackupController::class, 'store'])->name('store');
        Route::get('{backupJob}/download', [\App\Http\Controllers\Admin\AdminBackupController::class, 'download'])->name('download');
        Route::post('{backupJob}/restore', [\App\Http\Controllers\Admin\AdminBackupController::class, 'restore'])->name('restore-action');
        Route::delete('{backupJob}', [\App\Http\Controllers\Admin\AdminBackupController::class, 'destroy'])->name('destroy');
        Route::get('jobs', [\App\Http\Controllers\Admin\AdminBackupController::class, 'jobs'])->name('jobs');
        Route::post('{backupJob}/retry', [\App\Http\Controllers\Admin\AdminBackupController::class, 'retry'])->name('retry');
        Route::get('storage', [\App\Http\Controllers\Admin\AdminBackupStorageController::class, 'index'])->name('storage');
        Route::post('storage', [\App\Http\Controllers\Admin\AdminBackupStorageController::class, 'store'])->name('storage.store');
        Route::put('storage/{backupStorage}', [\App\Http\Controllers\Admin\AdminBackupStorageController::class, 'update'])->name('storage.update');
        Route::post('storage/{backupStorage}/test', [\App\Http\Controllers\Admin\AdminBackupStorageController::class, 'test'])->name('storage.test');
        Route::post('storage/{backupStorage}/default', [\App\Http\Controllers\Admin\AdminBackupStorageController::class, 'setDefault'])->name('storage.default');
        Route::delete('storage/{backupStorage}', [\App\Http\Controllers\Admin\AdminBackupStorageController::class, 'destroy'])->name('storage.destroy');
        Route::get('schedules', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'index'])->name('schedules');
        Route::post('schedules', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'store'])->name('schedules.store');
        Route::put('schedules/{backupSchedule}', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'update'])->name('schedules.update');
        Route::post('schedules/{backupSchedule}/toggle', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'toggle'])->name('schedules.toggle');
        Route::post('schedules/{backupSchedule}/run-now', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'runNow'])->name('schedules.run-now');
        Route::delete('schedules/{backupSchedule}', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::get('restore', [\App\Http\Controllers\Admin\AdminBackupController::class, 'restorePage'])->name('restore');
        Route::post('restore-upload', [\App\Http\Controllers\Admin\AdminBackupController::class, 'uploadAndRestore'])->name('restore.upload');
        Route::get('logs', [\App\Http\Controllers\Admin\AdminBackupController::class, 'logs'])->name('logs');
        Route::post('logs/flush', [\App\Http\Controllers\Admin\AdminBackupController::class, 'flushLogs'])->name('logs.flush');
    });

    // 12. Monitoring
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('overview', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'overview'])->name('overview');
        Route::get('api/metrics', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiMetrics'])->name('api.metrics');
        Route::post('drop-caches', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'dropCaches'])->name('drop-caches');
        Route::post('kill-process', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'killProcess'])->name('kill-process');
        Route::get('cpu', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'cpu'])->name('cpu');
        Route::get('api/cpu', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiCpuMetrics'])->name('api.cpu');
        Route::post('renice-process', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'reniceProcess'])->name('renice-process');
        Route::get('ram', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'ram'])->name('ram');
        Route::get('api/ram', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiRamMetrics'])->name('api.ram');
        Route::post('flush-swap', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'flushSwap'])->name('flush-swap');
        Route::get('disk', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'disk'])->name('disk');
        Route::get('api/disk', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiDiskMetrics'])->name('api.disk');
        Route::post('vacuum-logs', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'vacuumLogs'])->name('vacuum-logs');
        Route::post('clean-temp', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'cleanTemp'])->name('clean-temp');
        Route::get('network', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'network'])->name('network');
        Route::get('api/network', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiNetworkMetrics'])->name('api.network');
        Route::post('flush-dns', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'flushDns'])->name('flush-dns');
        Route::get('php-fpm', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'phpFpm'])->name('php-fpm');
        Route::get('api/php-fpm', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiPhpFpmMetrics'])->name('api.php-fpm');
        Route::post('php-fpm/restart', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'restartPhpFpm'])->name('php-fpm.restart');
        Route::post('php-fpm/reload', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'reloadPhpFpm'])->name('php-fpm.reload');
        Route::get('database', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'database'])->name('database');
        Route::get('api/database', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiDatabaseMetrics'])->name('api.database');
        Route::post('database/kill-query', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'killDatabaseQuery'])->name('database.kill-query');
        Route::post('database/flush-tables', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'flushDatabaseTables'])->name('database.flush-tables');
        Route::post('database/restart', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'restartDatabaseService'])->name('database.restart');
        Route::get('services', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'services'])->name('services');
        Route::get('api/services', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiServicesMetrics'])->name('api.services');
        Route::post('services/action', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'serviceAction'])->name('services.action');
        Route::get('services/logs/{service}', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'serviceLogs'])->name('services.logs');
        Route::get('alerts', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'alerts'])->name('alerts');
        Route::get('api/alerts', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'apiAlertsMetrics'])->name('api.alerts');
        Route::post('alerts/rules', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'saveAlertRule'])->name('alerts.rules.save');
        Route::post('alerts/suppress', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'suppressAlert'])->name('alerts.suppress');
        Route::post('alerts/resolve', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'resolveAlert'])->name('alerts.resolve');
        Route::post('alerts/test-notification', [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'testAlertNotification'])->name('alerts.test-notification');
    });

    // 13. Automation
    Route::prefix('automation')->name('automation.')->group(function () {
        Route::get('cron', [\App\Http\Controllers\Admin\AdminCronController::class, 'index'])->name('cron');
        Route::get('api/cron', [\App\Http\Controllers\Admin\AdminCronController::class, 'apiMetrics'])->name('api.cron');
        Route::post('cron', [\App\Http\Controllers\Admin\AdminCronController::class, 'store'])->name('cron.store');
        Route::put('cron/{id}', [\App\Http\Controllers\Admin\AdminCronController::class, 'update'])->name('cron.update');
        Route::delete('cron/{id}', [\App\Http\Controllers\Admin\AdminCronController::class, 'destroy'])->name('cron.destroy');
        Route::post('cron/{id}/toggle', [\App\Http\Controllers\Admin\AdminCronController::class, 'toggle'])->name('cron.toggle');
        Route::post('cron/{id}/run-now', [\App\Http\Controllers\Admin\AdminCronController::class, 'runNow'])->name('cron.run-now');
        Route::get('cron/{id}/logs', [\App\Http\Controllers\Admin\AdminCronController::class, 'logs'])->name('cron.logs');
        Route::get('scheduled-tasks', [\App\Http\Controllers\Admin\AdminScheduledTaskController::class, 'index'])->name('scheduled-tasks');
        Route::get('api/scheduled-tasks', [\App\Http\Controllers\Admin\AdminScheduledTaskController::class, 'apiMetrics'])->name('api.scheduled-tasks');
        Route::post('scheduled-tasks/run-now', [\App\Http\Controllers\Admin\AdminScheduledTaskController::class, 'runNow'])->name('scheduled-tasks.run-now');
        Route::post('scheduled-tasks/run-schedule', [\App\Http\Controllers\Admin\AdminScheduledTaskController::class, 'runSchedule'])->name('scheduled-tasks.run-schedule');
        Route::get('queues', [\App\Http\Controllers\Admin\AdminQueueController::class, 'index'])->name('queues');
        Route::get('api/queues', [\App\Http\Controllers\Admin\AdminQueueController::class, 'apiMetrics'])->name('api.queues');
        Route::post('queues/retry', [\App\Http\Controllers\Admin\AdminQueueController::class, 'retry'])->name('queues.retry');
        Route::post('queues/retry-all', [\App\Http\Controllers\Admin\AdminQueueController::class, 'retryAll'])->name('queues.retry-all');
        Route::post('queues/forget', [\App\Http\Controllers\Admin\AdminQueueController::class, 'forget'])->name('queues.forget');
        Route::post('queues/flush', [\App\Http\Controllers\Admin\AdminQueueController::class, 'flush'])->name('queues.flush');
        Route::post('queues/restart', [\App\Http\Controllers\Admin\AdminQueueController::class, 'restartWorkers'])->name('queues.restart');
        Route::post('queues/dispatch-test', [\App\Http\Controllers\Admin\AdminQueueController::class, 'dispatchTest'])->name('queues.dispatch-test');
        Route::get('auto-backup', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'index'])->name('auto-backup');
        Route::get('api/auto-backup', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'apiMetrics'])->name('api.auto-backup');
        Route::post('auto-backup', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'store'])->name('auto-backup.store');
        Route::put('auto-backup/{backupSchedule}', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'update'])->name('auto-backup.update');
        Route::post('auto-backup/{backupSchedule}/toggle', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'toggle'])->name('auto-backup.toggle');
        Route::post('auto-backup/{backupSchedule}/run-now', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'runNow'])->name('auto-backup.run-now');
        Route::delete('auto-backup/{backupSchedule}', [\App\Http\Controllers\Admin\AdminBackupScheduleController::class, 'destroy'])->name('auto-backup.destroy');
        Route::get('auto-ssl', [\App\Http\Controllers\Admin\AdminAutoSslController::class, 'index'])->name('auto-ssl');
        Route::get('api/auto-ssl', [\App\Http\Controllers\Admin\AdminAutoSslController::class, 'apiMetrics'])->name('api.auto-ssl');
        Route::post('auto-ssl/sweep', [\App\Http\Controllers\Admin\AdminAutoSslController::class, 'runAutoSslSweep'])->name('auto-ssl.sweep');
        Route::post('auto-ssl/issue', [\App\Http\Controllers\Admin\AdminAutoSslController::class, 'issue'])->name('auto-ssl.issue');
        Route::post('auto-ssl/{id}/toggle-renew', [\App\Http\Controllers\Admin\AdminAutoSslController::class, 'toggleAutoRenew'])->name('auto-ssl.toggle-renew');
        Route::post('auto-ssl/{id}/toggle-https', [\App\Http\Controllers\Admin\AdminAutoSslController::class, 'toggleForceHttps'])->name('auto-ssl.toggle-https');
        Route::post('auto-ssl/{id}/renew-now', [\App\Http\Controllers\Admin\AdminAutoSslController::class, 'renewNow'])->name('auto-ssl.renew-now');
        Route::get('auto-suspension', [\App\Http\Controllers\Admin\AdminAutoSuspensionController::class, 'index'])->name('auto-suspension');
        Route::get('api/auto-suspension', [\App\Http\Controllers\Admin\AdminAutoSuspensionController::class, 'apiMetrics'])->name('api.auto-suspension');
        Route::post('auto-suspension/sweep', [\App\Http\Controllers\Admin\AdminAutoSuspensionController::class, 'runSweep'])->name('auto-suspension.sweep');
        Route::post('auto-suspension/{id}/suspend', [\App\Http\Controllers\Admin\AdminAutoSuspensionController::class, 'suspend'])->name('auto-suspension.suspend');
        Route::post('auto-suspension/{id}/unsuspend', [\App\Http\Controllers\Admin\AdminAutoSuspensionController::class, 'unsuspend'])->name('auto-suspension.unsuspend');
        Route::post('auto-suspension/{id}/extend-grace', [\App\Http\Controllers\Admin\AdminAutoSuspensionController::class, 'extendGrace'])->name('auto-suspension.extend-grace');
        Route::get('maintenance', [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'index'])->name('maintenance');
        Route::get('api/maintenance', [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'apiMetrics'])->name('api.maintenance');
        Route::post('maintenance/enable', [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'enable'])->name('maintenance.enable');
        Route::post('maintenance/disable', [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'disable'])->name('maintenance.disable');
        Route::post('maintenance/routine/{routineId}', [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'runRoutine'])->name('maintenance.routine');
        Route::post('maintenance/server/{id}/toggle', [\App\Http\Controllers\Admin\AdminMaintenanceController::class, 'toggleServer'])->name('maintenance.server.toggle');
    });

    // 14. Billing
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        
        // Invoices Management
        Route::get('invoices', [AdminInvoiceController::class, 'index'])->name('invoices');
        Route::post('invoices', [AdminInvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
        Route::put('invoices/{invoice}', [AdminInvoiceController::class, 'update'])->name('invoices.update');
        Route::post('invoices/{invoice}/mark-paid', [AdminInvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('invoices/{invoice}/send-reminder', [AdminInvoiceController::class, 'sendReminder'])->name('invoices.send-reminder');
        Route::post('invoices/{invoice}/cancel', [AdminInvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::delete('invoices/{invoice}', [AdminInvoiceController::class, 'destroy'])->name('invoices.destroy');

        // Payments & Settlement
        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments');
        Route::post('payments', [AdminPaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->name('payments.refund');
        Route::delete('payments/{payment}', [AdminPaymentController::class, 'destroy'])->name('payments.destroy');

        // Transactions & Unified Accounting Ledger
        Route::get('transactions', [AdminTransactionController::class, 'index'])->name('transactions');
        Route::post('transactions', [AdminTransactionController::class, 'store'])->name('transactions.store');
        Route::get('transactions/{transaction}', [AdminTransactionController::class, 'show'])->name('transactions.show');
        Route::put('transactions/{transaction}', [AdminTransactionController::class, 'update'])->name('transactions.update');
        Route::delete('transactions/{transaction}', [AdminTransactionController::class, 'destroy'])->name('transactions.destroy');

        // Coupons & Promotions
        Route::get('coupons', [AdminCouponController::class, 'index'])->name('coupons');
        Route::post('coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
        Route::get('coupons/{coupon}', [AdminCouponController::class, 'show'])->name('coupons.show');
        Route::put('coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
        Route::post('coupons/{coupon}/toggle-status', [AdminCouponController::class, 'toggleStatus'])->name('coupons.toggle-status');
        Route::delete('coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

        // Client Account Credits & Wallet
        Route::get('credits', [AdminCreditController::class, 'index'])->name('credits');
        Route::post('credits', [AdminCreditController::class, 'store'])->name('credits.store');
        Route::get('credits/{credit}', [AdminCreditController::class, 'show'])->name('credits.show');
        Route::delete('credits/{credit}', [AdminCreditController::class, 'destroy'])->name('credits.destroy');

        // Payment Gateways & Merchant API
        Route::get('gateways', [AdminGatewayController::class, 'index'])->name('gateways');
        Route::match(['put', 'post'], 'gateways/{gateway}', [AdminGatewayController::class, 'update'])->name('gateways.update');
        Route::post('gateways/{gateway}/toggle-status', [AdminGatewayController::class, 'toggleStatus'])->name('gateways.toggle-status');
        Route::post('gateways/{gateway}/toggle', [AdminGatewayController::class, 'toggleStatus'])->name('gateways.toggle');
        Route::post('gateways/{gateway}/test-connection', [AdminGatewayController::class, 'testConnection'])->name('gateways.test-connection');
        Route::post('gateways/{gateway}/test', [AdminGatewayController::class, 'testConnection'])->name('gateways.test');
    });

    // 15. Support & Helpdesk
    Route::get('tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::post('tickets', [AdminTicketController::class, 'store'])->name('tickets.store');
    Route::get('tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::put('tickets/{ticket}', [AdminTicketController::class, 'update'])->name('tickets.update');
    Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
    Route::post('tickets/{ticket}/close', [AdminTicketController::class, 'close'])->name('tickets.close');
    Route::delete('tickets/{ticket}', [AdminTicketController::class, 'destroy'])->name('tickets.destroy');

    Route::prefix('support')->name('support.')->group(function () {
        Route::get('tickets', [AdminTicketController::class, 'index'])->name('tickets');
        
        // Departments
        Route::get('departments', [AdminDepartmentController::class, 'index'])->name('departments');
        Route::post('departments', [AdminDepartmentController::class, 'store'])->name('departments.store');
        Route::put('departments/{department}', [AdminDepartmentController::class, 'update'])->name('departments.update');
        Route::post('departments/{department}/toggle-status', [AdminDepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');
        Route::delete('departments/{department}', [AdminDepartmentController::class, 'destroy'])->name('departments.destroy');

        // Agents
        Route::get('agents', [AdminAgentController::class, 'index'])->name('agents');
        Route::post('agents', [AdminAgentController::class, 'store'])->name('agents.store');
        Route::put('agents/{user}', [AdminAgentController::class, 'update'])->name('agents.update');
        Route::post('agents/{user}/toggle-auto-assign', [AdminAgentController::class, 'toggleAutoAssign'])->name('agents.toggle-auto-assign');
        Route::delete('agents/{user}', [AdminAgentController::class, 'destroy'])->name('agents.destroy');

        // Canned Responses
        Route::get('canned-responses', [AdminCannedResponseController::class, 'index'])->name('canned-responses');
        Route::post('canned-responses', [AdminCannedResponseController::class, 'store'])->name('canned-responses.store');
        Route::put('canned-responses/{cannedResponse}', [AdminCannedResponseController::class, 'update'])->name('canned-responses.update');
        Route::post('canned-responses/{cannedResponse}/use', [AdminCannedResponseController::class, 'incrementUsage'])->name('canned-responses.use');
        Route::delete('canned-responses/{cannedResponse}', [AdminCannedResponseController::class, 'destroy'])->name('canned-responses.destroy');

        // Announcements
        Route::get('announcements', [AdminAnnouncementController::class, 'index'])->name('announcements');
        Route::post('announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('announcements.update');
        Route::post('announcements/{announcement}/toggle-publish', [AdminAnnouncementController::class, 'togglePublish'])->name('announcements.toggle-publish');
        Route::post('announcements/{announcement}/toggle-pin', [AdminAnnouncementController::class, 'togglePin'])->name('announcements.toggle-pin');
        Route::delete('announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    // 16. Logs & Audit
    Route::prefix('logs')->name('logs.')->group(function () {
        // System & Kernel Logs
        Route::get('system', [AdminSystemLogController::class, 'index'])->name('system');
        Route::get('system/download', [AdminSystemLogController::class, 'download'])->name('system.download');
        Route::post('system/flush', [AdminSystemLogController::class, 'flush'])->name('system.flush');

        // Web Server Logs (Nginx / Apache)
        Route::get('web-server', [AdminWebServerLogController::class, 'index'])->name('web-server');
        Route::get('web-server/download', [AdminWebServerLogController::class, 'download'])->name('web-server.download');
        // PHP-FPM & Runtime Logs
        Route::get('php', [AdminPhpLogController::class, 'index'])->name('php');
        Route::get('php/download', [AdminPhpLogController::class, 'download'])->name('php.download');
        Route::post('php/restart', [AdminPhpLogController::class, 'restart'])->name('php.restart');
        // Database Logs (MySQL / PostgreSQL)
        Route::get('database', [AdminDatabaseLogController::class, 'index'])->name('database');
        Route::get('database/download', [AdminDatabaseLogController::class, 'download'])->name('database.download');
        Route::post('database/flush', [AdminDatabaseLogController::class, 'flush'])->name('database.flush');
        // Mail Server Logs (Postfix / OpenDKIM)
        Route::get('mail', [AdminMailLogController::class, 'index'])->name('mail');
        Route::get('mail/download', [AdminMailLogController::class, 'download'])->name('mail.download');
        Route::post('mail/flush', [AdminMailLogController::class, 'flush'])->name('mail.flush');
        // Security & Firewall Logs (Fail2ban / UFW / SSH)
        Route::get('security', [AdminSecurityLogController::class, 'index'])->name('security');
        Route::get('security/download', [AdminSecurityLogController::class, 'download'])->name('security.download');
        Route::post('security/ban-ip', [AdminSecurityLogController::class, 'banIp'])->name('security.ban-ip');
        Route::post('security/unban-ip', [AdminSecurityLogController::class, 'unbanIp'])->name('security.unban-ip');
        // Login History & Authentication Audit
        Route::get('login-history', [AdminLoginHistoryController::class, 'index'])->name('login-history');
        Route::get('login-history/export', [AdminLoginHistoryController::class, 'export'])->name('login-history.export');
        Route::post('login-history/clear', [AdminLoginHistoryController::class, 'clear'])->name('login-history.clear');
        // Activity Audit Trail
        Route::get('audit', [AdminAuditLogController::class, 'index'])->name('audit');
        Route::get('audit/export', [AdminAuditLogController::class, 'export'])->name('audit.export');
        Route::post('audit/clear', [AdminAuditLogController::class, 'clear'])->name('audit.clear');
    });

    // 17. API & Integrations
    Route::prefix('api-manager')->name('api.')->group(function () {
        // API Keys Management
        Route::get('keys', [AdminApiKeyController::class, 'index'])->name('keys');
        Route::post('keys', [AdminApiKeyController::class, 'store'])->name('keys.store');
        Route::put('keys/{apiKey}', [AdminApiKeyController::class, 'update'])->name('keys.update');
        Route::post('keys/{apiKey}/regenerate', [AdminApiKeyController::class, 'regenerate'])->name('keys.regenerate');
        Route::post('keys/{apiKey}/revoke', [AdminApiKeyController::class, 'revoke'])->name('keys.revoke');
        Route::delete('keys/{apiKey}', [AdminApiKeyController::class, 'destroy'])->name('keys.destroy');
        // API Users / Service Accounts Management
        Route::get('users', [AdminApiUserController::class, 'index'])->name('users');
        Route::post('users', [AdminApiUserController::class, 'store'])->name('users.store');
        Route::put('users/{apiUser}', [AdminApiUserController::class, 'update'])->name('users.update');
        Route::post('users/{apiUser}/toggle-status', [AdminApiUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::delete('users/{apiUser}', [AdminApiUserController::class, 'destroy'])->name('users.destroy');
        // Outbound Webhooks Management
        Route::get('webhooks', [AdminWebhookController::class, 'index'])->name('webhooks');
        Route::post('webhooks', [AdminWebhookController::class, 'store'])->name('webhooks.store');
        Route::put('webhooks/{webhook}', [AdminWebhookController::class, 'update'])->name('webhooks.update');
        Route::post('webhooks/{webhook}/test', [AdminWebhookController::class, 'test'])->name('webhooks.test');
        Route::post('webhooks/{webhook}/toggle-status', [AdminWebhookController::class, 'toggleStatus'])->name('webhooks.toggle-status');
        Route::delete('webhooks/{webhook}', [AdminWebhookController::class, 'destroy'])->name('webhooks.destroy');
        Route::get('webhooks/{webhook}/deliveries', [AdminWebhookController::class, 'deliveries'])->name('webhooks.deliveries');
        // Third-Party Integrations Management
        Route::get('integrations', [AdminIntegrationController::class, 'index'])->name('integrations');
        Route::put('integrations/{integration}', [AdminIntegrationController::class, 'update'])->name('integrations.update');
        Route::post('integrations/{integration}/test', [AdminIntegrationController::class, 'test'])->name('integrations.test');
        Route::post('integrations/{integration}/sync', [AdminIntegrationController::class, 'sync'])->name('integrations.sync');
        Route::post('integrations/{integration}/disconnect', [AdminIntegrationController::class, 'disconnect'])->name('integrations.disconnect');
        // REST API Access & Audit Logs
        Route::get('logs', [AdminApiLogController::class, 'index'])->name('logs');
        Route::get('logs/export', [AdminApiLogController::class, 'export'])->name('logs.export');
        Route::post('logs/clear', [AdminApiLogController::class, 'clear'])->name('logs.clear');
        Route::get('logs/{apiLog}', [AdminApiLogController::class, 'show'])->name('logs.show');
        // API Rate Limits & Throttling Policies
        Route::get('rate-limits', [AdminApiRateLimitController::class, 'index'])->name('rate-limits');
        Route::post('rate-limits', [AdminApiRateLimitController::class, 'store'])->name('rate-limits.store');
        Route::put('rate-limits/{apiRateLimit}', [AdminApiRateLimitController::class, 'update'])->name('rate-limits.update');
        Route::post('rate-limits/{apiRateLimit}/toggle-status', [AdminApiRateLimitController::class, 'toggleStatus'])->name('rate-limits.toggle-status');
        Route::delete('rate-limits/{apiRateLimit}', [AdminApiRateLimitController::class, 'destroy'])->name('rate-limits.destroy');
    });

    // 18. Administration
    Route::prefix('administration')->name('administration.')->group(function () {
        Route::get('administrators', [AdminAdministratorController::class, 'index'])->name('administrators');
        Route::post('administrators', [AdminAdministratorController::class, 'store'])->name('administrators.store');
        Route::put('administrators/{admin}', [AdminAdministratorController::class, 'update'])->name('administrators.update');
        Route::post('administrators/{admin}/toggle-status', [AdminAdministratorController::class, 'toggleStatus'])->name('administrators.toggle-status');
        Route::post('administrators/{admin}/reset-2fa', [AdminAdministratorController::class, 'reset2fa'])->name('administrators.reset-2fa');
        Route::delete('administrators/{admin}', [AdminAdministratorController::class, 'destroy'])->name('administrators.destroy');
        // Staff Personnel Management
        Route::get('staff', [AdminStaffController::class, 'index'])->name('staff');
        Route::post('staff', [AdminStaffController::class, 'store'])->name('staff.store');
        Route::put('staff/{staff}', [AdminStaffController::class, 'update'])->name('staff.update');
        Route::post('staff/{staff}/toggle-duty', [AdminStaffController::class, 'toggleDuty'])->name('staff.toggle-duty');
        Route::post('staff/{staff}/toggle-status', [AdminStaffController::class, 'toggleStatus'])->name('staff.toggle-status');
        Route::delete('staff/{staff}', [AdminStaffController::class, 'destroy'])->name('staff.destroy');
        // Roles & Permissions RBAC Management
        Route::get('roles', [AdminRoleController::class, 'index'])->name('roles');
        Route::post('roles', [AdminRoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');
        // Permissions Matrix
        Route::get('permissions', [AdminPermissionController::class, 'index'])->name('permissions');
        Route::post('permissions', [AdminPermissionController::class, 'store'])->name('permissions.store');
        Route::post('permissions/toggle-role', [AdminPermissionController::class, 'toggleRole'])->name('permissions.toggle-role');
        Route::delete('permissions/{permission}', [AdminPermissionController::class, 'destroy'])->name('permissions.destroy');
        // Active Administrator & Staff Sessions
        Route::get('sessions', [AdminSessionController::class, 'index'])->name('sessions');
        Route::post('sessions/terminate-all-other', [AdminSessionController::class, 'terminateAllOther'])->name('sessions.terminate-all-other');
        Route::delete('sessions/{sessionId}', [AdminSessionController::class, 'destroy'])->name('sessions.destroy');
        // Two-Factor Authentication & Zero Trust IAM
        Route::get('2fa', [AdminTwoFactorController::class, 'index'])->name('2fa');
        Route::post('2fa/policy', [AdminTwoFactorController::class, 'updatePolicy'])->name('2fa.policy');
        Route::post('2fa/{user}/toggle-enforce', [AdminTwoFactorController::class, 'toggleEnforce'])->name('2fa.toggle-enforce');
        Route::post('2fa/{user}/reset', [AdminTwoFactorController::class, 'resetUser'])->name('2fa.reset');
    });

    // 19. Root Tools
    Route::prefix('root-tools')->name('root-tools.')->group(function () {
        Route::get('terminal', [TerminalController::class, 'index'])->name('terminal');
        Route::post('terminal/execute', [TerminalController::class, 'execute'])->name('terminal.execute');
        Route::post('terminal/clear-logs', [TerminalController::class, 'clearLogs'])->name('terminal.clear-logs');

        Route::get('processes', [AdminProcessController::class, 'index'])->name('processes');
        Route::post('processes/kill', [AdminProcessController::class, 'kill'])->name('processes.kill');
        Route::get('processes/{pid}/details', [AdminProcessController::class, 'details'])->name('processes.details');
        Route::get('services', [AdminServiceController::class, 'index'])->name('services');
        Route::post('services/action', [AdminServiceController::class, 'action'])->name('services.action');
        Route::get('services/{unit}/logs', [AdminServiceController::class, 'logs'])->name('services.logs');
        Route::get('packages', [AdminPackageManagerController::class, 'index'])->name('packages');
        Route::post('packages/update-index', [AdminPackageManagerController::class, 'updateIndex'])->name('packages.update-index');
        Route::post('packages/upgrade', [AdminPackageManagerController::class, 'upgradePackage'])->name('packages.upgrade');
        Route::get('packages/{package}/details', [AdminPackageManagerController::class, 'details'])->name('packages.details');
        Route::get('network', [AdminNetworkController::class, 'index'])->name('network');
        Route::post('network/ping', [AdminNetworkController::class, 'ping'])->name('network.ping');
        Route::post('network/dns-lookup', [AdminNetworkController::class, 'dnsLookup'])->name('network.dns-lookup');
        Route::get('system-users', [AdminSystemUserController::class, 'index'])->name('system-users');
        Route::post('system-users/toggle-shell', [AdminSystemUserController::class, 'toggleShell'])->name('system-users.toggle-shell');
        Route::get('system-users/{username}/details', [AdminSystemUserController::class, 'details'])->name('system-users.details');
        Route::get('system-groups', [AdminSystemGroupController::class, 'index'])->name('system-groups');
        Route::get('system-groups/{name}/details', [AdminSystemGroupController::class, 'details'])->name('system-groups.details');
        Route::get('commands', [AdminRootCommandController::class, 'index'])->name('commands');
        Route::post('commands/execute', [AdminRootCommandController::class, 'execute'])->name('commands.execute');
    });

    // 20. Settings
    Route::prefix('system-settings')->name('settings.')->group(function () {
        Route::get('general', [AdminGeneralSettingController::class, 'index'])->name('general');
        Route::post('general', [AdminGeneralSettingController::class, 'update'])->name('general.update');
        Route::post('general/reset', [AdminGeneralSettingController::class, 'resetDefaults'])->name('general.reset');
        Route::get('branding', [AdminBrandingSettingController::class, 'index'])->name('branding');
        Route::post('branding', [AdminBrandingSettingController::class, 'update'])->name('branding.update');
        Route::post('branding/reset', [AdminBrandingSettingController::class, 'resetDefaults'])->name('branding.reset');
        Route::get('localization', [AdminLocalizationSettingController::class, 'index'])->name('localization');
        Route::post('localization', [AdminLocalizationSettingController::class, 'update'])->name('localization.update');
        Route::post('localization/reset', [AdminLocalizationSettingController::class, 'resetDefaults'])->name('localization.reset');
        Route::get('email', [AdminEmailSettingController::class, 'index'])->name('email');
        Route::post('email', [AdminEmailSettingController::class, 'update'])->name('email.update');
        Route::post('email/test', [AdminEmailSettingController::class, 'sendTestEmail'])->name('email.test');
        Route::post('email/reset', [AdminEmailSettingController::class, 'resetDefaults'])->name('email.reset');
        Route::get('sms', [AdminSmsSettingController::class, 'index'])->name('sms');
        Route::post('sms', [AdminSmsSettingController::class, 'update'])->name('sms.update');
        Route::post('sms/test', [AdminSmsSettingController::class, 'sendTestSms'])->name('sms.test');
        Route::post('sms/reset', [AdminSmsSettingController::class, 'resetDefaults'])->name('sms.reset');
        Route::get('notifications', [AdminNotificationSettingController::class, 'index'])->name('notifications');
        Route::post('notifications', [AdminNotificationSettingController::class, 'update'])->name('notifications.update');
        Route::post('notifications/probe', [AdminNotificationSettingController::class, 'sendTestProbe'])->name('notifications.probe');
        Route::post('notifications/reset', [AdminNotificationSettingController::class, 'resetDefaults'])->name('notifications.reset');
        Route::get('security', [AdminSecuritySettingController::class, 'index'])->name('security');
        Route::post('security', [AdminSecuritySettingController::class, 'update'])->name('security.update');
        Route::post('security/audit', [AdminSecuritySettingController::class, 'runAudit'])->name('security.audit');
        Route::post('security/reset', [AdminSecuritySettingController::class, 'resetDefaults'])->name('security.reset');
        Route::get('backup', [AdminBackupSettingController::class, 'index'])->name('backup');
        Route::post('backup', [AdminBackupSettingController::class, 'update'])->name('backup.update');
        Route::post('backup/probe', [AdminBackupSettingController::class, 'triggerTestSnapshot'])->name('backup.probe');
        Route::post('backup/reset', [AdminBackupSettingController::class, 'resetDefaults'])->name('backup.reset');
        Route::get('defaults', [AdminDefaultSettingController::class, 'index'])->name('defaults');
        Route::post('defaults', [AdminDefaultSettingController::class, 'update'])->name('defaults.update');
        Route::post('defaults/probe', [AdminDefaultSettingController::class, 'triggerProbe'])->name('defaults.probe');
        Route::post('defaults/reset', [AdminDefaultSettingController::class, 'resetDefaults'])->name('defaults.reset');
    });
    Route::get('settings/security', [AdminSecuritySettingController::class, 'index'])->name('system_settings.security');
    Route::get('settings/backup', [AdminBackupSettingController::class, 'index'])->name('system_settings.backup');
    Route::get('settings/defaults', [AdminDefaultSettingController::class, 'index'])->name('system_settings.defaults');
    Route::get('system-settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('system-settings', [SettingsController::class, 'update']);
});
