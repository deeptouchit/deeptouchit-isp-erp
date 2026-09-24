<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PHPSettingsController extends Controller
{
    public function index(Subscription $subscription): Response
    {
        return Inertia::render('Client/PHP/Settings', [
            'subscription' => $subscription,
            'availableVersions' => config('panel.hosting.php_versions', ['8.1', '8.2', '8.3', '8.5'])
        ]);
    }

    public function updateVersion(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'php_version' => 'required|string|in:8.1,8.2,8.3,8.5',
        ]);

        $subscription = Subscription::findOrFail($validated['subscription_id']);
        $subscription->update(['php_version' => $validated['php_version']]);

        return redirect()->back()->with('success', 'PHP version updated.');
    }

    public function updateIni(Request $request)
    {
        return redirect()->back()->with('success', 'PHP INI settings saved.');
    }
}
