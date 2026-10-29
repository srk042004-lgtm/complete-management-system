<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LostAndFoundItem extends Model
{
    public const CATEGORIES = ['electronics' => 'Electronics', 'clothing' => 'Clothing', 'jewelry' => 'Jewelry', 'documents' => 'Documents', 'wallet_keys' => 'Wallet / Keys', 'other' => 'Other'];

    protected $table = 'lost_and_found_items';

    protected $fillable = ['tenant_id', 'room_id', 'found_by_id', 'guest_id', 'item_description', 'category', 'location_found', 'found_at', 'photo_path', 'status'];

    protected function casts(): array
    {
        return ['found_at' => 'datetime'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
