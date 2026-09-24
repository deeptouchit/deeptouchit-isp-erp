<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('hostname', 255);
            $table->string('ip_address', 45);
            $table->string('os', 50)->default('Ubuntu 24.04');
            $table->integer('cpu_cores')->default(0);
            $table->integer('total_ram')->default(0)->comment('MB');
            $table->integer('total_disk')->default(0)->comment('GB');
            $table->integer('used_ram')->default(0)->comment('MB');
            $table->integer('used_disk')->default(0)->comment('GB');
            $table->float('load_avg_1min')->default(0);
            $table->float('load_avg_5min')->default(0);
            $table->float('load_avg_15min')->default(0);
            $table->enum('status', ['online', 'offline', 'maintenance'])->default('online');
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
