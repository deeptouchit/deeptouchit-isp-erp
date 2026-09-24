<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Database\PostgresService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostgresDatabaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_postgres_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.databases.postgres'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Databases/Postgres')
            ->has('telemetry')
            ->has('databases')
            ->has('roles')
            ->has('processlist')
        );
    }

    public function test_admin_can_create_postgres_database(): void
    {
        $this->mock(PostgresService::class, function ($mock) {
            $mock->shouldReceive('createDatabase')
                ->once()
                ->andReturn(['success' => true, 'message' => 'Postgres database created.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.databases.postgres.store'), [
                'name' => 'client_pgdb',
                'db_user' => 'client_pgusr',
                'db_password' => 'SecurePass123!@#',
                'encoding' => 'UTF8',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_vacuum_postgres_database(): void
    {
        $this->mock(PostgresService::class, function ($mock) {
            $mock->shouldReceive('vacuumAnalyze')
                ->once()
                ->with('client_pgdb', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Vacuum completed.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.databases.postgres.vacuum'), [
                'name' => 'client_pgdb',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_terminate_postgres_process(): void
    {
        $this->mock(PostgresService::class, function ($mock) {
            $mock->shouldReceive('terminateProcess')
                ->once()
                ->with(12345, $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Process terminated.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.databases.postgres.kill', 12345));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
