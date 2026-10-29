<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('demo_requests', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('business_type');
            }
            if (! Schema::hasColumn('demo_requests', 'city')) {
                $table->string('city', 100)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('demo_requests', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('demo_requests', 'contact_name')) {
                $table->dropColumn('contact_name');
            }
        });
    }
};
