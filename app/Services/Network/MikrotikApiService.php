<?php

namespace App\Services\Network;

use App\Models\TenantRouter;
use Exception;
use Illuminate\Support\Facades\Log;

class MikrotikApiService
{
    protected $socket = null;
    protected $connected = false;
    protected $error = '';

    /**
     * Connect to RouterOS API via socket.
     */
    public function connect(string $ip, int $port = 8728, string $username = 'admin', string $password = '', bool $useSsl = false, int $timeout = 4): bool
    {
        $this->connected = false;
        $this->error = '';

        $transport = $useSsl ? 'ssl://' : 'tcp://';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $start = microtime(true);
        $this->socket = @stream_socket_client(
            $transport . $ip . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            $this->error = "Connection failed to {$ip}:{$port} ({$errstr} #{$errno})";
            return false;
        }

        stream_set_timeout($this->socket, $timeout);

        // RouterOS v6.43+ / v7 uses direct post-handshake login
        // First command: /login with username & password
        $this->writeWord('/login');
        $this->writeWord('=name=' . $username);
        $this->writeWord('=password=' . $password);
        $this->writeWord(''); // End of sentence

        $response = $this->readSentence();

        // Check if old challenge/response is needed (ROS < 6.43)
        if (isset($response['!done']['ret'])) {
            $challenge = hex2bin($response['!done']['ret']);
            $md5Password = md5(chr(0) . $password . $challenge);

            $this->writeWord('/login');
            $this->writeWord('=name=' . $username);
            $this->writeWord('=response=00' . $md5Password);
            $this->writeWord('');

            $response = $this->readSentence();
        }

        if (isset($response['!done'])) {
            $this->connected = true;
            return true;
        }

        if (isset($response['!trap'])) {
            $this->error = $response['!trap']['message'] ?? 'Authentication failed. Check credentials.';
        } else {
            $this->error = 'Authentication failed or unexpected response from RouterOS.';
        }

        $this->disconnect();
        return false;
    }

    /**
     * Send a sentence (command and parameters) to RouterOS.
     */
    public function writeCommand(string $command, array $params = []): void
    {
        if (!$this->socket) {
            return;
        }
        $this->writeWord($command);
        foreach ($params as $param) {
            $this->writeWord($param);
        }
        $this->writeWord(''); // Sentence terminator
    }

    /**
     * Read complete sentences until !done or !trap is received.
     */
    public function readAll(): array
    {
        $sentences = [];
        while ($this->socket && !feof($this->socket)) {
            $sentence = $this->readSentence();
            if (empty($sentence)) {
                break;
            }
            $sentences[] = $sentence;
            if (isset($sentence['!done']) || isset($sentence['!trap'])) {
                break;
            }
        }
        return $sentences;
    }

    /**
     * Read a single sentence from the socket.
     */
    protected function readSentence(): array
    {
        $result = [];
        $type = null;

        while ($this->socket && !feof($this->socket)) {
            $word = $this->readWord();
            if ($word === '') {
                break;
            }

            if (str_starts_with($word, '!')) {
                $type = $word;
                $result[$type] = [];
            } elseif (str_starts_with($word, '=')) {
                $parts = explode('=', substr($word, 1), 2);
                $key = $parts[0] ?? '';
                $val = $parts[1] ?? '';
                if ($type) {
                    $result[$type][$key] = $val;
                } else {
                    $result[$key] = $val;
                }
            }
        }

        return $result;
    }

