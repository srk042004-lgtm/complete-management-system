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
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('username');
            }
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $columns = [
                'hotel_code' => fn () => $table->string('hotel_code')->nullable()->unique()->after('id'),
                'description' => fn () => $table->text('description')->nullable(),
                'total_rooms' => fn () => $table->unsignedInteger('total_rooms')->nullable(),
                'state' => fn () => $table->string('state')->nullable(),
                'postal_code' => fn () => $table->string('postal_code')->nullable(),
                'hotel_type' => fn () => $table->string('hotel_type')->nullable(),
                'logo_path' => fn () => $table->string('logo_path')->nullable(),
                'whatsapp' => fn () => $table->string('whatsapp')->nullable(),
                'business_name' => fn () => $table->string('business_name')->nullable(),
                'registration_number' => fn () => $table->string('registration_number')->nullable(),
                'tax_number' => fn () => $table->string('tax_number')->nullable(),
                'timezone' => fn () => $table->string('timezone')->nullable(),
                'date_format' => fn () => $table->string('date_format')->nullable(),
                'check_in_time' => fn () => $table->time('check_in_time')->nullable(),
                'check_out_time' => fn () => $table->time('check_out_time')->nullable(),
                'reception_phone' => fn () => $table->string('reception_phone')->nullable(),
                'reservation_phone' => fn () => $table->string('reservation_phone')->nullable(),
                'contact_email' => fn () => $table->string('contact_email')->nullable(),
                'emergency_contact' => fn () => $table->string('emergency_contact')->nullable(),
                'contact_address' => fn () => $table->text('contact_address')->nullable(),
            ];

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('tenants', $column)) {
                    $definition();
                }
            }
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscriptions', 'trial_start')) {
                $table->timestamp('trial_start')->nullable()->after('starts_at');
            }

            if (! Schema::hasColumn('subscriptions', 'trial_end')) {
                $table->timestamp('trial_end')->nullable()->after('trial_start');
            }

            if (! Schema::hasColumn('subscriptions', 'trial_duration_days')) {
                $table->unsignedInteger('trial_duration_days')->nullable()->after('trial_end');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active', 'expired', 'cancelled', 'trial', 'suspended') NOT NULL DEFAULT 'active'");
        }

        if (! Schema::hasTable('tenant_settings')) {
            Schema::create('tenant_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('key');
                $table->json('value')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['trial_start', 'trial_end', 'trial_duration_days']);
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'hotel_code', 'description', 'total_rooms', 'state', 'postal_code', 'hotel_type',
                'logo_path', 'whatsapp', 'business_name', 'registration_number', 'tax_number',
                'timezone', 'date_format', 'check_in_time', 'check_out_time', 'reception_phone',
                'reservation_phone', 'contact_email', 'emergency_contact',
                'contact_address',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'phone']);
        });
    }
};
