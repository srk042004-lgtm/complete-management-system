<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class SuperAdminHotelsController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()->latest('id')->paginate(10);

        return view('super-admin.hotels', compact('tenants'));
    }
}
