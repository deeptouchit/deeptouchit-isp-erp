<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_customers')
            && !Schema::hasColumn('tenant_customers', 'last_online_at')) {
            Schema::table('tenant_customers', function (Blueprint $table): void {
                $table->timestamp('last_online_at')->nullable()->after('online_status');
            });
        }
    }

    public function down(): void
    {
        // Telemetry history is intentionally preserved.
    }
};
