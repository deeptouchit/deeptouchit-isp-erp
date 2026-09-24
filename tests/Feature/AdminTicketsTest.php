<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;
    protected SupportTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $this->ticket = SupportTicket::create([
            'user_id' => $this->clientUser->id,
            'ticket_no' => 'TKT-TEST-9988',
            'subject' => 'Need help with MySQL remote database connection',
            'department' => 'technical',
            'priority' => 'high',
            'status' => 'open',
            'message' => 'I cannot connect to my database from my external server.',
            'assigned_to' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_view_tickets_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.tickets.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Tickets/Index')
            ->has('tickets')
            ->has('stats')
            ->has('staff')
            ->has('clients')
        );
    }

    public function test_admin_can_create_ticket_on_behalf_of_client(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.tickets.store'), [
                'user_id' => $this->clientUser->id,
                'subject' => 'Server OPcache high memory allocation',
                'department' => 'technical',
                'priority' => 'critical',
                'message' => 'Container memory consumption reached 92%.',
                'assigned_to' => $this->adminUser->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $this->clientUser->id,
            'subject' => 'Server OPcache high memory allocation',
            'department' => 'technical',
            'priority' => 'critical',
        ]);
    }

    public function test_admin_can_view_ticket_show_thread(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Tickets/Show')
            ->has('ticket')
            ->has('staff')
            ->has('cannedResponses')
        );
    }

    public function test_admin_can_reply_to_ticket(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.tickets.reply', $this->ticket->id), [
                'message' => 'We have enabled remote MySQL for your IP 103.59.177.138.',
                'status' => 'answered',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('answered', $this->ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->adminUser->id,
            'is_staff' => true,
        ]);
    }

    public function test_admin_can_update_ticket_metadata(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.tickets.update', $this->ticket->id), [
                'status' => 'in_progress',
                'priority' => 'critical',
                'department' => 'abuse',
                'assigned_to' => $this->adminUser->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->ticket->refresh();
        $this->assertEquals('in_progress', $this->ticket->status);
        $this->assertEquals('critical', $this->ticket->priority);
        $this->assertEquals('abuse', $this->ticket->department);
    }

    public function test_admin_can_close_ticket(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.tickets.close', $this->ticket->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('closed', $this->ticket->fresh()->status);
        $this->assertNotNull($this->ticket->fresh()->closed_at);
    }

    public function test_admin_can_delete_ticket(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.tickets.destroy', $this->ticket->id));

        $response->assertRedirect(route('admin.tickets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('support_tickets', ['id' => $this->ticket->id]);
    }
}
