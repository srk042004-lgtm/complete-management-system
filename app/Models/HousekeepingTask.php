<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingTask extends Model
{
    public const SERVICE_TYPES = ['checkout_clean' => 'Checkout clean', 'stayover' => 'Stayover service', 'turndown' => 'Turndown service', 'deep_clean' => 'Deep clean / sanitisation'];

    protected $fillable = ['tenant_id', 'room_id', 'assigned_to_user_id', 'service_type', 'status', 'priority', 'inspection_notes', 'completed_at'];

    protected function casts(): array
    {
        return ['priority' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
