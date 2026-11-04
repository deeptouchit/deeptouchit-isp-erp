<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Receipt #{{ $payment->invoice_no ?: $payment->id }} - {{ $tenant->company_name ?? 'ISP' }}</title>
    
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 8px;
            background-color: #fff;
            color: #000;
            font-size: 12px;
            line-height: 1.25;
            max-width: 320px; /* 58mm to 80mm standard width */
            margin-left: auto;
            margin-right: auto;
        }

        .no-print {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .btn {
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .btn-primary { background: #0891b2; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .company-header {
            text-align: center;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: -0.3px;
        }
        .company-sub {
            font-size: 10px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-divider {
            border-top: 1px double #000;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .footer-note {
            text-align: center;
            font-size: 10px;
            margin-top: 8px;
        }

        @media print {
            .no-print { display: none !important; }
            body {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                font-size: 11.5px;
            }
            @page {
                size: 80mm auto;
                margin: 0mm;
            }
        }
    </style>
</head>
<body>

    {{-- Control Toolbar (Screen Only) --}}
    <div class="no-print">
        <span class="bold" style="font-size: 11px;">POS Thermal Slip</span>
        <div style="display: flex; gap: 6px;">
            <button onclick="window.print()" class="btn btn-primary">
                Print Slip
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                Close
            </button>
        </div>
    </div>

    {{-- Slip Content --}}
    <div class="company-header">
        <div class="company-name">{{ $tenant->company_name ?? $tenant->name ?? 'ISP BROADBAND' }}</div>
        @if(!empty($tenant->address))
            <div class="company-sub">{{ $tenant->address }}</div>
        @endif
        @if(!empty($tenant->phone))
            <div class="company-sub">Hotline: {{ $tenant->phone }}</div>
        @endif
        <div class="bold" style="font-size: 11px; margin-top: 4px;">MONEY RECEIPT (CUSTOMER COPY)</div>
    </div>

    <div class="divider"></div>

    <div class="row">
        <span>Receipt No:</span>
        <span class="bold">{{ $payment->invoice_no ?: ('REC-' . $payment->id) }}</span>
    </div>
    <div class="row">
        <span>Date & Time:</span>
        <span>{{ \Carbon\Carbon::parse($payment->paid_at ?: $payment->created_at)->format('d-M-Y h:i A') }}</span>
    </div>
    <div class="row">
        <span>Customer ID:</span>
        <span class="bold">{{ $payment->customer?->customer_id ?: $payment->customer?->username }}</span>
    </div>
    <div class="row">
        <span>Subscriber:</span>
        <span class="bold">{{ $payment->customer?->name ?: $payment->customer?->username }}</span>
    </div>
    <div class="row">
        <span>Username:</span>
        <span>{{ $payment->customer?->username }}</span>
    </div>
    <div class="row">
        <span>Mobile:</span>
        <span>{{ $payment->customer?->phone ?: '—' }}</span>
    </div>
    <div class="row">
        <span>Package:</span>
        <span>{{ $payment->customer?->package_display_name }}</span>
    </div>

    <div class="divider"></div>

    <div class="row bold" style="font-size: 13px;">
        <span>Amount Paid:</span>
        <span>Tk {{ number_format((float)$payment->amount, 2) }}</span>
    </div>

    @if((float)$payment->discount > 0)
        <div class="row">
            <span>Discount/Waiver:</span>
            <span>Tk {{ number_format((float)$payment->discount, 2) }}</span>
        </div>
    @endif

    <div class="row">
        <span>Payment Method:</span>
        <span class="uppercase">{{ $payment->payment_method }}</span>
    </div>

    @if(!empty($payment->transaction_id))
        <div class="row">
            <span>Trx ID:</span>
            <span>{{ $payment->transaction_id }}</span>
        </div>
    @endif

    <div class="divider"></div>

    <div class="row">
        <span>Remaining Due:</span>
        <span class="bold">Tk {{ number_format((float)($payment->customer?->due_amount ?? 0), 2) }}</span>
    </div>
    <div class="row">
        <span>Next Expiry Date:</span>
        <span class="bold">
            {{ $payment->customer?->expiry_date ? \Carbon\Carbon::parse($payment->customer->expiry_date)->format('d-M-Y') : 'Active' }}
        </span>
    </div>
    <div class="row">
        <span>Collected By:</span>
        <span>{{ $payment->collector?->name ?: 'Counter Staff' }}</span>
    </div>

    <div class="double-divider"></div>

    <div class="footer-note">
        Thank you for choosing our internet service!<br>
        Keep this slip for future inquiries.<br>
        Powered by SomitySoft Cloud ERP
    </div>

    <script>
        // Auto-focus print dialog on load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 400);
        };
    </script>
</body>
</html>
