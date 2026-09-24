<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            
            $table->decimal('cpu_usage', 5, 2)->default(0)->comment('% CPU usage');
            
            $table->unsignedBigInteger('memory_total')->default(0)->comment('MB');
            $table->unsignedBigInteger('memory_used')->default(0)->comment('MB');
            $table->unsignedBigInteger('memory_available')->default(0)->comment('MB');
            
            $table->unsignedBigInteger('disk_total')->default(0)->comment('GB');
            $table->unsignedBigInteger('disk_used')->default(0)->comment('GB');
            $table->decimal('disk_usage', 5, 2)->default(0)->comment('% Disk usage');
            
            $table->decimal('load_1m', 6, 2)->default(0);
            $table->decimal('load_5m', 6, 2)->default(0);
            $table->decimal('load_15m', 6, 2)->default(0);
            
            $table->unsignedBigInteger('network_rx')->default(0)->comment('Bytes/s Rx');
            $table->unsignedBigInteger('network_tx')->default(0)->comment('Bytes/s Tx');
            
            $table->unsignedBigInteger('disk_read')->default(0)->comment('Bytes/s Read');
            $table->unsignedBigInteger('disk_write')->default(0)->comment('Bytes/s Write');
            
            $table->unsignedInteger('process_count')->default(0);
            $table->unsignedInteger('open_file_descriptors')->default(0);
            $table->unsignedInteger('active_tcp_connections')->default(0);
            
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            
            // Query optimization index for time-series retrieval
            $table->index(['server_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
