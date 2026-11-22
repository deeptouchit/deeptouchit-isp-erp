<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Bandwidth Usage') }} - {{ $reseller->name }}</title>
    <style>
        body { max-width: 52rem; margin: 2rem auto; padding: 0 1rem; color: #0f172a; font: 14px system-ui, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #cbd5e1; padding: .45rem; white-space: nowrap; }
        th, td:first-child, td:last-child { text-align: center; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button type="button" onclick="window.print()">{{ __('Print') }}</button>
    <h1>{{ __('Bandwidth Usage') }}</h1>
    <p>{{ $reseller->name }} · {{ $tenant->company_name ?? $tenant->name }}</p>
    @if($bandwidth)
        <h2>{{ __('Bandwidth Allocation') }}</h2>
        <p>{{ $bandwidth->formatted_total_bandwidth }} · <strong>@currency($bandwidth->monthly_bill_amount)</strong> / {{ __('Month') }}</p>
        <table class="saas-table">
            <thead><tr><th>#</th><th>{{ __('Pipeline') }}</th><th>{{ __('Allocated Bandwidth') }}</th></tr></thead>
            <tbody>
                @foreach($pipelines as $pipeline => $mbps)
                    <tr><td>{{ $loop->iteration }}</td><td>{{ __($pipeline) }}</td><td>{{ __(':speed Mbps', ['speed' => number_format($mbps, 2)]) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>{{ __('No bandwidth allocation configured.') }}</p>
    @endif
    @if($sample)
        <h2>{{ __('Latest Bandwidth Measurement') }}</h2>
        <p><time datetime="{{ $sample['timestamp'] }}">{{ $sample['timestamp'] }}</time></p>
        <p>{{ __('Download Speed') }}: {{ __(':speed Mbps', ['speed' => $sample['download']]) }} · {{ __('Upload Speed') }}: {{ __(':speed Mbps', ['speed' => $sample['upload']]) }}</p>
    @endif
</body>
</html>
