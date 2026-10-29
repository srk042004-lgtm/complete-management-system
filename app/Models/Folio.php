<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Folio extends Model
{
    protected $table = 'folios';

    protected $fillable = [
        'tenant_id',
        'reservation_id',
        'booking_id',
        'room_id',
        'invoice_number',
        'type',
        'item_category',
        'amount',
        'tax',
        'total',
        'is_reversed',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
