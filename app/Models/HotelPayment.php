<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'booking_id',
        'transaction_reference',
        'proof_path',
        'payment_method',
        'amount',
        'recorded_by',
        'cash_shift_id',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        // Cash taken while a shift is open lands in that shift's drawer automatically.
        static::creating(function (HotelPayment $payment): void {
            if ($payment->payment_method !== 'cash' || $payment->cash_shift_id !== null) {
                return;
            }

            $payment->cash_shift_id = CashShift::query()
                ->where('tenant_id', $payment->tenant_id)
                ->open()
                ->latest('opened_at')
                ->value('id');
        });

        static::created(function (HotelPayment $payment): void {
            if ($payment->cash_shift_id !== null) {
                CashShift::query()->whereKey($payment->cash_shift_id)->first()?->recalculate();
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'cash_shift_id');
    }
}
