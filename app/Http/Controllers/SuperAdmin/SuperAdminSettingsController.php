<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminSetting;
use App\Support\SuperAdminActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminSettingsController extends Controller
{
    public function index(): View
    {
        $settings = SuperAdminSetting::query()->pluck('value', 'key');

        return view('super-admin.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'default_currency' => ['required', 'string', 'size:3'],
            'trial_duration_days' => ['required', 'integer', 'min:0', 'max:365'],
            'registration_enabled' => ['nullable', 'boolean'],
            'approval_required' => ['nullable', 'boolean'],
        ]);

        foreach ($validated as $key => $value) {
            SuperAdminSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => ['value' => $value]],
            );
        }

        SuperAdminActivity::log('settings.updated', null, 'Platform settings were updated.');

        return back()->with('success', 'Platform settings saved.');
    }
}
