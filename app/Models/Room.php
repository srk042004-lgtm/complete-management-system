<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'tenant_id',
        'room_number',
        'room_type',
        'room_type_id',
        'floor',
        'occupancy_status',
        'housekeeping_status',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'room_number', 'room_number');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_type_id');
    }

    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'room_id');
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_rooms')->withPivot('price_per_night');
    }
}
