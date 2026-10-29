<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'billing_cycle',
        'price',
        'currency_code',
        'trial_days',
        'max_rooms',
        'max_users',
        'max_staff',
        'max_reservations',
        'has_rooms',
        'has_restaurant_pos',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'trial_days' => 'integer',
            'max_rooms' => 'integer',
            'max_users' => 'integer',
            'max_staff' => 'integer',
            'max_reservations' => 'integer',
            'has_rooms' => 'boolean',
            'has_restaurant_pos' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
