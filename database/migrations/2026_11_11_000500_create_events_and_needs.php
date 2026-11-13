<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->string('event_type', 60)->default('other');
            $table->date('event_date')->nullable();
            $table->string('start_time', 5)->nullable();
            $table->string('end_time', 5)->nullable();
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('venue_name', 160)->nullable();
            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'event_date']);
        });

        Schema::create('event_agenda_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('start_time', 5)->nullable();
            $table->string('end_time', 5)->nullable();
            $table->string('location', 160)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('needs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('category', 80)->default('Lainnya');
            $table->string('vendor_name', 160)->nullable();
            $table->string('budget_category', 80)->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('estimated_amount', 14, 2)->nullable();
            $table->decimal('purchased_amount', 14, 2)->nullable();
            $table->date('needed_by')->nullable();
            $table->date('purchased_at')->nullable();
            $table->string('status', 20)->default('planned');
            $table->string('priority', 20)->default('normal');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'status', 'needed_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs');
        Schema::dropIfExists('event_agenda_items');
        Schema::dropIfExists('events');
    }
};
