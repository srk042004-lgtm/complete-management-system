<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'payment_reference',
        'amount',
        'currency_code',
        'payment_status',
        'gateway',
        'payment_method',
        'transaction_id',
        'notes',
        'paid_at',
        'refunded_at',
        'requested_plan_id',
        'proof_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
    ];

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'requested_plan_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
