<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Field Due &amp; Expired Sheet - {{ $tenant->company_name ?? 'ISP Management' }}</title>
    
    {{-- Project Typography & Web Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Baloo Da 2', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 15px;
            background-color: #fff;
            color: #1e293b;
            font-size: 11.5px;
            line-height: 1.3;
        }

        .no-print {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .btn {
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: #0891b2;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #0e7490;
        }

        .btn-secondary {
            background-color: #f1f5f9;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        .header-container {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .company-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        .sheet-subtitle {
            font-size: 13px;
            font-weight: 700;
            color: #e11d48;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-strip {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-top: 6px;
            color: #475569;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #64748b;
            padding: 4.5px 6px;
            vertical-align: middle;
            white-space: nowrap !important;
        }

        .print-table th {
            background-color: #f1f5f9 !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            color: #0f172a;
            text-align: left;
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        .write-box {
            height: 22px;
            border-bottom: 1px dotted #94a3b8;
        }

        .footer-summary {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
        }

        .sig-block {
            text-align: center;
            width: 180px;
            padding-top: 40px;
            border-top: 1px dashed #475569;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; font-size: 10.5px; }
            .print-table th { background-color: #f1f5f9 !important; }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    {{-- Control Bar --}}
    <div class="no-print">
        <div>
            <strong style="font-size: 13px;">Field Collection Due &amp; Expired Sheet</strong>
            <span style="color: #64748b; font-size: 11px; margin-left: 8px;">
                Total {{ number_format(count($customers)) }} Due &amp; Expired Subscribers
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Document
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>

    {{-- Sheet Header --}}
    <div class="header-container">
        <h1 class="company-title">{{ $tenant->company_name ?? $tenant->name ?? 'ISP Management System' }}</h1>
        <div style="font-size: 11px; color: #475569;">
            {{ $tenant->address ?? 'ISP Operations Center' }} {{ !empty($tenant->phone) ? ' | Helpline: ' . $tenant->phone : '' }}
        </div>
        <div class="sheet-subtitle">
            <i class="fas fa-file-invoice-dollar"></i> FIELD COLLECTION ROSTER & DUE SHEET
        </div>
        
        <div class="meta-strip">
            <div>
                <strong>Coverage Area:</strong> 
                <span>{{ $activeZone ? $activeZone->name : 'All Assigned Zones' }}</span>
            </div>
            <div>
                <strong>Assigned Collector:</strong> 
                <span>{{ $activeCollector ? $activeCollector->name : ($authUser->name ?? 'Field Agent') }}</span>
            </div>
            <div>
                <strong>Print Date:</strong> 
                <span class="font-mono">{{ date('d M Y - h:i A') }}</span>
            </div>
        </div>
    </div>

    {{-- Printable Master Table --}}
    <table class="print-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">#</th>
                <th style="width: 90px;">Customer ID</th>
                <th>Customer Name</th>
                <th style="width: 95px;">Guardian Name</th>
                <th style="width: 90px;">Mobile Number</th>
                <th style="width: 95px;">Area</th>
                <th style="width: 85px;">Package</th>
                <th class="text-right" style="width: 70px;">Monthly Bill</th>
                <th class="text-right" style="width: 75px;">Due Amount</th>
                <th class="text-center" style="width: 80px;">Collected (Tk)</th>
                <th class="text-center" style="width: 90px;">Signature</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $index => $customer)
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="font-mono font-bold">{{ $customer->customer_id ?: $customer->username }}</td>
                    <td><strong>{{ $customer->name ?: $customer->username }}</strong></td>
                    <td>{{ $customer->father_name ?: '—' }}</td>
                    <td class="font-mono">{{ $customer->phone ?? '—' }}</td>
                    <td>{{ (is_object($customer->zone) ? ($customer->zone->name ?? '') : $customer->zone) ?: ($customer->coverageZone?->name ?: ($customer->address ?: '—')) }}</td>
                    <td>{{ $customer->package_display_name }}</td>
                    <td class="text-right font-mono">
                        {{ number_format((float)($customer->monthly_bill ?? ($customer->package?->price ?? 0)), 2) }}
                    </td>
                    <td class="text-right font-mono font-bold" style="color: #9f1239;">
                        {{ number_format((float)($customer->due_amount ?? 0), 2) }}
                    </td>
                    <td><div class="write-box"></div></td>
                    <td><div class="write-box"></div></td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px;">
                        No due subscribers found for the selected scope.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="8" class="text-right uppercase">Total Scope Due:</td>
                <td class="text-right font-mono font-bold" style="color: #9f1239; font-size: 12px;">
                    Tk {{ number_format($totalDue, 2) }}
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    {{-- Footer Signatures --}}
    <div class="footer-summary">
        <div>
            <div style="font-size: 10.5px; color: #475569;">
                <strong>Instructions for Collector:</strong><br>
                1. Always verify subscriber identity before receiving cash.<br>
                2. Issue instant SMS / money receipt upon collecting payment.<br>
                3. Submit collected cash to office counter by end of shift.
            </div>
        </div>
        <div style="display: flex; gap: 40px;">
            <div class="sig-block">
                Field Collector Signature
            </div>
            <div class="sig-block">
                Accounts Officer Signature
            </div>
        </div>
    </div>

</body>
</html>

