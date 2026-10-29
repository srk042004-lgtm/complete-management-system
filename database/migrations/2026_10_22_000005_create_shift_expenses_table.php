<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_expenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cash_shift_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2);
            $table->string('category', 40);
            $table->string('description', 255);
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at'], 'idx_shift_expenses_tenant_date');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cash_shift_id')->references('id')->on('cash_shifts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('cash_shifts', function (Blueprint $table): void {
            $table->decimal('expenses_total', 12, 2)->default(0)->after('refunds_paid');
            $table->timestamp('shortage_acknowledged_at')->nullable()->after('variance');
        });
    }

    public function down(): void
    {
        Schema::table('cash_shifts', function (Blueprint $table): void {
            $table->dropColumn(['expenses_total', 'shortage_acknowledged_at']);
        });
        Schema::dropIfExists('shift_expenses');
    }
};
