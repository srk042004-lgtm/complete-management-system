<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('icon', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'sort_order']);
        });

        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('food_category_id')->constrained('food_categories')->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->enum('billing_type', ['per_guest_per_night', 'one_time_order'])->default('one_time_order');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['food_category_id', 'name']);
            $table->index(['food_category_id', 'is_available']);
        });

        Schema::table('booking_food_packages', function (Blueprint $table): void {
            $table->foreignId('menu_item_id')->nullable()->after('booking_id')->constrained('menu_items')->nullOnDelete();
            $table->foreignId('food_category_id')->nullable()->after('menu_item_id')->constrained('food_categories')->nullOnDelete();
            $table->string('category_name', 120)->nullable();
            $table->string('billing_type', 40)->default('per_guest_per_night');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('kitchen_notes', 1000)->nullable();
        });

        $defaults = [
            ['Food', 'utensils', 10],
            ['Milkshakes', 'milkshake', 20],
            ['Fast Food', 'burger', 30],
            ['Beverages', 'coffee', 40],
            ['Desserts', 'cake', 50],
        ];
        foreach (DB::table('tenants')->select('id')->get() as $tenant) {
            foreach ($defaults as [$name, $icon, $sortOrder]) {
                DB::table('food_categories')->insertOrIgnore([
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'icon' => $icon,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('booking_food_packages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('menu_item_id');
            $table->dropConstrainedForeignId('food_category_id');
            $table->dropColumn(['category_name', 'billing_type', 'quantity', 'kitchen_notes']);
        });

        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('food_categories');
    }
};
