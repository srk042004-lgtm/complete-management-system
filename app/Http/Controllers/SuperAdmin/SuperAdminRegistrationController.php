<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SuperAdminRegistrationController extends Controller
{
    public function create(): View
    {
        return view('super-admin.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => [
                'required',
                'string',
                'min:12',
                'max:255',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                function (string $attribute, mixed $value, Closure $fail) {
                    if (preg_match('/^\d+$/', (string) $value)) {
                        $fail('The password must contain letters and symbols, not only numbers.');
                    }

                    if (preg_match('/^(?:0{4,}|1{4,}|2{4,}|3{4,}|4{4,}|5{4,}|6{4,}|7{4,}|8{4,}|9{4,}|0000|1111|1122|1234|123456|6789|7890)$/', (string) $value)) {
                        $fail('The password must not use repeated or common numeric sequences like 0000, 1111, 1122, 1234, or 6789.');
                    }
                },
            ],
            'password_confirmation' => ['required', 'string'],
            'authorization_pin' => ['required', 'digits:6'],
            'saas_platform_name' => ['required', 'string', 'max:255'],
        ]);

        SuperAdmin::updateOrCreate(
            ['email' => $request->email],
            [
                'name' => trim($request->first_name.' '.$request->last_name),
                'fname' => $request->last_name,
                'email' => $request->email,
                'number' => $request->phone,
                'password' => Hash::make($request->password),
                'address' => $request->saas_platform_name.' | Pakistan',
                'is_active' => true,
            ]
        );

        return redirect()->route('login')->with('status', 'Super admin account secured successfully.');
    }
}
