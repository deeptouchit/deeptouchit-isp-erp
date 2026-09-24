<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('custom_disk_space')->nullable()->after('php_version')->comment('Custom disk limit in MB');
            $table->unsignedBigInteger('custom_inodes')->nullable()->after('custom_disk_space')->comment('Custom inode limit');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['custom_disk_space', 'custom_inodes']);
        });
    }
};
