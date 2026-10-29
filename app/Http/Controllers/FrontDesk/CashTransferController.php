<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\CashShift;
use App\Models\CashTransfer;
use App\Models\ShiftExpense;
use App\Models\HotelOperationLog;
use App\Models\User;
use App\Support\HotelRoleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Night vault drop and owner/admin verification.
 *
 * Money in a reception drawer is "unverified drawer cash". It only becomes "verified net revenue"
 * after an owner or admin approves the vault drop that carried it.
 */
class CashTransferController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        $currency = $request->user()->tenant?->currency_code ?? 'PKR';

        $unverifiedDrawerCash = round(
            (float) CashShift::query()->where('tenant_id', $tenantId)->where('status', 'open')->sum('expected_cash')
            + (float) CashTransfer::query()->where('tenant_id', $tenantId)->where('transfer_type', 'vault_drop')->where('status', 'pending')->sum('amount'),
            2
        );
        $verifiedNetRevenue = round((float) CashTransfer::query()->where('tenant_id', $tenantId)->where('transfer_type', 'vault_drop')->where('status', 'verified')->sum('amount'), 2);

        $pending = CashTransfer::query()
            ->where('tenant_id', $tenantId)
            ->where('transfer_type', 'vault_drop')
            ->where('status', 'pending')
            ->with(['sender:id,name', 'receiver:id,name', 'shift:id,opened_at,closed_at'])
            ->oldest('id')
            ->get();

        $history = CashTransfer::query()
            ->where('tenant_id', $tenantId)
            ->where('transfer_type', 'vault_drop')
            ->whereIn('status', ['verified', 'rejected'])
            ->with(['sender:id,name', 'receiver:id,name'])
            ->latest('verified_at')
            ->limit(15)
            ->get();

        $expenses = ShiftExpense::query()
            ->where('tenant_id', $tenantId)
            ->with(['user:id,name', 'shift:id,opened_at'])
            ->latest('id')
            ->limit(25)
            ->get();

        $expensesThisMonth = round((float) ShiftExpense::query()
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount'), 2);

        $shortages = CashShift::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'closed')
            ->where('variance', '<', -0.009)
            ->whereNull('shortage_acknowledged_at')
            ->with(['opener:id,name', 'closer:id,name'])
            ->latest('closed_at')
            ->get();

        return view('admin.cash-verification', compact('unverifiedDrawerCash', 'verifiedNetRevenue', 'pending', 'history', 'currency', 'expenses', 'expensesThisMonth', 'shortages'));
    }

    /** Owners and admins acknowledge a shortage so it stops showing as an open alert. */
    public function acknowledgeShortage(Request $request, CashShift $shift): RedirectResponse
    {
        $actor = $request->user();
        abort_unless((int) $shift->tenant_id === (int) $actor->tenant_id, 404);
        abort_unless((float) $shift->variance < -0.009, 422, 'This shift has no cash shortage.');

        $shift->forceFill(['shortage_acknowledged_at' => now()])->save();

        HotelOperationLog::query()->create([
            'tenant_id' => $actor->tenant_id,
            'actor_id' => $actor->id,
            'action' => 'cash.shortage_acknowledged',
            'subject_type' => CashShift::class,
            'subject_id' => $shift->id,
            'details' => ['variance' => $shift->variance],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Shortage acknowledged.');
    }

    public function approve(Request $request, CashTransfer $transfer): RedirectResponse
    {
        return $this->decide($request, $transfer, 'verified');
    }

    public function reject(Request $request, CashTransfer $transfer): RedirectResponse
    {
        return $this->decide($request, $transfer, 'rejected');
    }

    private function decide(Request $request, CashTransfer $transfer, string $decision): RedirectResponse
    {
        $actor = $request->user();
        abort_unless((int) $transfer->tenant_id === (int) $actor->tenant_id, 404);
        abort_unless(in_array(HotelRoleCatalog::normalize((string) $actor->role), ['owner', 'admin'], true), 403);
        abort_unless($transfer->status === 'pending', 409, 'This transfer has already been decided.');

        $transfer->forceFill([
            'status' => $decision,
            'receiver_id' => $decision === 'verified' ? $actor->id : $transfer->receiver_id,
            'verified_at' => now(),
        ])->save();

        $this->log($actor, $decision === 'verified' ? 'cash.verified' : 'cash.rejected', $transfer, ['amount' => $transfer->amount]);

        return back()->with('success', $decision === 'verified'
            ? 'Cash received and added to the verified net revenue ledger.'
            : 'Vault drop rejected. The cash stays unverified until it is recounted.');
    }

    private function receivers(int $tenantId)
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', ['owner', 'admin', 'hotel_admin'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function log(User $actor, string $action, CashTransfer $transfer, array $details): void
    {
        HotelOperationLog::query()->create([
            'tenant_id' => $actor->tenant_id,
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => CashTransfer::class,
            'subject_id' => $transfer->id,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
