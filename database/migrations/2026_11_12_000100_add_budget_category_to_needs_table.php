<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('needs', function (Blueprint $table): void {
            $table->foreignId('budget_category_id')
                ->nullable()
                ->after('budget_category')
                ->constrained('budget_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('needs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('budget_category_id');
        });
    }
};