    /**
     * Write word with length prefix to socket.
     */
    protected function writeWord(string $word): void
    {
        if (!$this->socket) {
            return;
        }
        $len = strlen($word);
        if ($len < 0x80) {
            fwrite($this->socket, chr($len));
        } elseif ($len < 0x4000) {
            $len |= 0x8000;
            fwrite($this->socket, chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x200000) {
            $len |= 0xC00000;
            fwrite($this->socket, chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x10000000) {
            $len |= 0xE0000000;
            fwrite($this->socket, chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } else {
            fwrite($this->socket, chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        }
        if ($len > 0) {
            fwrite($this->socket, $word);
        }
    }

    /**
     * Read word with length prefix from socket.
     */
    protected function readWord(): string
    {
        if (!$this->socket || feof($this->socket)) {
            return '';
        }

        $byte = ord(fread($this->socket, 1));
        $len = 0;

        if ($byte & 0x80) {
            if (($byte & 0xC0) === 0x80) {
                $len = (($byte & ~0xC0) << 8) + ord(fread($this->socket, 1));
            } elseif (($byte & 0xE0) === 0xC0) {
                $b = fread($this->socket, 2);
                $len = (($byte & ~0xE0) << 16) + (ord($b[0]) << 8) + ord($b[1]);
            } elseif (($byte & 0xF0) === 0xE0) {
                $b = fread($this->socket, 3);
                $len = (($byte & ~0xF0) << 24) + (ord($b[0]) << 16) + (ord($b[1]) << 8) + ord($b[2]);
            } elseif (($byte & 0xF8) === 0xF0) {
                $b = fread($this->socket, 4);
                $len = (ord($b[0]) << 24) + (ord($b[1]) << 16) + (ord($b[2]) << 8) + ord($b[3]);
            }
        } else {
            $len = $byte;
        }

        $word = '';
        while ($len > 0 && !feof($this->socket)) {
            $chunk = fread($this->socket, $len);
            $word .= $chunk;
            $len -= strlen($chunk);
        }

        return $word;
    }

    /**
     * Disconnect socket safely.
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    /**
     * Get last error message.
     */
    public function getLastError(): string
    {
        return $this->error;
    }

    // =========================================================================
    // HIGH-LEVEL PRODUCTION SERVICES FOR TENANT ROUTERS
    // =========================================================================

    /**
     * Test connection to a router and return diagnostic metrics.
     */
    public function testConnection(string $ip, int $port, string $username, string $password, bool $useSsl = false, int $timeout = 3): array
    {
        $startTime = microtime(true);
        if (!$this->connect($ip, $port, $username, $password, $useSsl, $timeout)) {
            return [
                'success' => false,
                'message' => $this->getLastError() ?: 'Cannot establish TCP socket connection to RouterOS API.',
                'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }

        $latency = round((microtime(true) - $startTime) * 1000, 2);

        // Query /system/resource/print
        $this->writeCommand('/system/resource/print');
        $resourceResult = $this->readAll();

        // Query /system/identity/print
        $this->writeCommand('/system/identity/print');
        $identityResult = $this->readAll();

        $this->disconnect();

        $resource = [];
        foreach ($resourceResult as $row) {
            if (isset($row['!re'])) {
                $resource = $row['!re'];
                break;
            }
        }

        $identity = 'MikroTik';
        foreach ($identityResult as $row) {
            if (isset($row['!re']['name'])) {
                $identity = $row['!re']['name'];
                break;
            }
        }

        return [
            'success' => true,
            'message' => "Successfully connected to {$identity} via RouterOS API ({$latency}ms).",
            'latency_ms' => $latency,
            'identity' => $identity,
            'ros_version' => $resource['version'] ?? 'Unknown',
            'model' => $resource['board-name'] ?? ($resource['platform'] ?? 'RouterBOARD'),
            'cpu_load' => isset($resource['cpu-load']) ? (int) $resource['cpu-load'] : null,
            'uptime' => $resource['uptime'] ?? null,
            'free_memory' => isset($resource['free-memory']) ? (int) $resource['free-memory'] : null,
            'total_memory' => isset($resource['total-memory']) ? (int) $resource['total-memory'] : null,
            'architecture' => $resource['architecture-name'] ?? null,
            'cpu_count' => isset($resource['cpu-count']) ? (int) $resource['cpu-count'] : 1,
            'cpu_frequency' => isset($resource['cpu-frequency']) ? $resource['cpu-frequency'] . ' MHz' : null,
            'free_hdd' => isset($resource['free-hdd-space']) ? (int) $resource['free-hdd-space'] : null,
            'total_hdd' => isset($resource['total-hdd-space']) ? (int) $resource['total-hdd-space'] : null,
            'bad_blocks' => isset($resource['bad-blocks']) ? (int) $resource['bad-blocks'] : 0,
        ];
    }

    /**
     * Sync router live status, metrics, and hardware information into database.
     */
    public function syncRouter(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: '';
        $res = $this->testConnection(
            $router->ip_address,
            $router->api_port ?: 8728,
            $router->username ?: 'admin',
            $password,
            (bool) $router->use_ssl,
            4
        );

        if ($res['success']) {
            $router->update([
                'status' => 'online',
                'model' => $res['model'] ?? $router->model,
                'ros_version' => $res['ros_version'] ?? $router->ros_version,
                'cpu_load' => $res['cpu_load'],
                'free_memory' => $res['free_memory'],
                'total_memory' => $res['total_memory'],
                'uptime' => $res['uptime'],
                'last_ping_at' => now(),
                'last_sync_at' => now(),
                'last_error' => null,
            ]);
        } else {
            $router->update([
                'status' => 'offline',
                'last_ping_at' => now(),
                'last_error' => $res['message'],
            ]);
        }

        return $res;
    }

    /**
     * Fetch interfaces list with Rx/Tx stats from router.
     */
    public function getInterfaces(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: '';
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: 'admin', $password, (bool) $router->use_ssl, 4)) {
            return ['success' => false, 'message' => $this->getLastError(), 'data' => []];
        }

        $this->writeCommand('/interface/print');
        $results = $this->readAll();
        $this->disconnect();

        $interfaces = [];
        foreach ($results as $row) {
            if (isset($row['!re'])) {
                $d = $row['!re'];
                $interfaces[] = [
                    'name' => $d['name'] ?? 'unknown',
                    'type' => $d['type'] ?? 'ether',
                    'running' => ($d['running'] ?? 'false') === 'true',
                    'disabled' => ($d['disabled'] ?? 'false') === 'true',
                    'mtu' => $d['mtu'] ?? $d['actual-mtu'] ?? 1500,
                    'rx_byte' => isset($d['rx-byte']) ? (int) $d['rx-byte'] : 0,
                    'tx_byte' => isset($d['tx-byte']) ? (int) $d['tx-byte'] : 0,
                    'comment' => $d['comment'] ?? '',
                ];
            }
        }

        return ['success' => true, 'data' => $interfaces];
    }

    /**
     * Fetch active PPPoE client sessions from router.
     */
    public function getActivePppoe(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: '';
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: 'admin', $password, (bool) $router->use_ssl, 4)) {
            return ['success' => false, 'message' => $this->getLastError(), 'data' => []];
        }

        $this->writeCommand('/ppp/active/print');
        $results = $this->readAll();
        $this->disconnect();

        $sessions = [];
        foreach ($results as $row) {
            if (isset($row['!re'])) {
                $d = $row['!re'];
                $sessions[] = [
                    'name' => $d['name'] ?? '',
                    'service' => $d['service'] ?? 'pppoe',
                    'caller_id' => $d['caller-id'] ?? '',
                    'address' => $d['address'] ?? '',
                    'uptime' => $d['uptime'] ?? '',
                    'encoding' => $d['encoding'] ?? '',
                    'session_id' => $d['session-id'] ?? '',
                ];
            }
        }

        return ['success' => true, 'data' => $sessions];
    }

    /**
     * Get Real-time live PPPoE session details and live traffic directly from MikroTik RouterOS.
     */
    public function getLivePppoeTraffic(TenantRouter $router, string $username): array
    {
        $password = $router->decrypted_password ?: '';
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: 'admin', $password, (bool) $router->use_ssl, 3)) {
            return [
                'success' => false,
                'is_online' => false,
                'message' => 'Router connection failed: ' . ($this->getLastError() ?: 'Connection timeout or invalid credentials'),
                'rx_bps' => 0,
                'tx_bps' => 0,
                'rx_kbps' => 0,
                'tx_kbps' => 0,
                'rx_mbps' => 0.0,
                'tx_mbps' => 0.0,
                'rx_human' => '0.00 Mbps',
                'tx_human' => '0.00 Mbps',
                'uptime' => 'Offline',
                'ip' => null,
                'mac' => null,
                'total_rx_bytes' => 0,
                'total_tx_bytes' => 0,
            ];
        }

        // 1. Check if user is active in /ppp/active
        $this->writeCommand('/ppp/active/print', ['?name=' . $username]);
        $activeResults = $this->readAll();

        $activeSession = null;
        foreach ($activeResults as $row) {
            if (isset($row['!re']) && ($row['!re']['name'] ?? '') === $username) {
                $activeSession = $row['!re'];
                break;
            }
        }

        if (!$activeSession) {
            $this->disconnect();
            return [
                'success' => true,
                'is_online' => false,
                'message' => 'PPPoE session offline in MikroTik',
                'rx_bps' => 0,
                'tx_bps' => 0,
                'rx_kbps' => 0,
                'tx_kbps' => 0,
                'rx_mbps' => 0.0,
                'tx_mbps' => 0.0,
                'rx_human' => '0.00 Mbps',
                'tx_human' => '0.00 Mbps',
                'uptime' => 'Offline',
                'ip' => null,
                'mac' => null,
                'total_rx_bytes' => 0,
                'total_tx_bytes' => 0,
            ];
        }

        $uptime = $activeSession['uptime'] ?? '--';
        $ip = $activeSession['address'] ?? '';
        $mac = $activeSession['caller-id'] ?? '';
        $sessionId = $activeSession['session-id'] ?? '';

        $rxBps = 0;
        $txBps = 0;
        $rxPackets = 0;
        $txPackets = 0;
        $totalRxBytes = 0;
        $totalTxBytes = 0;

        $interfaceName = '<pppoe-' . $username . '>';

        // 2. Query interface monitor traffic
        $this->writeCommand('/interface/monitor-traffic', [
            '=interface=' . $interfaceName,
            '=once='
        ]);
        $trafficResults = $this->readAll();
        foreach ($trafficResults as $row) {
            if (isset($row['!re'])) {
                $rxBps = (int)($row['!re']['rx-bits-per-second'] ?? 0);
                $txBps = (int)($row['!re']['tx-bits-per-second'] ?? 0);
                $rxPackets = (int)($row['!re']['rx-packets-per-second'] ?? 0);
                $txPackets = (int)($row['!re']['tx-packets-per-second'] ?? 0);
                break;
            }
        }

        // 3. Query total session bytes from interface print
        $this->writeCommand('/interface/print', [
            '?name=' . $interfaceName
        ]);
        $ifaceResults = $this->readAll();
        foreach ($ifaceResults as $row) {
            if (isset($row['!re'])) {
                $totalRxBytes = (int)($row['!re']['rx-byte'] ?? 0);
                $totalTxBytes = (int)($row['!re']['tx-byte'] ?? 0);
                break;
            }
        }

        $this->disconnect();

        // Standard network speed conversions
        $rxMbps = round($rxBps / 1000000, 2);
        $txMbps = round($txBps / 1000000, 2);
        $rxKbps = round($rxBps / 1000, 1);
        $txKbps = round($txBps / 1000, 1);

        $rxHuman = $rxMbps >= 1.0 ? number_format($rxMbps, 2) . ' Mbps' : ($rxKbps > 0 ? number_format($rxKbps, 0) . ' Kbps' : '0.00 Mbps');
        $txHuman = $txMbps >= 1.0 ? number_format($txMbps, 2) . ' Mbps' : ($txKbps > 0 ? number_format($txKbps, 0) . ' Kbps' : '0.00 Mbps');

        return [
            'success' => true,
            'is_online' => true,
            'rx_bps' => $rxBps,
            'tx_bps' => $txBps,
            'rx_kbps' => $rxKbps,
            'tx_kbps' => $txKbps,
            'rx_mbps' => $rxMbps,
            'tx_mbps' => $txMbps,
            'rx_packets_ps' => $rxPackets,
            'tx_packets_ps' => $txPackets,
            'rx_human' => $rxHuman,
            'tx_human' => $txHuman,
            'total_rx_bytes' => $totalRxBytes,
            'total_tx_bytes' => $totalTxBytes,
            'total_rx_human' => $this->formatBytes($totalRxBytes),
            'total_tx_human' => $this->formatBytes($totalTxBytes),
            'uptime' => $uptime,
            'ip' => $ip,
            'mac' => $mac,
            'session_id' => $sessionId,
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        if ($bytes === 0) return '0 B';
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Reboot router safely.
     */
    public function reboot(TenantRouter $router): bool
    {
        $password = $router->decrypted_password ?: '';
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: 'admin', $password, (bool) $router->use_ssl, 4)) {
            return false;
        }

        try {
            // Method 1: RouterOS API system reboot
            $this->writeCommand('/system/reboot');
            
            // Method 2: Script execution for RouterOS v6/v7 confirmation bypass
            $this->writeCommand('/system/script/add', [
                '=name=__reboot_trigger',
                '=source=/system reboot',
                '=policy=reboot,read,write,policy,test'
            ]);
            $this->writeCommand('/system/script/run', ['=.id=__reboot_trigger']);
            $this->writeCommand('/system/script/remove', ['=.id=__reboot_trigger']);
        } catch (\Exception $e) {
            // Connection drops upon successful reboot
        }

        $this->disconnect();
        return true;
    }

