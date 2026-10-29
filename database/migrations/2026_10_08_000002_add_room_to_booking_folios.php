<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folios', function (Blueprint $table): void {
            $table->unsignedBigInteger('room_id')->nullable()->after('booking_id');
            $table->index(['tenant_id', 'room_id'], 'idx_folios_tenant_room');
            $table->foreign(['tenant_id', 'room_id'], 'fk_folios_tenant_room')
                ->references(['tenant_id', 'id'])->on('rooms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('folios', function (Blueprint $table): void {
            $table->dropForeign('fk_folios_tenant_room');
            $table->dropIndex('idx_folios_tenant_room');
            $table->dropColumn('room_id');
        });
    }
};
