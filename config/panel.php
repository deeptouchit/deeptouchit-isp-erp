<?php
return [
    'system' => [
        'name' => env('PANEL_NAME', 'DeepTouch Host'),
        'version' => '1.0.0',
        'timezone' => env('APP_TIMEZONE', 'Asia/Dhaka'),
        'currency' => env('PANEL_CURRENCY', 'BDT'),
        'currency_symbol' => env('PANEL_CURRENCY_SYMBOL', '৳'),
    ],
    
    'hosting' => [
        'base_path' => env('HOSTING_BASE_PATH', '/var/www/vhosts'),
        'backup_path' => env('BACKUP_BASE_PATH', '/var/www/backups'),
        'nginx_available' => env('NGINX_SITES_AVAILABLE', '/etc/nginx/sites-available'),
        'nginx_enabled' => env('NGINX_SITES_ENABLED', '/etc/nginx/sites-enabled'),
        'php_versions' => ['8.1', '8.2', '8.3', '8.5'],
        'default_php_version' => env('DEFAULT_PHP_VERSION', '8.2'),
        'default_cpu_limit' => env('DEFAULT_CPU_LIMIT', 50),
        'default_ram_limit' => env('DEFAULT_RAM_LIMIT', 512),
    ],
    
    'billing' => [
        'tax_rate' => env('BILLING_TAX_RATE', 0),
        'grace_period_days' => env('GRACE_PERIOD_DAYS', 3),
        'invoice_prefix' => env('INVOICE_PREFIX', 'INV-'),
        'reminder_days' => [7, 3, 1],
    ],
    
    'backup' => [
        'disk' => env('BACKUP_DISK', 'local'),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'compression_level' => env('BACKUP_COMPRESSION_LEVEL', 6),
    ],
    
    'gateways' => [
        'bkash' => [
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'base_url' => env('BKASH_BASE_URL', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'),
        ],
        'sslcommerz' => [
            'store_id' => env('SSLCOMMERZ_STORE_ID'),
            'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'initiate_url' => env('SSLCOMMERZ_INITIATE_URL', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'),
            'validation_url' => env('SSLCOMMERZ_VALIDATION_URL', 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'),
        ],
        'stripe' => [
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],
    ],
];
