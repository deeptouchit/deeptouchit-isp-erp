<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenant_customers', 'pon_port')) {
            Schema::table('tenant_customers', function (Blueprint $table) {
                $table->string('pon_port', 50)->nullable()->after('olt_id');
            });
        }

        // Migrate historical PON port & ONU ID data out of fiber_route_info to pon_port
        // where it matches standard PON patterns like 0/4, 0/4:47, EPON0/1, 0/1/2:35
        try {
            $records = DB::table('tenant_customers')
                ->whereNotNull('fiber_route_info')
                ->where('fiber_route_info', '!=', '')
                ->select('id', 'fiber_route_info', 'pon_port')
                ->get();

            foreach ($records as $r) {
                $val = trim($r->fiber_route_info);
                // Check if value is a PON port notation (e.g. 0/1, 0/4:47, 1/1:22, EPON0/1)
                if (preg_match('/^(?:(?:EPON|GPON|pon)?\d+\/\d+(?:\/\d+)?(?::\d+)?)$/i', $val)) {
                    DB::table('tenant_customers')
                        ->where('id', $r->id)
                        ->update([
                            'pon_port' => $val,
                            'fiber_route_info' => null, // Reset fiber_route_info so TJ Box is clean
                        ]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore if migration already ran
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenant_customers', 'pon_port')) {
            Schema::table('tenant_customers', function (Blueprint $table) {
                $table->dropColumn('pon_port');
            });
        }
    }
};
