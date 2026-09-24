<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Database;
use App\Models\Subscription;
use App\Services\Database\PostgresService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostgresDatabaseController extends Controller
{
    protected PostgresService $postgresService;

    public function __construct(PostgresService $postgresService)
    {
        $this->postgresService = $postgresService;
    }

    /**
     * Render the PostgreSQL Database Manager Dashboard.
     */
    public function index(Request $request): Response
    {
        $telemetry = $this->postgresService->getTelemetry();
        $databases = $this->postgresService->getDatabases();
        $roles = $this->postgresService->getRoles();
        $processlist = $this->postgresService->getProcesslist();

        $subscriptions = Subscription::with('user')
            ->where('status', 'active')
            ->get(['id', 'domain', 'username', 'user_id']);

        return Inertia::render('Admin/Databases/Postgres', [
            'telemetry' => $telemetry,
            'databases' => $databases,
            'roles' => $roles,
            'processlist' => $processlist,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Create a new PostgreSQL database, role, and privileges.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'name' => 'required|string|max:63|regex:/^[a-z0-9_]+$/',
            'db_user' => 'required|string|max:63|regex:/^[a-z0-9_]+$/',
            'db_password' => 'required|string|min:8',
            'encoding' => 'nullable|string|in:UTF8,LATIN1,SQL_ASCII',
        ]);

        $res = $this->postgresService->createDatabase($validated, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['name' => $res['error']]);
    }

    /**
     * Reset / change PostgreSQL role password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'db_user' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $res = $this->postgresService->changePassword($validated['db_user'], $validated['new_password'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Vacuum & Analyze a PostgreSQL database.
     */
    public function vacuum(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        $res = $this->postgresService->vacuumAnalyze($validated['name'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Kill an active query / terminate backend.
     */
    public function killProcess($processId)
    {
        $res = $this->postgresService->terminateProcess((int)$processId, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Export / Download PostgreSQL database dump.
     */
    public function export(string $dbName): StreamedResponse
    {
        $fileName = $dbName . '_' . date('Y_m_d_His') . '.sql';

        return response()->streamDownload(function () use ($dbName) {
            $cmd = sprintf(
                "sudo -u postgres pg_dump -d %s",
                escapeshellarg($dbName)
            );

            passthru($cmd);
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Drop a PostgreSQL database and associated role.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'db_user' => 'nullable|string',
        ]);

        $res = $this->postgresService->dropDatabase($validated['name'], $validated['db_user'] ?? null, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }
}
