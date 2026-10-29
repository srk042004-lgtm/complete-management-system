<?php

namespace App\Http\Controllers;

use App\Models\IndustryRegistration;
use App\Models\Tenant;
use App\Models\TrialAccount;
use App\Models\User;
use App\Support\HotelFeatureCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class IndustryRegistrationController extends Controller
{
    private const SYSTEM_LABELS = [
        'hotel' => 'Hotel Management System',
        'school' => 'School Management System',
        'clinic' => 'Clinic Management System',
        'restaurant' => 'Restaurant Management System',
        'retail' => 'Retail & POS Management System',
    ];

    public function create(Request $request, string $system): View|RedirectResponse
    {
        abort_unless(isset(self::SYSTEM_LABELS[$system]), 404);

        $trialAccount = null;
        if ($request->filled('trial_account')) {
            $trialAccount = TrialAccount::query()
                ->whereKey($request->integer('trial_account'))
                ->where('system', $system)
                ->where('status', TrialAccount::STATUS_PENDING)
                ->first();
        }

        if ($trialAccount === null && (! $request->filled('plan') || ! $request->filled('cycle'))) {
            return redirect()->route('pricing.calculator', ['system' => $system]);
        }

        return view('industry-register', [
            'system' => $system,
            'systemLabel' => self::SYSTEM_LABELS[$system],
            'trialDurationDays' => (int) config('saas.trial_duration_days', 10),
            'trialAccountId' => $trialAccount?->id,
            'paidPlan' => $request->query('plan'),
            'billingCycle' => $request->query('cycle'),
            'currency' => $request->query('currency', 'PKR'),
            'hotelFeatures' => $system === 'hotel' ? HotelFeatureCatalog::configurable() : [],
            'featureDefaults' => $system === 'hotel' ? [
                'luxury' => HotelFeatureCatalog::defaults('luxury'),
                'large' => HotelFeatureCatalog::defaults('large'),
                'small' => HotelFeatureCatalog::defaults('small'),
            ] : [],
        ]);
    }

    public function store(Request $request, string $system): View|RedirectResponse
    {
        abort_unless(isset(self::SYSTEM_LABELS[$system]), 404);

        if ($system === 'hotel' && ! $request->filled('hotel_type')) {
            $request->merge(['hotel_type' => 'small']);
        }

        $priceTable = [
            'hotel' => ['small' => ['monthly' => 29, 'annual' => 290, 'lifetime' => 899], 'medium' => ['monthly' => 79, 'annual' => 790, 'lifetime' => 2299], 'enterprise' => ['monthly' => 199, 'annual' => 1990, 'lifetime' => 5499]],
            'clinic' => ['small' => ['monthly' => 19, 'annual' => 190, 'lifetime' => 599], 'medium' => ['monthly' => 59, 'annual' => 590, 'lifetime' => 1699], 'enterprise' => ['monthly' => 149, 'annual' => 1490, 'lifetime' => 3999]],
        ];
        $supportedPlans = array_keys($priceTable[$system] ?? []);

        $rules = [
            'business_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'details' => ['nullable', 'array'],
            'plan' => ['nullable', 'required_without:trial_account_id', Rule::in($supportedPlans)],
            'cycle' => ['nullable', 'required_without:trial_account_id', 'in:monthly,annual,lifetime'],
            'currency' => ['nullable', 'in:PKR,USD'],
            'account_role' => ['required', 'in:owner,admin'],
            'trial_account_id' => ['nullable', 'integer', 'exists:trial_accounts,id'],
        ];
        if ($system === 'hotel') {
            $rules['hotel_type'] = ['required', Rule::in(['luxury', 'large', 'small'])];
            $rules['features'] = ['sometimes', 'array'];
            $rules['features.*'] = ['string', Rule::in(HotelFeatureCatalog::optionalKeys())];
        }
        $validated = $request->validate($rules);

        $pendingTrial = null;
        if (! empty($validated['trial_account_id'])) {
            $pendingTrial = TrialAccount::query()
                ->whereKey($validated['trial_account_id'])
                ->where('system', $system)
                ->where('status', TrialAccount::STATUS_PENDING)
                ->firstOrFail();

            abort_unless(
                hash_equals(strtolower((string) $pendingTrial->email), strtolower($validated['email'])),
                403,
                'This trial registration request belongs to a different email address.'
            );
        }

        $isDemoRegistration = $pendingTrial !== null;
        $isPaidRegistration = ! $isDemoRegistration;

        $account = DB::transaction(function () use ($validated, $system, $priceTable, $isPaidRegistration, $isDemoRegistration, $pendingTrial): array {
            $trialStart = now();
            $trialDuration = (int) config('saas.trial_duration_days', 10);
            $trialEnd = $trialStart->copy()->addDays($trialDuration);
            $plan = $validated['plan'] ?? null;
            $cycle = $validated['cycle'] ?? null;
            $currency = $validated['currency'] ?? 'PKR';
            $price = $isPaidRegistration ? $priceTable[$system][$plan][$cycle] : 0;
            if ($isPaidRegistration && $currency === 'PKR') {
                $price *= 280;
            }

            $hotelType = $system === 'hotel' ? $validated['hotel_type'] : null;
            $enabledModules = $system === 'hotel'
                ? HotelFeatureCatalog::defaults($hotelType)
                : [$system => true];
            if ($system === 'hotel' && array_key_exists('features', $validated)) {
                foreach (HotelFeatureCatalog::optionalKeys() as $feature) {
                    $enabledModules[$feature] = in_array($feature, $validated['features'] ?? [], true);
                }
            }

            $tenant = Tenant::query()->create([
                'name' => $validated['business_name'],
                'tenant_name' => $validated['business_name'],
                'business_name' => $validated['business_name'],
                'owner_name' => $validated['contact_name'],
                'owner_email' => $validated['email'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'contact_address' => $validated['address'],
                'city' => $validated['city'],
                'country' => $validated['country'],
                'status' => 'active',
                'property_type' => ucfirst($system),
                'hotel_type' => $hotelType,
                'enabled_modules' => $enabledModules,
                'currency_code' => $currency,
                'timezone' => 'Asia/Karachi',
            ]);

            $user = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $validated['contact_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['account_role'],
                'status' => 'active',
            ]);

            $subscriptionCycle = $cycle === 'annual' ? 'yearly' : ($cycle ?? 'monthly');
            $subscription = $tenant->subscriptions()->create([
                'plan_type' => $subscriptionCycle,
                'price' => $price,
                'currency_code' => $currency,
                'status' => $isDemoRegistration ? 'trial' : 'expired',
                'starts_at' => $isDemoRegistration ? $trialStart : null,
                'ends_at' => $isDemoRegistration ? $trialEnd : null,
                'trial_start' => $isDemoRegistration ? $trialStart : null,
                'trial_end' => $isDemoRegistration ? $trialEnd : null,
                'trial_duration_days' => $isDemoRegistration ? $trialDuration : null,
                'auto_renew' => false,
                'has_rooms' => $system === 'hotel',
                'has_restaurant_pos' => $system === 'hotel' ? $enabledModules['pos'] : $system === 'restaurant',
            ]);

            if ($isPaidRegistration) {
                $registration = new IndustryRegistration;
                $registration->setTable($system.'_registrations');
                $registration->fill([
                    'tenant_id' => $tenant->id,
                    'business_name' => $validated['business_name'],
                    'contact_name' => $validated['contact_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                    'country' => $validated['country'],
                    'details' => array_merge($validated['details'] ?? [], [
                        'account_type' => 'permanent',
                        'plan_tier' => $plan,
                        'billing_cycle' => $cycle,
                        'quoted_price' => $price,
                        'currency_code' => $currency,
                        'payment_status' => 'awaiting_approval',
                    ]),
                ]);
                $registration->save();
            }

            $trialAccount = null;
            if ($isDemoRegistration) {
                $trialAccount = $pendingTrial ?? new TrialAccount;
                $trialAccount->fill([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'system' => $system,
                    'business_name' => $validated['business_name'],
                    'contact_name' => $validated['contact_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'source' => $pendingTrial?->source ?? 'direct_registration',
                    'status' => TrialAccount::STATUS_ACTIVE,
                    'trial_starts_at' => $trialStart,
                    'trial_ends_at' => $trialEnd,
                    'registered_at' => $trialStart,
                    'metadata' => $validated['details'] ?? [],
                ]);
                $trialAccount->save();
            }

            return compact('tenant', 'user', 'subscription', 'trialAccount');
        });

        Auth::login($account['user']);
        $request->session()->regenerate();
        if ($system === 'hotel') {
            $request->session()->put('hotel_tenant_id', (int) $account['user']->tenant_id);
        }

        if ($isPaidRegistration) {
            return redirect()->route('account.subscription')
                ->with('success', 'Your permanent account record is saved. Submit payment proof for Super Admin approval to activate access.');
        }

        return view('industry-registration-success', [
            'tenant' => $account['tenant'],
            'subscription' => $account['subscription'],
            'system' => $system,
            'isPaidRegistration' => false,
        ]);
    }
}
