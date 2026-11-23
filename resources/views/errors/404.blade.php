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
            $actionUrl = $homeUrl;
            $actionLabel = 'Go to dashboard';
        } elseif ($isReseller) {
            $homeUrl = route('reseller.dashboard');
            $homeLabel = 'Reseller Dashboard';
            $actionUrl = $homeUrl;
            $actionLabel = 'Go to dashboard';
        } elseif ($isCustomerPortal && $portalTenant) {
            $homeUrl = route('customer.portal.clean.dashboard', ['tenant' => $portalTenant->slug]);
            $homeLabel = 'Customer Home';
            $actionUrl = $homeUrl;
            $actionLabel = 'Go to home';
        } elseif ($isTenant) {
            $homeUrl = route('tenant.dashboard');
            $homeLabel = 'Tenant Dashboard';
            $actionUrl = $homeUrl;
            $actionLabel = 'Go to dashboard';
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
        <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-2xl font-bold shadow-sm">
            <i class="fas fa-map-signs"></i>
        </div>
        <div class="space-y-2">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Page not found</h1>
            <p class="text-sm text-slate-500 leading-relaxed">
                We couldn’t find the page you’re looking for. It may have been moved or is no longer available.
            </p>
        </div>
        <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2">
            <a href="{{ $actionUrl }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition inline-flex items-center justify-center gap-2 shadow-sm">
                <i class="fas fa-house text-xs"></i>
                <span>{{ $actionLabel }}</span>
            </a>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : $homeUrl }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm transition inline-flex items-center justify-center gap-2 border border-slate-200">
                <i class="fas fa-arrow-left text-xs"></i>
                <span>Go back</span>
            </a>
        </div>
    </div>
</body>
</html>
