<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Customer Login - {{ $tenant->company_name ?? $tenant->name }}</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
<main class="w-full max-w-sm bg-white rounded-2xl border border-slate-200 shadow-xl p-5">
    <div class="text-center mb-5"><h1 class="text-lg font-bold text-slate-900">{{ $tenant->company_name ?? $tenant->name }}</h1><p class="text-xs text-slate-500">Customer Portal</p></div>
    <form method="POST" action="{{ route('customer.portal.clean.login.submit', ['tenant' => $tenant->slug]) }}" class="space-y-3">
        @csrf
        <div><label class="block text-xs font-semibold text-slate-700 mb-1">Customer ID or Email</label><input name="login_identifier" value="{{ old('login_identifier') }}" required autocomplete="username" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-cyan-500 focus:outline-none"></div>
        <div><label class="block text-xs font-semibold text-slate-700 mb-1">Portal Password</label><input type="password" name="password" required autocomplete="current-password" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-cyan-500 focus:outline-none"></div>
        @error('login_identifier')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        <button class="w-full h-10 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold">Sign in</button>
    </form>
</main>
</body></html>
