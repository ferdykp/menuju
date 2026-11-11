<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 160);
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('group_name', 100)->nullable();
            $table->string('side', 20)->default('shared');
            $table->string('invitation_status', 20)->default('not_sent');
            $table->string('rsvp_status', 20)->default('pending');
            $table->unsignedTinyInteger('plus_ones')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'rsvp_status', 'side']);
            $table->index(['workspace_id', 'group_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
