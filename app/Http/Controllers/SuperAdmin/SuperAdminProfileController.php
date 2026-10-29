<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SuperAdminProfileController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('super_admin')->user();

        return view('super-admin.profile', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::guard('super_admin')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:super_admins,email,' . $user->id],
            'number' => ['nullable', 'string', 'max:255'],
            'cnic' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $validated['profile_picture'] = $path;

            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }
        }

        $user->fill($validated);
        $user->save();

        return redirect()->route('super-admin.profile')->with('success', 'Profile updated successfully.');
    }
}
