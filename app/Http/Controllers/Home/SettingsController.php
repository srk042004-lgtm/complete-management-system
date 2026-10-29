<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Support\HotelFeatureCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant !== null, 403);

        $features = HotelFeatureCatalog::defaults($tenant->hotel_type, $tenant->total_rooms);
        foreach (HotelFeatureCatalog::configurableKeys() as $feature) {
            $features[$feature] = $tenant->hasFeature($feature);
        }

        return view('home.settings', [
            'tenant' => $tenant,
            'features' => $features,
            'featureGroups' => HotelFeatureCatalog::configurable(),
            'comingSoonGroups' => HotelFeatureCatalog::comingSoon(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'features_present' => ['required', 'accepted'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::in(HotelFeatureCatalog::optionalKeys())],
        ]);

        $tenant = $request->user()->tenant;
        abort_unless($tenant !== null, 403);

        $modules = array_merge(
            HotelFeatureCatalog::defaults($tenant->hotel_type, $tenant->total_rooms),
            $tenant->moduleConfiguration(),
        );
        $selected = $validated['features'] ?? [];

        foreach (HotelFeatureCatalog::optionalKeys() as $feature) {
            $modules[$feature] = in_array($feature, $selected, true);
        }

        // Restaurant POS is only meaningful when the restaurant module is enabled.
        $modules['pos'] = $modules['restaurant'] && $modules['pos'];
        $modules['restaurant'] = $modules['pos'] || $modules['restaurant'];

        $tenant->enabled_modules = $modules;
        $tenant->save();

        $tenant->subscriptions()->latest('id')->first()?->update([
            'has_rooms' => $tenant->hasFeature('rooms'),
            'has_restaurant_pos' => $tenant->hasFeature('pos'),
        ]);

        return back()->with('success', 'Hotel feature configuration saved. Navigation and access rules are updated.');
    }
}
