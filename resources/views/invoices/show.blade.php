<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 24px;
            background: #fff;
        }

        .invoice {
            max-width: 210mm;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            padding: 24px;
            box-sizing: border-box;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .muted {
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th, td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 0;
            text-align: left;
        }

        .totals {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .signature-box {
            width: 45%;
            border-top: 1px solid #111827;
            padding-top: 8px;
            text-align: center;
        }

        @media print {
            .navbar,
            .sidebar,
            .btn,
            form[action*="post"],
            form[action*="payment"],
            form[action*="folio"] {
                display: none !important;
            }

            body {
                margin: 0;
                padding: 0;
                background: #fff;
                font-size: 11pt;
            }

            @page {
                size: A4;
                margin: 12mm;
            }

            .invoice-container,
            .invoice {
                width: 100% !important;
                max-width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                margin: 0;
                padding: 0;
            }

            table tr {
                page-break-inside: avoid;
            }

            .signatures {
                display: flex !important;
                position: fixed;
                bottom: 12mm;
                left: 12mm;
                right: 12mm;
                gap: 16mm;
                margin-top: 0;
            }

            .signature-box {
                flex: 1;
                border-top: 1px solid #111827;
                padding-top: 8px;
                text-align: center;
                font-size: 10pt;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
    @include('partials.slate-teal-theme')
</head>
<body>
    <div class="invoice">
        <div class="header">
            <div>
                <h2>Guest Invoice</h2>
                <p class="muted">Reservation #{{ $reservation->id }}</p>
                <p><strong>Guest:</strong> {{ $reservation->guest_name }}</p>
                <p><strong>Room:</strong> {{ $reservation->room->number ?? 'N/A' }}</p>
            </div>
            <div class="muted">
                <p>{{ now()->format('F j, Y') }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ledgerEntries as $entry)
                    <tr>
                        <td>{{ $entry->description }}</td>
                        <td>{{ ucfirst($entry->entry_type) }}</td>
                        <td>{{ number_format($entry->amount_cents / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <strong>Total Due: {{ number_format($ledgerEntries->sum('amount_cents') / 100, 2) }}</strong>
        </div>

        <div class="signatures">
            <div class="signature-box">Guest Signature</div>
            <div class="signature-box">Cashier Signature</div>
        </div>
    </div>
</body>
</html>
