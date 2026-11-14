<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('needs', function (Blueprint $table): void {
            $table->foreignId('assignee_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->index(['workspace_id', 'assignee_id']);
        });
    }

    public function down(): void
    {
        Schema::table('needs', function (Blueprint $table): void {
            $table->dropIndex(['workspace_id', 'assignee_id']);
            $table->dropConstrainedForeignId('assignee_id');
        });
    }
};
