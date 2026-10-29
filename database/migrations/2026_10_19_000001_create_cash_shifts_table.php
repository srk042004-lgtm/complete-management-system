<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_shifts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('opened_by');
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->decimal('cash_collected', 12, 2)->default(0);
            $table->decimal('expected_cash', 12, 2)->default(0);
            $table->decimal('counted_cash', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_cash_shifts_tenant_status');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('opened_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('hotel_payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('cash_shift_id')->nullable()->after('payment_method');
            $table->index('cash_shift_id', 'idx_hotel_payments_cash_shift');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_payments', function (Blueprint $table): void {
            $table->dropIndex('idx_hotel_payments_cash_shift');
            $table->dropColumn('cash_shift_id');
        });
        Schema::dropIfExists('cash_shifts');
    }
};
