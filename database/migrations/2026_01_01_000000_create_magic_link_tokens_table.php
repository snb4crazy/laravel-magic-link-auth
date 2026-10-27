<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('magic-link-auth.table', 'magic_link_tokens'), function (Blueprint $table): void {
            $table->id();
            $table->string('user_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('token_hash');
            $table->string('requested_ip', 45)->nullable();
            $table->text('requested_user_agent')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->string('consumed_ip', 45)->nullable();
            $table->text('consumed_user_agent')->nullable();
            $table->timestamps();
            $table->index(['email', 'consumed_at', 'expires_at'], 'magic_link_email_consumed_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('magic-link-auth.table', 'magic_link_tokens'));
    }
};
