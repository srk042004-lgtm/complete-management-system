<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
                $table->string('occupancy_status')->default('vacant');
                $table->string('housekeeping_status')->default('clean');
                $table->timestamps();
            });
        } else {
            if (Schema::hasColumn('rooms', 'number') && ! Schema::hasColumn('rooms', 'room_number')) {
                if (DB::getDriverName() === 'mysql') {
                    DB::statement('ALTER TABLE rooms CHANGE COLUMN `number` `room_number` VARCHAR(255) NOT NULL');
                } else {
                    DB::statement('ALTER TABLE rooms RENAME COLUMN number TO room_number');
                }
            }

            Schema::table('rooms', function (Blueprint $table): void {
                if (! Schema::hasColumn('rooms', 'room_number')) {
                    $table->string('room_number')->nullable();
                }
                if (! Schema::hasColumn('rooms', 'room_type')) {
                    $table->string('room_type')->nullable();
                }
                if (! Schema::hasColumn('rooms', 'occupancy_status')) {
                    $table->string('occupancy_status')->default('vacant');
                }
                if (! Schema::hasColumn('rooms', 'housekeeping_status')) {
                    $table->string('housekeeping_status')->default('clean');
                }
            });
        }

        if (! Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('guest_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('room_number');
                $table->string('status')->default('Tentative');
                $table->decimal('base_rate', 10, 2)->default(0);
                $table->timestamp('actual_checkin_at')->nullable();
                $table->timestamp('actual_checkout_at')->nullable();
                $table->timestamps();

                $table->foreign('room_number')->references('room_number')->on('rooms')->nullOnDelete();
                $table->index('actual_checkout_at');
                $table->index('status');
            });
        } else {
            Schema::table('reservations', function (Blueprint $table): void {
                if (! Schema::hasColumn('reservations', 'guest_id')) {
                    $table->foreignId('guest_id')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('reservations', 'room_number')) {
                    $table->string('room_number')->nullable();
                }
                if (! Schema::hasColumn('reservations', 'status')) {
                    $table->string('status')->default('Tentative');
                }
                if (! Schema::hasColumn('reservations', 'base_rate')) {
                    $table->decimal('base_rate', 10, 2)->default(0);
                }
                if (! Schema::hasColumn('reservations', 'actual_checkin_at')) {
                    $table->timestamp('actual_checkin_at')->nullable();
                }
                if (! Schema::hasColumn('reservations', 'actual_checkout_at')) {
                    $table->timestamp('actual_checkout_at')->nullable();
                }

                $table->index('actual_checkout_at');
                $table->index('status');

                if (Schema::hasColumn('reservations', 'room_number')) {
                    $table->foreign('room_number')->references('room_number')->on('rooms')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('folios')) {
            Schema::create('folios', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->string('invoice_number')->unique();
                $table->string('type');
                $table->string('item_category');
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->boolean('is_reversed')->default(false);
                $table->timestamp('created_at')->useCurrent();
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
