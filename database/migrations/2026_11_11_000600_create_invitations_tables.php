<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->string('slug', 32)->unique();
            $table->string('theme_key', 40)->default('botanical');
            $table->string('cover_title', 160)->nullable();
            $table->string('greeting', 240)->nullable();
            $table->text('story')->nullable();
            $table->string('quote', 300)->nullable();
            $table->string('closing', 240)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guest_invitation_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['invitation_id', 'guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_invitation_tokens');
        Schema::dropIfExists('invitations');
    }
};
