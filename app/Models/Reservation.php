<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'tenant_id',
        'guest_name',
        'guest_id',
        'room_id',
        'room_number',
        'status',
        'base_rate',
        'actual_checkin_at',
        'actual_checkout_at',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class);
    }

    public function getLiveBalanceAttribute(): float
    {
        $charges = $this->folios()
            ->where('type', 'charge')
            ->where('is_reversed', false)
            ->sum(function (Folio $folio): float {
                return (float) $folio->total;
            });

        $receipts = $this->folios()
            ->where('type', 'receipt')
            ->where('is_reversed', false)
            ->sum(function (Folio $folio): float {
                return (float) $folio->total;
            });

        return round($charges - $receipts, 2);
    }
}
