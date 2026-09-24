<?php

namespace Tests\Unit;

use App\Models\Server;
use App\Support\ServerHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServerHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(ServerHelper::CACHE_KEY);
    }

    public function test_it_identifies_private_and_public_ips_correctly(): void
    {
        $this->assertTrue(ServerHelper::isPrivateIp('10.70.0.2'));
        $this->assertTrue(ServerHelper::isPrivateIp('192.168.1.100'));
        $this->assertTrue(ServerHelper::isPrivateIp('172.16.0.5'));
        $this->assertTrue(ServerHelper::isPrivateIp('127.0.0.1'));
        $this->assertTrue(ServerHelper::isPrivateIp(null));

        $this->assertFalse(ServerHelper::isPrivateIp('103.59.177.138'));
        $this->assertFalse(ServerHelper::isPrivateIp('8.8.8.8'));
    }

    public function test_it_returns_database_primary_ip_when_valid_public(): void
    {
        Server::create([
            'name' => 'Primary Test Node',
            'hostname' => 'node1.test.com',
            'ip_address' => '103.59.177.138',
            'primary_ip' => '103.59.177.138',
            'status' => 'online',
        ]);

        $ip = ServerHelper::getPublicIp(true);
        $this->assertEquals('103.59.177.138', $ip);
    }

    public function test_it_auto_detects_ip_from_external_resolvers_when_db_is_empty_or_private(): void
    {
        Http::fake([
            'https://api.ipify.org' => Http::response('103.59.177.138', 200),
        ]);

        $ip = ServerHelper::getPublicIp(true);
        $this->assertEquals('103.59.177.138', $ip);
    }

    public function test_it_returns_local_private_ip(): void
    {
        $privateIp = ServerHelper::getPrivateIp();
        $this->assertNotEmpty($privateIp);
    }
}
