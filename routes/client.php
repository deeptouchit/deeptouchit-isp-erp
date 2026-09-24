<?php

use App\Http\Controllers\Client\ClientToolController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\DatabaseController;
use App\Http\Controllers\Client\EmailController;
use App\Http\Controllers\Client\FileManagerController;
use App\Http\Controllers\Client\FTPController;
use App\Http\Controllers\Client\PHPSettingsController;
use App\Http\Controllers\Client\SSLController;
use App\Http\Controllers\Client\SubscriptionController;
use App\Http\Controllers\Client\TicketController;
use App\Http\Controllers\Client\WebsiteController;
use App\Http\Controllers\Payment\InvoiceController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client Portal Routes
|--------------------------------------------------------------------------
|
| Here is where all client/user portal routes are registered.
| All routes in this file are prefixed with 'client' and protected by 'auth'.
|
*/

Route::middleware(['auth'])->prefix('client')->group(function () {
    // Client Dashboard & Suspended View
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('client.dashboard');
    Route::get('/suspended', [DashboardController::class, 'suspended'])->name('client.suspended');
    
    // Subscriptions & Hosting Plans
    Route::resource('subscriptions', SubscriptionController::class);
    
    // Websites & VirtualHosts
    Route::post('websites/{website}/change-php', [WebsiteController::class, 'updatePhp'])->name('websites.change-php');
    Route::resource('websites', WebsiteController::class);
    
    // Databases & phpMyAdmin & Remote MySQL
    Route::get('databases/remote', [ClientToolController::class, 'remoteMySQL'])->name('databases.remote');
    Route::post('databases/remote/add', [ClientToolController::class, 'addRemoteMySQLAccess'])->name('databases.remote.add');
    Route::post('databases/remote/revoke', [ClientToolController::class, 'revokeRemoteMySQLAccess'])->name('databases.remote.revoke');
    Route::get('databases/sso/{database?}', [DatabaseController::class, 'sso'])->name('databases.sso');
    Route::get('databases/{database}/export', [DatabaseController::class, 'export'])->name('databases.export');
    Route::post('databases/{database}/import', [DatabaseController::class, 'import'])->name('databases.import');
    Route::post('databases/{database}/change-password', [DatabaseController::class, 'changePassword'])->name('databases.change-password');
    Route::post('databases/{database}/repair', [DatabaseController::class, 'repair'])->name('databases.repair');
    Route::post('databases/{database}/optimize', [DatabaseController::class, 'optimize'])->name('databases.optimize');
    Route::resource('databases', DatabaseController::class)->only(['index', 'store', 'show', 'destroy']);
    
    // FTP Accounts
    Route::post('ftp-accounts/{ftpAccount}/change-password', [FTPController::class, 'changePassword'])->name('ftp-accounts.change-password');
    Route::post('ftp-accounts/{ftpAccount}/toggle-status', [FTPController::class, 'toggleStatus'])->name('ftp-accounts.toggle-status');
    Route::get('ftp-accounts/{ftpAccount}/filezilla', [FTPController::class, 'filezillaXml'])->name('ftp-accounts.filezilla');
    Route::resource('ftp-accounts', FTPController::class);
    
    // Email Accounts & Mailboxes
    Route::post('email-accounts/{emailAccount}/change-password', [EmailController::class, 'changePassword'])->name('email-accounts.change-password');
    Route::post('email-accounts/{emailAccount}/update-quota', [EmailController::class, 'updateQuota'])->name('email-accounts.update-quota');
    Route::post('email-accounts/{emailAccount}/toggle-status', [EmailController::class, 'toggleStatus'])->name('email-accounts.toggle-status');
    Route::post('email-accounts/{emailAccount}/forwarding', [EmailController::class, 'updateForwarding'])->name('email-accounts.forwarding');
    Route::get('email-accounts/{emailAccount}/webmail', [EmailController::class, 'webmailSso'])->name('email-accounts.webmail');
    Route::resource('email-accounts', EmailController::class)->only(['index', 'store', 'show', 'destroy']);
    
    // Web File Manager
    Route::prefix('file-manager')->group(function () {
        Route::get('/browse', [FileManagerController::class, 'browse'])->name('file.browse');
        Route::get('/list', [FileManagerController::class, 'listFiles'])->name('file.list');
        Route::post('/upload', [FileManagerController::class, 'upload'])->name('file.upload');
        Route::post('/create-folder', [FileManagerController::class, 'createFolder'])->name('file.create-folder');
        Route::post('/create-file', [FileManagerController::class, 'createFile'])->name('file.create-file');
        Route::post('/rename', [FileManagerController::class, 'rename'])->name('file.rename');
        Route::delete('/delete', [FileManagerController::class, 'delete'])->name('file.delete');
        Route::post('/chmod', [FileManagerController::class, 'chmodItem'])->name('file.chmod');
        Route::post('/move', [FileManagerController::class, 'moveItem'])->name('file.move');
        Route::post('/copy', [FileManagerController::class, 'copyItem'])->name('file.copy');
        Route::post('/bulk-delete', [FileManagerController::class, 'bulkDelete'])->name('file.bulk-delete');
        Route::post('/bulk-zip', [FileManagerController::class, 'bulkZip'])->name('file.bulk-zip');
        Route::post('/zip', [FileManagerController::class, 'zip'])->name('file.zip');
        Route::post('/unzip', [FileManagerController::class, 'unzip'])->name('file.unzip');
        Route::get('/edit', [FileManagerController::class, 'edit'])->name('file.edit');
        Route::post('/save-file', [FileManagerController::class, 'saveFile'])->name('file.save');
        Route::get('/download', [FileManagerController::class, 'download'])->name('file.download');
    });
    
    // SSL Operations
    Route::prefix('ssl')->group(function () {
        Route::post('/generate/{website}', [SSLController::class, 'generate'])->name('ssl.generate');
        Route::post('/renew/{website}', [SSLController::class, 'renew'])->name('ssl.renew');
        Route::delete('/revoke/{website}', [SSLController::class, 'revoke'])->name('ssl.revoke');
    });
    
    // PHP Settings
    Route::prefix('php-settings')->group(function () {
        Route::get('/{subscription}', [PHPSettingsController::class, 'index'])->name('php.settings');
        Route::post('/update-version', [PHPSettingsController::class, 'updateVersion'])->name('php.update-version');
        Route::post('/update-ini', [PHPSettingsController::class, 'updateIni'])->name('php.update-ini');
    });

    // ========================================================================
    // Client Portal Tools & Services (Hostinger / cPanel Architecture)
    // ========================================================================
    // 1. Hosting Plan
    Route::get('/hosting/resources', [ClientToolController::class, 'resources'])->name('hosting.resources');
    Route::get('/hosting/renew', [ClientToolController::class, 'renew'])->name('hosting.renew');
    Route::post('/hosting/renew', [ClientToolController::class, 'processRenewal'])->name('hosting.renew.process');
    Route::get('/hosting/upgrade', [ClientToolController::class, 'upgrade'])->name('hosting.upgrade');
    Route::post('/hosting/upgrade', [ClientToolController::class, 'processUpgrade'])->name('hosting.upgrade.process');

    // 2. Domains
    Route::get('/domains/subdomains', [ClientToolController::class, 'subdomains'])->name('domains.subdomains');
    Route::post('/domains/subdomains', [ClientToolController::class, 'storeSubdomain'])->name('domains.subdomains.store');
    Route::post('/domains/subdomains/{website}/php', [ClientToolController::class, 'updateSubdomainPhp'])->name('domains.subdomains.php');
    Route::post('/domains/subdomains/{website}/ssl', [ClientToolController::class, 'issueSubdomainSsl'])->name('domains.subdomains.ssl');
    Route::delete('/domains/subdomains/{website}', [ClientToolController::class, 'deleteSubdomain'])->name('domains.subdomains.destroy');
    Route::get('/domains/redirects', [ClientToolController::class, 'redirects'])->name('domains.redirects');
    Route::post('/domains/redirects', [ClientToolController::class, 'storeRedirect'])->name('domains.redirects.store');
    Route::post('/domains/redirects/{redirect}/toggle', [ClientToolController::class, 'toggleRedirectStatus'])->name('domains.redirects.toggle');
    Route::delete('/domains/redirects/{redirect}', [ClientToolController::class, 'deleteRedirect'])->name('domains.redirects.destroy');

    // 3. Performance
    Route::get('/performance/troubleshooter', [ClientToolController::class, 'troubleshooter'])->name('performance.troubleshooter');
    Route::post('/performance/troubleshooter/fix', [ClientToolController::class, 'troubleshooterFix'])->name('performance.troubleshooter.fix');
    Route::get('/performance/pagespeed', [ClientToolController::class, 'pageSpeed'])->name('performance.pagespeed');
    Route::post('/performance/pagespeed/analyze', [ClientToolController::class, 'analyzeSpeed'])->name('performance.pagespeed.analyze');
    Route::post('/performance/pagespeed/toggle-optimization', [ClientToolController::class, 'toggleSpeedOptimization'])->name('performance.pagespeed.toggle');
    Route::get('/performance/cdn', [ClientToolController::class, 'cdn'])->name('performance.cdn');
    Route::post('/performance/cdn/toggle', [ClientToolController::class, 'toggleCdn'])->name('performance.cdn.toggle');
    Route::post('/performance/cdn/purge', [ClientToolController::class, 'purgeCdnCache'])->name('performance.cdn.purge');
    Route::post('/performance/cdn/update-settings', [ClientToolController::class, 'updateCdnSettings'])->name('performance.cdn.update-settings');

    // 4. Security
    Route::get('/security/malware-scanner', [ClientToolController::class, 'malwareScanner'])->name('security.malware');
    Route::post('/security/malware-scanner/scan', [ClientToolController::class, 'runMalwareScan'])->name('security.malware.scan');
    Route::post('/security/malware-scanner/remediate', [ClientToolController::class, 'remediateThreat'])->name('security.malware.remediate');
    Route::post('/security/malware-scanner/toggle-shield', [ClientToolController::class, 'toggleSecurityShield'])->name('security.malware.toggle-shield');
    Route::get('/security/ssl', [ClientToolController::class, 'ssl'])->name('security.ssl');
    Route::post('/security/ssl/issue', [ClientToolController::class, 'issueSslCertificate'])->name('security.ssl.issue');
    Route::post('/security/ssl/{website}/renew', [ClientToolController::class, 'renewSslCertificate'])->name('security.ssl.renew');
    Route::post('/security/ssl/{website}/revoke', [ClientToolController::class, 'revokeSslCertificate'])->name('security.ssl.revoke');
    Route::post('/security/ssl/{website}/force-https', [ClientToolController::class, 'toggleForceHttps'])->name('security.ssl.force-https');
    Route::post('/security/ssl/install-custom', [ClientToolController::class, 'installCustomSsl'])->name('security.ssl.install-custom');

    // 5. Website Tools
    Route::get('/website/wordpress', [ClientToolController::class, 'wordpress'])->name('website.wordpress');
    Route::post('/website/wordpress/install', [ClientToolController::class, 'installWordpress'])->name('website.wordpress.install');
    Route::post('/website/wordpress/{installation}/maintenance', [ClientToolController::class, 'toggleWordpressMaintenance'])->name('website.wordpress.maintenance');
    Route::post('/website/wordpress/{installation}/auto-update', [ClientToolController::class, 'toggleWordpressAutoUpdate'])->name('website.wordpress.auto-update');
    Route::delete('/website/wordpress/{installation}', [ClientToolController::class, 'deleteWordpress'])->name('website.wordpress.destroy');
    Route::get('/website/installer', [ClientToolController::class, 'autoInstaller'])->name('website.installer');
    Route::post('/website/installer/install', [ClientToolController::class, 'installApp'])->name('website.installer.install');
    Route::post('/website/installer/uninstall', [ClientToolController::class, 'uninstallApp'])->name('website.installer.uninstall');
    Route::get('/website/migrate', [ClientToolController::class, 'migrate'])->name('website.migrate');
    Route::post('/website/migrate/start', [ClientToolController::class, 'startMigration'])->name('website.migrate.start');
    Route::post('/website/migrate/{id}/cancel', [ClientToolController::class, 'cancelMigration'])->name('website.migrate.cancel');
    Route::get('/website/error-pages', [ClientToolController::class, 'errorPages'])->name('website.error-pages');
    Route::post('/website/error-pages/save', [ClientToolController::class, 'saveErrorPage'])->name('website.error-pages.save');
    Route::post('/website/error-pages/reset', [ClientToolController::class, 'resetErrorPage'])->name('website.error-pages.reset');
    Route::get('/website/logo-maker', [ClientToolController::class, 'logoMaker'])->name('website.logo-maker');
    Route::post('/website/logo-maker/deploy', [ClientToolController::class, 'deployLogoAndFavicon'])->name('website.logo-maker.deploy');

    // 6. Files
    Route::get('/files/backups', [ClientToolController::class, 'backups'])->name('files.backups');
    Route::post('/files/backups/create', [ClientToolController::class, 'createBackup'])->name('files.backups.create');
    Route::post('/files/backups/restore', [ClientToolController::class, 'restoreBackup'])->name('files.backups.restore');
    Route::delete('/files/backups/{filename}', [ClientToolController::class, 'deleteBackup'])->name('files.backups.destroy');
    Route::get('/files/backups/{filename}/download', [ClientToolController::class, 'downloadBackup'])->name('files.backups.download');

    Route::get('/advanced/ssh', [ClientToolController::class, 'ssh'])->name('advanced.ssh');
    Route::post('/advanced/ssh/add-key', [ClientToolController::class, 'addSshKey'])->name('advanced.ssh.add-key');
    Route::post('/advanced/ssh/generate-key', [ClientToolController::class, 'generateSshKeyPair'])->name('advanced.ssh.generate-key');
    Route::delete('/advanced/ssh/delete-key', [ClientToolController::class, 'deleteSshKey'])->name('advanced.ssh.delete-key');
    Route::post('/advanced/ssh/toggle-access', [ClientToolController::class, 'toggleSshAccess'])->name('advanced.ssh.toggle-access');
    Route::post('/advanced/ssh/change-password', [ClientToolController::class, 'changeSshPassword'])->name('advanced.ssh.change-password');
    Route::post('/advanced/ssh/run-command', [ClientToolController::class, 'runTerminalCommand'])->name('advanced.ssh.run-command');
    Route::get('/advanced/php-config', [ClientToolController::class, 'phpConfig'])->name('advanced.php-config');
    Route::post('/advanced/php-config/update-version', [ClientToolController::class, 'updatePhpVersion'])->name('advanced.php-config.update-version');
    Route::post('/advanced/php-config/update-ini', [ClientToolController::class, 'updatePhpIni'])->name('advanced.php-config.update-ini');
    Route::post('/advanced/php-config/reset-ini', [ClientToolController::class, 'resetPhpIni'])->name('advanced.php-config.reset-ini');
    Route::get('/advanced/dns', [ClientToolController::class, 'dns'])->name('advanced.dns');
    Route::post('/advanced/dns/records', [ClientToolController::class, 'addDnsRecord'])->name('advanced.dns.add-record');
    Route::put('/advanced/dns/records/{record}', [ClientToolController::class, 'updateDnsRecord'])->name('advanced.dns.update-record');
    Route::delete('/advanced/dns/records/{record}', [ClientToolController::class, 'deleteDnsRecord'])->name('advanced.dns.delete-record');
    Route::post('/advanced/dns/reset-defaults', [ClientToolController::class, 'resetDnsDefaults'])->name('advanced.dns.reset-defaults');
    Route::get('/advanced/cron', [ClientToolController::class, 'cron'])->name('advanced.cron');
    Route::post('/advanced/cron/jobs', [ClientToolController::class, 'addCronJob'])->name('advanced.cron.add-job');
    Route::put('/advanced/cron/jobs/{cronJob}', [ClientToolController::class, 'updateCronJob'])->name('advanced.cron.update-job');
    Route::delete('/advanced/cron/jobs/{cronJob}', [ClientToolController::class, 'deleteCronJob'])->name('advanced.cron.delete-job');
    Route::post('/advanced/cron/jobs/{cronJob}/toggle', [ClientToolController::class, 'toggleCronJob'])->name('advanced.cron.toggle-job');
    Route::post('/advanced/cron/jobs/{cronJob}/run', [ClientToolController::class, 'runCronJobNow'])->name('advanced.cron.run-now');
    Route::post('/advanced/cron/clear-logs', [ClientToolController::class, 'clearCronLogs'])->name('advanced.cron.clear-logs');
    Route::get('/advanced/phpinfo', [ClientToolController::class, 'phpinfo'])->name('advanced.phpinfo');
    Route::get('/advanced/cache', [ClientToolController::class, 'cache'])->name('advanced.cache');
    Route::post('/advanced/cache/purge-all', [ClientToolController::class, 'purgeAllCaches'])->name('advanced.cache.purge-all');
    Route::post('/advanced/cache/purge-redis', [ClientToolController::class, 'purgeRedisCache'])->name('advanced.cache.purge-redis');
    Route::post('/advanced/cache/purge-opcache', [ClientToolController::class, 'purgeOpcache'])->name('advanced.cache.purge-opcache');
    Route::post('/advanced/cache/purge-app', [ClientToolController::class, 'purgeAppCache'])->name('advanced.cache.purge-app');
    Route::get('/advanced/git', [ClientToolController::class, 'git'])->name('advanced.git');
    Route::post('/advanced/git/repositories', [ClientToolController::class, 'createGitRepository'])->name('advanced.git.create-repo');
    Route::put('/advanced/git/repositories/{repository}', [ClientToolController::class, 'updateGitRepository'])->name('advanced.git.update-repo');
    Route::delete('/advanced/git/repositories/{repository}', [ClientToolController::class, 'deleteGitRepository'])->name('advanced.git.delete-repo');
    Route::post('/advanced/git/repositories/{repository}/deploy', [ClientToolController::class, 'deployGitRepository'])->name('advanced.git.deploy-repo');
    Route::post('/advanced/git/repositories/{repository}/regenerate-webhook', [ClientToolController::class, 'regenerateGitWebhook'])->name('advanced.git.regenerate-webhook');
    Route::post('/advanced/git/clear-logs', [ClientToolController::class, 'clearGitLogs'])->name('advanced.git.clear-logs');
    Route::get('/advanced/protected-dirs', [ClientToolController::class, 'protectedDirs'])->name('advanced.protected-dirs');
    Route::post('/advanced/protected-dirs', [ClientToolController::class, 'createProtectedDir'])->name('advanced.protected-dirs.create');
    Route::put('/advanced/protected-dirs/{directory}', [ClientToolController::class, 'updateProtectedDir'])->name('advanced.protected-dirs.update');
    Route::delete('/advanced/protected-dirs/{directory}', [ClientToolController::class, 'deleteProtectedDir'])->name('advanced.protected-dirs.delete');
    Route::post('/advanced/protected-dirs/{directory}/toggle', [ClientToolController::class, 'toggleProtectedDir'])->name('advanced.protected-dirs.toggle');
    Route::post('/advanced/protected-dirs/{directory}/users', [ClientToolController::class, 'addProtectedDirUser'])->name('advanced.protected-dirs.add-user');
    Route::delete('/advanced/protected-dirs/users/{user}', [ClientToolController::class, 'deleteProtectedDirUser'])->name('advanced.protected-dirs.delete-user');
    Route::get('/advanced/ip-manager', [ClientToolController::class, 'ipManager'])->name('advanced.ip-manager');
    Route::post('/advanced/ip-manager/block', [ClientToolController::class, 'addIpBlock'])->name('advanced.ip-manager.block');
    Route::delete('/advanced/ip-manager/block/{ipBlock}', [ClientToolController::class, 'deleteIpBlock'])->name('advanced.ip-manager.unblock');
    Route::post('/advanced/ip-manager/allow', [ClientToolController::class, 'addIpAllow'])->name('advanced.ip-manager.allow');
    Route::delete('/advanced/ip-manager/allow/{ipAllow}', [ClientToolController::class, 'deleteIpAllow'])->name('advanced.ip-manager.unallow');
    Route::post('/advanced/ip-manager/allow-my-ip', [ClientToolController::class, 'allowMyIp'])->name('advanced.ip-manager.allow-my-ip');
    Route::get('/advanced/hotlink', [ClientToolController::class, 'hotlink'])->name('advanced.hotlink');
    Route::post('/advanced/hotlink', [ClientToolController::class, 'updateHotlink'])->name('advanced.hotlink.update');
    Route::post('/advanced/hotlink/toggle', [ClientToolController::class, 'toggleHotlink'])->name('advanced.hotlink.toggle');
    Route::get('/advanced/folder-index', [ClientToolController::class, 'folderIndex'])->name('advanced.folder-index');
    Route::post('/advanced/folder-index', [ClientToolController::class, 'saveFolderIndex'])->name('advanced.folder-index.save');
    Route::delete('/advanced/folder-index/{directoryIndexing}', [ClientToolController::class, 'deleteFolderIndex'])->name('advanced.folder-index.delete');
    Route::get('/advanced/fix-permissions', [ClientToolController::class, 'fixPermissions'])->name('advanced.fix-permissions');
    Route::post('/advanced/fix-permissions/execute', [ClientToolController::class, 'executeFixPermissions'])->name('advanced.fix-permissions.execute');
    Route::get('/advanced/activity-log', [ClientToolController::class, 'activityLog'])->name('advanced.activity-log');
    Route::post('/advanced/activity-log/clear', [ClientToolController::class, 'clearActivityLog'])->name('advanced.activity-log.clear');
    Route::get('/advanced/activity-log/export', [ClientToolController::class, 'exportActivityLog'])->name('advanced.activity-log.export');

    // Billing & Invoices
    Route::prefix('billing')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('billing.invoices');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('billing.invoice.show');
        Route::match(['get', 'post'], '/invoices/{invoice}/pay', [PaymentController::class, 'initiate'])->name('billing.pay');
        Route::match(['get', 'post'], '/invoices/{invoice}/callback', [PaymentController::class, 'callback'])->name('billing.callback');
        Route::match(['get', 'post'], '/invoices/{invoice}/confirm', [PaymentController::class, 'confirm'])->name('billing.confirm');
    });
    
    // Support Tickets
    Route::resource('tickets', TicketController::class);
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');

    // Profile Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
