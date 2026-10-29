<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Saves the signed-in user's light/dark choice so it follows them to any device. */
class ThemePreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate(['theme' => ['required', 'in:light,dark']]);

        $user = $request->user() ?? $request->user('super_admin');
        abort_if($user === null, 401);

        $user->forceFill(['theme_preference' => $validated['theme']])->save();

        return response()->json(['theme' => $validated['theme']]);
    }
}
