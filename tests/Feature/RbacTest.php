<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create();
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('Admin');
        }

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertStatus(200);
    }

    public function test_editor_cannot_access_admin_routes(): void
    {
        $editor = User::factory()->create();
        if (method_exists($editor, 'assignRole')) {
            $editor->assignRole('Editor');
        }

        $this->actingAs($editor)
            ->get('/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_user_cannot_access_editor_routes(): void
    {
        $user = User::factory()->create();
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('User');
        }

        $this->actingAs($user)
            ->get('/teacher/dashboard')
            ->assertStatus(403);
    }
}
