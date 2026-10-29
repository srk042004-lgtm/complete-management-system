<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasLedger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SuperAdminLedgerController extends Controller
{
    private const REGION_DOMESTIC = 'domestic';

    private const REGION_INTERNATIONAL = 'international';

    /**
     * Protect ledger data even when this controller is mounted outside its route group.
     */
    public function __construct()
    {
        $this->middleware(['auth:super_admin', 'super-admin']);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'region' => ['nullable', 'in:all,domestic,international'],
            'type' => ['nullable', 'in:all,subscription_base,addon_restaurant'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $region = $filters['region'] ?? 'all';
        $transactionType = $filters['type'] ?? 'all';
        $search = trim((string) ($filters['search'] ?? ''));

        /**
         * Keeps the dashboard cards and table on one consistent read snapshot.
         */
        [$ledger, $summary] = DB::transaction(function () use ($region, $transactionType, $search): array {
            $filteredLedger = $this->filteredLedgerQuery($region, $transactionType, $search);
            $summaryLedger = $this->filteredLedgerQuery($region, 'all', $search)
                ->where('payment_status', 'successful');

            $summary = [
                'total_platform_revenue' => $this->currencyTotals($summaryLedger, 'net_amount'),
                'active_mrr' => $this->currencyTotals(
                    (clone $summaryLedger)
                        ->where('transaction_type', 'subscription_base')
                        ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth()),
                    'net_amount'
                ),
                'addon_revenue' => $this->currencyTotals(
                    (clone $summaryLedger)->where('transaction_type', 'addon_restaurant'),
                    'net_amount'
                ),
            ];

            return [
                $filteredLedger
                    ->with('tenant:id,tenant_name,hotel_name,country')
                    ->latest()
                    ->paginate(20)
                    ->withQueryString(),
                $summary,
            ];
        }, 3);

        return view('super-admin.billing', compact('ledger', 'summary', 'region', 'transactionType', 'search'));
    }

    private function filteredLedgerQuery(string $region, string $transactionType, string $search): Builder
    {
        $query = SaasLedger::query();

        if ($region === self::REGION_DOMESTIC) {
            $query->where('currency_code', 'PKR');
        }

        if ($region === self::REGION_INTERNATIONAL) {
            $query->where('currency_code', '!=', 'PKR');
        }

        if ($transactionType !== 'all') {
            $query->where('transaction_type', $transactionType);
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function (Builder $tenantQuery) use ($search): void {
                        $tenantQuery->where('hotel_name', 'like', "%{$search}%")
                            ->orWhere('tenant_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function currencyTotals(Builder $query, string $amountColumn): string
    {
        $totals = $query
            ->select('currency_code', DB::raw("SUM({$amountColumn}) as total"))
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get();

        if ($totals->isEmpty()) {
            return '-';
        }

        return $totals
            ->map(fn (SaasLedger $ledger): string => $this->formatCurrency((float) $ledger->total, $ledger->currency_code))
            ->implode(' / ');
    }

    private function formatCurrency(float $amount, string $currencyCode): string
    {
        $symbol = match ($currencyCode) {
            'PKR' => 'Rs. ',
            'USD' => '$',
            'GBP' => "\u{00A3}",
            'AED' => 'AED ',
            default => "{$currencyCode} ",
        };

        return $symbol.number_format($amount, 2);
    }
}
