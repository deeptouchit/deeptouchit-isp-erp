<?php

namespace Tests\Feature;

use App\Models\EmailAccount;
use App\Models\EmailDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebmailSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected EmailDomain $domain;
    protected EmailAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domain = EmailDomain::create([
            'domain' => 'mycompany.com',
            'status' => 'active',
        ]);

        $this->account = EmailAccount::create([
            'email_domain_id' => $this->domain->id,
            'email' => 'ceo@mycompany.com',
            'password' => Hash::make('SecretPass123!@#'),
            'quota_mb' => 2048,
            'status' => 'active',
        ]);
    }

    public function test_user_can_view_webmail_login_portal(): void
    {
        $response = $this->get('/webmail');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Webmail/Login')
        );
    }

    public function test_user_can_authenticate_to_webmail(): void
    {
        $response = $this->post('/webmail/login', [
            'email' => 'ceo@mycompany.com',
            'password' => 'SecretPass123!@#',
        ]);

        $response->assertRedirect(route('webmail.inbox'));
        $this->assertEquals($this->account->id, session('webmail_account_id'));
    }

    public function test_webmail_rejects_invalid_password(): void
    {
        $response = $this->post('/webmail/login', [
            'email' => 'ceo@mycompany.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_can_view_webmail_inbox(): void
    {
        $response = $this->withSession(['webmail_account_id' => $this->account->id])
            ->get('/webmail/inbox');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Webmail/Inbox')
            ->has('account')
            ->has('messages')
        );
    }

    public function test_user_can_sign_out_from_webmail(): void
    {
        $response = $this->withSession(['webmail_account_id' => $this->account->id])
            ->post('/webmail/logout');

        $response->assertRedirect(route('webmail.login'));
        $this->assertNull(session('webmail_account_id'));
    }
}
