<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasLedgerEntry extends Model
{
    protected $table = 'saas_ledgers';

    protected $fillable = [
        'tenant_id', 'subscription_id', 'title', 'type', 'amount', 'currency', 'payment_method', 'transaction_ref', 'payment_date', 'status', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
