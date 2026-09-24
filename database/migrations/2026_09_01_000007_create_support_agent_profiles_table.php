<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_agent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('job_title', 100)->default('Support Engineer');
            $table->json('department_slugs')->nullable();
            $table->text('signature')->nullable();
            $table->integer('max_active_tickets')->default(20);
            $table->boolean('is_auto_assignable')->default(true);
            $table->boolean('is_online')->default(true);
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->timestamps();
        });

        // Seed default profile for user ID 1 (Root Admin)
        $adminUser = DB::table('users')->where('role', 'admin')->first();
        if ($adminUser) {
            DB::table('support_agent_profiles')->insert([
                'user_id' => $adminUser->id,
                'job_title' => 'Chief Systems Architect & Lead Support',
                'department_slugs' => json_encode(['technical', 'billing', 'sales', 'abuse']),
                'signature' => "--\nDeepTouch Host Cloud Operations Team\n24/7 Enterprise Tier-3 Support",
                'max_active_tickets' => 25,
                'is_auto_assignable' => true,
                'is_online' => true,
                'rating' => 5.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_agent_profiles');
    }
};
