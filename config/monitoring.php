<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agent Push Telemetry Configuration
    |--------------------------------------------------------------------------
    */
    'telemetry' => [
        'enabled' => env('MONITORING_TELEMETRY_ENABLED', true),
        'minimum_interval_seconds' => (int) env('MONITORING_MIN_INTERVAL', 15),
        'max_payload_bytes' => (int) env('MONITORING_MAX_PAYLOAD', 524288), // 512 KB
        'stale_after_seconds' => (int) env('MONITORING_STALE_AFTER', 180),
        'max_clock_skew_seconds' => (int) env('MONITORING_MAX_CLOCK_SKEW', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metric Retention Policy
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'raw_days' => (int) env('MONITORING_RETENTION_RAW_DAYS', 7),
        'hourly_days' => (int) env('MONITORING_RETENTION_HOURLY_DAYS', 30),
        'daily_days' => (int) env('MONITORING_RETENTION_DAILY_DAYS', 365),
        'prune_chunk_size' => (int) env('MONITORING_PRUNE_CHUNK_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Alert Threshold Defaults
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'cpu_warning' => (float) env('MONITORING_CPU_WARNING', 75.0),
        'cpu_critical' => (float) env('MONITORING_CPU_CRITICAL', 90.0),
        'memory_warning' => (float) env('MONITORING_MEMORY_WARNING', 80.0),
        'memory_critical' => (float) env('MONITORING_MEMORY_CRITICAL', 90.0),
        'disk_warning' => (float) env('MONITORING_DISK_WARNING', 80.0),
        'disk_critical' => (float) env('MONITORING_DISK_CRITICAL', 90.0),
        'load_warning' => (float) env('MONITORING_LOAD_WARNING', 4.0),
        'load_critical' => (float) env('MONITORING_LOAD_CRITICAL', 8.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Heartbeat & Liveness Thresholds
    |--------------------------------------------------------------------------
    */
    'heartbeat' => [
        'warning_after_seconds' => (int) env('MONITORING_HEARTBEAT_WARNING', 120),
        'offline_after_seconds' => (int) env('MONITORING_HEARTBEAT_OFFLINE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert State Engine Settings
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'enabled' => env('MONITORING_ALERTS_ENABLED', true),
        'default_cooldown_seconds' => (int) env('MONITORING_ALERT_COOLDOWN', 300),
        'default_duration_seconds' => (int) env('MONITORING_ALERT_DURATION', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'email' => [
            'enabled' => env('MONITORING_EMAIL_ENABLED', true),
            'to' => env('MONITORING_EMAIL_TO', env('MAIL_FROM_ADDRESS', 'admin@deeptouchhost.local')),
        ],
        'telegram' => [
            'enabled' => env('MONITORING_TELEGRAM_ENABLED', false),
            'bot_token' => env('MONITORING_TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('MONITORING_TELEGRAM_CHAT_ID'),
        ],
        'slack' => [
            'enabled' => env('MONITORING_SLACK_ENABLED', false),
            'webhook_url' => env('MONITORING_SLACK_WEBHOOK_URL'),
        ],
        'webhook' => [
            'enabled' => env('MONITORING_WEBHOOK_ENABLED', false),
            'url' => env('MONITORING_WEBHOOK_URL'),
            'secret' => env('MONITORING_WEBHOOK_SECRET'),
            'timeout_seconds' => (int) env('MONITORING_WEBHOOK_TIMEOUT', 5),
        ],
    ],
];
