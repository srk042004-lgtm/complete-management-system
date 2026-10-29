<?php

namespace App\Http\Controllers;

use App\Models\SaasLedgerEntry;
use App\Models\SaasTeamSalary;
use App\Models\Tenant;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminFinancialController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $month = $request->input('month', $now->format('Y-m'));
        [$yearFilter, $monthFilter] = explode('-', $month . '-01');

        $monthlyRevenue = SaasLedgerEntry::where('status', 'completed')
            ->whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)
            ->sum('amount');

        $annualRevenue = SaasLedgerEntry::where('status', 'completed')
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        $monthlyPayroll = SaasTeamSalary::where('status', 'paid')
            ->whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)
            ->sum('net_salary');

        $annualPayroll = SaasTeamSalary::where('status', 'paid')
            ->whereYear('payment_date', $now->year)
            ->sum('net_salary');

        $monthlyNet = $monthlyRevenue - $monthlyPayroll;
        $annualNet = $annualRevenue - $annualPayroll;

        // Prepare last 12 months data
        $labels = [];
        $revenueSeries = [];
        $payrollSeries = [];

        for ($i = 11; $i >= 0; $i--) {
            $dt = $now->copy()->subMonths($i);
            $labels[] = $dt->format('Y-m');

            $rev = SaasLedgerEntry::where('status', 'completed')
                ->whereYear('payment_date', $dt->year)
                ->whereMonth('payment_date', $dt->month)
                ->sum('amount');

            $pay = SaasTeamSalary::where('status', 'paid')
                ->whereYear('payment_date', $dt->year)
                ->whereMonth('payment_date', $dt->month)
                ->sum('net_salary');

            $revenueSeries[] = (float) $rev;
            $payrollSeries[] = (float) $pay;
        }

        $ledgerQuery = SaasLedgerEntry::query()->when($request->input('hotel'), function ($q, $hotel) {
            $q->whereHas('tenant', function ($t) use ($hotel) {
                $t->where('tenant_name', 'like', "%{$hotel}%");
            });
        })->orderByDesc('payment_date');

        $salaryQuery = SaasTeamSalary::query()->when($request->input('employee'), function ($q, $employee) {
            $q->where('employee_name', 'like', "%{$employee}%");
        })->orderByDesc('payment_date');

        $ledgerEntries = $ledgerQuery->paginate(15)->withQueryString();
        $salaries = $salaryQuery->paginate(15)->withQueryString();

        $tenants = Tenant::query()->paginate(20);

        return view('super-admin.financials', compact(
            'monthlyRevenue','annualRevenue','monthlyPayroll','annualPayroll','monthlyNet','annualNet','labels','revenueSeries','payrollSeries','tenants','ledgerEntries','salaries'
        ));
    }

    public function storeTenantPayment(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'title' => 'required|string',
            'type' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'payment_method' => 'nullable|string',
            'transaction_ref' => 'nullable|string',
            'payment_date' => 'required|date',
            'status' => 'required|string',
            'extend_days' => 'nullable|integer',
        ]);

        $entry = SaasLedgerEntry::create([
            'tenant_id' => $data['tenant_id'],
            'subscription_id' => $data['subscription_id'] ?? null,
            'title' => $data['title'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'payment_method' => $data['payment_method'] ?? null,
            'transaction_ref' => $data['transaction_ref'] ?? null,
            'payment_date' => $data['payment_date'],
            'status' => $data['status'],
            'notes' => $request->input('notes'),
        ]);

        // If payment completed and subscription provided, extend subscription
        if ($entry->status === 'completed' && $entry->subscription_id) {
            $subscription = Subscription::find($entry->subscription_id);
            if ($subscription) {
                $days = (int) ($request->input('extend_days', 30));
                $subscription->ends_at = $subscription->ends_at ? $subscription->ends_at->addDays($days) : now()->addDays($days);
                $subscription->save();
            }
        }

        return redirect()->back()->with('success', 'Tenant payment recorded.');
    }

    public function storeSalaryPayment(Request $request)
    {
        $data = $request->validate([
            'employee_name' => 'required|string',
            'designation' => 'nullable|string',
            'base_salary' => 'required|numeric',
            'bonus_allowances' => 'nullable|numeric',
            'deductions' => 'nullable|numeric',
            'net_salary' => 'required|numeric',
            'payment_month' => 'required|string',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'status' => 'required|string',
        ]);

        SaasTeamSalary::create($data + ['notes' => $request->input('notes')]);

        return redirect()->back()->with('success', 'Salary payment recorded.');
    }
}
