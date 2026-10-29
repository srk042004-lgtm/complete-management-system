<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftExpense extends Model
{
    public const CATEGORIES = [
        'guest_refreshments' => 'Guest tea / water',
        'cleaning_supplies' => 'Cleaning supplies',
        'stationery' => 'Stationery',
        'transport' => 'Transport',
        'other' => 'Other',
    ];

    protected $fillable = [
        'tenant_id',
        'cash_shift_id',
        'user_id',
        'amount',
        'category',
        'description',
        'receipt_path',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'cash_shift_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
