<?php
namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ServerMonitor extends Command
{
    protected $signature = 'server:monitor';
    protected $description = 'Monitor server health metrics (CPU, RAM, Disk, Services)';

    public function handle(): int
    {
        try {
            // CPU usage
            $cpuUsage = (float) trim(shell_exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | cut -d'%' -f1") ?? '0');
            
            // RAM usage in MB
            $memTotal = (int) trim(shell_exec("free -m | grep Mem | awk '{print $2}'") ?? '0');
            $memUsed = (int) trim(shell_exec("free -m | grep Mem | awk '{print $3}'") ?? '0');
            
            // Disk usage in GB
            $diskTotal = (int) trim(shell_exec("df -BG / | tail -1 | awk '{print $2}' | tr -d 'G'") ?? '0');
            $diskUsed = (int) trim(shell_exec("df -BG / | tail -1 | awk '{print $3}' | tr -d 'G'") ?? '0');
            
            // Load averages
            $load = sys_getloadavg() ?: [0, 0, 0];
            
            // Core count
            $cpuCores = (int) trim(shell_exec("nproc") ?? '1');
            
            // Service statuses
            $nginxStatus = trim(shell_exec("systemctl is-active nginx 2>/dev/null") ?? 'offline');
            $mysqlStatus = trim(shell_exec("systemctl is-active mysql 2>/dev/null") ?? 'offline');
            
            $serverStatus = ($nginxStatus === 'active' && $mysqlStatus === 'active') ? 'online' : 'maintenance';
            
            // Update or create default server record
            $server = Server::firstOrNew(['id' => 1]);
            $server->name = $server->name ?? gethostname();
            $server->hostname = gethostname();
            $server->ip_address = $server->ip_address ?? (gethostbyname(gethostname()) ?: '127.0.0.1');
            $server->cpu_cores = $cpuCores;
            $server->total_ram = $memTotal;
            $server->used_ram = $memUsed;
            $server->total_disk = $diskTotal;
            $server->used_disk = $diskUsed;
            $server->load_avg_1min = $load[0] ?? 0;
            $server->load_avg_5min = $load[1] ?? 0;
            $server->load_avg_15min = $load[2] ?? 0;
            $server->status = $serverStatus;
            $server->last_ping_at = now();
            $server->save();
            
            $this->info("✅ Server metrics updated successfully: CPU: {$cpuUsage}%, RAM: {$memUsed}/{$memTotal}MB, Disk: {$diskUsed}/{$diskTotal}GB, Load: {$load[0]}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Server monitoring failed: ' . $e->getMessage());
            $this->error('Failed to collect server metrics: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
