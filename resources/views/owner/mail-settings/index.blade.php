@extends('owner.layouts.app')

@section('page-title', 'Domain Mail & SMTP Settings')

@section('content')
<div class="space-y-4" x-data="{ 
    saving: false,
    testing: false,
    showPassword: false,
    applyDomainPreset(host, port, enc, user) {
        $refs.mailHost.value = host;
        $refs.mailPort.value = port;
        $refs.mailEnc.value = enc;
        if (user) {
            $refs.mailUser.value = user;
        }
    }
}">

    <!-- Top Header -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white text-base font-bold shadow-xs">
                <i class="fas fa-envelope-open-text text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-800">Mail Settings</h2>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="#test-section" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                <i class="fas fa-paper-plane text-[10px]"></i>
                <span>Test Email Dispatch</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 flex items-center gap-2"><i class="fas fa-circle-check"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800 flex items-center gap-2"><i class="fas fa-circle-exclamation"></i><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"><div class="font-semibold mb-1">Please correct the following:</div><ul class="list-disc pl-4 space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if(session('mail_test_result'))
        @php($testResult = session('mail_test_result'))
        <div class="rounded-lg border {{ $testResult['success'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} px-3 py-2 text-xs {{ $testResult['success'] ? 'text-emerald-900' : 'text-rose-900' }}">
            <div class="flex items-center gap-2 font-semibold"><i class="fas {{ $testResult['success'] ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i><span>SMTP Diagnostic {{ $testResult['success'] ? 'Passed' : 'Failed' }}</span></div>
            <div class="mt-1 grid grid-cols-2 sm:grid-cols-5 gap-2 font-mono text-[10px]"><span>Host: {{ $testResult['host'] }}</span><span>Port: {{ $testResult['port'] }}</span><span>Encryption: {{ strtoupper($testResult['encryption']) }}</span><span>Latency: {{ $testResult['latency'] }}</span><span>To: {{ $testResult['recipient'] }}</span></div>
        </div>
    @endif

    <!-- Active Mail Engine Summary Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs">
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">SMTP Server</span>
            <span class="text-xs font-bold text-slate-800 font-mono">{{ $settings['mail_host'] ?? 'mail.somitysoft.com' }}:{{ $settings['mail_port'] ?? '465' }}</span>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs">
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Encryption</span>
            <span class="text-xs font-bold text-slate-800 font-mono uppercase">{{ $settings['mail_encryption'] ?? 'SSL' }}</span>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs">
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Active Billing Mailbox</span>
            <span class="text-xs font-bold text-blue-600 font-mono">{{ $settings['billing_email'] ?? $settings['mail_username'] ?? 'billing@somitysoft.com' }}</span>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs">
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Sender Display</span>
            <span class="text-xs font-bold text-slate-800 truncate block">{{ $settings['mail_from_name'] ?? 'SomitySoft Billing' }}</span>
        </div>
    </div>

    <!-- MAIN DOMAIN MAIL CONFIGURATION FORM -->
    <form action="{{ route('owner.mail-settings.update') }}" method="POST" class="space-y-4" @submit="saving=true">
        @csrf

        <!-- Card 1: Domain SMTP Server Parameters -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                <div class="flex items-center gap-2">
                    <i class="fas fa-server text-blue-600 text-xs"></i>
                    <span class="text-xs font-bold text-slate-800">Domain SMTP Connection</span>
                </div>
                <div class="flex items-center gap-1.5 text-[10.5px]">
                    <span class="text-slate-400">Quick Switch:</span>
                    <button type="button" @click="applyDomainPreset('mail.somitysoft.com', '465', 'ssl', 'billing@somitysoft.com')" class="px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold border border-blue-200 transition">
                        Port 465 (SSL)
                    </button>
                    <button type="button" @click="applyDomainPreset('mail.somitysoft.com', '587', 'tls', 'billing@somitysoft.com')" class="px-2 py-0.5 rounded bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium border border-slate-200 transition">
                        Port 587 (TLS)
                    </button>
                    <button type="button" @click="applyDomainPreset('127.0.0.1', '25', 'none', '')" class="px-2 py-0.5 rounded bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium border border-slate-200 transition">
                        Port 25 (Local)
                    </button>
                </div>
            </div>

            <div class="p-4 space-y-3.5 text-xs">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">SMTP Server Host</label>
                        <input type="text" name="mail_host" x-ref="mailHost" value="{{ $settings['mail_host'] ?? 'mail.somitysoft.com' }}" placeholder="mail.somitysoft.com" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        <p class="text-[10px] text-slate-400 mt-1">Domain webmail server: <code class="text-blue-600">mail.somitysoft.com</code> or local loopback <code class="text-slate-600">127.0.0.1</code></p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Mailer Protocol</label>
                        <select name="mail_mailer" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                            <option value="smtp" {{ ($settings['mail_mailer'] ?? 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP Protocol (Standard)</option>
                            <option value="sendmail" {{ ($settings['mail_mailer'] ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail (Linux Local)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">SMTP Port</label>
                        <input type="number" name="mail_port" x-ref="mailPort" value="{{ $settings['mail_port'] ?? '465' }}" placeholder="465" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        <p class="text-[10px] text-slate-400 mt-1">SSL: <strong>465</strong> | TLS: <strong>587</strong> | Direct: <strong>25</strong></p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Encryption Protocol</label>
                        <select name="mail_encryption" x-ref="mailEnc" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                            <option value="ssl" {{ ($settings['mail_encryption'] ?? 'ssl') === 'ssl' ? 'selected' : '' }}>SSL (Recommended for Port 465)</option>
                            <option value="tls" {{ ($settings['mail_encryption'] ?? '') === 'tls' ? 'selected' : '' }}>TLS / STARTTLS (Port 587)</option>
                            <option value="none" {{ ($settings['mail_encryption'] ?? '') === 'none' ? 'selected' : '' }}>None / Plaintext (Port 25)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Connection Timeout (Seconds)</label>
                        <input type="number" name="mail_timeout" value="{{ $settings['mail_timeout'] ?? '15' }}" placeholder="15" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Official Domain Mailboxes & Authentication -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                <div class="flex items-center gap-2">
                    <i class="fas fa-user-lock text-indigo-600 text-xs"></i>
                    <span class="text-xs font-bold text-slate-800">Domain Mailbox Authentication</span>
                </div>
                <span class="text-[10px] text-slate-400">cPanel / Plesk Email Account Credentials</span>
            </div>

            <div class="p-4 space-y-3.5 text-xs">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1 flex items-center justify-between">
                            <span>SMTP Username / Login Email</span>
                            <span class="text-[9.5px] font-semibold text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded font-mono">billing@somitysoft.com</span>
                        </label>
                        <input type="text" name="mail_username" x-ref="mailUser" value="{{ $settings['mail_username'] ?? 'billing@somitysoft.com' }}" placeholder="billing@somitysoft.com" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        <p class="text-[10px] text-slate-400 mt-1">Full email address created in hosting control panel</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Mailbox Account Password</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="mail_password" value="{{ $settings['mail_password'] ?? '' }}" placeholder="Enter mailbox password" class="w-full pl-2.5 pr-8 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 text-xs">
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Password for this email mailbox on mail.somitysoft.com</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="mail_verify_peer" value="1" {{ ($settings['mail_verify_peer'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-[11px] font-semibold text-slate-700">Validate SSL Certificate (Disable if using self-signed certificate)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Card 3: Sender Identity & Addresses -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                <div class="flex items-center gap-2">
                    <i class="fas fa-id-card text-emerald-600 text-xs"></i>
                    <span class="text-xs font-bold text-slate-800">Sender Identity & Contact Addresses</span>
                </div>
                <span class="text-[10px] text-slate-400">Displayed on Client Invoices</span>
            </div>

            <div class="p-4 space-y-3.5 text-xs">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Official Billing Email (Invoices)</label>
                        <input type="email" name="billing_email" value="{{ $settings['billing_email'] ?? 'billing@somitysoft.com' }}" placeholder="billing@somitysoft.com" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-medium">
                        <p class="text-[10px] text-slate-400 mt-1">Used as sender when dispatching invoice emails</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Sender Display Name</label>
                        <input type="text" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? 'SomitySoft Billing' }}" placeholder="SomitySoft Billing" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                        <p class="text-[10px] text-slate-400 mt-1">Company name shown in client inbox</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Official Support / Reply-To Email</label>
                        <input type="email" name="mail_reply_to" value="{{ $settings['mail_reply_to'] ?? 'support@somitysoft.com' }}" placeholder="support@somitysoft.com" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                        <p class="text-[10px] text-slate-400 mt-1">Where clients reply when receiving emails</p>
                    </div>
                </div>

                <input type="hidden" name="mail_from_address" value="{{ $settings['billing_email'] ?? 'billing@somitysoft.com' }}">
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-1">
            <button type="submit" :disabled="saving" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas text-[11px]" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                <span x-text="saving ? 'Saving...' : 'Save Mail & SMTP Configuration'"></span>
            </button>
        </div>
    </form>

    <!-- Card 4: Live Diagnostic Test Dispatcher -->
    <div id="test-section" class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 mt-6">
        <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
            <div class="flex items-center gap-2">
                <i class="fas fa-paper-plane text-emerald-600 text-xs"></i>
                <span class="text-xs font-bold text-slate-800">Live Diagnostic Test Dispatcher</span>
            </div>
            <span class="text-[10px] text-slate-400">Verifies SMTP handshake & delivery</span>
        </div>

        <div class="p-4 space-y-3 text-xs">
            <p class="text-slate-600 text-[11.5px] leading-relaxed">
                Send a test email to verify that your domain email credentials can establish an authenticated connection with <strong class="text-slate-800 font-mono">{{ $settings['mail_host'] ?? 'mail.somitysoft.com' }}</strong>.
            </p>

            <form action="{{ route('owner.mail-settings.test') }}" method="POST" class="flex flex-col sm:flex-row items-center gap-3" @submit="testing=true">
                @csrf
                <div class="flex-1 w-full">
                    <input type="email" name="test_email" value="{{ $settings['support_email'] ?? 'salzarrahman84@gmail.com' }}" placeholder="Enter destination email (e.g. salzarrahman84@gmail.com)" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-medium">
                </div>
                <button type="submit" :disabled="testing" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer whitespace-nowrap">
                    <i class="fas text-[10px]" :class="testing ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                    <span x-text="testing ? 'Sending...' : 'Send Test Email'"></span>
                </button>
            </form>
        </div>
    </div>

    <section class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-3.5 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between gap-2"><span class="text-xs font-bold text-slate-800"><i class="fas fa-clock-rotate-left text-blue-600 mr-1.5"></i>SMTP Test History</span><div class="flex items-center gap-1.5"><form method="GET" class="flex"><select name="test_status" onchange="this.form.submit()" class="h-[30px] px-2 rounded-lg border border-slate-200 bg-white text-[10px]"><option value="">All</option><option value="passed" @selected(request('test_status') === 'passed')>Passed</option><option value="failed" @selected(request('test_status') === 'failed')>Failed</option></select></form><a href="{{ route('owner.mail-settings.test-history.export') }}" class="h-[30px] px-2 rounded-lg bg-slate-100 text-slate-700 text-[10px] flex items-center">Export</a><form method="POST" action="{{ route('owner.mail-settings.test-history.clear') }}" onsubmit="return confirm('Clear SMTP test history?')">@csrf @method('DELETE')<button class="h-[30px] px-2 rounded-lg bg-rose-50 text-rose-700 text-[10px]">Clear</button></form></div></div>
        <div class="overflow-x-auto"><table class="saas-table"><thead><tr><th class="text-center">Recipient</th><th class="text-center">Server</th><th class="text-center">Port</th><th class="text-center">Encryption</th><th class="text-center">Latency</th><th class="text-center">Status</th><th class="text-center">Tested At</th></tr></thead><tbody>@forelse($mailTestLogs as $log)<tr><td class="text-center">{{ $log->recipient }}</td><td class="text-center font-mono">{{ $log->host }}</td><td class="text-center">{{ $log->port }}</td><td class="text-center uppercase">{{ $log->encryption }}</td><td class="text-center">{{ $log->latency_ms !== null ? $log->latency_ms . ' ms' : '—' }}</td><td class="text-center">@if($log->success)<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px]">Passed</span>@else<span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 text-[10px]">Failed</span>@endif</td><td class="text-center text-slate-500">{{ $log->tested_at?->format('d M Y, h:i A') }}</td></tr>@empty<tr><td colspan="7" class="py-8 text-center text-xs text-slate-400">No SMTP tests recorded yet.</td></tr>@endforelse</tbody></table></div>
        @if($mailTestLogs->hasPages())<div class="p-3 bg-slate-50/50">{{ $mailTestLogs->links() }}</div>@endif
    </section>

</div>
@endsection
