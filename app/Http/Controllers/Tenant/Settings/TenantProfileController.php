<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TenantProfileController extends Controller
{
    /**
     * Get Current Active Tenant Workspace
     */
    protected function getTenant(): Tenant
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $tenant = $tenantId ? Tenant::find($tenantId) : (auth()->user()?->tenant ?? Tenant::first());

        if (!$tenant) {
            abort(403, 'Tenant context not found. Please log in.');
        }

        return $tenant;
    }

    /**
     * Display ISP Company Profile & Corporate Branding Settings
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();

        // 6 KPI Overview Cards for Settings Summary
        $stats = [
            'license_type' => $tenant->btrc_license_type ?: 'Nationwide ISP',
            'btrc_status' => !empty($tenant->btrc_license_no) ? 'Verified' : 'Pending',
            'subscription_status' => ucfirst($tenant->status ?? 'Active'),
            'trade_license' => !empty($tenant->trade_license_no) ? 'Registered' : 'N/A',
            'support_line' => $tenant->support_hotline ? 'Active' : 'Not Set',
            'plan_name' => $tenant->plan?->name ?? 'Active Plan',
        ];

        return view('tenant.settings.profile', compact('tenant', 'stats'));
    }

    /**
     * Update ISP Corporate Profile & Branding Details
     */
    public function update(Request $request)
    {
        $tenant = $this->getTenant();

        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'support_hotline' => 'nullable|string|max:100',
            'billing_phone' => 'nullable|string|max:50',
            'billing_email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'required|string|max:500',
            'btrc_license_no' => 'nullable|string|max:100',
            'btrc_license_type' => 'nullable|string|max:100',
            'trade_license_no' => 'nullable|string|max:100',
            'tin_bin_no' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_designation' => 'nullable|string|max:255',
            'mail_driver' => 'nullable|string|max:50',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|max:50',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'mail_enabled' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,ico,webp|max:1024',
        ]);

        $data = $request->only([
            'name',
            'company_name',
            'phone',
            'email',
            'support_hotline',
            'billing_phone',
            'billing_email',
            'website',
            'address',
            'btrc_license_no',
            'btrc_license_type',
            'trade_license_no',
            'tin_bin_no',
            'contact_person',
            'contact_person_designation',
            'mail_driver',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ]);

        $data['mail_enabled'] = $request->has('mail_enabled') ? (bool) $request->mail_enabled : false;

        if ($request->filled('mail_password')) {
            $data['mail_password'] = $request->mail_password;
        }

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $logoPath = $request->file('logo')->store('tenants/logos', 'public');
            $data['logo'] = $logoPath;
        }

        // Handle Favicon Upload
        if ($request->hasFile('favicon')) {
            if ($tenant->favicon && Storage::disk('public')->exists($tenant->favicon)) {
                Storage::disk('public')->delete($tenant->favicon);
            }
            $faviconPath = $request->file('favicon')->store('tenants/favicons', 'public');
            $data['favicon'] = $faviconPath;
        }

        $tenant->update($data);

        // Record Audit Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => auth()->id() ?? 1,
            'actor_name' => auth()->user()?->name ?? 'Admin',
            'event_type' => 'PROFILE_SETTINGS_UPDATED',
            'description' => 'Updated Company Profile & Email Gateway Settings',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Company profile and email settings updated successfully.');
    }

    /**
     * Test SMTP Email Connection
     */
    public function testEmailConnection(Request $request, \App\Services\Email\TenantMailService $mailService)
    {
        $tenant = $this->getTenant();
        $testEmail = $request->input('test_email', $tenant->billing_email ?: $tenant->email);

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid test recipient email address.',
            ], 422);
        }

        // If user submitted form values to test immediately without saving
        if ($request->filled('mail_host')) {
            $tenant->mail_host = $request->mail_host;
            $tenant->mail_port = $request->mail_port ?: 587;
            $tenant->mail_username = $request->mail_username;
            if ($request->filled('mail_password')) {
                $tenant->mail_password = $request->mail_password;
            }
            $tenant->mail_encryption = $request->mail_encryption ?: 'tls';
            $tenant->mail_from_address = $request->mail_from_address ?: $tenant->email;
            $tenant->mail_from_name = $request->mail_from_name ?: $tenant->company_name;
        }

        $result = $mailService->testConnection($tenant, $testEmail);

        return response()->json($result);
    }

    /**
     * Standalone A4 Printable Official Corporate ISP Profile Certificate
     */
    public function printCertificate(Request $request): View
    {
        $tenant = $this->getTenant();

        return view('tenant.settings.profile_print', compact('tenant'));
    }

    /**
     * Display the Authenticated ISP Staff / User Profile Page (https://somitysoft.com/admin/profile)
     */
    public function userProfile(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = auth()->user();

        $roleTitle = match($user->role) {
            'isp_collector', 'collector', 'reseller_collector' => 'Bill Collector',
            'isp_technician', 'technician', 'reseller_technician' => 'Technician',
            'isp_noc' => 'NOC Engineer',
            'isp_lineman' => 'Lineman',
            'isp_manager', 'manager' => 'Manager',
            'isp_admin', 'admin', 'owner' => 'ISP Administrator',
            default => ucwords(str_replace('_', ' ', $user->role ?? 'Staff'))
        };

        $stats = [
            'staff_id' => $user->staff_id ?: ($user->username ?: 'STF-' . str_pad($user->id, 4, '0', STR_PAD_LEFT)),
            'role_title' => $roleTitle,
            'mobile' => $user->mobile ?: ($user->phone ?: 'N/A'),
            'email' => $user->email,
            'joined_date' => $user->created_at ? $user->created_at->format('d M, Y') : date('d M, Y'),
            'status' => strtoupper($user->status ?? 'ACTIVE'),
        ];

        return view('tenant.profile.index', compact('user', 'tenant', 'stats'));
    }

    /**
     * Update Authenticated ISP Staff / User Personal Profile Information
     */
    public function updateUserProfile(Request $request)
    {
        $tenant = $this->getTenant();
        $user = auth()->user();

        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:100'],
            'user_email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
            'user_mobile' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

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
        if (isset($validated['address'])) {
            $user->address = $validated['address'];
        }
        $user->save();

        // Activity Log
        try {
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'tenant',
                'actor_id' => $user->id,
                'actor_name' => $user->name,
                'event_type' => 'PROFILE_UPDATED',
                'description' => "User '{$user->name}' updated personal profile details.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Ignore log failure
        }

        return back()->with('success', __('Profile information updated successfully.'));
    }

    /**
     * Update Authenticated ISP Staff / User Account Password
     */
    public function updatePassword(Request $request)
    {
        $tenant = $this->getTenant();
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        try {
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'tenant',
                'actor_id' => $user->id,
                'actor_name' => $user->name,
                'event_type' => 'PASSWORD_UPDATED',
                'description' => "User '{$user->name}' updated account password.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Ignore log failure
        }

        return back()->with('success', __('Password updated successfully.'));
    }
}
