<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'property_type')) {
                $table->string('property_type')->nullable()->after('hotel_name');
            }

            if (! Schema::hasColumn('tenants', 'city')) {
                $table->string('city')->nullable()->after('country');
            }

            if (! Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->after('city');
            }

            if (! Schema::hasColumn('tenants', 'website')) {
                $table->string('website')->nullable()->after('address');
            }

            if (! Schema::hasColumn('tenants', 'notes')) {
                $table->text('notes')->nullable()->after('website');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['property_type', 'city', 'address', 'website', 'notes']);
        });
    }
};
