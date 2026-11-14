<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 160);
            $table->string('asset_type', 40)->default('other');
            $table->string('owner_name', 120)->nullable();
            $table->decimal('quantity', 14, 3)->default(1);
            $table->string('unit', 24)->default('item');
            $table->decimal('purity', 6, 3)->nullable();
            $table->decimal('estimated_value', 16, 2)->nullable();
            $table->date('valuation_date')->nullable();
            $table->date('acquired_at')->nullable();
            $table->string('status', 20)->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'status', 'asset_type']);
        });

        Schema::create('asset_valuations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('estimated_value', 16, 2);
            $table->date('valuation_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['asset_id', 'valuation_date']);
        });

        Schema::create('asset_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('need_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['asset_id', 'need_id']);
            $table->index(['need_id']);
        });

        Schema::create('workspace_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 24);
            $table->string('subject_type', 64);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('summary', 220);
            $table->timestamp('occurred_at');
            $table->index(['workspace_id', 'occurred_at']);
        });

        Schema::create('workspace_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->text('context')->nullable();
            $table->json('options')->nullable();
            $table->string('status', 20)->default('open');
            $table->text('outcome')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'status', 'updated_at']);
        });

        Schema::create('home_priority_snoozes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('priority_key', 50);
            $table->date('snoozed_until');
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id', 'priority_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_decisions');
        Schema::dropIfExists('workspace_activities');
        Schema::dropIfExists('home_priority_snoozes');
        Schema::dropIfExists('asset_allocations');
        Schema::dropIfExists('asset_valuations');
        Schema::dropIfExists('assets');
    }
};
