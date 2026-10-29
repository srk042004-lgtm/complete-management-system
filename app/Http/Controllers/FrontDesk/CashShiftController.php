<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\CashShift;
use App\Models\CashTransfer;
use App\Models\HotelPayment;
use App\Models\ShiftExpense;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashShiftController extends Controller
{
    private const HANDOVER_ROLES = ['receptionist', 'cashier'];

    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;

        $openShift = CashShift::query()
            ->where('tenant_id', $tenantId)
            ->open()
            ->with('opener:id,name')
            ->latest('opened_at')
            ->first();
        $openShift?->recalculate();

        $history = CashShift::query()
            ->where('tenant_id', $tenantId)
            ->where('status', CashShift::STATUS_CLOSED)
            ->with(['opener:id,name', 'closer:id,name', 'handoverTo:id,name'])
            ->latest('closed_at')
            ->limit(20)
            ->get();

        $recentCashPayments = $openShift
            ? HotelPayment::query()->where('cash_shift_id', $openShift->id)->with('booking:id,booking_code')->latest('created_at')->limit(15)->get()
            : collect();

        return view('frontdesk.shift', [
            'openShift' => $openShift,
            'history' => $history,
            'recentCashPayments' => $recentCashPayments,
            'tenant' => $request->user()->tenant,
            'suggestion' => $this->openingSuggestion($tenantId, $request->user()),
            'handoverOptions' => $this->handoverOptions($tenantId, $request->user()),
            'openShiftExpenses' => $openShift
                ? ShiftExpense::query()->where('cash_shift_id', $openShift->id)->with('user:id,name')->latest('id')->get()
                : collect(),
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_float' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $tenantId = (int) $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId, $validated): void {
            $alreadyOpen = CashShift::query()->where('tenant_id', $tenantId)->open()->lockForUpdate()->exists();
            abort_if($alreadyOpen, 409, 'A cash shift is already open for this hotel.');

            $suggestion = $this->openingSuggestion($tenantId, $request->user());
            $amount = isset($validated['opening_float']) && $validated['opening_float'] !== ''
                ? round((float) $validated['opening_float'], 2)
                : ($suggestion['amount'] ?? null);

            if ($amount === null) {
                throw ValidationException::withMessages(['opening_float' => 'Enter the opening float you received.']);
            }

            // Money handed over or retained from the last vault drop is linked to this shift, so it is only used once.
            $linked = $suggestion !== null && round((float) $suggestion['amount'], 2) === $amount ? $suggestion : null;

            $shift = CashShift::query()->create([
                'tenant_id' => $tenantId,
                'opened_by' => $request->user()->id,
                'opening_float' => $amount,
                'expected_cash' => $amount,
                'opening_source' => $linked ? $linked['source'] : 'manual',
                'retained_from_transfer_id' => $linked ? $linked['transfer']->id : null,
                'status' => CashShift::STATUS_OPEN,
                'opened_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($linked) {
                $transfer = $linked['transfer'];
                $transfer->forceFill([
                    'consumed_by_shift_id' => $shift->id,
                    // A handover is accepted by the receptionist who opens the next shift with that cash.
                    'status' => $transfer->transfer_type === 'shift_handover' ? 'verified' : $transfer->status,
                    'verified_at' => $transfer->transfer_type === 'shift_handover' ? now() : $transfer->verified_at,
                ])->save();
            }
        });

        return redirect()->route('frontdesk.shift')->with('success', 'Cash shift opened.');
    }

    /**
     * Close the shift in one step: count the drawer, keep a morning float, and send the rest to the owner or admin.
     * If the drawer is handed to another receptionist instead, nothing is sent to the vault.
     */
    public function close(Request $request, CashShift $shift): RedirectResponse
    {
        $actor = $request->user();
        abort_unless((int) $shift->tenant_id === (int) $actor->tenant_id, 404);
        abort_unless($shift->status === CashShift::STATUS_OPEN, 409, 'This shift is already closed.');

        $validated = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'retained_float' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'handover_to_id' => ['nullable', Rule::in($this->handoverOptions((int) $actor->tenant_id, $actor)->pluck('id')->all())],
            'payment_method' => ['nullable', Rule::in(array_keys(CashTransfer::PAYMENT_METHODS))],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $shift->recalculate();
        $counted = round((float) $validated['counted_cash'], 2);
        $handoverTo = $validated['handover_to_id'] ?? null;
        $paymentMethod = $validated['payment_method'] ?? 'physical_cash';
        $reference = filled($validated['transaction_reference'] ?? null) ? trim((string) $validated['transaction_reference']) : null;
        $retained = $handoverTo ? 0.0 : round((float) ($validated['retained_float'] ?? 0), 2);
        $variance = round($counted - (float) $shift->expected_cash, 2);

        if ($retained > $counted + 0.009) {
            throw ValidationException::withMessages(['retained_float' => 'The float kept for the morning cannot be more than the cash counted.']);
        }
        if (abs($variance) > 0.009 && blank($validated['notes'] ?? null)) {
            throw ValidationException::withMessages(['notes' => 'The counted cash differs from the expected amount. Explain the difference in the notes.']);
        }

        $sendToOwner = round($counted - $retained, 2);

        if (! $handoverTo && $sendToOwner > 0.009 && $paymentMethod !== 'physical_cash') {
            if ($reference === null) {
                throw ValidationException::withMessages(['transaction_reference' => 'Enter the transaction ID or reference for this payment.']);
            }
            $duplicate = CashTransfer::query()->where('tenant_id', $actor->tenant_id)->where('transaction_reference', $reference)->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['transaction_reference' => 'This transaction ID has already been recorded for this hotel.']);
            }
        }

        $proofPath = $request->file('payment_proof')?->store('transfer-proofs/'.$actor->tenant_id, 'public');

        DB::transaction(function () use ($actor, $shift, $validated, $counted, $variance, $handoverTo, $retained, $sendToOwner, $paymentMethod, $reference, $proofPath): void {
            $shift->update([
                'status' => CashShift::STATUS_CLOSED,
                'closed_by' => $actor->id,
                'handover_to_id' => $handoverTo,
                'counted_cash' => $counted,
                'variance' => $variance,
                'closed_at' => now(),
                'notes' => $validated['notes'] ?? $shift->notes,
            ]);

            if ($handoverTo) {
                // Day-time change: the next receptionist accepts the whole drawer when they open their shift.
                CashTransfer::query()->create([
                    'tenant_id' => $actor->tenant_id,
                    'cash_shift_id' => $shift->id,
                    'sender_id' => $actor->id,
                    'receiver_id' => $handoverTo,
                    'amount' => $counted,
                    'retained_float' => 0,
                    'transfer_type' => 'shift_handover',
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);
            } elseif ($sendToOwner > 0.009) {
                // End of day: the rest goes to the owner or admin, who must approve it before it counts as revenue.
                CashTransfer::query()->create([
                    'tenant_id' => $actor->tenant_id,
                    'cash_shift_id' => $shift->id,
                    'sender_id' => $actor->id,
                    'receiver_id' => null,
                    'amount' => $sendToOwner,
                    'retained_float' => $retained,
                    'transfer_type' => 'vault_drop',
                    'payment_method' => $paymentMethod,
                    'transaction_reference' => $reference,
                    'proof_image_path' => $proofPath,
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        });

        $message = $handoverTo
            ? 'Shift closed. The drawer is handed over and waits for the next receptionist to accept it.'
            : ($sendToOwner > 0.009
                ? 'Shift closed. PKR '.number_format($sendToOwner, 2).' is waiting for the owner or admin to approve.'
                : 'Shift closed and reconciled.');

        return redirect()->route('frontdesk.shift')->with('success', $message);
    }

    /**
     * Cash the next shift should start with: a handover addressed to this receptionist first,
     * otherwise the float retained from the latest vault drop that nobody has used yet.
     *
     * @return array{amount: float, source: string, transfer: CashTransfer}|null
     */
    private function openingSuggestion(int $tenantId, User $user): ?array
    {
        $handover = CashTransfer::query()
            ->where('tenant_id', $tenantId)
            ->where('transfer_type', 'shift_handover')
            ->where('status', 'pending')
            ->where('receiver_id', $user->id)
            ->whereNull('consumed_by_shift_id')
            ->latest('id')
            ->first();
        if ($handover) {
            return ['amount' => (float) $handover->amount, 'source' => 'handover', 'transfer' => $handover];
        }

        $retained = CashTransfer::query()
            ->where('tenant_id', $tenantId)
            ->where('transfer_type', 'vault_drop')
            ->where('retained_float', '>', 0)
            ->whereNull('consumed_by_shift_id')
            ->latest('id')
            ->first();
        if ($retained) {
            return ['amount' => (float) $retained->retained_float, 'source' => 'vault_retained', 'transfer' => $retained];
        }

        return null;
    }

    private function handoverOptions(int $tenantId, User $actor)
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', self::HANDOVER_ROLES)
            ->where('status', 'active')
            ->where('id', '!=', $actor->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
