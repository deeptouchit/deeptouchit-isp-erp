<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Models\EmailDomain;
use App\Models\EmailMessage;
use App\Services\Email\WebmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class WebmailController extends Controller
{
    protected WebmailService $webmailService;

    public function __construct(WebmailService $webmailService)
    {
        $this->webmailService = $webmailService;
    }

    /**
     * Display Hostinger-style Webmail Login Portal.
     */
    public function login(Request $request)
    {
        if (Session::has('webmail_account_id')) {
            $acc = EmailAccount::find(Session::get('webmail_account_id'));
            if ($acc) {
                return redirect()->route('webmail.inbox');
            }
        }

        $emailPreFill = $request->query('email', '');

        return Inertia::render('Webmail/Login', [
            'initialEmail' => $emailPreFill,
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    /**
     * Authenticate mailbox credentials.
     */
    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($validated['email']));
        $password = $validated['password'];

        $account = EmailAccount::where('email', $email)->first();

        if (!$account) {
            return redirect()->back()->withErrors([
                'email' => 'No mailbox found with this email address.',
            ]);
        }

        if ($account->status !== 'active') {
            return redirect()->back()->withErrors([
                'email' => 'This mailbox account has been suspended by system administrator.',
            ]);
        }

        // Verify password
        if (!Hash::check($password, $account->password)) {
            return redirect()->back()->withErrors([
                'password' => 'Incorrect password for this mailbox.',
            ]);
        }

        // Store webmail session
        Session::put('webmail_account_id', $account->id);
        Session::put('webmail_email', $account->email);

        return redirect()->route('webmail.inbox');
    }

    /**
     * Webmail Inbox & Client Application.
     */
    public function inbox(Request $request)
    {
        if (!Session::has('webmail_account_id')) {
            return redirect()->route('webmail.login');
        }

        $account = EmailAccount::with('emailDomain')->find(Session::get('webmail_account_id'));
        if (!$account) {
            Session::forget(['webmail_account_id', 'webmail_email']);
            return redirect()->route('webmail.login');
        }

        $domainParts = explode('@', $account->email);
        $domain = $account->emailDomain?->domain ?: ($domainParts[1] ?? 'deeptouchit.com');
        $messages = $this->webmailService->getMessagesForAccount($account);

        return Inertia::render('Webmail/Inbox', [
            'account' => [
                'id' => $account->id,
                'email' => $account->email,
                'domain' => $domain,
                'quota_mb' => $account->quota_mb,
                'used_quota_mb' => $account->used_quota_mb ?: 1,
                'used_percent' => round((($account->used_quota_mb ?: 1) / $account->quota_mb) * 100, 1),
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Send email via webmail compose.
     */
    public function send(Request $request)
    {
        if (!Session::has('webmail_account_id')) {
            return redirect()->route('webmail.login');
        }

        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $account = EmailAccount::find(Session::get('webmail_account_id'));
        if (!$account) {
            return redirect()->route('webmail.login');
        }

        $res = $this->webmailService->sendEmail($account, $validated);

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle Star
     */
    public function toggleStar(EmailMessage $message)
    {
        $accountId = Session::get('webmail_account_id');
        if ($message->email_account_id !== $accountId) {
            abort(403);
        }

        $message->update(['is_starred' => !$message->is_starred]);
        return back();
    }

    /**
     * Mark as Read / Unread
     */
    public function markRead(Request $request, EmailMessage $message)
    {
        $accountId = Session::get('webmail_account_id');
        if ($message->email_account_id !== $accountId) {
            abort(403);
        }

        $isRead = (bool) $request->input('is_read', true);
        $message->update(['is_read' => $isRead]);
        return back();
    }

    /**
     * Delete Message (Move to trash or permanently delete)
     */
    public function deleteMessage(EmailMessage $message)
    {
        $accountId = Session::get('webmail_account_id');
        if ($message->email_account_id !== $accountId) {
            abort(403);
        }

        if ($message->folder === 'trash') {
            $message->delete();
            return back()->with('success', 'Message deleted permanently.');
        } else {
            $message->update(['folder' => 'trash']);
            return back()->with('success', 'Message moved to Trash.');
        }
    }

    /**
     * Empty Trash Folder
     */
    public function emptyTrash()
    {
        $accountId = Session::get('webmail_account_id');
        EmailMessage::where('email_account_id', $accountId)
            ->where('folder', 'trash')
            ->delete();

        return back()->with('success', 'Trash folder emptied.');
    }

    /**
     * Handle bulk operations (delete, mark read, mark unread, star, unstar).
     */
    public function bulkAction(Request $request)
    {
        $accountId = Session::get('webmail_account_id');
        if (!$accountId) {
            return redirect()->route('webmail.login');
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action' => 'required|string|in:delete,mark_read,mark_unread,star,unstar',
        ]);

        $query = EmailMessage::where('email_account_id', $accountId)
            ->whereIn('id', $validated['ids']);

        switch ($validated['action']) {
            case 'delete':
                $trashItems = (clone $query)->where('folder', 'trash')->get();
                foreach ($trashItems as $item) {
                    $item->delete();
                }
                $otherItems = (clone $query)->where('folder', '!=', 'trash')->get();
                foreach ($otherItems as $item) {
                    $item->update(['folder' => 'trash']);
                }
                $count = count($validated['ids']);
                return back()->with('success', "{$count} messages processed.");

            case 'mark_read':
                $query->update(['is_read' => true]);
                return back()->with('success', count($validated['ids']) . ' messages marked as read.');

            case 'mark_unread':
                $query->update(['is_read' => false]);
                return back()->with('success', count($validated['ids']) . ' messages marked as unread.');

            case 'star':
                $query->update(['is_starred' => true]);
                return back()->with('success', count($validated['ids']) . ' messages starred.');

            case 'unstar':
                $query->update(['is_starred' => false]);
                return back()->with('success', count($validated['ids']) . ' messages unstarred.');
        }

        return back();
    }

    /**
     * Sign out of webmail.
     */
    public function logout()
    {
        Session::forget(['webmail_account_id', 'webmail_email']);
        return redirect()->route('webmail.login')->with('status', 'You have been signed out from Webmail.');
    }
}
