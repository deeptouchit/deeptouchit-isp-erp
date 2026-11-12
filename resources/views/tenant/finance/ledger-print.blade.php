<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Financial Statement') }} - {{ $tenant->company_name ?? $tenant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            line-height: 1.4;
            font-size: 12px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Top Action Bar (Hidden in Print) */
        .top-action-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #0891b2;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #0e7490;
        }

        .btn-secondary {
            background: #334155;
            color: #f1f5f9;
            border-color: #475569;
        }
        .btn-secondary:hover {
            background: #475569;
        }

        /* Printable Paper Container */
        .page-sheet {
            max-width: 960px;
            margin: 24px auto;
            background: #ffffff;
            padding: 36px 40px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        }

        /* Header Section */
        .doc-header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .company-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #0f172a;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
            border: 1px solid #cbd5e1;
        }

        .doc-meta {
            font-size: 11px;
            color: #475569;
            margin-top: 6px;
        }

        /* Summary Grid */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
        }

        .summary-box {
            display: flex;
            flex-direction: column;
        }

        .summary-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }

        .summary-val {
            font-size: 15px;
            font-weight: 700;
            margin-top: 2px;
        }

        .text-green { color: #16a34a; }
        .text-rose { color: #dc2626; }
        .text-cyan { color: #0891b2; }

        /* Tables */
        table.print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 24px;
        }

        table.print-table thead th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1.5px solid #94a3b8;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        table.print-table tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        table.print-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        /* P&L Statement Grid */
        .pnl-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .pnl-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }

        .pnl-card-head {
            padding: 8px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
        }

        .pnl-green-head { background: #dcfce7; color: #166534; border-bottom: 1px solid #bbf7d0; }
        .pnl-rose-head { background: #ffe4e6; color: #9f1239; border-bottom: 1px solid #fecdd3; }

        .pnl-card-body {
            padding: 12px;
        }

        .pnl-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11.5px;
        }

        .pnl-total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 10px;
            font-weight: 700;
            margin-top: 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        /* 4-Tier Signature Matrix */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 50px;
            text-align: center;
            page-break-inside: avoid;
        }

        .sign-box {
            padding-top: 4px;
        }

        .sign-line {
            border-top: 1px solid #475569;
            margin-bottom: 4px;
        }

        .sign-title {
            font-weight: 700;
            font-size: 11px;
            color: #1e293b;
        }

        .sign-role {
            font-size: 10px;
            color: #64748b;
        }

        /* Print Specifics */
        @media print {
            body {
                background: #ffffff !important;
            }
            .top-action-bar, .no-print {
                display: none !important;
            }
            .page-sheet {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Bar (Screen Only) --}}
    <div class="top-action-bar no-print">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-file-invoice text-cyan-400"></i>
            <span style="font-weight: 700; font-size: 13px;">{{ __('Official Financial Statement & General Ledger') }}</span>
            <span style="color: #94a3b8; font-size: 11px;">({{ $periodLabel }})</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="{{ route('tenant.finance.ledger.export', request()->query()) }}" class="btn btn-secondary">
                <i class="fas fa-file-csv"></i> {{ __('Export CSV') }}
            </a>
            <button type="button" onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> {{ __('Print Document') }}
            </button>
            <button type="button" onclick="window.close()" class="btn btn-secondary" title="{{ __('Close Tab') }}">
                <i class="fas fa-times"></i> {{ __('Close') }}
            </button>
        </div>
    </div>

    {{-- Printable Paper Page --}}
    <div class="page-sheet">

        {{-- 1. Document Header --}}
        <div class="doc-header">
            <h1 class="company-title">{{ $tenant->company_name ?? $tenant->name }}</h1>
            <p class="company-sub">{{ $tenant->address ?? __('Central NOC & Financial Accounting Operations') }}</p>
            <div class="doc-badge">
                @if($tab === 'pnl')
                    {{ __('Statement of Profit & Loss') }}
                @elseif($tab === 'cashbook')
                    {{ __('Cash & Bank Summary') }}
                @elseif($tab === 'heads')
                    {{ __('Chart of Accounts Head Summary') }}
                @else
                    {{ __('Official Financial Statement & General Ledger') }}
                @endif
            </div>
            <div class="doc-meta font-mono">
                {{ __('Period:') }} <strong>{{ $periodLabel }}</strong> | {{ __('Generated On:') }} {{ now()->translatedFormat('d M, Y h:i A') }}
            </div>
        </div>

        {{-- 2. Financial Summary KPI Grid --}}
        <div class="summary-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="summary-box">
                <span class="summary-label">{{ __('Gross Revenue') }}</span>
                <span class="summary-val font-mono text-green">
                    @currency($kpi['gross_revenue'])
                </span>
            </div>
            <div class="summary-box">
                <span class="summary-label">{{ __('Upstream COGS') }}</span>
                <span class="summary-val font-mono text-rose">
                    @currency($kpi['upstream_cogs'] ?? $kpi['cogs_upstream'] ?? 0)
                </span>
            </div>
            <div class="summary-box">
                <span class="summary-label">{{ __('Gross Margin') }}</span>
                <span class="summary-val font-mono text-cyan">
                    @currency($pnl['gross_operating_margin'] ?? (($kpi['gross_revenue'] ?? 0) - ($kpi['upstream_cogs'] ?? 0)))
                    <span style="font-size: 10px; font-weight: normal; color: #64748b;">({{ $kpi['gross_margin'] ?? $kpi['gross_margin_pct'] ?? 0 }}%)</span>
                </span>
            </div>
            <div class="summary-box">
                <span class="summary-label">{{ __('Net Operating Profit') }}</span>
                <span class="summary-val font-mono {{ $kpi['net_profit'] >= 0 ? 'text-cyan' : 'text-rose' }}">
                    @currency($kpi['net_profit'])
                    <span style="font-size: 10px; font-weight: normal; color: #64748b;">({{ $kpi['profit_margin'] }}%)</span>
                </span>
            </div>
        </div>

        {{-- 3. Content Table based on Selected Statement --}}
        @if($tab === 'pnl')
            {{-- Profit & Loss Statement View (ISP Architecture: Revenue, Upstream COGS, OPEX) --}}
            <div class="pnl-container" style="grid-template-columns: repeat(3, 1fr);">
                {{-- A. Gross Revenues --}}
                <div class="pnl-card">
                    <div class="pnl-card-head pnl-green-head">
                        <span>{{ __('A. Revenue & Incomes') }}</span>
                        <span class="font-mono">@currency($pnl['total_gross_revenue'])</span>
                    </div>
                    <div class="pnl-card-body">
                        <div class="pnl-row">
                            <span>{{ __('1. Customer Retail') }}</span>
                            <span class="font-mono" style="font-weight: 600;">@currency($pnl['retail_revenue'])</span>
                        </div>
                        <div class="pnl-row">
                            <span>{{ __('2. Reseller Wholesale') }}</span>
                            <span class="font-mono" style="font-weight: 600;">@currency($pnl['wholesale_revenue'])</span>
                        </div>
                        @foreach($pnl['direct_income_groups'] as $headName => $amount)
                            <div class="pnl-row">
                                <span>{{ $headName }}</span>
                                <span class="font-mono" style="font-weight: 600;">@currency($amount)</span>
                            </div>
                        @endforeach
                        <div class="pnl-total-row" style="background: #dcfce7; color: #166534;">
                            <span>{{ __('Gross Revenue (A):') }}</span>
                            <span class="font-mono">@currency($pnl['total_gross_revenue'])</span>
                        </div>
                    </div>
                </div>

                {{-- B. Cost of Goods Sold (COGS - Upstream Bandwidth) --}}
                <div class="pnl-card">
                    <div class="pnl-card-head" style="background: #ffedd5; color: #9a3412; border-bottom: 1px solid #fed7aa;">
                        <span>{{ __('B. Upstream COGS') }}</span>
                        <span class="font-mono">@currency($pnl['total_upstream_cogs'])</span>
                    </div>
                    <div class="pnl-card-body">
                        @forelse($pnl['upstream_cogs_groups'] as $headName => $amount)
                            <div class="pnl-row">
                                <span>{{ $headName }}</span>
                                <span class="font-mono text-rose" style="font-weight: 600;">@currency($amount)</span>
                            </div>
                        @empty
                            <div style="padding: 12px; text-align: center; color: #94a3b8;">{{ __('No upstream carrier payments.') }}</div>
                        @endforelse
                        <div class="pnl-total-row" style="background: #ffedd5; color: #9a3412;">
                            <span>{{ __('Total Upstream (B):') }}</span>
                            <span class="font-mono">@currency($pnl['total_upstream_cogs'])</span>
                        </div>
                    </div>
                </div>

                {{-- C. Operating Expenses (OPEX) --}}
                <div class="pnl-card">
                    <div class="pnl-card-head pnl-rose-head">
                        <span>{{ __('C. Operating OPEX') }}</span>
                        <span class="font-mono">@currency($pnl['total_expenses'])</span>
                    </div>
                    <div class="pnl-card-body">
                        @forelse($pnl['expense_groups'] as $headName => $amount)
                            <div class="pnl-row">
                                <span>{{ $headName }}</span>
                                <span class="font-mono text-rose" style="font-weight: 600;">@currency($amount)</span>
                            </div>
                        @empty
                            <div style="padding: 12px; text-align: center; color: #94a3b8;">{{ __('No OPEX transactions.') }}</div>
                        @endforelse
                        <div class="pnl-total-row" style="background: #ffe4e6; color: #9f1239;">
                            <span>{{ __('Total OPEX (C):') }}</span>
                            <span class="font-mono">@currency($pnl['total_expenses'])</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- P&L Bottom Total & Gross Profit Overview --}}
            <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 14px 18px; margin-bottom: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div style="border-right: 1px solid #e2e8f0; padding-right: 16px;">
                    <span style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #64748b;">{{ __('Gross Operating Profit (A - B)') }}</span>
                    <div class="font-mono text-cyan" style="font-size: 17px; font-weight: 800; margin-top: 2px;">
                        @currency($pnl['gross_operating_margin'])
                    </div>
                    <span style="font-size: 11px; color: #475569;">{{ __('Gross Margin:') }} <strong class="text-green">{{ $pnl['gross_margin_pct'] }}%</strong></span>
                </div>
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #64748b;">{{ __('Net Operating Profit (Gross Profit - OPEX)') }}</span>
                    <div class="font-mono {{ $pnl['net_profit'] >= 0 ? 'text-cyan' : 'text-rose' }}" style="font-size: 17px; font-weight: 800; margin-top: 2px;">
                        @currency($pnl['net_profit'])
                    </div>
                    <span style="font-size: 11px; color: #475569;">{{ __('Net Operating Margin:') }} <strong class="text-green">{{ $pnl['profit_margin'] }}%</strong></span>
                </div>
            </div>

        @elseif($tab === 'cashbook')
            {{-- Cash & Bank Book View --}}
            <div class="pnl-container">
                <div class="pnl-card">
                    <div class="pnl-card-head" style="background: #fef3c7; color: #92400e; border-bottom: 1px solid #fde68a;">
                        <span>{{ __('Physical Cash In Hand Book') }}</span>
                        <span class="font-mono">@currency($cashbook['cash_net'])</span>
                    </div>
                    <div class="pnl-card-body">
                        <div class="pnl-row">
                            <span>{{ __('Total Cash Inflows') }}</span>
                            <span class="font-mono text-green">+@currency($cashbook['cash_in'])</span>
                        </div>
                        <div class="pnl-row">
                            <span>{{ __('Total Cash Outflows (Disbursed)') }}</span>
                            <span class="font-mono text-rose">-@currency($cashbook['cash_out'])</span>
                        </div>
                        <div class="pnl-total-row" style="background: #fef3c7; color: #92400e;">
                            <span>{{ __('Net Cash in Hand:') }}</span>
                            <span class="font-mono">@currency($cashbook['cash_net'])</span>
                        </div>
                    </div>
                </div>

                <div class="pnl-card">
                    <div class="pnl-card-head" style="background: #e0f2fe; color: #0369a1; border-bottom: 1px solid #bae6fd;">
                        <span>{{ __('Bank & Digital MFS Accounts Book') }}</span>
                        <span class="font-mono">@currency($cashbook['bank_net'])</span>
                    </div>
                    <div class="pnl-card-body">
                        <div class="pnl-row">
                            <span>{{ __('Total Bank & MFS Deposits') }}</span>
                            <span class="font-mono text-green">+@currency($cashbook['bank_in'])</span>
                        </div>
                        <div class="pnl-row">
                            <span>{{ __('Total Bank Disbursements') }}</span>
                            <span class="font-mono text-rose">-@currency($cashbook['bank_out'])</span>
                        </div>
                        <div class="pnl-total-row" style="background: #e0f2fe; color: #0369a1;">
                            <span>{{ __('Net Bank & MFS Position:') }}</span>
                            <span class="font-mono">@currency($cashbook['bank_net'])</span>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($tab === 'heads')
            {{-- Chart of Accounts Head Summary --}}
            <table class="print-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 35px;">#</th>
                        <th style="width: 100px;">{{ __('Head Code') }}</th>
                        <th>{{ __('Account Head Title') }}</th>
                        <th class="text-center" style="width: 80px;">{{ __('Type') }}</th>
                        <th class="text-right" style="width: 120px;">{{ __('Debit (Expense)') }}</th>
                        <th class="text-right" style="width: 120px;">{{ __('Credit (Revenue)') }}</th>
                        <th class="text-right" style="width: 130px;">{{ __('Net Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($headSummary as $i => $h)
                        <tr>
                            <td class="text-center font-mono" style="color: #94a3b8;">{{ $i + 1 }}</td>
                            <td class="font-mono" style="font-weight: 700;">{{ $h['code'] }}</td>
                            <td style="font-weight: 600;">{{ $h['name'] }}</td>
                            <td class="text-center">
                                <span style="font-size: 9.5px; font-weight: 700; text-transform: uppercase;">{{ $h['type'] === 'income' ? __('income') : __('expense') }}</span>
                            </td>
                            <td class="text-right font-mono text-rose">
                                {{ $h['debit'] > 0 ? number_format($h['debit'], 2) : '-' }}
                            </td>
                            <td class="text-right font-mono text-green">
                                {{ $h['credit'] > 0 ? number_format($h['credit'], 2) : '-' }}
                            </td>
                            <td class="text-right font-mono" style="font-weight: 700;">
                                {{ number_format($h['net'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @else
            {{-- General Ledger (Default) --}}
            <div style="font-size: 11px; margin-bottom: 8px; display: flex; justify-content: space-between; color: #475569;">
                <span><strong>{{ __('Opening Balance:') }}</strong> @currency($openingBalance['net'])</span>
                <span><strong>{{ __('Total Transactions:') }}</strong> {{ count($entries) }}</span>
            </div>

            <table class="print-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 35px;">#</th>
                        <th style="width: 110px;">{{ __('Date & Time') }}</th>
                        <th style="width: 115px;">{{ __('Voucher / Ref') }}</th>
                        <th style="width: 140px;">{{ __('Account Head') }}</th>
                        <th>{{ __('Narrative / Particulars') }}</th>
                        <th class="text-right" style="width: 95px;">{{ __('Debit (-)') }}</th>
                        <th class="text-right" style="width: 95px;">{{ __('Credit (+)') }}</th>
                        <th class="text-right" style="width: 110px;">{{ __('Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $printRunning = $openingBalance['net'];
                    @endphp
                    @forelse($entries as $i => $item)
                        @php
                            $deb = $item['type'] === 'debit' ? $item['amount'] : 0;
                            $crd = $item['type'] === 'credit' ? $item['amount'] : 0;
                            $printRunning += ($crd - $deb);
                        @endphp
                        <tr>
                            <td class="text-center font-mono" style="color: #94a3b8;">{{ $i + 1 }}</td>
                            <td class="font-mono">{{ $item['date'] }}</td>
                            <td class="font-mono" style="font-weight: 700; color: #0891b2;">{{ $item['ref_no'] }}</td>
                            <td style="font-weight: 600;">{{ $item['category'] }}</td>
                            <td style="color: #475569;">{{ $item['description'] }}</td>
                            <td class="text-right font-mono text-rose">
                                {{ $deb > 0 ? '-' . number_format($deb, 2) : '-' }}
                            </td>
                            <td class="text-right font-mono text-green">
                                {{ $crd > 0 ? '+' . number_format($crd, 2) : '-' }}
                            </td>
                            <td class="text-right font-mono" style="font-weight: 700; color: {{ $printRunning >= 0 ? '#1e293b' : '#dc2626' }};">
                                {{ number_format($printRunning, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 24px; color: #94a3b8;">
                                {{ __('No financial ledger records found for this period.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        {{-- 4. 4-Tier Official Signature Matrix --}}
        <div class="signatures-grid">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-title">{{ __('Prepared By') }}</div>
                <div class="sign-role">{{ __('Accounts Officer') }}</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-title">{{ __('Verified By') }}</div>
                <div class="sign-role">{{ __('Finance Manager') }}</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-title">{{ __('Internal Auditor') }}</div>
                <div class="sign-role">{{ __('Audit & Compliance') }}</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-title">{{ __('Approved By') }}</div>
                <div class="sign-role">{{ __('Managing Director / CEO') }}</div>
            </div>
        </div>

    </div>

</body>
</html>
