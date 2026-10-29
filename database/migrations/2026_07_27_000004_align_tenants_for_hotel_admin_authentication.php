<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('tenant_name')->nullable()->change();
            $table->string('owner_email')->nullable()->change();
            $table->enum('status', ['active', 'suspended', 'pending'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('tenant_name')->nullable(false)->change();
            $table->string('owner_email')->nullable(false)->change();
            $table->enum('status', ['active', 'suspended', 'trial'])->default('active')->change();
        });
    }
};
