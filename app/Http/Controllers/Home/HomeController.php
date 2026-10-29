<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Support\HotelRoleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('hotel.role.dashboard', [
            'role' => HotelRoleCatalog::normalize((string) $request->user()->role),
        ]);
    }
}
