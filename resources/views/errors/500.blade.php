<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error | SomitySoft ISP Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="bg-slate-50 text-slate-800 flex items-center justify-center min-h-screen p-4 antialiased font-sans">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 p-6 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-2xl font-bold">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div>
            <span class="text-xs font-bold text-rose-600 uppercase tracking-widest">HTTP 500 Error</span>
            <h1 class="text-xl font-extrabold text-slate-900 mt-1">Temporary Server Exception</h1>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                An unexpected condition was encountered. The error has been recorded.
            </p>
        </div>
        <div class="pt-2 flex items-center justify-center gap-2">
            <a href="/admin/staff" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition inline-flex items-center gap-2 shadow-xs">
                <i class="fas fa-user-tie text-[11px]"></i>
                <span>Staff Directory</span>
            </a>
            <a href="/admin/dashboard" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-2">
                <i class="fas fa-house text-[11px]"></i>
                <span>Admin Dashboard</span>
            </a>
        </div>
    </div>
</body>
</html>
