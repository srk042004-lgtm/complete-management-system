<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_shifts', function (Blueprint $table): void {
            $table->unsignedBigInteger('handover_to_id')->nullable()->after('closed_by');
            $table->string('opening_source', 20)->nullable()->after('opening_float');
            $table->unsignedBigInteger('retained_from_transfer_id')->nullable()->after('opening_source');
            $table->foreign('handover_to_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('cash_transfers', function (Blueprint $table): void {
            $table->unsignedBigInteger('consumed_by_shift_id')->nullable()->after('status');
            $table->foreign('consumed_by_shift_id')->references('id')->on('cash_shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_transfers', function (Blueprint $table): void {
            $table->dropForeign(['consumed_by_shift_id']);
            $table->dropColumn('consumed_by_shift_id');
        });
        Schema::table('cash_shifts', function (Blueprint $table): void {
            $table->dropForeign(['handover_to_id']);
            $table->dropColumn(['handover_to_id', 'opening_source', 'retained_from_transfer_id']);
        });
    }
};
