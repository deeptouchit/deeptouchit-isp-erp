<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\SSLManager;
use Illuminate\Http\Request;

class SSLController extends Controller
{
    public function generate(Website $website, SSLManager $sslManager)
    {
        $result = $sslManager->generateSSL($website->domain, auth()->user()->email);
        if ($result['success']) {
            $website->update([
                'ssl_status' => 'active',
                'ssl_last_renewed_at' => now(),
                'ssl_expires_at' => now()->addDays(90)
            ]);
            return redirect()->back()->with('success', 'SSL certificate generated successfully.');
        }

        return redirect()->back()->withErrors(['error' => $result['error']]);
    }

    public function renew(Website $website, SSLManager $sslManager)
    {
        $result = $sslManager->renewSSL();
        return redirect()->back()->with('success', 'SSL renewal triggered.');
    }

    public function revoke(Website $website, SSLManager $sslManager)
    {
        $sslManager->revokeSSL($website->domain);
        $website->update(['ssl_status' => 'none']);
        return redirect()->back()->with('success', 'SSL certificate revoked.');
    }
}
