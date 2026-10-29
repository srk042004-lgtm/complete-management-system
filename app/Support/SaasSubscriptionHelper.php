<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class SaasSubscriptionHelper
{
    public static function syncTenantAccessStatus(Tenant $tenant): void
    {
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->where('plan_type', '!=', 'lifetime')
            ->orderByDesc('ends_at')
            ->first();

        if (! $subscription || ! $subscription->ends_at) {
            return;
        }

        if ($subscription->ends_at->isPast()) {
            $tenant->status = 'suspended';
            $tenant->saveQuietly();

            Log::info('Tenant access suspended due to expired subscription.', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'ends_at' => $subscription->ends_at,
            ]);
        }
    }
}
