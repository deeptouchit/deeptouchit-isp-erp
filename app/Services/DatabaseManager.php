<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseManager
{
    public function createDatabase(array $data): array
    {
        $dbName = $data['name'];
        $dbUser = $data['db_user'];
        $dbPassword = $data['db_password'] ?? Str::random(32);
        
        try {
            // Check if database exists
            $exists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);
            if (!empty($exists)) {
                return ['success' => false, 'error' => 'Database already exists'];
            }
            
            // Create database
            DB::statement("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Check and create user for localhost and 127.0.0.1
            foreach (['localhost', '127.0.0.1', '%'] as $host) {
                try {
                    $userExists = DB::select("SELECT User FROM mysql.user WHERE User = ? AND Host = ?", [$dbUser, $host]);
                    if (empty($userExists)) {
                        DB::statement("CREATE USER '{$dbUser}'@'{$host}' IDENTIFIED BY '{$dbPassword}'");
                    } else {
                        DB::statement("ALTER USER '{$dbUser}'@'{$host}' IDENTIFIED BY '{$dbPassword}'");
                    }
                    DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$host}'");
                } catch (\Exception $ue) {
                    // Ignore if host not permitted
                }
            }
            
            DB::statement("FLUSH PRIVILEGES");
            
            return [
                'success' => true,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => $dbPassword,
                'host' => '127.0.0.1',
                'port' => 3306
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function deleteDatabase(string $dbName): bool
    {
        try {
            DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function deleteDatabaseUser(string $dbUser): bool
    {
        try {
            DB::statement("DROP USER IF EXISTS '{$dbUser}'@'localhost'");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function changePassword(string $dbUser, string $newPassword): bool
    {
        try {
            DB::statement("ALTER USER '{$dbUser}'@'localhost' IDENTIFIED BY '{$newPassword}'");
            DB::statement("FLUSH PRIVILEGES");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getDatabaseSize(string $dbName): int
    {
        $result = DB::select("
            SELECT SUM(data_length + index_length) AS size 
            FROM information_schema.TABLES 
            WHERE table_schema = ?
        ", [$dbName]);
        
        return $result[0]->size ?? 0;
    }

    public function getTableCount(string $dbName): int
    {
        $result = DB::select("
            SELECT COUNT(*) AS count 
            FROM information_schema.TABLES 
            WHERE table_schema = ?
        ", [$dbName]);
        
        return $result[0]->count ?? 0;
    }
}
