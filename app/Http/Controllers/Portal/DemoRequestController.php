<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\TrialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DemoRequestController extends Controller
{
    private const SYSTEMS = ['hotel', 'clinic'];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'in:'.implode(',', self::SYSTEMS)],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'already_using' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        $trialAccount = DB::transaction(function () use ($data): TrialAccount {
            $demoRequest = DemoRequest::query()->create([
                ...$data,
                'business_type' => ucfirst($data['business_type']),
            ]);

            return TrialAccount::query()->create([
                'demo_request_id' => $demoRequest->id,
                'system' => $data['business_type'],
                'business_name' => $data['business_name'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'source' => 'demo_request',
                'status' => TrialAccount::STATUS_PENDING,
                'metadata' => [
                    'city' => $data['city'],
                    'address' => $data['address'],
                    'already_using' => $data['already_using'] ?? null,
                ],
            ]);
        });

        return redirect()->route('industry.register', [
            'system' => $trialAccount->system,
            'trial_account' => $trialAccount->id,
        ])->withInput($data + ['account_role' => 'owner'])
            ->with('demo_success', 'Your demo request is saved. Complete account setup to open the '.ucfirst($trialAccount->system).' system with '.config('saas.trial_duration_days', 10).' days of trial access.');
    }
}
