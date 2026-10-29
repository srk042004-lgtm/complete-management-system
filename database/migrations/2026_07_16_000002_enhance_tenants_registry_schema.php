<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'hotel_name')) {
                $table->string('hotel_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('tenants', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('hotel_name');
            }

            if (! Schema::hasColumn('tenants', 'email')) {
                $table->string('email')->nullable()->after('owner_name');
            }

            if (! Schema::hasColumn('tenants', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (! Schema::hasColumn('tenants', 'country')) {
                $table->string('country')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('tenants', 'status')) {
                $table->enum('status', ['pending', 'active', 'suspended'])->default('pending')->after('country');
            }
        });

        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasIndex('tenants', ['country'])) {
                $table->index('country');
            }

            if (! Schema::hasIndex('tenants', ['status'])) {
                $table->index('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex(['country']);
            $table->dropIndex(['status']);
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['hotel_name', 'owner_name', 'email', 'phone', 'country']);
        });
    }
};
