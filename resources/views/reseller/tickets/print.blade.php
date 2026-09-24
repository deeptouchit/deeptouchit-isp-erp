<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets Report - {{ $reseller->name ?? 'Reseller' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white !important; padding: 0 !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-6 text-slate-800 text-xs font-sans">

    <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        
        <!-- Action Bar -->
        <div class="flex items-center justify-between no-print pb-3 border-b border-slate-200">
            <a href="{{ route('reseller.tickets.index') }}" class="px-3 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs">
                ← Back to Tickets
            </a>
            <button onclick="window.print()" class="px-4 py-1 rounded bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold">
                Print Report
            </button>
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4">
            <div>
                <h1 class="text-lg font-bold text-slate-900">{{ $reseller->name ?? 'Reseller Partner' }}</h1>
                <p class="text-xs text-slate-500">Host ISP: {{ $tenant->company_name ?? 'SomitySoft Network' }}</p>
            </div>
            <div class="text-right text-xs">
                <h2 class="font-bold text-purple-700">Support Tickets Master Sheet</h2>
                <span class="text-slate-400">Date: {{ date('d M, Y h:i A') }}</span>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full border-collapse text-left text-xs">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-300">
                    <th class="p-2 w-10 text-center">#</th>
                    <th class="p-2">Ticket ID</th>
                    <th class="p-2">Subject</th>
                    <th class="p-2">Department</th>
                    <th class="p-2 text-center">Priority</th>
                    <th class="p-2 text-center">Status</th>
                    <th class="p-2 text-right">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $index => $t)
                    <tr class="border-b border-slate-200">
                        <td class="p-2 text-center font-mono">{{ $index + 1 }}</td>
                        <td class="p-2 font-mono font-bold text-purple-700">{{ $t->ticket_number }}</td>
                        <td class="p-2 font-medium">{{ $t->subject }}</td>
                        <td class="p-2 capitalize">{{ $t->department }}</td>
                        <td class="p-2 text-center uppercase text-[10px] font-bold">{{ $t->priority }}</td>
                        <td class="p-2 text-center uppercase text-[10px] font-bold">{{ $t->status }}</td>
                        <td class="p-2 text-right font-mono text-[11px]">{{ $t->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-slate-400">No tickets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer -->
        <div class="pt-4 border-t text-[10px] text-slate-400 flex justify-between font-mono">
            <span>Total Records: {{ $tickets->count() }}</span>
            <span>Generated from SomitySoft Reseller Gateway</span>
        </div>

    </div>

</body>
</html>
