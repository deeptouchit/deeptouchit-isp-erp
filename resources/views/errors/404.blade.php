<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $path = request()->path();
        $isOwner = str_starts_with($path, 'owner');
        $isTenant = str_starts_with($path, 'admin') || str_starts_with($path, 'tenant');
        $isReseller = str_starts_with($path, 'reseller');
        $portalTenant = $path && !in_array(request()->segment(1), ['owner', 'admin', 'tenant', 'reseller', 'portal', 'login'], true)
            ? \App\Models\Tenant::where('slug', request()->segment(1))->first()
            : null;
        $isCustomerPortal = str_starts_with($path, 'portal') || $portalTenant;

        if ($isOwner) {
            $homeUrl = route('owner.dashboard');
            $homeLabel = 'Owner Dashboard';
            $actionUrl = route('owner.tenants.index');
            $actionLabel = 'Manage ISPs';
        } elseif ($isReseller) {
            $homeUrl = route('reseller.dashboard');
            $homeLabel = 'Reseller Dashboard';
            $actionUrl = route('reseller.customers.index');
            $actionLabel = 'Customers';
        } elseif ($isCustomerPortal && $portalTenant) {
            $homeUrl = route('customer.portal.clean.dashboard', ['tenant' => $portalTenant->slug]);
            $homeLabel = 'Customer Home';
            $actionUrl = route('customer.portal.clean.login', ['tenant' => $portalTenant->slug]);
            $actionLabel = 'Customer Login';
        } elseif ($isTenant) {
            $homeUrl = route('tenant.dashboard');
            $homeLabel = 'Tenant Dashboard';
            $actionUrl = route('tenant.staff.index');
            $actionLabel = 'Employees';
        } else {
            $homeUrl = auth()->check() ? route('tenant.dashboard') : route('login');
            $homeLabel = auth()->check() ? 'Dashboard' : 'Sign in';
            $actionUrl = $homeUrl;
            $actionLabel = $homeLabel;
        }
    @endphp
    <title>404 - Page Not Found | {{ config('app.name', 'SomitySoft ISP Platform') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="bg-slate-50 text-slate-800 flex items-center justify-center min-h-screen p-4 antialiased font-sans">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 p-6 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-2xl font-bold">
            <i class="fas fa-compass"></i>
        </div>
        <div>
            <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest">HTTP 404 Error</span>
            <h1 class="text-xl font-extrabold text-slate-900 mt-1">Page or Resource Not Found</h1>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                <span class="font-medium text-slate-700">{{ request()->path() }}</span> does not exist or may have been moved.
            </p>
        </div>
        <div class="pt-2 flex items-center justify-center gap-2">
            <a href="{{ $actionUrl }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition inline-flex items-center gap-2 shadow-xs">
                <i class="fas fa-compass text-[11px]"></i>
                <span>{{ $actionLabel }}</span>
            </a>
            <a href="{{ $homeUrl }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-2">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>{{ $homeLabel }}</span>
            </a>
        </div>
    </div>
</body>
</html>
