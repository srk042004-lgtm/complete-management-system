@php
    $bannerSubscription = auth()->user()?->tenant?->subscriptions()->latest('id')->first();
    $canManageSubscription = auth()->check()
        && in_array(\App\Support\HotelRoleCatalog::normalize((string) auth()->user()->role), ['owner', 'admin'], true);
@endphp
@if($canManageSubscription && $bannerSubscription && (
    in_array($bannerSubscription->status, ['trial', 'expired'], true)
    || ($bannerSubscription->status === 'active' && $bannerSubscription->ends_at !== null)
))
    @php
        $activeSubscriptionSecondsRemaining = $bannerSubscription->status === 'active' && $bannerSubscription->ends_at
            ? now()->diffInSeconds($bannerSubscription->ends_at, false)
            : null;
        $showActiveExpiryCountdown = $activeSubscriptionSecondsRemaining !== null
            && $activeSubscriptionSecondsRemaining > 0
            && $activeSubscriptionSecondsRemaining <= 259200;
    @endphp
    <aside class="subscription-trial-banner" data-subscription-banner>
        <div>
            <strong>{{ $bannerSubscription->status === 'trial' ? 'Hotel workspace trial' : ($bannerSubscription->status === 'expired' ? 'Subscription expired' : 'Subscription active') }}</strong>
            @if($bannerSubscription->status === 'trial' && $bannerSubscription->trial_end)
                <span>Time remaining: <b data-banner-countdown data-ends-at="{{ $bannerSubscription->trial_end->toIso8601String() }}">Calculating…</b></span>
            @elseif($bannerSubscription->status === 'active' && $bannerSubscription->ends_at)
                <span>Plan expires {{ $bannerSubscription->ends_at->format('d M Y') }}.</span>
                @if($showActiveExpiryCountdown)
                    <span data-expiry-reminder data-dismiss-key="subscription-expiry-dismissed-{{ $bannerSubscription->id }}-{{ $bannerSubscription->ends_at->timestamp }}">
                        Renewal due in <b data-banner-countdown data-ends-at="{{ $bannerSubscription->ends_at->toIso8601String() }}">Calculating…</b>
                        <button class="subscription-trial-banner__dismiss" type="button" data-dismiss-expiry aria-label="Hide subscription expiry timer">×</button>
                    </span>
                @endif
            @else
                <span>Submit a payment request to restore or extend paid access.</span>
            @endif
        </div>
        <a href="{{ route('account.subscription') }}">Manage subscription</a>
    </aside>
    <style>
        .subscription-trial-banner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin:0 0 16px;padding:11px 14px;border:1px solid #f1d08a;border-radius:12px;background:linear-gradient(110deg,#fff8e7,#fff1e8);color:#78450d;font-size:11px}
        .subscription-trial-banner strong{display:block;font-size:11px}
        .subscription-trial-banner span{display:block;margin-top:3px}
        .subscription-trial-banner a{padding:7px 10px;border-radius:8px;background:#9a4b12;color:#fff;font-size:10px;font-weight:850;text-decoration:none}
        html[data-theme="dark"] .subscription-trial-banner{border-color:#526477;background:linear-gradient(110deg,#222f3e,#1a2532);color:#edf3f8}
        html[data-theme="dark"] .subscription-trial-banner strong{color:#edf3f8}
        html[data-theme="dark"] .subscription-trial-banner a{display:inline-flex;align-items:center;justify-content:center;background:#65d6c5;color:#10252b!important}
        .subscription-trial-banner__dismiss{margin-left:8px;padding:0 5px;border:0;border-radius:5px;background:transparent;color:inherit;font-size:16px;line-height:1;cursor:pointer}
        .subscription-trial-banner__dismiss:hover{background:rgba(120,69,13,.12)}
        html[data-theme="dark"] .subscription-trial-banner__dismiss:hover{background:rgba(255,255,255,.12)}
        @media(max-width:600px){.subscription-trial-banner{align-items:flex-start;flex-direction:column}}
    </style>
    <script>
        (function () {
            document.querySelectorAll('[data-banner-countdown]').forEach(function (clock) {
                const expiry = new Date(clock.dataset.endsAt).getTime();
                const update = function () {
                    const remaining = Math.max(0, Math.floor((expiry - Date.now()) / 1000));
                    if (!remaining) {
                        clock.textContent = 'Expired';
                        return;
                    }
                    clock.textContent = Math.floor(remaining / 86400) + 'd ' + Math.floor(remaining % 86400 / 3600) + 'h ' + Math.floor(remaining % 3600 / 60) + 'm ' + remaining % 60 + 's';
                };
                update();
                window.setInterval(update, 1000);
            });

            const reminder = document.querySelector('[data-expiry-reminder]');
            if (!reminder) return;
            const dismissKey = reminder.dataset.dismissKey;
            if (window.localStorage.getItem(dismissKey) === '1') reminder.hidden = true;
            reminder.querySelector('[data-dismiss-expiry]').addEventListener('click', function () {
                window.localStorage.setItem(dismissKey, '1');
                reminder.hidden = true;
            });
        })();
    </script>
@endif
