<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table): void {
                $table->id();
                $table->string('room_number')->unique();
                $table->string('room_type')->nullable();
                $table->enum('occupancy_status', ['vacant', 'occupied'])->default('vacant');
                $table->enum('housekeeping_status', ['clean', 'dirty', 'maintenance'])->default('clean');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table): void {
                $table->id();
                $table->string('room_number');
                $table->enum('status', ['Tentative', 'Confirmed', 'Inhouse', 'Checkedout'])->default('Tentative');
                $table->decimal('base_rate', 10, 2)->default(0);
                $table->timestamp('actual_checkin_at')->nullable();
                $table->timestamp('actual_checkout_at')->nullable();
                $table->timestamps();

                $table->foreign('room_number')->references('room_number')->on('rooms')->cascadeOnDelete();
                $table->index('actual_checkout_at');
            });
        }

        if (! Schema::hasTable('folios')) {
            Schema::create('folios', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->string('invoice_number')->index();
                $table->enum('type', ['charge', 'receipt']);
                $table->string('item_category');
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('folios');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('rooms');
    }
};
