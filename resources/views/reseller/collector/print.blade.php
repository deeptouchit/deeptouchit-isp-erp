<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Assigned Customers') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400..800&display=swap">
    <style>
        body { font: 14px "Baloo Da 2", sans-serif; margin: 24px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; white-space: nowrap; }
        .center { text-align: center; }
        .amount { text-align: right; font-variant-numeric: tabular-nums; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button type="button" onclick="window.print()">{{ __('Print Report') }}</button>
    <h1>{{ __('Assigned Customers') }}</h1>
    <table class="saas-table">
        <thead><tr><th>#</th><th>{{ __('Customer ID') }}</th><th>{{ __('Customer Name') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Due Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
            @forelse($customers as $customer)
                <tr><td class="center">{{ $loop->iteration }}</td><td class="center">{{ $customer->customer_id }}</td><td>{{ $customer->name }}</td><td class="center">{{ $customer->phone }}</td><td class="amount">@currency($customer->due_amount)</td><td class="center">{{ __(ucfirst($customer->status)) }}</td></tr>
            @empty
                <tr><td colspan="6" class="center">{{ __('No assigned customers match your filters.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
