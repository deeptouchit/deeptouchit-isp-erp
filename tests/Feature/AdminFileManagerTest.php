<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminFileManagerTest extends TestCase
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

    public function test_admin_can_view_file_manager_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.files.manager'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Files/Manager')
            ->has('scope')
            ->has('breadcrumbs')
            ->has('items')
            ->has('stats')
        );
    }

    public function test_admin_can_create_and_delete_folder(): void
    {
        $testFolderName = 'test_unit_folder_' . time();

        $responseCreate = $this->actingAs($this->adminUser)
            ->post(route('admin.files.manager.create-folder'), [
                'scope' => 'vhosts',
                'path' => '',
                'name' => $testFolderName,
            ]);

        $responseCreate->assertRedirect();
        $responseCreate->assertSessionHas('success');

        $this->assertTrue(File::exists('/var/www/vhosts/' . $testFolderName));

        $responseDelete = $this->actingAs($this->adminUser)
            ->delete(route('admin.files.manager.delete'), [
                'scope' => 'vhosts',
                'path' => $testFolderName,
            ]);

        $responseDelete->assertRedirect();
        $responseDelete->assertSessionHas('success');

        $this->assertFalse(File::exists('/var/www/vhosts/' . $testFolderName));
    }

    public function test_admin_can_create_edit_and_save_file(): void
    {
        $testFileName = 'test_unit_file_' . time() . '.txt';

        $responseCreate = $this->actingAs($this->adminUser)
            ->post(route('admin.files.manager.create-file'), [
                'scope' => 'vhosts',
                'path' => '',
                'name' => $testFileName,
                'content' => 'Hello DeepTouchHost Unit Test',
            ]);

        $responseCreate->assertRedirect();
        $responseCreate->assertSessionHas('success');

        $responseEdit = $this->actingAs($this->adminUser)
            ->getJson(route('admin.files.manager.edit', ['scope' => 'vhosts', 'path' => $testFileName]));

        $responseEdit->assertStatus(200);
        $responseEdit->assertJson(['success' => true]);
        $this->assertStringContainsString('Hello DeepTouchHost Unit Test', $responseEdit->json('data.content'));

        $responseSave = $this->actingAs($this->adminUser)
            ->postJson(route('admin.files.manager.save'), [
                'scope' => 'vhosts',
                'path' => $testFileName,
                'content' => 'Updated Content for File',
            ]);

        $responseSave->assertStatus(200);
        $responseSave->assertJson(['success' => true]);

        // Cleanup
        File::delete('/var/www/vhosts/' . $testFileName);
    }
}
