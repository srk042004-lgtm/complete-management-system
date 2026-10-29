<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'name')) {
                $table->string('name')->after('id');
            }

            if (! Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }

            if (! Schema::hasColumn('tenants', 'currency_code')) {
                $table->string('currency_code', 3)->default('PKR')->after('country');
            }
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('billing_cycle');
            $table->decimal('price', 12, 2);
            $table->string('currency_code', 3)->default('PKR');
            $table->boolean('has_rooms')->default(true);
            $table->boolean('has_restaurant_pos')->default(false);
            $table->timestamps();
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscriptions', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('tenant_id')->constrained('plans')->nullOnDelete();
            }

            if (! Schema::hasColumn('subscriptions', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('starts_at');
            }
        });

        Schema::table('saas_ledger', function (Blueprint $table): void {
            $table->string('currency_code', 3)->default('PKR')->change();
            $table->enum('payment_status', [
                'successful',
                'pending_verification',
                'refunded',
            ])->default('successful')->change();
        });
    }

    public function down(): void
    {
        Schema::table('saas_ledger', function (Blueprint $table): void {
            $table->string('currency_code', 3)->change();
            $table->enum('payment_status', [
                'successful',
                'pending_verification',
                'refunded',
            ])->change();
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('subscriptions', 'plan_id')) {
                $table->dropConstrainedForeignId('plan_id');
            }

            if (Schema::hasColumn('subscriptions', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });

        Schema::dropIfExists('plans');

        Schema::table('tenants', function (Blueprint $table): void {
            if (Schema::hasColumn('tenants', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }

            if (Schema::hasColumn('tenants', 'name')) {
                $table->dropColumn('name');
            }

            if (Schema::hasColumn('tenants', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
