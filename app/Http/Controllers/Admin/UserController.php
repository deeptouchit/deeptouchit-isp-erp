<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a paginated directory of users, clients, resellers, and system administrators.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->with(['reseller:id,first_name,last_name,username,email,company'])
            ->withCount(['subscriptions', 'invoices', 'tickets', 'clients']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Role Filter
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->latest('created_at')->paginate(15)->withQueryString();

        // Top 4 Metrics Stats
        $allUsers = User::all();
        $stats = [
            'total_users' => $allUsers->count(),
            'active_clients' => $allUsers->where('role', 'client')->where('status', 'active')->count(),
            'total_resellers' => $allUsers->where('role', 'reseller')->count(),
            'total_admins' => $allUsers->where('role', 'admin')->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Show form for creating a new user account.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        $resellers = User::where('role', 'reseller')
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'username', 'email', 'company')
            ->orderBy('first_name', 'asc')
            ->get();

        return Inertia::render('Admin/Users/Create', [
            'resellers' => $resellers,
        ]);
    }

    /**
     * Store a newly created user account with password hashing and UUID.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', 'string', 'in:admin,reseller,client'],
            'status' => ['required', 'string', 'in:active,suspended,terminated'],
            'reseller_id' => ['nullable', 'exists:users,id'],
            'company' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
        ]);

        if (empty($validated['username'])) {
            $validated['username'] = explode('@', $validated['email'])[0] . '_' . Str::lower(Str::random(4));
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['uuid'] = (string) Str::uuid();
        $validated['created_by'] = (!empty($validated['reseller_id']) && $validated['role'] === 'client') 
            ? $validated['reseller_id'] 
            : $request->user()?->id;

        unset($validated['reseller_id']);

        $user = User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$user->name}' ({$user->email}) created successfully.");
    }

    /**
     * Display comprehensive user account profile, hosted subscriptions, and invoices.
     */
    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load([
            'reseller:id,first_name,last_name,username,email,company',
            'subscriptions' => function ($q) {
                $q->with(['server:id,name,hostname,ip_address', 'plan:id,name,storage_mb,bandwidth_mb'])
                    ->latest();
            },
            'invoices' => fn ($q) => $q->latest()->limit(10),
            'tickets' => fn ($q) => $q->latest()->limit(10),
            'clients' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'username', 'email', 'status', 'created_at', 'created_by')->latest()->limit(10),
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show form for editing user account details.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $resellers = User::where('role', 'reseller')
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->select('id', 'first_name', 'last_name', 'username', 'email', 'company')
            ->orderBy('first_name', 'asc')
            ->get();

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'resellers' => $resellers,
        ]);
    }

    /**
     * Update user account details.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::min(8)],
            'role' => ['required', 'string', 'in:admin,reseller,client'],
            'status' => ['required', 'string', 'in:active,suspended,terminated'],
            'reseller_id' => ['nullable', 'exists:users,id'],
            'company' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (isset($validated['reseller_id'])) {
            if (!empty($validated['reseller_id']) && $validated['role'] === 'client') {
                $validated['created_by'] = $validated['reseller_id'];
            }
            unset($validated['reseller_id']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Delete / soft delete a user account with safety checks.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === $request->user()?->id) {
            return redirect()->back()->with('error', 'You cannot delete your own active administrator account.');
        }

        $userName = $user->name;
        $user->forceDelete();

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$userName}' deleted successfully.");
    }

    /**
     * Instant toggle user status (Active <-> Suspended).
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->id === $request->user()?->id) {
            return redirect()->back()->with('error', 'You cannot suspend your own active administrator account.');
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "User '{$user->name}' status changed to {$newStatus}.");
    }

    /**
     * Reset / override user password.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'failed_login_attempts' => 0,
        ]);

        return redirect()->back()->with('success', "Password successfully reset for user '{$user->name}'.");
    }

    /**
     * Impersonate a customer account (Login-as-Client).
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        if ($user->id === $request->user()?->id) {
            return redirect()->back()->with('error', 'You cannot impersonate your own administrator account.');
        }

        // Store original admin ID in session
        $originalAdminId = $request->user()->id;
        session()->put('impersonated_by', $originalAdminId);

        \Illuminate\Support\Facades\Auth::login($user);

        return redirect()->route('client.dashboard')
            ->with('success', "Now logged in as customer '{$user->name}' ({$user->email}).");
    }

    /**
     * Stop impersonating and return to Admin control plane.
     */
    public function stopImpersonating(Request $request): RedirectResponse
    {
        $adminId = session()->pull('impersonated_by');

        if ($adminId && $admin = User::find($adminId)) {
            \Illuminate\Support\Facades\Auth::login($admin);
            return redirect()->route('admin.users.index')
                ->with('success', 'Returned to Administrator session.');
        }

        return redirect()->route('admin.dashboard');
    }
}
