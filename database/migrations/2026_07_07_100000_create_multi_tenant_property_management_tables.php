<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table): void {
                $table->id();
                $table->string('property_name');
                $table->string('legal_tax_number')->nullable();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('default_tax_rate', 5, 2)->default(0);
                $table->timestamps();

                $table->unique(['property_name']);
            });
        }

        if (! Schema::hasTable('room_types')) {
            Schema::create('room_types', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->string('type_name');
                $table->timestamps();

                $table->unique(['property_id', 'type_name']);
            });
        }

        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->string('room_number');
                $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
                $table->enum('occupancy_status', ['vacant', 'occupied'])->default('vacant');
                $table->enum('housekeeping_status', ['clean', 'dirty', 'maintenance'])->default('clean');
                $table->timestamps();

                $table->unique(['property_id', 'room_number']);
            });
        }

        if (! Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->string('guest_name');
                $table->enum('status', ['Inhouse', 'Checkedout'])->default('Inhouse');
                $table->timestamp('checkin_at')->nullable();
                $table->timestamp('checkout_at')->nullable();
                $table->timestamps();

                $table->index(['property_id', 'room_id']);
            });
        }

        if (! Schema::hasTable('folios')) {
            Schema::create('folios', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->string('item_description');
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->enum('type', ['charge', 'receipt']);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('folios');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('properties');
    }
};
