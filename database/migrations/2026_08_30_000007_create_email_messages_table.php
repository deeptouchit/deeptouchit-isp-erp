<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained('email_accounts')->cascadeOnDelete();
            $table->enum('folder', ['inbox', 'sent', 'drafts', 'spam', 'trash', 'archive'])->default('inbox');
            $table->string('from_name', 191)->nullable();
            $table->string('from_email', 191);
            $table->string('to', 191);
            $table->string('cc', 191)->nullable();
            $table->string('bcc', 191)->nullable();
            $table->string('subject', 255);
            $table->text('snippet')->nullable();
            $table->longText('body');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->json('attachments')->nullable();
            $table->integer('size_kb')->default(1);
            $table->timestamps();

            $table->index(['email_account_id', 'folder']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
