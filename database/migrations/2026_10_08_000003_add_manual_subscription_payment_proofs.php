<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_payments', function (Blueprint $table): void {
            $table->foreignId('requested_plan_id')->nullable()->after('subscription_id')->constrained('plans')->nullOnDelete();
            $table->string('proof_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('saas_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('requested_plan_id');
            $table->dropColumn('proof_path');
        });
    }
};
