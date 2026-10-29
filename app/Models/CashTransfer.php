<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransfer extends Model
{
    public const TYPES = ['shift_handover' => 'Shift handover', 'vault_drop' => 'Night vault drop'];
    public const STATUSES = ['pending' => 'Pending verification', 'verified' => 'Verified', 'rejected' => 'Rejected'];
    public const PAYMENT_METHODS = [
        'physical_cash' => 'Physical cash',
        'bank_transfer' => 'Bank transfer',
        'pos_card' => 'POS card',
        'online_wallet' => 'Online wallet',
        'mixed' => 'Mixed',
    ];

    protected $fillable = [
        'tenant_id',
        'cash_shift_id',
        'sender_id',
        'receiver_id',
        'amount',
        'retained_float',
        'transfer_type',
        'payment_method',
        'transaction_reference',
        'proof_image_path',
        'status',
        'consumed_by_shift_id',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'retained_float' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'cash_shift_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
