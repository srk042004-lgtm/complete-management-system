<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        if (! Schema::hasColumn('tenants', 'country')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('country')->default('Pakistan')->after('owner_email');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        if (Schema::hasColumn('tenants', 'country')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->dropColumn('country');
            });
        }
    }
};
