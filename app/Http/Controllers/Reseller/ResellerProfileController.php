<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerProfileController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        abort_unless($reseller && $user->tenant_id && (int) $reseller->tenant_id === (int) $user->tenant_id, 403);

        return [$user, $reseller, $reseller->tenant];
    }

    /**
     * Display the Reseller Partner Profile & Settings Page.
     */
    public function index(Request $request): View
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $isResellerAdmin = $user->isResellerAdmin();
        $activeSubscribersCount = $isResellerAdmin
            ? TenantCustomer::where('tenant_id', $reseller->tenant_id)->where('reseller_id', $reseller->id)
                ->where('status', 'active')->count()
            : 0;

        $walletBal = (float) ($reseller?->wallet_balance ?? 0);
        $creditLim = (float) ($reseller?->credit_limit ?? 0);
        $availableBalance = (float) ($reseller?->total_available_balance ?? ($walletBal + $creditLim));

        if ($isResellerAdmin) {
            // 6 Summary Metric Cards for Reseller Admin (AGENTS.md Rule 2.B)
            $stats = [
                'commission_rate' => (float) ($reseller?->commission_rate ?? 0),
                'billing_type' => __($reseller->billing_badge['label']),
                'active_subscribers' => $activeSubscribersCount,
                'wallet_balance' => $walletBal,
                'available_balance' => $availableBalance,
                'partner_code' => $reseller?->code ?: ($reseller?->prefix ?: __('N/A')),
                'status' => __(ucfirst($reseller->status ?? 'active')),
            ];
        } else {
            // 6 Summary Metric Cards for Staff / Collector (AGENTS.md Rule 2.B)
            $roleTitle = match($user->role) {
                'reseller_collector', 'collector', 'isp_collector' => 'Bill Collector',
                'reseller_technician', 'technician', 'isp_technician' => 'Technician',
                'reseller_operator' => 'Operator',
                'reseller_staff', 'staff' => 'Staff',
                default => ucwords(str_replace('_', ' ', $user->role ?? 'Staff'))
            };

            $stats = [
                'staff_id' => $user->staff_id ?: ($user->employee_id ?: ($user->username ?: 'STF-' . str_pad($user->id, 4, '0', STR_PAD_LEFT))),
                'role_title' => __($roleTitle),
                'mobile' => $user->mobile ?: ($user->phone ?: 'N/A'),
                'email' => $user->email,
                'joined_date' => $user->created_at ? $user->created_at->format('d M, Y') : date('d M, Y'),
                'status' => __(ucfirst($user->status ?? 'active')),
            ];
        }

        return view('reseller.profile.index', compact('user', 'reseller', 'tenant', 'stats', 'isResellerAdmin'));
    }

    /**
     * Update Reseller Business Profile Information.
     */
    public function update(Request $request): RedirectResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();
        $isResellerAdmin = $user->isResellerAdmin();

        $rules = [
            'user_name' => ['required', 'string', 'max:100'],
            'user_email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'user_mobile' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
        if ($isResellerAdmin) {
            $rules += [
                'contact_person' => ['required', 'string', 'max:150'],
                'mobile' => ['required', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:100'],
                'address' => ['nullable', 'string', 'max:500'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ];
        }
        $validated = $request->validateWithBag('profile', $rules);

        $newAvatar = null;
        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');
            if (!$newAvatar) {
                return back()->withInput()->withErrors(['avatar' => __('Unable to upload the profile photo.')], 'profile');
            }
        }

        try {
            $previousAvatar = DB::transaction(function () use ($user, $reseller, $isResellerAdmin, $validated, $newAvatar) {
                $account = User::query()->where('tenant_id', $reseller->tenant_id)->where('reseller_id', $reseller->id)
                    ->lockForUpdate()->findOrFail($user->id);
                if ($isResellerAdmin) {
                    abort_unless($account->isResellerAdmin(), 403);
                    $reseller->update(array_intersect_key($validated, array_flip(['contact_person', 'mobile', 'email', 'address', 'notes'])));
                }

                $previous = $account->avatar;
                $account->name = $validated['user_name'];
                if ($account->email !== $validated['user_email']) {
                    $account->email = $validated['user_email'];
                    $account->email_verified_at = null;
                }
                if (array_key_exists('user_mobile', $validated)) {
                    $account->mobile = $validated['user_mobile'];
                    $account->phone = $validated['user_mobile'];
                }
                if ($newAvatar || !empty($validated['remove_avatar'])) {
                    $account->avatar = $newAvatar;
                }
                $account->save();

                return $previous !== $account->avatar ? $previous : null;
            });
        } catch (\Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }

            throw $exception;
        }

        if ($previousAvatar && str_starts_with($previousAvatar, 'avatars/')) {
            Storage::disk('public')->delete($previousAvatar);
        }

        // Activity Log
        try {
            TenantActivityLog::create([
                'tenant_id' => $tenant?->id,
                'actor_type' => 'reseller',
                'actor_id' => $user->id,
                'actor_name' => $user->name,
                'event_type' => 'profile_updated',
                'description' => $isResellerAdmin 
                    ? "Reseller partner '{$reseller->name}' updated profile and business information."
                    : "User '{$user->name}' updated personal profile information.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Ignore log failure
        }

        return back()->with('success', __('Profile updated.'));
    }

    /**
     * Update Reseller Account Password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        [$user, $reseller] = $this->getResellerData();
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ]);

        $account = DB::transaction(function () use ($user, $reseller, $validated) {
            $account = User::query()->where('tenant_id', $reseller->tenant_id)->where('reseller_id', $reseller->id)
                ->lockForUpdate()->findOrFail($user->id);
            if (!Hash::check($validated['current_password'], $account->password)) {
                throw ValidationException::withMessages(['current_password' => __('The current password is incorrect.')])->errorBag('password');
            }

            $account->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            return $account;
        });

        Auth::setUser($account);
        $request->session()->regenerate(true);

        // Activity Log
        try {
            TenantActivityLog::create([
                'tenant_id' => $user->tenant_id,
                'actor_type' => 'reseller',
                'actor_id' => $user->id,
                'actor_name' => $user->name,
                'event_type' => 'password_changed',
                'description' => "Reseller user '{$user->name}' changed account login password.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Ignore
        }

        return back()->with('success', __('Password changed.'));
    }

    /**
     * Export Reseller Profile Summary as CSV.
     */
    public function exportProfileCsv(): StreamedResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        if (!$user->isResellerAdmin()) {
            abort(403);
        }

        $fileName = 'reseller_profile_' . (Str::slug($reseller->code ?? '') ?: 'partner') . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($reseller, $tenant) {
            $handle = fopen('php://output', 'w');
            $write = static function (array $row) use ($handle): void {
                $row = array_map(static fn ($value) => is_string($value) && preg_match('/^(?:\s*[=+@-]|[\t\r\n])/', $value)
                    ? "'" . $value : $value, $row);
                fputcsv($handle, $row, escape: '');
            };

            $write(['ATTRIBUTE', 'VALUE']);
            $write(['Partner Name', $reseller->name ?? 'N/A']);
            $write(['Partner Code', $reseller->code ?? 'N/A']);
            $write(['Subnet Prefix', $reseller->prefix ?? 'N/A']);
            $write(['Contact Person', $reseller->contact_person ?? 'N/A']);
            $write(['Official Mobile', $reseller->mobile ?? 'N/A']);
            $write(['Official Email', $reseller->email ?? 'N/A']);
            $write(['Billing Type', $reseller->billing_type ?? 'PREPAID_WALLET']);
            $write(['Wallet Balance', number_format((float)($reseller->wallet_balance ?? 0), 2)]);
            $write(['Credit Limit', number_format((float)($reseller->credit_limit ?? 0), 2)]);
            $write(['Commission Rate (%)', number_format((float)($reseller->commission_rate ?? 0), 2)]);
            $write(['Monthly Panel Charge', number_format((float)($reseller->monthly_panel_charge ?? 0), 2)]);
            $write(['Panel Expiry Date', $reseller->panel_expiry_date ? $reseller->panel_expiry_date->format('Y-m-d') : 'N/A']);
            $write(['Status', strtoupper($reseller->status ?? 'ACTIVE')]);
            $write(['Host ISP Provider', $tenant->company_name ?? 'N/A']);
            $write(['Exported Date', now()->toDateTimeString()]);
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Print Official Partner Agreement / Authorization Certificate.
     */
    public function printCertificate(): View
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        if (!$user->isResellerAdmin()) {
            abort(403);
        }

        return view('reseller.profile.certificate', compact('user', 'reseller', 'tenant'));
    }
}
