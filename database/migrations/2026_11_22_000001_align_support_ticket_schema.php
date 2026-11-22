<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('support_tickets', 'ticket_no') && !Schema::hasColumn('support_tickets', 'ticket_number')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->string('ticket_no')->nullable()->change();
                $table->string('ticket_number')->nullable()->unique();
                $table->string('department')->default('general');
                $table->string('priority')->default('medium')->change();
                $table->string('status')->default('open')->change();
                $table->string('last_reply_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
            });
            DB::table('support_tickets')->whereNull('ticket_number')->update(['ticket_number' => DB::raw('ticket_no')]);
            DB::table('support_tickets')->where('status', 'pending')->update(['status' => 'in_progress']);
            DB::table('support_tickets')->where('priority', 'critical')->update(['priority' => 'urgent']);
        }

        if (Schema::hasColumn('ticket_messages', 'ticket_id') && !Schema::hasColumn('ticket_messages', 'support_ticket_id')) {
            Schema::table('ticket_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('ticket_id')->nullable()->change();
                $table->foreignId('support_ticket_id')->nullable()->constrained('support_tickets')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_internal_note')->default(false);
            });
            DB::table('ticket_messages')->whereNull('support_ticket_id')->update(['support_ticket_id' => DB::raw('ticket_id')]);
        }

        if (Schema::hasColumn('ticket_attachments', 'ticket_id') && !Schema::hasColumn('ticket_attachments', 'ticket_message_id')) {
            Schema::table('ticket_attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('ticket_id')->nullable()->change();
                $table->string('mime_type', 100)->nullable()->change();
                $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->cascadeOnDelete();
                $table->string('file_type')->nullable();
            });
            DB::table('ticket_attachments')->whereNull('file_type')->update(['file_type' => DB::raw('mime_type')]);
        }
    }

    public function down(): void
    {
        // Keep ticket data written by the current application schema.
    }
};
