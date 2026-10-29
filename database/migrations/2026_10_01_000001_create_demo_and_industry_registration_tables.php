<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('business_name');
            $table->string('business_type');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('already_using')->nullable();
            $table->text('address');
            $table->string('status')->default('new');
            $table->timestamps();
        });

        foreach (['hotel', 'school', 'clinic', 'restaurant', 'retail'] as $industry) {
            Schema::create($industry . '_registrations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->string('business_name');
                $table->string('contact_name');
                $table->string('email');
                $table->string('phone');
                $table->string('address');
                $table->string('city');
                $table->string('country')->default('Pakistan');
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['hotel', 'school', 'clinic', 'restaurant', 'retail'] as $industry) {
            Schema::dropIfExists($industry . '_registrations');
        }

        Schema::dropIfExists('demo_requests');
    }
};
