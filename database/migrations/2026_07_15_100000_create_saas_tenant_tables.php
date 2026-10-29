<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_name');
            $table->string('owner_email')->unique();
            $table->enum('status', ['active', 'suspended', 'trial'])->default('active');
            $table->timestamps();

            $table->index('status');
        });

        }

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('plan_type', ['monthly', 'yearly', 'lifetime']);
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('ends_at');
            $table->index('plan_type');
        });

        }

        if (! Schema::hasTable('saas_payments')) {
            Schema::create('saas_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('gateway')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'payment_status']);
            $table->index('paid_at');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('tenants');
    }
};
