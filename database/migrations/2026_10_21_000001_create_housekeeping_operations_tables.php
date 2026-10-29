<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            if (! Schema::hasColumn('rooms', 'priority_flag')) {
                $table->boolean('priority_flag')->default(false)->after('housekeeping_status');
            }
            if (! Schema::hasColumn('rooms', 'last_cleaned_at')) {
                $table->timestamp('last_cleaned_at')->nullable()->after('priority_flag');
            }
        });

        Schema::create('housekeeping_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->string('service_type', 30);          // checkout_clean, stayover, turndown, deep_clean
            $table->string('status', 20)->default('open'); // open, in_progress, done, inspected, rejected
            $table->boolean('priority')->default(false);
            $table->text('inspection_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_hk_tasks_tenant_status');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
        });

        Schema::create('maintenance_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('reported_by_id')->nullable();
            $table->string('category', 30);   // plumbing, electrical, hvac, furniture, tv_wifi, lock
            $table->string('severity', 20);   // low, moderate, urgent
            $table->text('description')->nullable();
            $table->string('status', 20)->default('open'); // open, resolved
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_maint_tenant_status');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
        });

        Schema::create('lost_and_found_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('found_by_id')->nullable();
            $table->unsignedBigInteger('guest_id')->nullable();
            $table->string('item_description', 255);
            $table->string('category', 30);   // electronics, clothing, jewelry, documents, other
            $table->string('location_found', 150)->nullable();
            $table->timestamp('found_at')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status', 20)->default('unclaimed'); // unclaimed, claimed
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_lost_tenant_status');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('amenity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('logged_by_id')->nullable();
            $table->string('item_name', 120);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->boolean('charged_to_folio')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'room_id'], 'idx_amenity_tenant_room');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_logs');
        Schema::dropIfExists('lost_and_found_items');
        Schema::dropIfExists('maintenance_tickets');
        Schema::dropIfExists('housekeeping_tasks');
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropColumn(['priority_flag', 'last_cleaned_at']);
        });
    }
};
