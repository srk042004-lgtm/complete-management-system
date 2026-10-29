<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SuperAdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->startOfMonth();
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();

        $reports = [
            'hotels' => [
                'total' => Tenant::query()->count(),
                'new' => Tenant::query()->whereBetween('created_at', [$from, $to])->count(),
                'active' => Tenant::query()->where('status', 'active')->count(),
                'pending' => Tenant::query()->where('status', 'pending')->count(),
                'suspended' => Tenant::query()->where('status', 'suspended')->count(),
            ],
            'subscriptions' => [
                'active' => Subscription::query()->where('status', 'active')->count(),
                'trial' => Subscription::query()->where('status', 'trial')->count(),
                'expired' => Subscription::query()->where(function ($query): void {
                    $query->where('status', 'expired')->orWhere(fn ($nested) => $nested->whereNotNull('ends_at')->where('ends_at', '<', now()));
                })->count(),
                'cancelled' => Subscription::query()->where('status', 'cancelled')->count(),
            ],
            'payments' => [
                'paid' => SaasPayment::query()->where('payment_status', 'paid')->whereBetween('paid_at', [$from, $to])->count(),
                'pending' => SaasPayment::query()->where('payment_status', 'pending')->whereBetween('created_at', [$from, $to])->count(),
                'failed' => SaasPayment::query()->where('payment_status', 'failed')->whereBetween('created_at', [$from, $to])->count(),
                'refunded' => SaasPayment::query()->where('payment_status', 'refunded')->whereBetween('created_at', [$from, $to])->count(),
            ],
            'revenue' => SaasPayment::query()
                ->where('payment_status', 'paid')
                ->whereBetween('paid_at', [$from, $to])
                ->select('currency_code', DB::raw('SUM(amount) as total'))
                ->groupBy('currency_code')
                ->orderBy('currency_code')
                ->get(),
            'users' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('status', 'active')->count(),
                'by_role' => User::query()->select('role', DB::raw('COUNT(*) as total'))->groupBy('role')->orderBy('role')->get(),
            ],
        ];

        return view('super-admin.reports', compact('reports', 'from', 'to'));
    }
}
