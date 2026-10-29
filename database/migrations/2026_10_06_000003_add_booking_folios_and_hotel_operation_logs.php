<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folios', function (Blueprint $table): void {
            if (Schema::hasColumn('folios', 'reservation_id')) {
                $table->foreignId('reservation_id')->nullable()->change();
            }
        });

        Schema::table('folios', function (Blueprint $table): void {
            if (! Schema::hasColumn('folios', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->after('reservation_id')
                    ->constrained('bookings')->cascadeOnDelete();
                $table->index(['booking_id', 'is_reversed']);
            }
        });

        Schema::create('hotel_operation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_operation_logs');

        if (Schema::hasColumn('folios', 'booking_id')) {
            Schema::table('folios', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('booking_id');
            });
        }
    }
};
