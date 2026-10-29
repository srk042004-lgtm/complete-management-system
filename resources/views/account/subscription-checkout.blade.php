<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription | ArtFlow</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f7f8;color:#16343b;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;font-size:14px}.wrap{width:min(1040px,calc(100% - 30px));margin:34px auto 60px}.top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}.brand{color:#0f766e;font-size:13px;font-weight:950;letter-spacing:.12em}.back{color:#47616a;font-size:12px;font-weight:750;text-decoration:none}.card{overflow:hidden;border:1px solid #dce7e9;border-radius:20px;background:#fff;box-shadow:0 14px 40px rgba(19,52,59,.08)}.head{padding:22px 25px;background:#12343b;color:#fff}.head h1{margin:0;font-size:22px}.head p{margin:6px 0 0;color:#c3d4d7;font-size:12px}.body{padding:23px}.trial{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px;padding:15px 17px;border:1px solid #f4d38d;border-radius:14px;background:linear-gradient(110deg,#fff8e7,#fff2e9);color:#814b12}.trial strong{font-size:13px}.trial-time{font-family:ui-monospace,monospace;font-size:16px;font-weight:900}.notice{margin-bottom:16px;padding:12px 14px;border-radius:11px;background:#ecfdf5;color:#065f46;font-size:12px}.error{margin-bottom:16px;padding:12px 14px;border:1px solid #fecaca;border-radius:11px;background:#fff1f2;color:#9f1239;font-size:12px}.grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(310px,.85fr);gap:18px}.section{padding:16px;border:1px solid #e1eaec;border-radius:14px}.section h2{margin:0 0 4px;font-size:14px}.hint{margin:0 0 14px;color:#708187;font-size:11px;line-height:1.5}.plans{display:grid;gap:10px}.plan{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px;border:1px solid #d9e4e6;border-radius:12px;background:#fff;cursor:pointer}.plan:has(input:checked){border-color:#0f766e;background:#f0faf7;box-shadow:0 0 0 2px rgba(15,118,110,.1)}.plan-main{display:flex;align-items:center;gap:10px}.plan input{accent-color:#0f766e}.plan-name{display:block;font-size:12px;font-weight:850}.plan-cycle{display:block;margin-top:3px;color:#718187;font-size:10px}.plan-price{font-size:15px;font-weight:900;color:#0f6257;white-space:nowrap}.methods{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.method{display:flex;align-items:flex-start;gap:8px;padding:11px;border:1px solid #d9e4e6;border-radius:10px;cursor:pointer}.method:has(input:checked){border-color:#0f766e;background:#f0faf7}.method:has(input:disabled){opacity:.48;cursor:not-allowed}.method input{margin-top:2px;accent-color:#0f766e}.method strong{display:block;font-size:11px}.method small{display:block;margin-top:3px;color:#708187;font-size:9px}.account{margin-top:12px;padding:13px;border-radius:11px;background:#12343b;color:#fff}.account h3{margin:0 0 9px;color:#68d8c2;font-size:11px}.account-row{display:flex;justify-content:space-between;gap:12px;margin-top:6px;font-size:10px}.account-row span:first-child{color:#a8bec2}.account-row strong{overflow-wrap:anywhere;text-align:right}.account-instructions{margin:9px 0 0;color:#d0e0e2;font-size:10px;line-height:1.5}.copy{margin-left:5px;padding:3px 7px;border:0;border-radius:6px;background:#315157;color:#fff;font-size:9px;cursor:pointer}.field{display:grid;gap:6px;margin-top:12px;color:#425a60;font-size:10px;font-weight:800}.field input{width:100%;min-height:39px;padding:8px 10px;border:1px solid #d5e0e2;border-radius:9px;background:#fbfdfd;color:#19383f;font:inherit;font-size:11px}.field input[type=file]{padding:8px}.help{color:#788a8f;font-size:9px;font-weight:500}.submit{width:100%;min-height:43px;margin-top:15px;border:0;border-radius:10px;background:#0f766e;color:#fff;font-size:11px;font-weight:900;cursor:pointer}.submit:disabled{opacity:.5;cursor:not-allowed}.pending{padding:18px;border:1px solid #f4d38d;border-radius:12px;background:#fffbeb;color:#854d0e;font-size:12px;line-height:1.6}.pending strong{display:block;margin-bottom:4px}.pending-status{display:block;margin-top:7px;font-size:10px}.approval-dialog{width:min(440px,calc(100% - 30px));padding:25px;border:1px solid #b7e6d2;border-radius:18px;background:#fff;color:#16343b;text-align:center;box-shadow:0 24px 80px rgba(16,52,59,.24)}.approval-dialog::backdrop{background:rgba(13,35,40,.58);backdrop-filter:blur(3px)}.approval-dialog h2{margin:0 0 8px;color:#087568;font-size:21px}.approval-dialog p{margin:0 0 14px;font-size:12px;line-height:1.6}.approval-dialog a{display:inline-flex;justify-content:center;padding:10px 16px;border-radius:9px;background:#0f766e;color:#fff;font-size:11px;font-weight:850;text-decoration:none}.approval-redirect{display:block;margin-top:10px;color:#718187;font-size:10px}@media (max-width:760px){.wrap{margin-top:18px}.grid{grid-template-columns:1fr}.body{padding:15px}.head{padding:18px}.methods{grid-template-columns:1fr 1fr}}@media (max-width:450px){.methods{grid-template-columns:1fr}.plan{align-items:flex-start}.plan-price{font-size:12px}}
    </style>
    <link rel="stylesheet" href="{{ asset('css/hotel-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-switcher.css') }}">
    @include('partials.slate-teal-theme')
    <style>
        /* Billing page restyled to the shared light theme: calm header, light payment-details card, consistent controls. */
        .head { background: var(--st-card) !important; color: var(--st-text) !important; border-bottom: 1px solid var(--st-line); border-left: 4px solid var(--st-accent); }
        .head h1 { font-size: 1.25rem !important; font-weight: 700 !important; color: var(--st-text) !important; }
        .head p { color: var(--st-muted) !important; }
        .account { background: var(--st-accent-soft) !important; color: var(--st-text) !important; border: 1px solid var(--st-line); }
        .account strong, .account b { color: var(--st-text) !important; }
        .copy { background: var(--st-card) !important; color: var(--st-accent-text) !important; border: 1px solid var(--st-line) !important; font-size: 11px !important; font-weight: 600; padding: 4px 9px !important; }
        .copy:hover { background: var(--st-hover) !important; }
        .wrap { width: min(1100px, calc(100% - 40px)) !important; }
        .field input[type=file] { padding: 8px 10px; border: 1px solid var(--st-line); border-radius: 10px; background: var(--st-card); width: 100%; font: inherit; font-size: 13px; color: var(--st-text); }
        .field input[type=file]::file-selector-button { margin-right: 12px; padding: 7px 14px; border: 1px solid var(--st-line); border-radius: 8px; background: var(--st-hover); color: var(--st-text); font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: background-color .15s ease; }
        .field input[type=file]::file-selector-button:hover { background: var(--st-accent-soft); }
        .field input:not([type=file]):not([type=radio]):not([type=checkbox]), .field select, .field textarea { border: 1px solid var(--st-line); border-radius: 10px; background: var(--st-card); color: var(--st-text); font: inherit; font-size: 13.5px; padding: 9px 12px; }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--st-accent); box-shadow: 0 0 0 3px var(--st-accent-soft); }
        .method, .plan, .plan-card { transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease; }
        .method:hover, .plan:hover, .plan-card:hover { border-color: var(--st-accent) !important; }
    </style>
