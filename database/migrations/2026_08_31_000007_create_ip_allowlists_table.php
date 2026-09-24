<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_allowlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->boolean('is_subnet')->default(false);
            $table->string('label', 191);
            $table->string('scope', 50)->default('global'); // global, ssh_only, database, panel_admin
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ip_address', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_allowlists');
    }
};
