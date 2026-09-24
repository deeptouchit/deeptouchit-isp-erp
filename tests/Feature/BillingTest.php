<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_invoices_list(): void
    {
        $user = User::factory()->create([
            'username' => 'billinguser',
            'role' => 'client',
        ]);

        $response = $this->actingAs($user)->get('/billing/invoices');
        $response->assertStatus(200);
    }
}
