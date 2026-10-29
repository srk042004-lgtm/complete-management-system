<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\CashShift;
use App\Models\HotelOperationLog;
use App\Models\ShiftExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Petty cash paid out of the receptionist's drawer during an open shift. */
class ShiftExpenseController extends Controller
{
    public function store(Request $request, CashShift $shift): RedirectResponse
    {
        $actor = $request->user();
        abort_unless((int) $shift->tenant_id === (int) $actor->tenant_id, 404);
        abort_unless((int) $shift->opened_by === (int) $actor->id, 403, 'Only the receptionist who opened this shift can log its expenses.');
        abort_unless($shift->status === CashShift::STATUS_OPEN, 409, 'This shift is already closed.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'category' => ['required', Rule::in(array_keys(ShiftExpense::CATEGORIES))],
            'description' => ['required', 'string', 'max:255'],
            'receipt' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ]);

        $shift->recalculate();
        $amount = round((float) $validated['amount'], 2);
        if ($amount > round((float) $shift->expected_cash, 2) + 0.009) {
            throw ValidationException::withMessages(['amount' => 'This expense is more than the cash in the drawer (PKR '.number_format((float) $shift->expected_cash, 2).').']);
        }

        $receiptPath = $request->file('receipt')?->store('shift-receipts/'.$actor->tenant_id, 'public');

        DB::transaction(function () use ($request, $actor, $shift, $validated, $amount, $receiptPath): void {
            $expense = ShiftExpense::query()->create([
                'tenant_id' => $actor->tenant_id,
                'cash_shift_id' => $shift->id,
                'user_id' => $actor->id,
                'amount' => $amount,
                'category' => $validated['category'],
                'description' => $validated['description'],
                'receipt_path' => $receiptPath,
            ]);

            HotelOperationLog::query()->create([
                'tenant_id' => $actor->tenant_id,
                'actor_id' => $actor->id,
                'action' => 'shift.expense_added',
                'subject_type' => ShiftExpense::class,
                'subject_id' => $expense->id,
                'details' => ['amount' => $amount, 'category' => $validated['category']],
                'ip_address' => $request->ip(),
            ]);
        });

        $shift->recalculate();

        return redirect()->route('frontdesk.shift')->with('success', 'Expense recorded. The drawer is now PKR '.number_format((float) $shift->expected_cash, 2).' expected.');
    }
}
