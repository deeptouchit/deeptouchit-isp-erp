export interface ServerGroup {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    location?: string;
}

export interface ServerMetric {
    id: number;
    server_id: number;
    cpu_usage: number;
    memory_total: number;
    memory_used: number;
    memory_available: number;
    disk_total: number;
    disk_used: number;
    disk_usage: number;
    load_1m: number;
    load_5m: number;
    load_15m: number;
    network_rx?: number;
    network_tx?: number;
    recorded_at?: string;
}

export interface ServerService {
    id: number;
    server_id: number;
    service_name: string;
    display_name?: string;
    service_type?: string;
    status: 'running' | 'stopped' | 'failed' | 'inactive' | 'unknown';
    enabled: boolean;
    last_checked_at?: string;
}

export interface ServerEvent {
    id: number;
    server_id: number;
    event_type: string;
    severity: 'info' | 'warning' | 'critical' | 'emergency';
    message: string;
    metadata?: Record<string, any>;
    occurred_at: string;
}

export interface ServerLog {
    id: number;
    server_id: number;
    log_type: string;
    level: string;
    message: string;
    context?: Record<string, any>;
    occurred_at: string;
}

export interface ServerItem {
    id: number;
    uuid: string;
    server_group_id?: number;
    server_group_name?: string;
    name: string;
    hostname: string;
    ip_address: string;
    primary_ip?: string;
    ipv6?: string;
    server_type: string;
    environment: string;
    status: 'pending' | 'verifying' | 'verified' | 'provisioning' | 'active' | 'online' | 'warning' | 'offline' | 'maintenance' | 'suspended' | 'decommissioning' | 'decommissioned';
    health_status: 'unknown' | 'healthy' | 'warning' | 'critical' | 'offline';
    is_master: boolean;
    os_name?: string;
    os_version?: string;
    kernel_version?: string;
    architecture?: string;
    cpu_cores: number;
    total_ram: number;
    used_ram: number;
    total_disk: number;
    used_disk: number;
    load_avg_1min: number;
    load_avg_5min: number;
    load_avg_15min: number;
    ssh_port: number;
    ssh_user: string;
    auth_type: string;
    ssh_host_key_policy: string;
    ssh_host_key_fingerprint?: string;
    trusted_ssh_host_key_fingerprint?: string;
    agent_version?: string;
    agent_installed_at?: string;
    last_ping_at?: string;
    last_seen_at?: string;
    last_health_check_at?: string;
    maintenance_at?: string;
    maintenance_reason?: string;
    created_at?: string;
    updated_at?: string;

    // Optional loaded relationships
    server_group?: ServerGroup;
    latest_metric?: ServerMetric;
    services?: ServerService[];
    events?: ServerEvent[];
    logs?: ServerLog[];
    subscriptions_count?: number;
}