</head>
<body class="hotel-sidebar-page" data-hotel-layout-mode="sidebar">
@include('partials.hotel-shell-start')
<main class="wrap">
    <div class="top"><span class="brand">ARTFLOW</span><a class="back" href="{{ route('hotel.role.dashboard', ['role' => auth()->user()->role === 'hotel_admin' ? 'admin' : auth()->user()->role]) }}">Back to workspace</a></div>
    <section class="card">
        <header class="head">
            <h1>Subscription &amp; billing</h1>
            <p>{{ $tenant->hotel_name ?? $tenant->tenant_name ?? $tenant->name }} · Manage your hotel workspace plan.</p>
        </header>
        <div class="body">
            @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
            @if(session('subscription_message'))<div class="notice" role="status">{{ session('subscription_message') }}</div>@endif
            @if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
            @if($subscription->status === 'trial' && $subscription->trial_end)
                <div class="trial" data-trial-end="{{ $subscription->trial_end->toIso8601String() }}">
                    <div><strong>Free trial</strong><div style="margin-top:4px;font-size:11px">Trial ends {{ $subscription->trial_end->format('d M Y H:i') }}</div></div>
                    <div class="trial-time" data-trial-countdown>Calculating…</div>
                </div>
            @elseif($subscription->status === 'expired')
                <div class="trial"><strong>Your account is awaiting plan approval. Submit payment proof to request activation.</strong></div>
            @endif

            @if($pendingPayment)
                <div class="pending" data-payment-watch data-status-url="{{ route('account.subscription.payment-status', $pendingPayment->payment_reference) }}" data-workspace-url="{{ route('hotel.role.dashboard', ['role' => auth()->user()->role === 'hotel_admin' ? 'admin' : auth()->user()->role]) }}">
                    <strong>Payment request awaiting verification</strong>
                    Reference: {{ $pendingPayment->payment_reference }} · {{ $pendingPayment->currency_code }} {{ number_format((float) $pendingPayment->amount, 2) }}<br>
                    We will update your subscription after a Super Admin reviews the payment proof.
                    <span class="pending-status" data-payment-watch-status>Checking for review updates…</span>
                </div>
            @elseif($plans->isEmpty())
                <div class="empty">There are no active subscription plans available right now. Please contact support.</div>
            @else
                <form method="POST" action="{{ route('account.subscription.payment-request') }}" enctype="multipart/form-data" data-subscription-form>
                    @csrf
                    <div class="grid">
                        <section class="section">
                            <h2>Choose a plan</h2>
                            <p class="hint">Plan prices and currencies are set by ArtFlow and cannot be changed on this form.</p>
                            @if($requestedCycle)
                                <p class="hint">Your registration selected {{ $requestedCycle === 'yearly' ? 'annual' : $requestedCycle }} billing. The price shown below is the exact amount submitted for approval and determines your subscription term.</p>
                            @endif
                            <div class="plans">
                                @foreach($plans as $plan)
                                    <label class="plan">
                                        <span class="plan-main"><input type="radio" name="plan_id" value="{{ $plan->id }}" data-plan-cycle="{{ $plan->billing_cycle }}" data-plan-price="{{ $plan->price }}" data-plan-currency="{{ $plan->currency_code }}" @checked(old('plan_id') == $plan->id || (!old('plan_id') && $loop->first)) required><span><span class="plan-name">{{ $plan->name }}</span><span class="plan-cycle">{{ match($plan->billing_cycle) { 'yearly' => 'Annual', 'lifetime' => 'Lifetime', default => 'Monthly' } }} billing</span></span></span>
                                        <span class="plan-price">{{ $plan->currency_code }} {{ number_format((float) $plan->price, 2) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="hint" style="margin:12px 0 0">Your current status: <strong>{{ ucfirst($subscription->status) }}</strong>@if($subscription->ends_at) · Ends {{ $subscription->ends_at->format('d M Y') }}@endif</p>
                        </section>
                        <section class="section">
                            <h2>Payment and proof</h2>
                            <p class="hint">Choose the payment channel used, then enter its transaction reference and upload a receipt image.</p>
                            <div class="methods">
                                @foreach(['easypaisa' => 'Easypaisa', 'jazzcash' => 'JazzCash', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash payment'] as $method => $label)
                                    @php
                                        $details = data_get($paymentAccounts, $method, []);
                                        $configured = filled(data_get($details, 'account_number')) || filled(data_get($details, 'iban')) || filled(data_get($details, 'instructions'));
                                    @endphp
                                    <label class="method" data-payment-method-card="{{ $method }}" data-payment-account-name="{{ data_get($details, 'account_name') }}" data-payment-account-number="{{ data_get($details, 'account_number') }}" data-payment-account-iban="{{ data_get($details, 'iban') }}" data-payment-account-instructions="{{ data_get($details, 'instructions') }}">
                                        <input type="radio" name="payment_method" value="{{ $method }}" @checked($configured && old('payment_method') === $method) @disabled(!$configured) required>
                                        <span><strong>{{ $label }}</strong><small>{{ $configured ? 'Payment instructions available' : 'Not configured yet' }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="account" data-account-display aria-live="polite">
                                <h3 data-account-title>Select a configured payment method</h3>
                                <div class="account-row"><span>Account title</span><strong data-account-name>—</strong></div>
                                <div class="account-row"><span>Account / mobile</span><strong><span data-account-number>—</span><button class="copy" type="button" data-copy-account hidden>Copy</button></strong></div>
                                <div class="account-row" data-account-iban-row hidden><span>IBAN</span><strong><span data-account-iban></span><button class="copy" type="button" data-copy-iban>Copy</button></strong></div>
                                <p class="account-instructions" data-account-instructions></p>
                            </div>
                            <label class="field">Transaction ID / reference
                                <input type="text" name="transaction_id" value="{{ old('transaction_id') }}" maxlength="120" autocomplete="off" required>
                            </label>
                            <label class="field">Payment screenshot / receipt
                                <input type="file" name="screenshot" accept="image/jpeg,image/png,image/webp" required>
                                <span class="help">JPG, PNG, or WebP; maximum 2 MB. Uploaded proofs are visible only to Super Admin reviewers.</span>
                            </label>
                            <button class="submit" type="submit" @disabled(!$plans->count())>Submit payment for approval</button>
                        </section>
                    </div>
                </form>
            @endif
            @if(!$pendingPayment && $latestPayment?->payment_status === 'paid')
                <div class="notice" role="status">Your latest subscription payment request was approved. Your plan is now active.</div>
            @elseif(!$pendingPayment && $latestPayment?->payment_status === 'failed')
                <div class="error" role="status">Your latest subscription payment request was not approved. Review the note below and submit a new request if needed.<br><span class="help">{{ $latestPayment->notes }}</span></div>
            @endif
            @if(data_get($support, 'support_email.value') || data_get($support, 'support_phone.value'))
                <p class="support">Need help? @if(data_get($support, 'support_email.value')){{ data_get($support, 'support_email.value') }}@endif @if(data_get($support, 'support_phone.value')) · {{ data_get($support, 'support_phone.value') }}@endif</p>
            @endif
        </div>
    </section>
</main>
<dialog class="approval-dialog" data-approval-dialog>
    <h2>Congratulations!</h2>
    <p data-approval-message>Your subscription has been successfully approved.</p>
    <a data-approval-workspace>Go to your hotel workspace</a>
    <span class="approval-redirect" data-approval-redirect></span>
</dialog>
<script>
    (function () {
        const timer = document.querySelector('[data-trial-countdown]');
        if (timer) {
            const end = new Date(timer.closest('[data-trial-end]').dataset.trialEnd).getTime();
            const update = function () {
                const seconds = Math.max(0, Math.floor((end - Date.now()) / 1000));
                const days = Math.floor(seconds / 86400);
                const hours = Math.floor((seconds % 86400) / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const remainder = seconds % 60;
                timer.textContent = seconds ? days + 'd ' + hours + 'h ' + minutes + 'm ' + remainder + 's' : 'Trial expired';
            };
            update();
            window.setInterval(update, 1000);
        }

        const cards = document.querySelectorAll('[data-payment-method-card]');
        const title = document.querySelector('[data-account-title]');
        const name = document.querySelector('[data-account-name]');
        const number = document.querySelector('[data-account-number]');
        const iban = document.querySelector('[data-account-iban]');
        const ibanRow = document.querySelector('[data-account-iban-row]');
        const instructions = document.querySelector('[data-account-instructions]');
        const copyNumber = document.querySelector('[data-copy-account]');
        const copyIban = document.querySelector('[data-copy-iban]');
        const methodLabels = { easypaisa: 'Easypaisa instructions', jazzcash: 'JazzCash instructions', bank_transfer: 'Bank transfer instructions', cash: 'Cash payment instructions' };
        const refreshAccount = function () {
            const selected = document.querySelector('[name="payment_method"]:checked');
            const card = selected ? selected.closest('[data-payment-method-card]') : null;
            title.textContent = card ? methodLabels[card.dataset.paymentMethodCard] : 'Select a configured payment method';
            name.textContent = card?.dataset.paymentAccountName || '—';
            number.textContent = card?.dataset.paymentAccountNumber || '—';
            iban.textContent = card?.dataset.paymentAccountIban || '';
            ibanRow.hidden = !card?.dataset.paymentAccountIban;
            instructions.textContent = card?.dataset.paymentAccountInstructions || '';
            copyNumber.hidden = !card?.dataset.paymentAccountNumber;
        };
        cards.forEach(function (card) {
            card.querySelector('input').addEventListener('change', refreshAccount);
        });
        if (!document.querySelector('[name="payment_method"]:checked')) {
            const firstConfiguredMethod = document.querySelector('[name="payment_method"]:not(:disabled)');
            if (firstConfiguredMethod) firstConfiguredMethod.checked = true;
        }
        document.querySelectorAll('[data-plan-cycle]').forEach(function (input) {
            input.addEventListener('change', function () {
                const form = input.closest('form');
                form.dataset.selectedPlan = input.value;
            });
        });
        const copyText = function (text) {
            if (text && navigator.clipboard) navigator.clipboard.writeText(text);
        };
        copyNumber.addEventListener('click', function () { copyText(number.textContent); });
        copyIban.addEventListener('click', function () { copyText(iban.textContent); });
        refreshAccount();

        const paymentWatch = document.querySelector('[data-payment-watch]');
        if (paymentWatch) {
            const statusText = paymentWatch.querySelector('[data-payment-watch-status]');
            const approvalDialog = document.querySelector('[data-approval-dialog]');
            const approvalMessage = approvalDialog.querySelector('[data-approval-message]');
            const workspaceLink = approvalDialog.querySelector('[data-approval-workspace]');
            const redirectText = approvalDialog.querySelector('[data-approval-redirect]');
            workspaceLink.href = paymentWatch.dataset.workspaceUrl;
            let polling = true;
            let redirectTimer = null;
            const checkStatus = async function () {
                if (!polling) return;
                try {
                    const response = await fetch(paymentWatch.dataset.statusUrl, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });
                    if (!response.ok) throw new Error('Could not check the payment review status.');
                    const state = await response.json();
                    if (state.payment_status === 'paid' && state.subscription_status === 'active') {
                        polling = false;
                        const cycle = state.billing_cycle === 'yearly' ? 'annual' : 'monthly';
                        approvalMessage.textContent = 'Your ' + cycle + ' subscription has been successfully approved and is active.';
                        approvalDialog.showModal();
                        let seconds = 8;
                        const updateRedirect = function () {
                            redirectText.textContent = 'Taking you to your workspace in ' + seconds + ' seconds…';
                            if (seconds <= 0) {
                                window.location.assign(workspaceLink.href);
                                return;
                            }
                            seconds -= 1;
                        };
                        updateRedirect();
                        redirectTimer = window.setInterval(updateRedirect, 1000);
                    } else if (state.payment_status === 'failed') {
                        polling = false;
                        statusText.textContent = 'The payment request was not approved. Review the decision and submit a new request if needed.';
                    } else {
                        statusText.textContent = 'Still waiting for Super Admin review…';
                    }
                } catch (error) {
                    statusText.textContent = 'Unable to check the review status right now. We will retry automatically.';
                }
            };
            checkStatus();
            const pollTimer = window.setInterval(function () {
                if (!polling) {
                    window.clearInterval(pollTimer);
                    return;
                }
                checkStatus();
            }, 5000);
            workspaceLink.addEventListener('click', function () {
                if (redirectTimer !== null) window.clearInterval(redirectTimer);
            });
        }
    })();
</script>
@include('partials.hotel-shell-end')
</body>
</html>
