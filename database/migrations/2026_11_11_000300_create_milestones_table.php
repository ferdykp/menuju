<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('category', 80)->default('Umum');
            $table->date('target_date')->nullable();
            $table->string('status', 20)->default('upcoming');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'target_date', 'sort_order']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('milestone_id')->nullable()->after('workspace_id')->constrained()->nullOnDelete();
            $table->index(['workspace_id', 'milestone_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['workspace_id', 'milestone_id', 'status']);
            $table->dropConstrainedForeignId('milestone_id');
        });
        Schema::dropIfExists('milestones');
    }
};
