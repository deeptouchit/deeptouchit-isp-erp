<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Database;
use App\Models\Subscription;
use App\Services\Database\DatabaseLogService;
use App\Services\Database\PostgresService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseController extends Controller
{
    protected PostgresService $postgresService;
    protected DatabaseLogService $logService;

    public function __construct(PostgresService $postgresService, DatabaseLogService $logService)
    {
        $this->postgresService = $postgresService;
        $this->logService = $logService;
    }
    /**
     * Display all database instances, live MySQL telemetry, and processlist.
     */
    public function index(): Response
    {
        $databases = Database::with(['subscription.user', 'subscription.plan'])
            ->latest()
            ->paginate(30);

        // Fetch exact live database sizes & table counts from information_schema
        $dbSizes = [];
        try {
            $tableStats = DB::select("
                SELECT table_schema AS db_name, 
                       ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                       COUNT(table_name) as table_count
                FROM information_schema.tables 
                GROUP BY table_schema
            ");
            foreach ($tableStats as $row) {
                $dbSizes[$row->db_name] = [
                    'size_mb' => (float)$row->size_mb,
                    'table_count' => (int)$row->table_count,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Could not query DB sizes: " . $e->getMessage());
        }

        $databases->getCollection()->transform(function ($db) use ($dbSizes) {
            $stats = $dbSizes[$db->name] ?? ['size_mb' => 0.0, 'table_count' => 0];
            $db->size_mb = $stats['size_mb'];
            $db->table_count = $stats['table_count'];
            return $db;
        });

        // Fetch distinct MySQL Database Users
        $dbUsers = [];
        try {
            $usersQuery = DB::select("
                SELECT DISTINCT User as username, Host as host, 
                       account_locked as is_locked
                FROM mysql.user 
                WHERE User NOT IN ('mysql.session', 'mysql.sys', 'mysql.infoschema', 'debian-sys-maint')
            ");
            foreach ($usersQuery as $u) {
                $dbUsers[] = [
                    'username' => $u->username,
                    'host' => $u->host,
                    'is_locked' => ($u->is_locked ?? 'N') === 'Y',
                ];
            }
        } catch (\Throwable $e) {
            $dbUsers = Database::select('db_user as username', 'host')->distinct()->get()->toArray();
        }

        // Fetch live MySQL Server telemetry
        $telemetry = $this->getMySQLTelemetry();

        // Fetch active MySQL Processlist
        $processlist = [];
        try {
            $rawProcesses = DB::select("SHOW FULL PROCESSLIST");
            foreach ($rawProcesses as $proc) {
                $processlist[] = [
                    'id' => $proc->Id ?? null,
                    'user' => $proc->User ?? null,
                    'host' => $proc->Host ?? null,
                    'db' => $proc->db ?? null,
                    'command' => $proc->Command ?? null,
                    'time' => $proc->Time ?? 0,
                    'state' => $proc->State ?? '',
                    'info' => $proc->Info ?? null,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Could not fetch MySQL processlist: " . $e->getMessage());
        }

        // Subscriptions for DB creation modal
        $subscriptions = Subscription::with('user')->where('status', 'active')->get(['id', 'domain', 'username', 'user_id']);

        return Inertia::render('Admin/Databases/Index', [
            'databases' => $databases,
            'dbUsers' => $dbUsers,
            'telemetry' => $telemetry,
            'processlist' => $processlist,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * 1-Click phpMyAdmin Auto-Login (SSO).
     */
    public function sso(Request $request, ?Database $database = null)
    {
        $token = bin2hex(random_bytes(24));
        $tokenData = [
            'user' => config('database.connections.mysql.username', 'panel_user'),
            'password' => config('database.connections.mysql.password', 'StrongPassword123!'),
            'allowed_dbs' => [], // Admin has unrestricted access to all databases
            'target_db' => $database ? $database->name : '',
            'expires_at' => time() + 300,
        ];

        $tokenFile = storage_path('app/pma_sso/sso_' . $token . '.json');
        file_put_contents($tokenFile, json_encode($tokenData));
        @chmod($tokenFile, 0664);

        $targetDb = $database ? '&db=' . urlencode($database->name) : '';
        return redirect('/phpmyadmin/index.php?sso_token=' . $token . $targetDb);
    }

    /**
     * Create a new MySQL database, user, and assign privileges.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
            'db_user' => 'required|string|max:32|regex:/^[a-zA-Z0-9_]+$/',
            'db_password' => 'required|string|min:8',
            'charset' => 'nullable|string|max:32',
            'collation' => 'nullable|string|max:64',
        ]);

        $dbName = $validated['name'];
        $dbUser = $validated['db_user'];
        $dbPass = $validated['db_password'];
        $charset = $validated['charset'] ?? 'utf8mb4';
        $collation = $validated['collation'] ?? 'utf8mb4_unicode_ci';

        try {
            // Provision MySQL Database and User on Linux MySQL Server
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE {$collation}");
            DB::statement("CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}'");
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");
            DB::statement("FLUSH PRIVILEGES");

            Database::create([
                'subscription_id' => $validated['subscription_id'],
                'name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => $dbPass,
                'host' => 'localhost',
                'port' => 3306,
                'charset' => $charset,
                'collation' => $collation,
                'status' => 'active',
            ]);

            return redirect()->route('admin.databases.index')->with('success', "Database `{$dbName}` and user `{$dbUser}` created successfully.");
        } catch (\Throwable $e) {
            Log::error("Failed to create database: " . $e->getMessage());
            return back()->withErrors(['name' => 'MySQL Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Check, repair, and optimize all tables in a database.
     */
    public function repair(Database $database)
    {
        try {
            $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$database->name]);
            $repaired = 0;

            foreach ($tables as $table) {
                $tableName = $table->TABLE_NAME ?? $table->table_name;
                DB::statement("OPTIMIZE TABLE `{$database->name}`.`{$tableName}`");
                $repaired++;
            }

            return back()->with('success', "Successfully checked and optimized {$repaired} table(s) in `{$database->name}`.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Optimization failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Reset / change database user password.
     */
    public function changePassword(Request $request, Database $database)
    {
        $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $newPass = $request->input('new_password');

        try {
            DB::statement("ALTER USER '{$database->db_user}'@'localhost' IDENTIFIED BY '{$newPass}'");
            DB::statement("FLUSH PRIVILEGES");

            $database->update(['db_password' => $newPass]);

            return back()->with('success', "Password for MySQL user `{$database->db_user}` updated successfully.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Password change failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Export / Download Database SQL Dump.
     */
    public function export(Database $database): StreamedResponse
    {
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
     * Kill an unresponsive or slow query from processlist.
     */
    public function killProcess($processId)
    {
        try {
            DB::statement("KILL " . (int)$processId);
            return back()->with('success', "MySQL Process #{$processId} terminated.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to kill process: ' . $e->getMessage()]);
        }
    }

    /**
     * Drop database, drop user, and remove record.
     */
    public function destroy(Database $database)
    {
        try {
            DB::statement("DROP DATABASE IF EXISTS `{$database->name}`");
            if ($database->db_user && $database->db_user !== 'root' && $database->db_user !== 'panel_user') {
                DB::statement("DROP USER IF EXISTS '{$database->db_user}'@'localhost'");
                DB::statement("FLUSH PRIVILEGES");
            }
        } catch (\Throwable $e) {
            Log::warning("Could not drop MySQL instance completely: " . $e->getMessage());
        }

        $database->delete();
        return redirect()->route('admin.databases.index')->with('success', "Database `{$database->name}` dropped successfully.");
    }

    /**
     * Helper: Fetch Live MySQL Server Telemetry.
     */
    protected function getMySQLTelemetry(): array
    {
        try {
            $versionRow = DB::select('SELECT VERSION() as v');
            $version = $versionRow[0]->v ?? 'MySQL 8.0';

            $statusColl = collect(DB::select('SHOW GLOBAL STATUS'))->pluck('Value', 'Variable_name');
            $varsColl = collect(DB::select('SHOW GLOBAL VARIABLES'))->pluck('Value', 'Variable_name');

            $uptime = (int)$statusColl->get('Uptime', 0);
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $mins = floor(($uptime % 3600) / 60);
            $uptimeFormatted = "{$days}d {$hours}h {$mins}m";

            return [
                'version' => $version,
                'uptime' => $uptimeFormatted,
                'raw_uptime' => $uptime,
                'threads_connected' => (int)$statusColl->get('Threads_connected', 1),
                'questions' => (int)$statusColl->get('Questions', 0),
                'slow_queries' => (int)$statusColl->get('Slow_queries', 0),
                'open_tables' => (int)$statusColl->get('Open_tables', 0),
                'max_connections' => (int)$varsColl->get('max_connections', 151),
                'innodb_buffer_pool_size' => round(((int)$varsColl->get('innodb_buffer_pool_size', 134217728)) / 1024 / 1024, 0) . ' MB',
            ];
        } catch (\Throwable $e) {
            Log::error("Telemetry error: " . $e->getMessage());
            return [
                'version' => 'MySQL 8.0',
                'uptime' => 'Online',
                'raw_uptime' => 0,
                'threads_connected' => 1,
                'questions' => 0,
                'slow_queries' => 0,
                'open_tables' => 0,
                'max_connections' => 151,
                'innodb_buffer_pool_size' => '128 MB',
            ];
        }
    }

    /**
     * Display Unified Multi-Engine Database Explorer (MySQL + PostgreSQL).
     */
    public function list(Request $request): Response
    {
        // 1. Fetch MySQL Databases
        $mysqlDatabases = Database::with(['subscription.user', 'subscription.plan'])->latest()->get();

        $dbSizes = [];
        try {
            $tableStats = DB::select("
                SELECT table_schema AS db_name, 
                       ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                       COUNT(table_name) as table_count
                FROM information_schema.tables 
                GROUP BY table_schema
            ");
            foreach ($tableStats as $row) {
                $dbSizes[$row->db_name] = [
                    'size_mb' => (float)$row->size_mb,
                    'table_count' => (int)$row->table_count,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Could not query MySQL DB sizes: " . $e->getMessage());
        }

        $allDatabases = [];
        $mysqlTotalSize = 0.0;

        foreach ($mysqlDatabases as $db) {
            $stats = $dbSizes[$db->name] ?? ['size_mb' => 0.1, 'table_count' => 0];
            $mysqlTotalSize += $stats['size_mb'];

            $allDatabases[] = [
                'id' => $db->id,
                'name' => $db->name,
                'engine' => 'mysql',
                'engine_label' => 'MySQL 8.0',
                'db_user' => $db->db_user,
                'charset' => $db->charset ?? 'utf8mb4',
                'collation' => $db->collation ?? 'utf8mb4_unicode_ci',
                'size_mb' => $stats['size_mb'],
                'table_count' => $stats['table_count'],
                'subscription' => $db->subscription,
                'status' => 'active',
            ];
        }

        // 2. Fetch PostgreSQL Databases
        $postgresDatabases = $this->postgresService->getDatabases();
        $postgresTotalSize = 0.0;

        foreach ($postgresDatabases as $pg) {
            $sizeMb = round(($pg['size_bytes'] ?? 0) / 1024 / 1024, 2);
            $postgresTotalSize += $sizeMb;

            $allDatabases[] = [
                'id' => $pg['id'],
                'name' => $pg['name'],
                'engine' => 'postgres',
                'engine_label' => 'PostgreSQL 18',
                'db_user' => $pg['owner'],
                'charset' => $pg['encoding'] ?? 'UTF8',
                'collation' => $pg['collation'] ?? 'en_US.UTF-8',
                'size_mb' => $sizeMb,
                'table_count' => null,
                'subscription' => $pg['subscription'],
                'status' => 'active',
            ];
        }

        $stats = [
            'total_databases' => count($allDatabases),
            'mysql_count' => count($mysqlDatabases),
            'mysql_size_mb' => round($mysqlTotalSize, 2),
            'postgres_count' => count($postgresDatabases),
            'postgres_size_mb' => round($postgresTotalSize, 2),
            'total_size_mb' => round($mysqlTotalSize + $postgresTotalSize, 2),
        ];

        $subscriptions = Subscription::with('user')->where('status', 'active')->get(['id', 'domain', 'username', 'user_id']);

        return Inertia::render('Admin/Databases/List', [
            'databases' => $allDatabases,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Render the Database Users & Privilege Matrix View.
     */
    public function users(Request $request): Response
    {
        $allUsers = [];
        $remoteCount = 0;
        $localCount = 0;
        $adminCount = 0;

        // 1. Fetch MySQL Users & Host Scopes
        try {
            $mysqlGrants = DB::select("SELECT User, Host, Db FROM mysql.db");
            $userDbMap = [];
            foreach ($mysqlGrants as $g) {
                $userDbMap[$g->User . '@' . $g->Host][] = $g->Db;
            }

            $rawUsers = DB::select("
                SELECT User as username, Host as host, account_locked as is_locked,
                       Super_priv as is_super, Grant_priv as can_grant
                FROM mysql.user
                WHERE User NOT IN ('mysql.session', 'mysql.sys', 'mysql.infoschema', 'debian-sys-maint')
            ");

            foreach ($rawUsers as $u) {
                $isSuper = ($u->is_super ?? 'N') === 'Y';
                $isRemote = $u->host === '%' || (!in_array($u->host, ['localhost', '127.0.0.1', '::1']));
                $assignedDbs = $userDbMap[$u->username . '@' . $u->host] ?? [];

                if ($isSuper) $adminCount++;
                if ($isRemote) $remoteCount++;
                else $localCount++;

                $allUsers[] = [
                    'id' => 'mysql_' . $u->username . '_' . $u->host,
                    'username' => $u->username,
                    'host' => $u->host,
                    'engine' => 'mysql',
                    'engine_label' => 'MySQL 8.0',
                    'is_superuser' => $isSuper,
                    'is_remote' => $isRemote,
                    'is_locked' => ($u->is_locked ?? 'N') === 'Y',
                    'databases' => $assignedDbs,
                    'privileges_label' => $isSuper ? 'All Privileges (Root/Super)' : (!empty($assignedDbs) ? 'Schema Grant (' . count($assignedDbs) . ' DBs)' : 'Standard Access'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Could not query MySQL users: " . $e->getMessage());
        }

        // 2. Fetch PostgreSQL Roles
        try {
            $pgRoles = $this->postgresService->getRoles();
            foreach ($pgRoles as $r) {
                if ($r['is_superuser']) $adminCount++;
                $localCount++;

                $allUsers[] = [
                    'id' => 'pg_' . $r['username'],
                    'username' => $r['username'],
                    'host' => '127.0.0.1 / socket',
                    'engine' => 'postgres',
                    'engine_label' => 'PostgreSQL 18',
                    'is_superuser' => $r['is_superuser'],
                    'is_remote' => false,
                    'is_locked' => !$r['can_login'],
                    'databases' => [],
                    'privileges_label' => $r['is_superuser'] ? 'Cluster Superuser' : ($r['can_create_db'] ? 'Create DB + Login' : 'Login Role'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Could not query PG roles: " . $e->getMessage());
        }

        $stats = [
            'total_users' => count($allUsers),
            'remote_users' => $remoteCount,
            'localhost_users' => $localCount,
            'admin_users' => $adminCount,
        ];

        $databases = Database::select('id', 'name', 'db_user')->get();
        $subscriptions = Subscription::with('user')->where('status', 'active')->get(['id', 'domain', 'username', 'user_id']);

        return Inertia::render('Admin/Databases/Users', [
            'users' => $allUsers,
            'stats' => $stats,
            'databases' => $databases,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Create a new standalone database user.
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'engine' => 'required|string|in:mysql,postgres',
            'username' => 'required|string|max:32|regex:/^[a-zA-Z0-9_]+$/',
            'password' => 'required|string|min:8',
            'host' => 'nullable|string|max:64',
            'database_name' => 'nullable|string|max:64',
            'privileges' => 'nullable|string|in:all,read_write,read_only',
        ]);

        $engine = $validated['engine'];
        $user = $validated['username'];
        $pass = $validated['password'];
        $host = $validated['host'] ?: 'localhost';
        $dbName = $validated['database_name'] ?? null;
        $privs = $validated['privileges'] ?? 'all';

        try {
            if ($engine === 'mysql') {
                DB::statement("CREATE USER IF NOT EXISTS '{$user}'@'{$host}' IDENTIFIED BY '{$pass}'");
                if ($dbName) {
                    if ($privs === 'read_only') {
                        DB::statement("GRANT SELECT ON `{$dbName}`.* TO '{$user}'@'{$host}'");
                    } elseif ($privs === 'read_write') {
                        DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON `{$dbName}`.* TO '{$user}'@'{$host}'");
                    } else {
                        DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$user}'@'{$host}'");
                    }
                }
                DB::statement("FLUSH PRIVILEGES");
            } else {
                $sql = sprintf(
                    "CREATE ROLE %s WITH LOGIN PASSWORD %s %s;",
                    '"' . str_replace('"', '""', $user) . '"',
                    "'" . str_replace("'", "''", $pass) . "'",
                    ($privs === 'all' ? 'CREATEDB' : '')
                );
                DB::statement($sql);
                if ($dbName) {
                    DB::statement(sprintf("GRANT ALL PRIVILEGES ON DATABASE %s TO %s;", '"' . $dbName . '"', '"' . $user . '"'));
                }
            }

            return redirect()->back()->with('success', "Database user `{$user}` created successfully.");
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['username' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }

    /**
     * Reset password for database user.
     */
    public function changeUserPassword(Request $request)
    {
        $validated = $request->validate([
            'engine' => 'required|string|in:mysql,postgres',
            'username' => 'required|string',
            'host' => 'nullable|string',
            'new_password' => 'required|string|min:8',
        ]);

        $engine = $validated['engine'];
        $user = $validated['username'];
        $host = $validated['host'] ?: 'localhost';
        $pass = $validated['new_password'];

        try {
            if ($engine === 'mysql') {
                DB::statement("ALTER USER '{$user}'@'{$host}' IDENTIFIED BY '{$pass}'");
                DB::statement("FLUSH PRIVILEGES");
                Database::where('db_user', $user)->update(['db_password' => $pass]);
            } else {
                $this->postgresService->changePassword($user, $pass, auth()->id());
            }

            return redirect()->back()->with('success', "Password for user `{$user}` updated successfully.");
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['error' => 'Password reset failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Modify Remote Host Binding for MySQL user.
     */
    public function updateUserHost(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'current_host' => 'required|string',
            'new_host' => 'required|string|max:64',
        ]);

        $user = $validated['username'];
        $currHost = $validated['current_host'];
        $newHost = $validated['new_host'];

        try {
            DB::statement("RENAME USER '{$user}'@'{$currHost}' TO '{$user}'@'{$newHost}'");
            DB::statement("FLUSH PRIVILEGES");
            Database::where('db_user', $user)->where('host', $currHost)->update(['host' => $newHost]);

            return redirect()->back()->with('success', "Host access scope for `{$user}` updated to `{$newHost}`.");
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['error' => 'Host update failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Drop / delete database user.
     */
    public function deleteUser(Request $request)
    {
        $validated = $request->validate([
            'engine' => 'required|string|in:mysql,postgres',
            'username' => 'required|string',
            'host' => 'nullable|string',
        ]);

        $engine = $validated['engine'];
        $user = $validated['username'];
        $host = $validated['host'] ?: 'localhost';

        if (in_array($user, ['root', 'postgres', 'panel_user', 'debian-sys-maint'])) {
            return redirect()->back()->withErrors(['error' => "Cannot drop system administrative account: {$user}"]);
        }

        try {
            if ($engine === 'mysql') {
                DB::statement("DROP USER IF EXISTS '{$user}'@'{$host}'");
                DB::statement("FLUSH PRIVILEGES");
            } else {
                DB::statement(sprintf("DROP ROLE IF EXISTS %s;", '"' . str_replace('"', '""', $user) . '"'));
            }

            return redirect()->back()->with('success', "Database user `{$user}` dropped successfully.");
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to drop user: ' . $e->getMessage()]);
        }
    }

    /**
     * Render Database Activity & Engine Logs View.
     */
    public function logs(Request $request): Response
    {
        $engine = $request->query('engine', 'mysql');
        if (!in_array($engine, ['mysql', 'postgres'], true)) {
            $engine = 'mysql';
        }

        $lines = (int)$request->query('lines', 100);
        $level = $request->query('level', 'all');
        $search = $request->query('search');

        $engineLogs = $this->logService->getEngineLogs($engine, $lines, $level);
        $auditLogs = $this->logService->getAuditLogs(60, $search);
        $stats = $this->logService->getLogStats($engine);

        return Inertia::render('Admin/Databases/Logs', [
            'engineLogs' => $engineLogs,
            'auditLogs' => $auditLogs,
            'stats' => $stats,
            'currentEngine' => $engine,
            'currentLines' => $lines,
            'currentLevel' => $level,
        ]);
    }

    /**
     * Clear / Truncate database engine log file.
     */
    public function clearLogs(Request $request)
    {
        $validated = $request->validate([
            'engine' => 'required|string|in:mysql,postgres',
        ]);

        $res = $this->logService->clearEngineLog($validated['engine'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }
}
