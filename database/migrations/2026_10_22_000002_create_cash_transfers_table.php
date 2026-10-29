<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_shifts', function (Blueprint $table): void {
            $table->decimal('refunds_paid', 12, 2)->default(0)->after('cash_collected');
        });

        Schema::create('cash_transfers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cash_shift_id')->nullable();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('retained_float', 12, 2)->default(0);
            $table->string('transfer_type', 20)->default('vault_drop');
            $table->string('status', 20)->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_cash_transfers_tenant_status');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cash_shift_id')->references('id')->on('cash_shifts')->nullOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('receiver_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transfers');
        Schema::table('cash_shifts', function (Blueprint $table): void {
            $table->dropColumn('refunds_paid');
        });
    }
};
