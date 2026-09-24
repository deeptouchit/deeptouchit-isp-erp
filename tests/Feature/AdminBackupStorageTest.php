<?php

namespace Tests\Feature;

use App\Models\BackupStorage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBackupStorageTest extends TestCase
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

    public function test_admin_can_view_backup_storage_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backups.storage'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backups/Storage')
            ->has('storages')
            ->has('stats')
        );
    }

    public function test_admin_can_create_backup_storage(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.storage.store'), [
                'name' => 'Secondary S3 Vault',
                'driver' => 's3',
                'path' => 'my-s3-backup-bucket',
                'capacity_gb' => 200,
                'retention_days' => 45,
                'is_default' => false,
                'encryption_enabled' => true,
                'credentials' => [
                    'key' => 'AKIA1234567890',
                    'secret' => 'Secret1234567890',
                    'region' => 'us-east-1',
                ],
            ]);

        $response->assertRedirect(route('admin.backups.storage'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('backup_storages', [
            'name' => 'Secondary S3 Vault',
            'driver' => 's3',
            'path' => 'my-s3-backup-bucket',
        ]);
    }

    public function test_admin_can_update_backup_storage(): void
    {
        $storage = BackupStorage::create([
            'name' => 'Old Storage Name',
            'driver' => 's3',
            'path' => 'old-bucket',
            'is_default' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.backups.storage.update', $storage->id), [
                'name' => 'Updated Storage Name',
                'driver' => 's3',
                'path' => 'new-bucket',
                'capacity_gb' => 300,
                'retention_days' => 60,
                'is_default' => false,
                'encryption_enabled' => true,
            ]);

        $response->assertRedirect(route('admin.backups.storage'));
        $this->assertDatabaseHas('backup_storages', [
            'id' => $storage->id,
            'name' => 'Updated Storage Name',
            'path' => 'new-bucket',
        ]);
    }

    public function test_admin_can_test_backup_storage_connection(): void
    {
        $storage = BackupStorage::create([
            'name' => 'Test Local Target',
            'driver' => 'local',
            'path' => '/var/backups/deeptouchhost',
            'is_default' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.storage.test', $storage->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_set_default_storage(): void
    {
        $storage1 = BackupStorage::create([
            'name' => 'Storage 1',
            'driver' => 'local',
            'path' => '/var/backups/deeptouchhost',
            'is_default' => true,
            'status' => 'active',
        ]);

        $storage2 = BackupStorage::create([
            'name' => 'Storage 2',
            'driver' => 'r2',
            'path' => 'r2-bucket',
            'is_default' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.storage.default', $storage2->id));

        $response->assertRedirect();
        $this->assertTrue($storage2->fresh()->is_default);
        $this->assertFalse($storage1->fresh()->is_default);
    }

    public function test_admin_can_delete_backup_storage(): void
    {
        $storage = BackupStorage::create([
            'name' => 'Temporary Remote SFTP',
            'driver' => 'sftp',
            'path' => '/backups',
            'is_default' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.backups.storage.destroy', $storage->id));

        $response->assertRedirect(route('admin.backups.storage'));
        $this->assertDatabaseMissing('backup_storages', ['id' => $storage->id]);
    }
}
