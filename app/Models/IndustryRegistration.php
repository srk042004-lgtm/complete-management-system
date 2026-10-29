<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryRegistration extends Model
{
    protected $guarded = [];

    protected $casts = ['details' => 'array'];

    public static function latestPermanentForTenant(int $tenantId): ?self
    {
        foreach (['hotel', 'school', 'clinic', 'restaurant', 'retail'] as $industry) {
            $registration = new self;
            $registration->setTable($industry.'_registrations');
            $record = $registration->newQuery()
                ->where('tenant_id', $tenantId)
                ->latest('id')
                ->first();

            if ($record !== null && data_get($record->details, 'account_type') === 'permanent') {
                return $record;
            }
        }

        return null;
    }

    public function updatePaymentReviewStatus(string $status, ?Plan $plan = null): void
    {
        $details = $this->details ?? [];
        $details['payment_status'] = $status;
        $details[$status.'_at'] = now()->toIso8601String();
        if ($plan !== null) {
            $details['approved_plan_id'] = $plan->id;
            $details['approved_plan_name'] = $plan->name;
            $details['billing_cycle'] = $plan->billing_cycle;
        }

        $this->forceFill(['details' => $details])->save();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
