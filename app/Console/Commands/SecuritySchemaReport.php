<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SecuritySchemaReport extends Command
{
    protected $signature = 'security:schema-report {--database= : Configured connection name}';

    protected $description = 'Read billing schema metadata without reading customer records or changing data';

    public function handle(): int
    {
        $connection = $this->option('database') ?: config('database.default');
        $schema = Schema::connection($connection);
        $required = [
            'tenant_subscriptions' => ['plan_id', 'started_at', 'current_period_start', 'current_period_end', 'next_billing_date', 'grace_period_ends_at', 'cancelled_at', 'suspended_at'],
            'saas_invoices' => ['invoice_no', 'tenant_subscription_id', 'saas_plan_id', 'amount', 'paid_amount', 'credit_amount', 'due_amount', 'period_start', 'period_end', 'trx_id'],
            'subscription_invoice_items' => ['total_price'],
            'payment_transactions' => ['transaction_reference', 'saas_invoice_id', 'gateway_response', 'initiated_at', 'completed_at'],
            'settings' => ['tenant_id'],
        ];
        try {
            $report = ['driver' => DB::connection($connection)->getDriverName(), 'tables' => []];
            foreach ($required as $table => $columns) {
                $exists = $schema->hasTable($table);
                $metadata = $exists ? $schema->getColumns($table) : [];
                $names = array_column($metadata, 'name');
                $report['tables'][$table] = [
                    'exists' => $exists,
                    'columns' => array_map(fn ($column) => array_intersect_key($column, array_flip(['name', 'type', 'type_name', 'nullable'])), $metadata),
                    'indexes' => $exists ? $schema->getIndexes($table) : [],
                    'missing_runtime_columns' => array_values(array_diff($columns, $names)),
                ];
            }
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            // Connection exception strings can contain credentials or SQL.
            $this->error('Schema inspection could not connect or read metadata ('.get_class($exception).').');
            return self::FAILURE;
        }
    }
}
