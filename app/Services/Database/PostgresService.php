<?php

namespace App\Services\Database;

use App\Models\ActivityLog;
use App\Models\Database;
use App\Models\Subscription;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostgresService
{
    use CommandExecutor;

    /**
     * Check if PostgreSQL service is installed and running.
     */
    public function isRunning(): bool
    {
        $res = $this->executeCommand(['systemctl', 'is-active', 'postgresql']);
        return trim($res['output'] ?? '') === 'active';
    }

    /**
     * Get live PostgreSQL telemetry and server metrics.
     */
    public function getTelemetry(): array
    {
        if (!$this->isRunning()) {
            return [
                'is_running' => false,
                'version' => 'PostgreSQL 18 (Not Active)',
                'uptime' => 'Offline',
                'active_connections' => 0,
                'max_connections' => 100,
                'shared_buffers' => '128 MB',
                'total_databases' => 0,
            ];
        }

        try {
            $versionRes = $this->runPsqlQuery("SELECT version();");
            $version = 'PostgreSQL 18.6';
            if (!empty($versionRes) && preg_match('/PostgreSQL\s+([0-9\.]+)/i', $versionRes, $m)) {
                $version = "PostgreSQL " . $m[1];
            }

            $uptimeRes = $this->runPsqlQuery("SELECT date_trunc('second', now() - pg_postmaster_start_time())::text;");
            $uptime = trim($uptimeRes) ?: 'Online';

            $connRes = $this->runPsqlQuery("SELECT count(*) FROM pg_stat_activity;");
            $activeConn = (int)trim($connRes) ?: 1;

            $maxConnRes = $this->runPsqlQuery("SHOW max_connections;");
            $maxConn = (int)trim($maxConnRes) ?: 100;

            $buffersRes = $this->runPsqlQuery("SHOW shared_buffers;");
            $buffers = trim($buffersRes) ?: '128MB';

            $dbCountRes = $this->runPsqlQuery("SELECT count(*) FROM pg_database WHERE datistemplate = false;");
            $dbCount = (int)trim($dbCountRes) ?: 0;

            return [
                'is_running' => true,
                'version' => $version,
                'uptime' => $uptime,
                'active_connections' => $activeConn,
                'max_connections' => $maxConn,
                'shared_buffers' => $buffers,
                'total_databases' => $dbCount,
            ];
        } catch (\Throwable $e) {
            Log::error("PostgreSQL Telemetry error: " . $e->getMessage());
            return [
                'is_running' => true,
                'version' => 'PostgreSQL 18',
                'uptime' => 'Online',
                'active_connections' => 1,
                'max_connections' => 100,
                'shared_buffers' => '128MB',
                'total_databases' => 0,
            ];
        }
    }

    /**
     * Get all PostgreSQL databases with sizes and owner details.
     */
    public function getDatabases(): array
    {
        if (!$this->isRunning()) {
            return [];
        }

        $query = "
            SELECT 
                d.datname as name,
                pg_catalog.pg_get_userbyid(d.datdba) as owner,
                pg_catalog.pg_encoding_to_char(d.encoding) as encoding,
                d.datcollate as collation,
                pg_catalog.pg_size_pretty(pg_catalog.pg_database_size(d.datname)) as size_formatted,
                pg_catalog.pg_database_size(d.datname) as size_bytes
            FROM pg_catalog.pg_database d
            WHERE d.datistemplate = false
            ORDER BY d.datname;
        ";

        $output = $this->runPsqlQuery($query);
        $lines = explode("\n", trim($output));

        $databases = [];
        // Map databases with local database records
        $dbRecords = Database::with(['subscription.user', 'subscription.plan'])->get()->keyBy('name');

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 6) {
                $name = trim($parts[0]);
                if (empty($name)) continue;

                $record = $dbRecords->get($name);

                $databases[] = [
                    'id' => $record ? $record->id : null,
                    'name' => $name,
                    'owner' => trim($parts[1]),
                    'encoding' => trim($parts[2]),
                    'collation' => trim($parts[3]),
                    'size_formatted' => trim($parts[4]),
                    'size_bytes' => (int)trim($parts[5]),
                    'subscription' => $record ? $record->subscription : null,
                    'is_system' => in_array($name, ['postgres']),
                    'status' => 'active',
                ];
            }
        }

        return $databases;
    }

    /**
     * Get all PostgreSQL Roles (Users).
     */
    public function getRoles(): array
    {
        if (!$this->isRunning()) {
            return [];
        }

        $query = "
            SELECT 
                r.rolname as username,
                r.rolsuper as is_superuser,
                r.rolcreaterole as can_create_role,
                r.rolcreatedb as can_create_db,
                r.rolcanlogin as can_login,
                r.rolconnlimit as conn_limit
            FROM pg_catalog.pg_roles r
            WHERE r.rolname NOT LIKE 'pg_%'
            ORDER BY r.rolname;
        ";

        $output = $this->runPsqlQuery($query);
        $lines = explode("\n", trim($output));

        $roles = [];
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 6) {
                $username = trim($parts[0]);
                if (empty($username)) continue;

                $roles[] = [
                    'username' => $username,
                    'is_superuser' => trim($parts[1]) === 't',
                    'can_create_role' => trim($parts[2]) === 't',
                    'can_create_db' => trim($parts[3]) === 't',
                    'can_login' => trim($parts[4]) === 't',
                    'conn_limit' => (int)trim($parts[5]),
                ];
            }
        }

        return $roles;
    }

    /**
     * Get live PostgreSQL Processlist (pg_stat_activity).
     */
    public function getProcesslist(): array
    {
        if (!$this->isRunning()) {
            return [];
        }

        $query = "
            SELECT 
                pid,
                usename,
                datname,
                client_addr::text,
                state,
                date_trunc('second', now() - query_start)::text as duration,
                LEFT(query, 120) as query_snippet
            FROM pg_stat_activity
            WHERE pid <> pg_backend_pid()
            ORDER BY query_start DESC NULLS LAST;
        ";

        $output = $this->runPsqlQuery($query);
        $lines = explode("\n", trim($output));

        $processes = [];
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 7) {
                $pid = (int)trim($parts[0]);
                if (!$pid) continue;

                $processes[] = [
                    'pid' => $pid,
                    'user' => trim($parts[1]),
                    'db' => trim($parts[2]) ?: '(None)',
                    'client' => trim($parts[3]) ?: 'local/socket',
                    'state' => trim($parts[4]) ?: 'idle',
                    'duration' => trim($parts[5]) ?: '0s',
                    'query' => trim($parts[6]),
                ];
            }
        }

        return $processes;
    }

    /**
     * Create PostgreSQL Database and User Role.
     */
    public function createDatabase(array $data, ?int $adminId = null): array
    {
        $dbName = $data['name'];
        $dbUser = $data['db_user'];
        $dbPass = $data['db_password'];
        $encoding = $data['encoding'] ?? 'UTF8';

        // 1. Create Role
        $createRoleSql = sprintf(
            "DO \$\$ BEGIN IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = %s) THEN CREATE ROLE %s WITH LOGIN PASSWORD %s; END IF; END \$\$;",
            $this->quoteLiteral($dbUser),
            $this->quoteIdent($dbUser),
            $this->quoteLiteral($dbPass)
        );

        $resRole = $this->runPsqlCommand($createRoleSql);
        if (!$resRole['success']) {
            return ['success' => false, 'error' => "Failed to create PostgreSQL role: " . $resRole['error']];
        }

        // 2. Create Database
        $createDbSql = sprintf(
            "CREATE DATABASE %s WITH OWNER = %s ENCODING = %s;",
            $this->quoteIdent($dbName),
            $this->quoteIdent($dbUser),
            $this->quoteLiteral($encoding)
        );

        $resDb = $this->runPsqlCommand($createDbSql);
        if (!$resDb['success']) {
            return ['success' => false, 'error' => "Failed to create PostgreSQL database: " . $resDb['error']];
        }

        // 3. Grant Privileges
        $grantSql = sprintf(
            "GRANT ALL PRIVILEGES ON DATABASE %s TO %s;",
            $this->quoteIdent($dbName),
            $this->quoteIdent($dbUser)
        );
        $this->runPsqlCommand($grantSql);

        // 4. Save Database record in DeepTouchHost model
        Database::create([
            'subscription_id' => $data['subscription_id'] ?? null,
            'name' => $dbName,
            'db_user' => $dbUser,
            'db_password' => $dbPass,
            'host' => '127.0.0.1',
            'port' => 5432,
            'charset' => $encoding,
            'collation' => 'en_US.UTF-8',
            'status' => 'active',
        ]);

        $this->logAction($adminId, 'postgres_database_created', [
            'name' => $dbName,
            'user' => $dbUser,
        ]);

        return [
            'success' => true,
            'message' => "PostgreSQL database `{$dbName}` and role `{$dbUser}` created successfully.",
        ];
    }

    /**
     * Reset PostgreSQL Role password.
     */
    public function changePassword(string $dbUser, string $newPass, ?int $adminId = null): array
    {
        $sql = sprintf(
            "ALTER ROLE %s WITH PASSWORD %s;",
            $this->quoteIdent($dbUser),
            $this->quoteLiteral($newPass)
        );

        $res = $this->runPsqlCommand($sql);
        if ($res['success']) {
            Database::where('db_user', $dbUser)->update(['db_password' => $newPass]);

            $this->logAction($adminId, 'postgres_password_changed', ['user' => $dbUser]);

            return [
                'success' => true,
                'message' => "Password for PostgreSQL role `{$dbUser}` updated successfully.",
            ];
        }

        return ['success' => false, 'error' => $res['error']];
    }

    /**
     * Vacuum & Analyze database.
     */
    public function vacuumAnalyze(string $dbName, ?int $adminId = null): array
    {
        $cmd = ['sudo', '-u', 'postgres', 'vacuumdb', '-d', $dbName, '-z', '-v'];
        $res = $this->executeCommand($cmd);

        if ($res['success']) {
            $this->logAction($adminId, 'postgres_vacuum_analyzed', ['database' => $dbName]);

            return [
                'success' => true,
                'message' => "PostgreSQL database `{$dbName}` vacuumed and analyzed successfully.",
            ];
        }

        return ['success' => false, 'error' => "Vacuum failed: " . ($res['error_output'] ?: $res['error'])];
    }

    /**
     * Terminate / Kill active PostgreSQL backend process.
     */
    public function terminateProcess(int $pid, ?int $adminId = null): array
    {
        $sql = "SELECT pg_terminate_backend({$pid});";
        $res = $this->runPsqlCommand($sql);

        if ($res['success']) {
            $this->logAction($adminId, 'postgres_process_terminated', ['pid' => $pid]);

            return [
                'success' => true,
                'message' => "PostgreSQL backend process #{$pid} terminated.",
            ];
        }

        return ['success' => false, 'error' => "Failed to terminate process #{$pid}: " . $res['error']];
    }

    /**
     * Drop PostgreSQL Database and Role.
     */
    public function dropDatabase(string $dbName, ?string $dbUser = null, ?int $adminId = null): array
    {
        if (in_array($dbName, ['postgres', 'template0', 'template1'])) {
            return ['success' => false, 'error' => "Cannot drop system template database: {$dbName}"];
        }

        // Terminate any active connections to the database first
        $termSql = sprintf(
            "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = %s AND pid <> pg_backend_pid();",
            $this->quoteLiteral($dbName)
        );
        $this->runPsqlCommand($termSql);

        // Drop Database
        $dropDbSql = sprintf("DROP DATABASE IF EXISTS %s;", $this->quoteIdent($dbName));
        $res = $this->runPsqlCommand($dropDbSql);

        if (!$res['success']) {
            return ['success' => false, 'error' => "Failed to drop database: " . $res['error']];
        }

        // Drop Role if not system
        if ($dbUser && !in_array($dbUser, ['postgres'])) {
            $dropRoleSql = sprintf("DROP ROLE IF EXISTS %s;", $this->quoteIdent($dbUser));
            $this->runPsqlCommand($dropRoleSql);
        }

        Database::where('name', $dbName)->delete();

        $this->logAction($adminId, 'postgres_database_dropped', ['database' => $dbName]);

        return [
            'success' => true,
            'message' => "PostgreSQL database `{$dbName}` dropped successfully.",
        ];
    }

    /**
     * Execute SQL query via psql with clean pipe-delimited unaligned output.
     */
    protected function runPsqlQuery(string $sql): string
    {
        $cmd = ['sudo', '-u', 'postgres', 'psql', '-t', '-A', '-F', '|', '-c', $sql];
        $res = $this->executeCommand($cmd);
        return $res['output'] ?? '';
    }

    /**
     * Execute an administrative SQL command via psql.
     */
    protected function runPsqlCommand(string $sql): array
    {
        $cmd = ['sudo', '-u', 'postgres', 'psql', '-c', $sql];
        $res = $this->executeCommand($cmd);
        return $res;
    }

    protected function quoteIdent(string $ident): string
    {
        return '"' . str_replace('"', '""', $ident) . '"';
    }

    protected function quoteLiteral(string $str): string
    {
        return "'" . str_replace("'", "''", $str) . "'";
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "PostgreSQL Manager: {$action}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to log PostgreSQL activity: " . $e->getMessage());
        }
    }
}
