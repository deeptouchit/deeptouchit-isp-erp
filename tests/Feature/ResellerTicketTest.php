<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResellerTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_lists_reports_and_private_messages_are_reseller_scoped(): void
    {
        [$reseller, $user, $other] = $this->partners();
        $this->actingAs($user)->withSession(['locale' => 'en']);
        $own = $this->ticket($reseller, 'Matching ticket', 'high');
        $excluded = $this->ticket($reseller, 'Low priority ticket', 'low');
        $foreign = $this->ticket($other, 'Another reseller ticket', 'high');

        $own->messages()->create(['message' => 'Public support response', 'sender_type' => 'tenant']);
        $own->messages()->create(['message' => 'Internal ISP note', 'sender_type' => 'tenant', 'is_internal_note' => true]);

        $filters = ['priority' => 'high'];
        $this->get(route('reseller.tickets.index', $filters))->assertOk()->assertSee('Matching ticket')
            ->assertSee('More Filters')->assertSee('no-sort">#</th>', false)
            ->assertDontSee($excluded->subject)->assertDontSee($foreign->subject);
        $this->withSession(['locale' => 'bn'])->get(route('reseller.tickets.index', $filters))->assertOk()
            ->assertSee('টিকেট নম্বর বা বিষয় দিয়ে খুঁজুন');
        $this->get(route('reseller.tickets.print', $filters))->assertOk()
            ->assertSee($own->subject)->assertDontSee($excluded->subject)->assertDontSee($foreign->subject);
        $csv = $this->get(route('reseller.tickets.export', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString($own->subject, $csv);
        $this->assertStringNotContainsString($excluded->subject, $csv);
        $this->assertStringNotContainsString($foreign->subject, $csv);

        $this->get(route('reseller.tickets.show', $own))->assertOk()->assertSee('Public support response')->assertDontSee('Internal ISP note');
        $this->getJson(route('reseller.tickets.topbar-messages'))->assertOk()->assertSee('Public support response')->assertDontSee('Internal ISP note');
        $this->get(route('reseller.tickets.show', $foreign))->assertNotFound();
        $this->postJson(route('reseller.tickets.reply', $foreign), ['message' => 'Cross-reseller reply'])->assertNotFound();
        $this->postJson(route('reseller.tickets.close', $foreign))->assertNotFound();
    }

    public function test_reseller_can_create_reply_and_close_with_authorized_attachment_downloads(): void
    {
        [$reseller, $user, $other, $otherUser] = $this->partners();
        Notification::fake();
        Storage::fake('local');
        Storage::fake('public');
        $this->actingAs($user)->post(route('reseller.tickets.store'), [
            'subject' => 'Connectivity issue', 'department' => 'network', 'priority' => 'high',
            'message' => 'Please check the connection.',
            'attachment' => UploadedFile::fake()->createWithContent('diagnostics.txt', 'Sample log contents'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $ticket = SupportTicket::where('reseller_id', $reseller->id)->firstOrFail();
        $file = $ticket->publicMessages()->firstOrFail()->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($file->file_path);
        Storage::disk('public')->assertMissing($file->file_path);

        $download = route('reseller.tickets.attachments.download', [$ticket->id, $file->id]);
        $this->get($download)->assertOk()->assertDownload('diagnostics.txt')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($otherUser)->get($download)->assertNotFound();
        $this->actingAs($user)->post(route('reseller.tickets.reply', $ticket), ['message' => 'Connection still down.'])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $ticket->publicMessages()->count());

        $this->postJson(route('reseller.tickets.close', $ticket))->assertOk()->assertJsonPath('success', true);
        $this->assertSame('closed', $ticket->refresh()->status);
        $this->postJson(route('reseller.tickets.reply', $ticket), ['message' => 'Blocked reply.'])->assertUnprocessable();
        $this->assertSame(2, $ticket->publicMessages()->count());
    }

    private function ticket(TenantReseller $reseller, string $subject, string $priority): SupportTicket
    {
        return SupportTicket::create([
            'tenant_id' => $reseller->tenant_id, 'reseller_id' => $reseller->id,
            'ticket_number' => 'TIC-' . str()->random(10), 'subject' => $subject,
            'status' => 'open', 'department' => 'technical', 'priority' => $priority,
        ]);
    }

    private function partners(): array
    {
        $tenant = Tenant::create(['name' => 'Ticket ISP', 'company_name' => 'Ticket ISP', 'slug' => 'ticket-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Ticket Partner', 'mobile' => '01710000230']);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Partner', 'mobile' => '01710000231']);
        $partnerUser = fn ($partner) => User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $partner->id, 'role' => 'reseller_admin', 'status' => 'active',
        ]);

        return [$reseller, $partnerUser($reseller), $other, $partnerUser($other)];
    }
}
