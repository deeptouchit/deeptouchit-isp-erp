<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Database;
use App\Models\Subscription;
use App\Services\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseController extends Controller
{
    public function index(DatabaseManager $dbManager): Response
    {
        $userId = auth()->id();
        
        $subscriptions = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->with('plan')
            ->get();

        $subIds = $subscriptions->pluck('id');

        $rawDatabases = Database::whereIn('subscription_id', $subIds)
            ->with('subscription.plan')
            ->latest()
            ->get();

        // Enrich databases with live metrics
        $databases = $rawDatabases->map(function ($db) use ($dbManager) {
            $bytes = $dbManager->getDatabaseSize($db->name);
            $tables = $dbManager->getTableCount($db->name);
            
            return [
                'id' => $db->id,
                'subscription_id' => $db->subscription_id,
                'name' => $db->name,
                'db_user' => $db->db_user,
                'host' => $db->host ?: 'localhost',
                'port' => $db->port ?: 3306,
                'status' => $db->status ?: 'active',
                'size_bytes' => $bytes,
                'size_formatted' => $this->formatBytes($bytes),
                'table_count' => $tables,
                'created_at' => $db->created_at ? $db->created_at->format('M d, Y') : 'N/A',
                'subscription' => [
                    'id' => $db->subscription->id ?? null,
                    'domain' => $db->subscription->domain ?? 'N/A',
                    'username' => $db->subscription->username ?? 'user',
                    'plan_name' => $db->subscription->plan->name ?? 'Standard Plan',
                ]
            ];
        });

        // Calculate package quota
        $totalAllowed = (int) $subscriptions->sum(function ($sub) {
            return $sub->plan ? (int)$sub->plan->max_databases : 2;
        });
        if ($totalAllowed === 0) {
            $totalAllowed = 2; // Default baseline
        }
        $totalUsed = $databases->count();
        $remaining = max(0, $totalAllowed - $totalUsed);
        $canAdd = $totalUsed < $totalAllowed;
        $usagePercentage = $totalAllowed > 0 ? min(100, (int)round(($totalUsed / $totalAllowed) * 100)) : 100;

        // Formatted subscriptions for UI dropdown with prefix
        $subscriptionOptions = $subscriptions->map(function ($sub) {
            $prefix = Str::slug($sub->username, '_');
            return [
                'id' => $sub->id,
                'domain' => $sub->domain,
                'username' => $sub->username,
                'prefix' => $prefix,
                'plan_name' => $sub->plan->name ?? 'Standard Plan',
                'max_databases' => $sub->plan->max_databases ?? 2,
            ];
        });

        return Inertia::render('Client/Databases/Index', [
            'databases' => $databases,
            'subscriptions' => $subscriptionOptions,
            'quota' => [
                'total_allowed' => $totalAllowed,
                'total_used' => $totalUsed,
                'remaining' => $remaining,
                'can_add' => $canAdd,
                'usage_percentage' => $usagePercentage
            ],
            'serverInfo' => [
                'host' => 'localhost',
                'ip' => '127.0.0.1',
                'port' => 3306,
                'driver' => 'MySQL 8.0 / MariaDB',
                'socket' => '/var/run/mysqld/mysqld.sock',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci'
            ]
        ]);
    }

    /**
     * Display database detail / redirect back to index
     */
    public function show(?Database $database = null)
    {
        return redirect()->route('databases.index');
    }

    /**
     * Store new database with multi-tenant prefixing and quota enforcement
     */
    public function store(Request $request, DatabaseManager $dbManager)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'name' => 'required|string|max:40|regex:/^[a-zA-Z0-9_]+$/',
            'db_user' => 'required|string|max:20|regex:/^[a-zA-Z0-9_]+$/',
            'db_password' => 'required|string|min:8',
        ]);

        $subscription = Subscription::with('plan')->findOrFail($validated['subscription_id']);
        if ($subscription->user_id !== auth()->id()) {
            abort(403);
        }

        // 1. Quota Verification
        $currentCount = Database::where('subscription_id', $subscription->id)->count();
        $allowed = $subscription->plan ? (int)$subscription->plan->max_databases : 2;
        if ($allowed > 0 && $currentCount >= $allowed) {
            return back()->withErrors([
                'name' => "You have reached the maximum allowed databases ({$allowed}) for subscription {$subscription->domain}. Please upgrade your plan to create more."
            ]);
        }

        // 2. Multi-tenant prefixing (cPanel-standard)
        $prefix = Str::slug($subscription->username, '_');
        $rawDbName = $validated['name'];
        $rawDbUser = $validated['db_user'];

        // Prefix DB Name
        if (!Str::startsWith($rawDbName, $prefix . '_')) {
            $finalDbName = substr($prefix . '_' . $rawDbName, 0, 64);
        } else {
            $finalDbName = substr($rawDbName, 0, 64);
        }

        // Prefix DB User (MySQL username max 32 chars)
        if (!Str::startsWith($rawDbUser, $prefix . '_')) {
            $finalDbUser = substr($prefix . '_' . $rawDbUser, 0, 32);
        } else {
            $finalDbUser = substr($rawDbUser, 0, 32);
        }

        // Check collision in Database model
        if (Database::where('name', $finalDbName)->exists()) {
            return back()->withErrors(['name' => "Database '{$finalDbName}' already exists."]);
        }

        // 3. Create physical database and user in MySQL
        $result = $dbManager->createDatabase([
            'name' => $finalDbName,
            'db_user' => $finalDbUser,
            'db_password' => $validated['db_password']
        ]);

        if (!$result['success']) {
            return back()->withErrors(['name' => 'MySQL Provisioning Error: ' . $result['error']]);
        }

        // 4. Save record
        Database::create([
            'subscription_id' => $subscription->id,
            'name' => $finalDbName,
            'db_user' => $finalDbUser,
            'db_password' => $validated['db_password'],
            'host' => 'localhost',
            'port' => 3306,
            'status' => 'active'
        ]);

        return redirect()->route('databases.index')->with('success', "Database '{$finalDbName}' and user '{$finalDbUser}' provisioned successfully.");
    }

    /**
     * Change database user password
     */
    public function changePassword(Request $request, Database $database, DatabaseManager $dbManager)
    {
        if ($database->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8'
        ]);

        $success = $dbManager->changePassword($database->db_user, $validated['password']);
        if (!$success) {
            return back()->withErrors(['password' => 'Failed to update MySQL user password.']);
        }

        $database->update(['db_password' => $validated['password']]);

        return redirect()->route('databases.index')->with('success', "Password for user '{$database->db_user}' updated successfully.");
    }

    /**
     * 1-Click phpMyAdmin Auto-Login with strict tenant isolation.
     * Uses secure token-based SignonScript so Laravel session is never interrupted.
     */
    public function sso(Request $request, ?Database $database = null)
    {
        $userId = auth()->id();

        // 1. Verify ownership if specific database passed
        if ($database) {
            if ($database->subscription->user_id !== $userId && auth()->user()->role !== 'admin') {
                abort(403);
            }
            $targetDb = $database;
        } else {
            // Find user's first available database
            $targetDb = Database::whereHas('subscription', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->first();

            if (!$targetDb) {
                return redirect()->route('databases.index')->withErrors([
                    'name' => 'Please create a database first before accessing phpMyAdmin.'
                ]);
            }
        }

        // 2. Fetch all databases owned by this client
        $userDatabases = Database::whereHas('subscription', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

        $allowedDbNames = $userDatabases->pluck('name')->unique()->values()->toArray();
        if (empty($allowedDbNames)) {
            $allowedDbNames = [$targetDb->name];
        }

        // 3. Ensure the active database user exists and has synced credentials
        $ssoPassword = !empty($targetDb->db_password) && !Str::startsWith($targetDb->db_password, '$2y$')
            ? $targetDb->db_password
            : Str::random(24);

        try {
            // Check if user exists on MySQL server
            $userExists = \Illuminate\Support\Facades\DB::select("SELECT User FROM mysql.user WHERE User = ?", [$targetDb->db_user]);
            if (empty($userExists)) {
                \Illuminate\Support\Facades\DB::statement("CREATE USER IF NOT EXISTS '{$targetDb->db_user}'@'localhost' IDENTIFIED BY '{$ssoPassword}'");
                \Illuminate\Support\Facades\DB::statement("CREATE USER IF NOT EXISTS '{$targetDb->db_user}'@'127.0.0.1' IDENTIFIED BY '{$ssoPassword}'");
            } else {
                \Illuminate\Support\Facades\DB::statement("ALTER USER '{$targetDb->db_user}'@'localhost' IDENTIFIED BY '{$ssoPassword}'");
                \Illuminate\Support\Facades\DB::statement("ALTER USER '{$targetDb->db_user}'@'127.0.0.1' IDENTIFIED BY '{$ssoPassword}'");
            }

            foreach ($allowedDbNames as $dbName) {
                try {
                    \Illuminate\Support\Facades\DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    \Illuminate\Support\Facades\DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$targetDb->db_user}'@'localhost'");
                    \Illuminate\Support\Facades\DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$targetDb->db_user}'@'127.0.0.1'");
                } catch (\Throwable $e) {
                    // Ignore per-db grant errors
                }
            }
            \Illuminate\Support\Facades\DB::statement("FLUSH PRIVILEGES");

            $targetDb->update(['db_password' => $ssoPassword]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("SSO Privileges sync error: " . $e->getMessage());
        }

        // 4. Generate secure one-time SSO token
        $token = bin2hex(random_bytes(24));
        $tokenData = [
            'user' => $targetDb->db_user,
            'password' => $ssoPassword,
            'allowed_dbs' => $allowedDbNames,
            'target_db' => $targetDb->name,
            'expires_at' => time() + 300,
        ];

        $pmaDir = storage_path('app/pma_sso');
        if (!is_dir($pmaDir)) {
            @mkdir($pmaDir, 0777, true);
        }

        $tokenFile = $pmaDir . '/sso_' . $token . '.json';
        file_put_contents($tokenFile, json_encode($tokenData));
        @chmod($tokenFile, 0666);

        $targetUrl = '/client/phpmyadmin/index.php?sso_token=' . $token . '&db=' . urlencode($targetDb->name);

        return redirect($targetUrl);
    }

    /**
     * Export database .sql dump
     */
    public function export(Database $database): StreamedResponse
    {
        if ($database->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $fileName = $database->name . '_' . date('Y_m_d_His') . '.sql';
        $dbUser = config('database.connections.mysql.username', 'panel_user');
        $dbPass = config('database.connections.mysql.password', 'StrongPassword123!');

        return response()->streamDownload(function () use ($database, $dbUser, $dbPass) {
            $cmd = sprintf(
                "mysqldump -u%s -p%s -h%s %s",
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg('127.0.0.1'),
                escapeshellarg($database->name)
            );

            passthru($cmd);
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Import .sql file directly into database
     */
    public function import(Request $request, Database $database)
    {
        if ($database->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'sql_file' => 'required|file|max:102400', // 100MB max
        ]);

        $file = $request->file('sql_file');
        $ext = strtolower($file->getClientOriginalExtension());
        $tempPath = $file->getRealPath();

        $dbRootUser = config('database.connections.mysql.username', 'root');
        $dbRootPass = config('database.connections.mysql.password', '');
        $passArg = !empty($dbRootPass) ? "-p'" . addslashes($dbRootPass) . "'" : '';

        try {
            if ($ext === 'gz') {
                @exec("gunzip -c {$tempPath} | mysql -u {$dbRootUser} {$passArg} {$database->name} 2>&1", $output, $returnCode);
            } else {
                @exec("mysql -u {$dbRootUser} {$passArg} {$database->name} < {$tempPath} 2>&1", $output, $returnCode);
            }

            if ($returnCode !== 0) {
                $errStr = implode(' ', array_slice($output, 0, 3));
                return back()->withErrors(['sql_file' => "SQL Import Error: {$errStr}"]);
            }

            return back()->with('success', "SQL file '{$file->getClientOriginalName()}' imported into '{$database->name}' successfully!");
        } catch (\Throwable $e) {
            return back()->withErrors(['sql_file' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete Database & User
     */
    public function destroy(Database $database, DatabaseManager $dbManager)
    {
        if ($database->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $dbName = $database->name;
        $dbUser = $database->db_user;

        // Drop Database
        $dbManager->deleteDatabase($dbName);

        // Check if any other database still uses this dbUser
        $otherDbUsingUser = Database::where('db_user', $dbUser)
            ->where('id', '!=', $database->id)
            ->exists();

        if (!$otherDbUsingUser) {
            $dbManager->deleteDatabaseUser($dbUser);
        }

        $database->delete();

        return redirect()->route('databases.index')->with('success', "Database '{$dbName}' deleted successfully.");
    }

    /**
     * Check & Repair all tables in database
     */
    public function repair(Database $database, DatabaseManager $dbManager)
    {
        if ($database->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        try {
            $tables = \Illuminate\Support\Facades\DB::select("SHOW TABLES FROM `{$database->name}`");
            $key = "Tables_in_{$database->name}";
            $repaired = 0;
            foreach ($tables as $t) {
                if (isset($t->$key)) {
                    $tableName = $t->$key;
                    \Illuminate\Support\Facades\DB::statement("CHECK TABLE `{$database->name}`.`{$tableName}`");
                    \Illuminate\Support\Facades\DB::statement("REPAIR TABLE `{$database->name}`.`{$tableName}`");
                    $repaired++;
                }
            }
            return back()->with('success', "Database integrity check complete. {$repaired} table(s) checked and repaired successfully.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Repair failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Optimize all tables in database
     */
    public function optimize(Database $database, DatabaseManager $dbManager)
    {
        if ($database->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        try {
            $tables = \Illuminate\Support\Facades\DB::select("SHOW TABLES FROM `{$database->name}`");
            $key = "Tables_in_{$database->name}";
            $optimized = 0;
            foreach ($tables as $t) {
                if (isset($t->$key)) {
                    $tableName = $t->$key;
                    \Illuminate\Support\Facades\DB::statement("OPTIMIZE TABLE `{$database->name}`.`{$tableName}`");
                    $optimized++;
                }
            }
            return back()->with('success', "Database optimization complete. {$optimized} table(s) defragmented and indexed.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Optimization failed: ' . $e->getMessage()]);
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0.00 KB';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
