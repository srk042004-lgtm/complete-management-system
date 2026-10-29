<?php

namespace App\Support;

use App\Models\HotelOperationLog;
use App\Models\Subscription;
use App\Models\TrialAccount;
use Illuminate\Support\Facades\DB;

/**
 * Cancels hotels whose free trial has ended without a paid subscription.
 * The hotel is suspended (so nobody can sign in), the trial is expired, and the cancellation is logged.
 */
final class TrialLifecycle
{
    public static function cancelLapsedTrials(): int
    {
        $cancelled = 0;

        // Covers trials still marked 'trial' and trials the request-time check already marked 'expired' but never suspended.
        Subscription::query()
            ->whereIn('status', ['trial', 'expired'])
            ->whereNotNull('trial_end')
            ->where('trial_end', '<=', now())
            ->with('tenant')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$cancelled): void {
                foreach ($subscriptions as $subscription) {
                    // A hotel that has since bought a plan has another subscription row; only cancel if none is paid.
                    $hasPaid = Subscription::query()
                        ->where('tenant_id', $subscription->tenant_id)
                        ->where('id', '!=', $subscription->id)
                        ->where('status', 'active')
                        ->exists();
                    if ($hasPaid) {
                        continue;
                    }

                    DB::transaction(function () use ($subscription): void {
                        $locked = Subscription::query()->whereKey($subscription->id)->lockForUpdate()->first();
                        if ($locked === null || ! in_array($locked->status, ['trial', 'expired'], true)) {
                            return;
                        }

                        $locked->forceFill(['status' => 'expired'])->save();

                        TrialAccount::query()
                            ->where('subscription_id', $locked->id)
                            ->where('status', TrialAccount::STATUS_ACTIVE)
                            ->update(['status' => TrialAccount::STATUS_EXPIRED]);

                        $tenant = $locked->tenant;
                        if ($tenant !== null && $tenant->status !== 'suspended') {
                            $tenant->forceFill(['status' => 'suspended'])->save();
                        }

                        HotelOperationLog::query()->create([
                            'tenant_id' => $locked->tenant_id,
                            'actor_id' => null,
                            'action' => 'subscription.trial_cancelled',
                            'subject_type' => Subscription::class,
                            'subject_id' => $locked->id,
                            'details' => ['trial_end' => $locked->trial_end?->toDateTimeString(), 'reason' => 'Trial ended without a paid subscription.'],
                            'ip_address' => null,
                        ]);
                    });

                    $cancelled++;
                }
            });

        return $cancelled;
    }
}
