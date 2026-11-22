<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResellerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_own_business_profile_and_export_it(): void
    {
        [$tenant, $reseller, $admin] = $this->account();
        $this->actingAs($admin)->withSession(['locale' => 'en']);
        $this->get(route('reseller.profile'))->assertOk()->assertSee('Partner Profile')
            ->assertSee('Login Email')->assertDontSee('noc@isp.com')->assertSee('Admin Name');

        $admin->update(['name' => 'আজাদ ইসলাম']);
        $response = $this->withSession(['locale' => 'bn'])->get(route('reseller.profile'))->assertOk()
            ->assertSee('অ্যাডমিনের নাম')
            ->assertDontSee('Admin Name')
            ->assertSee('class="w-full space-y-4"', false)
            ->assertDontSee('�');
        $this->assertMatchesRegularExpression('/text-xl shadow-xs">\s*আ\s*<\/div>/u', $response->getContent());

        $this->put(route('reseller.profile.update'), [
            'user_name' => 'Updated Admin', 'user_email' => 'new-login@example.com',
            'contact_person' => '=Example', 'mobile' => '01710000331',
            'commission_rate' => 99, 'tenant_id' => 999,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('=Example', $reseller->refresh()->contact_person);
        $this->assertSame(20.0, (float) $reseller->commission_rate);
        $this->assertSame($tenant->id, $reseller->tenant_id);
        $this->assertSame('new-login@example.com', $admin->refresh()->email);
        $this->assertNull($admin->email_verified_at);

        $csv = $this->get(route('reseller.profile.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=Example", $csv);
        $this->get(route('reseller.profile.certificate'))->assertOk()->assertSee($reseller->name);
    }

    public function test_staff_can_change_personal_profile_and_avatar_without_business_access(): void
    {
        [$tenant, $reseller] = $this->account();
        $staff = User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id,
            'role' => 'reseller_technician', 'status' => 'active', 'avatar' => 'avatars/old.png',
        ]);
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.png', 'old avatar');
        $this->actingAs($staff)->withSession(['locale' => 'bn']);
        $this->get(route('reseller.profile'))->assertOk()->assertSee('প্রোফাইল সংরক্ষণ করুন')->assertDontSee('wallet_balance');
        $this->get(route('reseller.profile.export'))->assertForbidden();
        $this->get(route('reseller.profile.certificate'))->assertForbidden();

        $profile = ['user_name' => 'Updated Staff', 'user_email' => $staff->email, 'contact_person' => 'Injected'];
        $this->put(route('reseller.profile.update'), $profile + [
            'avatar' => UploadedFile::fake()->createWithContent('bad.png', 'invalid image'),
        ])->assertSessionHasErrorsIn('profile', ['avatar']);
        Storage::disk('public')->assertExists('avatars/old.png');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j9fkAAAAASUVORK5CYII=');
        $this->put(route('reseller.profile.update'), $profile + [
            'avatar' => UploadedFile::fake()->createWithContent('avatar.png', $png),
        ])->assertSessionHasNoErrors();
        $newAvatar = $staff->refresh()->avatar;
        Storage::disk('public')->assertExists($newAvatar);
        Storage::disk('public')->assertMissing('avatars/old.png');
        $this->assertNotSame('Injected', $reseller->refresh()->contact_person);

        $this->put(route('reseller.profile.update'), $profile + ['remove_avatar' => true])->assertSessionHasNoErrors();
        $this->assertNull($staff->refresh()->avatar);
        Storage::disk('public')->assertMissing($newAvatar);
    }

    public function test_password_requires_current_credential_and_rotates_remember_token(): void
    {
        [$tenant, $reseller, $admin] = $this->account();
        $current = 'OriginalPassword';
        $admin->forceFill(['password' => Hash::make($current), 'remember_token' => 'old-token'])->save();
        $this->actingAs($admin);
        $this->post(route('reseller.profile.password'), [
            'current_password' => 'WrongPassword', 'password' => 'NewPassword90', 'password_confirmation' => 'NewPassword90',
        ])->assertSessionHasErrorsIn('password', ['current_password']);
        $this->assertTrue(Hash::check($current, $admin->refresh()->password));

        $this->post(route('reseller.profile.password'), [
            'current_password' => $current, 'password' => 'NewPassword90', 'password_confirmation' => 'NewPassword90',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword90', $admin->refresh()->password));
        $this->assertNotSame('old-token', $admin->getRememberToken());

        $unlinked = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'isp_admin', 'status' => 'active']);
        $this->actingAs($unlinked)->get(route('reseller.profile'))->assertForbidden();
    }

    private function account(): array
    {
        $tenant = Tenant::create(['name' => 'Profile ISP', 'company_name' => 'Profile ISP', 'slug' => 'profile-isp', 'status' => 'active']);
        $reseller = TenantReseller::create([
            'tenant_id' => $tenant->id, 'name' => 'Profile Partner', 'mobile' => '01710000330', 'commission_rate' => 20,
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'role' => 'reseller_admin', 'status' => 'active',
        ]);

        return [$tenant, $reseller, $user];
    }
}
