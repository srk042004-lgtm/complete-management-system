<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasLedger extends Model
{
    protected $table = 'saas_ledger';

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'transaction_type',
        'payment_method',
        'gross_amount',
        'tax_amount',
        'net_amount',
        'currency_code',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
