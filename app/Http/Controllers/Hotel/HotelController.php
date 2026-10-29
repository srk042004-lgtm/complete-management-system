<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class HotelController extends Controller
{
    public function create(): View
    {
        return view('hotel.register', [
            'countries' => $this->countryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:tenants,owner_email'],
            'country' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,suspended,trial'],
        ]);

        try {
            Tenant::query()->create([
                'tenant_name' => $validated['tenant_name'],
                'owner_email' => $validated['owner_email'],
                'country' => $validated['country'],
                'status' => $validated['status'] ?? 'active',
            ]);

            return redirect()->route('hotel.register')->with('success', 'Hotel registered successfully.');
        } catch (Throwable $exception) {
            Log::error('Failed to register hotel tenant.', [
                'exception' => $exception,
                'payload' => $validated,
            ]);

            throw $exception;
        }
    }

    private function countryOptions(): array
    {
        return [
            'Pakistan',
            'United States',
            'United Kingdom',
            'European Union',
            'United Arab Emirates',
            'Saudi Arabia',
            'India',
            'Turkey',
            'Bangladesh',
        ];
    }
}
