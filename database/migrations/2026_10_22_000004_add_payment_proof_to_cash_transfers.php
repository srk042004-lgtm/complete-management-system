<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transfers', function (Blueprint $table): void {
            // Stored as a short string and validated in the model, so new channels need no schema change.
            $table->string('payment_method', 20)->default('physical_cash')->after('transfer_type');
            $table->string('transaction_reference', 100)->nullable()->after('payment_method');
            $table->string('proof_image_path')->nullable()->after('transaction_reference');
            $table->index(['tenant_id', 'transaction_reference'], 'idx_cash_transfers_reference');
        });
    }

    public function down(): void
    {
        Schema::table('cash_transfers', function (Blueprint $table): void {
            $table->dropIndex('idx_cash_transfers_reference');
            $table->dropColumn(['payment_method', 'transaction_reference', 'proof_image_path']);
        });
    }
};
