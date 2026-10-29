<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->string('code', 30)->nullable();
            $table->unsignedInteger('base_occupancy')->default(2);
            $table->unsignedInteger('max_adults')->default(2);
            $table->unsignedInteger('max_children')->default(2);
            $table->unsignedInteger('max_total_occupancy')->default(4);
            $table->decimal('extra_adult_rate', 10, 2)->default(0);
            $table->decimal('extra_child_rate', 10, 2)->default(0);
            $table->decimal('weekend_rate', 10, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('size_sq_ft', 8, 2)->nullable();
            $table->string('bed_type', 30)->default('KING');
            $table->unsignedInteger('bed_count')->default(1);
            $table->boolean('has_balcony')->default(false);
            $table->string('view_type', 30)->default('CITY_VIEW');
            $table->json('amenities')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_extra_bed')->default(true);
            $table->decimal('extra_bed_fee', 10, 2)->default(0);
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });

        DB::table('room_types')->orderBy('id')->get()->each(function (object $category): void {
            $base = Str::upper(Str::slug((string) ($category->title ?: $category->type_name), '-'));
            $base = trim(substr($base ?: 'CATEGORY', 0, 20), '-');
            $code = $base;
            $suffix = 1;
            while (DB::table('room_types')
                ->where('tenant_id', $category->tenant_id)
                ->where('code', $code)
                ->where('id', '<>', $category->id)
                ->exists()) {
                $code = substr($base, 0, 16).'-'.$suffix++;
            }

            DB::table('room_types')->where('id', $category->id)->update([
                'code' => $code,
                'base_occupancy' => $category->base_capacity ?? 2,
                'max_adults' => $category->adults ?? 2,
                'max_children' => $category->children ?? 2,
                'max_total_occupancy' => $category->max_capacity ?? 4,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'code']);
            $table->dropIndex(['tenant_id', 'is_active']);
            $table->dropColumn([
                'code',
                'base_occupancy',
                'max_adults',
                'max_children',
                'max_total_occupancy',
                'extra_adult_rate',
                'extra_child_rate',
                'weekend_rate',
                'hourly_rate',
                'size_sq_ft',
                'bed_type',
                'bed_count',
                'has_balcony',
                'view_type',
                'amenities',
                'cover_image',
                'gallery_images',
                'is_active',
                'allow_extra_bed',
                'extra_bed_fee',
            ]);
        });
    }
};
