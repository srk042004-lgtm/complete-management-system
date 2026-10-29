<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function show(Reservation $reservation): View
    {
        abort_unless(
            $reservation->room?->tenant_id === request()->user()->tenant_id,
            404
        );

        $ledgerEntries = $reservation->ledgerEntries()->orderBy('created_at')->get();

        return view('invoices.show', [
            'reservation' => $reservation,
            'ledgerEntries' => $ledgerEntries,
        ]);
    }
}
