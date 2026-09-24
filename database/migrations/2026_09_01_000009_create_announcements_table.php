<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->enum('type', ['maintenance', 'service_outage', 'promotional', 'security', 'general'])->default('general');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->enum('target_audience', ['all', 'clients_only', 'guests_only'])->default('all');
            $table->string('summary', 255)->nullable();
            $table->longText('content');
            $table->boolean('is_published')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('show_banner')->default(false);
            $table->integer('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_published', 'is_pinned'], 'idx_announcements_type_pub');
        });

        // Seed Authoritative Initial Announcements
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $defaults = [
            [
                'title' => 'Scheduled NVMe Storage & Kernel Maintenance on BD-Cluster-01',
                'slug' => 'scheduled-nvme-storage-kernel-maintenance-bd-cluster-01',
                'type' => 'maintenance',
                'severity' => 'warning',
                'target_audience' => 'all',
                'summary' => 'Routine kernel patch and NVMe pool optimization scheduled for Sunday at 02:00 AM UTC+6.',
                'content' => "Dear Customers,\n\nWe will be conducting routine infrastructure maintenance on our primary BD-Cluster-01 nodes to install the latest Linux security kernel and optimize ZFS NVMe storage pools.\n\n- **Window:** Sunday, 02:00 AM - 03:00 AM UTC+6 (BDT)\n- **Expected Impact:** Brief intermittent connectivity of 3-5 minutes during container reboot.\n- **Scope:** Shared Hosting, cPanel nodes, and managed database services.\n\nOur operations team will be actively monitoring all services throughout the maintenance window.\n\nBest regards,\nDeepTouchHost Operations Team",
                'is_published' => true,
                'is_pinned' => true,
                'show_banner' => true,
                'views_count' => 342,
                'published_at' => now(),
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'PHP 8.4 Runtime & JIT Compiler Now Available Across All Hosting Packages',
                'slug' => 'php-84-runtime-jit-compiler-now-available',
                'type' => 'promotional',
                'severity' => 'info',
                'target_audience' => 'all',
                'summary' => 'Switch your website to PHP 8.4 via cPanel / PHP Manager for up to 35% faster WordPress performance.',
                'content' => "Dear Customers,\n\nWe are excited to announce full availability of PHP 8.4 with OPcache JIT compiler and latest extensions across all shared hosting, reseller, and VPS plans.\n\nYou can instantly select PHP 8.4 per-domain from your cPanel MultiPHP Manager or through our host control panel.\n\nThank you for choosing DeepTouch Host Cloud,\nDeepTouchHost Technical Team",
                'is_published' => true,
                'is_pinned' => false,
                'show_banner' => false,
                'views_count' => 512,
                'published_at' => now(),
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Emergency Upstream BDIX Transit Provider Fiber Maintenance - Dhaka DC',
                'slug' => 'emergency-upstream-bdix-transit-provider-fiber-maintenance',
                'type' => 'service_outage',
                'severity' => 'critical',
                'target_audience' => 'all',
                'summary' => 'Upstream NTT / BDIX optical link maintenance. Redundant backup transit actively routing traffic.',
                'content' => "Dear Customers,\n\nOur upstream BDIX bandwidth consortium in Dhaka is undertaking emergency optical splicing. All Bangladesh local traffic is smoothly rerouted via our secondary 10Gbps NTT transit line.\n\nLatency may increase slightly by 4-8ms for some local ISPs until the primary line is restored.\n\nBest regards,\nDeepTouchHost Network Operations Center (NOC)",
                'is_published' => true,
                'is_pinned' => false,
                'show_banner' => true,
                'views_count' => 689,
                'published_at' => now(),
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Security Advisory: OpenSSL & Apache HTTP/2 Flow-Control Patch Applied',
                'slug' => 'security-advisory-openssl-apache-http2-flow-control-patch-applied',
                'type' => 'security',
                'severity' => 'info',
                'target_audience' => 'all',
                'summary' => 'All DeepTouchHost server clusters have been patched with zero downtime against CVE-2026 HTTP/2 vulnerabilities.',
                'content' => "Dear Customers,\n\nIn accordance with our zero-trust security policy, all server nodes and web servers (Apache & Nginx) have received the latest security hotfixes. No customer action is required.\n\nBest regards,\nDeepTouchHost Security Team",
                'is_published' => true,
                'is_pinned' => false,
                'show_banner' => false,
                'views_count' => 278,
                'published_at' => now(),
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'bKash & Nagad Direct Checkout PGW Integration Live',
                'slug' => 'bkash-nagad-direct-checkout-pgw-integration-live',
                'type' => 'general',
                'severity' => 'info',
                'target_audience' => 'all',
                'summary' => 'Instant automated billing clearance with zero transaction fee for all bKash and Nagad payments.',
                'content' => "Dear Customers,\n\nWe have deployed native bKash Merchant API & Nagad Direct Checkout PGW integration. All hosting renewals and new service activations are now verified and activated instantaneously in under 5 seconds.\n\nBest regards,\nDeepTouchHost Billing Support",
                'is_published' => true,
                'is_pinned' => false,
                'show_banner' => false,
                'views_count' => 415,
                'published_at' => now(),
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('announcements')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