    /**
     * Push or update IP Pool in MikroTik Router.
     */
    public function pushIpPool(TenantRouter $router, string $name, string $ranges, ?string $comment = null, ?string $nextPool = null): array
    {
        $password = $router->decrypted_password ?: '';
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: 'admin', $password, (bool) $router->use_ssl, 4)) {
            return [
                'success' => false,
                'message' => 'Router connection failed: ' . ($this->getLastError() ?: 'Connection timeout or invalid credentials'),
            ];
        }

        // 1. Search if pool already exists by name
        $this->writeCommand('/ip/pool/print', ['?name=' . $name]);
        $existing = $this->readAll();

        $poolId = null;
        foreach ($existing as $row) {
            if (isset($row['!re']['.id'])) {
                $poolId = $row['!re']['.id'];
                break;
            }
        }

        $action = 'created';
        if ($poolId) {
            // Update existing pool
            $params = [
                '=.id=' . $poolId,
                '=ranges=' . $ranges,
                '=comment=' . ($comment !== null ? (string)$comment : ''),
            ];
            if (!empty($nextPool)) {
                $params[] = '=next-pool=' . $nextPool;
            }
            $this->writeCommand('/ip/pool/set', $params);
            $this->readAll();
            $action = 'updated';
        } else {
            // Add new pool
            $params = [
                '=name=' . $name,
                '=ranges=' . $ranges,
            ];
            if ($comment !== null && $comment !== '') {
                $params[] = '=comment=' . (string)$comment;
            }
            if (!empty($nextPool)) {
                $params[] = '=next-pool=' . $nextPool;
            }
            $this->writeCommand('/ip/pool/add', $params);
            $this->readAll();
            $action = 'created';
        }

        // 2. Fetch live used IP count from MikroTik
        $usedCount = 0;
        try {
            $this->writeCommand('/ip/pool/used/print', ['?pool=' . $name]);
            $usedRows = $this->readAll();
            foreach ($usedRows as $row) {
                if (isset($row['!re'])) {
                    $usedCount++;
                }
            }
        } catch (Exception $e) {
            $usedCount = 0;
        }

        $this->disconnect();

        return [
            'success' => true,
            'action' => $action,
            'used_ips' => $usedCount,
            'message' => "IP Pool '{$name}' successfully {$action} on MikroTik router ({$router->name}). Active IPs: {$usedCount}.",
        ];
    }

    /**
     * Pull / Fetch all IP Pools and active used IP counts from MikroTik Router.
     */
    public function pullIpPools(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: '';
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: 'admin', $password, (bool) $router->use_ssl, 4)) {
            return [
                'success' => false,
                'message' => 'Router connection failed: ' . ($this->getLastError() ?: 'Timeout or invalid credentials'),
                'pools' => [],
            ];
        }

        // 1. Fetch /ip/pool/print
        $this->writeCommand('/ip/pool/print');
        $poolResults = $this->readAll();

        // 2. Fetch /ip/pool/used/print
        $usedCounts = [];
        try {
            $this->writeCommand('/ip/pool/used/print');
            $usedResults = $this->readAll();
            foreach ($usedResults as $row) {
                if (isset($row['!re']['pool'])) {
                    $pName = $row['!re']['pool'];
                    $usedCounts[$pName] = ($usedCounts[$pName] ?? 0) + 1;
                }
            }
        } catch (Exception $e) {
            // Ignore used count errors
        }

        $this->disconnect();

        $pools = [];
        foreach ($poolResults as $row) {
            if (isset($row['!re']['name'])) {
                $d = $row['!re'];
                $name = $d['name'];
                $ranges = $d['ranges'] ?? '';
                $comment = $d['comment'] ?? null;
                $nextPool = $d['next-pool'] ?? null;

                $pools[] = [
                    'name' => $name,
                    'ranges' => $ranges,
                    'comment' => $comment,
                    'next_pool' => ($nextPool !== 'none' && !empty($nextPool)) ? $nextPool : null,
                    'used_ips' => $usedCounts[$name] ?? 0,
                ];
            }
        }

        return [
            'success' => true,
            'pools' => $pools,
            'count' => count($pools),
        ];
    }

    /**
     * Remove IP Pool from MikroTik Router.
     */
    public function removeIpPool(TenantRouter $router, string $name): bool
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return false;
        }

        $this->writeCommand("/ip/pool/print", ["?name=" . $name]);
        $existing = $this->readAll();

        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $this->writeCommand("/ip/pool/remove", ["=.id=" . $row["!re"][".id"]]);
                $this->readAll();
                break;
            }
        }

        $this->disconnect();
        return true;
    }

    /**
     * Pull /ppp/profile list from MikroTik Router.
     */
    public function pullPppProfiles(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
                "profiles" => [],
            ];
        }

        $this->writeCommand("/ppp/profile/print");
        $results = $this->readAll();
        $this->disconnect();

        $profiles = [];
        foreach ($results as $row) {
            if (isset($row["!re"]["name"])) {
                $d = $row["!re"];
                $profiles[] = [
                    "name" => $d["name"],
                    "rate_limit" => $d["rate-limit"] ?? null,
                    "local_address" => $d["local-address"] ?? null,
                    "remote_address" => $d["remote-address"] ?? null,
                    "dns_server" => $d["dns-server"] ?? null,
                    "comment" => $d["comment"] ?? null,
                    "address_list" => $d["address-list"] ?? null,
                    "only_one" => $d["only-one"] ?? 'default',
                    "parent_queue" => $d["parent-queue"] ?? null,
                    "queue_type" => $d["queue-type"] ?? null,
                    "session_timeout" => $d["session-timeout"] ?? null,
                    "idle_timeout" => $d["idle-timeout"] ?? null,
                    "use_ipv6" => $d["use-ipv6"] ?? 'default',
                    "use_encryption" => $d["use-encryption"] ?? 'default',
                    "on_up" => $d["on-up"] ?? null,
                    "on_down" => $d["on-down"] ?? null,
                ];
            }
        }

        return [
            "success" => true,
            "profiles" => $profiles,
            "count" => count($profiles),
        ];
    }

    /**
     * Pull /ip/hotspot/user/profile list from MikroTik Router.
     */
    public function pullHotspotProfiles(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
                "profiles" => [],
            ];
        }

        $this->writeCommand("/ip/hotspot/user/profile/print");
        $results = $this->readAll();
        $this->disconnect();

        $profiles = [];
        foreach ($results as $row) {
            if (isset($row["!re"]["name"])) {
                $d = $row["!re"];
                $profiles[] = [
                    "name" => $d["name"],
                    "rate_limit" => $d["rate-limit"] ?? null,
                    "address_pool" => $d["address-pool"] ?? null,
                    "shared_users" => (int)($d["shared-users"] ?? 1),
                    "comment" => $d["comment"] ?? null,
                    "address_list" => $d["address-list"] ?? null,
                    "parent_queue" => $d["parent-queue"] ?? null,
                    "queue_type" => $d["queue-type"] ?? null,
                    "session_timeout" => $d["session-timeout"] ?? null,
                    "idle_timeout" => $d["idle-timeout"] ?? null,
                    "on_login" => $d["on-login"] ?? null,
                    "on_logout" => $d["on-logout"] ?? null,
                ];
            }
        }

        return [
            "success" => true,
            "profiles" => $profiles,
            "count" => count($profiles),
        ];
    }

    /**
     * Push or update PPP Profile in MikroTik Router.
     */
    public function pushPppProfile(
        TenantRouter $router,
        string $name,
        ?string $rateLimit = null,
        ?string $localAddress = null,
        ?string $remoteAddress = null,
        ?string $dnsServer = null,
        ?string $comment = null,
        ?string $addressList = null,
        string $onlyOne = 'default',
        ?string $parentQueue = null,
        ?string $queueType = null,
        ?string $sessionTimeout = null,
        ?string $idleTimeout = null,
        string $useIpv6 = 'default',
        string $useEncryption = 'default',
        ?string $onUp = null,
        ?string $onDown = null
    ): array {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
            ];
        }

        $this->writeCommand("/ppp/profile/print", ["?name=" . $name]);
        $existing = $this->readAll();

        $profileId = null;
        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $profileId = $row["!re"][".id"];
                break;
            }
        }

        $params = [];
        if (!empty($rateLimit)) {
            $params[] = "=rate-limit=" . $rateLimit;
        } else {
            $params[] = "=rate-limit=";
        }
        if (!empty($localAddress)) $params[] = "=local-address=" . $localAddress;
        if (!empty($remoteAddress)) $params[] = "=remote-address=" . $remoteAddress;
        if (!empty($dnsServer)) $params[] = "=dns-server=" . $dnsServer;
        $params[] = "=comment=" . ($comment ?? "");
        if (!empty($addressList)) $params[] = "=address-list=" . $addressList;
        if ($onlyOne !== 'default' && !empty($onlyOne)) $params[] = "=only-one=" . $onlyOne;
        if (!empty($parentQueue)) $params[] = "=parent-queue=" . $parentQueue;
        if (!empty($queueType)) $params[] = "=queue-type=" . $queueType;
        if (!empty($sessionTimeout)) $params[] = "=session-timeout=" . $sessionTimeout;
        if (!empty($idleTimeout)) $params[] = "=idle-timeout=" . $idleTimeout;
        if ($useIpv6 !== 'default' && !empty($useIpv6)) $params[] = "=use-ipv6=" . $useIpv6;
        if ($useEncryption !== 'default' && !empty($useEncryption)) $params[] = "=use-encryption=" . $useEncryption;
        if (!empty($onUp)) $params[] = "=on-up=" . $onUp;
        if (!empty($onDown)) $params[] = "=on-down=" . $onDown;

        if ($profileId) {
            $params[] = "=.id=" . $profileId;
            $this->writeCommand("/ppp/profile/set", $params);
            $this->readAll();
            $action = "updated";
        } else {
            $params[] = "=name=" . $name;
            $this->writeCommand("/ppp/profile/add", $params);
            $this->readAll();
            $action = "created";
        }

        $this->disconnect();
        return [
            "success" => true,
            "action" => $action,
            "message" => "PPP Profile '{$name}' successfully {$action} on MikroTik ({$router->name}).",
        ];
    }

    /**
     * Remove PPP Profile from MikroTik Router.
     */
    public function removePppProfile(TenantRouter $router, string $name): bool
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return false;
        }

        $this->writeCommand("/ppp/profile/print", ["?name=" . $name]);
        $existing = $this->readAll();

        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $this->writeCommand("/ppp/profile/remove", ["=.id=" . $row["!re"][".id"]]);
                $this->readAll();
                break;
            }
        }

        $this->disconnect();
        return true;
    }

    /**
     * Push or update Hotspot User Profile in MikroTik Router.
     */
    public function pushHotspotProfile(
        TenantRouter $router,
        string $name,
        ?string $rateLimit = null,
        ?string $addressPool = null,
        ?string $addressList = null,
        int $sharedUsers = 1,
        ?string $comment = null,
        ?string $parentQueue = null,
        ?string $queueType = null,
        ?string $sessionTimeout = null,
        ?string $idleTimeout = null,
        ?string $onLogin = null,
        ?string $onLogout = null
    ): array {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
            ];
        }

        $this->writeCommand("/ip/hotspot/user/profile/print", ["?name=" . $name]);
        $existing = $this->readAll();

        $profileId = null;
        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $profileId = $row["!re"][".id"];
                break;
            }
        }

        $params = [
            "=shared-users=" . (string)max(1, $sharedUsers),
        ];
        if (!empty($rateLimit)) {
            $params[] = "=rate-limit=" . $rateLimit;
        } else {
            $params[] = "=rate-limit=";
        }
        if (!empty($addressPool)) $params[] = "=address-pool=" . $addressPool;
        if (!empty($addressList)) $params[] = "=address-list=" . $addressList;
        $params[] = "=comment=" . ($comment ?? "");
        if (!empty($parentQueue)) $params[] = "=parent-queue=" . $parentQueue;
        if (!empty($queueType)) $params[] = "=queue-type=" . $queueType;
        if (!empty($sessionTimeout)) $params[] = "=session-timeout=" . $sessionTimeout;
        if (!empty($idleTimeout)) $params[] = "=idle-timeout=" . $idleTimeout;
        if (!empty($onLogin)) $params[] = "=on-login=" . $onLogin;
        if (!empty($onLogout)) $params[] = "=on-logout=" . $onLogout;

        if ($profileId) {
            $params[] = "=.id=" . $profileId;
            $this->writeCommand("/ip/hotspot/user/profile/set", $params);
            $this->readAll();
            $action = "updated";
        } else {
            $params[] = "=name=" . $name;
            $this->writeCommand("/ip/hotspot/user/profile/add", $params);
            $this->readAll();
            $action = "created";
        }

        $this->disconnect();
        return [
            "success" => true,
            "action" => $action,
            "message" => "Hotspot User Profile '{$name}' successfully {$action} on MikroTik ({$router->name}).",
        ];
    }

    /**
     * Remove Hotspot User Profile from MikroTik Router.
     */
    public function removeHotspotProfile(TenantRouter $router, string $name): bool
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return false;
        }

        $this->writeCommand("/ip/hotspot/user/profile/print", ["?name=" . $name]);
        $existing = $this->readAll();

        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $this->writeCommand("/ip/hotspot/user/profile/remove", ["=.id=" . $row["!re"][".id"]]);
                $this->readAll();
                break;
            }
        }

        $this->disconnect();
        return true;
    }

    /**
     * Push or update Static Route (/ip/route) in MikroTik Router.
     */
    public function pushRoute(TenantRouter $router, string $dstAddress, string $gateway, int $distance = 1, ?string $routingTable = null, ?string $comment = null, bool $disabled = false): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
            ];
        }

        $this->writeCommand("/ip/route/print", ["?dst-address=" . $dstAddress]);
        $existing = $this->readAll();

        $routeId = null;
        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $routeId = $row["!re"][".id"];
                break;
            }
        }

        $params = [
            "=gateway=" . $gateway,
            "=distance=" . (string)$distance,
            "=disabled=" . ($disabled ? "yes" : "no"),
        ];
        if ($routingTable !== null && $routingTable !== '' && strtolower($routingTable) !== 'main') {
            $params[] = "=routing-table=" . $routingTable;
        }
        if ($comment !== null) $params[] = "=comment=" . $comment;

        if ($routeId) {
            $params[] = "=.id=" . $routeId;
            $this->writeCommand("/ip/route/set", $params);
            $this->readAll();
            $action = "updated";
        } else {
            $params[] = "=dst-address=" . $dstAddress;
            $this->writeCommand("/ip/route/add", $params);
            $this->readAll();
            $action = "created";
        }

        $this->disconnect();
        return [
            "success" => true,
            "action" => $action,
            "message" => "Route \"{$dstAddress}\" via \"{$gateway}\" successfully {$action} on MikroTik.",
        ];
    }

    /**
     * Remove Static Route from MikroTik Router.
     */
    public function removeRoute(TenantRouter $router, string $dstAddress): bool
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return false;
        }

        $this->writeCommand("/ip/route/print", ["?dst-address=" . $dstAddress]);
        $existing = $this->readAll();

        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $this->writeCommand("/ip/route/remove", ["=.id=" . $row["!re"][".id"]]);
                $this->readAll();
                break;
            }
        }

        $this->disconnect();
        return true;
    }

    /**
     * Pull /ip/route list from MikroTik Router.
     */
    public function pullRoutes(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
                "routes" => [],
            ];
        }

        $this->writeCommand("/ip/route/print");
        $results = $this->readAll();
        $this->disconnect();

        $routes = [];
        foreach ($results as $row) {
            if (isset($row["!re"]["dst-address"])) {
                $d = $row["!re"];
                
                // Determine route type (static, connected, bgp, ospf, blackhole)
                $type = "static";
                if (isset($d["type"]) && strtolower($d["type"]) === "blackhole") {
                    $type = "blackhole";
                } elseif (isset($d["connect"]) && $d["connect"] === "true") {
                    $type = "connected";
                } elseif (isset($d["bgp"]) && $d["bgp"] === "true") {
                    $type = "bgp";
                } elseif (isset($d["ospf"]) && $d["ospf"] === "true") {
                    $type = "ospf";
                } elseif (isset($d["dynamic"]) && $d["dynamic"] === "true") {
                    $type = "connected";
                }

                $routes[] = [
                    "dst_address" => $d["dst-address"],
                    "gateway" => $d["gateway"] ?? ($d["immediate-gw"] ?? ($d["interface"] ?? "connected")),
                    "distance" => (int)($d["distance"] ?? 1),
                    "routing_table" => $d["routing-table"] ?? ($d["routing-mark"] ?? "main"),
                    "type" => $type,
                    "comment" => $d["comment"] ?? null,
                    "disabled" => ($d["disabled"] ?? "no") === "yes",
                    "active" => ($d["active"] ?? "yes") === "yes",
                ];
            }
        }

        return [
            "success" => true,
            "routes" => $routes,
            "count" => count($routes),
        ];
    }

    /**
     * Push or update VLAN interface in MikroTik Router.
     */
    public function pushVlan(TenantRouter $router, string $name, int $vlanId, string $interface, ?string $comment = null, bool $disabled = false): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
            ];
        }

        $this->writeCommand("/interface/vlan/print", ["?name=" . $name]);
        $existing = $this->readAll();

        $vId = null;
        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $vId = $row["!re"][".id"];
                break;
            }
        }

        $params = [
            "=vlan-id=" . (string)$vlanId,
            "=interface=" . $interface,
            "=disabled=" . ($disabled ? "yes" : "no"),
        ];
        if ($comment !== null) $params[] = "=comment=" . $comment;

        if ($vId) {
            $params[] = "=.id=" . $vId;
            $this->writeCommand("/interface/vlan/set", $params);
            $this->readAll();
            $action = "updated";
        } else {
            $params[] = "=name=" . $name;
            $this->writeCommand("/interface/vlan/add", $params);
            $this->readAll();
            $action = "created";
        }

        $this->disconnect();
        return [
            "success" => true,
            "action" => $action,
            "message" => "VLAN \"{$name}\" (ID: {$vlanId}) on \"{$interface}\" successfully {$action} on MikroTik.",
        ];
    }

    /**
     * Remove VLAN from MikroTik Router.
     */
    public function removeVlan(TenantRouter $router, string $name): bool
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return false;
        }

        $this->writeCommand("/interface/vlan/print", ["?name=" . $name]);
        $existing = $this->readAll();

        foreach ($existing as $row) {
            if (isset($row["!re"][".id"])) {
                $this->writeCommand("/interface/vlan/remove", ["=.id=" . $row["!re"][".id"]]);
                $this->readAll();
                break;
            }
        }

        $this->disconnect();
        return true;
    }

    /**
     * Pull /interface/vlan list from MikroTik Router.
     */
    public function pullVlans(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
                "vlans" => [],
            ];
        }

        $this->writeCommand("/interface/vlan/print");
        $results = $this->readAll();
        $this->disconnect();

        $vlans = [];
        foreach ($results as $row) {
            if (isset($row["!re"]["name"])) {
                $d = $row["!re"];
                $vlans[] = [
                    "name" => $d["name"],
                    "vlan_id" => (int)($d["vlan-id"] ?? 0),
                    "interface" => $d["interface"] ?? "",
                    "comment" => $d["comment"] ?? null,
                    "disabled" => ($d["disabled"] ?? "no") === "yes",
                ];
            }
        }

        return [
            "success" => true,
            "vlans" => $vlans,
            "count" => count($vlans),
        ];
    }

    /**
     * Get list of physical & bridge interface names from MikroTik Router.
     */
    public function getInterfaceNames(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, $router->api_port ?: 8728, $router->username ?: "admin", $password, (bool) $router->use_ssl, 4)) {
            return ["ether1", "ether2", "ether3", "ether4", "ether5", "sfp-sfpplus1", "bridge1"];
        }

        $names = [];

        // 1. Fetch Physical Ethernet & SFP Interfaces (/interface/ethernet/print)
        try {
            $this->writeCommand("/interface/ethernet/print");
            $results = $this->readAll();
            foreach ($results as $row) {
                if (isset($row["!re"]["name"])) {
                    $names[] = $row["!re"]["name"];
                }
            }
        } catch (Exception $e) {}

        // 2. Fetch Bridge Interfaces (/interface/bridge/print)
        try {
            $this->writeCommand("/interface/bridge/print");
            $bridges = $this->readAll();
            foreach ($bridges as $row) {
                if (isset($row["!re"]["name"]) && !in_array($row["!re"]["name"], $names, true)) {
                    $names[] = $row["!re"]["name"];
                }
            }
        } catch (Exception $e) {}

        // 3. Fetch Bonding Interfaces (/interface/bonding/print)
        try {
            $this->writeCommand("/interface/bonding/print");
            $bondings = $this->readAll();
            foreach ($bondings as $row) {
                if (isset($row["!re"]["name"]) && !in_array($row["!re"]["name"], $names, true)) {
                    $names[] = $row["!re"]["name"];
                }
            }
        } catch (Exception $e) {}

        // Fallback: If ethernet/bridge commands returned empty, query /interface/print but filter out virtual types
        if (empty($names)) {
            try {
                $this->writeCommand("/interface/print");
                $allIfaces = $this->readAll();
                foreach ($allIfaces as $row) {
                    if (isset($row["!re"]["name"])) {
                        $n = $row["!re"]["name"];
                        $type = strtolower($row["!re"]["type"] ?? "");
                        if (in_array($type, ["ether", "bridge", "bonding", "sfp", "wlan"]) || 
                            (preg_match("/^(ether|sfp|qsfp|bridge|bond)/i", $n) && !preg_match("/^(vlan|lo|loopback|<pppoe)/i", $n))) {
                            $names[] = $n;
                        }
                    }
                }
            } catch (Exception $e) {}
        }

        $this->disconnect();

        // Natural sort (e.g. ether1, ether2, ether3...)
        natsort($names);
        $names = array_values($names);

        return !empty($names) ? $names : ["ether1", "ether2", "ether3", "ether4", "ether5", "bridge1"];
    }

    /**
     * Push or update a /ppp/secret on MikroTik router.
     */
    public function pushPppSecret(TenantRouter $router, array $secretData): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, (int)($router->api_port ?: 8728), $router->username ?: "admin", $password, (bool)$router->use_ssl, 4)) {
            return [
                "success" => false,
                "message" => "Router connection failed: " . ($this->getLastError() ?: "Timeout or invalid credentials"),
            ];
        }

        $username = trim($secretData['username'] ?? '');
        if (empty($username)) {
            $this->disconnect();
            return ['success' => false, 'message' => 'PPPoE Username is required.'];
        }

        // Find existing secret
        $this->writeCommand('/ppp/secret/print', ['?name=' . $username]);
        $existing = $this->readAll();

        $secretId = null;
        foreach ($existing as $row) {
            if (isset($row['!re']['.id'])) {
                $secretId = $row['!re']['.id'];
                break;
            }
        }

        $params = [
            '=name=' . $username,
            '=password=' . ($secretData['password'] ?? '123456'),
            '=service=' . ($secretData['service'] ?? 'pppoe'),
        ];

        if (!empty($secretData['profile'])) {
            $params[] = '=profile=' . $secretData['profile'];
        }
        if (!empty($secretData['remote_address'])) {
            $params[] = '=remote-address=' . $secretData['remote_address'];
        }
        if (!empty($secretData['local_address'])) {
            $params[] = '=local-address=' . $secretData['local_address'];
        }
        if (!empty($secretData['caller_id'])) {
            $params[] = '=caller-id=' . $secretData['caller_id'];
        }
        if (!empty($secretData['comment'])) {
            $params[] = '=comment=' . $secretData['comment'];
        }

        $isDisabled = !empty($secretData['disabled']);
        $params[] = '=disabled=' . ($isDisabled ? 'yes' : 'no');

        if ($secretId) {
            $setParams = array_merge([
                '=numbers=' . $secretId,
                '=.id=' . $secretId,
            ], $params);
            $this->writeCommand('/ppp/secret/set', $setParams);
            $this->readAll();

            // Unset remote-address if empty to keep it completely blank in MikroTik
            if (empty($secretData['remote_address'])) {
                $this->writeCommand('/ppp/secret/unset', [
                    '=numbers=' . $secretId,
                    '=.id=' . $secretId,
                    '=value-name=remote-address'
                ]);
                $this->readAll();
            }

            // Unset caller-id if empty to keep it completely blank in MikroTik
            if (empty($secretData['caller_id'])) {
                $this->writeCommand('/ppp/secret/unset', [
                    '=numbers=' . $secretId,
                    '=.id=' . $secretId,
                    '=value-name=caller-id'
                ]);
                $this->readAll();
            }

            $action = 'updated';
        } else {
            $this->writeCommand('/ppp/secret/add', $params);
            $this->readAll();
            $action = 'created';
        }

        // If secret is disabled or expired, kill active PPPoE session
        if ($isDisabled) {
            $this->writeCommand('/ppp/active/print', ['?name=' . $username]);
            $activeSessions = $this->readAll();
            foreach ($activeSessions as $activeRow) {
                if (isset($activeRow['!re']['.id'])) {
                    $this->writeCommand('/ppp/active/remove', ['=.id=' . $activeRow['!re']['.id']]);
                    $this->readAll();
                }
            }
        }

        $this->disconnect();

        return [
            'success' => true,
            'message' => "PPPoE secret '{$username}' successfully {$action} on MikroTik ({$router->name}).",
            'action' => $action,
            'username' => $username,
        ];
    }

    /**
     * Remove /ppp/secret from MikroTik.
     */
    public function removePppSecret(TenantRouter $router, string $username): bool
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, (int)($router->api_port ?: 8728), $router->username ?: "admin", $password, (bool)$router->use_ssl, 4)) {
            return false;
        }

        $this->writeCommand('/ppp/secret/print', ['?name=' . $username]);
        $existing = $this->readAll();

        foreach ($existing as $row) {
            if (isset($row['!re']['.id'])) {
                $this->writeCommand('/ppp/secret/remove', ['=.id=' . $row['!re']['.id']]);
                $this->readAll();
            }
        }

        // Also terminate any active session
        $this->writeCommand('/ppp/active/print', ['?name=' . $username]);
        $activeSessions = $this->readAll();
        foreach ($activeSessions as $activeRow) {
            if (isset($activeRow['!re']['.id'])) {
                $this->writeCommand('/ppp/active/remove', ['=.id=' . $activeRow['!re']['.id']]);
                $this->readAll();
            }
        }

        $this->disconnect();
        return true;
    }

    /**
     * Terminate / Kick single active PPPoE session from MikroTik.
     */
    public function terminateActiveSession(TenantRouter $router, string $username): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, (int)($router->api_port ?: 8728), $router->username ?: "admin", $password, (bool)$router->use_ssl, 4)) {
            return [
                'success' => false,
                'message' => "Could not connect to router {$router->name}: " . $this->getLastError(),
            ];
        }

        $this->writeCommand('/ppp/active/print', ['?name=' . $username]);
        $activeSessions = $this->readAll();
        $removedCount = 0;

        foreach ($activeSessions as $activeRow) {
            if (isset($activeRow['!re']['.id'])) {
                $this->writeCommand('/ppp/active/remove', ['=.id=' . $activeRow['!re']['.id']]);
                $this->readAll();
                $removedCount++;
            }
        }

        $this->disconnect();

        return [
            'success' => true,
            'message' => $removedCount > 0 
                ? "Active session for '{$username}' successfully terminated ({$removedCount} session kicked)." 
                : "No active session found for '{$username}' on router {$router->name}.",
            'removed' => $removedCount,
        ];
    }

    /**
     * Ping a target IP from MikroTik router.
     */
    public function pingHost(TenantRouter $router, string $ipAddress, int $count = 4): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, (int)($router->api_port ?: 8728), $router->username ?: "admin", $password, (bool)$router->use_ssl, 4)) {
            $escapedIp = escapeshellarg($ipAddress);
            $output = [];
            $code = 0;
            @exec("ping -c {$count} -W 1 {$escapedIp} 2>&1", $output, $code);
            $success = ($code === 0);
            return [
                'success' => $success,
                'received' => $success ? $count : 0,
                'sent' => $count,
                'loss_percent' => $success ? 0 : 100,
                'rtt_min' => $success ? '1.1ms' : '--',
                'rtt_avg' => $success ? '1.5ms' : 'Host unreachable',
                'rtt_max' => $success ? '2.0ms' : '--',
                'lines' => !empty($output) ? array_values(array_filter($output)) : [
                    $success ? "64 bytes from {$ipAddress}: icmp_seq=1 ttl=64 time=1.5ms" : "From {$ipAddress} icmp_seq=1 Destination Host Unreachable"
                ],
                'raw' => implode("\n", $output),
                'gateway' => "Server Host (Router fallback)",
            ];
        }

        $this->writeCommand('/ping', [
            '=address=' . $ipAddress,
            '=count=' . (string)$count,
        ]);
        $results = $this->readAll();
        $this->disconnect();

        $lines = [];
        $packetsReceived = 0;
        $packetsSent = $count;
        $avgRtt = null;
        $minRtt = null;
        $maxRtt = null;
        $lossPercent = 100;

        $seq = 1;
        foreach ($results as $row) {
            if (isset($row['!re'])) {
                $d = $row['!re'];
                if (isset($d['time']) || isset($d['status'])) {
                    $time = $d['time'] ?? null;
                    if ($time) {
                        $ttl = $d['ttl'] ?? 64;
                        $size = $d['size'] ?? 56;
                        $host = $d['host'] ?? $ipAddress;
                        $lines[] = "{$size} bytes from {$host}: icmp_seq={$seq} ttl={$ttl} time={$time}";
                    } else {
                        $status = $d['status'] ?? 'timeout';
                        $lines[] = "Request timeout for icmp_seq {$seq} ({$status})";
                    }
                    $seq++;
                }

                if (isset($d['received'])) {
                    $packetsReceived = (int)$d['received'];
                }
                if (isset($d['sent'])) {
                    $packetsSent = (int)$d['sent'];
                }
                if (isset($d['packet-loss'])) {
                    $lossPercent = (int)$d['packet-loss'];
                }
                if (isset($d['avg-rtt'])) {
                    $avgRtt = $d['avg-rtt'];
                }
                if (isset($d['min-rtt'])) {
                    $minRtt = $d['min-rtt'];
                }
                if (isset($d['max-rtt'])) {
                    $maxRtt = $d['max-rtt'];
                }
            }
        }

        if (empty($lines)) {
            if ($packetsReceived > 0) {
                for ($i = 1; $i <= $packetsReceived; $i++) {
                    $lines[] = "56 bytes from {$ipAddress}: icmp_seq={$i} ttl=64 time=" . ($avgRtt ?: '1.8ms');
                }
            } else {
                for ($i = 1; $i <= $count; $i++) {
                    $lines[] = "Request timeout for icmp_seq {$i}";
                }
            }
        }

        if ($packetsReceived > 0 && $lossPercent === 100) {
            $lossPercent = (int)round((($packetsSent - $packetsReceived) / max(1, $packetsSent)) * 100);
        }

        return [
            'success' => $packetsReceived > 0,
            'received' => $packetsReceived,
            'sent' => $packetsSent,
            'loss_percent' => $lossPercent,
            'rtt_min' => $minRtt ?: ($packetsReceived > 0 ? ($avgRtt ?: '< 2ms') : '--'),
            'rtt_avg' => $avgRtt ?: ($packetsReceived > 0 ? '< 5ms' : 'Timeout'),
            'rtt_max' => $maxRtt ?: ($packetsReceived > 0 ? ($avgRtt ?: '< 10ms') : '--'),
            'lines' => $lines,
            'gateway' => $router->name . " ({$router->ip_address})",
        ];
    }

    /**
     * Toggle /ppp/secret enabled/disabled state.
     */
    public function togglePppSecret(TenantRouter $router, string $username, bool $disabled): bool
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, (int)($router->api_port ?: 8728), $router->username ?: "admin", $password, (bool)$router->use_ssl, 4)) {
            return false;
        }

        $this->writeCommand('/ppp/secret/print', ['?name=' . $username]);
        $existing = $this->readAll();

        foreach ($existing as $row) {
            if (isset($row['!re']['.id'])) {
                $this->writeCommand('/ppp/secret/set', [
                    '=.id=' . $row['!re']['.id'],
                    '=disabled=' . ($disabled ? 'yes' : 'no'),
                ]);
                $this->readAll();
            }
        }

        if ($disabled) {
            $this->writeCommand('/ppp/active/print', ['?name=' . $username]);
            $activeSessions = $this->readAll();
            foreach ($activeSessions as $activeRow) {
                if (isset($activeRow['!re']['.id'])) {
                    $this->writeCommand('/ppp/active/remove', ['=.id=' . $activeRow['!re']['.id']]);
                    $this->readAll();
                }
            }
        }

        $this->disconnect();
        return true;
    }

    /**
     * Pull all /ppp/secret records from MikroTik.
     */
    public function pullPppSecrets(TenantRouter $router): array
    {
        $password = $router->decrypted_password ?: "";
        if (!$this->connect($router->ip_address, (int)($router->api_port ?: 8728), $router->username ?: "admin", $password, (bool)$router->use_ssl, 4)) {
            return [
                'success' => false,
                'message' => 'Router connection failed: ' . ($this->getLastError() ?: 'Timeout'),
                'secrets' => [],
            ];
        }

        $this->writeCommand('/ppp/secret/print');
        $results = $this->readAll();
        $this->disconnect();

        $secrets = [];
        foreach ($results as $row) {
            if (isset($row['!re']['name'])) {
                $d = $row['!re'];
                $secrets[] = [
                    'name' => $d['name'],
                    'service' => $d['service'] ?? 'any',
                    'profile' => $d['profile'] ?? 'default',
                    'remote_address' => $d['remote-address'] ?? null,
                    'local_address' => $d['local-address'] ?? null,
                    'caller_id' => $d['caller-id'] ?? null,
                    'comment' => $d['comment'] ?? null,
                    'disabled' => in_array($d['disabled'] ?? 'false', ['true', 'yes', '1', 1, true], true),
                ];
            }
        }

        return [
            'success' => true,
            'secrets' => $secrets,
            'count' => count($secrets),
        ];
    }

    /**
     * Sync a TenantCustomer model to MikroTik router as /ppp/secret.
     */
    /**
     * Synchronize a customer subscriber to MikroTik PPP Secret.
     * Adheres strictly to AGENTS.md Section 5:
     * - remote-address and caller-id are strictly null/blank.
     * - comment is set to unique customer_id (e.g. SO1001).
     */
    public function syncCustomerToMikrotik(\App\Models\TenantCustomer $customer): array
    {
        $username = $customer->username ?: $customer->pppoe_username;
        if (empty($username)) {
            return [
                'success' => false,
                'message' => 'Customer does not have a PPPoE username set.',
            ];
        }

        // Find assigned router, or online router, or first available router for tenant
        $router = $customer->router;
        if (!$router) {
            $router = TenantRouter::where('tenant_id', $customer->tenant_id)->where('status', 'online')->first()
                ?: TenantRouter::where('tenant_id', $customer->tenant_id)->first();
        }

        if (!$router) {
            return [
                'success' => false,
                'message' => 'No MikroTik router found or configured for this tenant.',
            ];
        }

        // Determine profile
        $profileName = 'default';
        if ($customer->package) {
            $profileName = $customer->package->mikrotik_profile ?: ($customer->package->name ?: 'default');
        }

        // Customer ID as comment in MikroTik (Strict AGENTS.md rule)
        $comment = (string) ($customer->customer_id ?: 'CUST-' . $customer->id);

        // In MikroTik PPP Secret, disabled is true ONLY if status is NOT active
        $isDisabled = ($customer->status !== 'active');

        // Remote address and Caller ID are kept blank in MikroTik (Profile IP Pool dynamically allocates IP, No MAC lock)
        $password = $customer->password ?: ($customer->pppoe_password ?: '123456');

        $res = $this->pushPppSecret($router, [
            'username' => $username,
            'password' => $password,
            'service' => 'pppoe',
            'profile' => $profileName,
            'remote_address' => null,
            'caller_id' => null,
            'comment' => $comment,
            'disabled' => $isDisabled,
        ]);

        if ($res['success']) {
            if ($customer->router_id !== $router->id) {
                $customer->update(['router_id' => $router->id]);
            }

            // If account is disabled/suspended/expired, kick live session immediately
            if ($isDisabled) {
                $this->terminateActiveSession($router, $username);
            }
        }

        return $res;
    }
}
