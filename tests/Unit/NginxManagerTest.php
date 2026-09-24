<?php

namespace Tests\Unit;

use App\Services\NginxManager;
use Tests\TestCase;

class NginxManagerTest extends TestCase
{
    public function test_nginx_manager_instance(): void
    {
        $manager = new NginxManager();
        $this->assertInstanceOf(NginxManager::class, $manager);
    }
}
