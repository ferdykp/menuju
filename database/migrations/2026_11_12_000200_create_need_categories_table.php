<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('need_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->timestamps();
            $table->unique(['workspace_id', 'name']);
        });

        Schema::table('needs', function (Blueprint $table): void {
            $table->foreignId('need_category_id')->nullable()->after('category')
                ->constrained('need_categories')->nullOnDelete();
        });

        $existing = DB::table('needs')->select('workspace_id', 'category')->distinct()->get();
        foreach ($existing->groupBy('workspace_id') as $workspaceId => $rows) {
            $names = [];
            foreach ($rows as $row) {
                $name = trim((string) $row->category);
                $name = $name !== '' ? $name : 'Lainnya';
                $names[mb_strtolower($name)] ??= $name;
            }

            foreach ($names as $normalized => $name) {
                $now = now();
                $categoryId = DB::table('need_categories')->insertGetId([
                    'workspace_id' => $workspaceId,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('needs')->where('workspace_id', $workspaceId)
                    ->whereRaw("LOWER(TRIM(COALESCE(category, ''))) = ?", [$normalized])
                    ->update(['need_category_id' => $categoryId, 'category' => $name]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('needs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('need_category_id');
        });

        Schema::dropIfExists('need_categories');
    }
};
