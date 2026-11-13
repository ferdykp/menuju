<?php

namespace Tests\Feature;

use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_health_endpoint_reports_the_application_status(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('name', 'MENUJU')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', 'ok');
    }

    public function test_root_serves_the_vue_application_shell(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('MENUJU');
    }
}
