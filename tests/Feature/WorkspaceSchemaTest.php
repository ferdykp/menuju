<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_records_have_owner_and_event_timezone(): void
    {
        $owner = User::create([
            'name' => 'Ayu',
            'email' => 'ayu@example.test',
            'password' => 'a-strong-password',
        ]);

        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Ayu & Bima',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => '2027-08-21',
        ]);

        $this->assertSame($owner->id, $workspace->owner->id);
        $this->assertSame('Asia/Jakarta', $workspace->timezone);
        $this->assertSame('2027-08-21', $workspace->wedding_date->toDateString());
    }
}
