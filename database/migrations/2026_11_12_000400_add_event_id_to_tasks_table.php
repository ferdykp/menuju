<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('event_id')->nullable()->after('milestone_id')->constrained()->nullOnDelete();
            $table->index(['workspace_id', 'event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['workspace_id', 'event_id', 'status']);
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
