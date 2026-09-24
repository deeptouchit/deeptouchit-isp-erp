<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SomitySoft SaaS — SMTP Test Email</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #1e293b; }
        .container { max-width: 580px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background: linear-gradient(135deg, #1e40af, #3b82f6); padding: 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; }
        .header p { margin: 4px 0 0 0; font-size: 12px; opacity: 0.85; }
        .content { padding: 28px; }
        .badge { display: inline-block; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; margin-bottom: 16px; }
        .info-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 12px; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #edf2f7; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-weight: 500; }
        .info-value { font-weight: 600; color: #0f172a; font-family: monospace; }
        .footer { background-color: #f8fafc; padding: 18px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SomitySoft SaaS Platform</h1>
            <p>Enterprise Multi-Tenant ISP & Billing Infrastructure</p>
        </div>
        <div class="content">
            <span class="badge">✓ SMTP Dispatch Verified</span>
            <h2 style="font-size: 16px; margin-top: 0; color: #0f172a;">SMTP Connection Test Succeeded</h2>
            <p style="font-size: 13px; line-height: 1.6; color: #475569;">
                This email confirms that your outgoing SMTP mail server configuration is fully operational and capable of delivering transaction notices, invoices, and password reset tokens to your users.
            </p>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">SMTP Host:</span>
                    <span class="info-value">{{ $details['host'] ?? 'mail.somitysoft.com' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Port & Encryption:</span>
                    <span class="info-value">{{ $details['port'] ?? 587 }} ({{ strtoupper($details['encryption'] ?? 'TLS') }})</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sender Address:</span>
                    <span class="info-value">{{ $details['from_address'] ?? 'noreply@somitysoft.com' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Recipient:</span>
                    <span class="info-value">{{ $details['recipient'] ?? 'Administrator' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Dispatch Latency:</span>
                    <span class="info-value">{{ $details['latency'] ?? '120ms' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Timestamp:</span>
                    <span class="info-value">{{ date('d M Y, h:i:s A') }}</span>
                </div>
            </div>

            <p style="font-size: 12px; color: #64748b; line-height: 1.5;">
                If you did not initiate this test, please review your SaaS Platform Owner security settings immediately.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SomitySoft SaaS. All rights reserved. • Automated System Dispatcher
        </div>
    </div>
</body>
</html>
