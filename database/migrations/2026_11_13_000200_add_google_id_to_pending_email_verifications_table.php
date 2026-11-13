<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_email_verifications', function (Blueprint $table): void {
            $table->string('google_id')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('pending_email_verifications', function (Blueprint $table): void {
            $table->dropColumn('google_id');
        });
    }
};
