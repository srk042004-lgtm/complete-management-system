<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterHotelRequest;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Support\HotelRoleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class HotelAdminAuthController extends Controller
{
    public function create(): View
    {
        return view('hotel-admin.auth.register', [
            'countries' => $this->countries(),
            'currencies' => $this->currencies(),
            'timezones' => $this->timezones(),
            'hotelTypes' => $this->hotelTypes(),
        ]);
    }

    public function store(RegisterHotelRequest $request): View|RedirectResponse
    {
        $validated = $request->validated();
        $logoPath = null;

        try {
            $registration = DB::transaction(function () use ($request, $validated, &$logoPath): array {
                $trialStart = now();
                $trialDuration = max(1, (int) config('saas.trial_duration_days', 14));
                $trialEnd = $trialStart->copy()->addDays($trialDuration);
                $hotelType = $validated['hotel_type'] === 'Other'
                    ? $validated['other_hotel_type']
                    : $validated['hotel_type'];

                if ($request->hasFile('logo')) {
                    $logoPath = $request->file('logo')->store('hotel-logos', 'public');
                }

                $tenant = Tenant::query()->create([
                    'name' => $validated['hotel_name'],
                    'hotel_name' => $validated['hotel_name'],
                    'slug' => $this->uniqueSlug($validated['hotel_name']),
                    'hotel_type' => $hotelType,
                    'property_type' => $hotelType,
                    'total_rooms' => $validated['total_rooms'],
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                    'state' => $validated['state'] ?? null,
                    'country' => $validated['country'],
                    'postal_code' => $validated['postal_code'] ?? null,
                    'phone' => $validated['hotel_phone'],
                    'email' => $validated['hotel_email'],
                    'website' => $validated['website'] ?? null,
                    'logo_path' => $logoPath,
                    'description' => $validated['description'] ?? null,
                    'owner_name' => $validated['admin_name'],
                    'owner_email' => $validated['admin_email'],
                    'business_name' => $validated['business_name'] ?? null,
                    'registration_number' => $validated['registration_number'] ?? null,
                    'tax_number' => $validated['tax_number'] ?? null,
                    'currency_code' => strtoupper($validated['currency_code']),
                    'timezone' => $validated['timezone'],
                    'date_format' => $validated['date_format'] ?? 'Y-m-d',
                    'check_in_time' => $validated['check_in_time'],
                    'check_out_time' => $validated['check_out_time'],
                    'reception_phone' => $validated['reception_phone'] ?? null,
                    'reservation_phone' => $validated['reservation_phone'] ?? null,
                    'contact_email' => $validated['contact_email'] ?? null,
                    'emergency_contact' => $validated['emergency_contact'] ?? null,
                    'whatsapp' => $validated['whatsapp'] ?? null,
                    'contact_address' => $validated['contact_address'] ?? null,
                    'enabled_modules' => ['rooms' => true, 'restaurant' => false],
                    'status' => 'active',
                ]);

                $tenant->update(['hotel_code' => 'HTL-'.str_pad((string) $tenant->id, 6, '0', STR_PAD_LEFT)]);

                $user = User::query()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $validated['admin_name'],
                    'email' => $validated['admin_email'],
                    'username' => $validated['username'],
                    'phone' => $validated['admin_phone'],
                    'password' => $validated['password'],
                    'role' => 'hotel_admin',
                    'status' => 'active',
                ]);

                $subscription = $tenant->subscriptions()->create([
                    'plan_type' => 'monthly',
                    'price' => 0,
                    'currency_code' => strtoupper($validated['currency_code']),
                    'status' => 'trial',
                    'starts_at' => $trialStart,
                    'ends_at' => $trialEnd,
                    'trial_start' => $trialStart,
                    'trial_end' => $trialEnd,
                    'trial_duration_days' => $trialDuration,
                    'auto_renew' => false,
                    'has_rooms' => true,
                    'has_restaurant_pos' => false,
                ]);

                $settings = [
                    'currency_code' => strtoupper($validated['currency_code']),
                    'timezone' => $validated['timezone'],
                    'date_format' => $validated['date_format'] ?? 'Y-m-d',
                    'check_in_time' => $validated['check_in_time'],
                    'check_out_time' => $validated['check_out_time'],
                    'tax' => ['enabled' => false, 'rate' => 0],
                    'invoice' => ['prefix' => 'INV', 'footer' => null],
                    'reservation' => ['default_status' => 'confirmed'],
                    'notifications' => ['email' => true, 'sms' => false],
                ];

                foreach ($settings as $key => $value) {
                    TenantSetting::query()->create([
                        'tenant_id' => $tenant->id,
                        'key' => $key,
                        'value' => is_array($value) ? $value : ['value' => $value],
                    ]);
                }

                return compact('tenant', 'user', 'subscription');
            });
        } catch (Throwable $exception) {
            if ($logoPath !== null) {
                Storage::disk('public')->delete($logoPath);
            }

            Log::error('Hotel registration failed.', [
                'message' => $exception->getMessage(),
                'email' => $validated['admin_email'] ?? null,
            ]);

            return back()->withInput()->with('registration_error', 'We could not create the hotel account. Please review the form and try again.');
        }

        $user = $registration['user'];
        Auth::login($user);
        $request->session()->regenerate();

        Log::info('Hotel account registered.', [
            'tenant_id' => $registration['tenant']->id,
            'hotel_code' => $registration['tenant']->hotel_code,
            'subscription_id' => $registration['subscription']->id,
        ]);

        return view('hotel-admin.auth.registration-success', [
            'tenant' => $registration['tenant'],
            'user' => $user,
            'subscription' => $registration['subscription'],
        ]);
    }

    public function loginForm(): View
    {
        return view('hotel-admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required_without:identifier', 'nullable', 'string', 'max:255'],
            'identifier' => ['required_without:email', 'nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim((string) ($credentials['identifier'] ?? $credentials['email']));
        if ($identifier === '') {
            return back()->withErrors([
                'email' => 'Please provide a valid email or username.',
            ])->onlyInput('email', 'identifier');
        }

        $identityField = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $normalizedRoleValues = array_map(
            static fn (string $role): string => HotelRoleCatalog::normalize($role),
            array_keys(HotelRoleCatalog::definitions())
        );
        $hotelRoles = array_values(array_unique(array_merge($normalizedRoleValues, ['hotel_admin', 'housekeeper', 'cashier', 'admin', 'owner'])));

        $user = User::query()->where($identityField, $identifier)->where('status', 'active')->first();

        if ($user !== null && $user->tenant?->status === 'active' && Hash::check($credentials['password'], $user->password)) {
            if (! in_array(HotelRoleCatalog::normalize((string) $user->role), $hotelRoles, true)) {
                return back()->withErrors([
                    'email' => 'The Hotel Admin credentials are invalid or the account is inactive.',
                ])->onlyInput('email', 'identifier');
            }

            Auth::guard('web')->logout();
            Auth::guard('super_admin')->logout();
            // Invalidate first, then log in, so the authenticated user is stored in the new session.
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()->forget(['hotel_tenant_id', 'url.intended']);
            $request->session()->put('hotel_tenant_id', (int) $user->tenant_id);
            $request->session()->regenerate();

            return redirect()->intended(route('hotel.role.dashboard', [
                'role' => HotelRoleCatalog::normalize((string) $user->role),
            ]));
        }

        return back()->withErrors([
            'email' => 'The Hotel Admin credentials are invalid or the account is inactive.',
        ])->onlyInput('email', 'identifier');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->forget(['hotel_tenant_id', 'url.intended']);

        return redirect()->route('hotel-admin.login');
    }

    private function uniqueSlug(string $hotelName): string
    {
        $baseSlug = Str::slug($hotelName) ?: 'hotel';
        $slug = $baseSlug;
        $suffix = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function countries(): array
    {
        return ['Pakistan', 'United States', 'United Kingdom', 'European Union', 'United Arab Emirates', 'Saudi Arabia', 'India', 'Turkey', 'Bangladesh'];
    }

    private function hotelTypes(): array
    {
        return ['Hotel', 'Resort', 'Guest House', 'Motel', 'Hostel', 'Boutique Hotel', 'Apartment Hotel', 'Bed & Breakfast', 'Other'];
    }

    private function currencies(): array
    {
        return ['PKR' => 'Pakistani Rupee', 'USD' => 'US Dollar', 'GBP' => 'British Pound', 'AED' => 'UAE Dirham', 'EUR' => 'Euro', 'SAR' => 'Saudi Riyal'];
    }

    private function timezones(): array
    {
        return ['Asia/Karachi', 'Asia/Dubai', 'Asia/Kolkata', 'Europe/London', 'Europe/Paris', 'America/New_York', 'America/Los_Angeles', 'UTC'];
    }
}
