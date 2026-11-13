<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('account_type', 30)->default('cash');
            $table->string('institution', 120)->nullable();
            $table->decimal('opening_balance', 16, 2)->default(0);
            $table->char('currency', 3)->default('IDR');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->index(['workspace_id', 'is_archived']);
        });

        Schema::create('savings_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 140);
            $table->decimal('target_amount', 16, 2);
            $table->date('target_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->index(['workspace_id', 'is_archived', 'target_date']);
        });

        Schema::create('financial_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignId('savings_goal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('category', 100)->nullable();
            $table->string('description', 180);
            $table->decimal('amount', 16, 2);
            $table->date('occurred_at');
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'occurred_at', 'type']);
            $table->index(['account_id', 'voided_at']);
        });

        Schema::create('budget_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->decimal('planned_amount', 16, 2)->default(0);
            $table->string('color', 12)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'name']);
        });

        Schema::create('payment_obligations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->string('vendor_name', 160)->nullable();
            $table->decimal('total_amount', 16, 2);
            $table->date('due_at')->nullable();
            $table->string('status', 20)->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'status', 'due_at']);
        });

        Schema::create('payment_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_obligation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_transaction_id')->constrained('financial_transactions')->restrictOnDelete();
            $table->decimal('amount', 16, 2);
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('savings_deposits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('savings_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->decimal('amount', 16, 2);
            $table->date('deposited_at');
            $table->text('notes')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->index(['savings_goal_id', 'deposited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_deposits');
        Schema::dropIfExists('payment_records');
        Schema::dropIfExists('payment_obligations');
        Schema::dropIfExists('budget_categories');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('savings_goals');
        Schema::dropIfExists('financial_accounts');
    }
};
