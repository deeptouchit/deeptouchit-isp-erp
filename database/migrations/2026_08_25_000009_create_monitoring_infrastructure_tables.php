<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('server_metric_aggregates');
        Schema::dropIfExists('monitoring_alert_states');
        Schema::dropIfExists('monitoring_alert_rules');

        // 1. Monitoring Alert Rules
        Schema::create('monitoring_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained('servers')->cascadeOnDelete();
            $table->string('alert_type', 50)->index(); // cpu_high, memory_high, disk_high, etc.
            $table->float('warning_threshold')->default(75.0);
            $table->float('critical_threshold')->default(90.0);
            $table->unsignedInteger('duration_seconds')->default(60);
            $table->unsignedInteger('cooldown_seconds')->default(300);
            $table->boolean('enabled')->default(true)->index();
            $table->json('channels')->nullable(); // ["email", "telegram", "slack", "webhook"]
            $table->timestamps();

            $table->index(['server_id', 'alert_type', 'enabled']);
        });

        // 2. Monitoring Alert States
        Schema::create('monitoring_alert_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('alert_type', 50)->index();
            $table->string('resource_identity', 100)->default('global'); // e.g. '/', '/var', 'cpu', 'nginx'
            $table->string('state', 30)->default('ok')->index(); // ok, warning, critical, recovered, suppressed
            $table->string('severity', 30)->default('info');
            $table->string('current_value', 100)->nullable();
            $table->string('threshold_value', 100)->nullable();
            $table->string('fingerprint', 64)->unique(); // sha256(server_id + alert_type + resource_identity)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('suppressed_until')->nullable();
            $table->string('suppression_reason')->nullable();
            $table->timestamp('notification_dispatched_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'state'], 'mon_alert_states_srv_state_idx');
            $table->index(['server_id', 'alert_type', 'resource_identity'], 'mon_alert_states_lookup_idx');
        });

        // 3. Server Metric Aggregates (Hourly & Daily Rollups)
        Schema::create('server_metric_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->timestamp('period_start')->index();
            $table->string('interval', 10)->index(); // '1h', '1d'
            $table->float('cpu_avg')->default(0);
            $table->float('cpu_min')->default(0);
            $table->float('cpu_max')->default(0);
            $table->float('ram_avg')->default(0);
            $table->float('ram_max')->default(0);
            $table->float('disk_avg')->default(0);
            $table->float('disk_max')->default(0);
            $table->float('load_1m_avg')->default(0);
            $table->unsignedBigInteger('network_rx_total')->default(0);
            $table->unsignedBigInteger('network_tx_total')->default(0);
            $table->unsignedInteger('sample_count')->default(0);
            $table->timestamps();

            $table->unique(['server_id', 'period_start', 'interval'], 'server_metric_agg_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_metric_aggregates');
        Schema::dropIfExists('monitoring_alert_states');
        Schema::dropIfExists('monitoring_alert_rules');
    }
};
