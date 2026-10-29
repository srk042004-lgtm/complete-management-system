<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('tenants', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
        });

        Schema::table('plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('plans', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (! Schema::hasColumn('plans', 'trial_days')) {
                $table->unsignedInteger('trial_days')->default(0)->after('price');
            }

            if (! Schema::hasColumn('plans', 'max_rooms')) {
                $table->unsignedInteger('max_rooms')->nullable()->after('trial_days');
            }

            if (! Schema::hasColumn('plans', 'max_users')) {
                $table->unsignedInteger('max_users')->nullable()->after('max_rooms');
            }

            if (! Schema::hasColumn('plans', 'max_staff')) {
                $table->unsignedInteger('max_staff')->nullable()->after('max_users');
            }

            if (! Schema::hasColumn('plans', 'max_reservations')) {
                $table->unsignedInteger('max_reservations')->nullable()->after('max_staff');
            }

            if (! Schema::hasColumn('plans', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('has_restaurant_pos');
            }
        });

        Schema::table('saas_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('saas_payments', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('gateway');
            }

            if (! Schema::hasColumn('saas_payments', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('saas_payments', 'notes')) {
                $table->text('notes')->nullable()->after('transaction_id');
            }

            if (! Schema::hasColumn('saas_payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('paid_at');
            }
        });

        if (! Schema::hasTable('super_admin_settings')) {
            Schema::create('super_admin_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('super_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->string('action');
                $table->string('target_type')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->text('description')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->timestamps();

                $table->index(['target_type', 'target_id']);
                $table->index('action');
            });
        }

        if (! Schema::hasTable('super_admin_notifications')) {
            Schema::create('super_admin_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('super_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
                $table->string('type');
                $table->string('title');
                $table->text('message');
                $table->string('target_type')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['super_admin_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_notifications');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('super_admin_settings');
    }
};