<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->string('email', 150)->nullable();
            $table->string('description', 255)->nullable();
            $table->json('assigned_staff_ids')->nullable();
            $table->boolean('is_client_selectable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sla_response_hours')->default(2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['slug', 'is_active'], 'idx_departments_slug_active');
        });

        // Seed 4 Default Authoritative Helpdesk Departments
        $defaults = [
            [
                'name' => 'Technical Support',
                'slug' => 'technical',
                'email' => 'support@deeptouchhost.local',
                'description' => 'Server management, DNS, PHP runtime, Apache/Nginx, and SSL certificate troubleshooting.',
                'assigned_staff_ids' => json_encode([1]),
                'is_client_selectable' => true,
                'is_active' => true,
                'sla_response_hours' => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Billing & Accounts',
                'slug' => 'billing',
                'email' => 'billing@deeptouchhost.local',
                'description' => 'Invoices, bKash / Nagad / Card payment clearances, account credits, and refunds.',
                'assigned_staff_ids' => json_encode([1]),
                'is_client_selectable' => true,
                'is_active' => true,
                'sla_response_hours' => 2,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sales & Pre-Sales',
                'slug' => 'sales',
                'email' => 'sales@deeptouchhost.local',
                'description' => 'Custom dedicated server quotes, account migrations, and hosting plan upgrades.',
                'assigned_staff_ids' => json_encode([1]),
                'is_client_selectable' => true,
                'is_active' => true,
                'sla_response_hours' => 4,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Abuse & Security',
                'slug' => 'abuse',
                'email' => 'abuse@deeptouchhost.local',
                'description' => 'DMCA copyright claims, phishing reports, malware quarantine, and IP block escalations.',
                'assigned_staff_ids' => json_encode([1]),
                'is_client_selectable' => true,
                'is_active' => true,
                'sla_response_hours' => 1,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('support_departments')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('support_departments');
    }
};
