<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmenityLog extends Model
{
    protected $fillable = ['tenant_id', 'room_id', 'booking_id', 'logged_by_id', 'item_name', 'quantity', 'unit_price', 'charged_to_folio'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'charged_to_folio' => 'boolean'];
    }
}
