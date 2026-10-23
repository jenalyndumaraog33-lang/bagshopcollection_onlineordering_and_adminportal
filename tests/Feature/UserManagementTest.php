<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_management_index(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_customer_cannot_access_user_management_index(): void
    {
        $customer = User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->actingAs($customer)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_login_and_register_pages_load(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_profile_route_exists_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk();
    }

    public function test_capitalized_admin_role_is_still_recognized_as_admin(): void
    {
        $admin = User::factory()->create([
            'name' => 'Capitalized Admin',
            'email' => 'capitalized-admin@example.com',
            'role' => 'Admin',
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_admin_can_deactivate_a_customer_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $customer->createToken('existing client');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $customer), [
                'name' => $customer->name,
                'email' => $customer->email,
                'role' => User::ROLE_CUSTOMER,
                'is_active' => '0',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($customer->fresh()->is_active);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->actingAs($customer->fresh())
            ->get(route('profile.edit'))
            ->assertForbidden();
    }
}
