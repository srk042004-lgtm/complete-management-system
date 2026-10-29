<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saas_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->enum('transaction_type', [
                'subscription_base',
                'addon_restaurant',
                'manual_adjustment',
            ]);
            $table->string('payment_method');
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('currency_code', 3);
            $table->enum('payment_status', [
                'successful',
                'refunded',
                'pending_verification',
            ]);
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['currency_code', 'payment_status']);
            $table->index(['transaction_type', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_ledger');
    }
};
