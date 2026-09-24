<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tenant_reseller_bandwidth')) {
            // Using raw alter table to safely change column type across mysql/mariadb without requiring doctrine/dbal
            DB::statement("ALTER TABLE `tenant_reseller_bandwidth` MODIFY COLUMN `vlan_id` VARCHAR(150) NULL DEFAULT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenant_reseller_bandwidth')) {
            DB::statement("ALTER TABLE `tenant_reseller_bandwidth` MODIFY COLUMN `vlan_id` INT NULL DEFAULT NULL");
        }
    }
};
