<?php

namespace Tests\Feature;

use App\Jobs\SyncTenantOnus;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantOlt;
use App\Models\TenantOnu;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\Network\OltApiService;
use App\Services\Network\OnuLinkService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class OnuProductionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private TenantOlt $olt;

    private TenantOnu $onu;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->tenant = Tenant::create(['name' => 'ONU Test', 'company_name' => 'ONU Test', 'slug' => 'onu-test', 'status' => 'active']);
        $this->olt = TenantOlt::create(['tenant_id' => $this->tenant->id, 'name' => 'OLT', 'brand' => 'Test', 'ip_address' => '192.0.2.10', 'web_port' => 9443, 'web_username' => 'operator', 'web_password' => 'device-test']);
        $this->onu = TenantOnu::create(['tenant_id' => $this->tenant->id, 'olt_id' => $this->olt->id, 'pon_port' => '0/1', 'onu_id' => 1, 'name' => 'ONU', 'status' => 'online']);
        $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'isp_admin', 'status' => 'active']);
        $this->actingAs($user);
    }

    private function customer(array $attributes = []): TenantCustomer
    {
        $id = (string) Str::uuid();

        return TenantCustomer::create(array_merge(['tenant_id' => $this->tenant->id, 'name' => 'Customer', 'customer_id' => $id, 'username' => $id, 'password' => 'customer-test', 'phone' => '01000000000'], $attributes));
    }

    public function test_secrets_are_encrypted_and_not_in_json_or_page(): void
    {
        $this->onu->update(['pppoe_password' => 'ppp-secret-test', 'wifi_password' => 'wifi-secret-test']);
        $this->assertNotEquals('ppp-secret-test', DB::table('tenant_onus')->where('id', $this->onu->id)->value('pppoe_password'));
        $this->assertSame('ppp-secret-test', $this->onu->fresh()->pppoe_password);
        $this->getJson(route('tenant.network.onu.show', $this->onu->id))->assertOk()->assertJsonMissingPath('onu.pppoe_password')->assertJsonMissingPath('onu.olt')->assertJsonMissingPath('onu.customer');
        $this->get(route('tenant.network.onu'))->assertOk()->assertDontSee('ppp-secret-test')->assertDontSee('wifi-secret-test');
    }

    public function test_cross_tenant_onu_is_inaccessible(): void
    {
        $other = Tenant::create(['name' => 'Other', 'company_name' => 'Other', 'slug' => 'other-onu', 'status' => 'active']);
        $this->onu->update(['tenant_id' => $other->id]);
        $this->getJson(route('tenant.network.onu.show', $this->onu->id))->assertNotFound();
        $this->putJson(route('tenant.network.onu.update', $this->onu->id), ['name' => 'Invalid'])->assertNotFound();
    }

    public function test_reboot_auth_failure_is_not_success(): void
    {
        $api = Mockery::mock(OltApiService::class)->makePartial();
        $api->shouldReceive('getToken')->andReturnNull();
        $this->assertFalse($api->rebootOnu($this->olt, '0/1', 1));
        Http::assertNothingSent();
    }

    public function test_device_rejection_and_html_are_not_acknowledgements(): void
    {
        $api = Mockery::mock(OltApiService::class)->makePartial();
        $api->shouldReceive('getToken')->andReturn('test-token');
        Cache::put('olt_driver_192.0.2.10_9443', 'fastcgi');
        Cache::put('olt_proto_192.0.2.10_9443', 'https');
        Http::fake(['*' => Http::sequence()->push(['code' => 500])->push('<html>Login</html>')->push(['code' => 0])]);
        $this->assertFalse($api->rebootOnu($this->olt, '0/1', 1));
        $this->assertFalse($api->factoryResetOnu($this->olt, '0/1', 1));
        $this->assertTrue($api->rebootOnu($this->olt, '0/1', 1));
        Http::assertSentCount(3);
    }

    public function test_failed_configuration_does_not_change_database(): void
    {
        $api = Mockery::mock(OltApiService::class);
        $api->shouldReceive('configureOnu')->once()->andReturn(['success' => false, 'pushed_to_olt' => false]);
        $this->app->instance(OltApiService::class, $api);
        $this->putJson(route('tenant.network.onu.update', $this->onu->id), ['vlan_id' => 123])->assertStatus(422)->assertJsonPath('success', false);
        $this->assertNull($this->onu->fresh()->vlan_id);
        $this->assertDatabaseHas('tenant_activity_logs', ['event_type' => 'ONU_CONFIGURE']);
    }

    public function test_unsupported_wifi_changes_are_rejected_without_device_request(): void
    {
        $this->putJson(route('tenant.network.onu.update', $this->onu->id), ['wifi_ssid' => 'Unsupported'])->assertStatus(422);
        $this->assertNull($this->onu->fresh()->wifi_ssid);
        Http::assertNothingSent();
    }

    public function test_local_details_can_be_saved_without_changing_device(): void
    {
        $this->putJson(route('tenant.network.onu.update', $this->onu->id), ['name' => 'Local label'])->assertOk()->assertJsonPath('success', true);
        $this->assertSame('Local label', $this->onu->fresh()->local_name);
        $this->assertSame('ONU', $this->onu->fresh()->name);
        Http::assertNothingSent();
    }

    public function test_factory_reset_requires_explicit_target_confirmation(): void
    {
        $this->postJson(route('tenant.network.onu.factory-reset', $this->onu->id))->assertUnprocessable()->assertJsonValidationErrors('confirm_onu_id');
        Http::assertNothingSent();
    }

    public function test_cached_optical_data_does_not_become_a_fresh_reading(): void
    {
        $when = now()->subDays(2)->startOfSecond();
        $this->onu->update(['rx_power_dbm' => -24, 'optical_observed_at' => $when]);
        $api = Mockery::mock(OltApiService::class);
        $api->shouldReceive('getOnuOpticalTelemetry')->andReturn(['rx_power_dbm' => -24, 'updated_at' => $when->toIso8601String(), 'source' => 'cache']);
        $this->app->instance(OltApiService::class, $api);
        $this->getJson(route('tenant.network.onu.optical', $this->onu->id))->assertOk()->assertJsonPath('source', 'cache');
        $this->assertTrue($this->onu->fresh()->optical_observed_at->equalTo($when));
    }

    public function test_duplicate_customer_mac_is_not_auto_linked(): void
    {
        $this->onu->update(['router_mac' => 'AA:BB:CC:DD:EE:FF']);
        $this->customer(['mac_address' => 'AA:BB:CC:DD:EE:FF']);
        $this->customer(['mac_address' => 'aa-bb-cc-dd-ee-ff']);
        $this->assertSame(0, app(OnuLinkService::class)->linkTenant($this->tenant->id));
        $this->assertNull($this->onu->fresh()->customer_id);
    }

    public function test_unique_normalized_match_links_both_sides(): void
    {
        $this->onu->update(['router_mac' => 'AA:BB:CC:DD:EE:FF']);
        $customer = $this->customer(['mac_address' => 'aa-bb-cc-dd-ee-ff']);
        $this->assertSame(1, app(OnuLinkService::class)->linkTenant($this->tenant->id));
        $this->assertEquals($customer->id, $this->onu->fresh()->customer_id);
        $this->assertEquals($this->olt->id, $customer->fresh()->olt_id);
        $this->assertSame(0, app(OnuLinkService::class)->linkTenant($this->tenant->id));
    }

    public function test_customer_read_does_not_reassign_existing_binding(): void
    {
        $customer = $this->customer(['mac_address' => 'AA:BB:CC:DD:EE:FF']);
        $this->onu->update(['router_mac' => $customer->mac_address]);
        $this->assertNull($customer->onu_device);
        $this->assertNull($this->onu->fresh()->customer_id);
    }

    public function test_sync_is_queued_and_duplicate_submission_is_blocked(): void
    {
        Queue::fake();
        $response = $this->postJson(route('tenant.network.onu.sync-all'))->assertStatus(202)->assertJsonPath('state', 'queued');
        Queue::assertPushed(SyncTenantOnus::class, fn ($job) => $job->tenantId === $this->tenant->id && $job->connection === 'network');
        $this->getJson($response->json('status_url'))->assertOk()->assertJsonPath('state', 'queued');
        $this->postJson(route('tenant.network.onu.sync-all'))->assertStatus(409);
    }

    public function test_table_and_kpis_share_dynamic_signal_thresholds(): void
    {
        config(['onu.signal.good_min_dbm' => -22, 'onu.signal.warning_min_dbm' => -28]);
        $this->onu->update(['rx_power_dbm' => -21]);
        $this->get(route('tenant.network.onu', ['status' => 'good']))->assertOk()->assertViewHas('totalOnus', 1)->assertViewHas('warningOnus', 0);
        $this->assertSame('good', $this->onu->fresh()->signal_quality['status']);
    }

    public function test_partial_pon_sync_preserves_missing_inventory(): void
    {
        $this->olt->update(['pon_port_map' => ['0/1' => 1, '0/2' => 2]]);
        $api = Mockery::mock(OltApiService::class)->makePartial();
        $api->shouldReceive('getToken')->andReturn('test-token');
        $api->shouldReceive('getOpticalList')->andReturnNull();
        $api->shouldReceive('getModule')->andReturnUsing(fn ($ip, $port, $user, $password, $module) => $module === 'onu_list_get&PonId=0/2' ? ['list' => [['OnuId' => 2, 'RunningState' => 1, 'Mac' => 'AA:00:00:00:00:02']]] : null
        );
        Cache::put('olt_driver_192.0.2.10_9443', 'fastcgi');
        $this->assertFalse($api->syncOlt($this->olt)['success']);
        $this->assertDatabaseHas('tenant_onus', ['id' => $this->onu->id]);
        $this->assertDatabaseHas('tenant_onus', ['olt_id' => $this->olt->id, 'pon_port' => '0/2', 'onu_id' => 2]);
    }

    public function test_two_onus_claiming_one_customer_are_not_linked(): void
    {
        $customer = $this->customer();
        $this->onu->update(['pppoe_username' => $customer->username]);
        TenantOnu::create(['tenant_id' => $this->tenant->id, 'olt_id' => $this->olt->id, 'pon_port' => '0/2', 'onu_id' => 2, 'pppoe_username' => $customer->username]);
        $this->assertSame(0, app(OnuLinkService::class)->linkTenant($this->tenant->id));
    }

    public function test_existing_customer_binding_is_preserved(): void
    {
        $original = $this->customer();
        $other = $this->customer();
        $this->onu->update(['customer_id' => $original->id, 'pppoe_username' => $other->username]);
        $this->assertSame(0, app(OnuLinkService::class)->linkTenant($this->tenant->id));
        $this->assertEquals($original->id, $this->onu->fresh()->customer_id);
    }

    public function test_link_rolls_back_when_customer_update_fails(): void
    {
        $customer = $this->customer();
        $this->onu->update(['pppoe_username' => $customer->username]);
        DB::unprepared("CREATE TRIGGER reject_onu_pair BEFORE UPDATE ON tenant_customers BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        try {
            app(OnuLinkService::class)->linkTenant($this->tenant->id);
            $this->fail('Expected database rejection');
        } catch (QueryException $e) {
            $this->assertNull($this->onu->fresh()->customer_id);
        }
    }

    public function test_read_permission_does_not_allow_device_writes(): void
    {
        TenantRole::create(['tenant_id' => $this->tenant->id, 'name' => 'onu_reader', 'display_name' => 'ONU Reader', 'status' => 'active', 'permissions' => ['network.olts_onus']]);
        auth()->user()->update(['role' => 'onu_reader']);
        $this->getJson(route('tenant.network.onu.show', $this->onu->id))->assertOk();
        $this->putJson(route('tenant.network.onu.update', $this->onu->id), ['name' => 'Blocked'])->assertForbidden();
        $this->postJson(route('tenant.network.onu.reboot', $this->onu->id))->assertForbidden();
        $this->postJson(route('tenant.network.onu.factory-reset', $this->onu->id), ['confirm_onu_id' => $this->onu->id])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_job_reports_partial_failure_and_releases_pending_marker(): void
    {
        $operation = (string) Str::uuid();
        Cache::put('onu-operation-pending:'.$this->tenant->id, $operation);
        $api = Mockery::mock(OltApiService::class);
        $api->shouldReceive('syncOlt')->once()->andReturn(['success' => false]);
        (new SyncTenantOnus($this->tenant->id, $operation))->handle($api);
        $this->assertSame('partial', Cache::get('onu-operation-status:'.$this->tenant->id.':'.$operation)['state']);
        $this->assertNull(Cache::get('onu-operation-pending:'.$this->tenant->id));
    }

    public function test_other_tenant_cannot_read_operation_status(): void
    {
        $operation = (string) Str::uuid();
        Cache::put('onu-operation-status:999:'.$operation, ['state' => 'completed']);
        $this->getJson(route('tenant.network.onu.operation-status', $operation))->assertNotFound();
    }

    public function test_migration_encrypts_existing_plaintext_without_double_encryption(): void
    {
        DB::table('tenant_onus')->where('id', $this->onu->id)->update(['pppoe_password' => 'legacy-test']);
        $migration = require database_path('migrations/2026_11_18_000001_harden_onu_inventory.php');
        $migration->up();
        $cipher = DB::table('tenant_onus')->where('id', $this->onu->id)->value('pppoe_password');
        $migration->up();
        $this->assertSame($cipher, DB::table('tenant_onus')->where('id', $this->onu->id)->value('pppoe_password'));
        $this->assertSame('legacy-test', $this->onu->fresh()->pppoe_password);
    }

    public function test_bad_filter_types_are_validation_errors(): void
    {
        $this->getJson(route('tenant.network.onu', ['search' => ['unexpected']]))->assertUnprocessable();
    }

    public function test_hsgq_sync_uses_actual_per_onu_state_and_signal(): void
    {
        $api = Mockery::mock(OltApiService::class)->makePartial();
        $api->shouldReceive('getToken')->andReturn('test-token');
        $api->shouldReceive('getHsgqEndpoint')->andReturnUsing(function ($ip, $port, $username, $password, $endpoint) {
            if (str_starts_with($endpoint, 'onu_allow_list')) {
                return ['data' => [['port_id' => 1, 'onu_id' => 1, 'status' => 'Online', 'receive_power' => '-17.3', 'distance' => 1234]]];
            }
            if ($endpoint === 'onumgmt?form=base-info') {
                return ['data' => [['port_id' => 1, 'onu_id' => 1, 'macaddr' => 'AA:00:00:00:00:01']]];
            }

            return ['data' => []];
        });
        Cache::put('olt_driver_192.0.2.10_9443', 'hsgq');
        $this->assertTrue($api->syncOlt($this->olt)['success']);
        $onu = $this->onu->fresh();
        $this->assertSame('online', $onu->status);
        $this->assertSame(-17.3, $onu->rx_power_dbm);
        $this->assertSame(1234, $onu->distance_m);
        $this->assertNull($onu->tx_power_dbm);
        $this->assertNotNull($onu->optical_observed_at);
    }

    public function test_hsgq_live_optics_uses_verified_endpoint_and_exact_identity(): void
    {
        $api = Mockery::mock(OltApiService::class)->makePartial();
        $api->shouldReceive('getHsgqEndpoint')->once()->withArgs(fn ($ip, $port, $username, $password, $endpoint) => $endpoint === 'onu_allow_list?port_id=1')
            ->andReturn(['data' => [['port_id' => 2, 'onu_id' => 1, 'receive_power' => '-10'], ['port_id' => 1, 'onu_id' => 1, 'receive_power' => '-23.4 dBm', 'distance' => 400]]]);
        Cache::put('olt_driver_192.0.2.10_9443', 'hsgq');
        $reading = $api->getOnuOpticalTelemetry($this->olt, '0/1', 1);
        $this->assertSame(-23.4, $reading['rx_power_dbm']);
        $this->assertSame('live', $reading['source']);
    }

    public function test_duplicate_pon_configuration_is_rejected(): void
    {
        $this->putJson(route('tenant.network.olt.update', $this->olt->id), ['name' => 'OLT', 'ip_address' => '192.0.2.10', 'pon_ports' => "0/1\n0/1"])
            ->assertUnprocessable()->assertJsonValidationErrors('pon_ports');
        Http::assertNothingSent();
    }

    public function test_foreign_olt_relationship_cannot_send_device_requests(): void
    {
        $other = Tenant::create(['name' => 'Foreign', 'company_name' => 'Foreign', 'slug' => 'foreign-olt', 'status' => 'active']);
        $olt = TenantOlt::create(['tenant_id' => $other->id, 'name' => 'Foreign OLT', 'brand' => 'Test', 'ip_address' => '192.0.2.30']);
        $this->onu->update(['olt_id' => $olt->id]);
        $this->getJson(route('tenant.network.onu.optical', $this->onu->id))->assertNotFound();
        $this->postJson(route('tenant.network.onu.reboot', $this->onu->id))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_expired_queued_job_does_not_run_or_clear_a_newer_operation(): void
    {
        Cache::put('onu-operation-pending:'.$this->tenant->id, 'new-operation');
        $api = Mockery::mock(OltApiService::class);
        $api->shouldNotReceive('syncOlt');
        (new SyncTenantOnus($this->tenant->id, 'old-operation'))->handle($api);
        $this->assertSame('new-operation', Cache::get('onu-operation-pending:'.$this->tenant->id));
        $this->assertSame('failed', Cache::get('onu-operation-status:'.$this->tenant->id.':old-operation')['state']);
    }
}
