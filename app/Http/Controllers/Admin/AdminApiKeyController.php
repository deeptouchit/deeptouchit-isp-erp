<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminApiKeyController extends Controller
{
    /**
     * Display REST API keys and authentication tokens.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = ApiKey::with('user:id,first_name,last_name,email,role,username');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key_prefix', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $apiKeys = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $all = ApiKey::all();
        $activeKeys = $all->where('status', 'active')->count();
        $revokedCount = $all->whereIn('status', ['revoked', 'expired'])->count();
        $ipLockedCount = $all->filter(fn($k) => !empty($k->ip_allowlist) && count($k->ip_allowlist) > 0)->count();
        $totalRequests = ActivityLog::where('action', 'like', 'api_%')->count();

        $stats = [
            'total_keys' => $all->count(),
            'active_keys' => $activeKeys,
            'revoked_count' => $revokedCount,
            'ip_locked_count' => $ipLockedCount,
            'total_requests' => $totalRequests,
        ];

        return Inertia::render('Admin/Api/Keys/Index', [
            'apiKeys' => $apiKeys,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
            'newKeyToken' => session('generated_token'),
            'newKeyName' => session('key_name'),
        ]);
    }

    /**
     * Store a newly created API key in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['nullable', 'array'],
            'ip_allowlist' => ['nullable', 'array'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:10', 'max:1000'],
            'expires_days' => ['nullable', 'integer', 'min:1'],
        ]);

        // Generate 48-char cryptographically secure token
        $rawSecret = 'sh_live_' . bin2hex(random_bytes(20));
        $prefix = substr($rawSecret, 0, 14) . '...';
        $expiresAt = !empty($validated['expires_days']) ? now()->addDays((int)$validated['expires_days']) : null;

        $apiKey = ApiKey::create([
            'user_id' => auth()->id() ?: 1,
            'name' => $validated['name'],
            'key_prefix' => $prefix,
            'secret_hash' => Hash::make($rawSecret),
            'abilities' => $validated['abilities'] ?? ['*'],
            'ip_allowlist' => $validated['ip_allowlist'] ?? [],
            'rate_limit_per_minute' => $validated['rate_limit_per_minute'] ?? 60,
            'status' => 'active',
            'expires_at' => $expiresAt,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_key_created',
            'description' => "Created API Key '{$apiKey->name}' [Prefix: {$prefix}].",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [
                'name' => $apiKey->name,
                'prefix' => $prefix,
                'abilities' => $apiKey->abilities,
            ],
        ]);

        return redirect()->route('admin.api.keys')
            ->with('success', "API Key '{$apiKey->name}' created successfully.")
            ->with('generated_token', $rawSecret)
            ->with('key_name', $apiKey->name);
    }

    /**
     * Update existing API key settings.
     */
    public function update(Request $request, ApiKey $apiKey): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['nullable', 'array'],
            'ip_allowlist' => ['nullable', 'array'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:10', 'max:1000'],
            'status' => ['required', 'in:active,revoked,expired'],
        ]);

        $oldValues = $apiKey->only(['name', 'abilities', 'ip_allowlist', 'rate_limit_per_minute', 'status']);
        $apiKey->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_key_updated',
            'description' => "Updated API Key '{$apiKey->name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $oldValues,
            'new_values' => $apiKey->only(['name', 'abilities', 'ip_allowlist', 'rate_limit_per_minute', 'status']),
        ]);

        return redirect()->route('admin.api.keys')
            ->with('success', "API Key '{$apiKey->name}' updated successfully.");
    }

    /**
     * Regenerate secret token for API key.
     */
    public function regenerate(Request $request, ApiKey $apiKey): RedirectResponse
    {
        $this->authorize('create', User::class);

        $rawSecret = 'sh_live_' . bin2hex(random_bytes(20));
        $prefix = substr($rawSecret, 0, 14) . '...';

        $apiKey->update([
            'key_prefix' => $prefix,
            'secret_hash' => Hash::make($rawSecret),
            'status' => 'active',
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_key_regenerated',
            'description' => "Regenerated secret token for API Key '{$apiKey->name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['name' => $apiKey->name, 'prefix' => $prefix],
        ]);

        return redirect()->route('admin.api.keys')
            ->with('success', "API Key '{$apiKey->name}' regenerated.")
            ->with('generated_token', $rawSecret)
            ->with('key_name', $apiKey->name);
    }

    /**
     * Revoke API key access immediately.
     */
    public function revoke(Request $request, ApiKey $apiKey): RedirectResponse
    {
        $this->authorize('create', User::class);

        $apiKey->update(['status' => 'revoked']);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_key_revoked',
            'description' => "Revoked API Key '{$apiKey->name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'revoked'],
        ]);

        return redirect()->route('admin.api.keys')
            ->with('success', "API Key '{$apiKey->name}' has been revoked.");
    }

    /**
     * Delete API key permanently.
     */
    public function destroy(Request $request, ApiKey $apiKey): RedirectResponse
    {
        $this->authorize('create', User::class);

        $name = $apiKey->name;
        $apiKey->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_key_deleted',
            'description' => "Permanently deleted API Key '{$name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['name' => $name],
            'new_values' => [],
        ]);

        return redirect()->route('admin.api.keys')
            ->with('success', "API Key '{$name}' deleted permanently.");
    }
}
