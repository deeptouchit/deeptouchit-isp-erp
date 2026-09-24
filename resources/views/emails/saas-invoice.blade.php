<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_no }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1e293b; }
        table { border-collapse: collapse; width: 100%; }
        .email-wrapper { width: 100%; background-color: #f1f5f9; padding: 30px 10px; }
        .email-card { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .top-banner { background: #0f172a; padding: 24px; color: #ffffff; }
        .logo-img { max-height: 38px; vertical-align: middle; }
        .brand-name { font-size: 17px; font-weight: 700; color: #ffffff; letter-spacing: -0.3px; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-paid { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-unpaid { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .content-body { padding: 24px; }
        .meta-grid { margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
        .meta-col { width: 50%; vertical-align: top; }
        .meta-label { font-size: 10px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px; }
        .meta-val { font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.4; }
        .items-table { margin: 20px 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .items-table th { background-color: #f8fafc; color: #475569; font-size: 10.5px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 10px 12px; font-size: 12px; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
        .items-table tr:last-child td { border-bottom: none; }
        .totals-table { margin-top: 10px; margin-left: auto; width: 260px; }
        .totals-table td { padding: 4px 0; font-size: 12px; }
        .total-highlight { font-size: 15px; font-weight: 800; color: #0f172a; border-top: 2px solid #0f172a; padding-top: 8px; }
        .cta-btn { display: inline-block; background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 10px 22px; border-radius: 6px; font-size: 12.5px; font-weight: 700; text-align: center; }
        .info-panel { background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; padding: 14px; margin-top: 20px; font-size: 11.5px; color: #475569; line-height: 1.5; }
        .email-footer { background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-card">
            <!-- Header -->
            <div class="top-banner">
                <table style="width: 100%;">
                    <tr>
                        <td style="vertical-align: middle;">
                            @if(!empty($settings['app_logo']))
                                <img src="{{ url($settings['app_logo']) }}" alt="Logo" class="logo-img">
                            @endif
                            <span class="brand-name">{{ $settings['app_name'] ?? 'SomitySoft SaaS' }}</span>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            @if($invoice->status === 'paid')
                                <span class="status-badge status-paid">✓ PAID</span>
                            @else
                                <span class="status-badge status-unpaid">UNPAID DUE</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Body -->
            <div class="content-body">
                <h2 style="font-size: 16px; margin: 0 0 16px 0; color: #0f172a;">
                    {{ $invoice->status === 'paid' ? 'Payment Receipt Voucher' : 'Subscription Invoice' }} #{{ $invoice->invoice_no }}
                </h2>

                <!-- Invoice Meta Grid -->
                <div class="meta-grid">
                    <table>
                        <tr>
                            <td class="meta-col">
                                <div class="meta-label">Billed To</div>
                                <div class="meta-val">{{ $tenant->company_name ?: ($tenant->name ?? 'Valued Client') }}</div>
                                @if(!empty($tenant->email))
                                    <div style="font-size: 11.5px; color: #64748b;">{{ $tenant->email }}</div>
                                @endif
                                @if(!empty($tenant->domain))
                                    <div style="font-size: 11.5px; color: #2563eb; font-family: monospace;">{{ $tenant->domain }}</div>
                                @endif
                            </td>
                            <td class="meta-col" style="text-align: right;">
                                <div class="meta-label">Invoice Details</div>
                                <div class="meta-val">Date: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}</div>
                                <div style="font-size: 11.5px; color: {{ $invoice->status === 'paid' ? '#059669' : '#dc2626' }}; font-weight: 600;">
                                    Due: {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : 'Due on Receipt' }}
                                </div>
                                <div style="font-size: 10.5px; color: #64748b; font-family: monospace;">Ref: {{ $invoice->invoice_no }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Line Items -->
                <div class="items-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Item & Description</th>
                                <th style="text-align: center; width: 50px;">Qty</th>
                                <th style="text-align: right; width: 90px;">Rate</th>
                                <th style="text-align: right; width: 90px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->description }}</strong>
                                    </td>
                                    <td style="text-align: center;">{{ $item->quantity }}</td>
                                    <td style="text-align: right; font-family: monospace;">৳{{ number_format($item->unit_price, 2) }}</td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 600;">৳{{ number_format($item->total_price, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td><strong>{{ $invoice->plan->name ?? 'Cloud Subscription Plan' }}</strong></td>
                                    <td style="text-align: center;">1</td>
                                    <td style="text-align: right; font-family: monospace;">৳{{ number_format($invoice->amount, 2) }}</td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 600;">৳{{ number_format($invoice->amount, 2) }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <table class="totals-table">
                    <tr>
                        <td style="color: #64748b;">Subtotal:</td>
                        <td style="text-align: right; font-family: monospace;">৳{{ number_format($invoice->subtotal ?: $invoice->amount, 2) }}</td>
                    </tr>
                    @if((float)($invoice->discount ?? 0) > 0)
                        <tr>
                            <td style="color: #059669;">Discount:</td>
                            <td style="text-align: right; color: #059669; font-family: monospace;">-৳{{ number_format($invoice->discount, 2) }}</td>
                        </tr>
                    @endif
                    @if((float)($invoice->paid_amount ?? 0) > 0)
                        <tr>
                            <td style="color: #64748b;">Amount Paid:</td>
                            <td style="text-align: right; color: #059669; font-family: monospace; font-weight: 600;">৳{{ number_format($invoice->paid_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="total-highlight">{{ $invoice->status === 'paid' ? 'Total Paid:' : 'Balance Due:' }}</td>
                        <td class="total-highlight" style="text-align: right; font-family: monospace;">
                            ৳{{ number_format($invoice->status === 'paid' ? ($invoice->paid_amount ?: $invoice->amount) : ($invoice->due_amount ?: $invoice->amount), 2) }}
                        </td>
                    </tr>
                </table>

                <!-- Action / Instructions -->
                <div style="margin-top: 24px; text-align: center;">
                    @php
                        $portalUrl = !empty($tenant->domain) ? 'https://' . $tenant->domain . '/admin/billing/invoices/' . $invoice->id : url('/admin/billing/invoices/' . $invoice->id);
                    @endphp
                    <a href="{{ $portalUrl }}" class="cta-btn">
                        {{ $invoice->status === 'paid' ? 'View Online Receipt' : 'Pay Invoice Online' }}
                    </a>
                </div>

                @if($invoice->status !== 'paid' && !empty($settings['bank_name']))
                    <div class="info-panel">
                        <div style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Bank Payment Instructions:</div>
                        <div><strong>Bank:</strong> {{ $settings['bank_name'] }}</div>
                        <div><strong>Account Name:</strong> {{ $settings['bank_account_name'] ?? '' }}</div>
                        <div><strong>Account Number:</strong> {{ $settings['bank_account_number'] ?? '' }}</div>
                        @if(!empty($settings['bank_routing_number']))
                            <div><strong>Routing / Branch:</strong> {{ $settings['bank_routing_number'] }}</div>
                        @endif
                        @if(!empty($settings['mfs_bkash_personal']))
                            <div style="margin-top: 6px;"><strong>bKash / MFS:</strong> {{ $settings['mfs_bkash_personal'] }}</div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <div>This billing notice was dispatched from official billing address: <strong style="color: #2563eb;">{{ $billingEmail }}</strong></div>
                <div style="margin-top: 4px;">{{ $settings['company_address'] ?? 'Dhaka, Bangladesh' }} • Hotline: {{ $settings['support_phone'] ?? '+880 1700-000000' }}</div>
                <div style="margin-top: 6px; font-size: 10px; color: #94a3b8;">
                    &copy; {{ date('Y') }} {{ $settings['app_name'] ?? 'SomitySoft' }}. All rights reserved.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
