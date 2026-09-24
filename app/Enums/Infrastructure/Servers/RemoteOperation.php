<?php

namespace App\Enums\Infrastructure\Servers;

use App\Exceptions\Infrastructure\Servers\ServerException;

enum RemoteOperation: string
{
    case VERIFY_CONNECTIVITY = 'verify_connectivity';
    case GET_OS_INFO = 'get_os_info';
    case GET_KERNEL_INFO = 'get_kernel_info';
    case GET_ARCH_INFO = 'get_arch_info';
    case GET_CPU_INFO = 'get_cpu_info';
    case GET_MEMORY_INFO = 'get_memory_info';
    case GET_DISK_INFO = 'get_disk_info';
    case GET_HOSTNAME = 'get_hostname';
    case GET_NETWORK_INTERFACES = 'get_network_interfaces';
    case GET_SYSTEMD_SERVICES = 'get_systemd_services';
    case GET_UPTIME_LOAD = 'get_uptime_load';
    
    // Service state commands
    case SYSTEMD_STATUS = 'systemd_status';
    case SYSTEMD_START = 'systemd_start';
    case SYSTEMD_STOP = 'systemd_stop';
    case SYSTEMD_RESTART = 'systemd_restart';
    case SYSTEMD_RELOAD = 'systemd_reload';

    // Allowlisted service names for systemd operations
    public const ALLOWED_SERVICES = [
        'nginx', 'apache2', 'httpd', 'mysql', 'mariadb',
        'php-fpm', 'php7.4-fpm', 'php8.0-fpm', 'php8.1-fpm', 'php8.2-fpm', 'php8.3-fpm', 'php8.4-fpm',
        'redis', 'redis-server', 'ufw', 'fail2ban', 'cron', 'sshd', 'named', 'bind9', 'postfix', 'dovecot'
    ];

    /**
     * Build the immutable, allowlisted shell command.
     * Throws ServerException on unallowed parameters or injection attempts.
     */
    public function getPredefinedCommand(array $params = []): string
    {
        return match ($this) {
            self::VERIFY_CONNECTIVITY => 'echo "__DEEPTOUCHHOST_PONG__"',
            self::GET_OS_INFO => 'cat /etc/os-release 2>/dev/null || cat /usr/lib/os-release 2>/dev/null',
            self::GET_KERNEL_INFO => 'uname -r',
            self::GET_ARCH_INFO => 'uname -m',
            self::GET_CPU_INFO => 'cat /proc/cpuinfo 2>/dev/null | grep -E "model name|processor|cpu cores" || nproc',
            self::GET_MEMORY_INFO => 'cat /proc/meminfo 2>/dev/null || free -b',
            self::GET_DISK_INFO => 'df -PB1 / 2>/dev/null',
            self::GET_HOSTNAME => 'hostname -f 2>/dev/null || hostname',
            self::GET_NETWORK_INTERFACES => 'ip -o addr 2>/dev/null || ifconfig -a 2>/dev/null',
            self::GET_SYSTEMD_SERVICES => 'systemctl list-units --type=service --state=running --no-pager --no-legend 2>/dev/null',
            self::GET_UPTIME_LOAD => 'cat /proc/loadavg 2>/dev/null && cat /proc/uptime 2>/dev/null',

            self::SYSTEMD_STATUS, self::SYSTEMD_START, self::SYSTEMD_STOP, self::SYSTEMD_RESTART, self::SYSTEMD_RELOAD => $this->buildServiceCommand($params),
        };
    }

    protected function buildServiceCommand(array $params): string
    {
        $service = $params['service'] ?? '';

        if (!in_array($service, self::ALLOWED_SERVICES, true)) {
            throw new ServerException("Service '{$service}' is not in the approved infrastructure whitelist.");
        }

        $action = match ($this) {
            self::SYSTEMD_STATUS => 'is-active',
            self::SYSTEMD_START => 'start',
            self::SYSTEMD_STOP => 'stop',
            self::SYSTEMD_RESTART => 'restart',
            self::SYSTEMD_RELOAD => 'reload',
            default => throw new ServerException("Invalid service action"),
        };

        // Strict command format using escapeshellarg
        return 'sudo systemctl ' . $action . ' ' . escapeshellarg($service);
    }
}
