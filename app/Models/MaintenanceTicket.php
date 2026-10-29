<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicket extends Model
{
    public const CATEGORIES = ['plumbing' => 'Plumbing', 'electrical' => 'Electrical', 'hvac' => 'HVAC / AC', 'furniture' => 'Furniture', 'tv_wifi' => 'TV / Wi-Fi', 'lock' => 'Lock'];
    public const SEVERITIES = ['low' => 'Low', 'moderate' => 'Moderate', 'urgent' => 'Urgent (blocks check-in)'];

    protected $fillable = ['tenant_id', 'room_id', 'reported_by_id', 'category', 'severity', 'description', 'status', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
