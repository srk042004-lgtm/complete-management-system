<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'tenant_id',
        'guest_id',
        'booking_code',
        'check_in_date',
        'check_out_date',
        'actual_check_in',
        'actual_check_out',
        'total_guests',
        'adults',
        'children',
        'booking_status',
        'subtotal_amount',
        'tax_amount',
        'grand_total',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'actual_check_in' => 'datetime',
            'actual_check_out' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'booking_rooms')->withPivot('price_per_night');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HotelPayment::class);
    }

    public function folioEntries(): HasMany
    {
        return $this->hasMany(Folio::class, 'booking_id');
    }

    public function foodPackages(): HasMany
    {
        return $this->hasMany(BookingFoodPackage::class, 'booking_id');
    }
}
