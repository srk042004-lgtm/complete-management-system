<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashShift extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'tenant_id',
        'opened_by',
        'closed_by',
        'opening_float',
        'cash_collected',
        'refunds_paid',
        'expenses_total',
        'shortage_acknowledged_at',
        'expected_cash',
        'counted_cash',
        'variance',
        'status',
        'opened_at',
        'closed_at',
        'notes',
        'handover_to_id',
        'opening_source',
        'retained_from_transfer_id',
    ];

    protected function casts(): array
    {
        return [
            'opening_float' => 'decimal:2',
            'cash_collected' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'variance' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HotelPayment::class, 'cash_shift_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /** Recomputes cash collected and the expected drawer total from the cash payments linked to this shift. */
    public function handoverTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handover_to_id');
    }

    public function recalculate(): void
    {
        $collected = (float) $this->payments()->where('payment_method', 'cash')->sum('amount');
        $this->cash_collected = $collected;
        // Expected drawer cash: opening float + cash collected - refunds paid out of the drawer.
        $expenses = (float) ShiftExpense::query()->where('cash_shift_id', $this->id)->sum('amount');
        $this->expenses_total = $expenses;
        $this->expected_cash = round((float) $this->opening_float + $collected - (float) $this->refunds_paid - $expenses, 2);
        $this->save();
    }
}
