<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'plan_type',
        'price',
        'currency_code',
        'status',
        'starts_at',
        'trial_start',
        'trial_end',
        'trial_duration_days',
        'ends_at',
        'auto_renew',
        'has_rooms',
        'has_restaurant_pos',
        'restaurant_addon_price',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'trial_start' => 'datetime',
        'trial_end' => 'datetime',
        'trial_duration_days' => 'integer',
        'ends_at' => 'datetime',
        'auto_renew' => 'boolean',
        'price' => 'decimal:2',
        'has_rooms' => 'boolean',
        'has_restaurant_pos' => 'boolean',
        'restaurant_addon_price' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SaasPayment::class);
    }

    public function expireIfNeeded(): self
    {
        $expiryDate = $this->status === 'trial' ? $this->trial_end : $this->ends_at;
        if (in_array($this->status, ['trial', 'active'], true) && $expiryDate?->isPast()) {
            $this->forceFill(['status' => 'expired'])->save();
        }

        return $this->refresh();
    }
}
