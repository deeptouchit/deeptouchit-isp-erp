@component('mail::message')
# Welcome to {{ config('app.name') }}!

Dear {{ $user->first_name ?? $user->username }},

Your hosting account has been successfully created and is ready for use.

## Account Details
- **Domain:** {{ $subscription->domain }}
- **Username:** {{ $subscription->username }}
- **Plan:** {{ $subscription->plan->name ?? 'Default Plan' }}
- **PHP Version:** {{ $subscription->php_version ?? '8.2' }}
- **Expires At:** {{ $subscription->expires_at ? $subscription->expires_at->format('M d, Y') : 'N/A' }}

## FTP Details
- **Host:** ftp.{{ $subscription->domain }}
- **Username:** {{ $subscription->username }}
- **Password:** {{ $password ?? 'Configured in panel' }}

## Database Details
- **Database Name:** db_{{ $subscription->username }}
- **Database User:** usr_{{ $subscription->username }}
- **Database Password:** {{ $db_password ?? 'Configured in panel' }}

## Control Panel Access
You can manage your website, files, databases, and emails from your control panel:

@component('mail::button', ['url' => url('/login')])
Login to Control Panel
@endcomponent

If you have any questions or need assistance, feel free to submit a ticket to our support team.

Thanks,<br>
**{{ config('app.name') }} Team**
@endcomponent
