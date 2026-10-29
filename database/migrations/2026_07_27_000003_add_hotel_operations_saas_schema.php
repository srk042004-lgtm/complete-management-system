<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'suspended'])->default('active')->after('role');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'hotel_admin', 'receptionist', 'housekeeper') NOT NULL DEFAULT 'receptionist'");
        }

        Schema::table('room_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('room_types', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('room_types', 'title')) {
                $table->string('title')->nullable()->after('tenant_id');
            }

            if (! Schema::hasColumn('room_types', 'base_capacity')) {
                $table->unsignedInteger('base_capacity')->default(2)->after('title');
            }

            if (! Schema::hasColumn('room_types', 'max_capacity')) {
                $table->unsignedInteger('max_capacity')->default(4)->after('base_capacity');
            }

            if (! Schema::hasColumn('room_types', 'base_price_per_night')) {
                $table->decimal('base_price_per_night', 10, 2)->default(0)->after('max_capacity');
            }

            if (! Schema::hasColumn('room_types', 'description')) {
                $table->text('description')->nullable()->after('base_price_per_night');
            }
        });

        Schema::table('rooms', function (Blueprint $table): void {
            if (! Schema::hasColumn('rooms', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('rooms', 'room_type_id')) {
                $table->foreignId('room_type_id')->nullable()->after('tenant_id')->constrained('room_types')->nullOnDelete();
            }

            if (! Schema::hasColumn('rooms', 'floor')) {
                $table->unsignedInteger('floor')->default(1)->after('room_type_id');
            }
        });

        Schema::create('guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->enum('id_type', ['passport', 'cnic_national_id', 'drivers_license'])->default('cnic_national_id');
            $table->string('id_number');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('guests')->restrictOnDelete();
            $table->string('booking_code')->unique();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->dateTime('actual_check_in')->nullable();
            $table->dateTime('actual_check_out')->nullable();
            $table->unsignedInteger('total_guests')->default(1);
            $table->enum('booking_status', ['confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'])->default('confirmed');
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'booking_status']);
            $table->index(['check_in_date', 'check_out_date']);
        });

        Schema::create('booking_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->decimal('price_per_night', 10, 2);
            $table->timestamps();

            $table->unique(['booking_id', 'room_id']);
        });

        Schema::create('hotel_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('transaction_reference')->nullable();
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer'])->default('cash');
            $table->decimal('amount', 12, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_payments');
        Schema::dropIfExists('booking_rooms');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('guests');

        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('room_type_id');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('floor');
        });

        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['title', 'base_capacity', 'max_capacity', 'base_price_per_night', 'description']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('status');
        });
    }
};
