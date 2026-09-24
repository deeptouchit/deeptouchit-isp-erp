<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tenant_coverage_zones')) {
            Schema::create('tenant_coverage_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('code', 50)->index();
                $table->string('name', 150);
                $table->string('city_upazila', 150)->nullable();
                $table->string('in_charge_name', 150)->nullable();
                $table->string('in_charge_phone', 50)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
            });
        }

        if (Schema::hasTable('tenant_customers') && !Schema::hasColumn('tenant_customers', 'zone_id')) {
            Schema::table('tenant_customers', function (Blueprint $table) {
                $table->foreignId('zone_id')->nullable()->after('zone')->constrained('tenant_coverage_zones')->onDelete('set null');
            });
        }

        // Auto-seed and backfill existing zones from tenant_customers
        try {
            $existingZones = DB::table('tenant_customers')
                ->whereNotNull('zone')
                ->where('zone', '!=', '')
                ->select('tenant_id', 'zone')
                ->distinct()
                ->get();

            $zoneCounters = [];

            foreach ($existingZones as $row) {
                $tenantId = $row->tenant_id;
                $zoneName = trim($row->zone);
                if (empty($zoneName)) continue;

                if (!isset($zoneCounters[$tenantId])) {
                    $zoneCounters[$tenantId] = 1;
                }

                $code = 'ZN-' . str_pad($zoneCounters[$tenantId], 3, '0', STR_PAD_LEFT);
                $zoneCounters[$tenantId]++;

                // Check if already exists
                $zoneRecord = DB::table('tenant_coverage_zones')
                    ->where('tenant_id', $tenantId)
                    ->where('name', $zoneName)
                    ->first();

                if (!$zoneRecord) {
                    $zoneId = DB::table('tenant_coverage_zones')->insertGetId([
                        'tenant_id' => $tenantId,
                        'code' => $code,
                        'name' => $zoneName,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $zoneId = $zoneRecord->id;
                }

                // Link customers with this zone
                DB::table('tenant_customers')
                    ->where('tenant_id', $tenantId)
                    ->where('zone', $zoneName)
                    ->update(['zone_id' => $zoneId]);
            }
        } catch (\Throwable $e) {
            // Log or ignore during migration if data is empty
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenant_customers') && Schema::hasColumn('tenant_customers', 'zone_id')) {
            Schema::table('tenant_customers', function (Blueprint $table) {
                $table->dropForeign(['zone_id']);
                $table->dropColumn('zone_id');
            });
        }

        Schema::dropIfExists('tenant_coverage_zones');
    }
};
