<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\TrialAccount;
use App\Support\HotelRoleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrialAccountController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $subscription = $request->user()->tenant?->subscriptions()->latest('id')->first();
        if ($subscription === null) {
            return response()->json(['status' => 'unavailable'], 404);
        }

        $subscription = $subscription->expireIfNeeded();
        if ($subscription->status === 'expired') {
            TrialAccount::query()->where('subscription_id', $subscription->id)
                ->where('status', TrialAccount::STATUS_ACTIVE)
                ->update(['status' => TrialAccount::STATUS_EXPIRED]);
        }

        return response()->json([
            'status' => $subscription->status,
            'is_trial' => $subscription->status === 'trial',
            'trial_ends_at' => $subscription->trial_end?->toISOString(),
            'remaining_seconds' => $subscription->remainingTrialSeconds(),
        ]);
    }

    public function activatePermanent(Request $request): RedirectResponse
    {
        abort_unless(HotelRoleCatalog::normalize((string) $request->user()->role) === 'owner', 403);

        return redirect()->route('account.subscription')
            ->with('subscription_message', 'Choose a plan and submit payment proof. Your subscription activates after Super Admin approval.');
    }
}
