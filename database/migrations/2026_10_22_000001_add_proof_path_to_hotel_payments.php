<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('hotel_payments', 'proof_path')) {
                $table->string('proof_path')->nullable()->after('transaction_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotel_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('hotel_payments', 'proof_path')) {
                $table->dropColumn('proof_path');
            }
        });
    }
};
