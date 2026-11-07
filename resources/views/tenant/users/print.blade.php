<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Directory Statement - {{ $tenant->company_name ?? $tenant->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0891b2;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .report-title {
            font-size: 13px;
            font-weight: 600;
            color: #0891b2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 3px 0 0 0;
        }
        .meta-info {
            text-align: right;
            font-size: 10px;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10.5px;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-suspended { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .footer {
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: #94a3b8;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 6px 16px; background: #0891b2; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px;">
            Print / Save as PDF
        </button>
    </div>

    <div class="header">
        <div>
            <h1 class="company-name">{{ $tenant->company_name ?? $tenant->name }}</h1>
            <h2 class="report-title">System User Accounts &amp; Security Directory</h2>
        </div>
        <div class="meta-info">
            <div>Printed On: <strong>{{ date('d-M-Y h:i A') }}</strong></div>
            <div>Total Users: <strong>{{ $users->count() }} Accounts</strong></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">#</th>
                <th>Full Name</th>
                <th>Email / Login ID</th>
                <th class="text-center">Phone</th>
                <th class="text-center">Assigned Role</th>
                <th class="text-center">Designation</th>
                <th class="text-center" style="width: 70px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $idx => $u)
                <tr>
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td style="font-weight: 600;">{{ $u->name }}</td>
                    <td class="font-mono" style="color: #475569;">{{ $u->email }}</td>
                    <td class="text-center font-mono">{{ $u->phone ?? $u->mobile ?? '—' }}</td>
                    <td class="text-center" style="font-weight: 600;">{{ $u->role_badge['label'] ?? ucfirst($u->role) }}</td>
                    <td class="text-center" style="color: #64748b;">{{ $u->designation ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $u->status === 'active' ? 'badge-active' : 'badge-suspended' }}">
                            {{ ucfirst($u->status ?? 'active') }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div>Confidential &bull; Internal System Administration Record</div>
        <div>{{ $tenant->company_name ?? $tenant->name }} ERP Platform</div>
    </div>

</body>
</html>
