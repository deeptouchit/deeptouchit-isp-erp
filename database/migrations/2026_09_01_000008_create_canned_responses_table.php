<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->enum('category', ['technical', 'billing', 'sales', 'security', 'general'])->default('technical');
            $table->string('shortcut_code', 50)->nullable();
            $table->text('content');
            $table->integer('usage_count')->default(0);
            $table->boolean('is_shared')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_shared'], 'idx_canned_category_shared');
        });

        // Seed 8 Authoritative Canned Templates
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $defaults = [
            [
                'title' => 'DNS Propagation Notice',
                'category' => 'technical',
                'shortcut_code' => '!dns',
                'content' => "Hello,\n\nPlease allow 4 to 24 hours for worldwide DNS propagation. You can verify your live DNS records using our DNS management tool or an external DNS propagation checker.\n\nBest regards,\nDeepTouchHost Technical Support",
                'usage_count' => 42,
                'is_shared' => true,
                'created_by' => $adminId,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => "Let's Encrypt SSL Certificate Provisioned",
                'category' => 'technical',
                'shortcut_code' => '!ssl',
                'content' => "Hello,\n\nWe have re-issued and validated your Let's Encrypt SSL certificate. HTTPS is now enforced with full HTTP/2 & HTTP/3 acceleration.\n\nBest regards,\nDeepTouchHost Security Team",
                'usage_count' => 67,
                'is_shared' => true,
                'created_by' => $adminId,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Invoice & Payment Clearance Confirmed',
                'category' => 'billing',
                'shortcut_code' => '!pay',
                'content' => "Hello,\n\nYour recent payment clearance has been verified and settled. Your service subscription is fully active.\n\nThank you for choosing DeepTouch Host Cloud,\nDeepTouchHost Billing Support",
                'usage_count' => 85,
                'is_shared' => true,
                'created_by' => $adminId,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'cPanel & File Manager Migration Complete',
                'category' => 'technical',
                'shortcut_code' => '!mig',
                'content' => "Hello,\n\nYour website migration from your previous hosting provider has been completed successfully. Database connections, file permissions, and SSL certificates are active.\n\nBest regards,\nDeepTouchHost Migration Operations",
                'usage_count' => 31,
                'is_shared' => true,
                'created_by' => $adminId,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'PHP OPcache & Memory Limit Boosted',
                'category' => 'technical',
                'shortcut_code' => '!php',
                'content' => "Hello,\n\nWe have analyzed your container resource utilization and optimized PHP-FPM / OPcache memory limits. Your website is running normally with zero throttling.\n\nBest regards,\nDeepTouchHost Infrastructure Team",
                'usage_count' => 19,
                'is_shared' => true,
                'created_by' => $adminId,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Custom VPS & Cloud Server Quote',
                'category' => 'sales',
                'shortcut_code' => '!vps',
                'content' => "Hello,\n\nThank you for inquiring about our Dedicated NVMe VPS servers. We have customized a resource package with dedicated vCPUs, DDR5 ECC RAM, and BDIX 10Gbps connectivity.\n\nBest regards,\nDeepTouchHost Sales & Enterprise Solutions",
                'usage_count' => 24,
                'is_shared' => true,
                'created_by' => $adminId,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'IP Address Unblocked from Firewall',
                'category' => 'security',
                'shortcut_code' => '!unban',
                'content' => "Hello,\n\nYour public IP address has been unbanned from Fail2ban and added to the temporary allowlist. Please verify your cPanel/SSH login credentials to prevent future triggers.\n\nBest regards,\nDeepTouchHost Security Team",
                'usage_count' => 53,
                'is_shared' => true,
                'created_by' => $adminId,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Domain Whois & EPP Auth Code Info',
                'category' => 'general',
                'shortcut_code' => '!domain',
                'content' => "Hello,\n\nYour domain transfer lock has been disabled and the EPP Authorization Code has been dispatched to your registrant administrative email address.\n\nBest regards,\nDeepTouchHost Domain Registrar Services",
                'usage_count' => 14,
                'is_shared' => true,
                'created_by' => $adminId,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('canned_responses')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('canned_responses');
    }
};
