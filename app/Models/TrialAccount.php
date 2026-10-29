<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialAccount extends Model
{
    public const STATUS_PENDING = 'pending_registration';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'demo_request_id',
        'tenant_id',
        'user_id',
        'subscription_id',
        'system',
        'business_name',
        'contact_name',
        'email',
        'phone',
        'source',
        'status',
        'trial_starts_at',
        'trial_ends_at',
        'registered_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'registered_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function demoRequest(): BelongsTo
    {
        return $this->belongsTo(DemoRequest::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now());
    }
}
