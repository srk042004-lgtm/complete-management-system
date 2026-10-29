<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomCategory extends Model
{
    protected $table = 'room_types';

    protected $fillable = [
        'property_id',
        'tenant_id',
        'type_name',
        'title',
        'code',
        'description',
        'base_capacity',
        'max_capacity',
        'base_occupancy',
        'adults',
        'children',
        'max_adults',
        'max_children',
        'max_total_occupancy',
        'base_price_per_night',
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
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'gallery_images' => 'array',
            'has_balcony' => 'boolean',
            'is_active' => 'boolean',
            'allow_extra_bed' => 'boolean',
            'base_price_per_night' => 'decimal:2',
            'extra_adult_rate' => 'decimal:2',
            'extra_child_rate' => 'decimal:2',
            'weekend_rate' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'size_sq_ft' => 'decimal:2',
            'extra_bed_fee' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_type_id');
    }
}
