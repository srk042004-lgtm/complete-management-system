<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\View\View;

class SuperAdminSubscriptionsController extends Controller
{
    public function index(): View
    {
        $subscriptions = Subscription::query()->with('tenant')->latest('created_at')->paginate(10);

        return view('super-admin.subscriptions', compact('subscriptions'));
    }
}
