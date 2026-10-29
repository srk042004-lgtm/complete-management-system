<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscriptions', 'has_rooms')) {
                $table->boolean('has_rooms')->default(true)->after('auto_renew');
            }

            if (! Schema::hasColumn('subscriptions', 'has_restaurant_pos')) {
                $table->boolean('has_restaurant_pos')->default(false)->after('has_rooms');
            }

            if (! Schema::hasColumn('subscriptions', 'restaurant_addon_price')) {
                $table->decimal('restaurant_addon_price', 10, 2)->default(0)->after('has_restaurant_pos');
            }

            if (! Schema::hasColumn('subscriptions', 'currency_code')) {
                $table->string('currency_code', 3)->default('PKR')->after('price');
            }
        });

        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'enabled_modules')) {
                $table->json('enabled_modules')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('subscriptions', 'currency_code')) {
                $table->dropColumn('currency_code');
            }

            if (Schema::hasColumn('subscriptions', 'restaurant_addon_price')) {
                $table->dropColumn('restaurant_addon_price');
            }

            if (Schema::hasColumn('subscriptions', 'has_restaurant_pos')) {
                $table->dropColumn('has_restaurant_pos');
            }

            if (Schema::hasColumn('subscriptions', 'has_rooms')) {
                $table->dropColumn('has_rooms');
            }
        });

        Schema::table('tenants', function (Blueprint $table): void {
            if (Schema::hasColumn('tenants', 'enabled_modules')) {
                $table->dropColumn('enabled_modules');
            }
        });
    }
};
