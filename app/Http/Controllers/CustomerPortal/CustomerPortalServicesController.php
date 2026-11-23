<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TenantCustomerPayment;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerPortalServicesController extends Controller
{
    private function account()
    {
        return Auth::guard('customer')->user();
    }

    public function package(Tenant $tenant)
    {
        $customer = $this->account()->customer()->with(['package', 'zone'])->firstOrFail();
        abort_unless((int) $customer->tenant_id === (int) $tenant->id, 404);
        return view('customer-portal.services.package', compact('tenant', 'customer'));
    }

    public function payments(Tenant $tenant)
    {
        $account = $this->account();
        $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('customer_id', $account->customer_id)
            ->latest('paid_at')->latest('id')->paginate(10);
        return view('customer-portal.services.payments', compact('tenant', 'payments'));
    }

    public function tickets(Tenant $tenant)
    {
        $account = $this->account();
        $tickets = SupportTicket::where('tenant_id', $tenant->id)
            ->where('customer_id', $account->customer_id)
            ->latest('updated_at')->paginate(10);
        return view('customer-portal.services.tickets', compact('tenant', 'tickets'));
    }

    public function createTicket(Request $request, Tenant $tenant)
    {
        $account = $this->account();
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'department' => ['required', Rule::in(['general', 'technical', 'billing', 'payment_gateway'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $account->customer_id,
            'ticket_number' => 'CUS-' . date('y') . '-' . strtoupper(Str::random(6)),
            'subject' => $data['subject'],
            'department' => $data['department'],
            'priority' => $data['priority'],
            'status' => 'open',
            'last_reply_by' => 'customer',
            'last_reply_at' => now(),
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'customer',
            'sender_id' => $account->id,
            'message' => $data['message'],
            'is_internal_note' => false,
        ]);

        return redirect()->route('customer.portal.ticket', ['tenant' => $tenant->slug, 'ticket' => $ticket->id])->with('success', 'Your support ticket has been created.');
    }

    public function ticket(Tenant $tenant, SupportTicket $ticket)
    {
        abort_unless((int) $ticket->tenant_id === (int) $tenant->id && (int) $ticket->customer_id === (int) $this->account()->customer_id, 404);
        $ticket->load(['publicMessages']);
        return view('customer-portal.services.ticket', compact('tenant', 'ticket'));
    }
}
