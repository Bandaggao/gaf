<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_their_own_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/profile', ['name' => 'Updated Admin']);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Updated Admin');
        $this->assertSame('Updated Admin', $admin->fresh()->name);
    }

    public function test_non_admin_cannot_update_admin_profile(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher, 'sanctum')
            ->putJson('/api/admin/profile', ['name' => 'Updated Name'])
            ->assertForbidden();
    }
}