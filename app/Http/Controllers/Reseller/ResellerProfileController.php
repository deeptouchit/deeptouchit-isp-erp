<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantReseller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
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
        if (!$reseller && $user->reseller_id) {
            $reseller = TenantReseller::find($user->reseller_id);
        }
        if (!$reseller && $user->tenant_id) {
            $reseller = TenantReseller::where('tenant_id', $user->tenant_id)->first();
        }
        if (!$reseller) {
            $reseller = TenantReseller::first();
        }

        $tenant = $reseller?->tenant ?? ($user->tenant ?? Tenant::first());

        return [$user, $reseller, $tenant];
    }

    /**
     * Display the Reseller Partner Profile & Settings Page.
     */
    public function index(Request $request): View
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $resellerId = $reseller?->id;
        $activeSubscribersCount = $resellerId 
            ? TenantCustomer::where('reseller_id', $resellerId)->where('status', 'active')->count() 
            : 0;

        $walletBal = (float) ($reseller?->wallet_balance ?? 0);
        $creditLim = (float) ($reseller?->credit_limit ?? 0);
        $availableBalance = (float) ($reseller?->total_available_balance ?? ($walletBal + $creditLim));

        $isResellerAdmin = $user->isResellerAdmin();

        if ($isResellerAdmin) {
            // 6 Summary Metric Cards for Reseller Admin (AGENTS.md Rule 2.B)
            $stats = [
                'commission_rate' => (float) ($reseller?->commission_rate ?? 0),
                'billing_type' => $reseller?->billing_type ? str_replace('_', ' ', $reseller->billing_type) : 'Prepaid Wallet',
                'active_subscribers' => $activeSubscribersCount,
                'wallet_balance' => $walletBal,
                'available_balance' => $availableBalance,
                'partner_code' => $reseller?->code ?: ($reseller?->prefix ?: 'RES-001'),
                'status' => strtoupper($reseller?->status ?? 'ACTIVE'),
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
                'staff_id' => $user->employee_id ?: ($user->username ?: 'STF-' . str_pad($user->id, 4, '0', STR_PAD_LEFT)),
                'role_title' => $roleTitle,
                'mobile' => $user->mobile ?: ($user->phone ?: 'N/A'),
                'email' => $user->email,
                'joined_date' => $user->created_at ? $user->created_at->format('d M, Y') : date('d M, Y'),
                'status' => 'ACTIVE',
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

        if ($isResellerAdmin) {
            if (!$reseller) {
                return back()->with('error', 'রিসেলার অ্যাকাউন্ট রেকর্ড পাওয়া যায়নি।');
            }

            $validated = $request->validate([
                'contact_person' => ['required', 'string', 'max:150'],
                'mobile' => ['required', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:100'],
                'address' => ['nullable', 'string', 'max:500'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'user_name' => ['required', 'string', 'max:100'],
                'user_email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
                'user_mobile' => ['nullable', 'string', 'max:30'],
                'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            ]);

            // Update Reseller Entity
            $reseller->update([
                'contact_person' => $validated['contact_person'],
                'mobile' => $validated['mobile'],
                'email' => $validated['email'],
                'address' => $validated['address'],
                'notes' => $validated['notes'],
            ]);
        } else {
            $validated = $request->validate([
                'user_name' => ['required', 'string', 'max:100'],
                'user_email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
                'user_mobile' => ['nullable', 'string', 'max:30'],
                'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            ]);
        }

        // Handle Avatar upload / removal
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        } elseif ($request->boolean('remove_avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
        }

        // Update Current User
        $user->name = $validated['user_name'];
        $user->email = $validated['user_email'];
        $user->mobile = $validated['user_mobile'];
        $user->phone = $validated['user_mobile'];
        $user->save();

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

        return back()->with('success', 'প্রোফাইল তথ্য সফলভাবে আপডেট হয়েছে।');
    }

    /**
     * Update Reseller Account Password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.current_password' => 'আপনার বর্তমান পাসওয়ার্ডটি সঠিক নয়।',
            'password.confirmed' => 'নতুন পাসওয়ার্ড নিশ্চিতকরণ মেলেনি।',
            'password.min' => 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

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

        return back()->with('success', 'আপনার লগইন পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।');
    }

    /**
     * Export Reseller Profile Summary as CSV.
     */
    public function exportProfileCsv(): StreamedResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        if (!$user->isResellerAdmin()) {
            abort(403, 'অননুমোদিত অ্যাক্সেস।');
        }

        $fileName = 'reseller_profile_' . ($reseller->code ?? 'PARTNER') . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($user, $reseller, $tenant) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ATTRIBUTE', 'VALUE']);
            fputcsv($handle, ['Partner Name', $reseller->name ?? 'N/A']);
            fputcsv($handle, ['Partner Code', $reseller->code ?? 'N/A']);
            fputcsv($handle, ['Subnet Prefix', $reseller->prefix ?? 'N/A']);
            fputcsv($handle, ['Contact Person', $reseller->contact_person ?? 'N/A']);
            fputcsv($handle, ['Official Mobile', $reseller->mobile ?? 'N/A']);
            fputcsv($handle, ['Official Email', $reseller->email ?? 'N/A']);
            fputcsv($handle, ['Billing Type', $reseller->billing_type ?? 'PREPAID_WALLET']);
            fputcsv($handle, ['Wallet Balance', number_format((float)($reseller->wallet_balance ?? 0), 2)]);
            fputcsv($handle, ['Credit Limit', number_format((float)($reseller->credit_limit ?? 0), 2)]);
            fputcsv($handle, ['Commission Rate (%)', number_format((float)($reseller->commission_rate ?? 0), 2)]);
            fputcsv($handle, ['Monthly Panel Charge', number_format((float)($reseller->monthly_panel_charge ?? 0), 2)]);
            fputcsv($handle, ['Panel Expiry Date', $reseller->panel_expiry_date ? $reseller->panel_expiry_date->format('Y-m-d') : 'N/A']);
            fputcsv($handle, ['Status', strtoupper($reseller->status ?? 'ACTIVE')]);
            fputcsv($handle, ['Host ISP Provider', $tenant->company_name ?? 'N/A']);
            fputcsv($handle, ['Exported Date', now()->toDateTimeString()]);
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
            abort(403, 'অননুমোদিত অ্যাক্সেস।');
        }

        return view('reseller.profile.certificate', compact('user', 'reseller', 'tenant'));
    }
}
