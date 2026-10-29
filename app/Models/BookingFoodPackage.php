<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingFoodPackage extends Model
{
    protected $fillable = [
        'tenant_id',
        'booking_id',
        'menu_item_id',
        'food_category_id',
        'package_name',
        'category_name',
        'billing_type',
        'guest_count',
        'quantity',
        'price_per_person_per_night',
        'nights',
        'total_amount',
        'kitchen_notes',
    ];

    protected function casts(): array
    {
        return [
            'price_per_person_per_night' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FoodCategory::class, 'food_category_id');
    }
}
