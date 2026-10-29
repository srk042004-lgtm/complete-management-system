<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folios', function (Blueprint $table): void {
            $table->foreignId('reservation_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Booking folio entries can have no legacy reservation_id, so the column must remain nullable.
    }
};
